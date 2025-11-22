<?php
include '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Filtros
$filtro_producto = isset($_GET['producto']) ? $_GET['producto'] : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Construir consulta con filtros
$query = "SELECT m.*, p.descripcion as producto, 
          CASE 
            WHEN m.tipo_cliente = 'cliente' THEN c.nombre 
            WHEN m.tipo_cliente = 'proveedor' THEN pr.nombre 
            ELSE '—' 
          END as contacto
          FROM movimientos m
          LEFT JOIN productos p ON m.producto_id = p.id
          LEFT JOIN clientes c ON m.cliente_proveedor_id = c.id AND m.tipo_cliente = 'cliente'
          LEFT JOIN proveedores pr ON m.cliente_proveedor_id = pr.id AND m.tipo_cliente = 'proveedor'
          WHERE 1=1";

$params = [];

if ($filtro_producto) {
    $query .= " AND m.producto_id = ?";
    $params[] = $filtro_producto;
}

if ($filtro_fecha_desde) {
    $query .= " AND m.fecha >= ?";
    $params[] = $filtro_fecha_desde;
}

if ($filtro_fecha_hasta) {
    $query .= " AND m.fecha <= ?";
    $params[] = $filtro_fecha_hasta;
}

if ($filtro_tipo) {
    $query .= " AND m.tipo = ?";
    $params[] = $filtro_tipo;
}

$query .= " ORDER BY m.fecha DESC, m.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos para filtro
$query_productos = "SELECT * FROM productos ORDER BY descripcion";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Exportar a CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_inventario_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Fecha', 'Movimiento', 'Producto', 'Cantidad', 'Contacto', 'Comentario'], ';');
    
    foreach ($movimientos as $mov) {
        $tipo = '';
        if ($mov['tipo'] == 'entrada') {
            $tipo = 'ENTRADA';
        } elseif ($mov['tipo'] == 'salida') {
            $tipo = 'SALIDA';
        } else {
            $tipo = 'AJUSTE';
        }
        
        fputcsv($output, [
            $mov['fecha'],
            $tipo,
            $mov['producto'],
            $mov['cantidad'],
            $mov['contacto'],
            $mov['comentario']
        ], ';');
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Inventario</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Reportes de Inventario</h1>
            <a href="../index.php" class="btn">← Volver al Inicio</a>
        </header>

        <div class="form-container">
            <h2>Filtros de Reporte</h2>
            <form method="GET">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="form-group">
                        <label>Producto:</label>
                        <select name="producto">
                            <option value="">Todos los productos</option>
                            <?php foreach ($productos as $producto): ?>
                            <option value="<?php echo $producto['id']; ?>" <?php echo $filtro_producto == $producto['id'] ? 'selected' : ''; ?>>
                                <?php echo $producto['descripcion']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Movimiento:</label>
                        <select name="tipo">
                            <option value="">Todos los tipos</option>
                            <option value="entrada" <?php echo $filtro_tipo == 'entrada' ? 'selected' : ''; ?>>Entradas</option>
                            <option value="salida" <?php echo $filtro_tipo == 'salida' ? 'selected' : ''; ?>>Salidas</option>
                            <option value="ajuste" <?php echo $filtro_tipo == 'ajuste' ? 'selected' : ''; ?>>Ajustes</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha Desde:</label>
                        <input type="date" name="fecha_desde" value="<?php echo $filtro_fecha_desde; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha Hasta:</label>
                        <input type="date" name="fecha_hasta" value="<?php echo $filtro_fecha_hasta; ?>">
                    </div>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn">Aplicar Filtros</button>
                    <a href="reportes.php" class="btn" style="background: #6c757d;">Limpiar Filtros</a>
                    <a href="reportes.php?<?php echo http_build_query($_GET); ?>&export=csv" class="btn" style="background: #28a745;">Exportar a CSV</a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h2>Movimientos de Inventario 
                <small style="font-size: 14px; color: #666;">
                    (<?php echo count($movimientos); ?> registros encontrados)
                </small>
            </h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Movimiento</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Cliente/Proveedor</th>
                        <th>Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientos)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
                            No se encontraron movimientos con los filtros aplicados.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($movimientos as $mov): ?>
                    <tr>
                        <td><?php echo $mov['fecha']; ?></td>
                        <td>
                            <?php 
                            $icon = '';
                            $color = '';
                            if ($mov['tipo'] == 'entrada') {
                                $icon = '⬇️ ENTRADA';
                                $color = 'green';
                            } elseif ($mov['tipo'] == 'salida') {
                                $icon = '⬆️ SALIDA';
                                $color = 'red';
                            } else {
                                $icon = '⚖️ AJUSTE';
                                $color = 'orange';
                            }
                            echo '<span style="color: ' . $color . '; font-weight: bold;">' . $icon . '</span>';
                            ?>
                        </td>
                        <td><?php echo $mov['producto']; ?></td>
                        <td style="font-weight: bold; color: <?php echo $mov['tipo'] == 'entrada' ? 'green' : ($mov['tipo'] == 'salida' ? 'red' : 'orange'); ?>;">
                            <?php 
                            if ($mov['tipo'] == 'entrada') {
                                echo '+' . $mov['cantidad'];
                            } elseif ($mov['tipo'] == 'salida') {
                                echo '-' . $mov['cantidad'];
                            } else {
                                echo ($mov['cantidad'] >= 0 ? '+' : '') . $mov['cantidad'];
                            }
                            ?>
                        </td>
                        <td><?php echo $mov['contacto']; ?></td>
                        <td><?php echo $mov['comentario']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>