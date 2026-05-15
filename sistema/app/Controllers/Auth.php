<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use Exception;

/**
 * Controlador de Autenticacion
 * Maneja login, registro y logout del sistema
 * 
 * Funciones PHP utilizadas:
 * - Excepciones (try-catch) para manejo de errores
 * - Condiciones (if-else) para validaciones
 * - Bucles (foreach) para procesamiento de datos
 * - Operaciones para validaciones y sanitizacion
 */
class Auth extends BaseController
{
    /**
     * Modelo de usuario
     * @var UsuarioModel
     */
    protected UsuarioModel $usuarioModel;

    /**
     * Roles permitidos en el sistema
     * @var array
     */
    private const ROLES_PERMITIDOS = ['admin', 'mesero', 'cocinero', 'cliente', 'aprendiz'];

    /**
     * Mensajes de error personalizados
     * @var array
     */
    private const MENSAJES_ERROR = [
        'credenciales_invalidas' => 'Correo o contraseña incorrectos',
        'usuario_no_existe' => 'El usuario no existe en el sistema',
        'password_incorrecto' => 'La contraseña ingresada es incorrecta',
        'acceso_denegado' => 'No tienes permisos para acceder a esta sección',
        'sesion_expirada' => 'Tu sesión ha expirado, inicia sesión nuevamente',
        'correo_duplicado' => 'El correo electrónico ya está registrado',
        'datos_invalidos' => 'Los datos proporcionados no son válidos',
        'campos_vacios' => 'Todos los campos son obligatorios',
        'usuario_inactivo' => 'Tu cuenta está desactivada, contacta al administrador'
    ];

    /**
     * Intentos maximos de login permitidos
     * @var int
     */
    private const MAX_INTENTOS_LOGIN = 5;

    /**
     * Tiempo de bloqueo en segundos (15 minutos)
     * @var int
     */
    private const TIEMPO_BLOQUEO = 900;

    /**
     * Constructor - Inicializa el modelo
     */
    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    // ==========================================
    // FUNCIONES AUXILIARES (Operaciones)
    // ==========================================

    /**
     * Valida formato de correo electronico
     * Operacion: Usa filter_var para validar email
     * @param string $correo
     * @return bool
     */
    private function validarCorreo(string $correo): bool
    {
        // Condicion: verificar que no este vacio
        if (empty($correo)) {
            return false;
        }
        
        // Operacion: validar formato de email
        return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida fortaleza de contraseña
     * Operacion: Verifica longitud, mayusculas y numeros
     * @param string $password
     * @return array ['valido' => bool, 'errores' => array]
     */
    private function validarPassword(string $password): array
    {
        $errores = [];

        // Condicion: verificar longitud minima
        if (strlen($password) < 6) {
            $errores[] = 'Mínimo 6 caracteres';
        }

        // Condicion: verificar longitud maxima
        if (strlen($password) > 50) {
            $errores[] = 'Máximo 50 caracteres';
        }

        // Condicion: verificar al menos una mayuscula
        if (!preg_match('/[A-Z]/', $password)) {
            $errores[] = 'Al menos una letra mayúscula';
        }

        // Condicion: verificar al menos una minuscula
        if (!preg_match('/[a-z]/', $password)) {
            $errores[] = 'Al menos una letra minúscula';
        }

        // Condicion: verificar al menos un numero
        if (!preg_match('/[0-9]/', $password)) {
            $errores[] = 'Al menos un número';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'mensaje' => implode(', ', $errores)
        ];
    }

    /**
     * Valida formato de nombre
     * @param string $nombre
     * @return array
     */
    private function validarNombre(string $nombre): array
    {
        $errores = [];

        // Condicion: verificar longitud
        if (strlen($nombre) < 3) {
            $errores[] = 'El nombre debe tener al menos 3 caracteres';
        }

        if (strlen($nombre) > 100) {
            $errores[] = 'El nombre no puede exceder 100 caracteres';
        }

        // Condicion: verificar caracteres validos (letras y espacios)
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
            $errores[] = 'El nombre solo puede contener letras y espacios';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Verifica si el usuario tiene sesion activa
     * @return bool
     */
    private function verificarSesion(): bool
    {
        return session('logueado') === true;
    }

    /**
     * Obtiene el mensaje de error por clave
     * @param string $clave
     * @return string
     */
    private function obtenerMensajeError(string $clave): string
    {
        // Condicion: verificar si existe la clave
        if (array_key_exists($clave, self::MENSAJES_ERROR)) {
            return self::MENSAJES_ERROR[$clave];
        }
        return 'Error desconocido';
    }

    /**
     * Registra actividad del usuario (log)
     * @param string $accion
     * @param array $datos
     * @return void
     */
    private function registrarActividad(string $accion, array $datos = []): void
    {
        $log = [
            'fecha' => date('Y-m-d H:i:s'),
            'usuario_id' => session('id') ?? 'anonimo',
            'accion' => $accion,
            'ip' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'datos' => json_encode($datos)
        ];

        log_message('info', 'Actividad Auth: ' . json_encode($log));
    }

    /**
     * Sanitiza datos de entrada
     * Operacion: Limpia caracteres especiales
     * @param array $datos
     * @return array
     */
    private function sanitizarDatos(array $datos): array
    {
        $sanitizados = [];

        // Bucle: procesar cada campo
        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                // Operacion: limpiar y escapar HTML
                $sanitizados[$clave] = htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
            } elseif (is_array($valor)) {
                // Recursion para arrays anidados
                $sanitizados[$clave] = $this->sanitizarDatos($valor);
            } else {
                $sanitizados[$clave] = $valor;
            }
        }

        return $sanitizados;
    }

    /**
     * Verifica si la IP esta bloqueada por intentos fallidos
     * @param string $ip
     * @return bool
     */
    private function ipBloqueada(string $ip): bool
    {
        $intentos = session('intentos_login') ?? [];
        
        // Condicion: verificar si existe registro para esta IP
        if (!isset($intentos[$ip])) {
            return false;
        }

        $datosIp = $intentos[$ip];

        // Condicion: verificar si supero el maximo de intentos
        if ($datosIp['cantidad'] >= self::MAX_INTENTOS_LOGIN) {
            $tiempoTranscurrido = time() - $datosIp['ultimo_intento'];
            
            // Condicion: verificar si ya paso el tiempo de bloqueo
            if ($tiempoTranscurrido < self::TIEMPO_BLOQUEO) {
                return true;
            }
            
            // Reiniciar contador si ya paso el tiempo
            $this->reiniciarIntentosLogin($ip);
        }

        return false;
    }

    /**
     * Registra un intento de login fallido
     * @param string $ip
     * @return void
     */
    private function registrarIntentoFallido(string $ip): void
    {
        $intentos = session('intentos_login') ?? [];

        // Condicion: crear o actualizar registro
        if (!isset($intentos[$ip])) {
            $intentos[$ip] = [
                'cantidad' => 1,
                'ultimo_intento' => time()
            ];
        } else {
            $intentos[$ip]['cantidad']++;
            $intentos[$ip]['ultimo_intento'] = time();
        }

        session()->set('intentos_login', $intentos);
    }

    /**
     * Reinicia los intentos de login para una IP
     * @param string $ip
     * @return void
     */
    private function reiniciarIntentosLogin(string $ip): void
    {
        $intentos = session('intentos_login') ?? [];
        
        // Condicion: verificar si existe
        if (isset($intentos[$ip])) {
            unset($intentos[$ip]);
            session()->set('intentos_login', $intentos);
        }
    }

    /**
     * Genera token CSRF
     * @return string
     */
    private function generarTokenCSRF(): string
    {
        $token = bin2hex(random_bytes(32));
        session()->set('csrf_token', $token);
        return $token;
    }

    /**
     * Valida token CSRF
     * @param string $token
     * @return bool
     */
    private function validarTokenCSRF(string $token): bool
    {
        $tokenSesion = session('csrf_token');
        return $token === $tokenSesion;
    }

    // ==========================================
    // VISTAS DE AUTENTICACION
    // ==========================================

    /**
     * Muestra pagina de login
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function login()
    {
        try {
            // Condicion: si ya esta logueado, redirigir al dashboard
            if ($this->verificarSesion()) {
                return redirect()->to('/dashboard');
            }

            $this->registrarActividad('vista_login');

            // Generar token CSRF
            $csrfToken = $this->generarTokenCSRF();

            return view('login', [
                'csrf_token' => $csrfToken
            ]);

        } catch (Exception $e) {
            log_message('error', 'Error en vista login: ' . $e->getMessage());
            return view('login', ['error' => 'Error al cargar la pagina']);
        }
    }

    /**
     * Muestra formulario de registro
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function registro()
    {
        try {
            // Condicion: si ya esta logueado, redirigir
            if ($this->verificarSesion()) {
                return redirect()->to('/dashboard');
            }

            $this->registrarActividad('vista_registro');

            // Generar token CSRF
            $csrfToken = $this->generarTokenCSRF();

            return view('registro', [
                'roles' => self::ROLES_PERMITIDOS,
                'csrf_token' => $csrfToken
            ]);

        } catch (Exception $e) {
            log_message('error', 'Error en vista registro: ' . $e->getMessage());
            return view('registro', ['error' => 'Error al cargar la pagina']);
        }
    }

    // ==========================================
    // PROCESAR AUTENTICACION
    // ==========================================

    /**
     * Procesa inicio de sesion
     * Usa: Excepciones, Condiciones, Operaciones
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function procesarLogin()
    {
        try {
            // Obtener IP del cliente
            $ip = $this->request->getIPAddress();

            // Condicion: verificar si la IP esta bloqueada
            if ($this->ipBloqueada($ip)) {
                $tiempoRestante = ceil(self::TIEMPO_BLOQUEO / 60);
                throw new Exception("Demasiados intentos fallidos. Intenta en {$tiempoRestante} minutos");
            }

            // Obtener datos del formulario
            $correo = $this->request->getPost('correo');
            $password = $this->request->getPost('password');
            $recordar = $this->request->getPost('recordar') ?? false;

            // Condicion: validar campos vacios
            if (empty($correo) || empty($password)) {
                throw new Exception($this->obtenerMensajeError('campos_vacios'));
            }

            // Condicion: validar formato de correo
            if (!$this->validarCorreo($correo)) {
                throw new Exception('Formato de correo electrónico inválido');
            }

            // Sanitizar correo
            $correo = strtolower(trim($correo));

            // Buscar usuario en la base de datos
            $usuario = $this->usuarioModel->buscarPorCorreo($correo);

            // Condicion: verificar si el usuario existe
            if ($usuario === null) {
                $this->registrarIntentoFallido($ip);
                $this->registrarActividad('login_fallido', [
                    'motivo' => 'usuario_no_existe',
                    'correo' => $correo
                ]);
                throw new Exception($this->obtenerMensajeError('credenciales_invalidas'));
            }

            // Condicion: verificar si el usuario esta activo
            if (isset($usuario['activo']) && $usuario['activo'] == 0) {
                $this->registrarActividad('login_fallido', [
                    'motivo' => 'usuario_inactivo',
                    'correo' => $correo
                ]);
                throw new Exception($this->obtenerMensajeError('usuario_inactivo'));
            }

            // Condicion: verificar contraseña
            if (!password_verify($password, $usuario['password'])) {
                $this->registrarIntentoFallido($ip);
                $this->registrarActividad('login_fallido', [
                    'motivo' => 'password_incorrecto',
                    'correo' => $correo
                ]);
                throw new Exception($this->obtenerMensajeError('credenciales_invalidas'));
            }

            // Login exitoso - Reiniciar intentos
            $this->reiniciarIntentosLogin($ip);

            // Crear sesion
            $datosSesion = [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'correo' => $usuario['correo'],
                'rol' => $usuario['rol'],
                'logueado' => true,
                'tiempo_login' => time()
            ];

            session()->set($datosSesion);

            // Condicion: si marco "recordarme", extender sesion
            if ($recordar) {
                session()->set('tiempo_expiracion', time() + (86400 * 30)); // 30 dias
            }

            $this->registrarActividad('login_exitoso', ['usuario_id' => $usuario['id']]);

            // Redirigir segun rol
            return $this->redirigirPorRol($usuario['rol']);

        } catch (Exception $e) {
            $this->registrarActividad('error_login', ['error' => $e->getMessage()]);
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/auth/login');
        }
    }

    /**
     * Guarda nuevo usuario (registro)
     * Usa: Excepciones, Condiciones, Bucles, Operaciones
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function procesarRegistro()
    {
        try {
            // Obtener datos del formulario
            $datosRaw = [
                'nombre' => $this->request->getPost('nombre'),
                'correo' => $this->request->getPost('correo'),
                'password' => $this->request->getPost('password'),
                'confirmar_password' => $this->request->getPost('confirmar_password'),
                'rol' => $this->request->getPost('rol') ?? 'cliente'
            ];

            // Sanitizar datos (excepto password)
            $datos = $this->sanitizarDatos([
                'nombre' => $datosRaw['nombre'],
                'correo' => $datosRaw['correo'],
                'rol' => $datosRaw['rol']
            ]);
            $datos['password'] = $datosRaw['password'];
            $datos['confirmar_password'] = $datosRaw['confirmar_password'];

            // Array para almacenar errores
            $errores = [];

            // Validar nombre
            $validacionNombre = $this->validarNombre($datos['nombre']);
            if (!$validacionNombre['valido']) {
                // Bucle: agregar todos los errores de nombre
                foreach ($validacionNombre['errores'] as $error) {
                    $errores[] = $error;
                }
            }

            // Validar correo
            if (!$this->validarCorreo($datos['correo'])) {
                $errores[] = 'Formato de correo electrónico inválido';
            }

            // Validar password
            $validacionPass = $this->validarPassword($datos['password']);
            if (!$validacionPass['valido']) {
                // Bucle: agregar todos los errores de password
                foreach ($validacionPass['errores'] as $error) {
                    $errores[] = $error;
                }
            }

            // Condicion: verificar que las contraseñas coincidan
            if ($datos['password'] !== $datos['confirmar_password']) {
                $errores[] = 'Las contraseñas no coinciden';
            }

            // Condicion: verificar rol permitido
            if (!in_array($datos['rol'], self::ROLES_PERMITIDOS)) {
                $errores[] = 'El rol seleccionado no es válido';
            }

            // Condicion: si hay errores, mostrarlos
            if (!empty($errores)) {
                throw new Exception(implode('. ', $errores));
            }

            // Verificar si correo ya existe
            $existente = $this->usuarioModel->buscarPorCorreo($datos['correo']);
            if ($existente !== null) {
                throw new Exception($this->obtenerMensajeError('correo_duplicado'));
            }

            // Preparar datos para insercion
            $datosInsertar = [
                'nombre' => $datos['nombre'],
                'correo' => strtolower($datos['correo']),
                'password' => password_hash($datos['password'], PASSWORD_DEFAULT, ['cost' => 12]),
                'rol' => $datos['rol'],
                'activo' => 1
            ];

            // Insertar usuario
            $resultado = $this->usuarioModel->insert($datosInsertar);

            // Condicion: verificar resultado
            if ($resultado === false) {
                throw new Exception('Error al registrar usuario. Intenta nuevamente.');
            }

            $this->registrarActividad('registro_exitoso', [
                'usuario_id' => $resultado,
                'correo' => $datos['correo']
            ]);

            session()->setFlashdata('exito', 'Registro exitoso. Ya puedes iniciar sesión.');
            return redirect()->to('/auth/login');

        } catch (Exception $e) {
            $this->registrarActividad('error_registro', ['error' => $e->getMessage()]);
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/auth/registro');
        }
    }

    /**
     * Cierra sesion del usuario
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function logout()
    {
        try {
            $usuarioId = session('id');
            $nombreUsuario = session('nombre');

            // Condicion: solo registrar si habia sesion activa
            if ($usuarioId) {
                $this->registrarActividad('logout', [
                    'usuario_id' => $usuarioId,
                    'nombre' => $nombreUsuario
                ]);
            }

            // Destruir sesion completamente
            session()->destroy();

            // Crear nueva sesion limpia
            session()->start();
            session()->setFlashdata('exito', 'Has cerrado sesión correctamente');

            return redirect()->to('/');

        } catch (Exception $e) {
            log_message('error', 'Error en logout: ' . $e->getMessage());
            session()->destroy();
            return redirect()->to('/');
        }
    }

    // ==========================================
    // FUNCIONES DE REDIRECCION
    // ==========================================

    /**
     * Redirige al dashboard segun el rol del usuario
     * Usa condiciones para determinar la ruta
     * @param string $rol
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    private function redirigirPorRol(string $rol)
    {
        // Array de rutas por rol
        $rutasPorRol = [
            'admin' => '/dashboard',
            'mesero' => '/mesero',
            'cocinero' => '/cocina',
            'cliente' => '/cliente',
            'aprendiz' => '/dashboard'
        ];

        // Condicion: obtener ruta segun rol
        $ruta = $rutasPorRol[$rol] ?? '/dashboard';

        return redirect()->to($ruta);
    }

    // ==========================================
    // FUNCIONES ADICIONALES
    // ==========================================

    /**
     * Verifica si la sesion sigue activa (AJAX)
     * @return \CodeIgniter\HTTP\Response
     */
    public function verificarSesionActiva()
    {
        $respuesta = [
            'activa' => $this->verificarSesion(),
            'usuario' => session('nombre') ?? null,
            'rol' => session('rol') ?? null
        ];

        return $this->response->setJSON($respuesta);
    }

    /**
     * Renueva el tiempo de sesion
     * @return \CodeIgniter\HTTP\Response
     */
    public function renovarSesion()
    {
        try {
            // Condicion: verificar que hay sesion activa
            if (!$this->verificarSesion()) {
                return $this->response->setJSON([
                    'exito' => false,
                    'mensaje' => 'No hay sesión activa'
                ])->setStatusCode(401);
            }

            // Actualizar tiempo de login
            session()->set('tiempo_login', time());

            $this->registrarActividad('sesion_renovada');

            return $this->response->setJSON([
                'exito' => true,
                'mensaje' => 'Sesión renovada'
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Recuperar contraseña - Mostrar formulario
     * @return string
     */
    public function recuperarPassword()
    {
        // Condicion: si ya esta logueado, redirigir
        if ($this->verificarSesion()) {
            return redirect()->to('/dashboard');
        }

        return view('recuperar_password', [
            'csrf_token' => $this->generarTokenCSRF()
        ]);
    }

    /**
     * Procesar solicitud de recuperacion de contraseña
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function procesarRecuperacion()
    {
        try {
            $correo = $this->request->getPost('correo');

            // Condicion: validar correo
            if (!$this->validarCorreo($correo)) {
                throw new Exception('Formato de correo inválido');
            }

            // Buscar usuario
            $usuario = $this->usuarioModel->buscarPorCorreo($correo);

            // Por seguridad, siempre mostrar el mismo mensaje
            // independientemente de si el correo existe o no
            $this->registrarActividad('solicitud_recuperacion', ['correo' => $correo]);

            session()->setFlashdata('exito', 'Si el correo existe, recibirás instrucciones para recuperar tu contraseña');
            return redirect()->to('/auth/login');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/auth/recuperar-password');
        }
    }
}
