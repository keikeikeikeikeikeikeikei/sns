<?php

class RateLimiter
{
    private $db;
    private $limit;
    private $window;

    public function __construct($dbConnection, $limit = 60, $window = 60)
    {
        $this->db = $dbConnection;
        $this->limit = $limit;   // Max requests
        $this->window = $window; // Window size in seconds
    }

    public function check($ip, $endpoint)
    {
        $currentTime = time();
        $windowStart = $currentTime - ($currentTime % $this->window);

        // Check existing record
        $stmt = $this->db->prepare("SELECT window_start, request_count FROM rate_limits WHERE ip_address = :ip AND endpoint = :endpoint");
        $stmt->execute([':ip' => $ip, ':endpoint' => $endpoint]);
        $row = $stmt->fetch();

        if ($row) {
            if ($row['window_start'] < $windowStart) {
                // Window expired, reset
                $update = $this->db->prepare("UPDATE rate_limits SET window_start = :start, request_count = 1 WHERE ip_address = :ip AND endpoint = :endpoint");
                $update->execute([':start' => $windowStart, ':ip' => $ip, ':endpoint' => $endpoint]);
                return true;
            } else {
                // Within window
                if ($row['request_count'] >= $this->limit) {
                    return false; // Limit exceeded
                } else {
                    // Increment
                    $update = $this->db->prepare("UPDATE rate_limits SET request_count = request_count + 1 WHERE ip_address = :ip AND endpoint = :endpoint");
                    $update->execute([':ip' => $ip, ':endpoint' => $endpoint]);
                    return true;
                }
            }
        } else {
            // New record
            $insert = $this->db->prepare("INSERT INTO rate_limits (ip_address, endpoint, window_start, request_count) VALUES (:ip, :endpoint, :start, 1)");
            $insert->execute([':ip' => $ip, ':endpoint' => $endpoint, ':start' => $windowStart]);
            return true;
        }
    }
}
