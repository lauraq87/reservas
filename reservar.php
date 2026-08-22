<?php

date_default_timezone_set('America/Argentina/Buenos_Aires');

ob_start();

require_once 'config/database.php';
require_once 'src/Disponibilidad.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: index.php');
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$celular = trim($_POST['celular'] ?? '');
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';
$personas = (int) ($_POST['personas'] ?? 0);

$errores = [];


// =====================================================
// VALIDAR DATOS PERSONALES
// =====================================================

if ($nombre === '') {

    $errores[] = 'El nombre es obligatorio.';
}

if ($apellido === '') {

    $errores[] = 'El apellido es obligatorio.';
}

if ($celular === '') {

    $errores[] = 'El celular es obligatorio.';
}


// =====================================================
// VALIDAR CANTIDAD DE PERSONAS
// =====================================================

if ($personas < 1) {

    $errores[] =
        'La cantidad de personas debe ser mayor a 0.';
}


// =====================================================
//  VALIDAR FECHA
// =====================================================

$fechaObj = DateTime::createFromFormat(
    'Y-m-d',
    $fecha
);

if (
    !$fechaObj ||
    $fechaObj->format('Y-m-d') !== $fecha
) {

    $errores[] =
        'La fecha seleccionada no es válida.';
}


// =====================================================
// VALIDAR HORA
// =====================================================

$horaObj = DateTime::createFromFormat(
    'H:i',
    $hora
);

if (
    !$horaObj ||
    $horaObj->format('H:i') !== $hora
) {

    $errores[] =
        'La hora seleccionada no es válida.';
}


// =====================================================
// VALIDAR HORARIO SEGÚN EL DÍA
// =====================================================

function horarioPermitido(
    DateTime $fecha,
    string $hora
): bool {

    $diaSemana = (int) $fecha->format('N');

    $minutos =
        ((int) substr($hora, 0, 2) * 60)
        +
        (int) substr($hora, 3, 2);


    if ($diaSemana >= 1 && $diaSemana <= 5) {

        return $minutos >= 600 &&
               $minutos <= 1440;
    }

    if ($diaSemana === 6) {

        return $minutos >= 1320 ||
               $minutos <= 120;
    }


    // Domingo: 12:00 a 16:00
    return $minutos >= 720 &&
           $minutos <= 960;
}


if (
    $fechaObj &&
    $fechaObj->format('Y-m-d') === $fecha &&
    $horaObj &&
    $horaObj->format('H:i') === $hora
) {

    if (!horarioPermitido($fechaObj, $hora)) {

        $errores[] =
            'El horario seleccionado no está disponible para ese día.';
    }
}

// =====================================================
// VALIDAR ANTICIPACIÓN DE 15 MINUTOS
// =====================================================

if (
    $fechaObj &&
    $fechaObj->format('Y-m-d') === $fecha &&
    $horaObj &&
    $horaObj->format('H:i') === $hora
) {

    $fechaHoraReserva = new DateTime(
        $fecha . ' ' . $hora
    );

    $ahora = new DateTime();

    $limite = clone $ahora;

    $limite->modify('+15 minutes');


    if ($fechaHoraReserva < $limite) {

        $errores[] =
            'La reserva debe realizarse con al menos 15 minutos de anticipación.';
    }
}


// =====================================================
// MOSTRAR ERRORES
// =====================================================

if (!empty($errores)) {

    $contenido = '<div class="navegacion superior-derecha">';
    $contenido .= '<a href="reservarMesa.php" class="boton boton-volver">Realizar otra reserva</a>';
    $contenido .= '<a href="index.php" class="boton boton-volver">← Volver al inicio</a>';
    $contenido .= '</div>';

    $contenido = '<div class="error-mensaje error-validacion">';
    $contenido .= '<div class="icono-error">⚠️</div>';
    $contenido .= '<h1>No pudo realizarse la reserva</h1>';

       $contenido .= '<ul>';

    foreach ($errores as $error) {

        $contenido .= '<li>' .
             htmlspecialchars($error) .
             '</li>';
    }

    $contenido .= '</ul>';

    $contenido .= '<div class="acciones">';
    $contenido .= '<a href="index.php" class="boton boton-grande">Volver al inicio</a>';
    $contenido .= '<a href="reservarMesa.php" class="boton boton-secundario">Intentar nuevamente</a>';
    $contenido .= '</div>';
    $contenido .= '</div>';

    // Limpiar el buffer y mostrar el contenido con HTML completo
    ob_clean();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error en la Reserva - Sistema de Reservas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="contenedor">
        <?php echo $contenido; ?>
    </main>
</body>
</html>
<?php
    ob_end_flush();
    exit;
}


// =====================================================
// CALCULAR INICIO Y FIN DE LA RESERVA
// =====================================================

$inicioReserva = new DateTime(
    $fecha . ' ' . $hora
);

$finReserva = clone $inicioReserva;

$finReserva->modify('+120 minutes');


// =====================================================
// OBTENER MESAS DISPONIBLES CON CACHE
// =====================================================

$mesasPorUbicacion = Disponibilidad::obtenerTodasLasMesasDisponibles(
    $pdo,
    $inicioReserva,
    $finReserva
);


// =====================================================
// VALIDACION MÁXIMO 3 MESAS
// =====================================================

function buscarCombinacion(
    array $mesas,
    int $personas
): ?array {

    $cantidadMesas = count($mesas);

    // -------------------------------------------------
    // MESA
    // -------------------------------------------------

    for ($i = 0; $i < $cantidadMesas; $i++) {

        if ((int) $mesas[$i]['capacidad'] >= $personas) {

            return [
                $mesas[$i]
            ];
        }
    }

    for ($i = 0; $i < $cantidadMesas; $i++) {

        for ($j = $i + 1; $j < $cantidadMesas; $j++) {

            $capacidad =
                (int) $mesas[$i]['capacidad']
                +
                (int) $mesas[$j]['capacidad'];


            if ($capacidad >= $personas) {

                return [
                    $mesas[$i],
                    $mesas[$j]
                ];
            }
        }
    }


 
    for ($i = 0; $i < $cantidadMesas; $i++) {

        for ($j = $i + 1; $j < $cantidadMesas; $j++) {

            for (
                $k = $j + 1;
                $k < $cantidadMesas;
                $k++
            ) {

                $capacidad =
                    (int) $mesas[$i]['capacidad']
                    +
                    (int) $mesas[$j]['capacidad']
                    +
                    (int) $mesas[$k]['capacidad'];


                if ($capacidad >= $personas) {

                    return [
                        $mesas[$i],
                        $mesas[$j],
                        $mesas[$k]
                    ];
                }
            }
        }
    }


    return null;
}


// =====================================================
// 16. BUSCAR UBICACIÓN EN ORDEN A → B → C → D
// =====================================================

$ubicaciones = [
    'A',
    'B',
    'C',
    'D'
];

$mesasAsignadas = null;
$ubicacionAsignada = null;


foreach ($ubicaciones as $ubicacion) {

    $combinacion = buscarCombinacion(
        $mesasPorUbicacion[$ubicacion],
        $personas
    );


    if ($combinacion !== null) {

        $mesasAsignadas = $combinacion;

        $ubicacionAsignada = $ubicacion;

        break;
    }
}


// =====================================================
// SI NO HAY DISPONIBILIDAD
// =====================================================

if ($mesasAsignadas === null) {

    $contenido = '<div class="error-mensaje">';
    $contenido .= '<div class="icono-error">⚠️</div>';
    $contenido .= '<h1>No hay disponibilidad</h1>';

    $contenido .= '<p>';
    $contenido .= 'No hay mesas disponibles para ' .
         htmlspecialchars($personas) .
         ' personas en ese horario.';
    $contenido .= '</p>';

    $contenido .= '<div class="acciones">';
    $contenido .= '<a href="reservarMesa.php" class="boton boton-secundario">Intentar con otros datos</a>';
    $contenido .= '</div>';
    $contenido .= '</div>';

    // Limpiar el buffer y mostrar el contenido con HTML completo
    ob_clean();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin Disponibilidad - Sistema de Reservas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="contenedor">
        <?php echo $contenido; ?>
    </main>
</body>
</html>
<?php
    ob_end_flush();
    exit;
}


// =====================================================
// GUARDAR LA RESERVA
// =====================================================

try {

    $pdo->beginTransaction();

    $sqlReserva = "
        INSERT INTO reservas (
            nombre,
            apellido,
            celular,
            fecha,
            hora,
            personas,
            duracion_minutos
        )
        VALUES (
            :nombre,
            :apellido,
            :celular,
            :fecha,
            :hora,
            :personas,
            :duracion
        )
    ";


    $stmtReserva = $pdo->prepare($sqlReserva);


    $stmtReserva->execute([
        ':nombre' =>
            $nombre,

        ':apellido' =>
            $apellido,

        ':celular' =>
            $celular,

        ':fecha' =>
            $fecha,

        ':hora' =>
            $hora,

        ':personas' =>
            $personas,

        ':duracion' =>
            120
    ]);


    $reservaId = $pdo->lastInsertId();


    $sqlReservaMesa = "
        INSERT INTO reserva_mesa (
            reserva_id,
            mesa_id
        )
        VALUES (
            :reserva_id,
            :mesa_id
        )
    ";


    $stmtReservaMesa = $pdo->prepare(
        $sqlReservaMesa
    );


    foreach ($mesasAsignadas as $mesa) {

        $stmtReservaMesa->execute([

            ':reserva_id' =>
                $reservaId,

            ':mesa_id' =>
                $mesa['id']
        ]);
    }


    $pdo->commit();
    
    // Invalidar el cache de disponibilidad para la ubicación asignada
    Disponibilidad::invalidarCache($ubicacionAsignada);


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }

    $contenido = '<div class="error-mensaje">';
    $contenido .= '<h1>Error del sistema</h1>';

    $contenido .= '<p>';
    $contenido .= 'Ocurrió un error al guardar la reserva. Por favor, intenta nuevamente.';
    $contenido .= '</p>';

    $contenido .= '<div class="acciones">';
    $contenido .= '<a href="index.php" class="boton boton-grande">Salir</a>';
    $contenido .= '<a href="reservarMesa.php" class="boton boton-secundario">Intentar nuevamente</a>';
    $contenido .= '</div>';
    $contenido .= '</div>';

    // Limpiar el buffer y mostrar el contenido con HTML completo
    ob_clean();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del Sistema - Sistema de Reservas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="contenedor">
        <?php echo $contenido; ?>
    </main>
</body>
</html>
<?php
    ob_end_flush();
    exit;
}


// =====================================================
// MOSTRAR CONFIRMACIÓN
// =====================================================

$contenido = '<div class="navegacion superior-derecha">';
$contenido .= '<a href="reservarMesa.php" class="boton boton-volver">Realizar otra reserva</a>';
$contenido .= '<a href="index.php" class="boton boton-volver">← Volver al inicio</a>';
$contenido .= '</div>';

$contenido .= '<div class="confirmacion-exito">';
$contenido .= '<div class="icono-exito">✓</div>';
$contenido .= '<h1>Reserva realizada correctamente</h1>';

$contenido .= '<div class="detalles-reserva">';

// Renglón 1: Nro de reserva + Nombre
$contenido .= '<div class="detalle-item dos-columnas">';
$contenido .= '<div class="detalle-group">';
$contenido .= '<span class="detalle-label">Reserva número:</span>';
$contenido .= '<span class="detalle-valor">' . htmlspecialchars($reservaId) . '</span>';
$contenido .= '</div>';
$contenido .= '<div class="detalle-group">';
$contenido .= '<span class="detalle-label">Nombre:</span>';
$contenido .= '<span class="detalle-valor">' . htmlspecialchars($nombre) . ' ' . htmlspecialchars($apellido) . '</span>';
$contenido .= '</div>';
$contenido .= '</div>';

// Renglón 2: Fecha + Personas + Ubicación
$contenido .= '<div class="detalle-item tres-columnas">';
$contenido .= '<div class="detalle-group-compact">';
$contenido .= '<span class="detalle-label">Fecha:</span>';
$contenido .= '<span class="detalle-valor">' . htmlspecialchars($fecha) . '</span>';
$contenido .= '<span class="detalle-label">Hora:</span>';
$contenido .= '<span class="detalle-valor">' . htmlspecialchars($hora) . '</span>';

$contenido .= '</div>';
$contenido .= '<div class="detalle-group-compact">';
$contenido .= '<span class="detalle-label">Personas:</span>';
$contenido .= '<span class="detalle-valor">' . htmlspecialchars($personas) . '</span>';
$contenido .= '</div>';
$contenido .= '<div class="detalle-group-compact">';
$contenido .= '<span class="detalle-label">Ubicación:</span>';
$contenido .= '<span class="detalle-valor">' . htmlspecialchars($ubicacionAsignada) . '</span>';
$contenido .= '</div>';
$contenido .= '</div>';

$contenido .= '</div>';

$contenido .= '<div class="mesas-container">';
$contenido .= '<h2>Mesas asignadas</h2>';

foreach ($mesasAsignadas as $mesa) {
    $contenido .= '<div class="mesa-card">';
    $contenido .= '<div class="mesa-info">';
    $contenido .= '<div class="mesa-numero">Mesa ' . htmlspecialchars($mesa['numero']) . '</div>';
    $contenido .= '</div>';
    $contenido .= '</div>';
}
$contenido .= '</div>';
$contenido .= '</div>';


$contenido .= '</div>';

// Limpiar el buffer y mostrar el contenido con HTML completo
ob_clean();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva Confirmada - Sistema de Reservas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="contenedor">
        <?php echo $contenido; ?>
    </main>
</body>
</html>
<?php
ob_end_flush();
exit; 
