<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu chưa đăng nhập thì đá về trang login ngay
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php?tab=login");
    exit();
}

// Kết nối database (Thay đổi đường dẫn file config của ông nếu cần)
require_once __DIR__ . '/config/db_connect.php'; 

try {
    // Lấy thông tin mới nhất của User từ Database
    $sql = "SELECT username, email, role, created_at FROM users WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "Người dùng không tồn tại!";
        exit();
    }
} catch (PDOException $e) {
    echo "Lỗi hệ thống database: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang cá nhân - <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .profile-card {
            background-color: #1a1a1a;
            border: 1px solid #2b2b2b;
            border-radius: 16px;
        }
        .text-pink {
            color: #ff69b4 !important;
        }
        .btn-pink {
            background-color: #ff69b4;
            color: white;
            border: none;
        }
        .btn-pink:hover {
            background-color: #ff1493;
            color: white;
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="profile-card p-4 shadow">
                <div class="text-center mb-4">
                    <div class="display-1 text-pink"><i class="fa-solid fa-circle-user"></i></div>
                    <h3 class="mt-2 fw-bold"><?php echo htmlspecialchars($user['username']); ?></h3>
                    <span class="badge bg-secondary"><?php echo strtoupper(htmlspecialchars($user['role'] ?? 'USER')); ?></span>
                </div>
                
                <hr class="border-secondary">

                <div class="mb-3">
                    <label class="text-white-50 small d-block">Địa chỉ Email</label>
                    <span class="fs-5 fw-medium"><i class="fa-solid fa-envelope me-2 text-pink"></i><?php echo htmlspecialchars($user['email']); ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-white-50 small d-block">Tên đăng nhập</label>
                    <span class="fs-5 fw-medium"><i class="fa-solid fa-user me-2 text-pink"></i><?php echo htmlspecialchars($user['username']); ?></span>
                </div>

                <div class="mb-4">
                    <label class="text-white-50 small d-block">Ngày tham gia</label>
                    <span class="fs-6 text-white-50"><i class="fa-solid fa-calendar-days me-2"></i><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></span>
                </div>

                <div class="d-grid gap-2">
                    <a href="index.php" class="btn btn-outline-light"><i class="fa-solid fa-house me-2"></i>Quay lại Trang chủ</a>
                    <a href="logout.php" class="btn btn-danger"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>