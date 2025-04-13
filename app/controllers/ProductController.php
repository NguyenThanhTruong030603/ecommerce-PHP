<?php
require_once './app/models/Product.php';
require_once './app/models/Category.php';

class ProductController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function search() {
        $productModel = new Product($this->conn);
        $categoryModel = new Category($this->conn);

        $params = [
            'search' => $_GET['q'] ?? '',
            'category_id' => $_GET['category'] ?? 0,
            'min_price' => $_GET['min_price'] ?? 0,
            'max_price' => $_GET['max_price'] ?? 0,
            'sort' => $_GET['sort'] ?? 'newest'
        ];

        $products = $productModel->search($params);
        $categories = $categoryModel->all();

        include './app/views/product/search.php';
    }

    public function detail() {
        if (!isset($_GET['id'])) {
            header("Location: index.php?page=search");
            exit();
        }

        $productModel = new Product($this->conn);
        $product = $productModel->findById($_GET['id']);

        if (!$product) {
            header("Location: index.php?page=search");
            exit();
        }

        include './app/views/product/detail.php';
    }
}
