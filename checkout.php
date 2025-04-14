<?php
session_start();
include 'app/config/database.php';
include 'app/config/vnpay_config.php';

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

// Kiểm tra tổng tiền hợp lệ
if ($total <= 0) {
    $error = "Tổng tiền không hợp lệ.";
}

// Xử lý đặt hàng
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Lấy thông tin giao hàng và ghi chú từ form
        $shipping_address = $_POST['shipping_address'];
        $notes = $_POST['notes'];
        $payment_method = $_POST['payment_method'];

        // Kiểm tra phương thức thanh toán hợp lệ
        $valid_methods = ['CASH', 'VNPAY'];
        if (!in_array($payment_method, $valid_methods)) {
            throw new Exception("Phương thức thanh toán không hợp lệ.");
        }

        if ($payment_method === 'VNPAY') {
            // Lưu tạm thông tin vào SESSION cho VNPay
            $_SESSION['vnpay_pending'] = [
                'cart_items' => $cart_items,
                'total' => $total,
                'shipping_address' => $shipping_address,
                'notes' => $notes,
                'user_id' => $_SESSION['user_id'],
                'txn_ref' => time() // Dùng time() làm txn_ref tạm
            ];

            // Tạo URL thanh toán VNPay
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            $vnp_TxnRef = $_SESSION['vnpay_pending']['txn_ref'];
            $vnp_OrderInfo = "Thanh toán cho giao dịch: " . $vnp_TxnRef;
            $vnp_OrderType = "other";
            $vnp_Amount = (int)($total * 100);
            $vnp_Locale = 'vn';
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
            $vnp_CreateDate = date('YmdHis');
            $vnp_ExpireDate = date('YmdHis', strtotime('+15 minutes'));

            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => VNPAY_TMN_CODE,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => $vnp_CreateDate,
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => VNPAY_RETURN_URL,
                "vnp_TxnRef" => $vnp_TxnRef,
                "vnp_ExpireDate" => $vnp_ExpireDate
            );

            // Debug: Ghi log yêu cầu
            error_log("VNPay request data: " . print_r($inputData, true));

            ksort($inputData);
            $query = "";
            $hashdata = "";
            $i = 0;
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = VNPAY_URL . "?" . $query;
            $vnpSecureHash = hash_hmac("sha512", $hashdata, VNPAY_HASH_SECRET);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

            // Debug: Ghi log URL
            error_log("VNPay redirect URL: " . $vnp_Url);

            // Chuyển hướng đến VNPay
            header("Location: $vnp_Url");
            exit();
        } else {
            // COD: Lưu đơn hàng ngay
            $conn->beginTransaction();

            // Tạo đơn hàng
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address, notes, payment_method) 
                                   VALUES (?, ?, 'PENDING', ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total, $shipping_address, $notes, $payment_method]);
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

            // Tạo bản ghi thanh toán
            $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, status) VALUES (?, ?, ?)");
            $stmt->execute([$order_id, $payment_method, 'PENDING']);

            $conn->commit();

            $_SESSION['success'] = "Đặt hàng thành công! Bạn sẽ thanh toán khi nhận hàng.";
            header("Location: order-confirmation.php?id=" . $order_id);
            exit();
        }
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = "Có lỗi xảy ra: " . $e->getMessage();
        error_log("Checkout error: " . $e->getMessage());
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
                                    <option value="CASH" selected>Thanh toán khi nhận hàng (COD)</option>
                                    <option value="VNPAY">VNPay</option>
                                </select>
                                <small class="form-text text-muted">VNPay sẽ hiển thị danh sách ngân hàng (chọn NCB trong sandbox).</small>
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

    <?php include './app/views/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>