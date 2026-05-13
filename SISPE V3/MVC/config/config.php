<?php
/**
 * Archivo de configuracion global del sistema
 * Define constantes y configuraciones generales
 */

// Prevenir acceso directo
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__) . '/');
}

// URLs base
define('BASE_URL', '/MVC/');
define('ASSETS_URL', BASE_URL . 'public/');

// Rutas del sistema
define('CONFIG_PATH', BASE_PATH . 'config/');
define('MODELS_PATH', BASE_PATH . 'models/');
define('VIEWS_PATH', BASE_PATH . 'views/');
define('CONTROLLERS_PATH', BASE_PATH . 'controllers/');
define('HELPERS_PATH', BASE_PATH . 'helpers/');

// Configuracion de la aplicacion
define('APP_NAME', 'Divina Comida');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'development'); // development | production

// Configuracion de sesiones
define('SESSION_LIFETIME', 3600); // 1 hora
define('SESSION_NAME', 'SISPE_SESSION');

// Estados de pedido
define('ESTADO_PENDIENTE', 'pendiente');
define('ESTADO_EN_PREPARACION', 'en_preparacion');
define('ESTADO_LISTO', 'listo');
define('ESTADO_ENTREGADO', 'entregado');
define('ESTADO_CANCELADO', 'cancelado');

// Estados de mesa
define('MESA_DISPONIBLE', 0);
define('MESA_OCUPADA', 1);

// Roles del sistema
define('ROL_ADMIN', 1);
define('ROL_COCINERO', 2);
define('ROL_MESERO', 3);
define('ROL_CLIENTE', 4);

// Configuracion de errores segun ambiente
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Zona horaria
date_default_timezone_set('America/Bogota');

// Autoload de clases
spl_autoload_register(function ($class) {
    $paths = [
        MODELS_PATH,
        CONTROLLERS_PATH,
        HELPERS_PATH,
        CONFIG_PATH
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Cargar helpers
require_once HELPERS_PATH . 'functions.php';

// Iniciar sesion de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
?>
