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
                <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded hover-bg" href="orders.php">
                    <i class="fas fa-shopping-cart fa-fw"></i> <span>Đơn hàng</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded hover-bg" href="coupons.php">
                    <i class="fas fa-shopping-cart fa-fw"></i> <span>Mã giảm giá</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded hover-bg" href="users.php">
                    <i class="fas fa-users fa-fw"></i> <span>Người dùng</span>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link text-white d-flex align-items-center gap-2 py-2 px-3 rounded hover-bg" href="order_stats.php">
                    <i class="fas fa-users fa-fw"></i> <span>Thống kê</span>
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
</style>
