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
    'todos' => ['admin', 'mesero', 'cocinero', 'cliente', 'aprendiz']
];

// ==========================================
// RUTAS PUBLICAS - PAGINA PRINCIPAL (Nosotros)
// ==========================================

// Pagina de inicio - Nosotros
$routes->get('/', 'Home::index', ['as' => 'home']);
$routes->get('/inicio', 'Home::index', ['as' => 'inicio']);
$routes->get('/nosotros', 'Home::index', ['as' => 'nosotros']);

// Contacto
$routes->get('/contacto', 'Home::contacto', ['as' => 'contacto']);
$routes->post('/contacto/enviar', 'Home::enviarContacto', ['as' => 'enviar_contacto']);

// Reservaciones
$routes->get('/reservaciones', 'Home::reservaciones', ['as' => 'reservaciones']);
$routes->post('/reservaciones/procesar', 'Home::procesarReservacion', ['as' => 'procesar_reservacion']);

// Galeria
$routes->get('/galeria', 'Home::galeria', ['as' => 'galeria']);

// ==========================================
// RUTAS DE AUTENTICACION (Auth Controller)
// ==========================================

$routes->group('auth', ['namespace' => 'App\Controllers'], static function ($routes) {
    // Mostrar formulario de login
    $routes->get('login', 'Auth::login', ['as' => 'auth_login']);
    
    // Procesar login (POST)
    $routes->post('login', 'Auth::procesarLogin', ['as' => 'procesar_login']);
    
    // Mostrar formulario de registro
    $routes->get('registro', 'Auth::registro', ['as' => 'auth_registro']);
    
    // Procesar registro (POST)
    $routes->post('registro', 'Auth::procesarRegistro', ['as' => 'procesar_registro']);
    
    // Cerrar sesion
    $routes->get('logout', 'Auth::logout', ['as' => 'auth_logout']);
    
    // Verificar sesion (AJAX)
    $routes->get('verificar-sesion', 'Auth::verificarSesionActiva', ['as' => 'verificar_sesion']);
    
    // Renovar sesion (AJAX)
    $routes->post('renovar-sesion', 'Auth::renovarSesion', ['as' => 'renovar_sesion']);
    
    // Recuperar contraseña
    $routes->get('recuperar-password', 'Auth::recuperarPassword', ['as' => 'recuperar_password']);
    $routes->post('recuperar-password', 'Auth::procesarRecuperacion', ['as' => 'procesar_recuperacion']);
});

// Rutas alias para acceso directo
$routes->get('/login', 'Auth::login', ['as' => 'login']);
$routes->get('/registro', 'Auth::registro', ['as' => 'registro']);
$routes->get('/logout', 'Auth::logout', ['as' => 'logout']);

// ==========================================
// RUTAS DE PRODUCTOS (Productos Controller)
// ==========================================

$routes->group('productos', ['namespace' => 'App\Controllers'], static function ($routes) {
    // Vista publica - Menu de productos
    $routes->get('/', 'Productos::index', ['as' => 'productos']);
    
    // Detalle de producto
    $routes->get('detalle/(:num)', 'Productos::detalle/$1', ['as' => 'detalle_producto']);
    
    // Gestion de productos (admin/cocinero)
    $routes->get('gestion', 'Productos::gestion', ['as' => 'gestion_productos']);
    
    // Agregar producto
    $routes->get('agregar', 'Productos::agregar', ['as' => 'agregar_producto']);
    $routes->post('guardar', 'Productos::guardar', ['as' => 'guardar_producto']);
    
    // Editar producto
    $routes->get('editar/(:num)', 'Productos::editar/$1', ['as' => 'editar_producto']);
    $routes->post('actualizar/(:num)', 'Productos::actualizar/$1', ['as' => 'actualizar_producto']);
    
    // Eliminar producto
    $routes->get('eliminar/(:num)', 'Productos::eliminar/$1', ['as' => 'eliminar_producto']);
    
    // Cambiar estado
    $routes->get('estado/(:num)/(:alpha)', 'Productos::cambiarEstado/$1/$2', ['as' => 'cambiar_estado_producto']);
    
    // API endpoints
    $routes->get('api', 'Productos::apiProductos', ['as' => 'api_productos']);
    $routes->get('api/categoria/(:alpha)', 'Productos::apiPorCategoria/$1', ['as' => 'api_productos_categoria']);
    $routes->get('api/buscar', 'Productos::apiBuscar', ['as' => 'api_buscar_productos']);
});

// Ruta alias para menu
$routes->get('/menu', 'Productos::index', ['as' => 'menu']);

// ==========================================
// RUTAS PROTEGIDAS (Requieren autenticacion)
// ==========================================

// Dashboard principal
$routes->get('/dashboard', 'Dashboard::index', ['as' => 'dashboard']);

// ==========================================
// RUTAS DE PERSONAS
// ==========================================

$routes->group('personas', static function ($routes) {
    $routes->get('/', 'Persona::index', ['as' => 'listar_personas']);
    $routes->get('agregar', 'Persona::agregar', ['as' => 'agregar_persona']);
    $routes->post('guardar', 'Persona::guardar', ['as' => 'guardar_persona']);
    $routes->get('editar/(:num)', 'Persona::editar/$1', ['as' => 'editar_persona']);
    $routes->post('actualizar/(:num)', 'Persona::actualizar/$1', ['as' => 'actualizar_persona']);
    $routes->get('eliminar/(:num)', 'Persona::eliminar/$1', ['as' => 'eliminar_persona']);
});

// ==========================================
// RUTAS DE MESAS
// ==========================================

$routes->group('mesas', static function ($routes) {
    $routes->get('/', 'Mesa::index', ['as' => 'listar_mesas']);
    $routes->get('gestion', 'Mesa::gestion', ['as' => 'gestion_mesas']);
    $routes->get('agregar', 'Mesa::agregar', ['as' => 'agregar_mesa']);
    $routes->post('guardar', 'Mesa::guardar', ['as' => 'guardar_mesa']);
    $routes->get('editar/(:num)', 'Mesa::editar/$1', ['as' => 'editar_mesa']);
    $routes->post('actualizar/(:num)', 'Mesa::actualizar/$1', ['as' => 'actualizar_mesa']);
    $routes->get('eliminar/(:num)', 'Mesa::eliminar/$1', ['as' => 'eliminar_mesa']);
    $routes->get('estado/(:num)/(:alpha)', 'Mesa::cambiarEstado/$1/$2', ['as' => 'cambiar_estado_mesa']);
});

// ==========================================
// RUTAS DE PEDIDOS
// ==========================================

$routes->group('pedidos', static function ($routes) {
    $routes->get('/', 'Pedido::index', ['as' => 'listar_pedidos']);
    $routes->get('agregar', 'Pedido::agregar', ['as' => 'agregar_pedido']);
    $routes->post('guardar', 'Pedido::guardar', ['as' => 'guardar_pedido']);
    $routes->get('ver/(:num)', 'Pedido::ver/$1', ['as' => 'ver_pedido']);
    $routes->get('cambiar-estado/(:num)', 'Pedido::cambiarEstado/$1', ['as' => 'cambiar_estado_pedido']);
    $routes->post('cancelar/(:num)', 'Pedido::cancelar/$1', ['as' => 'cancelar_pedido']);
});

// ==========================================
// RUTAS DE FACTURAS
// ==========================================

$routes->group('facturas', static function ($routes) {
    $routes->get('/', 'Factura::index', ['as' => 'listar_facturas']);
    $routes->get('agregar', 'Factura::agregar', ['as' => 'agregar_factura']);
    $routes->post('guardar', 'Factura::guardar', ['as' => 'guardar_factura']);
    $routes->get('editar/(:num)', 'Factura::editar/$1', ['as' => 'editar_factura']);
    $routes->post('actualizar/(:num)', 'Factura::actualizar/$1', ['as' => 'actualizar_factura']);
    $routes->get('eliminar/(:num)', 'Factura::eliminar/$1', ['as' => 'eliminar_factura']);
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
// RUTAS API GENERALES
// ==========================================

$routes->group('api', ['namespace' => 'App\Controllers'], static function ($routes) {
    // API de informacion del restaurante
    $routes->get('info', 'Home::apiInfo', ['as' => 'api_info']);
    $routes->get('testimonios', 'Home::apiTestimonios', ['as' => 'api_testimonios']);
    
    // API de mesas
    $routes->get('mesas', 'Mesa::apiMesas', ['as' => 'api_mesas']);
    $routes->get('mesas/disponibles', 'Mesa::apiDisponibles', ['as' => 'api_mesas_disponibles']);
    
    // API de pedidos
    $routes->get('pedidos', 'Pedido::apiPedidos', ['as' => 'api_pedidos']);
    $routes->get('pedidos/pendientes', 'Pedido::apiPendientes', ['as' => 'api_pedidos_pendientes']);
    
    // API de autenticacion
    $routes->get('sesion', 'Auth::verificarSesionActiva', ['as' => 'api_sesion']);
});

// ==========================================
// RUTAS DE ADMINISTRACION
// ==========================================

$routes->group('admin', static function ($routes) {
    $routes->get('/', 'Admin::index', ['as' => 'admin_dashboard']);
    $routes->get('usuarios', 'Admin::usuarios', ['as' => 'admin_usuarios']);
    $routes->get('reportes', 'Admin::reportes', ['as' => 'admin_reportes']);
    $routes->get('reportes/ventas', 'Admin::reporteVentas', ['as' => 'admin_reporte_ventas']);
    $routes->get('reportes/usuarios', 'Admin::reporteUsuarios', ['as' => 'admin_reporte_usuarios']);
    $routes->get('configuracion', 'Admin::configuracion', ['as' => 'admin_configuracion']);
});

// ==========================================
// RUTA 404 PERSONALIZADA
// ==========================================

$routes->set404Override(static function () {
    log_message('warning', '404 - Pagina no encontrada: ' . current_url());
    
    return view('errors/html/error_404', [
        'mensaje' => 'La pagina que buscas no existe',
        'codigo' => 404
    ]);
});
