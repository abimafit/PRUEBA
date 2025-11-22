<?php
include '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Operaciones CRUD
$mensaje = '';
if ($_POST) {
    try {
        if ($_POST['action'] == 'create') {
            // Registrar entrada
            $query = "INSERT INTO movimientos SET tipo='entrada', producto_id=?, cantidad=?, fecha=?, cliente_proveedor_id=?, tipo_cliente='proveedor', comentario=?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_POST['producto_id'],
                $_POST['cantidad'],
                $_POST['fecha'],
                $_POST['proveedor_id'],
                $_POST['comentario']
            ]);
            
            // Actualizar stock del producto
            $query = "UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_POST['cantidad'], $_POST['producto_id']]);
            
            $mensaje = "Entrada de mercancía registrada exitosamente!";
        }
    } catch(PDOException $exception) {
        $mensaje = "Error: " . $exception->getMessage();
    }
}

// Obtener productos y proveedores para los selects
$query_productos = "SELECT * FROM productos ORDER BY descripcion";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

$query_proveedores = "SELECT * FROM proveedores ORDER BY nombre";
$stmt_proveedores = $db->prepare($query_proveedores);
$stmt_proveedores->execute();
$proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

// Leer entradas recientes
$query = "SELECT m.*, p.descripcion as producto, pr.nombre as proveedor 
          FROM movimientos m 
          LEFT JOIN productos p ON m.producto_id = p.id 
          LEFT JOIN proveedores pr ON m.cliente_proveedor_id = pr.id 
          WHERE m.tipo = 'entrada' 
          ORDER BY m.fecha DESC, m.id DESC 
          LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute();
$entradas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Entradas de Mercancía</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>⬇️ Entradas de Mercancía</h1>
            <a href="../index.php" class="btn">← Volver al Inicio</a>
        </header>

        <?php if ($mensaje): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px;">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Registrar Nueva Entrada</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Producto *:</label>
                    <select name="producto_id" required>
                        <option value="">Seleccionar Producto</option>
                        <?php foreach ($productos as $producto): ?>
                        <option value="<?php echo $producto['id']; ?>">
                            <?php echo $producto['descripcion'] . ' (' . $producto['codigo'] . ') - Stock: ' . $producto['stock_actual']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Proveedor *:</label>
                    <select name="proveedor_id" required>
                        <option value="">Seleccionar Proveedor</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                        <option value="<?php echo $proveedor['id']; ?>"><?php echo $proveedor['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Cantidad *:</label>
                    <input type="number" name="cantidad" min="1" required>
                </div>
                
                <div class="form-group">
                    <label>Fecha *:</label>
                    <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Comentario:</label>
                    <textarea name="comentario" rows="3" placeholder="Ej: Compra mensual, Pedido especial, etc."></textarea>
                </div>
                
                <button type="submit" class="btn">Registrar Entrada</button>
            </form>
        </div>

        <div class="table-container">
            <h2>Historial de Entradas Recientes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Proveedor</th>
                        <th>Cantidad</th>
                        <th>Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entradas as $entrada): ?>
                    <tr>
                        <td><?php echo $entrada['fecha']; ?></td>
                        <td><?php echo $entrada['producto']; ?></td>
                        <td><?php echo $entrada['proveedor']; ?></td>
                        <td style="color: green; font-weight: bold;">+<?php echo $entrada['cantidad']; ?></td>
                        <td><?php echo $entrada['comentario']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>