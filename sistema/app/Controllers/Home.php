<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

class Home extends BaseController
{
    // LOGIN
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
        'datos_invalidos' => 'Los datos proporcionados no son válidos'
    ];

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
     * @param string $correo
     * @return bool
     */
    private function validarCorreo(string $correo): bool
    {
        return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida fortaleza de contraseña
     * @param string $password
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    private function validarPassword(string $password): array
    {
        $errores = [];

        // Condiciones de validacion
        if (strlen($password) < 6) {
            $errores[] = 'Mínimo 6 caracteres';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errores[] = 'Al menos una mayúscula';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errores[] = 'Al menos un número';
        }

        return [
            'valido' => empty($errores),
            'mensaje' => implode(', ', $errores)
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
     * Verifica si el usuario tiene rol de administrador
     * @return bool
     */
    private function esAdmin(): bool
    {
        return session('rol') === 'admin';
    }

    /**
     * Obtiene el mensaje de error por clave
     * @param string $clave
     * @return string
     */
    private function obtenerMensajeError(string $clave): string
    {
        return self::MENSAJES_ERROR[$clave] ?? 'Error desconocido';
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
            'datos' => json_encode($datos)
        ];

        log_message('info', 'Actividad: ' . json_encode($log));
    }

    /**
     * Sanitiza datos de entrada
     * @param array $datos
     * @return array
     */
    private function sanitizarDatos(array $datos): array
    {
        $sanitizados = [];

        // Bucle para limpiar cada campo
        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                $sanitizados[$clave] = htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitizados[$clave] = $valor;
            }
        }

        return $sanitizados;
    }

    /**
     * Calcula estadisticas de usuarios
     * @param array $usuarios
     * @return array
     */
    private function calcularEstadisticas(array $usuarios): array
    {
        $stats = [
            'total' => 0,
            'por_rol' => [],
            'activos_hoy' => 0
        ];

        // Bucle para contar usuarios por rol
        foreach ($usuarios as $usuario) {
            $stats['total']++;
            $rol = $usuario['rol'] ?? 'sin_rol';

            if (!isset($stats['por_rol'][$rol])) {
                $stats['por_rol'][$rol] = 0;
            }
            $stats['por_rol'][$rol]++;
        }

        return $stats;
    }

    // ==========================================
    // VISTAS PUBLICAS
    // ==========================================

    /**
     * Muestra pagina de login
     * @return string
     */
    public function index(): string
    {
        // Condicion: si ya esta logueado, redirigir
        if ($this->verificarSesion()) {
            return redirect()->to('/dashboard')->getBody() ?? '';
        }

        $this->registrarActividad('vista_login');
        return view('login');
    }

    /**
     * Muestra formulario de registro
     * @return string
     */
    public function registrar(): string
    {
        // Condicion: si ya esta logueado, redirigir
        if ($this->verificarSesion()) {
            return redirect()->to('/dashboard')->getBody() ?? '';
        }

        return view('registro', ['roles' => self::ROLES_PERMITIDOS]);
    }

    // ==========================================
    // AUTENTICACION
    // ==========================================

    /**
     * Guarda nuevo usuario con validaciones
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function guardar()
    {
        try {
            // Obtener y sanitizar datos
            $datosRaw = [
                'nombre' => $this->request->getPost('nombre'),
                'correo' => $this->request->getPost('correo'),
                'password' => $this->request->getPost('password'),
                'rol' => $this->request->getPost('rol') ?? 'aprendiz'
            ];

            $datos = $this->sanitizarDatos($datosRaw);

            // Validar correo
            if (!$this->validarCorreo($datos['correo'])) {
                throw new Exception($this->obtenerMensajeError('datos_invalidos') . ': correo inválido');
            }

            // Validar password
            $validacionPass = $this->validarPassword($datos['password']);
            if (!$validacionPass['valido']) {
                throw new Exception('Contraseña inválida: ' . $validacionPass['mensaje']);
            }

            // Validar rol permitido
            if (!in_array($datos['rol'], self::ROLES_PERMITIDOS)) {
                throw new Exception('Rol no permitido');
            }

            // Verificar si correo ya existe
            $existente = $this->usuarioModel->buscarPorCorreo($datos['correo']);
            if ($existente !== null) {
                throw new Exception($this->obtenerMensajeError('correo_duplicado'));
            }

            // Preparar datos para insercion
            $datosInsertar = [
                'nombre' => $datos['nombre'],
                'correo' => $datos['correo'],
                'password' => password_hash($datos['password'], PASSWORD_DEFAULT),
                'rol' => $datos['rol']
            ];

            // Intentar insertar
            $resultado = $this->usuarioModel->insert($datosInsertar);

            if ($resultado === false) {
                throw new Exception('Error al registrar usuario');
            }

            $this->registrarActividad('registro_usuario', ['correo' => $datos['correo']]);

            session()->setFlashdata('exito', 'Usuario registrado correctamente');
            return redirect()->to('/');

        } catch (Exception $e) {
            $this->registrarActividad('error_registro', ['error' => $e->getMessage()]);
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/');
        }
    }

    /**
     * Procesa inicio de sesion
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function login()
    {
        try {
            $correo = $this->request->getPost('correo');
            $password = $this->request->getPost('password');

            // Validaciones basicas
            if (empty($correo) || empty($password)) {
                throw new Exception('Correo y contraseña son requeridos');
            }

            if (!$this->validarCorreo($correo)) {
                throw new Exception('Formato de correo inválido');
            }

            // Buscar usuario
            $usuario = $this->usuarioModel->buscarPorCorreo($correo);

            // Condicion: verificar existencia
            if ($usuario === null) {
                $this->registrarActividad('login_fallido', ['motivo' => 'usuario_no_existe', 'correo' => $correo]);
                throw new Exception($this->obtenerMensajeError('usuario_no_existe'));
            }

            // Condicion: verificar password
            if (!password_verify($password, $usuario['password'])) {
                $this->registrarActividad('login_fallido', ['motivo' => 'password_incorrecto', 'correo' => $correo]);
                throw new Exception($this->obtenerMensajeError('password_incorrecto'));
            }

            // Crear sesion
            session()->set([
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'correo' => $usuario['correo'],
                'rol' => $usuario['rol'],
                'logueado' => true,
                'tiempo_login' => time()
            ]);

            $this->registrarActividad('login_exitoso', ['usuario_id' => $usuario['id']]);

            // Redirigir segun rol
            return $this->redirigirPorRol($usuario['rol']);

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/login');
        }
    }

    /**
     * Redirige al dashboard segun el rol del usuario
     * @param string $rol
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    private function redirigirPorRol(string $rol)
    {
        $rutas = [
            'admin' => '/dashboard',
            'mesero' => '/dashboard',
            'cocinero' => '/inicio',
            'cliente' => '/dashboard',
            'aprendiz' => '/dashboard'
        ];

        // Condicion: verificar si existe ruta para el rol
        $ruta = $rutas[$rol] ?? '/dashboard';

        return redirect()->to($ruta);
    }

    /**
     * Cierra sesion del usuario
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function logout()
    {
        $usuarioId = session('id');
        $this->registrarActividad('logout', ['usuario_id' => $usuarioId]);

        session()->destroy();

        return redirect()->to('/');
    }

    // ==========================================
    // DASHBOARD Y VISTAS PROTEGIDAS
    // ==========================================

    /**
     * Muestra dashboard principal
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function dashboard()
    {
        // Condicion: verificar sesion
        if (!$this->verificarSesion()) {
            session()->setFlashdata('error', $this->obtenerMensajeError('sesion_expirada'));
            return redirect()->to('/');
        }

        // Verificar tiempo de sesion (2 horas)
        $tiempoLogin = session('tiempo_login') ?? 0;
        $tiempoActual = time();
        $tiempoMaximo = 7200; // 2 horas en segundos

        if (($tiempoActual - $tiempoLogin) > $tiempoMaximo) {
            session()->destroy();
            session()->setFlashdata('error', $this->obtenerMensajeError('sesion_expirada'));
            return redirect()->to('/');
        }

        $datos = [
            'usuario' => session('nombre'),
            'rol' => session('rol')
        ];

        return view('dashboard', $datos);
    }

    // ==========================================
    // GESTION DE USUARIOS (Solo Admin)
    // ==========================================

    /**
     * Lista todos los usuarios
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function usuarios()
    {
        try {
            // Condicion: verificar permisos
            if (!$this->verificarSesion()) {
                throw new Exception($this->obtenerMensajeError('sesion_expirada'));
            }

            if (!$this->esAdmin()) {
                $this->registrarActividad('acceso_denegado', ['seccion' => 'usuarios']);
                throw new Exception($this->obtenerMensajeError('acceso_denegado'));
            }

            // Obtener usuarios con paginacion
            $usuarios = $this->usuarioModel->obtenerTodos();

            // Calcular estadisticas usando bucle
            $estadisticas = $this->calcularEstadisticas($usuarios);

            // Filtrar usuarios si hay parametro de busqueda
            $busqueda = $this->request->getGet('buscar');
            if (!empty($busqueda)) {
                $usuarios = $this->filtrarUsuarios($usuarios, $busqueda);
            }

            $datos = [
                'usuarios' => $usuarios,
                'estadisticas' => $estadisticas,
                'roles' => self::ROLES_PERMITIDOS
            ];

            return view('usuarios', $datos);

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/dashboard');
        }
    }

    /**
     * Filtra usuarios por criterio de busqueda
     * @param array $usuarios
     * @param string $busqueda
     * @return array
     */
    private function filtrarUsuarios(array $usuarios, string $busqueda): array
    {
        $filtrados = [];
        $busquedaLower = strtolower($busqueda);

        // Bucle para filtrar
        foreach ($usuarios as $usuario) {
            $coincide = false;

            // Verificar coincidencia en nombre
            if (strpos(strtolower($usuario['nombre']), $busquedaLower) !== false) {
                $coincide = true;
            }

            // Verificar coincidencia en correo
            if (strpos(strtolower($usuario['correo']), $busquedaLower) !== false) {
                $coincide = true;
            }

            // Verificar coincidencia en rol
            if (strpos(strtolower($usuario['rol']), $busquedaLower) !== false) {
                $coincide = true;
            }

            if ($coincide) {
                $filtrados[] = $usuario;
            }
        }

        return $filtrados;
    }

    /**
     * Muestra formulario de edicion de usuario
     * @param int $id
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function editar(int $id)
    {
        try {
            // Condicion: verificar permisos
            if (!$this->verificarSesion() || !$this->esAdmin()) {
                throw new Exception($this->obtenerMensajeError('acceso_denegado'));
            }

            // Validar ID
            if ($id <= 0) {
                throw new PageNotFoundException('Usuario no encontrado');
            }

            $usuario = $this->usuarioModel->find($id);

            // Condicion: verificar existencia
            if ($usuario === null) {
                throw new PageNotFoundException('Usuario no encontrado');
            }

            $datos = [
                'usuario' => $usuario,
                'roles' => self::ROLES_PERMITIDOS
            ];

            return view('editar_usuario', $datos);

        } catch (PageNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/usuarios');
        }
    }

    /**
     * Actualiza datos de usuario
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function actualizar(int $id)
    {
        try {
            // Condicion: verificar permisos
            if (!$this->verificarSesion() || !$this->esAdmin()) {
                throw new Exception($this->obtenerMensajeError('acceso_denegado'));
            }

            // Validar ID
            if ($id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            // Verificar que usuario existe
            $usuarioExistente = $this->usuarioModel->find($id);
            if ($usuarioExistente === null) {
                throw new Exception('Usuario no encontrado');
            }

            // Obtener y sanitizar datos
            $datosRaw = [
                'nombre' => $this->request->getPost('nombre'),
                'correo' => $this->request->getPost('correo'),
                'rol' => $this->request->getPost('rol')
            ];

            $datos = $this->sanitizarDatos($datosRaw);

            // Validaciones
            if (!$this->validarCorreo($datos['correo'])) {
                throw new Exception('Correo electrónico inválido');
            }

            if (!in_array($datos['rol'], self::ROLES_PERMITIDOS)) {
                throw new Exception('Rol no válido');
            }

            // Verificar correo duplicado (excepto el mismo usuario)
            $otroUsuario = $this->usuarioModel->buscarPorCorreo($datos['correo']);
            if ($otroUsuario !== null && $otroUsuario['id'] != $id) {
                throw new Exception($this->obtenerMensajeError('correo_duplicado'));
            }

            // Actualizar
            $resultado = $this->usuarioModel->update($id, $datos);

            if ($resultado === false) {
                throw new Exception('Error al actualizar usuario');
            }

            $this->registrarActividad('actualizar_usuario', ['usuario_id' => $id]);

            session()->setFlashdata('exito', 'Usuario actualizado correctamente');
            return redirect()->to('/usuarios');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/editar/' . $id);
        }
    }

    /**
     * Elimina usuario del sistema
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function eliminar(int $id)
    {
        try {
            // Condicion: verificar permisos
            if (!$this->verificarSesion() || !$this->esAdmin()) {
                throw new Exception($this->obtenerMensajeError('acceso_denegado'));
            }

            // Validar ID
            if ($id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            // No permitir auto-eliminacion
            if ($id == session('id')) {
                throw new Exception('No puedes eliminar tu propia cuenta');
            }

            // Verificar que usuario existe
            $usuario = $this->usuarioModel->find($id);
            if ($usuario === null) {
                throw new Exception('Usuario no encontrado');
            }

            // Eliminar
            $resultado = $this->usuarioModel->delete($id);

            if ($resultado === false) {
                throw new Exception('Error al eliminar usuario');
            }

            $this->registrarActividad('eliminar_usuario', ['usuario_id' => $id]);

            session()->setFlashdata('exito', 'Usuario eliminado correctamente');
            return redirect()->to('/usuarios');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/usuarios');
        }
    }

    // ==========================================
    // API ENDPOINTS
    // ==========================================

    /**
     * Retorna usuarios en formato JSON (API)
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiUsuarios()
    {
        try {
            if (!$this->verificarSesion() || !$this->esAdmin()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => $this->obtenerMensajeError('acceso_denegado')
                ])->setStatusCode(403);
            }

            $usuarios = $this->usuarioModel->obtenerTodos();
            $estadisticas = $this->calcularEstadisticas($usuarios);

            return $this->response->setJSON([
                'error' => false,
                'datos' => $usuarios,
                'estadisticas' => $estadisticas,
                'total' => count($usuarios)
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    // ==========================================
    // VISTAS ADICIONALES
    // ==========================================

    /**
     * Muestra pagina de inicio publica
     * @return string
     */
    public function inicio(): string
    {
        $this->registrarActividad('vista_inicio');
        return view('inicio');
    }

    /**
     * Retorna estadisticas del sistema (API)
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiEstadisticas()
    {
        try {
            // Condicion: verificar sesion
            if (!$this->verificarSesion()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(401);
            }

            // Obtener estadisticas
            $usuarios = $this->usuarioModel->obtenerTodos();
            $estadisticas = $this->calcularEstadisticas($usuarios);

            return $this->response->setJSON([
                'error' => false,
                'datos' => $estadisticas
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Verifica si hay sesion activa (API)
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiVerificarSesion()
    {
        return $this->response->setJSON([
            'logueado' => $this->verificarSesion(),
            'usuario' => session('nombre') ?? null,
            'rol' => session('rol') ?? null
        ]);
    }
}