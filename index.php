<?php
session_start();
include 'app/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách danh mục
$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy sản phẩm mới nhất (chỉ hiển thị 4 sản phẩm)
$stmt = $conn->query("SELECT p.*, c.name as category_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     ORDER BY p.created_at DESC 
                     LIMIT 4");
$new_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy sản phẩm bán chạy nhất (chỉ hiển thị 4 sản phẩm)
$stmt = $conn->query("SELECT p.*, c.name as category_name, 
                     (SELECT SUM(quantity) FROM order_details WHERE product_id = p.id) as total_sold
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     ORDER BY total_sold DESC 
                     LIMIT 4");
$best_sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy tất cả sản phẩm (danh sách tất cả sản phẩm)
$stmt = $conn->query("SELECT p.*, c.name as category_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id");
$all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyStore - Cửa hàng giày dép uy tín</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include './app/views/header.php'; ?>

    <!-- Hero Section -->
    <div class="bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold">Chào mừng đến với MyStore</h1>
                    <p class="lead">Khám phá bộ sưu tập giày dép đa dạng với chất lượng tốt nhất</p>
                    <a href="products.php" class="btn btn-light btn-lg">
                        <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                    </a>
                </div>
                <div class="col-md-6">
                    <img src="./uploads/products/Anh.webp" alt="Hero Image" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <!-- Các danh mục có thể được thêm vào ở đây nếu muốn -->

    <!-- New Products Section (Hiển thị chỉ 4 sản phẩm) -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Sản phẩm mới nhất</h2>
        <div class="row g-4">
            <?php foreach($new_products as $product): ?>
                <div class="col-md-3">
                    <div class="card h-100">
                        <img src="./uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="width: 100%; height: 220px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </p>
                            <p class="card-text">
                                <strong><?php echo number_format($product['price'], 0, ',', '.'); ?> đ</strong>
                            </p>
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                Chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Best Sellers Section (Hiển thị chỉ 4 sản phẩm) -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Sản phẩm bán chạy</h2>
        <div class="row g-4">
            <?php foreach($best_sellers as $product): ?>
                <div class="col-md-3">
                    <div class="card h-100">
                        <img src="./uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="width: 100%; height: 220px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </p>
                            <p class="card-text">
                                <strong><?php echo number_format($product['price'], 0, ',', '.'); ?> đ</strong>
                            </p>
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                Chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- All Products Section (Danh sách tất cả sản phẩm) -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Danh sách tất cả sản phẩm</h2>
        <div class="row g-4">
            <?php foreach($all_products as $product): ?>
                <div class="col-md-3">
                    <div class="card h-100">
                        <img src="./uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="width: 100%; height: 220px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </p>
                            <p class="card-text">
                                <strong><?php echo number_format($product['price'], 0, ',', '.'); ?> đ</strong>
                            </p>
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                Chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
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

    <!-- Footer -->
    <?php include './app/views/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
