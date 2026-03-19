<?php
// migrate_ratings.php
// A quick script to apply the database changes for ratings securely.
require_once __DIR__ . '/db_connect.php';

try {
    // 1. Add columns to recipes table (ignore if they already exist)
    try {
        $pdo->exec("ALTER TABLE recipes ADD COLUMN average_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00");
        $pdo->exec("ALTER TABLE recipes ADD COLUMN rating_count INT UNSIGNED NOT NULL DEFAULT 0");
        echo "Added rating columns to recipes table.\n";
    } catch (PDOException $e) {
        // SQLSTATE 42S21 means "Duplicate column name", which is fine
        if ($e->getCode() == '42S21') {
            echo "Columns already exist in recipes table.\n";
        } else {
            throw $e;
        }
    }

    // 2. Create user_ratings table
    $sql = "
    CREATE TABLE IF NOT EXISTS user_ratings (
        user_id INT UNSIGNED NOT NULL,
        recipe_id INT UNSIGNED NOT NULL,
        rating TINYINT UNSIGNED NOT NULL CHECK (rating >= 0 AND rating <= 5),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, recipe_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
    echo "Created user_ratings table.\n";

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
