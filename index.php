<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sistema de Reservas</title>

    <link
        rel="stylesheet"
        href="/reservas/css/style.css"
    >
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>


<body>

    <!-- Header con botón de ingreso -->
    <header>

        <div class="header-derecha">
            <a href="restaurante.php" class="boton-ingreso">
                👤 Ingreso personal restaurante
            </a>
        </div>
    </header>

    <main class="contenedor inicio-dos-columnas">

        <!-- Columna Izquierda: Contenido principal -->
        <div class="columna-izquierda">
            <div class="contenido-principal">
                <h1>
                    Bienvenido a nuestro <span class="texto-destacado">Sistema de Reservas</span>
                </h1>
                                
                <a href="reservarMesa.php" class="boton-principal">
                    📅 INICIAR SU RESERVA
                </a>
                
            </div>
        </div>

        <!-- Columna Derecha: Tarjeta de características -->
        <div class="columna-derecha">
            <div class="tarjeta-caracteristicas">             

                <h2>Disfrute sin preocupaciones</h2>
                <h5>Sin llamadas, sin esperas, desde cualquier lugar</h5>
                
                <ul class="caracteristicas-lista">
                    <li>
                        <span class="caracteristica-icono"><i class="fa-solid fa-circle-check"></i></span>
                        <span class="caracteristica-texto">Proceso simple y rápido</span>
                    </li>
                    <li>
                        <span class="caracteristica-icono"><i class="fa-solid fa-calendar-days"></i></span>
                        <span class="caracteristica-texto">Elija fecha y horario ideal</span>
                    </li>
                    <li>
                        <span class="caracteristica-icono"><i class="fa-solid fa-lock"></i></span>
                        <span class="caracteristica-texto">Datos protegidos</span>
                    </li>
                </ul>
            </div>
        </div>

    </main>

</body>

</html>