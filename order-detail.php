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
$stmt = $conn->prepare("SELECT o.*, u.username, u.email, u.phone, u.address, p.payment_method 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       LEFT JOIN payments p ON o.id = p.order_id
                       WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: profile.php");
    exit();
}

// Lấy chi tiết đơnhàng
$stmt = $conn->prepare("SELECT od.*, p.name, p.image 
                       FROM order_details od 
                       JOIN products p ON od.product_id = p.id 
                       WHERE od.order_id = ?");
$stmt->execute([$_GET['id']]);
$order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý hủy đơn hàng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_order']) && $order['status'] == 'PENDING') {
    $stmt = $conn->prepare("UPDATE orders SET status = 'CANCELLED' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: order-detail.php?id=" . $_GET['id']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order['id']; ?> - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include './app/views/header.php'; ?>

    <!-- Order Detail Section -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">Chi tiết đơn hàng #<?php echo $order['id']; ?></h4>
                            <?php
                            $status_class = [
                                'PENDING' => 'bg-warning',
                                'PROCESSING' => 'bg-info',
                                'SHIPPED' => 'bg-primary',
                                'DELIVERED' => 'bg-success',
                                'CANCELLED' => 'bg-danger'
                            ];
                            $status_text = [
                                'PENDING' => 'Chờ xử lý',
                                'PROCESSING' => 'Đang xử lý',
                                'SHIPPED' => 'Đang giao hàng',
                                'DELIVERED' => 'Đã giao hàng',
                                'CANCELLED' => 'Đã hủy'
                            ];
                            ?>
                            <span class="badge <?php echo $status_class[$order['status']]; ?>">
                                <?php echo $status_text[$order['status']]; ?>
                            </span>
                        </div>

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
                                    <?php foreach($order_details as $detail): ?>
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
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Thông tin giao hàng</h4>
                        <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                        <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                        <p><strong>Phương thức thanh toán:</strong> 
                            <?php
                            $payment_methods = [
                                'CASH' => 'Thanh toán khi nhận hàng',
                                'VNPAY' => 'VNPay',
                                'PAYPAL' => 'PayPal',
                                'CREDIT_CARD' => 'Thẻ tín dụng'
                            ];
                            echo $payment_methods[$order['payment_method']];
                            ?>
                        </p>
                        <p><strong>Ngày đặt hàng:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                </div>

                <?php if($order['status'] == 'PENDING'): ?>
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Hủy đơn hàng</h4>
                            <p>Bạn có thể hủy đơn hàng này.</p>
                            <form method="POST" action="" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?');">
                                <button type="submit" name="cancel_order" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Hủy đơn hàng
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
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