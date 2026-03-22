<?php
/**
 * db_connect.php
 * ─────────────────────────────────────────────────────────────
 * Establishes a secure PDO connection to the MySQL recipe_db.
 *
 * WHY PDO?
 *  - Database-agnostic: swap MySQL → PostgreSQL with one line.
 *  - Supports named :placeholder prepared statements (prevents SQL injection).
 *  - Throws exceptions on error (easier than checking return codes).
 *
 * USAGE (in any other PHP file):
 *   require_once __DIR__ . '/db_connect.php';
 *   $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = :id");
 *   $stmt->execute([':id' => $id]);
 * ─────────────────────────────────────────────────────────────
 */

// ── 1. Database credentials ───────────────────────────────────
// In production, load these from environment variables or a
// secrets manager — never hard-code in version-controlled files.
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'recipie');
define('DB_USER', 'root');          // ← change to your DB user
define('DB_PASS', '');              // ← change to your DB password
define('DB_CHAR', 'utf8mb4');       // Full Unicode (emoji-safe)

// ── 2. DSN (Data Source Name) ─────────────────────────────────
// The DSN string tells PDO which driver and database to use.
// charset=utf8mb4 ensures strings are read/written correctly.
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    DB_HOST, DB_PORT, DB_NAME, DB_CHAR
);

// ── 3. PDO Options ────────────────────────────────────────────
$options = [
    // Throw a PDOException on every error instead of silently failing
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    // Return rows as associative arrays (column_name => value)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    // Disable emulated prepares — use real DB-side prepared statements
    // This is the key security setting that prevents SQL injection
    PDO::ATTR_EMULATE_PREPARES   => false,

    // Keep the connection alive across requests (persistent connection pool)
    PDO::ATTR_PERSISTENT         => true,
];

// ── 4. Create the PDO instance ────────────────────────────────
// Wrapped in try/catch so connection failures are handled gracefully.
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log the real error server-side (never expose credentials to the browser)
    error_log('Database connection failed: ' . $e->getMessage());

    // Show a user-friendly error and halt execution
    http_response_code(503);
    die(json_encode([
        'error' => 'Service temporarily unavailable. Please try again later.'
    ]));
}
// $pdo is now available to any file that require_once's this script
