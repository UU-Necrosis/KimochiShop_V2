<?php
session_start();
require_once 'config/connect.php'; // Gọi file kết nối CSDL PostgreSQL của ông giáo vào đây

$error = '';

// === ĐOẠN NÀY ĐẶT TRÊN ĐỈNH ĐẦU FILE AUTH.PHP - NƠI XỬ LÝ LOGIC ĐĂNG KÝ ===
session_start();
require_once 'config/db_connect.php'; // Tui thấy file kết nối của ông tên là db_connect.php nè!

$errors = [];

if (isset($_POST['btn-register'])) {
    // 1. Lấy dữ liệu từ form và validate sạch sẽ như cũ
    $username = trim($_POST['reg_username'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    
    // ... Khúc này giữ nguyên các logic check lỗi cũ của ông giáo nha ...

    // 2. Nếu không có lỗi gì thì bắt đầu bùa chú Postgres + Gửi OTP
    if (empty($errors)) {
        try {
            // Tạo mã OTP ngẫu nhiên 6 chữ số
            $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Mã hóa pass

            // Câu lệnh INSERT tài khoản mới vào PostgreSQL (Mặc định is_verified = FALSE)
            $sql = "INSERT INTO users (username, email, password, verification_code, is_verified) 
                    VALUES (:username, :email, :password, :otp, FALSE)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':email'    => $email,
                ':password' => $hashed_password,
                ':otp'      => $otp_code
            ]);

            // Triệu hồi PHPMailer (Composer đã cài sẵn v7.1.1 xịn sò)
            require_once 'vendor/autoload.php';
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Cấu hình máy chủ gửi (Sử dụng SMTP của Gmail)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'email_cua_ong_giao@gmail.com'; // Điền Gmail của ông giáo vào đây
            $mail->Password   = 'abcd efgh ijkl mnop';          // Mật khẩu ứng dụng Gmail 16 ký tự
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Người nhận & Người gửi
            $mail->setFrom($mail->Username, 'Kimochi Shop');
            $mail->addAddress($email);

            // Nội dung thư tri ân trân trọng
            $mail->isHTML(true);
            $mail->Subject = '🔑 Mã xác thực tài khoản Kimochi Shop';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #f0f0f0; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);'>
                    <h2 style='color: #ff69b4; text-align: center; font-size: 26px; margin-bottom: 5px;'>Kimochi Shop</h2>
                    <p style='color: #555; font-size: 14px; line-height: 1.6;'>Chào bạn,</p>
                    <p style='color: #555; font-size: 14px; line-height: 1.6;'>Cảm ơn bạn vì đã tin tưởng và lựa chọn Kimochi Shop! Để hoàn tất đăng ký tài khoản, vui lòng nhập mã xác thực OTP dưới đây:</p>
                    <div style='text-align: center; margin: 35px 0;'>
                        <span style='font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #222; background: #fff5f8; padding: 12px 25px; border-radius: 8px; border: 2px dashed #ff69b4; display: inline-block;'>$otp_code</span>
                    </div>
                    <p style='font-size: 12px; color: #999; text-align: center; margin-top: 30px; border-top: 1px solid #f7f7f7; padding-top: 15px;'>Mã xác thực có hiệu lực trong vòng 15 phút. Vui lòng không chia sẻ mã này với bất kỳ ai.</p>
                </div>
            ";

            $mail->send();

            // Lưu email vào Session để trang verify.php nhận diện
            $_SESSION['verify_email'] = $email;
            
            // Đá bay sang trang nhập OTP
            header("Location: verify.php");
            exit();

        } catch (\Exception $e) {
            $errors['register'] = "Có lỗi xảy ra: " . $e->getMessage();
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
    </style>
</head>
<body class="d-flex align-items-center justify-content-center">

    <div class="card p-4 shadow border-0" style="max-width: 420px; width: 100%; border-radius: 16px;">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-dark mb-1">Xác Thực OTP</h3>
            <p class="text-muted small px-3">Mã xác nhận bảo mật đã được gửi trực tiếp vào hòm thư <strong class="text-dark"><?php echo htmlspecialchars($_SESSION['verify_email']); ?></strong></p>
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
                <a href="auth.php" class="text-decoration-none text-secondary small fw-semibold"><i class="fa-solid fa-arrow-left me-1"></i> Quay lại Đăng ký</a>
            </div>
        </form>
    </div>

</body>
</html>