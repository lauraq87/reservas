<?php

require_once 'config/database.php';
require_once 'src/Disponibilidad.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');

// ======================================================
// OBTENER TODAS LAS MESAS DEL RESTAURANTE
// ======================================================

$sqlTodasMesas = "
    SELECT 
        id,
        ubicacion,
        numero,
        capacidad
    FROM mesas
    ORDER BY ubicacion ASC, numero ASC
";

$stmtTodasMesas = $pdo->query($sqlTodasMesas);
$todasLasMesas = $stmtTodasMesas->fetchAll();

// Organizar mesas por ubicación
$mesasPorUbicacion = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
foreach ($todasLasMesas as $mesa) {
    $mesasPorUbicacion[$mesa['ubicacion']][] = $mesa;
}

// ======================================================
// GENERAR HORARIOS DISPONIBLES SEGÚN EL DÍA
// ======================================================

function generarHorariosDisponibles($fecha) {
    $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
    $diaSemana = (int) $fechaObj->format('N');
    $horarios = [];
    
    // Lunes a viernes: 10:00 a 24:00 (cada 15 minutos)
    if ($diaSemana >= 1 && $diaSemana <= 5) {
        for ($hora = 10; $hora < 24; $hora++) {
            for ($min = 0; $min < 60; $min += 15) {
                $horarios[] = sprintf('%02d:%02d', $hora, $min);
            }
        }
    }
    // Sábado: 22:00 a 02:00
    elseif ($diaSemana === 6) {
        for ($hora = 22; $hora < 24; $hora++) {
            for ($min = 0; $min < 60; $min += 15) {
                $horarios[] = sprintf('%02d:%02d', $hora, $min);
            }
        }
        for ($hora = 0; $hora < 2; $hora++) {
            for ($min = 0; $min < 60; $min += 15) {
                $horarios[] = sprintf('%02d:%02d', $hora, $min);
            }
        }
    }
    // Domingo: 12:00 a 16:00
    else {
        for ($hora = 12; $hora < 16; $hora++) {
            for ($min = 0; $min < 60; $min += 15) {
                $horarios[] = sprintf('%02d:%02d', $hora, $min);
            }
        }
    }
    
    return $horarios;
}

$horariosDisponibles = generarHorariosDisponibles($fecha);

// ======================================================
// CALCULAR DISPONIBILIDAD POR HORARIO
// ======================================================

function obtenerMesasOcupadasEnHorario($pdo, $fecha, $hora) {
    $sql = "
        SELECT 
            r.id as reserva_id,
            r.nombre,
            r.apellido,
            r.personas,
            m.id as mesa_id,
            m.ubicacion,
            m.numero as mesa_numero,
            m.capacidad as mesa_capacidad
        FROM reservas r
        INNER JOIN reserva_mesa rm ON r.id = rm.reserva_id
        INNER JOIN mesas m ON rm.mesa_id = m.id
        WHERE r.fecha = :fecha
        AND r.hora = :hora
        ORDER BY r.id ASC, m.ubicacion ASC, m.numero ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':fecha' => $fecha,
        ':hora' => $hora
    ]);
    
    return $stmt->fetchAll();
}

// ======================================================
// ORGANIZAR DATOS POR HORARIO
// ======================================================

$datosPorHorario = [];

foreach ($horariosDisponibles as $horario) {
    $mesasOcupadas = obtenerMesasOcupadasEnHorario($pdo, $fecha, $horario);
    
    // Si no hay reservas en este horario, no lo agregamos
    if (empty($mesasOcupadas)) {
        continue;
    }
    
    // Agrupar mesas por reserva
    $reservasPorId = [];
    foreach ($mesasOcupadas as $mesaOcupada) {
        $reservaId = $mesaOcupada['reserva_id'];
        if (!isset($reservasPorId[$reservaId])) {
            $reservasPorId[$reservaId] = [
                'nombre' => $mesaOcupada['nombre'],
                'apellido' => $mesaOcupada['apellido'],
                'personas' => $mesaOcupada['personas'],
                'mesas' => []
            ];
        }
        $reservasPorId[$reservaId]['mesas'][] = [
            'id' => $mesaOcupada['mesa_id'],
            'ubicacion' => $mesaOcupada['ubicacion'],
            'numero' => $mesaOcupada['mesa_numero'],
            'capacidad' => $mesaOcupada['mesa_capacidad']
        ];
    }
    
    // Calcular mesas disponibles por ubicación
    $mesasDisponiblesPorUbicacion = [
        'A' => [],
        'B' => [],
        'C' => [],
        'D' => []
    ];
    
    // Marcar mesas ocupadas
    $mesasOcupadasIds = [];
    foreach ($mesasOcupadas as $mesaOcupada) {
        $mesasOcupadasIds[] = $mesaOcupada['mesa_id'];
    }
    
    // Encontrar mesas disponibles por ubicación
    foreach ($mesasPorUbicacion as $ubicacion => $mesas) {
        foreach ($mesas as $mesa) {
            if (!in_array($mesa['id'], $mesasOcupadasIds)) {
                $mesasDisponiblesPorUbicacion[$ubicacion][] = $mesa;
            }
        }
    }
    
    // Organizar mesas ocupadas por ubicación (agrupadas por reserva)
    $mesasOcupadasPorUbicacion = [
        'A' => [],
        'B' => [],
        'C' => [],
        'D' => []
    ];
    
    foreach ($reservasPorId as $reservaId => $reserva) {
        foreach ($reserva['mesas'] as $mesa) {
            $ubicacion = $mesa['ubicacion'];
            $mesasOcupadasPorUbicacion[$ubicacion][] = [
                'reserva_id' => $reservaId,
                'nombre' => $reserva['nombre'],
                'apellido' => $reserva['apellido'],
                'personas' => $reserva['personas'],
                'mesa' => $mesa
            ];
        }
    }
    
    // Calcular total de mesas disponibles
    $totalDisponibles = 0;
    foreach ($mesasDisponiblesPorUbicacion as $ubicacion => $mesas) {
        $totalDisponibles += count($mesas);
    }
    
    $datosPorHorario[$horario] = [
        'reservas' => $reservasPorId,
        'mesas_ocupadas' => $mesasOcupadasPorUbicacion,
        'mesas_disponibles' => $mesasDisponiblesPorUbicacion,
        'total_disponibles' => $totalDisponibles,
        'tiene_reservas' => true
    ];
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Listado de reservas</title>

    <link rel="stylesheet" href="css/style.css">

</head>


<body>

    <main class="contenedor">


        <!-- ==========================================
             NAVEGACIÓN
        =========================================== -->

        <div class="navegacion superior-derecha">

            <a href="restaurante.php" class="boton boton-volver">
                ← Volver al inicio
            </a>

        </div>


        <!-- ==========================================
             TÍTULO
        =========================================== -->

        <h1> Listado de reservas por fecha</h1>


        <!-- ==========================================
             BUSCAR POR FECHA
        =========================================== -->

        <form method="GET" class="busqueda-form">

            <div class="campo-fecha">
                <input
                    type="date"
                    id="fecha"
                    name="fecha"
                    value="<?php echo htmlspecialchars($fecha); ?>"
                >

            </div>


            <button
                type="submit"
                class="boton boton-buscar"
            >
                Buscar reservas
            </button>

        </form>


        <hr>


        <!-- ==========================================
             LISTADO POR HORARIO
        =========================================== -->

        <?php if (empty($datosPorHorario)): ?>

            <p class="mensaje">
                No hay reservas para este día.
            </p>

        <?php else: ?>

            <div class="horarios-container">

                <?php foreach ($datosPorHorario as $horario => $datos): ?>

                <div class="horario-bloque">

                    <!-- CABECERA DEL HORARIO -->
                    <div class="horario-cabecera">
                        <h3>
                            🕐 <?php echo htmlspecialchars($horario); ?>
                            <span class="disponibilidad-badge">
                                (<?php echo $datos['total_disponibles']; ?> mesas disponibles)
                            </span>
                        </h3>
                    </div>


                    <!-- CONTENIDO DEL HORARIO -->
                    <div class="horario-contenido">

                        <?php if ($datos['tiene_reservas']): ?>

                            <!-- HAY RESERVAS: MOSTRAR POR UBICACIÓN -->
                            <?php foreach (['A', 'B', 'C', 'D'] as $ubicacion): ?>

                                <?php if (!empty($datos['mesas_ocupadas'][$ubicacion])): ?>

                                    <div class="ubicacion-horario">

                                        <h4>
                                            📍 Ubicación <?php echo htmlspecialchars($ubicacion); ?>
                                        </h4>


                                        <div class="mesas-ocupadas-lista">

                                            <?php 
                                            // Agrupar por reserva_id para mostrar mesas unidas
                                            $reservasEnUbicacion = [];
                                            foreach ($datos['mesas_ocupadas'][$ubicacion] as $item) {
                                                $reservaId = $item['reserva_id'];
                                                if (!isset($reservasEnUbicacion[$reservaId])) {
                                                    $reservasEnUbicacion[$reservaId] = [
                                                        'nombre' => $item['nombre'],
                                                        'apellido' => $item['apellido'],
                                                        'personas' => $item['personas'],
                                                        'mesas' => []
                                                    ];
                                                }
                                                $reservasEnUbicacion[$reservaId]['mesas'][] = $item['mesa'];
                                            }
                                            
                                            foreach ($reservasEnUbicacion as $reserva): 
                                            ?>

                                                <div class="mesa-ocupada-item">

                                                    <div class="mesa-info">
                                                        <?php 
                                                        $mesasInfo = [];
                                                        foreach ($reserva['mesas'] as $mesa) {
                                                            $mesasInfo[] = 'Mesa ' . htmlspecialchars($mesa['numero']);
                                                        }
                                                        echo implode(' + ', $mesasInfo);
                                                        ?>
                                                        <span class="mesa-capacidad">
                                                            (<?php echo htmlspecialchars($reserva['personas']); ?> pers.)
                                                        </span>
                                                    </div>

                                                    <div class="reserva-info">
                                                        <span class="cliente-nombre">
                                                            <?php echo htmlspecialchars($reserva['nombre'] . ' ' . $reserva['apellido']); ?>
                                                        </span>
                                                    </div>

                                                </div>

                                            <?php endforeach; ?>

                                        </div>


                                    </div>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <!-- NO HAY RESERVAS: MOSTRAR TODAS LAS MESAS DISPONIBLES -->
                            <div class="sin-reservas">

                                <p class="mensaje-sin-reservas">
                                    No hay reservas en este horario
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <?php endif; ?>

    </main>

</body>

</html>