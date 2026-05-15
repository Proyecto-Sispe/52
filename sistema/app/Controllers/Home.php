<?php

namespace App\Controllers;

use Exception;

/**
 * Controlador Home - Pagina de Inicio "Nosotros"
 * Maneja la pagina principal del restaurante con informacion institucional
 * 
 * Funciones PHP utilizadas:
 * - Excepciones (try-catch) para manejo de errores
 * - Condiciones (if-else) para mostrar contenido dinamico
 * - Bucles (foreach) para procesar datos
 * - Operaciones para calculos y formateo
 */
class Home extends BaseController
{
    /**
     * Informacion del restaurante
     * @var array
     */
    private const INFO_RESTAURANTE = [
        'nombre' => 'Restaurante SISPE',
        'slogan' => 'Sabor que inspira, servicio que enamora',
        'descripcion' => 'Somos un restaurante comprometido con ofrecer la mejor experiencia gastronómica, combinando ingredientes frescos, recetas tradicionales y un servicio excepcional.',
        'telefono' => '+57 300 123 4567',
        'email' => 'contacto@restaurantesispe.com',
        'direccion' => 'Calle Principal #123, Centro, Colombia',
        'horario' => [
            'lunes_viernes' => '11:00 AM - 10:00 PM',
            'sabado' => '12:00 PM - 11:00 PM',
            'domingo' => '12:00 PM - 8:00 PM'
        ]
    ];

    /**
     * Valores del restaurante
     * @var array
     */
    private const VALORES = [
        [
            'icono' => 'fa-heart',
            'titulo' => 'Pasión',
            'descripcion' => 'Cocinamos con amor y dedicación cada uno de nuestros platos.'
        ],
        [
            'icono' => 'fa-leaf',
            'titulo' => 'Frescura',
            'descripcion' => 'Utilizamos ingredientes frescos y de la más alta calidad.'
        ],
        [
            'icono' => 'fa-users',
            'titulo' => 'Servicio',
            'descripcion' => 'Nuestro equipo está comprometido con tu satisfacción.'
        ],
        [
            'icono' => 'fa-star',
            'titulo' => 'Excelencia',
            'descripcion' => 'Buscamos la perfección en cada detalle de tu experiencia.'
        ]
    ];

    /**
     * Equipo del restaurante
     * @var array
     */
    private const EQUIPO = [
        [
            'nombre' => 'Chef Carlos Rodríguez',
            'cargo' => 'Chef Ejecutivo',
            'descripcion' => 'Con más de 15 años de experiencia en cocina internacional.',
            'imagen' => 'chef1.jpg'
        ],
        [
            'nombre' => 'María González',
            'cargo' => 'Gerente General',
            'descripcion' => 'Líder apasionada por el servicio al cliente.',
            'imagen' => 'gerente.jpg'
        ],
        [
            'nombre' => 'Juan Pérez',
            'cargo' => 'Sommelier',
            'descripcion' => 'Experto en maridaje y selección de vinos.',
            'imagen' => 'sommelier.jpg'
        ]
    ];

    /**
     * Estadisticas del restaurante
     * @var array
     */
    private const ESTADISTICAS = [
        'anos_experiencia' => 10,
        'clientes_satisfechos' => 50000,
        'platos_menu' => 80,
        'chefs_expertos' => 5
    ];

    // ==========================================
    // FUNCIONES AUXILIARES
    // ==========================================

    /**
     * Registra actividad del visitante
     * @param string $accion
     * @param array $datos
     * @return void
     */
    private function registrarActividad(string $accion, array $datos = []): void
    {
        $log = [
            'fecha' => date('Y-m-d H:i:s'),
            'accion' => $accion,
            'ip' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'datos' => json_encode($datos)
        ];

        log_message('info', 'Actividad Home: ' . json_encode($log));
    }

    /**
     * Formatea numero con separador de miles
     * Operacion: Formato numerico
     * @param int $numero
     * @return string
     */
    private function formatearNumero(int $numero): string
    {
        // Condicion: si es mayor a 1000, usar formato con K
        if ($numero >= 1000) {
            return number_format($numero, 0, ',', '.');
        }
        return (string)$numero;
    }

    /**
     * Obtiene el dia de la semana actual
     * @return string
     */
    private function obtenerDiaActual(): string
    {
        $dias = [
            'Sunday' => 'domingo',
            'Monday' => 'lunes',
            'Tuesday' => 'martes',
            'Wednesday' => 'miercoles',
            'Thursday' => 'jueves',
            'Friday' => 'viernes',
            'Saturday' => 'sabado'
        ];

        $diaIngles = date('l');
        return $dias[$diaIngles] ?? 'lunes';
    }

    /**
     * Verifica si el restaurante esta abierto
     * Usa condiciones y operaciones
     * @return array ['abierto' => bool, 'mensaje' => string]
     */
    private function verificarHorario(): array
    {
        $diaActual = $this->obtenerDiaActual();
        $horaActual = (int)date('H');
        $minutosActual = (int)date('i');

        // Determinar horario segun dia
        $horaApertura = 11;
        $horaCierre = 22;

        // Condicion: ajustar segun dia
        if ($diaActual === 'sabado') {
            $horaApertura = 12;
            $horaCierre = 23;
        } elseif ($diaActual === 'domingo') {
            $horaApertura = 12;
            $horaCierre = 20;
        }

        // Operacion: verificar si esta dentro del horario
        $horaDecimal = $horaActual + ($minutosActual / 60);

        if ($horaDecimal >= $horaApertura && $horaDecimal < $horaCierre) {
            $horasRestantes = $horaCierre - $horaActual;
            return [
                'abierto' => true,
                'mensaje' => "Abierto - Cerramos en {$horasRestantes} horas"
            ];
        } else {
            // Condicion: calcular tiempo hasta apertura
            if ($horaActual < $horaApertura) {
                $horasParaAbrir = $horaApertura - $horaActual;
                return [
                    'abierto' => false,
                    'mensaje' => "Cerrado - Abrimos en {$horasParaAbrir} horas"
                ];
            }
            return [
                'abierto' => false,
                'mensaje' => "Cerrado - Abrimos mañana"
            ];
        }
    }

    /**
     * Obtiene testimonios de clientes
     * @return array
     */
    private function obtenerTestimonios(): array
    {
        // En produccion, esto vendria de la base de datos
        return [
            [
                'nombre' => 'Ana María López',
                'comentario' => 'La mejor experiencia gastronómica que he tenido. Los platos son exquisitos y el servicio es impecable.',
                'calificacion' => 5,
                'fecha' => '2024-01-15'
            ],
            [
                'nombre' => 'Roberto Sánchez',
                'comentario' => 'Un lugar acogedor con comida deliciosa. El ambiente familiar es perfecto para cualquier ocasión.',
                'calificacion' => 5,
                'fecha' => '2024-01-10'
            ],
            [
                'nombre' => 'Carmen Díaz',
                'comentario' => 'Increíble relación calidad-precio. Definitivamente volveré con mi familia.',
                'calificacion' => 4,
                'fecha' => '2024-01-05'
            ]
        ];
    }

    /**
     * Calcula promedio de calificaciones
     * Usa bucle y operaciones
     * @param array $testimonios
     * @return float
     */
    private function calcularPromedioCalificacion(array $testimonios): float
    {
        // Condicion: verificar si hay testimonios
        if (empty($testimonios)) {
            return 0;
        }

        $suma = 0;
        $contador = 0;

        // Bucle: sumar calificaciones
        foreach ($testimonios as $testimonio) {
            if (isset($testimonio['calificacion'])) {
                $suma += $testimonio['calificacion'];
                $contador++;
            }
        }

        // Operacion: calcular promedio
        return ($contador > 0) ? round($suma / $contador, 1) : 0;
    }

    /**
     * Genera estrellas HTML segun calificacion
     * Usa bucle
     * @param int $calificacion
     * @return string
     */
    private function generarEstrellas(int $calificacion): string
    {
        $estrellas = '';

        // Bucle: generar estrellas llenas
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $calificacion) {
                $estrellas .= '<i class="fas fa-star text-warning"></i>';
            } else {
                $estrellas .= '<i class="far fa-star text-warning"></i>';
            }
        }

        return $estrellas;
    }

    /**
     * Obtiene eventos o promociones especiales
     * @return array
     */
    private function obtenerEventos(): array
    {
        $eventos = [];
        $fechaActual = date('Y-m-d');

        // Lista de eventos
        $eventosBase = [
            [
                'titulo' => 'Noche de Vinos',
                'descripcion' => 'Maridaje especial con selección de vinos premium',
                'fecha' => date('Y-m-d', strtotime('+3 days')),
                'tipo' => 'evento'
            ],
            [
                'titulo' => '2x1 en Postres',
                'descripcion' => 'Todos los postres al 2x1 los días martes',
                'fecha' => date('Y-m-d', strtotime('next tuesday')),
                'tipo' => 'promocion'
            ],
            [
                'titulo' => 'Menú Ejecutivo',
                'descripcion' => 'Almuerzo completo a precio especial de lunes a viernes',
                'fecha' => $fechaActual,
                'tipo' => 'promocion'
            ]
        ];

        // Bucle: filtrar eventos vigentes
        foreach ($eventosBase as $evento) {
            // Condicion: solo incluir eventos futuros o actuales
            if ($evento['fecha'] >= $fechaActual) {
                $eventos[] = $evento;
            }
        }

        return $eventos;
    }

    // ==========================================
    // VISTAS PRINCIPALES
    // ==========================================

    /**
     * Pagina de inicio - Nosotros
     * @return string
     */
    public function index()
    {
        try {
            $this->registrarActividad('vista_inicio');

            // Obtener datos dinamicos
            $testimonios = $this->obtenerTestimonios();
            $eventos = $this->obtenerEventos();
            $estadoHorario = $this->verificarHorario();
            $promedioCalificacion = $this->calcularPromedioCalificacion($testimonios);

            // Formatear estadisticas
            $estadisticasFormateadas = [];
            foreach (self::ESTADISTICAS as $clave => $valor) {
                $estadisticasFormateadas[$clave] = [
                    'valor' => $valor,
                    'formateado' => $this->formatearNumero($valor)
                ];
            }

            // Procesar testimonios con estrellas
            $testimoniosProcesados = [];
            foreach ($testimonios as $testimonio) {
                $testimonio['estrellas'] = $this->generarEstrellas($testimonio['calificacion']);
                $testimoniosProcesados[] = $testimonio;
            }

            // Preparar datos para la vista
            $datos = [
                'info' => self::INFO_RESTAURANTE,
                'valores' => self::VALORES,
                'equipo' => self::EQUIPO,
                'estadisticas' => $estadisticasFormateadas,
                'testimonios' => $testimoniosProcesados,
                'eventos' => $eventos,
                'horario' => $estadoHorario,
                'promedio_calificacion' => $promedioCalificacion,
                'dia_actual' => $this->obtenerDiaActual()
            ];

            return view('inicio', $datos);

        } catch (Exception $e) {
            log_message('error', 'Error en pagina de inicio: ' . $e->getMessage());
            return view('inicio', [
                'info' => self::INFO_RESTAURANTE,
                'error' => 'Ocurrió un error al cargar la página'
            ]);
        }
    }

    /**
     * Pagina de contacto
     * @return string
     */
    public function contacto()
    {
        try {
            $this->registrarActividad('vista_contacto');

            $datos = [
                'info' => self::INFO_RESTAURANTE
            ];

            return view('contacto', $datos);

        } catch (Exception $e) {
            log_message('error', 'Error en pagina de contacto: ' . $e->getMessage());
            session()->setFlashdata('error', 'Error al cargar la página');
            return redirect()->to('/');
        }
    }

    /**
     * Procesa formulario de contacto
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function enviarContacto()
    {
        try {
            // Obtener datos del formulario
            $nombre = $this->request->getPost('nombre');
            $correo = $this->request->getPost('correo');
            $telefono = $this->request->getPost('telefono');
            $mensaje = $this->request->getPost('mensaje');

            // Validaciones
            $errores = [];

            // Condicion: validar nombre
            if (empty($nombre) || strlen($nombre) < 3) {
                $errores[] = 'El nombre debe tener al menos 3 caracteres';
            }

            // Condicion: validar correo
            if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $errores[] = 'Ingresa un correo electrónico válido';
            }

            // Condicion: validar mensaje
            if (empty($mensaje) || strlen($mensaje) < 10) {
                $errores[] = 'El mensaje debe tener al menos 10 caracteres';
            }

            // Condicion: si hay errores, mostrarlos
            if (!empty($errores)) {
                throw new Exception(implode('. ', $errores));
            }

            // Registrar el mensaje de contacto
            $this->registrarActividad('mensaje_contacto', [
                'nombre' => $nombre,
                'correo' => $correo,
                'telefono' => $telefono
            ]);

            // Aqui iria la logica para enviar email o guardar en BD

            session()->setFlashdata('exito', 'Mensaje enviado correctamente. Te contactaremos pronto.');
            return redirect()->to('/contacto');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/contacto');
        }
    }

    /**
     * Pagina de reservaciones
     * @return string
     */
    public function reservaciones()
    {
        try {
            $this->registrarActividad('vista_reservaciones');

            $datos = [
                'info' => self::INFO_RESTAURANTE,
                'horarios' => self::INFO_RESTAURANTE['horario']
            ];

            return view('reservaciones', $datos);

        } catch (Exception $e) {
            log_message('error', 'Error en reservaciones: ' . $e->getMessage());
            return redirect()->to('/');
        }
    }

    /**
     * Procesa solicitud de reservacion
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function procesarReservacion()
    {
        try {
            // Obtener datos
            $nombre = $this->request->getPost('nombre');
            $telefono = $this->request->getPost('telefono');
            $fecha = $this->request->getPost('fecha');
            $hora = $this->request->getPost('hora');
            $personas = (int)$this->request->getPost('personas');
            $comentarios = $this->request->getPost('comentarios');

            // Validaciones
            $errores = [];

            if (empty($nombre)) {
                $errores[] = 'El nombre es obligatorio';
            }

            if (empty($telefono)) {
                $errores[] = 'El teléfono es obligatorio';
            }

            if (empty($fecha)) {
                $errores[] = 'La fecha es obligatoria';
            } elseif (strtotime($fecha) < strtotime(date('Y-m-d'))) {
                $errores[] = 'La fecha no puede ser anterior a hoy';
            }

            if ($personas < 1 || $personas > 20) {
                $errores[] = 'El número de personas debe estar entre 1 y 20';
            }

            if (!empty($errores)) {
                throw new Exception(implode('. ', $errores));
            }

            // Registrar reservacion
            $this->registrarActividad('solicitud_reservacion', [
                'nombre' => $nombre,
                'fecha' => $fecha,
                'hora' => $hora,
                'personas' => $personas
            ]);

            session()->setFlashdata('exito', 'Reservación solicitada correctamente. Te contactaremos para confirmar.');
            return redirect()->to('/reservaciones');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/reservaciones');
        }
    }

    /**
     * Pagina de galeria
     * @return string
     */
    public function galeria()
    {
        try {
            $this->registrarActividad('vista_galeria');

            // Imagenes de la galeria (en produccion vendrian de la BD)
            $imagenes = [
                ['src' => 'plato1.jpg', 'titulo' => 'Plato Especial', 'categoria' => 'platos'],
                ['src' => 'interior1.jpg', 'titulo' => 'Nuestro Salón', 'categoria' => 'ambiente'],
                ['src' => 'equipo1.jpg', 'titulo' => 'Nuestro Equipo', 'categoria' => 'equipo'],
                ['src' => 'plato2.jpg', 'titulo' => 'Entrada Gourmet', 'categoria' => 'platos'],
                ['src' => 'interior2.jpg', 'titulo' => 'Terraza', 'categoria' => 'ambiente'],
                ['src' => 'plato3.jpg', 'titulo' => 'Postre Artesanal', 'categoria' => 'platos']
            ];

            // Filtrar por categoria si se especifica
            $categoriaFiltro = $this->request->getGet('categoria');
            
            if (!empty($categoriaFiltro)) {
                $imagenesFiltradas = [];
                foreach ($imagenes as $imagen) {
                    if ($imagen['categoria'] === $categoriaFiltro) {
                        $imagenesFiltradas[] = $imagen;
                    }
                }
                $imagenes = $imagenesFiltradas;
            }

            $datos = [
                'imagenes' => $imagenes,
                'categoriaActual' => $categoriaFiltro
            ];

            return view('galeria', $datos);

        } catch (Exception $e) {
            log_message('error', 'Error en galeria: ' . $e->getMessage());
            return redirect()->to('/');
        }
    }

    /**
     * API: Obtener informacion del restaurante
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiInfo()
    {
        return $this->response->setJSON([
            'exito' => true,
            'datos' => [
                'info' => self::INFO_RESTAURANTE,
                'horario' => $this->verificarHorario(),
                'estadisticas' => self::ESTADISTICAS
            ]
        ]);
    }

    /**
     * API: Obtener testimonios
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiTestimonios()
    {
        $testimonios = $this->obtenerTestimonios();
        $promedio = $this->calcularPromedioCalificacion($testimonios);

        return $this->response->setJSON([
            'exito' => true,
            'datos' => $testimonios,
            'promedio' => $promedio,
            'total' => count($testimonios)
        ]);
    }
}
