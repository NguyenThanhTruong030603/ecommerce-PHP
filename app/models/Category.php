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
//finally, we can create a new file named `Category.php` in the `app/models` directory. This file will handle the logic for interacting with the categories in the database. The code in this file will use the PDO connection to fetch all categories from the database.