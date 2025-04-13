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

// Lấy danh sách danh mục
$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $description = $_POST['description'] ?? '';

    // Validate dữ liệu
    if (empty($name) || empty($category_id) || empty($price) || empty($stock)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } else {
        // Xử lý upload ảnh
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];  // Thêm 'webp' vào danh sách cho phép
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Kiểm tra định dạng file
            if (in_array($ext, $allowed)) {
                // Tạo tên file duy nhất
                $new_filename = uniqid() . '.' . $ext;
                $upload_dir = '../uploads/products/'; // Đảm bảo thư mục này tồn tại
                $upload_path = $upload_dir . $new_filename;

                // Tạo thư mục nếu chưa tồn tại
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true); // Tạo thư mục với quyền truy cập đầy đủ
                }

                // Di chuyển file đến thư mục uploads
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image = $new_filename;
                } else {
                    $error = 'Lỗi khi tải lên hình ảnh!';
                }
            } else {
                $error = 'Chỉ chấp nhận các định dạng ảnh: jpg, jpeg, png, gif, webp';
            }
        }

        // Thêm sản phẩm vào cơ sở dữ liệu
        if (empty($error)) {
            try {
                $stmt = $conn->prepare("INSERT INTO products (name, category_id, price, stock, description, image) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $category_id, $price, $stock, $description, $image]);

                $success = 'Thêm sản phẩm thành công!';
            } catch (PDOException $e) {
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm mới - MyStore</title>
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
                    <h1 class="h2">Thêm sản phẩm mới</h1>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <a href="products.php" class="alert-link">Xem danh sách sản phẩm</a>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Tên sản phẩm</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Danh mục</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Chọn danh mục</option>
                                            <?php foreach($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="price" class="form-label">Giá</label>
                                        <input type="number" class="form-control" id="price" name="price" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">Số lượng trong kho</label>
                                        <input type="number" class="form-control" id="stock" name="stock" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="image" class="form-label">Hình ảnh</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <div class="form-text">Chỉ chấp nhận file ảnh (jpg, jpeg, png, gif, webp)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Mô tả</label>
                                <textarea class="form-control" id="description" name="description" rows="5"></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Lưu sản phẩm
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
