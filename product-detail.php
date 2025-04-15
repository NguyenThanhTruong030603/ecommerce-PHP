<?php
session_start();
include 'app/config/database.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.id = ?");
$stmt->execute([$_GET['id']]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: products.php");
    exit();
}

// Xử lý thêm sản phẩm vào giỏ hàng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['quantity']) && isset($_SESSION['user_id'])) {
    $quantity = $_POST['quantity'];
    $user_id = $_SESSION['user_id'];
    $product_id = $product['id'];

    // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
    $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cart_item = $stmt->fetch();

    if ($cart_item) {
        // Cập nhật số lượng
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$new_quantity, $user_id, $product_id]);
    } else {
        // Thêm mới vào giỏ hàng
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }

    $_SESSION['success'] = "Đã thêm sản phẩm vào giỏ hàng!";
    header("Location: cart.php");
    exit();
}

// Xử lý bình luận
// Thêm bình luận
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment']) && isset($_SESSION['user_id'])) {
    $content = trim($_POST['comment']);
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO comments (user_id, product_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $product['id'], $content]);
        header("Location: product-detail.php?id=" . $product['id']);
        exit();
    }
}

// Xóa bình luận
if (isset($_GET['delete_comment']) && isset($_SESSION['user_id'])) {
    $comment_id = $_GET['delete_comment'];
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $_SESSION['user_id']]);
    header("Location: product-detail.php?id=" . $product['id']);
    exit();
}

// Sửa bình luận
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_comment_id']) && isset($_SESSION['user_id'])) {
    $comment_id = $_POST['edit_comment_id'];
    $edited_content = trim($_POST['edited_content']);
    $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$edited_content, $comment_id, $_SESSION['user_id']]);
    header("Location: product-detail.php?id=" . $product['id']);
    exit();
}

// Lấy bình luận
$stmt = $conn->prepare("SELECT comments.*, users.username 
                        FROM comments 
                        JOIN users ON comments.user_id = users.id 
                        WHERE product_id = ? 
                        ORDER BY created_at DESC");
$stmt->execute([$product['id']]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<?php include './app/views/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-6">
            <img src="./uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                 class="img-fluid rounded" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="col-md-6">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="text-muted">Danh mục: <?php echo htmlspecialchars($product['category_name']); ?></p>
            <h2 class="text-primary mb-4"><?php echo number_format($product['price'], 0, ',', '.'); ?> đ</h2>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

            <?php if(isset($_SESSION['user_id'])): ?>
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Số lượng</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" 
                               value="1" min="1" max="<?php echo $product['stock']; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-info">
                    Vui lòng <a href="login.php">đăng nhập</a> để thêm sản phẩm vào giỏ hàng.
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <h4>Thông tin sản phẩm</h4>
                <ul class="list-unstyled">
                    <li><strong>Mã sản phẩm:</strong> <?php echo $product['id']; ?></li>
                    <li><strong>Còn lại:</strong> <?php echo $product['stock']; ?> sản phẩm</li>
                    <li><strong>Ngày thêm:</strong> <?php echo date('d/m/Y', strtotime($product['created_at'])); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Bình luận -->
    <div class="mt-5">
        <h4>Bình luận</h4>

        <?php if (isset($_SESSION['user_id'])): ?>
            <form method="POST" class="mb-3">
                <div class="mb-3">
                    <textarea name="comment" class="form-control" rows="3" placeholder="Viết bình luận..." required></textarea>
                </div>
                <button type="submit" class="btn btn-secondary">Gửi bình luận</button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">Vui lòng <a href="login.php">đăng nhập</a> để bình luận.</div>
        <?php endif; ?>

        <?php foreach ($comments as $c): ?>
            <div class="border p-3 mb-3 bg-light rounded">
                <strong><?php echo htmlspecialchars($c['username']); ?></strong>
                <small class="text-muted"> - <?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></small>
                <p class="mb-1"><?php echo nl2br(htmlspecialchars($c['content'])); ?></p>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $c['user_id']): ?>
                    <form method="POST" class="mb-2">
                        <input type="hidden" name="edit_comment_id" value="<?php echo $c['id']; ?>">
                        <textarea name="edited_content" class="form-control mb-2" rows="2"><?php echo htmlspecialchars($c['content']); ?></textarea>
                        <button type="submit" class="btn btn-sm btn-warning">Cập nhật</button>
                        <a href="?id=<?php echo $product['id']; ?>&delete_comment=<?php echo $c['id']; ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Bạn có chắc muốn xóa bình luận này?');">Xóa</a>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include './app/views/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
