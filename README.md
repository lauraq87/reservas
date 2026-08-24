# Sistema de reservas para restaurante

Aplicación web en PHP para gestionar reservas de mesas de un restaurante. Permite a los clientes elegir una fecha, un horario y la cantidad de personas; el sistema busca mesas disponibles y guarda la reserva en MySQL/MariaDB.

## Funcionalidades

* Formulario de reserva para clientes.
* Selección de turnos según el día de la semana.
* Validación de datos, fecha, horario y anticipación mínima de 15 minutos.
* Asignación automática de hasta tres mesas dentro de una misma ubicación.
* Consulta de disponibilidad considerando reservas de 120 minutos.
* Área informativa para el personal del restaurante.
* Listado de reservas filtrable por fecha.
* Distribución de mesas por ubicaciones `A`, `B`, `C` y `D`.

## Tecnologías

* PHP 8.2 o superior.
* MySQL.
* HTML, CSS y JavaScript.
* PDO para la conexión con la base de datos.
* Lucide y Font Awesome desde CDN para algunos iconos.

## Requisitos

En Windows, la forma más sencilla de ejecutar el proyecto es instalar [XAMPP](https://www.apachefriends.org/), que incluye Apache, PHP y MariaDB.

También se necesita:

* Un navegador web actualizado.
* PHP con la extensión `pdo\_mysql` habilitada.
* MySQL o MariaDB en ejecución.

## Instalación con XAMPP

1. Instala XAMPP.
2. Copia o clona este proyecto dentro de la carpeta `htdocs`: C:\\xampp\\htdocs\\reservas
3. Abre el panel de control de XAMPP.
4. Inicia los servicios **Apache** y **MySQL**.
5. Abre phpMyAdmin en [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
6. Crea una base de datos llamada `restaurante`.
7. Importa el archivo que está dentro de la carpeta: [`base-de-datos/restaurante.sql`](base-de-datos/restaurante.sql).
8. Comprueba los datos de conexión en [`config/database.php`](config/database.php):

```php
   $host = 'localhost';
   $dbname = 'restaurante';
   $username = 'root';
   $password = '';
  

9. Abre la aplicación en [http://localhost/reservas/](http://localhost/reservas/).

## Ejecución con el servidor integrado de PHP

Si PHP ya está instalado y disponible en el `PATH`, ejecuta desde la raíz del proyecto:

```powershell
php -S localhost:8000
```

Después, abre [http://localhost:8000/](http://localhost:8000/).

La base de datos debe estar iniciada igualmente y la configuración de [`config/database.php`](config/database.php) debe coincidir con el usuario, contraseña, host y puerto de tu instalación.

## 

## Uso Cliente

1. Accede a la página principal.
2. Selecciona **Iniciar su reserva**.
3. Completa nombre, apellido, celular, fecha, horario y cantidad de personas.
4. Envía el formulario.
5. El sistema confirma la reserva o informa si no hay mesas disponibles.

Los turnos disponibles son:

* Lunes a viernes: de 10:00 a 24:00.
* Sábados: de 22:00 a 02:00.
* Domingos: de 12:00 a 16:00.

### Personal del restaurante

Desde la página principal, selecciona **Ingreso personal restaurante**. Una vez dentro del área informativa, se puede acceder al listado de reservas por fecha y consultar las mesas ocupadas y disponibles.

> Nota: el formulario de ingreso actual no implementa todavía una validación real de usuario y contraseña.

## Estructura del proyecto

```text
reservas/
├── base-de-datos/
│   └── restaurante.sql       # Estructura y datos iniciales de la base
├── config/
│   └── database.php          # Conexión PDO con MySQL/MariaDB
├── css/
│   └── style.css             # Estilos de la aplicación
├── img/                      # Imágenes del proyecto
├── js/
│   └── horarios.js           # Generación de turnos en el formulario
├── src/
│   └── Disponibilidad.php    # Consulta y cache de mesas disponibles
├── index.php                 # Página principal
├── reservarMesa.php          # Formulario de reserva
├── reservar.php              # Validación y guardado de reservas
├── restaurante.php           # Área informativa del restaurante
└── listaReservas.php         # Listado de reservas por fecha
```

## Base de datos

El esquema contiene estas tablas principales:

* `mesas`: mesas, ubicación, número y capacidad.
* `reservas`: datos del cliente, fecha, hora y duración.
* `reserva\_mesa`: relación entre reservas y mesas asignadas.

La duración predeterminada de cada reserva es de 120 minutos.

## Solución de problemas

* **Error de conexión:** verifica que MySQL esté iniciado y que los valores de [`config/database.php`](config/database.php) sean correctos.
* **Página no encontrada:** confirma que la carpeta esté dentro de `C:\\xampp\\htdocs` y que Apache esté iniciado.
* **No se muestran los horarios:** selecciona una fecha en el formulario y comprueba que JavaScript esté habilitado en el navegador.
* **No funciona `pnpm install`:** este proyecto no utiliza Node.js ni tiene `package.json`; no es necesario ejecutar `pnpm install`.

