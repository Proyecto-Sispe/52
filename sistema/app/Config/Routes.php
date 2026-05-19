<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Configuracion de Rutas del Sistema SISPE
 * 
 * Definicion de todas las rutas de la aplicacion
 * siguiendo el patron MVC de CodeIgniter 4
 * 
 * Funciones PHP utilizadas:
 * - Funciones anonimas para verificacion de permisos
 * - Condiciones para rutas condicionales
 * - Arrays para agrupar rutas por modulo
 */

/** @var RouteCollection $routes */

// ==========================================
// FUNCIONES DE VERIFICACION (Operaciones)
// ==========================================

/**
 * Funcion para verificar si el usuario tiene sesion activa
 * @return bool
 */
$verificarSesion = function (): bool {
    return session('logueado') === true;
};

/**
 * Funcion para verificar si el usuario es administrador
 * @return bool
 */
$verificarAdmin = function (): bool {
    return session('rol') === 'admin';
};

/**
 * Funcion para verificar roles permitidos
 * @param array $rolesPermitidos
 * @return bool
 */
$verificarRol = function (array $rolesPermitidos): bool {
    $rolUsuario = session('rol') ?? '';
    return in_array($rolUsuario, $rolesPermitidos);
};

/**
 * Array de roles permitidos por tipo de acceso
 */
$rolesPermitidos = [
    'admin' => ['admin'],
    'staff' => ['admin', 'mesero', 'cocinero'],
    'ventas' => ['admin', 'mesero'],
    'todos' => ['admin', 'mesero', 'cocinero', 'cliente', 'mesa', 'aprendiz']
];

// ==========================================
// RUTAS PUBLICAS (Sin autenticacion)
// ==========================================

// Pagina principal - Login
$routes->get('/', 'Home::index', ['as' => 'home']);

// Pagina de inicio publica
$routes->get('/inicio', 'Home::inicio', ['as' => 'inicio']);

// Formulario de registro
$routes->get('/registro', 'Home::registrar', ['as' => 'registro']);

// Procesar registro (POST)
$routes->post('/guardar', 'Home::guardar', ['as' => 'guardar_usuario']);

// Procesar login (POST)
$routes->post('/login', 'Home::login', ['as' => 'login']);

// ==========================================
// RUTAS PROTEGIDAS (Requieren autenticacion)
// ==========================================

// Dashboard principal
$routes->get('/dashboard', 'Home::dashboard', ['as' => 'dashboard']);

// Cerrar sesion
$routes->get('/logout', 'Home::logout', ['as' => 'logout']);

// ==========================================
// RUTAS DE GESTION DE USUARIOS
// ==========================================

// Listar usuarios (solo admin)
$routes->get('/usuarios', 'Home::usuarios', ['as' => 'usuarios']);

// Editar usuario
$routes->get('/editar/(:num)', 'Home::editar/$1', ['as' => 'editar_usuario']);

// Actualizar usuario (POST)
$routes->post('/actualizar/(:num)', 'Home::actualizar/$1', ['as' => 'actualizar_usuario']);

// Eliminar usuario
$routes->get('/eliminar/(:num)', 'Home::eliminar/$1', ['as' => 'eliminar_usuario']);

// ==========================================
// RUTAS DE PERSONAS
// ==========================================

$routes->group('personas', static function ($routes) {
    // Listar personas
    $routes->get('/', 'Persona::index', ['as' => 'listar_personas']);
    
    // Formulario agregar persona
    $routes->get('agregar', 'Persona::agregar', ['as' => 'agregar_persona']);
    
    // Guardar persona (POST)
    $routes->post('guardar', 'Persona::guardar', ['as' => 'guardar_persona']);
    
    // Editar persona
    $routes->get('editar/(:num)', 'Persona::editar/$1', ['as' => 'editar_persona']);
    
    // Actualizar persona (POST)
    $routes->post('actualizar/(:num)', 'Persona::actualizar/$1', ['as' => 'actualizar_persona']);
    
    // Eliminar persona
    $routes->get('eliminar/(:num)', 'Persona::eliminar/$1', ['as' => 'eliminar_persona']);
});

// ==========================================
// RUTAS DE MESAS
// ==========================================

$routes->group('mesas', static function ($routes) {
    // Vista de tarjetas de mesas
    $routes->get('/', 'Mesa::index', ['as' => 'listar_mesas']);
    
    // Vista de gestion (tabla administrativa)
    $routes->get('gestion', 'Mesa::gestion', ['as' => 'gestion_mesas']);
    
    // Formulario agregar mesa
    $routes->get('agregar', 'Mesa::agregar', ['as' => 'agregar_mesa']);
    
    // Guardar mesa (POST)
    $routes->post('guardar', 'Mesa::guardar', ['as' => 'guardar_mesa']);
    
    // Editar mesa
    $routes->get('editar/(:num)', 'Mesa::editar/$1', ['as' => 'editar_mesa']);
    
    // Actualizar mesa (POST)
    $routes->post('actualizar/(:num)', 'Mesa::actualizar/$1', ['as' => 'actualizar_mesa']);
    
    // Eliminar mesa
    $routes->get('eliminar/(:num)', 'Mesa::eliminar/$1', ['as' => 'eliminar_mesa']);
    
    // Cambiar estado de mesa
    $routes->get('estado/(:num)/(:alpha)', 'Mesa::cambiarEstado/$1/$2', ['as' => 'cambiar_estado_mesa']);
});

// ==========================================
// RUTAS DE MENU
// ==========================================

$routes->get('/menu', 'Menu::index', ['as' => 'menu']);

// ==========================================
// RUTAS DE PRODUCTOS
// ==========================================

$routes->group('productos', static function ($routes) {
    // Listar productos
    $routes->get('/', 'Producto::index', ['as' => 'listar_productos']);
    
    // Formulario agregar producto
    $routes->get('agregar', 'Producto::agregar', ['as' => 'agregar_producto']);
    
    // Guardar producto (POST)
    $routes->post('guardar', 'Producto::guardar', ['as' => 'guardar_producto']);
    
    // Editar producto
    $routes->get('editar/(:num)', 'Producto::editar/$1', ['as' => 'editar_producto']);
    
    // Actualizar producto (POST)
    $routes->post('actualizar/(:num)', 'Producto::actualizar/$1', ['as' => 'actualizar_producto']);
    
    // Eliminar producto
    $routes->get('eliminar/(:num)', 'Producto::eliminar/$1', ['as' => 'eliminar_producto']);
});

// ==========================================
// RUTAS DE PEDIDOS
// ==========================================

$routes->group('pedidos', static function ($routes) {
    // Listar pedidos
    $routes->get('/', 'Pedido::index', ['as' => 'listar_pedidos']);
    
    // Formulario agregar pedido
    $routes->get('agregar', 'Pedido::agregar', ['as' => 'agregar_pedido']);
    
    // Guardar pedido (POST)
    $routes->post('guardar', 'Pedido::guardar', ['as' => 'guardar_pedido']);
    
    // Ver detalle de pedido
    $routes->get('ver/(:num)', 'Pedido::ver/$1', ['as' => 'ver_pedido']);
    
    // Cambiar estado de pedido
    $routes->get('cambiar-estado/(:num)', 'Pedido::cambiarEstado/$1', ['as' => 'cambiar_estado_pedido']);
    
    // Cancelar pedido
    $routes->post('cancelar/(:num)', 'Pedido::cancelar/$1', ['as' => 'cancelar_pedido']);
});

// ==========================================
// RUTAS DE FACTURAS
// ==========================================

$routes->group('facturas', static function ($routes) {
    // Listar facturas
    $routes->get('/', 'Factura::index', ['as' => 'listar_facturas']);
    
    // Formulario agregar factura
    $routes->get('agregar', 'Factura::agregar', ['as' => 'agregar_factura']);
    
    // Guardar factura (POST)
    $routes->post('guardar', 'Factura::guardar', ['as' => 'guardar_factura']);
    
    // Editar factura
    $routes->get('editar/(:num)', 'Factura::editar/$1', ['as' => 'editar_factura']);
    
    // Actualizar factura (POST)
    $routes->post('actualizar/(:num)', 'Factura::actualizar/$1', ['as' => 'actualizar_factura']);
    
    // Eliminar factura
    $routes->get('eliminar/(:num)', 'Factura::eliminar/$1', ['as' => 'eliminar_factura']);
    
    // Imprimir factura
    $routes->get('imprimir/(:num)', 'Factura::imprimir/$1', ['as' => 'imprimir_factura']);
});

// ==========================================
// RUTAS POR ROL (Vistas especificas)
// ==========================================

// Vista del cocinero
$routes->get('/cocina', 'Cocina::index', ['as' => 'vista_cocina']);
$routes->post('/cocina/pedido/(:num)/listo', 'Cocina::marcarListo/$1', ['as' => 'marcar_pedido_listo']);

// Vista del mesero
$routes->get('/mesero', 'Mesero::index', ['as' => 'vista_mesero']);
$routes->get('/mesero/mesas', 'Mesero::mesas', ['as' => 'mesero_mesas']);

// Vista del cliente
$routes->get('/cliente', 'Cliente::index', ['as' => 'vista_cliente']);
$routes->get('/cliente/menu', 'Cliente::menu', ['as' => 'cliente_menu']);
$routes->get('/cliente/pedidos', 'Cliente::pedidos', ['as' => 'cliente_pedidos']);

// ==========================================
// RUTAS API (Endpoints JSON)
// ==========================================

$routes->group('api', ['namespace' => 'App\Controllers'], static function ($routes) {
    // API de usuarios
    $routes->get('usuarios', 'Home::apiUsuarios', ['as' => 'api_usuarios']);
    
    // API de mesas
    $routes->get('mesas', 'Mesa::apiMesas', ['as' => 'api_mesas']);
    $routes->get('mesas/disponibles', 'Mesa::apiDisponibles', ['as' => 'api_mesas_disponibles']);
    
    // API de pedidos
    $routes->get('pedidos', 'Pedido::apiPedidos', ['as' => 'api_pedidos']);
    $routes->get('pedidos/pendientes', 'Pedido::apiPendientes', ['as' => 'api_pedidos_pendientes']);
    
    // API de productos
    $routes->get('productos', 'Producto::apiProductos', ['as' => 'api_productos']);
    $routes->get('productos/categoria/(:alpha)', 'Producto::apiPorCategoria/$1', ['as' => 'api_productos_categoria']);
    
    // API de estadisticas
    $routes->get('estadisticas', 'Home::apiEstadisticas', ['as' => 'api_estadisticas']);
    
    // API de verificacion de sesion
    $routes->get('sesion', 'Home::apiVerificarSesion', ['as' => 'api_sesion']);
});

// ==========================================
// RUTAS DE ADMINISTRACION
// ==========================================

$routes->group('admin', static function ($routes) {
    // Dashboard de admin
    $routes->get('/', 'Admin::index', ['as' => 'admin_dashboard']);
    
    // Gestion de usuarios
    $routes->get('usuarios', 'Admin::usuarios', ['as' => 'admin_usuarios']);
    
    // Reportes
    $routes->get('reportes', 'Admin::reportes', ['as' => 'admin_reportes']);
    $routes->get('reportes/ventas', 'Admin::reporteVentas', ['as' => 'admin_reporte_ventas']);
    $routes->get('reportes/usuarios', 'Admin::reporteUsuarios', ['as' => 'admin_reporte_usuarios']);
    
    // Configuracion
    $routes->get('configuracion', 'Admin::configuracion', ['as' => 'admin_configuracion']);
});

// ==========================================
// RUTA 404 PERSONALIZADA
// ==========================================

$routes->set404Override(static function () {
    // Registrar error 404
    log_message('warning', '404 - Pagina no encontrada: ' . current_url());
    
    // Retornar vista de error personalizada
    return view('errors/html/error_404', [
        'mensaje' => 'La pagina que buscas no existe',
        'codigo' => 404
    ]);
});
