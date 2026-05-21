<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Gọi file kết nối CSDL PostgreSQL (Nơi đã có hàm loadEnv() tự chế của ông giáo)
require_once 'config/db_connect.php'; 
require_once 'vendor/autoload.php'; // Gọi PHPMailer ở đây để phục vụ gửi mail công việc

$error = '';

$error_msg = "";
$email = $_SESSION['verify_email'] ?? '';
$username = $_SESSION['verify_username'] ?? 'Thành viên';
$otp_code = $_SESSION['verify_otp'] ?? '';
$action = $_SESSION['verify_action'] ?? 'register';

if (empty($email)) {
    header("Location: auth.php");
    exit();
}

// ==========================================================================
// LOGIC GỬI EMAIL NGẦM KHI GIAO DIỆN ĐÃ LOAD XONG
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
        $mail->addAddress($email, $username);

        $verify_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?otp=" . $otp_code;

        $mail->isHTML(true);
        $mail->Subject = '🔑 Mã xác thực tài khoản Kimochi Shop';
        $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #f0f0f0; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);'>
                    <h2 style='color: white; text-align: center; font-size: 26px; margin-bottom: 5px;'>Kimochi <span style='color:pink'>Shop</span></h2>
                    <p style='color: #555; font-size: 14px;'>Xin chào <strong>$username</strong>,</p>
                    <p style='color: #555; font-size: 14px;'>Cảm ơn bạn vì đã tin tưởng và lựa chọn Kimochi Shop. Mã xác thực tài khoản của bạn là: <strong>$otp_code</strong></p>
                    <p style='color: #dbdee1; font-size: 14px; text-align: center;'>Hoặc bạn có thể click trực tiếp vào nút bên dưới để kích hoạt nhanh tài khoản:</p>
                    <div style='text-align: center; margin: 35px 0;'>
                        <a href='$verify_link' style='font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #222; background: #fff5f8; padding: 12px 25px; border-radius: 8px; border: 2px dashed #c0c0c0; display: inline-block;'>
                            <p style='margin: 0;'>
                                Xác nhận Mã OTP
                            </p>
                        </a>
                    </div>
                    <p style='font-size: 12px; color: #999; text-align: center;'>
                        Nếu bạn không yêu cầu đăng ký, vui lòng bỏ qua email này. Mã này có hiệu lực trong vòng 15 phút. Tuyệt đối không chia sẻ mã này cho ai.
                    </p>
                </div>
            ";

        $mail->send();
        
        // Gửi xong thì tắt cờ này đi để khi F5 trang verify nó không bị gửi lại mail liên tục
        unset($_SESSION['need_send_mail']);
        unset($_SESSION['verify_otp']); // Xóa OTP tạm trong session luôn cho bảo mật

    } catch (\Exception $e) {
        // Ghi nhận lỗi nếu cấu hình SMTP sai nhưng vẫn cho ở lại trang để dùng
        $error_msg = "Hệ thống đã tạo tài khoản, nhưng không thể gửi email: " . $e->getMessage();
        unset($_SESSION['need_send_mail']);
    }
}

if (isset($_GET['otp'])) {
    process_verification(trim($_GET['otp']), $email, $conn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn-verify'])) {
    process_verification(trim($_POST['otp_code']), $email, $conn);
}

// Nếu không có session chứng tỏ chưa qua bước đăng ký hoặc đăng nhập thất bại, đá về trang auth luôn
if (!isset($_SESSION['verify_email'])) {
    echo "<script>
        alert('Phiên làm việc đã hết hạn hoặc bạn chưa đăng ký! Vui lòng đăng ký lại.');
        window.location.href='auth.php?tab=register';
    </script>";
    exit();
}

if (isset($_POST['btn-verify'])) {
    $otp_input = trim($_POST['otp_code'] ?? ''); // Nhớ check kỹ tên name ở form HTML là 'otp' hay 'otp_code' nha ông
    $email = $_SESSION['verify_email'] ?? '';

    if (empty($otp_input)) {
        $error = "Vui lòng nhập đủ 6 số xác thực!";
    } else {
        try {
            // Tìm user dựa vào Email trong Session và mã OTP nhập vào
            $sql = "SELECT * FROM users WHERE email = :email AND verification_code = :otp";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':email' => $email, ':otp' => $otp_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // =============== TRƯỜNG HỢP 1: KHỚP MÃ OTP (THÀNH CÔNG) ===============
                unset($_SESSION['otp_attempts']); 

                // Kích hoạt tài khoản lên TRUE
                $update_sql = "UPDATE users SET is_verified = TRUE, verification_code = NULL WHERE email = :email";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->execute([':email' => $email]);

                unset($_SESSION['verify_email']);
                unset($_SESSION['verify_action']);

                // Đẩy sang trang Login kèm thông báo
                header("Location: auth.php?tab=login&success=verified");
                exit();

            } else {
                // =============== TRƯỜNG HỢP 2: SAI MÃ OTP (THẤT BẠI) ===============
                $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;

                if ($_SESSION['otp_attempts'] >= 3) {
                    unset($_SESSION['verify_email']);
                    unset($_SESSION['otp_attempts']);
                    echo "<script>
                        alert('Bạn đã nhập sai OTP quá 3 lần! Phiên làm việc đã bị hủy, vui lòng đăng ký lại.');
                        window.location.href='auth.php?tab=register';
                    </script>";
                    exit();
                }

                $remaining = 3 - $_SESSION['otp_attempts'];
                $error = "Mã OTP không chính xác! Bạn còn $remaining lần thử.";
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