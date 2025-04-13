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

// Lấy ID mã giảm giá cần sửa
if (!isset($_GET['id'])) {
    header('Location: coupon-manage.php');
    exit();
}

$coupon_id = $_GET['id'];

// Lấy dữ liệu hiện tại
$stmt = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
$stmt->execute([$coupon_id]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    header('Location: coupon-manage.php');
    exit();
}

// Xử lý cập nhật
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $discount = floatval($_POST['discount']);
    $expiry_date = $_POST['expiry_date'];

    if (empty($code) || $discount <= 0 || $discount > 100 || empty($expiry_date)) {
        $error = "Vui lòng nhập đầy đủ và hợp lệ các thông tin.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE coupons SET code = ?, discount = ?, expiry_date = ? WHERE id = ?");
            $stmt->execute([$code, $discount, $expiry_date, $coupon_id]);
            $success = "Cập nhật mã giảm giá thành công!";

            // Reload dữ liệu
            $stmt = $conn->prepare("SELECT * FROM coupons WHERE id = ?");
            $stmt->execute([$coupon_id]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa mã giảm giá - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include '../app/views/adminHeader.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Chỉnh sửa mã giảm giá</h1>
                <a href="coupons.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="code" class="form-label">Mã</label>
                            <input type="text" name="code" id="code" class="form-control" required
                                   value="<?php echo htmlspecialchars($coupon['code']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="discount" class="form-label">% Giảm</label>
                            <input type="number" step="0.01" name="discount" id="discount" class="form-control" required
                                   value="<?php echo $coupon['discount']; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="expiry_date" class="form-label">Ngày hết hạn</label>
                            <input type="date" name="expiry_date" id="expiry_date" class="form-control" required
                                   value="<?php echo $coupon['expiry_date']; ?>">
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Cập nhật
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
