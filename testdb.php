<?php
require_once __DIR__ . '/vendor/autoload.php';

use Inc\Database;

try {
    $conn = Database::getConnection();
    echo "✅ Connected to database successfully!";
} catch (PDOException $e) {
    echo "❌ Failed to connect: " . $e->getMessage();
}