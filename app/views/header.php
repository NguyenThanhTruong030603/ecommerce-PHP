<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShoeStore - Cửa hàng giày dép uy tín</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Nút chính để chọn chat */
        #main-chat-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 30px;
            cursor: pointer;
            z-index: 1000;
        }

        #main-chat-button:hover {
            background-color: #0056b3;
        }

        /* Các nút chat ẩn ban đầu */
        .chat-option {
            position: fixed;
            bottom: 100px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color: #28a745;
            color: white;
            border-radius: 50%;
            display: none; /* Ẩn nút chat ban đầu */
            justify-content: center;
            align-items: center;
            font-size: 20px;
            cursor: pointer;
            z-index: 999;
        }

        .chat-option:hover {
            background-color: #218838;
        }

        /* Các nút chat khác nhau */
        #chat-button-dialogflow {
            bottom: 160px;
            background-color: #f39c12;
        }

        #chat-button-other {
            bottom: 240px;
            background-color: #e74c3c;
        }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">MyStore</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">Sản phẩm</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="categories.php">Danh mục</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Giỏ hàng
                    </a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i> Tài khoản
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Đăng xuất</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Đăng ký</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/webbanhang/chat.php'); ?>
<script src="https://www.gstatic.com/dialogflow-console/fast/messenger/bootstrap.js?v=1"></script>
<df-messenger
  intent="WELCOME"
  chat-title="CHAT-HO-TRO"
  agent-id="a00b221f-481a-47c3-acb0-1d96f129d406"
  language-code="en"
></df-messenger>