<?php
require_once __DIR__ . '/../src/config/database.php';

try {
    $pdo->beginTransaction();

    // 1. Create user_addresses table
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_addresses (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT,
      label VARCHAR(50) DEFAULT 'Rumah',
      address TEXT NOT NULL,
      is_default TINYINT(1) DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // 2. Migrate data (only if column exists)
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'address'");
    if ($stmt->fetch()) {
        $pdo->exec("INSERT INTO user_addresses (user_id, address, is_default)
                    SELECT id, address, 1 FROM users WHERE address IS NOT NULL AND address != ''");
        
        // 3. Drop column
        $pdo->exec("ALTER TABLE users DROP COLUMN address");
    }

    // 4. Add address to orders (only if column doesn't exist)
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'address'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN address TEXT AFTER payment_address");
    }

    $pdo->commit();
    echo "Migration successful!\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
}
