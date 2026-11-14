<?php

declare(strict_types=1);

/**
 * Copie este archivo como config.php y complete los datos de su MySQL.
 * config.php no se sube al repositorio.
 * APP_URL es la dirección que se codifica en el QR. Al publicar el sistema,
 * reemplácela por el dominio real, sin barra final.
 */
const DB_HOST = '127.0.0.1';
const DB_PUERTO = 3306;
const DB_NOMBRE = 'gestion_certificados';
const DB_USUARIO = 'usuario_mysql';
const DB_CLAVE = 'clave_mysql';

const APP_NOMBRE = 'Gestión de certificados';
const APP_TIMEZONE = 'America/Caracas';
const APP_URL = 'http://localhost/gestion_certificados';
const APP_DEBUG = false;
const POR_PAGINA = 10;
