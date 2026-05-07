<?php
/**
 * Funciones auxiliares del sistema
 * Contiene funciones de utilidad usadas en todo el proyecto
 */

/**
 * Escapa HTML para prevenir XSS
 * @param string|null $string Texto a escapar
 * @return string Texto escapado
 */
function escape(?string $string): string {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Alias de escape()
 */
function e(?string $string): string {
    return escape($string);
}

/**
 * Redirige a otra URL
 * @param string $url URL destino
 * @param int $statusCode Codigo HTTP
 */
function redirect(string $url, int $statusCode = 302): void {
    header("Location: " . $url, true, $statusCode);
    exit;
}

/**
 * Genera URL completa
 * @param string $path Ruta relativa
 * @return string URL completa
 */
function url(string $path = ''): string {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Genera URL para assets
 * @param string $path Ruta del asset
 * @return string URL del asset
 */
function asset(string $path): string {
    return ASSETS_URL . ltrim($path, '/');
}

/**
 * Establece un mensaje flash en sesion
 * @param string $type Tipo de mensaje (success, error, warning, info)
 * @param string $message Mensaje a mostrar
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtiene y elimina el mensaje flash
 * @return array|null Mensaje flash o null
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Verifica si hay mensaje flash
 * @return bool
 */
function hasFlash(): bool {
    return isset($_SESSION['flash']);
}

/**
 * Formatea fecha a formato legible
 * @param string $date Fecha en formato MySQL
 * @param string $format Formato deseado
 * @return string Fecha formateada
 */
function formatDate(string $date, string $format = 'd/m/Y H:i'): string {
    try {
        $datetime = new DateTime($date);
        return $datetime->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Formatea numero como moneda colombiana
 * @param float $amount Cantidad
 * @return string Cantidad formateada
 */
function formatMoney(float $amount): string {
    return '$ ' . number_format($amount, 0, ',', '.');
}

/**
 * Calcula tiempo transcurrido
 * @param string $datetime Fecha/hora inicial
 * @return string Tiempo transcurrido
 */
function timeAgo(string $datetime): string {
    try {
        $now = new DateTime();
        $past = new DateTime($datetime);
        $diff = $now->diff($past);
        
        if ($diff->y > 0) {
            return $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
        } elseif ($diff->m > 0) {
            return $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
        } elseif ($diff->d > 0) {
            return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->i > 0) {
            return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
        } else {
            return 'Ahora mismo';
        }
    } catch (Exception $e) {
        return 'Desconocido';
    }
}

/**
 * Calcula minutos transcurridos
 * @param string $datetime Fecha/hora inicial
 * @return int Minutos transcurridos
 */
function minutesAgo(string $datetime): int {
    try {
        $now = new DateTime();
        $past = new DateTime($datetime);
        $diff = $now->getTimestamp() - $past->getTimestamp();
        return (int)floor($diff / 60);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Genera codigo aleatorio
 * @param int $length Longitud del codigo
 * @return string Codigo generado
 */
function generateCode(int $length = 6): string {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $max)];
    }
    
    return $code;
}

/**
 * Verifica si el usuario esta autenticado
 * @return bool
 */
function isAuthenticated(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtiene datos del usuario actual
 * @param string|null $key Clave especifica o null para todo
 * @return mixed
 */
function currentUser(?string $key = null) {
    if (!isAuthenticated()) {
        return null;
    }
    
    if ($key !== null) {
        return $_SESSION['user'][$key] ?? null;
    }
    
    return $_SESSION['user'] ?? null;
}

/**
 * Verifica si el usuario tiene un rol especifico
 * @param int $rolId ID del rol
 * @return bool
 */
function hasRole(int $rolId): bool {
    if (!isAuthenticated()) {
        return false;
    }
    return (currentUser('rol') ?? 0) === $rolId;
}

/**
 * Valida que una cadena no este vacia
 * @param string|null $value Valor a validar
 * @return bool
 */
function notEmpty(?string $value): bool {
    return $value !== null && trim($value) !== '';
}

/**
 * Sanitiza entrada de texto
 * @param string|null $input Entrada a sanitizar
 * @return string
 */
function sanitize(?string $input): string {
    if ($input === null) {
        return '';
    }
    return trim(strip_tags($input));
}

/**
 * Obtiene valor POST sanitizado
 * @param string $key Clave del parametro
 * @param mixed $default Valor por defecto
 * @return mixed
 */
function post(string $key, $default = null) {
    if (!isset($_POST[$key])) {
        return $default;
    }
    
    $value = $_POST[$key];
    
    if (is_string($value)) {
        return sanitize($value);
    }
    
    return $value;
}

/**
 * Obtiene valor GET sanitizado
 * @param string $key Clave del parametro
 * @param mixed $default Valor por defecto
 * @return mixed
 */
function get(string $key, $default = null) {
    if (!isset($_GET[$key])) {
        return $default;
    }
    
    $value = $_GET[$key];
    
    if (is_string($value)) {
        return sanitize($value);
    }
    
    return $value;
}

/**
 * Responde con JSON
 * @param mixed $data Datos a enviar
 * @param int $statusCode Codigo HTTP
 */
function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Obtiene clase CSS segun estado del pedido
 * @param string $estado Estado del pedido
 * @return string Clase CSS
 */
function getEstadoClass(string $estado): string {
    $clases = [
        ESTADO_PENDIENTE => 'estado-pendiente',
        ESTADO_EN_PREPARACION => 'estado-preparacion',
        ESTADO_LISTO => 'estado-listo',
        ESTADO_ENTREGADO => 'estado-entregado',
        ESTADO_CANCELADO => 'estado-cancelado'
    ];
    
    return $clases[$estado] ?? 'estado-desconocido';
}

/**
 * Obtiene texto legible del estado
 * @param string $estado Estado del pedido
 * @return string Texto del estado
 */
function getEstadoTexto(string $estado): string {
    $textos = [
        ESTADO_PENDIENTE => 'Pendiente',
        ESTADO_EN_PREPARACION => 'En Preparacion',
        ESTADO_LISTO => 'Listo',
        ESTADO_ENTREGADO => 'Entregado',
        ESTADO_CANCELADO => 'Cancelado'
    ];
    
    return $textos[$estado] ?? 'Desconocido';
}

/**
 * Obtiene icono del estado
 * @param string $estado Estado del pedido
 * @return string Icono unicode
 */
function getEstadoIcono(string $estado): string {
    $iconos = [
        ESTADO_PENDIENTE => '&#9203;',
        ESTADO_EN_PREPARACION => '&#127859;',
        ESTADO_LISTO => '&#9989;',
        ESTADO_ENTREGADO => '&#128230;',
        ESTADO_CANCELADO => '&#10060;'
    ];
    
    return $iconos[$estado] ?? '&#10067;';
}

/**
 * Renderiza una vista
 * @param string $view Nombre de la vista
 * @param array $data Datos para la vista
 */
function renderView(string $view, array $data = []): void {
    extract($data);
    $viewFile = VIEWS_PATH . str_replace('.', '/', $view) . '.php';
    
    if (!file_exists($viewFile)) {
        throw new Exception("Vista no encontrada: {$view}");
    }
    
    require $viewFile;
}

/**
 * Incluye un partial/componente
 * @param string $partial Nombre del partial
 * @param array $data Datos para el partial
 */
function partial(string $partial, array $data = []): void {
    extract($data);
    $partialFile = VIEWS_PATH . 'partials/' . $partial . '.php';
    
    if (file_exists($partialFile)) {
        require $partialFile;
    }
}
?>
