<?php
class Category {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function all() {
        $stmt = $this->conn->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
