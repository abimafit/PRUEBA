<?php
include '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Operaciones CRUD
$mensaje = '';
if ($_POST) {
    try {
        if ($_POST['action'] == 'create') {
            $query = "INSERT INTO productos SET codigo=?, descripcion=?, marca=?, unidad=?, costo=?, precio=?, itbms=?, comentario=?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_POST['codigo'],
                $_POST['descripcion'],
                $_POST['marca'],
                $_POST['unidad'],
                $_POST['costo'],
                $_POST['precio'],
                isset($_POST['itbms']) ? 1 : 0,
                $_POST['comentario']
            ]);
            $mensaje = "Producto agregado exitosamente!";
        }
        elseif ($_POST['action'] == 'update') {
            $query = "UPDATE productos SET codigo=?, descripcion=?, marca=?, unidad=?, costo=?, precio=?, itbms=?, comentario=? WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_POST['codigo'],
                $_POST['descripcion'],
                $_POST['marca'],
                $_POST['unidad'],
                $_POST['costo'],
                $_POST['precio'],
                isset($_POST['itbms']) ? 1 : 0,
                $_POST['comentario'],
                $_POST['id']
            ]);
            $mensaje = "Producto actualizado exitosamente!";
        }
    } catch(PDOException $exception) {
        if ($exception->getCode() == 23000) {
            $mensaje = "Error: El código del producto ya existe";
        } else {
            $mensaje = "Error: " . $exception->getMessage();
        }
    }
}

// Eliminar producto
if (isset($_GET['delete'])) {
    try {
        $query = "DELETE FROM productos WHERE id=?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_GET['delete']]);
        $mensaje = "Producto eliminado exitosamente!";
    } catch(PDOException $exception) {
        $mensaje = "Error: No se puede eliminar el producto porque tiene movimientos asociados";
    }
}

// Leer productos
$query = "SELECT * FROM productos ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener producto para editar
$producto_editar = null;
if (isset($_GET['edit'])) {
    $query = "SELECT * FROM productos WHERE id=?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['edit']]);
    $producto_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Productos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📦 Registro de Productos</h1>
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
            <h2><?php echo $producto_editar ? 'Editar Producto' : 'Agregar Nuevo Producto'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $producto_editar ? 'update' : 'create'; ?>">
                <?php if ($producto_editar): ?>
                <input type="hidden" name="id" value="<?php echo $producto_editar['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Código del Producto *:</label>
                    <input type="text" name="codigo" value="<?php echo $producto_editar ? $producto_editar['codigo'] : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Descripción *:</label>
                    <input type="text" name="descripcion" value="<?php echo $producto_editar ? $producto_editar['descripcion'] : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Marca:</label>
                    <input type="text" name="marca" value="<?php echo $producto_editar ? $producto_editar['marca'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Unidad de Medida:</label>
                    <input type="text" name="unidad" value="<?php echo $producto_editar ? $producto_editar['unidad'] : ''; ?>" placeholder="Ej: Unidad, Caja, Paquete, etc.">
                </div>
                
                <div class="form-group">
                    <label>Costo Unitario:</label>
                    <input type="number" step="0.01" name="costo" value="<?php echo $producto_editar ? $producto_editar['costo'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Precio de Venta:</label>
                    <input type="number" step="0.01" name="precio" value="<?php echo $producto_editar ? $producto_editar['precio'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="itbms" <?php echo $producto_editar && $producto_editar['itbms'] ? 'checked' : ''; ?>> 
                        Aplica ITBMS
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Comentario:</label>
                    <textarea name="comentario" rows="3"><?php echo $producto_editar ? $producto_editar['comentario'] : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn">
                    <?php echo $producto_editar ? 'Actualizar Producto' : 'Guardar Producto'; ?>
                </button>
                
                <?php if ($producto_editar): ?>
                <a href="productos.php" class="btn" style="background: #6c757d;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h2>Inventario de Productos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Marca</th>
                        <th>Unidad</th>
                        <th>Costo</th>
                        <th>Precio</th>
                        <th>ITBMS</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?php echo $producto['codigo']; ?></td>
                        <td><?php echo $producto['descripcion']; ?></td>
                        <td><?php echo $producto['marca']; ?></td>
                        <td><?php echo $producto['unidad']; ?></td>
                        <td>$<?php echo number_format($producto['costo'], 2); ?></td>
                        <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                        <td><?php echo $producto['itbms'] ? 'Sí' : 'No'; ?></td>
                        <td style="font-weight: bold; color: <?php echo $producto['stock_actual'] > 0 ? 'green' : 'red'; ?>;">
                            <?php echo $producto['stock_actual']; ?>
                        </td>
                        <td class="actions">
                            <a href="productos.php?edit=<?php echo $producto['id']; ?>" class="action-btn edit-btn">Editar</a>
                            <a href="productos.php?delete=<?php echo $producto['id']; ?>" class="action-btn delete-btn" onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>