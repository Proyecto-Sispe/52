<?php
/**
 * Vista del Menu de Comidas
 * Sistema SISPE - CodeIgniter 4
 */

// Obtener datos de sesion
$usuarioLogueado = session('logueado') === true;
$rolUsuario = session('rol') ?? 'cliente';

// Datos del menu organizados por categoria
$menuPorCategoria = $menuPorCategoria ?? [
    'Hamburguesas' => [
        ['id' => 1, 'nombre' => 'Hamburguesa Clasica', 'descripcion' => 'Carne, lechuga, tomate y queso', 'precio' => 15000],
        ['id' => 2, 'nombre' => 'Hamburguesa Doble', 'descripcion' => 'Doble carne con queso', 'precio' => 20000]
    ],
    'Perros Calientes' => [
        ['id' => 3, 'nombre' => 'Perro Sencillo', 'descripcion' => 'Pan Salchicha Salsa y Papas', 'precio' => 15000],
        ['id' => 4, 'nombre' => 'Perro Grande', 'descripcion' => 'Pan Salchicha Salsa Papas Queso', 'precio' => 20000]
    ],
    'Gaseosas' => [
        ['id' => 5, 'nombre' => 'Coca-Cola', 'descripcion' => 'Coca-Cola Zero', 'precio' => 5000],
        ['id' => 6, 'nombre' => 'Coca-Cola', 'descripcion' => 'Coca-Cola Clasica', 'precio' => 4000]
    ]
];

// Funcion para formatear precio
$formatearPrecio = function(int $precio): string {
    return '$' . number_format($precio, 0, ',', '.');
};

// Funcion para contar total de productos
$contarProductos = function(array $menu): int {
    $total = 0;
    foreach ($menu as $categoria => $productos) {
        $total += count($productos);
    }
    return $total;
};

$totalProductos = $contarProductos($menuPorCategoria);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= base_url('images/logo2.png') ?>" type="image/png">
    <title>Menu - SISPE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; color: #fff; }
        nav { background: #0f0f1a; padding: 1rem 2rem; }
        .menu { display: flex; list-style: none; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .menu .logo img { height: 40px; }
        .menu li a { color: #fff; text-decoration: none; font-size: 0.95rem; transition: color 0.3s; }
        .menu li a:hover { color: #4fc3f7; }
        .menu .right { margin-left: auto; }
        .hero { display: flex; justify-content: space-between; align-items: center; padding: 2rem 5%; flex-wrap: wrap; gap: 1rem; }
        .hero-texto h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .hero-texto p { color: #b0b0b0; }
        .hero-boton .btn { display: inline-block; background: #4fc3f7; color: #1a1a2e; padding: 0.8rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .menu-comida { padding: 2rem 5%; }
        .menu-comida > h2 { text-align: center; margin-bottom: 2rem; font-size: 1.8rem; color: #4fc3f7; }
        .categoria { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .categoria h3 { color: #4fc3f7; margin-bottom: 1rem; font-size: 1.4rem; border-bottom: 2px solid #4fc3f7; padding-bottom: 0.5rem; }
        .items { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .item { background: rgba(255,255,255,0.03); border-radius: 8px; padding: 1.2rem; transition: transform 0.3s; }
        .item:hover { transform: translateY(-3px); background: rgba(255,255,255,0.06); }
        .item h4 { color: #fff; margin-bottom: 0.5rem; font-size: 1.1rem; }
        .item .desc { color: #b0b0b0; font-size: 0.9rem; margin-bottom: 0.8rem; }
        .item .precio { color: #4fc3f7; font-size: 1.2rem; font-weight: 700; }
        .item .btn-agregar { display: inline-block; background: #27ae60; color: #fff; padding: 0.4rem 0.8rem; border-radius: 4px; text-decoration: none; font-size: 0.85rem; margin-top: 0.5rem; cursor: pointer; border: none; }
        .item .btn-agregar:hover { background: #2ecc71; }
        .stats-menu { display: flex; gap: 1rem; justify-content: center; margin-bottom: 2rem; flex-wrap: wrap; }
        .stat-menu { background: rgba(79, 195, 247, 0.1); padding: 0.6rem 1.2rem; border-radius: 20px; font-size: 0.9rem; }
        .stat-menu strong { color: #4fc3f7; }
    </style>
</head>
<body>
<nav>
    <ul class="menu">
        <li class="logo">
            <img src="<?= base_url('images/logo2.png') ?>" alt="Logo SISPE">
        </li>
        <li><a href="<?= base_url('inicio') ?>">Inicio</a></li>
        <li><a href="<?= base_url('personas') ?>">Personas</a></li>
        <li><a href="<?= base_url('mesas') ?>">Mesas</a></li>
        <li><a href="<?= base_url('mesas/gestion') ?>">Gestion de Mesas</a></li>
        <li><a href="<?= base_url('menu') ?>">Menu</a></li>
        <li><a href="<?= base_url('productos') ?>">Productos</a></li>
        <li><a href="<?= base_url('facturas') ?>">Facturas</a></li>
        <li><a href="<?= base_url('pedidos') ?>">Pedidos</a></li>
        <?php if ($usuarioLogueado): ?>
            <li class="right"><a href="<?= base_url('logout') ?>">Cerrar Sesion</a></li>
        <?php else: ?>
            <li class="right"><a href="<?= base_url('/') ?>">Login</a></li>
            <li><a href="<?= base_url('registro') ?>">Registro</a></li>
        <?php endif; ?>
    </ul>
</nav>

<section class="hero">
    <div class="hero-texto">
        <h1>Menu Digital</h1>
        <p>Administra la informacion de clientes, empleados y administradores</p>
    </div>
    <div class="hero-boton">
        <?php 
        // Condicion: solo admin puede agregar productos al menu
        if ($rolUsuario === 'admin'): 
        ?>
            <a href="<?= base_url('productos/agregar') ?>" class="btn">+ Agregar Producto</a>
        <?php endif; ?>
    </div>
</section>

<section class="menu-comida">
    <h2>Menu de Comida Rapida</h2>
    
    <!-- Estadisticas del menu -->
    <div class="stats-menu">
        <div class="stat-menu"><strong><?= $totalProductos ?></strong> Productos</div>
        <div class="stat-menu"><strong><?= count($menuPorCategoria) ?></strong> Categorias</div>
    </div>
    
    <?php 
    // Bucle: mostrar cada categoria con sus productos
    foreach ($menuPorCategoria as $categoria => $productos): 
    ?>
        <div class="categoria">
            <h3><?= esc($categoria) ?></h3>
            <div class="items">
                <?php 
                // Bucle: mostrar cada producto de la categoria
                foreach ($productos as $producto): 
                ?>
                    <div class="item">
                        <h4><?= esc($producto['nombre']) ?></h4>
                        <p class="desc"><?= esc($producto['descripcion']) ?></p>
                        <p class="precio"><?= $formatearPrecio($producto['precio']) ?></p>
                        <?php 
                        // Condicion: mostrar boton de agregar solo si hay sesion
                        if ($usuarioLogueado): 
                        ?>
                            <button class="btn-agregar" onclick="agregarAlPedido(<?= $producto['id'] ?>, '<?= esc($producto['nombre']) ?>', <?= $producto['precio'] ?>)">
                                Agregar al pedido
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<script>
// Array para almacenar el pedido actual
let pedidoActual = [];

// Funcion para agregar producto al pedido
function agregarAlPedido(id, nombre, precio) {
    // Buscar si el producto ya existe en el pedido
    let existente = false;
    
    // Bucle: verificar productos existentes
    for (let i = 0; i < pedidoActual.length; i++) {
        // Condicion: si existe, incrementar cantidad
        if (pedidoActual[i].id === id) {
            pedidoActual[i].cantidad++;
            existente = true;
            break;
        }
    }
    
    // Condicion: si no existe, agregar nuevo
    if (!existente) {
        pedidoActual.push({
            id: id,
            nombre: nombre,
            precio: precio,
            cantidad: 1
        });
    }
    
    // Calcular total
    let total = 0;
    for (let i = 0; i < pedidoActual.length; i++) {
        total += pedidoActual[i].precio * pedidoActual[i].cantidad;
    }
    
    alert('Producto agregado: ' + nombre + '\nTotal actual: $' + total.toLocaleString());
}
</script>
</body>
</html>
