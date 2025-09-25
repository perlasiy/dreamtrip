<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

// Database connection (adjust with your credentials)
$servername = "localhost";
$username = "root";
$password = "Hola1415";
$dbname = "based";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle cancellation request
if (isset($_POST['cancel_reserva'])) {
    $reserva_id = $_POST['reserva_id'];
    $stmt = $conn->prepare("DELETE FROM reservas WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $reserva_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// Fetch user reservations
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, destino, duracion, precio, fecha_reserva FROM reservas WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reservas - Dreamtrip</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <div id="navbar-placeholder"></div>

    <div class="container py-5">
        <h2 class="text-center mb-4">Mis Reservas</h2>
        <?php if ($result->num_rows > 0): ?>
            <div class="row">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['destino']); ?></h5>
                                <p class="card-text">
                                    Duración: <?php echo htmlspecialchars($row['duracion']); ?><br>
                                    Precio: $<?php echo htmlspecialchars($row['precio']); ?><br>
                                    Fecha de Reserva: <?php echo htmlspecialchars($row['fecha_reserva']); ?>
                                </p>
                                <form method="POST" onsubmit="return confirm('¿Estás seguro de cancelar esta reserva?');">
                                    <input type="hidden" name="reserva_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="cancel_reserva" class="btn btn-danger">Cancelar Reserva</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-center">No tienes reservas actualmente.</p>
        <?php endif; ?>
    </div>

    <div id="footer-placeholder"></div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        fetch('../includes/navbar.html')
            .then(res => res.text())
            .then(html => document.getElementById('navbar-placeholder').innerHTML = html);

        fetch('../includes/footer.html')
            .then(res => res.text())
            .then(html => document.getElementById('footer-placeholder').innerHTML = html);
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>