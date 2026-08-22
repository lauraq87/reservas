<?php

/**
 * Clase para manejar el cache en memoria de disponibilidad de mesas por ubicación
 */
class Disponibilidad
{
    private static $cache = [];
    private static $cacheExpiry = 300; // 5 minutos en segundos
    private static $lastUpdate = [];

    /**
     * Obtiene las mesas disponibles por ubicación desde el cache
     * 
     * @param PDO $pdo Conexión a la base de datos
     * @param DateTime $inicioReserva Inicio del periodo a consultar
     * @param DateTime $finReserva Fin del periodo a consultar
     * @param string $ubicación Ubicación a consultar (A, B, C, D)
     * @return array Mesas disponibles en la ubicación
     */
    public static function obtenerMesasDisponiblesPorUbicacion(
        PDO $pdo,
        DateTime $inicioReserva,
        DateTime $finReserva,
        string $ubicacion
    ): array {
        $cacheKey = self::generarCacheKey($inicioReserva, $finReserva, $ubicacion);
        
        // Verificar si existe en cache y no ha expirado
        if (self::estaEnCache($cacheKey)) {
            return self::$cache[$cacheKey];
        }
        
        // Si no está en cache, consultar a la base de datos
        $mesas = self::consultarMesasDisponibles($pdo, $inicioReserva, $finReserva, $ubicacion);
        
        // Guardar en cache
        self::$cache[$cacheKey] = $mesas;
        self::$lastUpdate[$cacheKey] = time();
        
        return $mesas;
    }

    /**
     * Obtiene todas las mesas disponibles agrupadas por ubicación
     * 
     * @param PDO $pdo Conexión a la base de datos
     * @param DateTime $inicioReserva Inicio del periodo a consultar
     * @param DateTime $finReserva Fin del periodo a consultar
     * @return array Array con mesas agrupadas por ubicación
     */
    public static function obtenerTodasLasMesasDisponibles(
        PDO $pdo,
        DateTime $inicioReserva,
        DateTime $finReserva
    ): array {
        $ubicaciones = ['A', 'B', 'C', 'D'];
        $mesasPorUbicacion = [];
        
        foreach ($ubicaciones as $ubicacion) {
            $mesasPorUbicacion[$ubicacion] = self::obtenerMesasDisponiblesPorUbicacion(
                $pdo,
                $inicioReserva,
                $finReserva,
                $ubicacion
            );
        }
        
        return $mesasPorUbicacion;
    }

    /**
     * Invalida el cache para una ubicación específica
     * 
     * @param string $ubicacion Ubicación a invalidar
     */
    public static function invalidarCache(string $ubicacion): void
    {
        foreach (array_keys(self::$cache) as $key) {
            if (strpos($key, $ubicacion) !== false) {
                unset(self::$cache[$key]);
                unset(self::$lastUpdate[$key]);
            }
        }
    }

    /**
     * Invalida todo el cache
     */
    public static function invalidarTodoCache(): void
    {
        self::$cache = [];
        self::$lastUpdate = [];
    }

    /**
     * Genera una clave única para el cache
     */
    private static function generarCacheKey(
        DateTime $inicioReserva,
        DateTime $finReserva,
        string $ubicacion
    ): string {
        return md5(
            $inicioReserva->format('Y-m-d H:i:s') .
            $finReserva->format('Y-m-d H:i:s') .
            $ubicacion
        );
    }

    /**
     * Verifica si un dato está en cache y no ha expirado
     */
    private static function estaEnCache(string $key): bool
    {
        if (!isset(self::$cache[$key]) || !isset(self::$lastUpdate[$key])) {
            return false;
        }
        
        $edad = time() - self::$lastUpdate[$key];
        return $edad < self::$cacheExpiry;
    }

    /**
     * Consulta las mesas disponibles en la base de datos
     */
    private static function consultarMesasDisponibles(
        PDO $pdo,
        DateTime $inicioReserva,
        DateTime $finReserva,
        string $ubicacion
    ): array {
        $sql = "
            SELECT
                m.id,
                m.ubicacion,
                m.numero,
                m.capacidad
            FROM mesas AS m
            WHERE m.ubicacion = :ubicacion
            AND NOT EXISTS (
                SELECT 1
                FROM reserva_mesa AS rm
                INNER JOIN reservas AS r ON r.id = rm.reserva_id
                WHERE rm.mesa_id = m.id
                AND TIMESTAMP(r.fecha, r.hora) < :fin_reserva
                AND TIMESTAMP(r.fecha, r.hora) + INTERVAL r.duracion_minutos MINUTE > :inicio_reserva
            )
            ORDER BY m.numero ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ubicacion' => $ubicacion,
            ':inicio_reserva' => $inicioReserva->format('Y-m-d H:i:s'),
            ':fin_reserva' => $finReserva->format('Y-m-d H:i:s')
        ]);

        return $stmt->fetchAll();
    }
}