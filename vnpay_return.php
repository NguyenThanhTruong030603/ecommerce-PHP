<?php
session_start();
include 'app/config/database.php';
include 'app/config/vnpay_config.php';

// Kiểm tra tham số trả về từ VNPay
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
$vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
$vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
$vnp_Amount = ($_GET['vnp_Amount'] ?? 0) / 100; // Chia 100 để lấy số tiền gốc
$vnp_OrderInfo = $_GET['vnp_OrderInfo'] ?? '';

// Kiểm tra dữ liệu tạm
if (!isset($_SESSION['vnpay_pending']) || $_SESSION['vnpay_pending']['txn_ref'] != $vnp_TxnRef) {
    $_SESSION['error'] = "Dữ liệu giao dịch không hợp lệ.";
    unset($_SESSION['vnpay_pending']);
    header("Location: cart.php");
    exit();
}

// Tạo danh sách tham số để kiểm tra chữ ký
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}
unset($inputData['vnp_SecureHash']);
ksort($inputData);
$hashData = "";
$i = 0;
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}
$secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);

// Debug: Ghi log callback
error_log("VNPay callback data: " . print_r($_GET, true));
error_log("Generated secure hash: " . $secureHash);
error_log("Received secure hash: " . $vnp_SecureHash);

// Kiểm tra chữ ký
if ($secureHash !== $vnp_SecureHash) {
    $_SESSION['error'] = "Chữ ký không hợp lệ từ VNPay.";
    unset($_SESSION['vnpay_pending']);
    header("Location: cart.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    if ($vnp_ResponseCode == '00') {
        // Thanh toán thành công: Lưu đơn hàng
        $conn->beginTransaction();

        $pending = $_SESSION['vnpay_pending'];
        $cart_items = $pending['cart_items'];
        $total = $pending['total'];
        $shipping_address = $pending['shipping_address'];
        $notes = $pending['notes'];
        $user_id = $pending['user_id'];

        // Tạo đơn hàng
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address, notes, payment_method) 
                               VALUES (?, ?, 'PENDING', ?, ?, 'VNPAY')");
        $stmt->execute([$user_id, $total, $shipping_address, $notes]);
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
        $stmt->execute([$user_id]);

        // Tạo bản ghi thanh toán
        $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, status, transaction_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, 'VNPAY', 'COMPLETED', $vnp_TransactionNo]);

        $conn->commit();

        // Thiết lập thông báo
        $_SESSION['success'] = "Thanh toán VNPay thành công! Mã giao dịch: $vnp_TransactionNo";

        // Debug: Ghi log
        error_log("VNPay success: Order ID $order_id, Transaction ID: $vnp_TransactionNo");

        // Xóa dữ liệu tạm
        unset($_SESSION['vnpay_pending']);

        // Chuyển hướng
        header("Location: order-confirmation.php?id=" . $order_id);
        exit();
    } else {
        // Thanh toán thất bại hoặc hủy: Không lưu đơn hàng
        $error_message = ($vnp_ResponseCode == '24') ? "Thanh toán bị hủy bởi người dùng." : "Thanh toán thất bại. Mã lỗi: $vnp_ResponseCode";
        $_SESSION['error'] = $error_message;

        // Debug: Ghi log
        error_log("VNPay failed: Response code $vnp_ResponseCode, TxnRef: $vnp_TxnRef");

        // Xóa dữ liệu tạm
        unset($_SESSION['vnpay_pending']);

        // Chuyển hướng về giỏ hàng
        header("Location: cart.php");
        exit();
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['error'] = "Lỗi khi xử lý thanh toán: " . $e->getMessage();
    error_log("VNPay return error: " . $e->getMessage());
    unset($_SESSION['vnpay_pending']);
    header("Location: cart.php");
    exit();
}
?>