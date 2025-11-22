<?php
include '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Operaciones CRUD
$mensaje = '';
if ($_POST) {
    try {
        if ($_POST['action'] == 'create') {
            // Verificar stock disponible
            $query_stock = "SELECT stock_actual FROM productos WHERE id = ?";
            $stmt_stock = $db->prepare($query_stock);
            $stmt_stock->execute([$_POST['producto_id']]);
            $stock_actual = $stmt_stock->fetchColumn();
            
            if ($stock_actual >= $_POST['cantidad']) {
                // Registrar salida
                $query = "INSERT INTO movimientos SET tipo='salida', producto_id=?, cantidad=?, fecha=?, cliente_proveedor_id=?, tipo_cliente='cliente', comentario=?";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $_POST['producto_id'],
                    $_POST['cantidad'],
                    $_POST['fecha'],
                    $_POST['cliente_id'],
                    $_POST['comentario']
                ]);
                
                // Actualizar stock del producto
                $query = "UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$_POST['cantidad'], $_POST['producto_id']]);
                
                $mensaje = "Salida de mercancía registrada exitosamente!";
            } else {
                $mensaje = "Error: Stock insuficiente. Stock disponible: " . $stock_actual;
            }
        }
    } catch(PDOException $exception) {
        $mensaje = "Error: " . $exception->getMessage();
    }
}

// Obtener productos y clientes para los selects
$query_productos = "SELECT * FROM productos ORDER BY descripcion";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

$query_clientes = "SELECT * FROM clientes ORDER BY nombre";
$stmt_clientes = $db->prepare($query_clientes);
$stmt_clientes->execute();
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Leer salidas recientes
$query = "SELECT m.*, p.descripcion as producto, c.nombre as cliente 
          FROM movimientos m 
          LEFT JOIN productos p ON m.producto_id = p.id 
          LEFT JOIN clientes c ON m.cliente_proveedor_id = c.id 
          WHERE m.tipo = 'salida' 
          ORDER BY m.fecha DESC, m.id DESC 
          LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute();
$salidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Salidas de Mercancía</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>⬆️ Salidas de Mercancía</h1>
            <a href="../index.php" class="btn">← Volver al Inicio</a>
        </header>

        <?php if ($mensaje): ?>
        <div style="background: <?php echo strpos($mensaje, 'Error:') !== false ? '#f8d7da' : '#d4edda'; ?>; 
                    color: <?php echo strpos($mensaje, 'Error:') !== false ? '#721c24' : '#155724'; ?>; 
                    padding: 15px; margin: 20px; border-radius: 5px;">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Registrar Nueva Salida</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label>Producto *:</label>
                    <select name="producto_id" required id="producto_select">
                        <option value="">Seleccionar Producto</option>
                        <?php foreach ($productos as $producto): ?>
                        <option value="<?php echo $producto['id']; ?>" data-stock="<?php echo $producto['stock_actual']; ?>">
                            <?php echo $producto['descripcion'] . ' (' . $producto['codigo'] . ') - Stock: ' . $producto['stock_actual']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Cliente *:</label>
                    <select name="cliente_id" required>
                        <option value="">Seleccionar Cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                        <option value="<?php echo $cliente['id']; ?>"><?php echo $cliente['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Cantidad *:</label>
                    <input type="number" name="cantidad" min="1" required id="cantidad_input">
                    <small id="stock_info" style="color: #666; display: block; margin-top: 5px;"></small>
                </div>
                
                <div class="form-group">
                    <label>Fecha *:</label>
                    <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Comentario:</label>
                    <textarea name="comentario" rows="3" placeholder="Ej: Pedido del cliente, Venta directa, etc."></textarea>
                </div>
                
                <button type="submit" class="btn">Registrar Salida</button>
            </form>
        </div>

        <div class="table-container">
            <h2>Historial de Salidas Recientes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Cliente</th>
                        <th>Cantidad</th>
                        <th>Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salidas as $salida): ?>
                    <tr>
                        <td><?php echo $salida['fecha']; ?></td>
                        <td><?php echo $salida['producto']; ?></td>
                        <td><?php echo $salida['cliente']; ?></td>
                        <td style="color: red; font-weight: bold;">-<?php echo $salida['cantidad']; ?></td>
                        <td><?php echo $salida['comentario']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        document.getElementById('producto_select').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var stock = selectedOption.getAttribute('data-stock');
            document.getElementById('stock_info').textContent = 'Stock disponible: ' + stock;
        });

        document.getElementById('cantidad_input').addEventListener('input', function() {
            var selectedOption = document.getElementById('producto_select').options[document.getElementById('producto_select').selectedIndex];
            var stock = parseInt(selectedOption.getAttribute('data-stock'));
            var cantidad = parseInt(this.value);
            
            if (cantidad > stock) {
                this.style.borderColor = 'red';
            } else {
                this.style.borderColor = '#e9ecef';
            }
        });
        </script>
    </div>
</body>
</html>