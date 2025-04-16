<?php
session_start();
include '../app/config/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../index.php');
    exit();
}

// Kiểm tra ID người dùng
if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$user_id = $_GET['id'];

$database = new Database();
$conn = $database->getConnection();

// Lấy thông tin người dùng
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
          (SELECT SUM(total_price) FROM orders WHERE user_id = u.id) as total_spent
          FROM users u 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php');
    exit();
}

// Lấy danh sách đơn hàng của người dùng
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra thay đổi vai trò người dùng 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['role'])) {
    // Chỉ cho phép thay đổi vai trò nếu người dùng không phải là admin
    if ($user['role'] !== 'ADMIN') {
        $new_role = $_POST['role'];
        $update_query = "UPDATE users SET role = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([$new_role, $user_id]);

        // Reload lại trang sau khi thay đổi vai trò
        header("Location: user-detail.php?id=" . $user_id);
        exit();
    } else {
        // Thông báo lỗi nếu người dùng là admin
        echo "<script>alert('Không thể thay đổi vai trò của người dùng này.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết người dùng - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
        <?php include '../app/views/adminHeader.php'; ?>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Chi tiết người dùng</h1>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <div class="row">
                    <!-- Thông tin người dùng -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Thông tin cá nhân</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
                                <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                                <p><strong>Vai trò:</strong> 
                                    <span class="badge bg-<?php echo $user['role'] == 'ADMIN' ? 'danger' : 'primary'; ?>">
                                        <?php echo $user['role'] == 'ADMIN' ? 'Admin' : 'Khách hàng'; ?>
                                    </span>
                                </p>

                                <!-- Thay đổi vai trò -->
                                <?php if ($user['role'] !== 'ADMIN'): ?>
                                    <form method="POST">
    <div class="mb-3">
        <label for="role" class="form-label">Chọn vai trò</label>
        <select class="form-select" id="role" name="role" <?php echo $user['role'] == 'ADMIN' ? 'disabled' : ''; ?>>
            <option value="ADMIN" <?php echo $user['role'] == 'ADMIN' ? 'selected' : ''; ?>>Admin</option>
            <option value="USER" <?php echo $user['role'] == 'USER' ? 'selected' : ''; ?>>Khách hàng</option>
        </select>
    </div>
    <?php if ($user['role'] == 'ADMIN'): ?>
        <p class="text-muted">Không thể thay đổi vai trò của người dùng này.</p>
    <?php else: ?>
        <button type="submit" class="btn btn-primary">Cập nhật vai trò</button>
    <?php endif; ?>
</form>
                                <?php else: ?>
                                    <p>Không thể thay đổi vai trò của người dùng này.</p>
                                <?php endif; ?>

                                <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Thống kê</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Số đơn hàng:</strong> <?php echo $user['order_count']; ?></p>
                                <p><strong>Tổng chi tiêu:</strong> <?php echo number_format($user['total_spent'] ?? 0, 0, ',', '.'); ?>đ</p>
                            </div>
                        </div>
                    </div>

                    <!-- Danh sách đơn hàng -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Lịch sử đơn hàng</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($orders)): ?>
                                    <p class="text-muted">Chưa có đơn hàng nào.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Mã đơn</th>
                                                    <th>Ngày đặt</th>
                                                    <th>Tổng tiền</th>
                                                    <th>Trạng thái</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td>#<?php echo $order['id']; ?></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                                        <td><?php echo number_format($order['total_price'], 0, ',', '.'); ?>đ</td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                $status = strtolower($order['status']); 
                                                                echo $status == 'pending' ? 'warning' : 
                                                                    ($status == 'processing' ? 'info' : 
                                                                    ($status == 'shipped' ? 'primary' : 
                                                                    ($status == 'delivered' ? 'success' : 
                                                                    ($status == 'returned' ? 'secondary' : 'danger')))); 
                                                            ?>">
                                                                <?php 
                                                                    echo $status == 'pending' ? 'Chờ xử lý' : 
                                                                        ($status == 'processing' ? 'Đang xử lý' : 
                                                                        ($status == 'shipped' ? 'Đã giao hàng' : 
                                                                        ($status == 'delivered' ? 'Đã nhận hàng' : 
                                                                        ($status == 'returned' ? 'Đã trả hàng' : 'Đã hủy')))); 
                                                                ?>
                                                            </span>
                                                        </td>

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
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
