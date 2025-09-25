<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root"; // Camb subtle esto según tu configuración
$password = "Hola1415"; // Camb subtle esto según tu configuración
$dbname = "based";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener el ID del destino desde la URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("ID de destino no válido.");
}

// Consultar los datos del destino
$sql = "SELECT d.*, c.ciudad, c.pais FROM destinos d JOIN ciudades c ON d.ciudad_id = c.id WHERE d.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Destino no encontrado.");
}

$destino = $result->fetch_assoc();

// Consultar itinerario
$sql_itinerario = "SELECT * FROM itinerarios WHERE destino_id = ? ORDER BY dia";
$stmt_itinerario = $conn->prepare($sql_itinerario);
$stmt_itinerario->bind_param("i", $id);
$stmt_itinerario->execute();
$itinerario_result = $stmt_itinerario->get_result();
$itinerario = [];
while ($row = $itinerario_result->fetch_assoc()) {
    $itinerario[] = $row;
}
$stmt_itinerario->close();

// Consultar preguntas frecuentes
$sql_preguntas = "SELECT * FROM preguntas_frecuentes WHERE destino_id = ?";
$stmt_preguntas = $conn->prepare($sql_preguntas);
$stmt_preguntas->bind_param("i", $id);
$stmt_preguntas->execute();
$preguntas_result = $stmt_preguntas->get_result();
$preguntas_frecuentes = [];
while ($row = $preguntas_result->fetch_assoc()) {
    $preguntas_frecuentes[$row['pregunta']] = $row['respuesta'];
}
$stmt_preguntas->close();

// Consultar comentarios
$sql_comentarios = "SELECT c.*, u.nombre FROM comentarios c LEFT JOIN usuarios u ON c.usuario_id = u.id_pasajero WHERE c.destino_id = ? ORDER BY c.fecha DESC";
$stmt_comentarios = $conn->prepare($sql_comentarios);
$stmt_comentarios->bind_param("i", $id);
$stmt_comentarios->execute();
$comentarios_result = $stmt_comentarios->get_result();
$comentarios = [];
while ($row = $comentarios_result->fetch_assoc()) {
    $comentarios[] = $row;
}
$stmt_comentarios->close();

// Consultar servicios adicionales
$sql_servicios = "SELECT * FROM servicios_adicionales WHERE destino_id = ?";
$stmt_servicios = $conn->prepare($sql_servicios);
$stmt_servicios->bind_param("i", $id);
$stmt_servicios->execute();
$servicios_result = $stmt_servicios->get_result();
$servicios_adicionales = [];
while ($row = $servicios_result->fetch_assoc()) {
    $servicios_adicionales[$row['id']] = $row;
}
$stmt_servicios->close();

// Consultar disponibilidad
$sql_disponibilidad = "SELECT * FROM disponibilidad WHERE destino_id = ? AND disponible = TRUE";
$stmt_disponibilidad = $conn->prepare($sql_disponibilidad);
$stmt_disponibilidad->bind_param("i", $id);
$stmt_disponibilidad->execute();
$disponibilidad_result = $stmt_disponibilidad->get_result();
$disponibilidad = [];
while ($row = $disponibilidad_result->fetch_assoc()) {
    $disponibilidad[] = $row;
}
$stmt_disponibilidad->close();

// Consultar sugerencias
$sql_sugerencias = "SELECT id, titulo, precio, imagen FROM destinos WHERE id != ? LIMIT 3";
$stmt_sugerencias = $conn->prepare($sql_sugerencias);
$stmt_sugerencias->bind_param("i", $id);
$stmt_sugerencias->execute();
$sugerencias_result = $stmt_sugerencias->get_result();
$sugerencias = [];
while ($row = $sugerencias_result->fetch_assoc()) {
    $sugerencias[] = $row;
}
$stmt_sugerencias->close();

// Procesar la reserva
$booking_confirmation = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservar'])) {
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $horario = $_POST['horario'];
    $adultos = (int)$_POST['adultos'];
    $jóvenes = (int)$_POST['jóvenes'];
    $niños = (int)$_POST['niños'];
    $servicios_seleccionados = isset($_POST['servicios']) ? implode(',', $_POST['servicios']) : '';
    $total = (float)$_POST['total'];

    // Insertar la reserva
    $sql_reserva = "INSERT INTO reservas (destino_id, fecha_inicio, fecha_fin, horario, adultos, jóvenes, niños, servicios_adicionales, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_reserva = $conn->prepare($sql_reserva);
    $stmt_reserva->bind_param("isssiisds", $id, $fecha_inicio, $fecha_fin, $horario, $adultos, $jóvenes, $niños, $servicios_seleccionados, $total);
    $stmt_reserva->execute();
    $reserva_id = $conn->insert_id; // Obtener el ID de la reserva
    $stmt_reserva->close();

    // Generar mensaje de confirmación
    $booking_confirmation = "Reserva realizada con éxito! ID de reserva: $reserva_id";
}

// Cerrar la conexión
$conn->close();

// Precios de los tickets
$precios = [
    "adulto" => 282.00,
    "joven" => 168.00,
    "ninio" => 80.00
];

// Coordenadas genéricas para el mapa
$latitud = 25.7617; // Ejemplo: Miami
$longitud = -80.1918;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $destino['titulo']; ?> - Detalles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Flatpickr para el calendario -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --color-primario: #4A704C;
            --color-secundario: #9DB496;
        }
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand img {
            height: 40px;
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .booking-card {
            position: sticky;
            top: 20px;
        }
        .map {
            height: 300px;
            border-radius: 8px;
        }
        .footer {
            background-color: var(--color-secundario);
            color: #333;
        }
        .footer a {
            color: #333;
            text-decoration: none;
        }
        .footer a:hover {
            color: var(--color-primario);
        }
        .wishlist-btn i {
            color:rgb(252, 252, 252);
        }
        .wishlist-btn.active i {
            color: #ff0000;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm no-print">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="../assets/images/logo.jpg" alt="Logo"> 
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="lista.php">Destinos</a></li>
                    <li class="nav-item"><a class="nav-link" href="galeria.html">Galeria</a></li>
                    <li class="nav-item"><a class="nav-link" href="contacto.html">Contacto</a></li>
                    <li class="nav-item"><a class="nav-link fw-bold" href="nosotros.html">Nosotros</a></li>
                     <li class="nav-item"><a class="nav-link fw-bold" href="registro.php">Registrate</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Acceso</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <?php if ($booking_confirmation): ?>
            <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                <?php echo $booking_confirmation; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h1 class="card-title"><?php echo $destino['titulo']; ?></h1>
                        <p class="text-muted"><?php echo $destino['ciudad'] . ', ' . $destino['pais']; ?></p>
                        <button class="btn btn-outline-danger wishlist-btn no-print" onclick="toggleWishlist()">
                            <i class="fas fa-heart"></i> Añadir a Lista de Deseos
                        </button>
                        <button class="btn btn-outline-secondary ms-2 no-print" onclick="sharePage()">
                            <i class="fas fa-share"></i> Compartir
                        </button>
                    </div>
                </div>

                <!-- Image Gallery -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <img src="<?php echo $destino['imagen']; ?>" class="img-fluid rounded" alt="<?php echo $destino['titulo']; ?>">
                    </div>
                   
                </div>

                <!-- Overview -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">Descripción general del recorrido</h2>
                        <p><?php echo $destino['descripcion']; ?></p>
                    </div>
                </div>

                <!-- Included -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">¿Qué está incluido?</h2>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Transporte desde el punto de encuentro</li>
                            <li class="list-group-item">Guía turístico profesional</li>
                            <li class="list-group-item">Alojamiento básico</li>
                            <li class="list-group-item">Comidas según el itinerario</li>
                        </ul>
                    </div>
                </div>

                <!-- Itinerary -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">Itinerario</h2>
                        <ul class="list-group list-group-flush">
                            <?php if (empty($itinerario)): ?>
                                <li class="list-group-item">No hay itinerario disponible.</li>
                            <?php else: ?>
                                <?php foreach ($itinerario as $dia): ?>
                                    <li class="list-group-item">Día <?php echo $dia['dia']; ?>: <?php echo $dia['descripcion']; ?></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Map -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">Mapa del recorrido</h2>
                        
                        <div>
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d345615.5224915655!2d-99.73469289626219!3d19.39042707727347!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85ce0026db097507%3A0x54061076265ee841!2sCiudad%20de%20M%C3%A9xico%2C%20CDMX!5e1!3m2!1ses-419!2smx!4v1748529132571!5m2!1ses-419!2smx" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">

        </iframe>
    </div>
                    </div>
                </div>

                <!-- Calendar Placeholder -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h2 class="card-title">Calendario de disponibilidad</h2>
                        <!-- Duración -->
                    <div class="col-12 col-md-3 collapsible-section">
                        <div class="collapsible-header" onclick="toggleCollapse('duration')">
                            Duración
                            <span class="tooltip-text">Selecciona las fechas de inicio y fin de tu viaje usando el calendario.</span>
                        </div>
                        <div class="collapsible-body" id="duration">
                            <input type="text" id="dateRange" class="form-control" placeholder="Selecciona un rango de fechas">
                            <div class="date-summary" id="dateSummary"></div>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">Preguntas frecuentes</h2>
                        <ul class="list-group list-group-flush">
                            <?php if (empty($preguntas_frecuentes)): ?>
                                <li class="list-group-item">No hay preguntas frecuentes disponibles.</li>
                            <?php else: ?>
                                <?php foreach ($preguntas_frecuentes as $pregunta => $respuesta): ?>
                                    <li class="list-group-item">
                                        <?php echo $pregunta; ?>
                                        <p class="text-muted"><?php echo $respuesta; ?></p>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Reviews -->
                <div class="card mb-4 bg-light">
                    <div class="card-body">
                        <h2 class="card-title">Comentarios de clientes</h2>
                        <table class="table table-bordered">
                            <tr>
                                <td>Localización</td>
                                <td>5.0</td>
                                <td>Comodidad</td>
                                <td>5.0</td>
                            </tr>
                            <tr>
                                <td>Comida</td>
                                <td>5.0</td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                        <?php if (empty($comentarios)): ?>
                            <p>No hay comentarios disponibles.</p>
                        <?php else: ?>
                            <?php foreach ($comentarios as $comentario): ?>
                                <div class="d-flex mb-3">
                                    <img src="<?php echo $comentario['imagen'] ?: 'https://via.placeholder.com/50'; ?>" class="rounded-circle me-3" width="50" height="50" alt="Foto de usuario">
                                    <div>
                                        <p><strong><?php echo $comentario['nombre'] ?: 'Anónimo'; ?></strong> - <?php echo $comentario['calificacion']; ?>/5</p>
                                        <p><?php echo $comentario['comentario']; ?></p>
                                        <p class="text-muted"><?php echo $comentario['fecha']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Comment Form -->
                <div class="card mb-4 no-print">
                    <div class="card-body">
                        <h2 class="card-title">Déjanos tu comentario</h2>
                        <form>
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Nombre">
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" placeholder="Email">
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" placeholder="Comentario" rows="4"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Enviar</button>
                        </form>
                    </div>
                </div>

                <!-- Suggestions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title">También podría gustarte</h2>
                        <div class="row">
                            <?php if (empty($sugerencias)): ?>
                                <p>No hay sugerencias disponibles.</p>
                            <?php else: ?>
                                <?php foreach ($sugerencias as $sugerencia): ?>
                                    <div class="col-md-4">
                                        <div class="card">
                                            <img src="<?php echo $sugerencia['imagen']; ?>" class="card-img-top" alt="<?php echo $sugerencia['titulo']; ?>">
                                            <div class="card-body">
                                                <p class="card-text"><?php echo $sugerencia['titulo']; ?></p>
                                                <p class="card-text">$<?php echo number_format($sugerencia['precio'], 2); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Section -->
            <div class="col-lg-4">
                <div class="card booking-card">
                    <div class="card-body">
                        <h2 class="card-title">Reservar ahora</h2>
                        <p class="card-text fs-4">Desde $<?php echo number_format($destino['precio'], 2); ?></p>
                        <form method="POST" id="bookingForm">
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col">
                                        <input type="date" class="form-control" name="fecha_inicio" required>
                                    </div>
                                    <div class="col">
                                        <input type="date" class="form-control" name="fecha_fin" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <select class="form-select" name="horario" required>
                                    <option value="">Elige el horario</option>
                                    <?php foreach ($disponibilidad as $disp): ?>
                                        <?php if ($disp['horario']): ?>
                                            <option value="<?php echo $disp['horario']; ?>"><?php echo $disp['horario']; ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <h3>Tickets</h3>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label>Adulto (18+ años) $<?php echo number_format($precios['adulto'], 2); ?></label>
                                    <input type="number" class="form-control w-25" id="adultos" name="adultos" min="0" value="0" onchange="calcularTotal()">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label>Jóvenes (13-17 años) $<?php echo number_format($precios['joven'], 2); ?></label>
                                    <input type="number" class="form-control w-25" id="jóvenes" name="jóvenes" min="0" value="0" onchange="calcularTotal()">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label>Niños (0-12 años) $<?php echo number_format($precios['ninio'], 2); ?></label>
                                    <input type="number" class="form-control w-25" id="niños" name="niños" min="0" value="0" onchange="calcularTotal()">
                                </div>
                            </div>
                            <h3>Agregar más</h3>
                            <div class="mb-3">
                                <?php if (empty($servicios_adicionales)): ?>
                                    <p>No hay servicios adicionales disponibles.</p>
                                <?php else: ?>
                                    <?php foreach ($servicios_adicionales as $servicio): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="servicio_<?php echo $servicio['id']; ?>" name="servicios[]" value="<?php echo $servicio['id']; ?>" onchange="calcularTotal()">
                                            <label class="form-check-label" for="servicio_<?php echo $servicio['id']; ?>">
                                                <?php echo $servicio['nombre']; ?> $<?php echo number_format($servicio['precio'], 2); ?>
                                                <?php if ($servicio['precio_adulto'] && $servicio['precio_joven']): ?>
                                                    <small class="text-muted">(Adulto: $<?php echo number_format($servicio['precio_adulto'], 2); ?> - Joven: $<?php echo number_format($servicio['precio_joven'], 2); ?>)</small>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <p class="fs-5">Total: $<span id="total">0.00</span></p>
                            <input type="hidden" id="total_hidden" name="total" value="0.00">
                            <button type="submit" name="reservar" class="btn btn-success w-100">Reservar ahora</button>
                            <button type="button" class="btn btn-primary w-100 mt-2 no-print" onclick="printInvoice()">Imprimir Factura</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Template (Hidden by Default) -->
    <div class="container print-only d-none" id="invoice">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title">Factura de Reserva</h1>
                <h3><?php echo $destino['titulo']; ?></h3>
                <p><strong>Ciudad:</strong> <?php echo $destino['ciudad']; ?>, <?php echo $destino['pais']; ?></p>
                <hr>
                <h4>Detalles de la Reserva</h4>
                <p><strong>Fecha de Inicio:</strong> <span id="invoice_fecha_inicio"></span></p>
                <p><strong>Fecha de Fin:</strong> <span id="invoice_fecha_fin"></span></p>
                <p><strong>Horario:</strong> <span id="invoice_horario"></span></p>
                <p><strong>Adultos:</strong> <span id="invoice_adultos"></span> x $<?php echo number_format($precios['adulto'], 2); ?></p>
                <p><strong>Jóvenes:</strong> <span id="invoice_jóvenes"></span> x $<?php echo number_format($precios['joven'], 2); ?></p>
                <p><strong>Niños:</strong> <span id="invoice_niños"></span> x $<?php echo number_format($precios['ninio'], 2); ?></p>
                <h4>Servicios Adicionales</h4>
                <ul id="invoice_servicios"></ul>
                <p><strong>Total:</strong> $<span id="invoice_total"></span></p>
                <p><strong>Fecha de Emisión:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer py-4 no-print">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <h5>Contactanos</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Unido</a></li>
                        <li><a href="#">Sobre nosotros</a></li>
                        <li><a href="#">Tours</a></li>
                        <li><a href="#">Paquetes</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Información de contacto</h5>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.<br>Quisque pharetra condimentum.</p>
                </div>
                <div class="col-md-3">
                    <h5>Enlaces rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="#">Inicio</a></li>
                        <li><a href="#">Destinos</a></li>
                        <li><a href="#">Promociones</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Síguenos</h5>
                    <div class="d-flex gap-3">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <p>© Copyright 2025 DreamTrip. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
 <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr JS -->
   
    <script>
        function initMap() {
            const destinoLocation = { lat: <?php echo $latitud; ?>, lng: <?php echo $longitud; ?> };
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 10,
                center: destinoLocation,
            });
            new google.maps.Marker({
                position: destinoLocation,
                map: map,
                title: "<?php echo $destino['titulo']; ?>",
            });
        }

        function calcularTotal() {
            let total = 0;
            const adultos = parseInt(document.getElementById('adultos').value) || 0;
            const jóvenes = parseInt(document.getElementById('jóvenes').value) || 0;
            const niños = parseInt(document.getElementById('niños').value) || 0;

            total += adultos * <?php echo $precios['adulto']; ?>;
            total += jóvenes * <?php echo $precios['joven']; ?>;
            total += niños * <?php echo $precios['ninio']; ?>;

            <?php foreach ($servicios_adicionales as $servicio): ?>
                if (document.getElementById('servicio_<?php echo $servicio['id']; ?>').checked) {
                    total += <?php echo $servicio['precio']; ?>;
                }
            <?php endforeach; ?>

            document.getElementById('total').textContent = total.toFixed(2);
            document.getElementById('total_hidden').value = total.toFixed(2);
        }

        function printInvoice() {
            const form = document.getElementById('bookingForm');
            const fecha_inicio = form.querySelector('input[name="fecha_inicio"]').value;
            const fecha_fin = form.querySelector('input[name="fecha_fin"]').value;
            const horario = form.querySelector('select[name="horario"]').value;
            const adultos = form.querySelector('input[name="adultos"]').value;
            const jóvenes = form.querySelector('input[name="jóvenes"]').value;
            const niños = form.querySelector('input[name="niños"]').value;
            const total = document.getElementById('total').textContent;
            const servicios = [];
            <?php foreach ($servicios_adicionales as $servicio): ?>
                if (document.getElementById('servicio_<?php echo $servicio['id']; ?>').checked) {
                    servicios.push("<?php echo $servicio['nombre']; ?> ($<?php echo number_format($servicio['precio'], 2); ?>)");
                }
            <?php endforeach; ?>

            document.getElementById('invoice_fecha_inicio').textContent = fecha_inicio || 'No especificado';
            document.getElementById('invoice_fecha_fin').textContent = fecha_fin || 'No especificado';
            document.getElementById('invoice_horario').textContent = horario || 'No especificado';
            document.getElementById('invoice_adultos').textContent = adultos || 0;
            document.getElementById('invoice_jóvenes').textContent = jóvenes || 0;
            document.getElementById('invoice_niños').textContent = niños || 0;
            document.getElementById('invoice_total').textContent = total;
            const serviciosList = document.getElementById('invoice_servicios');
            serviciosList.innerHTML = servicios.length ? servicios.map(s => `<li>${s}</li>`).join('') : '<li>Ninguno</li>';

            window.print();
        }

        function toggleWishlist() {
            const btn = document.querySelector('.wishlist-btn');
            btn.classList.toggle('active');
            const icon = btn.querySelector('i');
            icon.classList.toggle('fas');
            icon.classList.toggle('far');
            btn.querySelector('span') ? btn.querySelector('span').textContent = btn.classList.contains('active') ? 'Quitar de Lista de Deseos' : 'Añadir a Lista de Deseos' : null;
        }

        function sharePage() {
            const shareData = {
                title: '<?php echo $destino['titulo']; ?>',
                text: '¡Mira este increíble destino en DreamTrip!',
                url: window.location.href
            };
            if (navigator.share) {
                navigator.share(shareData).catch(err => console.error('Error al compartir:', err));
            } else {
                const url = encodeURIComponent(shareData.url);
                const text = encodeURIComponent(shareData.text);
                const twitterUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
                window.open(twitterUrl, '_blank');
            }
        }

        window.onload = function() {
            calcularTotal();
        };

        
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=TU_CLAVE_API_AQUI&callback=initMap" async defer></script>
</body>
</html>
```