<?php
session_start();
include '../app/config/database.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Thêm mã giảm giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount = floatval($_POST['discount']);
    $expiry_date = $_POST['expiry_date'];

    if (empty($code) || $discount <= 0 || $discount > 100 || empty($expiry_date)) {
        $error = "Vui lòng nhập đầy đủ và hợp lệ các thông tin.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO coupons (code, discount, expiry_date) VALUES (?, ?, ?)");
            $stmt->execute([$code, $discount, $expiry_date]);
            $success = "Thêm mã giảm giá thành công!";
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

// Xóa mã giảm giá
if (isset($_POST['delete']) && isset($_POST['coupon_id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$_POST['coupon_id']]);
        $success = "Xóa mã giảm giá thành công!";
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách mã giảm giá
$stmt = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC");
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý mã giảm giá - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include '../app/views/adminHeader.php'; ?>
        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h2>Quản lý mã giảm giá</h2>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Thêm mã mới -->
            <div class="card mb-4">
                <div class="card-header">Thêm mã giảm giá</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Mã</label>
                                <input type="text" name="code" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">% Giảm</label>
                                <input type="number" step="0.01" name="discount" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ngày hết hạn</label>
                                <input type="date" name="expiry_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button type="submit" name="add" class="btn btn-success">Thêm mã</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danh sách mã -->
            <div class="card">
                <div class="card-header">Danh sách mã giảm giá</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mã</th>
                            <th>% Giảm</th>
                            <th>Hết hạn</th>
                            <th>Thêm lúc</th>
                            <th>Thao tác</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($coupons as $coupon): ?>
                            <tr>
                                <td><?php echo $coupon['id']; ?></td>
                                <td><?php echo htmlspecialchars($coupon['code']); ?></td>
                                <td><?php echo $coupon['discount']; ?>%</td>
                                <td><?php echo $coupon['expiry_date']; ?></td>
                                <td><?php echo $coupon['created_at']; ?></td>
                                <td>
                                    <a href="coupon-edit.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Xóa mã này?');">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
