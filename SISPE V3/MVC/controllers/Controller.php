<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Clase Controller - Controlador base abstracto
 * Proporciona funcionalidad comun para todos los controladores
 */
abstract class Controller {
    protected array $data = [];
    
    /**
     * Renderiza una vista
     * @param string $view Nombre de la vista (ruta con puntos)
     * @param array $data Datos para la vista
     */
    protected function view(string $view, array $data = []): void {
        // Combinar datos del controlador con datos pasados
        $data = array_merge($this->data, $data);
        
        // Extraer variables para la vista
        extract($data);
        
        // Construir ruta de la vista
        $viewPath = VIEWS_PATH . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new Exception("Vista no encontrada: {$view}");
        }
        
        // Renderizar vista
        require $viewPath;
    }
    
    /**
     * Renderiza vista con layout
     * @param string $view Vista principal
     * @param string $layout Layout a usar
     * @param array $data Datos
     */
    protected function render(string $view, string $layout = 'main', array $data = []): void {
        $data = array_merge($this->data, $data);
        $data['_content'] = $view;
        
        $layoutPath = VIEWS_PATH . 'layouts/' . $layout . '.php';
        
        if (file_exists($layoutPath)) {
            extract($data);
            require $layoutPath;
        } else {
            $this->view($view, $data);
        }
    }
    
    /**
     * Responde con JSON
     * @param mixed $data Datos a enviar
     * @param int $statusCode Codigo HTTP
     */
    protected function json($data, int $statusCode = 200): void {
        jsonResponse($data, $statusCode);
    }
    
    /**
     * Redirige a otra URL
     * @param string $url URL destino
     * @param string|null $message Mensaje flash opcional
     * @param string $type Tipo de mensaje (success, error, warning, info)
     */
    protected function redirect(string $url, ?string $message = null, string $type = 'info'): void {
        if ($message !== null) {
            setFlash($type, $message);
        }
        redirect($url);
    }
    
    /**
     * Redirige con mensaje de exito
     */
    protected function redirectSuccess(string $url, string $message): void {
        $this->redirect($url, $message, 'success');
    }
    
    /**
     * Redirige con mensaje de error
     */
    protected function redirectError(string $url, string $message): void {
        $this->redirect($url, $message, 'error');
    }
    
    /**
     * Valida que el request sea POST
     * @return bool
     */
    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Valida que el request sea GET
     * @return bool
     */
    protected function isGet(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /**
     * Valida que el request sea AJAX
     * @return bool
     */
    protected function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Requiere autenticacion
     * @param string $redirectUrl URL de redireccion si no autenticado
     */
    protected function requireAuth(string $redirectUrl = 'index.php?controller=auth&action=login'): void {
        if (!isAuthenticated()) {
            $this->redirectError($redirectUrl, 'Debe iniciar sesion para acceder');
        }
    }
    
    /**
     * Requiere un rol especifico
     * @param int $rolId ID del rol requerido
     * @param string $redirectUrl URL de redireccion si no tiene permiso
     */
    protected function requireRole(int $rolId, string $redirectUrl = 'index.php'): void {
        $this->requireAuth();
        
        if (!hasRole($rolId)) {
            $this->redirectError($redirectUrl, 'No tiene permisos para acceder a esta seccion');
        }
    }
    
    /**
     * Requiere uno de varios roles
     * @param array $roles Array de IDs de roles permitidos
     * @param string $redirectUrl URL de redireccion
     */
    protected function requireAnyRole(array $roles, string $redirectUrl = 'index.php'): void {
        $this->requireAuth();
        
        $hasPermission = false;
        foreach ($roles as $rolId) {
            if (hasRole($rolId)) {
                $hasPermission = true;
                break;
            }
        }
        
        if (!$hasPermission) {
            $this->redirectError($redirectUrl, 'No tiene permisos para acceder a esta seccion');
        }
    }
    
    /**
     * Obtiene parametro POST
     * @param string $key Nombre del parametro
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    protected function post(string $key, $default = null) {
        return post($key, $default);
    }
    
    /**
     * Obtiene parametro GET
     * @param string $key Nombre del parametro
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    protected function get(string $key, $default = null) {
        return get($key, $default);
    }
    
    /**
     * Obtiene todos los parametros POST
     * @return array
     */
    protected function postAll(): array {
        $data = [];
        foreach ($_POST as $key => $value) {
            $data[$key] = is_string($value) ? sanitize($value) : $value;
        }
        return $data;
    }
    
    /**
     * Valida datos con reglas
     * @param array $data Datos a validar
     * @param array $rules Reglas de validacion
     * @return array Array de errores (vacio si todo ok)
     */
    protected function validate(array $data, array $rules): array {
        $errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $rulesArray = explode('|', $ruleSet);
            $value = $data[$field] ?? null;
            
            foreach ($rulesArray as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field][] = "El campo {$field} es requerido";
                        }
                        break;
                        
                    case 'min':
                        if (strlen($value) < (int)$ruleParam) {
                            $errors[$field][] = "El campo {$field} debe tener al menos {$ruleParam} caracteres";
                        }
                        break;
                        
                    case 'max':
                        if (strlen($value) > (int)$ruleParam) {
                            $errors[$field][] = "El campo {$field} no debe exceder {$ruleParam} caracteres";
                        }
                        break;
                        
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "El campo {$field} debe ser un email valido";
                        }
                        break;
                        
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field][] = "El campo {$field} debe ser numerico";
                        }
                        break;
                        
                    case 'integer':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field][] = "El campo {$field} debe ser un numero entero";
                        }
                        break;
                        
                    case 'positive':
                        if ((float)$value <= 0) {
                            $errors[$field][] = "El campo {$field} debe ser mayor a 0";
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Establece datos para la vista
     * @param string|array $key Nombre o array de datos
     * @param mixed $value Valor (solo si key es string)
     */
    protected function set($key, $value = null): void {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }
}
?>
