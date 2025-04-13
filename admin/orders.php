<?php
session_start();
include '../app/config/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../index.php'); // Chuyển hướng về trang chủ nếu không phải admin
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    // Lấy trạng thái hiện tại của đơn hàng
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $current_status = $stmt->fetchColumn();

    // Kiểm tra không cho phép thay đổi trạng thái "Đã hủy" hoặc "Đã trả hàng"
    if (in_array($current_status, ['CANCELLED', 'RETURNED'])) {
        $error_message = 'Không thể thay đổi trạng thái của đơn hàng đã hủy hoặc đã trả hàng.';
    } else {
        // Kiểm tra trạng thái không cho phép chuyển về trạng thái cũ
        if (
            ($current_status == 'PROCESSING' && in_array($status, ['PENDING'])) ||
            ($current_status == 'SHIPPED' && in_array($status, ['PENDING', 'PROCESSING'])) ||
            ($current_status == 'DELIVERED' && in_array($status, ['PENDING', 'PROCESSING', 'SHIPPED'])) ||
            ($current_status == 'CANCELLED' && in_array($status, ['PENDING', 'PROCESSING', 'SHIPPED', 'DELIVERED'])) ||
            ($current_status == 'RETURNED' && in_array($status, ['PENDING', 'PROCESSING', 'SHIPPED', 'DELIVERED']))
        ) {
            $error_message = 'Không thể thay đổi trạng thái đơn hàng từ "' . $current_status . '" về trạng thái "' . $status . '".';
        } else {
            // Kiểm tra trạng thái có hợp lệ không
            $valid_status = ['PENDING', 'PAID', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'RETURNED'];
            if (!in_array($status, $valid_status)) {
                $error_message = 'Trạng thái không hợp lệ.';
            } else {
                try {
                    // Cập nhật trạng thái đơn hàng trong cơ sở dữ liệu
                    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $order_id]);

                    if ($stmt->rowCount() > 0) {
                        $success_message = 'Trạng thái đơn hàng đã được cập nhật thành công!';
                    } else {
                        $error_message = 'Không có thay đổi gì đối với trạng thái đơn hàng.';
                    }
                } catch (PDOException $e) {
                    $error_message = 'Lỗi cập nhật trạng thái: ' . $e->getMessage();
                }
            }
        }
    }
}

// Lấy danh sách đơn hàng
$query = "SELECT o.*, u.username as user_name, u.email as user_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          ORDER BY o.created_at DESC";
$stmt = $conn->query($query);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - MyStore</title>
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
                    <h1 class="h2">Quản lý đơn hàng</h1>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php elseif (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã đơn hàng</th>
                                        <th>Khách hàng</th>
                                        <th>Email</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày đặt</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                                            <td><?php echo number_format($order['total_price'], 0, ',', '.'); ?>đ</td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" <?php echo in_array($order['status'], ['CANCELLED', 'RETURNED']) ? 'disabled' : ''; ?>>
                                                        <option value="PENDING" <?php echo $order['status'] == 'PENDING' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                                        <option value="PROCESSING" <?php echo $order['status'] == 'PROCESSING' ? 'selected' : ''; ?>>Đang xử lý</option>
                                                        <option value="SHIPPED" <?php echo $order['status'] == 'SHIPPED' ? 'selected' : ''; ?>>Đã giao hàng</option>
                                                        <option value="DELIVERED" <?php echo $order['status'] == 'DELIVERED' ? 'selected' : ''; ?>>Đã nhận hàng</option>
                                                        <option value="CANCELLED" <?php echo $order['status'] == 'CANCELLED' ? 'selected' : ''; ?>>Đã hủy</option>
                                                        <option value="RETURNED" <?php echo $order['status'] == 'RETURNED' ? 'selected' : ''; ?>>Đã trả hàng</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
