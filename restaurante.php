<?php

session_start();
require_once 'config/database.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: restaurante.php');
    exit;
}


// Procesar login si se envió el formulario
if (isset($_POST['login_submit'])) {
    $_SESSION['usuario_logueado'] = true;
    $mostrarContenido = true;
} else {
    // Verificar si ya hay una sesión activa
    $mostrarContenido = isset($_SESSION['usuario_logueado']) && $_SESSION['usuario_logueado'] === true;
}

// ======================================================
// OBTENER INFORMACIÓN GENERAL DE LAS MESAS
// ======================================================

$sqlMesas = "
    SELECT 
        ubicacion,
        COUNT(*) AS total_mesas,
        SUM(capacidad) AS total_capacidad,
        GROUP_CONCAT(
            CONCAT(
                'Mesa ',
                numero,
                ' - ',
                capacidad,
                ' personas'
            )
            ORDER BY numero ASC
            SEPARATOR ', '
        ) AS mesas_info
    FROM mesas
    GROUP BY ubicacion
    ORDER BY ubicacion ASC
";

$stmtMesas = $pdo->query($sqlMesas);
$infoMesas = $stmtMesas->fetchAll();

// Obtener mesas disponibles por ubicación para hoy
function obtenerMesasDisponiblesPorUbicacionFecha($pdo, $fecha, $ubicacion)
{
    $sql = "
        SELECT 
            m.id,
            m.ubicacion,
            m.numero,
            m.capacidad
        FROM mesas m
        WHERE m.ubicacion = :ubicacion
        AND NOT EXISTS (
            SELECT 1
            FROM reserva_mesa rm
            INNER JOIN reservas r 
                ON r.id = rm.reserva_id
            WHERE rm.mesa_id = m.id
            AND r.fecha = :fecha
        )
        ORDER BY m.numero ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ubicacion' => $ubicacion,
        ':fecha' => $fecha
    ]);
    
    return $stmt->fetchAll();
}

// Calcular mesas disponibles por ubicación para hoy
$fechaHoy = date('Y-m-d');
$mesasDisponiblesPorUbicacion = [];
$ubicaciones = ['A', 'B', 'C', 'D'];

foreach ($ubicaciones as $ubicacion) {
    $mesasDisponiblesPorUbicacion[$ubicacion] =
        obtenerMesasDisponiblesPorUbicacionFecha(
            $pdo,
            $fechaHoy,
            $ubicacion
        );
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Información para el Restaurante</title>

    <link rel="stylesheet" href="css/style.css">

</head>


<body>

    <main class="contenedor inicio">

        <?php if (!$mostrarContenido): ?>
        
        <!-- ==========================================
             FORMULARIO DE LOGIN
        =========================================== -->

        <div class="navegacion superior-derecha">
            <a href="index.php" class="boton boton-volver">← Volver al inicio</a>
        </div>

        <div class="login-container">
            
            <form method="POST" class="login-form">
                <div class="campo">
                    <label for="usuario">Usuario</label>
                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        value="usuario"
                        disabled
                    >
                </div>
                
                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        value="123456"
                        disabled
                    >
                </div>
                
                <button type="submit" name="login_submit" class="boton-login">
                    Entrar
                </button>
            </form>
        </div>

        <?php else: ?>


        <div class="navegacion superior-derecha">
            <a href="restaurante.php?logout=1" class="boton boton-logout">
                Cerrar sesión
            </a>

        </div>




        <h1>
             Información del Restaurante
        </h1>


        
        <div class="botones-inicio">

            <a
                href="listaReservas.php"
                class="boton-inicio"
            >
                Listado de reservas por fecha
            </a>

        </div>


        <!-- ==========================================
             INFORMACIÓN GENERAL DEL RESTAURANTE
        =========================================== -->

        <div class="info-restaurante-container visible">
            <h3>Información general del restaurante</h3>
            <div class="ubicaciones-grid">
                <?php foreach ($infoMesas as $info): ?>
                    <div class="ubicacion-card">
                        <h4>Ubicación <?php echo htmlspecialchars($info['ubicacion']); ?></h4>
                                                
                        <!-- Totales al final -->
                        <div class="ubicacion-totales">
                            <div class="total-item">
                                <span class="total-label">Total mesas:</span>
                                <span class="total-value"><?php echo htmlspecialchars($info['total_mesas']); ?></span>
                            </div>
                            <div class="total-item">
                                <span class="total-label">Capacidad personas:</span>
                                <span class="total-value"><?php echo htmlspecialchars($info['total_capacidad']); ?></span>
                            </div>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php endif; ?>

    </main>

</body>

</html>