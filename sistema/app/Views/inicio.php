<?php
/**
 * Vista de Inicio / Dashboard Principal
 * Sistema SISPE - CodeIgniter 4
 */

// Funcion para verificar sesion activa
$usuarioLogueado = session('logueado') === true;
$nombreUsuario = session('nombre') ?? 'Invitado';
$rolUsuario = session('rol') ?? 'cliente';

// Definir modulos del sistema
$modulos = [
    [
        'titulo' => 'Gestion de Personas',
        'descripcion' => 'Administra clientes, meseros, cocineros y administradores.',
        'enlace' => 'personas',
        'icono' => 'users'
    ],
    [
        'titulo' => 'Control de Mesas',
        'descripcion' => 'Gestiona disponibilidad, capacidad y ubicacion.',
        'enlace' => 'mesas',
        'icono' => 'table'
    ],
    [
        'titulo' => 'Menu Digital',
        'descripcion' => 'Administra productos, categorias y precios.',
        'enlace' => 'menu',
        'icono' => 'book'
    ],
    [
        'titulo' => 'Pedidos',
        'descripcion' => 'Controla pedidos en tiempo real.',
        'enlace' => 'pedidos',
        'icono' => 'clipboard'
    ],
    [
        'titulo' => 'Facturacion',
        'descripcion' => 'Genera facturas y gestiona pagos.',
        'enlace' => 'facturas',
        'icono' => 'receipt'
    ],
    [
        'titulo' => 'Productos',
        'descripcion' => 'Gestion y modificacion de productos.',
        'enlace' => 'productos',
        'icono' => 'box'
    ]
];

// Definir paneles por rol
$panelesPorRol = [
    [
        'titulo' => 'Panel Cocinero',
        'descripcion' => 'Gestiona pedidos, cambia estados y controla tiempos de preparacion.',
        'enlace' => 'cocina',
        'color' => '#e74c3c'
    ],
    [
        'titulo' => 'Panel Mesero',
        'descripcion' => 'Ve pedidos listos, recibe notificaciones y entrega a las mesas.',
        'enlace' => 'mesero',
        'color' => '#27ae60'
    ],
    [
        'titulo' => 'Menu Cliente',
        'descripcion' => 'Acceso digital al menu, realiza pedidos y ve el estado.',
        'enlace' => 'cliente',
        'color' => '#9b59b6'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Inicio - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; color: #fff; }
        nav { background: #0f0f1a; padding: 1rem 2rem; position: sticky; top: 0; z-index: 100; }
        .menu { display: flex; list-style: none; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .menu .logo img { height: 40px; }
        .menu li a { color: #fff; text-decoration: none; font-size: 0.95rem; transition: color 0.3s; }
        .menu li a:hover { color: #4fc3f7; }
        .menu .right { margin-left: auto; }
        .dropdown { position: relative; }
        .dropdown .submenu { display: none; position: absolute; background: #1a1a2e; min-width: 150px; border-radius: 8px; padding: 0.5rem 0; top: 100%; left: 0; }
        .dropdown:hover .submenu { display: block; }
        .dropdown .submenu li { padding: 0.5rem 1rem; }
        .hero { display: flex; justify-content: space-between; align-items: center; padding: 4rem 5%; flex-wrap: wrap; gap: 2rem; }
        .hero-contenido { max-width: 600px; }
        .hero-contenido h1 { font-size: 2.5rem; margin-bottom: 1rem; line-height: 1.3; }
        .hero-contenido p { color: #b0b0b0; font-size: 1.1rem; margin-bottom: 2rem; line-height: 1.6; }
        .botones { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn { padding: 0.8rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; transition: all 0.3s; }
        .btn-principal { background: #4fc3f7; color: #1a1a2e; }
        .btn-principal:hover { background: #29b6f6; transform: translateY(-2px); }
        .btn-secundario { background: transparent; color: #4fc3f7; border: 2px solid #4fc3f7; }
        .btn-secundario:hover { background: rgba(79, 195, 247, 0.1); }
        .hero-icono img { max-width: 300px; height: auto; }
        .modulos { padding: 4rem 5%; }
        .modulos h2 { text-align: center; margin-bottom: 3rem; font-size: 2rem; }
        .contenedor-modulos { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
        .card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; transition: transform 0.3s, box-shadow 0.3s; border-top: 4px solid #4fc3f7; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .card h3 { margin-bottom: 0.8rem; font-size: 1.2rem; }
        .card p { color: #b0b0b0; margin-bottom: 1rem; font-size: 0.95rem; line-height: 1.5; }
        .btn-card { display: inline-block; background: #4fc3f7; color: #1a1a2e; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: 600; }
        .btn-card:hover { background: #29b6f6; }
        .footer { background: #0f0f1a; padding: 2rem 5%; margin-top: 4rem; }
        .footer-contenido { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .footer-izq h3 { color: #4fc3f7; margin-bottom: 0.5rem; }
        .footer-izq p, .footer-der p { color: #888; font-size: 0.9rem; }
        @media (max-width: 768px) { .hero { flex-direction: column; text-align: center; } .hero-contenido { max-width: 100%; } .botones { justify-content: center; } }
    </style>
</head>
<body>
<nav>
    <ul class="menu">
        <li class="logo">
            <img src="<?= base_url('images/logo2.png') ?>" alt="Logo SISPE">
        </li>
        <li><a href="<?= base_url('/') ?>">Inicio</a></li>
        <?php 
        // Condicion: mostrar menu segun estado de sesion
        if ($usuarioLogueado): 
        ?>
            <li><a href="<?= base_url('personas') ?>">Personas</a></li>
            <li><a href="<?= base_url('mesas') ?>">Mesas</a></li>
            <li><a href="<?= base_url('menu') ?>">Menu</a></li>
            <li><a href="<?= base_url('productos') ?>">Productos</a></li>
            <li><a href="<?= base_url('facturas') ?>">Facturas</a></li>
            <li><a href="<?= base_url('pedidos') ?>">Pedidos</a></li>
            <li class="dropdown">
                <a href="#">Roles</a>
                <ul class="submenu">
                    <li><a href="<?= base_url('cocina') ?>">Cocinero</a></li>
                    <li><a href="<?= base_url('mesero') ?>">Mesero</a></li>
                    <li><a href="<?= base_url('cliente') ?>">Cliente</a></li>
                </ul>
            </li>
            <li class="right"><a href="<?= base_url('logout') ?>">Cerrar Sesion (<?= esc($nombreUsuario) ?>)</a></li>
        <?php else: ?>
            <li class="right"><a href="<?= base_url('/') ?>">Login</a></li>
            <li><a href="<?= base_url('registro') ?>">Registro</a></li>
        <?php endif; ?>
    </ul>
</nav>

<section class="hero">
    <div class="hero-contenido">
        <h1>Sistema de Gestion para Restaurantes</h1>
        <p>
            Administra tu restaurante de manera eficiente con SISPE.
            Control de mesas, pedidos, menu y facturacion en un solo lugar.
        </p>
        <div class="botones">
            <?php 
            // Condicion: mostrar boton segun estado de sesion
            if ($usuarioLogueado): 
            ?>
                <a href="<?= base_url('dashboard') ?>" class="btn btn-principal">Ir al Dashboard</a>
            <?php else: ?>
                <a href="<?= base_url('/') ?>" class="btn btn-principal">Ingresar al Sistema</a>
            <?php endif; ?>
            <a href="#modulos" class="btn btn-secundario">Conocer mas</a>
        </div>
    </div>
    <div class="hero-icono">
        <img src="<?= base_url('images/logo.png') ?>" alt="SISPE Logo">
    </div>
</section>

<section class="modulos" id="modulos">
    <h2>Modulos del Sistema</h2>
    <div class="contenedor-modulos">
        <?php 
        // Bucle: generar tarjetas de modulos
        foreach ($modulos as $index => $modulo): 
            // Condicion: verificar si el usuario tiene acceso al modulo
            $tieneAcceso = $usuarioLogueado || in_array($modulo['enlace'], ['menu', 'cliente']);
        ?>
            <div class="card">
                <h3><?= esc($modulo['titulo']) ?></h3>
                <p><?= esc($modulo['descripcion']) ?></p>
                <?php if ($tieneAcceso): ?>
                    <a href="<?= base_url($modulo['enlace']) ?>" class="btn-card">Ver mas</a>
                <?php else: ?>
                    <a href="<?= base_url('/') ?>" class="btn-card">Iniciar sesion</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="modulos">
    <h2>Acceso por Roles</h2>
    <div class="contenedor-modulos">
        <?php 
        // Bucle: generar tarjetas de paneles por rol
        foreach ($panelesPorRol as $panel): 
        ?>
            <div class="card" style="border-top: 4px solid <?= esc($panel['color']) ?>;">
                <h3><?= esc($panel['titulo']) ?></h3>
                <p><?= esc($panel['descripcion']) ?></p>
                <?php 
                // Condicion: verificar acceso segun rol
                if ($usuarioLogueado): 
                ?>
                    <a href="<?= base_url($panel['enlace']) ?>" class="btn-card">Acceder</a>
                <?php else: ?>
                    <a href="<?= base_url('/') ?>" class="btn-card">Iniciar sesion</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<footer class="footer">
    <div class="footer-contenido">
        <div class="footer-izq">
            <h3>SISPE</h3>
            <p>Sistema de Gestion para Restaurantes</p>
        </div>
        <div class="footer-der">
            <p><?= date('Y') ?> SISPE. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>
</body>
</html>
