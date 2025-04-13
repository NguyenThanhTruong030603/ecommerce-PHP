<?php
session_start();
include 'app/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy từ khóa tìm kiếm từ yêu cầu GET
$term = isset($_GET['term']) ? $_GET['term'] : '';

// Truy vấn tìm kiếm sản phẩm
$sql = "SELECT id, name FROM products WHERE name LIKE ? LIMIT 10"; // Giới hạn kết quả tìm kiếm
$stmt = $conn->prepare($sql);
$stmt->execute(["%$term%"]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Trả về kết quả tìm kiếm dưới dạng JSON
$response = [];
foreach ($results as $product) {
    $response[] = [
        'label' => $product['name'],  // Tên sản phẩm gợi ý
        'value' => $product['name'],  // Tên sản phẩm khi người dùng chọn
        'id' => $product['id']       // ID sản phẩm
    ];
}

echo json_encode($response);
?>
