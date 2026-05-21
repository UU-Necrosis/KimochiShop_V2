<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db_connect.php';
require_once 'vendor/autoload.php';

$error_msg = "";
$success_msg = "";
$email = $_SESSION['verify_email'] ?? '';
$username = $_SESSION['verify_username'] ?? 'Thành viên';
$otp_code = $_SESSION['verify_otp'] ?? '';
$action = $_SESSION['verify_action'] ?? 'register';

if (empty($email)) {
    header("Location: auth.php");
    exit();
}

// ==========================================================================
// 1. CHẶN ĐẦU: LOGIC GỬI EMAIL NGẦM KHI GIAO DIỆN HIỂN THỊ
// ==========================================================================
if (isset($_SESSION['need_send_mail']) && $_SESSION['need_send_mail'] === true) {
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($mail->Username, $_ENV['SHOP_NAME'] ?? 'Kimochi Shop');
        $mail->addAddress($email);

        $mail->isHTML(true);
        
        if ($action === 'forgot_password') {
            $mail->Subject = '🔑 Reset lại mật khẩu ' . ($_ENV['SHOP_NAME'] ?? 'Kimochi Shop');
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #ff69b4; padding: 25px; border-radius: 12px; background-color: #1e1f22; color: #f2f3f5;'>
                    <h2 style='color: #ff69b4; text-align: center;'>Kimochi Shop</h2>
                    <p style='color: #dbdee1;'>Bạn đã yêu cầu đặt lại mật khẩu. Mã OTP xác nhận của bạn là:</p>
                    <div style='text-align: center; margin: 30px 0; background-color: #111214; padding: 15px; border-radius: 6px; border: 1px dashed #ff69b4;'>
                        <span style='font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #ffffff;'>$otp_code</span>
                    </div>
                </div>";
        } else {
            $verify_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?otp=" . $otp_code;
            $mail->Subject = '🔑 Mã xác thực tài khoản Kimochi Shop';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #ff69b4; padding: 25px; border-radius: 12px; background-color: #1e1f22; color: #f2f3f5;'>
                    <h2 style='color: #ff69b4; text-align: center;'>Kimochi Shop V2</h2>
                    <p style='color: #dbdee1;'>Xin chào <strong>$username</strong>, mã OTP kích hoạt tài khoản của bạn là:</p>
                    <div style='text-align: center; margin: 25px 0; background-color: #111214; padding: 15px; border-radius: 6px; border: 1px dashed #ff69b4;'>
                        <span style='font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #ffffff;'>$otp_code</span>
                    </div>
                    <p style='color: #dbdee1; text-align: center;'>Hoặc bạn nhấp trực tiếp vào link kích hoạt nhanh này:</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <a href='$verify_link' style='background-color: #248046; color: white; padding: 12px 30px; text-decoration: none; font-weight: bold; border-radius: 4px; display: inline-block;'>XÁC NHẬN MÃ OTP</a>
                    </div>
                </div>";
        }

        $mail->send();
        
        // Tắt cờ gửi mail ngay lập tức để tránh F5 bị gửi lặp
        unset($_SESSION['need_send_mail']);
        unset($_SESSION['verify_otp']); 

    } catch (\Exception $e) {
        $error_msg = "Tài khoản đã tạo nhưng lỗi gửi Email: " . $e->getMessage();
        unset($_SESSION['need_send_mail']);
    }
}

// ==========================================================================
// 2. LOGIC KIỂM TRA ĐỐI CHIẾU MÃ OTP KHI USER NHẬP HOẶC CLICK LINK
// ==========================================================================
function process_verification($input_otp, $email, $action, $conn) {
    global $error_msg;
    try {
        $sql = "SELECT * FROM users WHERE email = :email AND verification_code = :otp";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':email' => $email, ':otp' => $input_otp]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($action === 'forgot_password') {
                // Nếu là quên mật khẩu, chuyển sang trang đổi mật khẩu mới (ông viết sau)
                $_SESSION['can_reset_password'] = true;
                header("Location: reset_password.php"); 
                exit();
            } else {
                // Nếu là đăng ký tài khoản mới -> Kích hoạt luôn
                $update = $conn->prepare("UPDATE users SET is_verified = TRUE, verification_code = NULL WHERE id = :id");
                $update->execute([':id' => $user['id']]);

                // Tạo session đăng nhập tự động luôn
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'customer';

                unset($_SESSION['verify_email']);
                unset($_SESSION['verify_username']);
                unset($_SESSION['verify_action']);

                header("Location: index.php");
                exit();
            }
        } else {
            $error_msg = "Mã xác thực OTP không chính xác hoặc đã hết hạn!";
        }
    } catch (PDOException $e) {
        $error_msg = "Lỗi hệ thống: " . $e->getMessage();
    }
}

// Kiểm tra xem có nhận mã tự động từ URL không
if (isset($_GET['otp'])) {
    process_verification(trim($_GET['otp']), $email, $action, $conn);
}

// Kiểm tra khi ấn nút Submit trên form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn-verify'])) {
    process_verification(trim($_POST['otp_code']), $email, $action, $conn);
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