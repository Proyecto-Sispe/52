<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Configuracion de Rutas del Sistema SISPE
 * 
 * Definicion de todas las rutas de la aplicacion
 * siguiendo el patron MVC de CodeIgniter 4
 */

/** @var RouteCollection $routes */

// ==========================================
// CONFIGURACION GLOBAL DE RUTAS
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
 * Array de roles permitidos por ruta
 */
$rolesPermitidos = [
    'admin' => ['admin'],
    'staff' => ['admin', 'mesero', 'cocinero'],
    'todos' => ['admin', 'mesero', 'cocinero', 'cliente', 'aprendiz']
];

// ==========================================
// RUTAS PUBLICAS (Sin autenticacion)
// ==========================================

// Pagina principal - Login
$routes->get('/', 'Home::index', ['as' => 'home']);

// Formulario de registro
$routes->get('/registro', 'Home::registrar', ['as' => 'registro']);

// Procesar registro (POST)
$routes->post('/guardar', 'Home::guardar', ['as' => 'guardar_usuario']);

// Procesar login (POST)
$routes->post('/login', 'Home::login', ['as' => 'login']);

// ==========================================
// RUTAS PROTEGIDAS (Requieren autenticacion)
// ==========================================

// Grupo de rutas que requieren sesion activa
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    
    // Dashboard principal
    $routes->get('/dashboard', 'Home::dashboard', ['as' => 'dashboard']);
    
    // Cerrar sesion
    $routes->get('/logout', 'Home::logout', ['as' => 'logout']);
    
    // Perfil de usuario (futuro)
    $routes->get('/perfil', 'Home::perfil', ['as' => 'perfil']);
    $routes->post('/perfil/actualizar', 'Home::actualizarPerfil', ['as' => 'actualizar_perfil']);
    
});

// ==========================================
// RUTAS DE ADMINISTRACION (Solo Admin)
// ==========================================

// Grupo de rutas de administracion
$routes->group('', ['filter' => 'admin'], static function ($routes) {
    
    // Gestion de usuarios
    $routes->get('/usuarios', 'Home::usuarios', ['as' => 'listar_usuarios']);
    
    // Editar usuario - acepta ID numerico
    $routes->get('/editar/(:num)', 'Home::editar/$1', ['as' => 'editar_usuario']);
    
    // Actualizar usuario (POST)
    $routes->post('/actualizar/(:num)', 'Home::actualizar/$1', ['as' => 'actualizar_usuario']);
    
    // Eliminar usuario
    $routes->get('/eliminar/(:num)', 'Home::eliminar/$1', ['as' => 'eliminar_usuario']);
    
    // Crear nuevo usuario (formulario)
    $routes->get('/usuarios/nuevo', 'Home::nuevoUsuario', ['as' => 'nuevo_usuario']);
    
    // Guardar nuevo usuario desde admin (POST)
    $routes->post('/usuarios/crear', 'Home::crearUsuario', ['as' => 'crear_usuario']);
    
});

// ==========================================
// RUTAS API (Endpoints JSON)
// ==========================================

// Grupo de rutas API
$routes->group('api', ['namespace' => 'App\Controllers'], static function ($routes) {
    
    // API de usuarios (requiere autenticacion)
    $routes->get('usuarios', 'Home::apiUsuarios', ['as' => 'api_usuarios']);
    
    // API de estadisticas
    $routes->get('estadisticas', 'Home::apiEstadisticas', ['as' => 'api_estadisticas']);
    
    // API de verificacion de sesion
    $routes->get('sesion', 'Home::apiVerificarSesion', ['as' => 'api_sesion']);
    
    // API de busqueda de usuarios
    $routes->get('usuarios/buscar', 'Home::apiBuscarUsuarios', ['as' => 'api_buscar_usuarios']);
    
});

// ==========================================
// RUTAS DE PRODUCTOS (Futuro modulo)
// ==========================================

$routes->group('productos', static function ($routes) {
    
    // Listar productos
    $routes->get('/', 'Producto::index', ['as' => 'listar_productos']);
    
    // Ver detalle de producto
    $routes->get('ver/(:num)', 'Producto::ver/$1', ['as' => 'ver_producto']);
    
    // Formulario de nuevo producto (admin)
    $routes->get('nuevo', 'Producto::nuevo', ['as' => 'nuevo_producto']);
    
    // Guardar producto (POST)
    $routes->post('guardar', 'Producto::guardar', ['as' => 'guardar_producto']);
    
    // Editar producto
    $routes->get('editar/(:num)', 'Producto::editar/$1', ['as' => 'editar_producto']);
    
    // Actualizar producto (POST)
    $routes->post('actualizar/(:num)', 'Producto::actualizar/$1', ['as' => 'actualizar_producto']);
    
    // Eliminar producto
    $routes->delete('eliminar/(:num)', 'Producto::eliminar/$1', ['as' => 'eliminar_producto']);
    
});

// ==========================================
// RUTAS DE PEDIDOS (Futuro modulo)
// ==========================================

$routes->group('pedidos', static function ($routes) {
    
    // Listar pedidos
    $routes->get('/', 'Pedido::index', ['as' => 'listar_pedidos']);
    
    // Nuevo pedido
    $routes->get('nuevo', 'Pedido::nuevo', ['as' => 'nuevo_pedido']);
    
    // Guardar pedido (POST)
    $routes->post('guardar', 'Pedido::guardar', ['as' => 'guardar_pedido']);
    
    // Ver detalle de pedido
    $routes->get('ver/(:num)', 'Pedido::ver/$1', ['as' => 'ver_pedido']);
    
    // Actualizar estado de pedido
    $routes->post('estado/(:num)', 'Pedido::actualizarEstado/$1', ['as' => 'actualizar_estado_pedido']);
    
    // Cancelar pedido
    $routes->post('cancelar/(:num)', 'Pedido::cancelar/$1', ['as' => 'cancelar_pedido']);
    
});

// ==========================================
// RUTAS DE MESAS (Futuro modulo)
// ==========================================

$routes->group('mesas', static function ($routes) {
    
    // Listar mesas
    $routes->get('/', 'Mesa::index', ['as' => 'listar_mesas']);
    
    // Estado de mesas (vista en tiempo real)
    $routes->get('estado', 'Mesa::estado', ['as' => 'estado_mesas']);
    
    // Ocupar mesa
    $routes->post('ocupar/(:num)', 'Mesa::ocupar/$1', ['as' => 'ocupar_mesa']);
    
    // Liberar mesa
    $routes->post('liberar/(:num)', 'Mesa::liberar/$1', ['as' => 'liberar_mesa']);
    
    // Nueva mesa (admin)
    $routes->get('nueva', 'Mesa::nueva', ['as' => 'nueva_mesa']);
    
    // Guardar mesa (POST)
    $routes->post('guardar', 'Mesa::guardar', ['as' => 'guardar_mesa']);
    
});

// ==========================================
// RUTAS DE FACTURAS (Futuro modulo)
// ==========================================

$routes->group('facturas', static function ($routes) {
    
    // Listar facturas
    $routes->get('/', 'Factura::index', ['as' => 'listar_facturas']);
    
    // Nueva factura
    $routes->get('nueva', 'Factura::nueva', ['as' => 'nueva_factura']);
    
    // Generar factura desde pedido
    $routes->post('generar/(:num)', 'Factura::generar/$1', ['as' => 'generar_factura']);
    
    // Ver factura
    $routes->get('ver/(:num)', 'Factura::ver/$1', ['as' => 'ver_factura']);
    
    // Imprimir factura (PDF)
    $routes->get('imprimir/(:num)', 'Factura::imprimir/$1', ['as' => 'imprimir_factura']);
    
    // Anular factura
    $routes->post('anular/(:num)', 'Factura::anular/$1', ['as' => 'anular_factura']);
    
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

// ==========================================
// RUTAS DE ERROR PERSONALIZADAS
// ==========================================

// Pagina 404 personalizada
$routes->set404Override(static function () {
    // Registrar error 404
    log_message('warning', '404 - Página no encontrada: ' . current_url());
    
    return view('errors/html/error_404', [
        'mensaje' => 'La página que buscas no existe',
        'codigo' => 404
    ]);
});

// ==========================================
// RUTAS DE MANTENIMIENTO
// ==========================================

$routes->group('mantenimiento', ['filter' => 'admin'], static function ($routes) {
    
    // Limpiar cache
    $routes->get('cache/limpiar', 'Mantenimiento::limpiarCache', ['as' => 'limpiar_cache']);
    
    // Ver logs
    $routes->get('logs', 'Mantenimiento::verLogs', ['as' => 'ver_logs']);
    
    // Backup de base de datos
    $routes->get('backup', 'Mantenimiento::backup', ['as' => 'backup_bd']);
    
});

// ==========================================
// RUTAS DE REPORTES
// ==========================================

$routes->group('reportes', ['filter' => 'admin'], static function ($routes) {
    
    // Dashboard de reportes
    $routes->get('/', 'Reporte::index', ['as' => 'reportes']);
    
    // Reporte de ventas
    $routes->get('ventas', 'Reporte::ventas', ['as' => 'reporte_ventas']);
    
    // Reporte de usuarios
    $routes->get('usuarios', 'Reporte::usuarios', ['as' => 'reporte_usuarios']);
    
    // Reporte de productos mas vendidos
    $routes->get('productos', 'Reporte::productos', ['as' => 'reporte_productos']);
    
    // Exportar a Excel
    $routes->get('exportar/(:alpha)', 'Reporte::exportar/$1', ['as' => 'exportar_reporte']);
    
});

// ==========================================
// CONFIGURACION ADICIONAL
// ==========================================

/**
 * Funcion auxiliar para generar URL con nombre de ruta
 * Uso: route_to('nombre_ruta', $param1, $param2)
 */

// Deshabilitar auto-routing por seguridad
// $routes->setAutoRoute(false);

// Configurar namespace por defecto
// $routes->setDefaultNamespace('App\Controllers');

// Configurar controlador por defecto
// $routes->setDefaultController('Home');

// Configurar metodo por defecto
// $routes->setDefaultMethod('index');
