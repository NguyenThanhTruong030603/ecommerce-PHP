<?php
session_start();
include 'app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Xử lý cập nhật số lượng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        if ($quantity > 0) {
            // Lấy số lượng tồn kho của sản phẩm
            $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $stock = $product['stock'];
                
                // Kiểm tra xem số lượng yêu cầu có vượt quá số lượng tồn kho không
                if ($quantity > $stock) {
                    $_SESSION['error'] = "Số lượng sản phẩm '{$product_id}' không thể lớn hơn số lượng tồn kho ($stock).";
                    header("Location: cart.php");
                    exit();
                }
                
                // Cập nhật số lượng giỏ hàng
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $_SESSION['user_id'], $product_id]);
            }
        } else {
            // Xóa sản phẩm khỏi giỏ hàng nếu số lượng bằng 0
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
        }
    }
    $_SESSION['success'] = "Đã cập nhật giỏ hàng!";
    header("Location: cart.php");
    exit();
}

// Xử lý xóa sản phẩm
if (isset($_GET['remove'])) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $_GET['remove']]);
    $_SESSION['success'] = "Đã xóa sản phẩm khỏi giỏ hàng!";
    header("Location: cart.php");
    exit();
}

// Lấy thông tin giỏ hàng
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image 
                       FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính tổng tiền
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include './app/views/header.php'; ?>

    <!-- Cart Section -->
    <div class="container my-5">
        <h2 class="mb-4">Giỏ hàng</h2>

        <!-- Display success or error message -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(count($cart_items) > 0): ?>
            <form method="POST" action="">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Tổng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cart_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="./uploads/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="img-thumbnail me-3" style="width: 100px;">
                                            <div>
                                                <h5 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h5>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</td>
                                    <td>
                                        <?php 
                                        // Lấy số lượng tồn kho của sản phẩm để giới hạn
                                        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
                                        $stmt->execute([$item['product_id']]);
                                        $product = $stmt->fetch(PDO::FETCH_ASSOC);
                                        $stock = $product['stock'];
                                        ?>
                                        <input type="number" name="quantity[<?php echo $item['product_id']; ?>]" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="0" max="<?php echo $stock; ?>" class="form-control" style="width: 100px;">
                                    </td>
                                    <td><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> đ</td>
                                    <td>
                                        <a href="cart.php?remove=<?php echo $item['product_id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                <td><strong><?php echo number_format($total, 0, ',', '.'); ?> đ</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                    </a>
                    <div>
                        <button type="submit" name="update_cart" class="btn btn-primary me-2">
                            <i class="fas fa-sync"></i> Cập nhật giỏ hàng
                        </button>
                        <a href="checkout.php" class="btn btn-success">
                            <i class="fas fa-check"></i> Thanh toán
                        </a>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                Giỏ hàng của bạn đang trống. 
                <a href="products.php">Tiếp tục mua sắm</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include './app/views/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
