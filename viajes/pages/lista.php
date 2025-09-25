<?php
// Conexión a la base de datos
$servername = "localhost";
$username = "root"; // Cambia esto según tu configuración
$password = "Hola1415"; // Cambia esto según tu configuración
$dbname = "based";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener filtros de la URL (si existen, para la carga inicial)
$tipo_recorrido = isset($_GET['tipo_recorrido']) ? $_GET['tipo_recorrido'] : '';
$precio = isset($_GET['precio']) ? (int)$_GET['precio'] : 0;
$duracion = isset($_GET['duracion']) ? $_GET['duracion'] : '';
$calificacion = isset($_GET['calificacion']) ? $_GET['calificacion'] : '';

// Construir la consulta SQL con JOIN para obtener los datos de destinos y ciudades
$sql = "SELECT d.*, c.ciudad, c.pais 
        FROM destinos d 
        JOIN ciudades c ON d.ciudad_id = c.id 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($tipo_recorrido)) {
    $sql .= " AND d.tipo_recorrido = ?";
    $params[] = $tipo_recorrido;
    $types .= "s";
}

if ($precio > 0) {
    $sql .= " AND d.precio <= ?";
    $params[] = $precio;
    $types .= "d";
}

if (!empty($duracion)) {
    $sql .= " AND d.duracion = ?";
    $params[] = $duracion;
    $types .= "s";
}

if (!empty($calificacion)) {
    $sql .= " AND d.calificacion = ?";
    $params[] = $calificacion;
    $types .= "s";
}

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Obtener los destinos filtrados
$destinos_filtrados = [];
while ($row = $result->fetch_assoc()) {
    $destinos_filtrados[] = $row;
}

// Obtener el conteo total de resultados (sin filtros para mostrar en el mensaje)
$sql_count = "SELECT COUNT(*) as total FROM destinos";
$result_count = $conn->query($sql_count);
$total_destinos = $result_count->fetch_assoc()['total'];

// Cerrar la conexión
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinos Turísticos</title>
    <style>
        /* Estilos generales */
        body {
            font-family: sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        @media (min-width: 768px) {
            .container {
                flex-direction: row;
            }
        }

        /* Header */
        header {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header .title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a; /* Azul oscuro */
        }

        header button {
            background-color: #22c55e; /* Verde */
            color: #ffffff;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        header button:hover {
            background-color: #16a34a; /* Verde más oscuro */
        }

        /* Filtros (Izquierda) */
        .filters {
            background-color: #ffffff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
        }

        @media (min-width: 768px) {
            .filters {
                width: 25%;
            }
        }

        .filters h2 {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 16px;
        }

        .filters p {
            color: #4b5563; /* Gris oscuro */
            margin-bottom: 16px;
        }

        .filters form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .filters label {
            font-size: 14px;
            font-weight: medium;
            color: #374151; /* Gris oscuro */
        }

        .filters input,
        .filters select {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db; /* Gris claro */
            border-radius: 4px;
            font-size: 14px;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            border-color: #3b82f6; /* Azul */
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        /* Tarjetas de destinos (Derecha) */
        .destinos {
            width: 100%;
        }

        @media (min-width: 768px) {
            .destinos {
                width: 75%;
            }
        }

        .destino-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 16px;
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }

        .destino-card img {
            width: 33%;
            height: 160px;
            object-fit: cover;
            border-radius: 8px;
        }

        .destino-card .info {
            flex: 1;
        }

        .destino-card .info h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1e3a8a;
            margin: 0;
        }

        .destino-card .info .location {
            font-size: 14px;
            color: #4b5563;
            margin-top: 4px;
        }

        .destino-card .info p {
            font-size: 14px;
            color: #4b5563;
            margin-top: 8px;
        }

        .destino-card .info .detail {
            font-size: 12px;
            color: #6b7280; /* Gris */
            margin-top: 8px;
        }

        .destino-card .info .detail strong {
            color: #374151;
        }

        .destino-card .info .tags {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .destino-card .info .tags span {
            font-size: 12px;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 12px;
        }

        .destino-card .info .tags .tag-green {
            background-color: #dcfce7; /* Verde claro */
            color: #15803d; /* Verde oscuro */
        }

        .destino-card .info .tags .tag-gray {
            background-color: #f3f4f6; /* Gris claro */
            color: #4b5563;
        }

        .destino-card .price {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: space-between;
        }

        .destino-card .price p {
            margin: 0;
            text-align: right;
        }

        .destino-card .price .label {
            font-size: 12px;
            color: #6b7280;
        }

        .destino-card .price .amount {
            font-size: 24px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .destino-card .price a {
            background-color: #2563eb;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }

        .destino-card .price a:hover {
            background-color: #1d4ed8;
        }

        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 16px;
        }

        .pagination nav {
            display: inline-flex;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .pagination a {
            padding: 8px 12px;
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }

        .pagination a:hover {
            background-color: #f9fafb;
        }

        .pagination a.active {
            color: #2563eb;
        }

        .pagination a:first-child {
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
        }

        .pagination a:last-child {
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }

        /* Footer */
        footer {
            background-color: #1f2937; /* Gris oscuro */
            color: #ffffff;
            padding: 24px;
            margin-top: 24px;
        }

        footer .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        @media (min-width: 768px) {
            footer .footer-content {
                flex-direction: row;
                justify-content: space-between;
            }
        }

        footer .footer-content p,
        footer .footer-content a {
            margin: 0;
            color: #ffffff;
            text-decoration: none;
        }

        footer .footer-content a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Header -->
   

    <!-- Main Content -->
    <div class="container">
        <!-- Filtros (Izquierda) -->
        <div class="filters">
            <h2>Explora todas las actividades que puedes hacer</h2>
            <p id="resultados"><?php echo count($destinos_filtrados); ?> resultados</p>

            <form id="filtros-form">
                <div>
                    <label>¿Cuándo viajas?</label>
                    <input type="date" name="fecha" onchange="filtrarDestinos()">
                </div>

                <div>
                    <label>Tipo de recorrido</label>
                    <select name="tipo_recorrido" onchange="filtrarDestinos()">
                        <option value="">Seleccionar</option>
                        <option value="Tours de Naturaleza" <?php if ($tipo_recorrido === "Tours de Naturaleza") echo "selected"; ?>>Tours de Naturaleza</option>
                        <option value="Tours de Aventura" <?php if ($tipo_recorrido === "Tours de Aventura") echo "selected"; ?>>Tours de Aventura</option>
                        <option value="Tours en Barco" <?php if ($tipo_recorrido === "Tours en Barco") echo "selected"; ?>>Tours en Barco</option>
                        <option value="Tours Culturales" <?php if ($tipo_recorrido === "Tours Culturales") echo "selected"; ?>>Tours Culturales</option>
                    </select>
                </div>

                <div>
                    <label>Precio del paquete (máximo)</label>
                    <input type="number" name="precio" value="<?php echo $precio ?: ''; ?>" placeholder="Ej: 500" oninput="filtrarDestinos()">
                </div>

                <div>
                    <label>Duración</label>
                    <select name="duracion" onchange="filtrarDestinos()">
                        <option value="">Seleccionar</option>
                        <option value="1 Día" <?php if ($duracion === "1 Día") echo "selected"; ?>>1 Día</option>
                        <option value="2 Días 1 Noche" <?php if ($duracion === "2 Días 1 Noche") echo "selected"; ?>>2 Días 1 Noche</option>
                        <option value="3 Días 2 Noches" <?php if ($duracion === "3 Días 2 Noches") echo "selected"; ?>>3 Días 2 Noches</option>
                        <option value="4 Días 3 Noches" <?php if ($duracion === "4 Días 3 Noches") echo "selected"; ?>>4 Días 3 Noches</option>
                    </select>
                </div>

                <div>
                    <label>Calificación</label>
                    <select name="calificacion" onchange="filtrarDestinos()">
                        <option value="">Seleccionar</option>
                        <option value="5 Estrellas" <?php if ($calificacion === "5 Estrellas") echo "selected"; ?>>5 Estrellas</option>
                        <option value="4 Estrellas" <?php if ($calificacion === "4 Estrellas") echo "selected"; ?>>4 Estrellas</option>
                        <option value="3 Estrellas" <?php if ($calificacion === "3 Estrellas") echo "selected"; ?>>3 Estrellas</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Tarjetas de destinos (Derecha) -->
        <div class="destinos" id="destinos-container">
            <?php foreach ($destinos_filtrados as $destino): ?>
                <div class="destino-card">
                    <img src="<?php echo $destino['imagen']; ?>" alt="<?php echo $destino['titulo']; ?>">
                    <div class="info">
                        <h3><?php echo $destino['titulo']; ?></h3>
                        <p class="location"><?php echo $destino['ciudad'] . ', ' . $destino['pais']; ?></p>
                        <p><?php echo $destino['descripcion']; ?></p>
                        <p class="detail"><strong>Tipo:</strong> <?php echo $destino['tipo_recorrido']; ?></p>
                        <p class="detail"><strong>Duración:</strong> <?php echo $destino['duracion']; ?></p>
                        <p class="detail"><strong>Calificación:</strong> <?php echo $destino['calificacion']; ?></p>
                        <div class="tags">
                            <span class="tag-green">Mejor Precio Garantizado</span>
                            <span class="tag-gray">Cancelación Gratuita</span>
                        </div>
                    </div>
                    <div class="price">
                        <div>
                            <p class="label">Desde</p>
                            <p class="amount">$<?php echo number_format($destino['precio'], 2); ?></p>
                        </div>
                        <a href="detalles.php?id=<?php echo $destino['id']; ?>">Ver Detalles</a>
                    </div>
                </div>
            <?php endforeach; ?>
            <!-- Paginación -->
            <div class="pagination">
                <nav>
                    <a href="#">Anterior</a>
                    <a href="#" class="active">1</a>
                    <a href="#">2</a>
                    <a href="#">Siguiente</a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <p>© 2025</p>
            <div>
                <a href="#">Información de contacto</a>
                <a href="#">Enlace rápido</a>
                <a href="#">Síguenos</a>
            </div>
        </div>
    </footer>

    <!-- Script para filtrar dinámicamente -->
    <script>
        function filtrarDestinos() {
            // Obtener los valores de los filtros
            const form = document.getElementById('filtros-form');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData).toString();

            // Hacer solicitud AJAX
            fetch('filtrar_destinos.php?' + params)
                .then(response => response.text())
                .then(data => {
                    // Actualizar las tarjetas
                    const container = document.getElementById('destinos-container');
                    container.innerHTML = data || '<p>No se encontraron resultados.</p>';

                    // Actualizar el conteo de resultados
                    const resultados = document.getElementById('resultados');
                    const numResultados = data ? (data.match(/destino-card/g) || []).length : 0;
                    resultados.textContent = numResultados + ' resultados';
                })
                .catch(error => {
                    console.error('Error al filtrar destinos:', error);
                });
        }
    </script>
</body>
</html>