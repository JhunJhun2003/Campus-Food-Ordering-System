<?php
require __DIR__ . '/../../vendor/autoload.php';

$db = Inc\Database::getConnection();

try {
    $columns = $db->query('DESCRIBE notifications')->fetchAll(PDO::FETCH_ASSOC);
    echo "Current columns:\n";
    foreach ($columns as $column) {
        echo '- ' . $column['Field'] . "\n";
    }

    $existing = array_column($columns, 'Field');
    $alterStatements = [];

    if (!in_array('type', $existing, true)) {
        $alterStatements[] = "ADD COLUMN type VARCHAR(50) DEFAULT 'system' AFTER message";
    }
    if (!in_array('reference_type', $existing, true)) {
        $alterStatements[] = "ADD COLUMN reference_type VARCHAR(50) DEFAULT NULL AFTER type";
    }
    if (!in_array('reference_id', $existing, true)) {
        $alterStatements[] = "ADD COLUMN reference_id INT DEFAULT NULL AFTER reference_type";
    }
    if (!in_array('updated_at', $existing, true)) {
        $alterStatements[] = "ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
    }

    if ($alterStatements === []) {
        echo "\nSchema already up to date.\n";
        exit(0);
    }

    $sql = 'ALTER TABLE notifications ' . implode(', ', $alterStatements);
    $db->exec($sql);
    echo "\nMigration applied successfully.\n";
} catch (Throwable $e) {
    echo 'Migration failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
