<?php
session_start();
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "Hola1415";
$dbname = "based";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? AND password = ?");
$stmt->bind_param("ss", $email, $password); // In production, use password_hash and password_verify
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $_SESSION['user_id'] = $user['id'];
    echo json_encode(['success' => true, 'user' => ['id' => $user['id'], 'email' => $user['email']]]);
} else {
    echo json_encode(['success' => false, 'message' => 'Correo o contraseña incorrectos']);
}

$stmt->close();
$conn->close();
?>