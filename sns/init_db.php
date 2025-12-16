<?php
require_once __DIR__ . '/backend/src/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $sql = file_get_contents(__DIR__ . '/backend/schema.sql');

    // SQLite can execute multiple statements? PDO::exec might support it.
    // If not, split by ';'.
    $conn->exec($sql);
    echo "Schema imported successfully.\n";

    $res = $conn->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $res->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
