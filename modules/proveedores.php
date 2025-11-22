<?php
include '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Operaciones CRUD
$mensaje = '';
if ($_POST) {
    try {
        if ($_POST['action'] == 'create') {
            $query = "INSERT INTO proveedores SET nombre=?, ruc=?, telefono=?, direccion=?, email=?, comentario=?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_POST['nombre'],
                $_POST['ruc'],
                $_POST['telefono'],
                $_POST['direccion'],
                $_POST['email'],
                $_POST['comentario']
            ]);
            $mensaje = "Proveedor agregado exitosamente!";
        }
        elseif ($_POST['action'] == 'update') {
            $query = "UPDATE proveedores SET nombre=?, ruc=?, telefono=?, direccion=?, email=?, comentario=? WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $_POST['nombre'],
                $_POST['ruc'],
                $_POST['telefono'],
                $_POST['direccion'],
                $_POST['email'],
                $_POST['comentario'],
                $_POST['id']
            ]);
            $mensaje = "Proveedor actualizado exitosamente!";
        }
    } catch(PDOException $exception) {
        $mensaje = "Error: " . $exception->getMessage();
    }
}

// Eliminar proveedor
if (isset($_GET['delete'])) {
    $query = "DELETE FROM proveedores WHERE id=?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['delete']]);
    $mensaje = "Proveedor eliminado exitosamente!";
}

// Leer proveedores
$query = "SELECT * FROM proveedores ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener proveedor para editar
$proveedor_editar = null;
if (isset($_GET['edit'])) {
    $query = "SELECT * FROM proveedores WHERE id=?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['edit']]);
    $proveedor_editar = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Proveedores</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🏢 Gestión de Proveedores</h1>
            <a href="../index.php" class="btn">← Volver al Inicio</a>
        </header>

        <?php if ($mensaje): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px;">
            <?php echo $mensaje; ?>
        </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?php echo $proveedor_editar ? 'Editar Proveedor' : 'Agregar Nuevo Proveedor'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $proveedor_editar ? 'update' : 'create'; ?>">
                <?php if ($proveedor_editar): ?>
                <input type="hidden" name="id" value="<?php echo $proveedor_editar['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Nombre del Proveedor *:</label>
                    <input type="text" name="nombre" value="<?php echo $proveedor_editar ? $proveedor_editar['nombre'] : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>RUC:</label>
                    <input type="text" name="ruc" value="<?php echo $proveedor_editar ? $proveedor_editar['ruc'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" name="telefono" value="<?php echo $proveedor_editar ? $proveedor_editar['telefono'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Dirección:</label>
                    <textarea name="direccion" rows="2"><?php echo $proveedor_editar ? $proveedor_editar['direccion'] : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?php echo $proveedor_editar ? $proveedor_editar['email'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Comentario:</label>
                    <textarea name="comentario" rows="3"><?php echo $proveedor_editar ? $proveedor_editar['comentario'] : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn">
                    <?php echo $proveedor_editar ? 'Actualizar Proveedor' : 'Guardar Proveedor'; ?>
                </button>
                
                <?php if ($proveedor_editar): ?>
                <a href="proveedores.php" class="btn" style="background: #6c757d;">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h2>Lista de Proveedores</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>RUC</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proveedores as $proveedor): ?>
                    <tr>
                        <td><?php echo $proveedor['id']; ?></td>
                        <td><?php echo $proveedor['nombre']; ?></td>
                        <td><?php echo $proveedor['ruc']; ?></td>
                        <td><?php echo $proveedor['telefono']; ?></td>
                        <td><?php echo $proveedor['email']; ?></td>
                        <td><?php echo substr($proveedor['direccion'], 0, 50) . '...'; ?></td>
                        <td class="actions">
                            <a href="proveedores.php?edit=<?php echo $proveedor['id']; ?>" class="action-btn edit-btn">Editar</a>
                            <a href="proveedores.php?delete=<?php echo $proveedor['id']; ?>" class="action-btn delete-btn" onclick="return confirm('¿Estás seguro de eliminar este proveedor?')">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>