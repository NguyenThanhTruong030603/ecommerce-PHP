<?php
session_start();
include 'app/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Lấy thông tin giỏ hàng
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image, p.stock 
                       FROM cart c 
                       JOIN products p ON c.product_id = p.id 
                       WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($cart_items) == 0) {
    header("Location: cart.php");
    exit();
}

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Tính tổng tiền
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Xử lý đặt hàng
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->beginTransaction();

        // Lấy thông tin giao hàng và ghi chú từ form
        $shipping_address = $_POST['shipping_address'];
        $notes = $_POST['notes'];

        // Tạo đơn hàng mới
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address, notes) 
                               VALUES (?, ?, 'PENDING', ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $total, $shipping_address, $notes]);
        $order_id = $conn->lastInsertId();

        // Thêm chi tiết đơn hàng
        $stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart_items as $item) {
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            
            // Cập nhật số lượng tồn kho
            $new_stock = $item['stock'] - $item['quantity'];
            $update_stock = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $update_stock->execute([$new_stock, $item['product_id']]);
        }

        // Xóa giỏ hàng
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        // Tạo thanh toán
        $payment_method = $_POST['payment_method'];
        $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method) VALUES (?, ?)");
        $stmt->execute([$order_id, $payment_method]);

        $conn->commit();

        $_SESSION['success'] = "Đặt hàng thành công!";
        header("Location: order-confirmation.php?id=" . $order_id);
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Có lỗi xảy ra khi đặt hàng. Vui lòng thử lại.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include './app/views/header.php'; ?>

    <!-- Checkout Section -->
    <div class="container my-5">
        <h2 class="mb-4">Thanh toán</h2>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Thông tin giao hàng</h4>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Họ tên</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ giao hàng</label>
                                <textarea class="form-control" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phương thức thanh toán</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="">Chọn phương thức thanh toán</option>
                                    <option value="CASH">Thanh toán khi nhận hàng</option>
                                    <option value="VNPAY">VNPay</option>
                                    <option value="PAYPAL">PayPal</option>
                                    <option value="CREDIT_CARD">Thẻ tín dụng</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Đặt hàng</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Đơn hàng</h4>
                        <?php foreach($cart_items as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">Số lượng: <?php echo $item['quantity']; ?></small>
                                </div>
                                <div>
                                    <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> đ
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Tổng cộng:</strong>
                            <strong><?php echo number_format($total, 0, ',', '.'); ?> đ</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include './app/views/header.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
