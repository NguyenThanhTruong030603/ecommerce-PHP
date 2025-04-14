<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar with Dropdown</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar .nav-link {
            transition: all 0.2s ease-in-out;
            font-weight: 500;
        }

        .sidebar .hover-bg:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            background-color: #0d6efd !important;
        }

        .sidebar .nav-link i {
            font-size: 1.1rem;
        }

        .dropdown-menu {
            position: relative;
            left: 0;
            margin: 0;
            padding: 0;
            width: 100%;
            background-color: #343a40;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }

        .dropdown-menu.show {
            max-height: 300px;
        }

        .dropdown-item {
            transition: all 0.2s ease-in-out;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .dropdown-item.active {
            background-color: #0d6efd !important;
        }
    </style>
</head>
<body>
    <nav class="col-md-2 d-none d-md-block bg-dark sidebar min-vh-100">
        <div class="position-sticky pt-3">
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link active text-white d-flex align-items-center gap-2 py-2 px-3 rounded bg-primary" href="index.php">
                        <i class="fas fa-home fa-fw"></i> <span>Trang chủ</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded hover-bg" href="products.php">
                        <i class="fas fa-shoe-prints fa-fw"></i> <span>Sản phẩm</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded hover-bg" href="categories.php">
                        <i class="fas fa-list fa-fw"></i> <span>Danh mục</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded hover-bg dropdown-toggle" href="#" onclick="toggleDropdown(event)" aria-expanded="false" aria-controls="orders-dropdown">
                        <i class="fas fa-shopping-cart fa-fw"></i> <span>Đơn hàng</span>
                    </a>
                    <ul class="dropdown-menu bg-dark border-0" id="orders-dropdown">
                        <li><a class="dropdown-item text-white px-4 py-2 hover-bg" href="orders.php?status=pending">Chờ xử lý</a></li>
                        <li><a class="dropdown-item text-white px-4 py-2 hover-bg" href="orders.php?status=processing">Đang xử lý</a></li>
                        <li><a class="dropdown-item text-white px-4 py-2 hover-bg" href="orders.php?status=shipped">Đã giao hàng</a></li>
                        <li><a class="dropdown-item text-white px-4 py-2 hover-bg" href="orders.php?status=delivered">Đã nhận hàng</a></li>
                        <li><a class="dropdown-item text-white px-4 py-2 hover-bg" href="orders.php?status=canceled">Đã hủy</a></li>
                        <li><a class="dropdown-item text-white px-4 py-2 hover-bg" href="orders.php?status=returned">Đã trả hàng</a></li>
                    </ul>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded hover-bg" href="coupons.php">
                        <i class="fas fa-ticket-alt fa-fw"></i> <span>Mã giảm giá</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded hover-bg" href="users.php">
                        <i class="fas fa-users fa-fw"></i> <span>Người dùng</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded hover-bg" href="order_stats.php">
                        <i class="fas fa-chart-bar fa-fw"></i> <span>Thống kê</span>
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded bg-danger" href="logout.php">
                        <i class="fas fa-sign-out-alt fa-fw"></i> <span>Đăng xuất</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <script>
        function toggleDropdown(event) {
            event.preventDefault();
            const dropdownMenu = event.currentTarget.nextElementSibling;
            const isExpanded = dropdownMenu.classList.toggle('show');
            event.currentTarget.setAttribute('aria-expanded', isExpanded);
        }

        document.addEventListener('click', function(event) {
            const dropdowns = document.querySelectorAll('.dropdown-menu');
            dropdowns.forEach(dropdown => {
                if (!dropdown.previousElementSibling.contains(event.target) && !dropdown.contains(event.target)) {
                    dropdown.classList.remove('show');
                    dropdown.previousElementSibling.setAttribute('aria-expanded', 'false');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const currentUrl = window.location.href;
            const dropdownItems = document.querySelectorAll('.dropdown-item');
            dropdownItems.forEach(item => {
                if (currentUrl.includes(item.getAttribute('href'))) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>