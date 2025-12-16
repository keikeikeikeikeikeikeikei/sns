<?php

class Database
{
    // Use relative path resolution from __DIR__
    private $db_file;
    public $conn;

    public function getConnection()
    {
        // backend/src/Database.php -> ../../sns_debug.db
        // Use dirname to resolve path without requiring file existence (unlike realpath)
        $this->db_file = dirname(__DIR__, 2) . '/sns_debug.db';

        error_log("Raw Path: " . __DIR__ . '/../../sns_debug.db');
        error_log("Resolved DB File Path: " . $this->db_file);

        $this->conn = null;

        try {
            error_log("Connecting to SQLite database at: " . $this->db_file);
            $this->conn = new PDO("sqlite:" . $this->db_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("PRAGMA foreign_keys = ON;");
        } catch (PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            echo json_encode(['error' => 'Connection error: ' . $exception->getMessage()]);
            exit;
        }

        return $this->conn;
    }
}
