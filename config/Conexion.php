<?php

declare(strict_types=1);

class Conexion
{
    private static ?mysqli $instancia = null;

    public static function obtener(): mysqli
    {
        if (self::$instancia instanceof mysqli) {
            return self::$instancia;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conexion = new mysqli(DB_HOST, DB_USUARIO, DB_CLAVE, DB_NOMBRE, DB_PUERTO);
        $conexion->set_charset('utf8mb4');
        $conexion->query("SET time_zone = '-04:00'");
        self::$instancia = $conexion;

        return self::$instancia;
    }
}
