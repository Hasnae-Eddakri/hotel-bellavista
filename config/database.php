<?php
// ============================================================
// config/database.php
// Conexión a la base de datos usando PDO
// PDO es más seguro que mysqli porque usa prepared statements
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'hotelbellavista');
define('DB_USER', 'root');       // Cambia esto en producción
define('DB_PASS', '');           // Cambia esto en producción
define('DB_CHARSET', 'utf8mb4');

/**
 * Crea y devuelve una conexión PDO a la base de datos.
 * Si falla, muestra un error y para la ejecución.
 *
 * @return PDO Objeto de conexión a la BD
 */
function getDB(): PDO {
    static $pdo = null; // static: solo se crea una vez aunque se llame varias veces

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lanza excepciones en errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Devuelve arrays asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Prepared statements reales
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En producción NO mostrar el mensaje de error real
            error_log("Error de BD: " . $e->getMessage());
            die("Error de conexión a la base de datos. Contacta al administrador.");
        }
    }

    return $pdo;
}
