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

// Xử lý thêm danh mục
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $name = $_POST['name'] ?? '';

    if (empty($name)) {
        $error = 'Vui lòng nhập tên danh mục!';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = 'Thêm danh mục thành công!';
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Xử lý xóa danh mục
if (isset($_POST['delete']) && isset($_POST['category_id'])) {
    $category_id = $_POST['category_id'];
    
    // Kiểm tra xem danh mục có sản phẩm không
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $product_count = $stmt->fetchColumn();
    
    if ($product_count > 0) {
        $error = 'Không thể xóa danh mục này vì đã có sản phẩm!';
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $success = 'Xóa danh mục thành công!';
        } catch (PDOException $e) {
            $error = 'Lỗi: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách danh mục
$stmt = $conn->query("SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id 
                     GROUP BY c.id 
                     ORDER BY c.name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - MyStore</title>
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
                    <h1 class="h2">Quản lý danh mục</h1>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <!-- Form thêm danh mục -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Thêm danh mục mới</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Tên danh mục</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="add" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Thêm danh mục
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách danh mục -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Danh sách danh mục</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên danh mục</th>
                                        <th>Số sản phẩm</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($categories as $category): ?>
                                        <tr>
                                            <td><?php echo $category['id']; ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo $category['product_count']; ?></td>
                                            <td>
    <a href="category-edit.php?id=<?php echo $category['id']; ?>" 
       class="btn btn-sm btn-primary">
        <i class="fas fa-edit"></i>
    </a>

    <form method="POST" style="display:inline;" 
          onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
        <button type="submit" name="delete" class="btn btn-sm btn-danger">
            <i class="fas fa-trash-alt"></i>
        </button>
    </form>
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
</body>
</html>
