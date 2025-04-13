<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra người dùng đã đăng nhập chưa
$loggedIn = isset($_SESSION['user_id']);
$user_name = $loggedIn ? $_SESSION['username'] : null;

// Đường dẫn đến file tin nhắn
$file_path = 'messages.json';

// Tạo file nếu chưa tồn tại
if (!file_exists($file_path)) {
    file_put_contents($file_path, json_encode([]));
}

// Lấy các tin nhắn hiện có
$messages = json_decode(file_get_contents($file_path), true);

// Nếu có yêu cầu POST gửi tin nhắn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    if (!$loggedIn) {
        echo json_encode(['error' => 'Bạn phải đăng nhập để gửi tin nhắn.']);
        exit();
    }

    $message = htmlspecialchars($_POST['message']);
    $new_message = [
        'username' => $user_name,
        'message' => $message,
        'time' => date('H:i:s d/m/Y')
    ];

    $messages[] = $new_message;
    file_put_contents($file_path, json_encode($messages));

    echo json_encode($new_message);
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat với mọi người</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <style>
        #chat-button {
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

        #chat-button:hover {
            background-color: #0056b3;
        }

        #chat-box {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 300px;
            height: 400px;
            border: 2px solid #007bff;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 999;
        }

        .chat-box {
            max-height: 300px;
            overflow-y: scroll;
            padding: 10px;
        }

        .chat-input {
            padding: 10px;
            background-color: #f1f1f1;
            border-top: 1px solid #ccc;
        }

        .chat-input input {
            width: calc(100% - 60px);
            padding: 5px;
            border: none;
            border-radius: 5px;
        }

        .chat-input button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .message {
            margin-bottom: 10px;
        }

        .message small {
            color: #888;
        }
    </style>
</head>
<body>

<!-- Nút bong bóng chat -->
<div id="chat-button">
    <i class="fas fa-comment"></i>
</div>

<!-- Khung chat -->
<div id="chat-box">
    <div class="chat-box" id="chat-box-content">
        <!-- Tin nhắn sẽ được tải tại đây -->
    </div>
    <div class="chat-input">
        <form id="chat-form">
            <input type="text" id="message" placeholder="Nhập tin nhắn..." required />
            <button type="submit">Gửi</button>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#chat-button').click(function() {
            $('#chat-box').toggle();
        });

        function loadMessages() {
            $.ajax({
                url: 'messages.json',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#chat-box-content').empty();
                    data.forEach(function(message) {
                        $('#chat-box-content').append(
                            '<div class="message"><strong>' + message.username +
                            ':</strong> ' + message.message +
                            '<small class="text-muted"> ' + message.time + '</small></div>'
                        );
                    });
                    $('#chat-box-content').scrollTop($('#chat-box-content')[0].scrollHeight);
                }
            });
        }

        $('#chat-form').on('submit', function(e) {
            e.preventDefault();

            var message = $('#message').val();

            $.ajax({
                url: 'chat.php',
                method: 'POST',
                data: { message: message },
                success: function(response) {
                    var res = JSON.parse(response);
                    if (res.error) {
                        alert(res.error); // hoặc hiển thị bằng modal đẹp hơn
                    } else {
                        $('#chat-box-content').append(
                            '<div class="message"><strong>' + res.username +
                            ':</strong> ' + res.message +
                            '<small class="text-muted"> ' + res.time + '</small></div>'
                        );
                        $('#message').val('');
                        $('#chat-box-content').scrollTop($('#chat-box-content')[0].scrollHeight);
                    }
                }
            });
        });

        loadMessages();
        setInterval(loadMessages, 5000);
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
