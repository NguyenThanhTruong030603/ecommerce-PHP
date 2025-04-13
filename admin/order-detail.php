<?php
session_start();
include '../app/config/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../index.php');
    exit();
}

// Kiểm tra ID đơn hàng
if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$order_id = $_GET['id'];

$database = new Database();
$conn = $database->getConnection();

// Lấy thông tin đơn hàng
// Lấy thông tin đơn hàng, bao gồm payment_method
$query = "SELECT o.*, u.username as user_name, u.email as user_email, u.phone as user_phone, o.coupon_id, o.payment_method
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$order) {
    header('Location: orders.php');
    exit();
}

// Lấy chi tiết sản phẩm trong đơn hàng
$query = "SELECT od.*, p.name as product_name, p.image as product_image 
          FROM order_details od 
          JOIN products p ON od.product_id = p.id 
          WHERE od.order_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$order_id]);
$order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Hiển thị trạng thái đúng
$status = strtolower($order['status']); // Chuyển về chữ thường để dễ so sánh
switch ($status) {
    case 'pending':
        $statusText = 'Chờ xử lý';
        $statusClass = 'warning'; // Màu vàng cho trạng thái chờ xử lý
        break;
    case 'processing':
        $statusText = 'Đang xử lý';
        $statusClass = 'info'; // Màu xanh cho trạng thái đang xử lý
        break;
    case 'shipped':
        $statusText = 'Đã giao hàng';
        $statusClass = 'primary'; // Màu xanh đậm cho trạng thái đã giao
        break;
        case 'delivered':
            $statusText = 'Đã nhận hàng';
            $statusClass = 'primary'; // Màu xanh đậm cho trạng thái đã giao
            break;
    case 'cancelled':
        $statusText = 'Đã hủy';
        $statusClass = 'danger'; // Màu đỏ cho trạng thái đã hủy
        break;
    case 'returned':
        $statusText = 'Đã trả hàng';
        $statusClass = 'success'; // Màu xanh lá cho trạng thái đã trả
        break;
    default:
        $statusText = 'Không xác định';
        $statusClass = 'secondary'; // Màu xám cho trạng thái không xác định
        break;
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order_id; ?> - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../app/views/adminHeader.php'; ?>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Chi tiết đơn hàng #<?php echo $order_id; ?></h1>
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <div class="row">
                    <!-- Thông tin đơn hàng -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Thông tin đơn hàng</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Mã đơn hàng:</strong> #<?php echo $order_id; ?></p>
                                <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                <p><strong>Trạng thái:</strong> 
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </p>
                                <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_price'], 0, ',', '.'); ?>đ</p>
                            </div>
                        </div>

                        <div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Thông tin đơn hàng</h5>
    </div>
    <div class="card-body">
        <p><strong>Mã đơn hàng:</strong> #<?php echo $order_id; ?></p>
        <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
        <p><strong>Trạng thái:</strong> 
            <span class="badge bg-<?php echo $statusClass; ?>">
                <?php echo $statusText; ?>
            </span>
        </p>
        <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_price'], 0, ',', '.'); ?>đ</p>
        <p><strong>Phương thức thanh toán:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
    </div>
</div>

                    </div>

                    <!-- Danh sách sản phẩm -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Danh sách sản phẩm</h5>
                            </div>
                            <div class="card-body">
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
                                                            <img src="../uploads/products/<?php echo htmlspecialchars($detail['product_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($detail['product_name']); ?>"
                                                                 class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                            <?php echo htmlspecialchars($detail['product_name']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo number_format($detail['price'], 0, ',', '.'); ?>đ</td>
                                                    <td><?php echo $detail['quantity']; ?></td>
                                                    <td><?php echo number_format($detail['price'] * $detail['quantity'], 0, ',', '.'); ?>đ</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
