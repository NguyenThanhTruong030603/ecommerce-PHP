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

// Xử lý lấy dữ liệu theo khoảng thời gian (nếu có)
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
$where_clause = "";

// Nếu có ngày bắt đầu và kết thúc, thêm điều kiện vào câu truy vấn
if ($start_date && $end_date) {
    $where_clause = "AND DATE(created_at) BETWEEN :start_date AND :end_date";
} elseif ($start_date) {
    $where_clause = "AND DATE(created_at) >= :start_date";
} elseif ($end_date) {
    $where_clause = "AND DATE(created_at) <= :end_date";
}

// Truy vấn doanh thu theo ngày
$revenueQuery = "
    SELECT DATE(created_at) AS date, SUM(total_price) AS revenue
    FROM orders
    WHERE status = 'DELIVERED' 
    $where_clause
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
";

// Chuẩn bị câu lệnh
$stmt = $conn->prepare($revenueQuery);

// Gắn tham số nếu có
if ($start_date && $end_date) {
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
} elseif ($start_date) {
    $stmt->bindParam(':start_date', $start_date);
} elseif ($end_date) {
    $stmt->bindParam(':end_date', $end_date);
}

$stmt->execute();
$revenues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra dữ liệu trả về
if (!$revenues) {
    $revenues = [];
}

// Lấy danh sách đơn hàng để hiển thị
$query = "SELECT o.*, u.username as user_name, u.email as user_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE o.status = 'DELIVERED' 
          ORDER BY o.created_at DESC";
$stmt = $conn->query($query);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê doanh thu - MyStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Thêm Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../app/views/adminHeader.php'; ?>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Thống kê doanh thu</h1>
                </div>

                <!-- Form lọc ngày -->
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <label for="start_date" class="form-label">Từ ngày</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                        </div>
                        <div class="col-md-5">
                            <label for="end_date" class="form-label">Đến ngày</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="visibility: hidden;">Tìm kiếm</label>
                            <button type="submit" class="btn btn-primary form-control">Tìm kiếm</button>
                        </div>
                    </div>
                </form>

                <!-- Hiển thị thông báo -->
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5>Doanh thu theo ngày</h5>

                        <!-- Biểu đồ doanh thu -->
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5>Danh sách đơn hàng đã giao</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã đơn hàng</th>
                                        <th>Khách hàng</th>
                                        <th>Email</th>
                                        <th>Tổng tiền</th>
                                        <th>Ngày đặt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                                            <td><?php echo number_format($order['total_price'], 0, ',', '.'); ?>đ</td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
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

    <!-- JavaScript để tạo biểu đồ -->
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');

        // Kiểm tra xem có dữ liệu hay không
        const revenueData = <?php echo json_encode($revenues); ?>;
        if (!revenueData || revenueData.length === 0) {
            console.log('Không có dữ liệu doanh thu');
        }

        // Lấy nhãn (ngày) và dữ liệu doanh thu từ PHP
        const labels = revenueData.map(item => item.date);
        const data = revenueData.map(item => item.revenue);

        const revenueChart = new Chart(ctx, {
            type: 'line', // Bạn có thể thay đổi kiểu biểu đồ (line, bar, pie...)
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: data,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Biểu đồ doanh thu theo ngày'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.raw.toLocaleString() + ' VNĐ';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Ngày'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Doanh thu'
                        },
                        ticks: {
                            beginAtZero: true
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
