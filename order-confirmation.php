<?php
session_start();
include 'app/config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT o.*, u.username, u.email, u.phone 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: index.php");
    exit();
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("SELECT od.*, p.name, p.image 
                       FROM order_details od 
                       JOIN products p ON od.product_id = p.id 
                       WHERE od.order_id = ?");
$stmt->execute([$_GET['id']]);
$order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin thanh toán
$stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ?");
$stmt->execute([$_GET['id']]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đơn hàng - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include './app/views/header.php'; ?>

    <!-- Order Confirmation Section -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        <h2 class="mt-3">Đặt hàng thành công!</h2>
                        <p class="lead">Cảm ơn bạn đã mua hàng tại MyStore</p>
                        <p>Mã đơn hàng của bạn là: <strong>#<?php echo htmlspecialchars($order['id']); ?></strong></p>
                        <?php if ($payment['payment_method'] === 'CASH'): ?>
                            <p class="text-info">Bạn sẽ thanh toán <strong><?php echo number_format($order['total_price'], 0, ',', '.'); ?> đ</strong> khi nhận hàng.</p>
                        <?php elseif ($payment['payment_method'] === 'VNPAY' && $payment['status'] === 'COMPLETED'): ?>
                            <p class="text-success">Thanh toán VNPay thành công! Mã giao dịch: <strong><?php echo htmlspecialchars($payment['transaction_id']); ?></strong></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h4 class="card-title">Chi tiết đơn hàng</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Giá</th>
                                        <th>Số lượng</th>
                                        <th>Tổng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_details as $detail): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="./uploads/products/<?php echo htmlspecialchars($detail['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($detail['name']); ?>"
                                                         class="img-thumbnail me-3" style="width: 80px;">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($detail['name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo number_format($detail['price'], 0, ',', '.'); ?> đ</td>
                                            <td><?php echo $detail['quantity']; ?></td>
                                            <td><?php echo number_format($detail['price'] * $detail['quantity'], 0, ',', '.'); ?> đ</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                        <td><strong><?php echo number_format($order['total_price'], 0, ',', '.'); ?> đ</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h4 class="card-title">Thông tin giao hàng</h4>
                        <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                        <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                        <p><strong>Địa chỉ giao hàng:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                        <p><strong>Trạng thái:</strong> 
                            <span class="badge <?php echo $order['status'] === 'PROCESSING' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $order['status'] === 'PROCESSING' ? 'Đang xử lý' : 'Chờ xử lý'; ?>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h4 class="card-title">Thông tin thanh toán</h4>
                        <p><strong>Phương thức:</strong> 
                            <?php echo $payment['payment_method'] === 'CASH' ? 'Thanh toán khi nhận hàng (COD)' : htmlspecialchars($payment['payment_method']); ?>
                        </p>
                        <p><strong>Trạng thái thanh toán:</strong> 
                            <span class="badge <?php echo $payment['status'] === 'COMPLETED' ? 'bg-success' : ($payment['status'] === 'FAILED' ? 'bg-danger' : 'bg-warning'); ?>">
                                <?php echo $payment['status'] === 'COMPLETED' ? 'Thành công' : ($payment['status'] === 'FAILED' ? 'Thất bại' : 'Chờ xử lý'); ?>
                            </span>
                        </p>
                        <?php if ($payment['payment_method'] === 'VNPAY' && $payment['transaction_id']): ?>
                            <p><strong>Mã giao dịch:</strong> <?php echo htmlspecialchars($payment['transaction_id']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
                    </a>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-user"></i> Xem đơn hàng của tôi
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include './app/views/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php // Đảm bảo thẻ đóng PHP ?>