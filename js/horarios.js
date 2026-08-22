document.addEventListener('DOMContentLoaded', function () {

    const fechaInput = document.getElementById('fecha');
    const horaSelect = document.getElementById('hora');


    fechaInput.addEventListener('change', function () {

        // Limpiar horarios anteriores
        horaSelect.innerHTML = '';


        // Opción inicial
        const opcionInicial = document.createElement('option');

        opcionInicial.value = '';
        opcionInicial.textContent = 'Seleccioná un turno';

        horaSelect.appendChild(opcionInicial);


        // Si no hay fecha, no hacemos nada
        if (!fechaInput.value) {
            return;
        }


        // Obtener día de la semana
        //
        // 0 = domingo
        // 1 = lunes
        // 2 = martes
        // 3 = miércoles
        // 4 = jueves
        // 5 = viernes
        // 6 = sábado

        const fecha = new Date(
            fechaInput.value + 'T00:00:00'
        );

        const dia = fecha.getDay();


        let turnos = [];


        // ==========================================
        // LUNES A VIERNES
        // 10:00 a 24:00
        // ==========================================

        if (dia >= 1 && dia <= 5) {

            turnos = [
                {
                    inicio: '10:00',
                    fin: '12:00'
                },
                {
                    inicio: '12:00',
                    fin: '14:00'
                },
                {
                    inicio: '14:00',
                    fin: '16:00'
                },
                {
                    inicio: '16:00',
                    fin: '18:00'
                },
                {
                    inicio: '18:00',
                    fin: '20:00'
                },
                {
                    inicio: '20:00',
                    fin: '22:00'
                },
                {
                    inicio: '22:00',
                    fin: '00:00'
                }
            ];
        }


        // ==========================================
        // SÁBADO
        // 22:00 a 02:00
        // ==========================================

        else if (dia === 6) {

            turnos = [
                {
                    inicio: '22:00',
                    fin: '00:00'
                },
                {
                    inicio: '00:00',
                    fin: '02:00'
                }
            ];
        }


        // ==========================================
        // DOMINGO
        // 12:00 a 16:00
        // ==========================================

        else {

            turnos = [
                {
                    inicio: '12:00',
                    fin: '14:00'
                },
                {
                    inicio: '14:00',
                    fin: '16:00'
                }
            ];
        }


        // ==========================================
        // CREAR OPCIONES DEL SELECT
        // ==========================================

        turnos.forEach(function (turno) {

            const opcion =
                document.createElement('option');


            // VALUE:
            // Esto es lo que se envía al PHP.
            //
            // Ejemplo:
            // 12:00

            opcion.value = turno.inicio;


            // TEXTO:
            // Esto es lo que ve el usuario.
            //
            // Ejemplo:
            // 12:00 - 14:00

            opcion.textContent =
                turno.inicio +
                ' - ' +
                turno.fin;


            horaSelect.appendChild(opcion);
        });

    });

});