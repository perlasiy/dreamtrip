<?php
// recuperar_contrasena.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../funciones/conexion.php");

$email = $password = $confirm_password = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validaciones
    if (empty($email)) {
        $errores[] = "El email es requerido";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Ingrese un email válido";
    }
    
    if (empty($password)) {
        $errores[] = "La contraseña es requerida";
    } elseif (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    if ($password !== $confirm_password) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    if (empty($errores)) {
        $conexion->begin_transaction();
        try {
            // Verificar si el usuario existe
            $stmt = $conexion->prepare("SELECT Id_pasajero FROM usuarios WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows === 0) {
                $errores[] = "No existe una cuenta con este email";
            } else {
                // Actualizar la contraseña
                $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt->bind_param("ss", $hashed_password, $email);
                $stmt->execute();
                
                $conexion->commit();
                $_SESSION['cambio_exitoso'] = true;
                header("Location: ".$_SERVER['PHP_SELF']);
                exit();
            }
        } catch (Exception $e) {
            $conexion->rollback();
            $errores[] = "Error al actualizar la contraseña: " . $e->getMessage();
        }
    }
}

$mostrar_exito = isset($_SESSION['cambio_exitoso']) && $_SESSION['cambio_exitoso'];
if ($mostrar_exito) unset($_SESSION['cambio_exitoso']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DreamTrip - Recuperar Contraseña</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --color-primario: rgb(255, 255, 255);
            --color-secundario: #9DB496;
            --color-texto: #333;
            --color-blanco: #fff;
            --color-gris: #f5f5f5;
            --color-negro: #000;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            color: var(--color-negro);
            line-height: 1.6;
        }
        
        /* Header */
        .main-header {
            background-color: var(--color-primario);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .logo-container {
            display: flex;
            align-items: center;
        }
        
        .logo {
            height: 50px;
            margin-right: 10px;
        }
        
        .logo-text {
            color: var(--color-negro);
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .search-box {
            flex-grow: 1;
            margin: 0 2rem;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: none;
            background-color: var(--color-gris);
        }
        
        .main-nav ul {
            display: flex;
            list-style: none;
        }
        
        .main-nav ul li {
            margin-left: 1.5rem;
        }
        
        .main-nav ul li a {
            color: var(--color-negro);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            padding: 0.5rem 0;
        }
        
        .main-nav ul li a:hover {
            color: var(--color-secundario);
            border-bottom: 2px solid var(--color-secundario);
        }
        
       /* Carrusel */
        .carousel-container {
            position: relative;
            width: 100%;
            height: 400px;
            margin-bottom: 2rem;
        }
        
        .carousel {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .carousel-inner {
            display: flex;
            transition: transform 0.5s ease;
            height: 100%;
        }
        
        .carousel-item {
            min-width: 100%;
            height: 100%;
        }
        
        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Texto fijo sobre el carrusel */
        .carousel-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 100%;
            z-index: 10;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .carousel-text h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: fadeIn 1s ease-in-out;
        }
        
        .carousel-links {
            margin-top: 1rem;
        }
        
        .carousel-links a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
            font-size: 1.1rem;
            transition: color 0.3s;
        }
        
        .carousel-links a:hover {
            color: var(--color-secundario);
            text-decoration: underline;
        }
        
        /* Controles del carrusel */
        .carousel-control {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 15px;
            cursor: pointer;
            z-index: 10;
            font-size: 1.5rem;
            transition: background-color 0.3s;
        }
        
        .carousel-control:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }
        
        .carousel-control.prev {
            left: 20px;
            border-radius: 0 5px 5px 0;
        }
        
        .carousel-control.next {
            right: 20px;
            border-radius: 5px 0 0 5px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Formulario */
        .form-container {
            display: flex;
            max-width: 1000px;
            margin: 0 auto 3rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .form-image {
            flex: 1;
            background: url('viajera.jpg') center/cover no-repeat;
        }
        
        .form-content {
            flex: 1;
            padding: 2rem;
            background: var(--color-blanco);
        }
        
        .form-title {
            text-align: left;
            margin-bottom: 1.5rem;
            color: var(--color-negro);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .btn-submit {
            background-color: var(--color-secundario);
            color: var(--color-blanco);
            border: none;
            padding: 1rem;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .btn-submit:hover {
            background-color: #8aa383;
        }
        
        /* Mensajes */
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            text-align: center;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        
        /* Footer */
        .main-footer {
            width: 100%;
            background-color: #9DB496;
            color: var(--color-texto);
        }
        
        .footer-top {
            display: flex;
            justify-content: space-around;
            padding: 3rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
            margin: 0 1rem 2rem;
        }
        
        .footer-section h3 {
            color: var(--color-negro);
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }
        
        .footer-section ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-section ul li {
            margin-bottom: 0.8rem;
        }
        
        .footer-section ul li a {
            color: var(--color-texto);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section ul li a:hover {
            color: var(--color-blanco);
        }
        
        .footer-section p {
            line-height: 1.6;
        }
        
        .social-icons {
            display: flex;
            gap: 1rem;
        }
        
        .social-icons a {
            color: var(--color-texto);
            font-size: 1.5rem;
            transition: color 0.3s;
        }
        
        .social-icons a:hover {
            color: var(--color-blanco);
        }
        
        .footer-bottom {
            background-color: #000;
            color: #fff;
            text-align: center;
            padding: 1.5rem 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="logo-container">
            <img src="" alt="" class="logo">
            <span class="logo-text">DT</span>
        </div>
        
        <div class="search-box">
            <input type="text" placeholder="Buscar destinos o actividades">
        </div>
        
        <nav class="main-nav">
            <ul>
                <li><a href="lista.php">Destinos</a></li>
                <li><a href="galería.html">Galeria</a></li>
                <li><a href="contacto.html">Contacto</a></li>
                <li><a href="registro.php" style="color:rgb(0, 0, 0); font-weight: bold;">Inscribirse</a></li>
                <li><a href="#">Acceso</a></li>
            </ul>
        </nav>
    </header>

  <div class="carousel-container">
        <!-- Texto fijo sobre el carrusel -->
        <div class="carousel-text">
            <h1>OLVIDÉ MI CONTRASEÑA</h1>
            <div class="carousel-links">
                <a href="index.html">Inicio</a> | 
                <a href="#">Recuperar contraseña</a>
            </div>
        </div>
        
        <!-- Carrusel de imágenes -->
        <div class="carousel">
            <div class="carousel-inner" id="carouselInner">
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1506929562872-bb421503ef21?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Destino 1">
                </div>
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1467269204594-9661b134dd2b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Destino 2">
                </div>
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" alt="Destino 3">
                </div>
            </div>
            
            <!-- Controles del carrusel -->
            <button class="carousel-control prev" onclick="moveCarousel(-1)">&#10094;</button>
            <button class="carousel-control next" onclick="moveCarousel(1)">&#10095;</button>
        </div>
    </div>

    <div class="form-container">
        <div class="form-image"></div>
        <div class="form-content">
            <h2 class="form-title">Restablecer contraseña</h2>
            
            <?php if ($mostrar_exito): ?>
                <div class="alert alert-success">¡Contraseña actualizada correctamente!</div>
                <script>
                    setTimeout(() => {
                        document.querySelector('form').reset();
                    }, 3000);
                </script>
            <?php endif; ?>
            
            <?php foreach ($errores as $error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
            
            <form method="post">
                <div class="form-group">
                    <input type="email" id="email" name="email" placeholder="Correo Electronico" value="<?= htmlspecialchars($email) ?>" required>
                </div>
                
                <div class="form-group">
                    <input type="password" id="password" name="password" placeholder="Nueva contraseña" required>
                </div>
                
                <div class="form-group">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar nueva contraseña" required>
                </div>
                
                <button type="submit" class="btn-submit">Actualizar contraseña</button>
            </form>
        </div>
    </div>

    <footer class="main-footer">
        <div class="footer-top">
            <div class="footer-section">
                <h3>Contactanos</h3>
                <ul>
                    <li><a href="#">Unido</a></li>
                    <li><a href="#">Sobre nosotros</a></li>
                    <li><a href="#">Tours</a></li>
                    <li><a href="#">Paquetes</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Información de contacto</h3>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.<br>
                Quisque pharetra condimentum.</p>
            </div>
            
            <div class="footer-section">
                <h3>Enlaces rápidos</h3>
                <ul>
                    <li><a href="#">Inicio</a></li>
                    <li><a href="#">Destinos</a></li>
                    <li><a href="#">Promociones</a></li>
                    <li><a href="#">Blog</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Síguenos</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© Copyright 2025 DreamTrip. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // Carrusel automático
        document.addEventListener('DOMContentLoaded', function() {
            const carouselInner = document.getElementById('carouselInner');
            const items = document.querySelectorAll('.carousel-item');
            let currentIndex = 0;
            const totalItems = items.length;
            let rotateInterval;
            
            function updateCarousel() {
                carouselInner.style.transform = `translateX(-${currentIndex * 100}%)`;
            }
            
            function moveCarousel(direction) {
                currentIndex = (currentIndex + direction + totalItems) % totalItems;
                updateCarousel();
                
                // Reiniciar el intervalo cuando se cambia manualmente
                clearInterval(rotateInterval);
                rotateInterval = setInterval(() => moveCarousel(1), 5000);
            }
            
            // Iniciar el carrusel
            rotateInterval = setInterval(() => moveCarousel(1), 5000);
            
            // Pausar el carrusel cuando el mouse está sobre él
            carouselInner.addEventListener('mouseenter', function() {
                clearInterval(rotateInterval);
            });
            
            carouselInner.addEventListener('mouseleave', function() {
                rotateInterval = setInterval(() => moveCarousel(1), 5000);
            });
            
            // Hacer la función accesible globalmente para los botones
            window.moveCarousel = moveCarousel;
        });
    </script>
</body>
</html> 