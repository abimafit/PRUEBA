<?php
include '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Operaciones CRUD
$mensaje = '';
if ($_POST) {
    try {
        if ($_POST['action'] == 'create') {
            // Registrar ajuste
            $query = "INSERT INTO movimientos SET tipo='ajuste', producto_id=?, cantidad=?, fecha=?, comentario=?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_POST['producto_id'],
                $_POST['cantidad'],
                $_POST['fecha'],
                $_POST['comentario']
            ]);
            
            // Actualizar stock del producto (ajuste puede ser positivo o negativo)
            $query = "UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$_POST['cantidad'], $_POST['producto_id']]);
            
            $tipo_ajuste = $_POST['cantidad'] >= 0 ? 'positivo' : 'negativo';
            $mensaje = "Ajuste {$tipo_ajuste} registrado exitosamente!";
        }
    } catch(PDOException $exception) {
        $mensaje = "Error: " . $exception->getMessage();
    }
}

// Obtener productos para el select
$query_productos = "SELECT * FROM productos ORDER BY descripcion";
$stmt_productos = $db->prepare($query_productos);
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

// Leer ajustes recientes
$query = "SELECT m.*, p.descripcion as producto 
          FROM movimientos m 
          LEFT JOIN productos p ON m.producto_id = p.id 
          WHERE m.tipo = 'ajuste' 
          ORDER BY m.fecha DESC, m.id DESC 
          LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute();
$ajustes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ajustes de Inventario</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>⚖️ Ajustes de Inventario</h1>
            <a href="../index.php" class="btn">← Volver al Inicio</a>
        </header>

        <?php if ($mensaje): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px;">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <h2>Registrar Nuevo Ajuste</h2>
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
                    <label>Tipo de Ajuste *:</label>
                    <select name="tipo_ajuste" required id="tipo_ajuste">
                        <option value="positivo">Ajuste Positivo (+)</option>
                        <option value="negativo">Ajuste Negativo (-)</option>
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
                    <label>Comentario *:</label>
                    <textarea name="comentario" rows="3" placeholder="Ej: Ajuste por conteo físico, Producto dañado, Diferencia en inventario, etc." required></textarea>
                </div>
                
                <button type="submit" class="btn">Registrar Ajuste</button>
            </form>
        </div>

        <div class="table-container">
            <h2>Historial de Ajustes Recientes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ajustes as $ajuste): ?>
                    <tr>
                        <td><?php echo $ajuste['fecha']; ?></td>
                        <td><?php echo $ajuste['producto']; ?></td>
                        <td>
                            <?php if ($ajuste['cantidad'] >= 0): ?>
                                <span style="color: green; font-weight: bold;">➕ Positivo</span>
                            <?php else: ?>
                                <span style="color: red; font-weight: bold;">➖ Negativo</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold;">
                            <?php echo $ajuste['cantidad'] >= 0 ? '+' . $ajuste['cantidad'] : $ajuste['cantidad']; ?>
                        </td>
                        <td><?php echo $ajuste['comentario']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        document.getElementById('producto_select').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var stock = selectedOption.getAttribute('data-stock');
            document.getElementById('stock_info').textContent = 'Stock actual: ' + stock;
        });

        document.getElementById('tipo_ajuste').addEventListener('change', function() {
            var cantidadInput = document.getElementById('cantidad_input');
            if (this.value === 'negativo') {
                cantidadInput.setAttribute('min', '-1000');
            } else {
                cantidadInput.setAttribute('min', '1');
            }
        });
        </script>
    </div>
</body>
</html>