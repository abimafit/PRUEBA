<?php
include 'config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Mercancía - LogiMarket</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <img src="img/logo.png" alt="LogiMarket" class="logo">
            <h1>Distribuciones LogiMarket S.A.</h1>
            <p>Sistema de Control de Mercancía en Despachos</p>
        </header>

        <nav class="main-menu">
            <div class="menu-item">
                <a href="modules/productos.php">
                    <div class="menu-icon">📦</div>
                    <span>Registro de Productos</span>
                </a>
            </div>
            <div class="menu-item">
                <a href="modules/proveedores.php">
                    <div class="menu-icon">🏢</div>
                    <span>Proveedores</span>
                </a>
            </div>
            <div class="menu-item">
                <a href="modules/clientes.php">
                    <div class="menu-icon">👥</div>
                    <span>Clientes</span>
                </a>
            </div>
            <div class="menu-item">
                <a href="modules/entradas.php">
                    <div class="menu-icon">⬇️</div>
                    <span>Entradas de Mercancía</span>
                </a>
            </div>
            <div class="menu-item">
                <a href="modules/salidas.php">
                    <div class="menu-icon">⬆️</div>
                    <span>Despachos (Salidas)</span>
                </a>
            </div>
            <div class="menu-item">
                <a href="modules/ajustes.php">
                    <div class="menu-icon">⚖️</div>
                    <span>Ajustes de Inventario</span>
                </a>
            </div>
            <div class="menu-item">
                <a href="modules/reportes.php">
                    <div class="menu-icon">📊</div>
                    <span>Reportes</span>
                </a>
            </div>
        </nav>

        <footer>
            <p>&copy; 2025 Distribuciones LogiMarket S.A. - Sistema desarrollado para el control de inventario</p>
        </footer>
    </div>
</body>
</html>