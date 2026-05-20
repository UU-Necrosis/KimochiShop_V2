<?php
if (session_status() == PHP_SESSION_NONE) { 
    session_start();
}

// Gọi file kết nối CSDL PostgreSQL (Nơi đã có hàm loadEnv() tự chế của ông giáo)
require_once 'config/db_connect.php'; 

$error = '';

// Nếu không có session chứng tỏ chưa qua bước đăng ký hoặc đăng nhập thất bại, đá về trang auth luôn
if (!isset($_SESSION['verify_email'])) {
    echo "<script>
        alert('Phiên làm việc đã hết hạn hoặc bạn chưa đăng ký! Vui lòng đăng ký lại.');
        window.location.href='auth.php?tab=register';
    </script>";
    exit();
}

if (isset($_POST['btn-verify'])) {
    $otp_input = trim($_POST['otp_code'] ?? '');
    $email = $_SESSION['verify_email'];
    if (empty($otp_input)) {
        $error = "Vui lòng nhập đủ 6 số xác thực!";
    } else {
        try {
            // 1. Kiểm tra mã OTP trong PostgreSQL (Dùng biến $conn từ db_connect.php)
            $sql = "SELECT * FROM users WHERE email = :email AND verification_code = :otp";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':email' => $email, 
                ':otp' => $otp_input
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // ... Đoạn code xử lý khi KHỚP MÃ (giữ nguyên như cũ) ...
                unset($_SESSION['otp_attempts']); // Đăng nhập đúng thì xóa số lần đếm đi
            } else {
                // Trường hợp SAI MÃ: Tăng số lần nhập sai lên
                $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;

                if ($_SESSION['otp_attempts'] >= 3) {
                    // Nếu nhập sai quá 3 lần, tự động xóa Session bắt cút về trang đăng ký luôn
                    unset($_SESSION['verify_email']);
                    unset($_SESSION['otp_attempts']);
                    echo "<script>
                        alert('Bạn đã nhập sai OTP quá 3 lần! Hệ thống đã hủy, vui lòng đăng ký lại.');
                        window.location.href='auth.php';
                    </script>";
                } else {
                    // 1. Cập nhật trạng thái xác thực
                    $update_sql = "UPDATE users SET is_verified = TRUE, verification_code = NULL WHERE email = :email";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->execute([':email' => $_SESSION['verify_email']]);

                    // 2. Dọn dẹp session
                    unset($_SESSION['verify_email']);
                    unset($_SESSION['verify_action']); // Thêm dòng này để chắc chắn dọn sạch

                    // 3. CHUYỂN HƯỚNG BẮT BUỘC
                    // Thay vì dùng alert, ông giáo dùng header để đảm bảo nó văng về trang login
                    header("Location: auth.php?tab=login&success=verified");
                    exit(); 
                }

                $remaining = 3 - $_SESSION['otp_attempts'];
                $error = "Mã OTP không chính xác! Bạn còn $remaining lần thử trước khi bị khóa.";
            }
        } catch (PDOException $e) {
            $error = "Lỗi hệ thống database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực tài khoản - Kimochi Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; min-height: 100vh; }
        .btn-pink { background-color: #ff69b4 !important; color: white !important; border: none !important; transition: 0.2s; }
        .btn-pink:hover { background-color: #ff4fa3 !important; opacity: 0.9; }
        .text-pink { color: #ff69b4 !important; }
        .card-verify { max-width: 420px; width: 100%; border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">

    <div class="card p-4 card-verify">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="fa-solid fa-envelope-circle-check fa-3x text-pink"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Xác Thực OTP</h3>
            <p class="text-muted small px-3">Mã xác nhận bảo mật đã được gửi trực tiếp vào hòm thư:<br> <strong class="text-dark"><?php echo htmlspecialchars($_SESSION['verify_email']); ?></strong></p>
        </div>
        
        <form action="verify.php" method="POST">
            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary text-uppercase" style="letter-spacing: 0.5px;">Nhập 6 số xác thực</label>
                <input type="text" name="otp_code" class="form-control text-center fw-bold fs-3 py-2" placeholder="• • • • • •" maxlength="6" required autocomplete="off" style="letter-spacing: 6px; border-radius: 10px;">
                
                <?php if(!empty($error)): ?>
                    <div class="text-danger small mt-2 text-center fw-semibold">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="submit" name="btn-verify" class="btn btn-pink w-100 fw-bold py-2.5 fs-6 shadow-sm mb-3" style="border-radius: 10px;">
                Xác Nhận Tài Khoản
            </button>
            
            <div class="text-center">
                <a href="auth.php" class="text-decoration-none text-secondary small fw-semibold"><i class="fa-solid fa-arrow-left me-1"></i> Quay lại trang chủ</a>
            </div>
        </form>
    </div>

</body>
</html>