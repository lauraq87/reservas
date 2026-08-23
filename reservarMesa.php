<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Realizar una reserva</title>

    <link
        rel="stylesheet"
        href="css/style.css">

    <script
        src="js/horarios.js"
        defer></script>

</head>


<body>

    <main class="contenedor sin-scroll">


        <div class="navegacion superior-derecha">

            <a
                href="index.php"
                class="boton boton-volver">
                ← Volver al inicio
            </a>

        </div>

        <h1> Realice una reserva </h1>

        <form
            action="reservar.php"
            method="POST">

            <section>

                <h2>Datos personales</h2>

                <div class="campo-row">
                    <div class="campo half-width">

                        <label for="nombre">
                            Nombre
                        </label>

                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            placeholder="Tu nombre"
                            required>

                    </div>

                    <div class="campo half-width">

                        <label for="apellido">
                            Apellido
                        </label>

                        <input
                            type="text"
                            id="apellido"
                            name="apellido"
                            placeholder="Tu apellido"
                            required>

                    </div>
                </div>


                <div class="campo">

                    <label for="celular">
                        Celular
                    </label>

                    <input
                        type="tel"
                        id="celular"
                        name="celular"
                        placeholder="Tu número de celular"
                        pattern="[0-9+\- ]+"
                        title="Solo números, + y -"
                        required
                        oninput="this.value = this.value.replace(/[^0-9+\- ]/g, '')">

                </div>

            </section>


            <section>

                <h2>Datos de la reserva</h2>

                <div class="campo-row">
                    <div class="campo third-width">

                        <label for="fecha">
                            Fecha
                        </label>

                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            required>

                    </div>

                    <div class="campo third-width">

                        <label for="hora">
                            Horario
                        </label>

                        <select
                            id="hora"
                            name="hora"
                            required>

                            <option value="">
                                Seleccioná un turno
                            </option>

                        </select>

                    </div>

                    <div class="campo third-width">

                        <label for="personas">
                            Cantidad de personas
                        </label>

                        <input
                            type="number"
                            id="personas"
                            name="personas"
                            min="1"
                            placeholder="Cantidad"
                            required>

                    </div>
                </div>

            </section>


            <button type="submit">
                RESERVAR
            </button>


        </form>

    </main>

</body>

</html>