<?php
class Product {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function search($params) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE 1=1";

        $queryParams = [];

        if (!empty($params['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $queryParams[] = "%{$params['search']}%";
            $queryParams[] = "%{$params['search']}%";
        }

        if (!empty($params['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $queryParams[] = $params['category_id'];
        }

        if (!empty($params['min_price'])) {
            $sql .= " AND p.price >= ?";
            $queryParams[] = $params['min_price'];
        }

        if (!empty($params['max_price'])) {
            $sql .= " AND p.price <= ?";
            $queryParams[] = $params['max_price'];
        }

        // Sort
        switch ($params['sort'] ?? '') {
            case 'price_asc':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY p.name ASC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY p.name DESC";
                break;
            default:
                $sql .= " ORDER BY p.created_at DESC";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($queryParams);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name 
                                     FROM products p 
                                     LEFT JOIN categories c ON p.category_id = c.id 
                                     WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
// //Finally, we can create a new file named `Product.php` in the `app/models` directory. This file will handle the logic for interacting with the products in the database. The code in this file will use the PDO connection to fetch products based on search criteria and product details by ID.
// /This file will also include methods for searching products and getting product details by ID.