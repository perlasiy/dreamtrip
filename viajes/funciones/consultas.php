<?php
session_start();
require_once 'conexion.php';

function procesarRegistro($conexion) {
    $data = [
        'nombre' => '',
        'email' => '',
        'errores' => [],
        'exito' => false
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data['nombre'] = trim($_POST['nombre'] ?? '');
        $data['email'] = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validaciones
        if (empty($data['nombre'])) {
            $data['errores'][] = "El nombre es requerido";
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $data['errores'][] = "Ingrese un email válido";
        }
        if (empty($password) || strlen($password) < 6) {
            $data['errores'][] = "La contraseña debe tener al menos 6 caracteres";
        }

        // Si no hay errores, procesar el registro
        if (empty($data['errores'])) {
            $conexion->begin_transaction();
            try {
                // Verificar si el email ya existe
                $stmt = $conexion->prepare("SELECT Id_pasajero FROM usuarios WHERE email = ?");
                $stmt->bind_param("s", $data['email']);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $data['errores'][] = "Este email ya está registrado";
                } else {
                    // Insertar nuevo usuario
                    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->bind_param("sss", $data['nombre'], $data['email'], $hashed_password);
                    $stmt->execute();

                    $conexion->commit();
                    $data['exito'] = true;
                    $_SESSION['registro_exitoso'] = true;
                }
            } catch (Exception $e) {
                $conexion->rollback();
                $data['errores'][] = "Error al registrar: " . $e->getMessage();
            }
        }
    }

    // Manejar mensaje de éxito
    if (isset($_SESSION['registro_exitoso']) && $_SESSION['registro_exitoso']) {
        $data['exito'] = true;
        unset($_SESSION['registro_exitoso']);
    }

    return $data;
}
?>