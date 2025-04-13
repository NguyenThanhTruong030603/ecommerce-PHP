<?php
session_start();
include 'app/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách danh mục
$stmt = $conn->query("SELECT c.*, 
                     (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
                     FROM categories c 
                     ORDER BY c.name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh mục sản phẩm - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include './app/views/header.php'; ?>

    <!-- Categories Section -->
    <div class="container my-5">
        <h1 class="text-center mb-5">Danh mục sản phẩm</h1>
        
        <div class="row g-4">
            <?php foreach($categories as $category): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h3>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">
                                    <?php echo $category['product_count']; ?> sản phẩm
                                </span>
                                <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-primary">
                                    Xem sản phẩm
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if(count($categories) == 0): ?>
            <div class="text-center">
                <p class="lead">Chưa có danh mục sản phẩm nào.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Features Section -->
    <div class="bg-light py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                        <h4>Giao hàng nhanh chóng</h4>
                        <p>Miễn phí vận chuyển cho đơn hàng trên 500.000đ</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                        <h4>Bảo hành chính hãng</h4>
                        <p>Cam kết bảo hành 12 tháng cho tất cả sản phẩm</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                        <h4>Hỗ trợ 24/7</h4>
                        <p>Đội ngũ tư vấn luôn sẵn sàng hỗ trợ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include './app/views/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 