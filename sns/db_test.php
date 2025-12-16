<?php
try {
    $db_file = __DIR__ . '/sns_debug.db';
    echo "Testing DB path: $db_file\n";
    if (!file_exists($db_file)) {
        echo "DB file not found!\n";
    } else {
        echo "DB file exists.\n";
    }

    $conn = new PDO("sqlite:" . $db_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connection successful!\n";

    $res = $conn->query("SELECT name FROM sqlite_master WHERE type='table'");
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo "Table: " . $row['name'] . "\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    echo "Available drivers: " . implode(", ", PDO::getAvailableDrivers());
}
