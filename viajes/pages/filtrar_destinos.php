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

// Obtener filtros de la solicitud (enviados mediante AJAX)
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

// Cerrar la conexión
$stmt->close();
$conn->close();

// Generar el HTML de las tarjetas
foreach ($destinos_filtrados as $destino) {
    echo '<div class="destino-card">';
    echo '    <img src="' . $destino['imagen'] . '" alt="' . $destino['titulo'] . '">';
    echo '    <div class="info">';
    echo '        <h3>' . $destino['titulo'] . '</h3>';
    echo '        <p class="location">' . $destino['ciudad'] . ', ' . $destino['pais'] . '</p>';
    echo '        <p>' . $destino['descripcion'] . '</p>';
    echo '        <p class="detail"><strong>Tipo:</strong> ' . $destino['tipo_recorrido'] . '</p>';
    echo '        <p class="detail"><strong>Duración:</strong> ' . $destino['duracion'] . '</p>';
    echo '        <p class="detail"><strong>Calificación:</strong> ' . $destino['calificacion'] . '</p>';
    echo '        <div class="tags">';
    echo '            <span class="tag-green">Mejor Precio Garantizado</span>';
    echo '            <span class="tag-gray">Cancelación Gratuita</span>';
    echo '        </div>';
    echo '    </div>';
    echo '    <div class="price">';
    echo '        <div>';
    echo '            <p class="label">Desde</p>';
    echo '            <p class="amount">$' . number_format($destino['precio'], 2) . '</p>';
    echo '        </div>';
    echo '        <a href="detalle.php?id=' . $destino['id'] . '">Ver Detalles</a>';
    echo '    </div>';
    echo '</div>';
}
?>