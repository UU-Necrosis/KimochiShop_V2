<?php
// ==================== KHỞI TẠO HỆ THỐNG & KẾT NỐI DATABASE ====================
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db_connect.php'; 
require_once 'vendor/autoload.php'; // Nạp Composer một lần duy nhất ở đây

// Xác định tab giao diện đang hiển thị
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'login';
$errors = [];
$success = "";

// ==================== XỬ LÝ LOGIC ĐĂNG KÝ GỬI OTP ====================

if (isset($_POST['btn-register'])) { 
    // 1. Nhận dữ liệu nhập vào từ Form
    $username = trim($_POST['reg_username'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $password_confirm = $_POST['reg_password_confirm'] ?? '';

    // 2. Validate dữ liệu đầu vào (Giữ nguyên các logic check của ông giáo)
    if (strlen($username) < 5) {
        $errors['username'] = "Tên đăng nhập tối thiểu 5 ký tự!";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Địa chỉ email không đúng định dạng!";
    }
    if ($password !== $password_confirm) {
        $errors['password_confirm'] = "Mật khẩu xác nhận không trùng khớp!";
    }

    // Chặn spam & Check trùng: Kiểm tra Username hoặc Email trong PostgreSQL
    if (empty($errors)) {
        try {
            // 1. Kiểm tra Username trước
            $check_user = $conn->prepare("SELECT id FROM users WHERE username = :username");
            $check_user->execute([':username' => $username]);
            if ($check_user->fetch()) {
                $errors['username'] = "Tên đăng nhập này đã có người sử dụng rồi!";
            }

            // 2. Kiểm tra Email sau
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $check_email->execute([':email' => $email]);
            if ($check_email->fetch()) {
                $errors['email'] = "Địa chỉ Email này đã được đăng ký rồi!";
            }
        } catch (PDOException $e) {
            $errors['register'] = "Lỗi hệ thống kiểm tra dữ liệu: " . $e->getMessage();
        }
    }

    // 3. Tiến hành bùa chú Postgres + Gửi Mail OTP nếu không có lỗi
    if (empty($errors)) {
        try {
            // Tạo mã OTP ngẫu nhiên 6 chữ số
            $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Đẩy dữ liệu vào PostgreSQL ở trạng thái chưa xác thực (FALSE)
            $sql = "INSERT INTO users (username, email, password, verification_code, is_verified, role) 
                    VALUES (:username, :email, :password, :otp, FALSE, 'customer')";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':email'    => $email,
                ':password' => $hashed_password,
                ':otp'      => $otp_code
            ]);
            // Lưu thông tin vào Session để verify.php nhận diện
            $_SESSION['verify_email'] = $email;
            $_SESSION['verify_username'] = $username;
            $_SESSION['verify_otp'] = $otp_code; // Lưu tạm OTP vào session để lát gửi mail
            $_SESSION['verify_action'] = 'register'; 
            $_SESSION['need_send_mail'] = true; // BẬT CỜ: Báo cho trang verify biết cần phải gửi mail

            // Đẩy người dùng sang trang nhập OTP
            header("Location: verify.php");
            exit();
            
            // Sút ngay sang trang verify.php (Mất chưa tới 0.5 giây!)
            header("Location: verify.php");

            // Triệu hồi PHPMailer gửi thư (Dùng mảng $_ENV chuẩn của ông)
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'] ?? '';
            $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
            $mail->CharSet    = 'UTF-8';

            // Người nhận & Người gửi
            $mail->setFrom($mail->Username, $_ENV['SHOP_NAME'] ?? 'Kimochi Shop');
            $mail->addAddress($email, $username);

            // Tạo đường link bấm xác thực nhanh tự động truyền OTP qua URL sang verify.php
            $verify_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?otp=" . $otp_code;

            // Nội dung bức thư gửi OTP dạng HTML đồng bộ giao diện
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


        } catch (\Exception $e) {
            $errors['register'] = "Có lỗi xảy ra trong quá trình xử lý: " . $e->getMessage();
        }
    }
}

// ==================== XỬ LÝ LOGIC ĐĂNG NHẬP POSTGRESQL ====================

if (isset($_POST['btn-login'])) {
    // 1. Dùng trim() triệt để cho cả 2 đầu dữ liệu để loại bỏ dấu cách thừa
    $login_input = isset($_POST['username']) ? trim($_POST['username']) : ''; 
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($login_input) || empty($password)) {
        $errors['login'] = "Vui lòng nhập đầy đủ tên đăng nhập/Email và mật khẩu!";
    }

    if (empty($errors)) {
        try {
            $sql = "SELECT * FROM users WHERE username = :login_input OR email = :login_input";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':login_input' => $login_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                
                // Kiểm tra trạng thái kích hoạt tài khoản linh hoạt hơn
                if ($user['is_verified'] === false || $user['is_verified'] === 'f' || $user['is_verified'] == 0) {
                    $_SESSION['verify_email'] = $user['email'];
                    $_SESSION['verify_action'] = 'register';
                    $errors['login'] = "Tài khoản chưa kích hoạt! <a href='verify.php' class='text-pink fw-bold'>Nhấp vào đây để kích hoạt</a>";
                } else {
                    // ĐĂNG NHẬP THÀNH CÔNG
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'] ?? 'customer';

                    // 2. GIẢI PHÁP AN TOÀN: Dùng JavaScript làm phương án dự phòng nếu header() bị lỗi đã gửi content trước
                    if (!headers_sent()) {
                        header("Location: index.php");
                        exit();
                    } else {
                        echo "<script>window.location.href='index.php';</script>";
                        exit();
                    }
                }
            } else {
                $errors['login'] = "Tên đăng nhập/Email hoặc mật khẩu không chính xác!";
            }
        } catch (PDOException $e) {
            $errors['login'] = "Lỗi hệ thống database: " . $e->getMessage();
        }
    }
}

// ==================== XỬ LÝ LOGIC QUÊN MẬT KHẨU ====================

if (isset($_POST['btn-forgot'])) {
    $email = trim($_POST['forgot_email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['forgot'] = "Vui lòng nhập địa chỉ email hợp lệ!";
    }

    if (empty($errors)) {
        try {
            // 1. Kiểm tra xem Email có tồn tại trong hệ thống PostgreSQL không
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // 2. Nếu có tồn tại, sinh mã OTP mới 6 chữ số
                $otp_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

                // Cập nhật mã OTP này vào cột verification_code của user đó
                $update_sql = "UPDATE users SET verification_code = :otp WHERE email = :email";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->execute([':otp' => $otp_code, ':email' => $email]);

                // 3. Gửi Mail chứa OTP về cho khách
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = $_ENV['SMTP_USER'] ?? '';
                $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom($mail->Username, 'Kimochi Shop');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = ' Reset lại mật khẩu ' . ($_ENV['SHOP_NAME'] ?? 'Kimochi Shop');
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #f0f0f0; padding: 25px; border-radius: 12px;'>
                        <h2 style='color: #ff69b4; text-align: center;'>Kimochi Shop</h2>
                        <p style='color: #555; font-size: 14px;'>Bạn đã yêu cầu đặt lại mật khẩu. Mã OTP xác nhận của bạn là:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <span style='font-size: 28px; font-weight: bold; letter-spacing: 6px; color: #222; background: #fff5f8; padding: 12px 25px; border-radius: 8px; border: 2px dashed #ff69b4; display: inline-block;'>$otp_code</span>
                        </div>
                        <p style='font-size: 12px; color: #999; text-align: center;'>Mã này có hiệu lực trong vòng 15 phút. Nếu không phải bạn yêu cầu, vui lòng bỏ qua email này.</p>
                    </div>
                ";
                $mail->send();

                // Lưu email và gắn thêm một cái "Cờ" (Flag) để phân biệt với Đăng ký
                $_SESSION['verify_email'] = $email;
                $_SESSION['verify_action'] = 'forgot_password'; // Đánh dấu hành động là quên mật khẩu

                // Đá sang trang verify.php
                header("Location: verify.php");
                exit();

            } else {
                $errors['forgot'] = "Địa chỉ email này không tồn tại trên hệ thống!";
            }
        } catch (\Exception $e) {
            $errors['forgot'] = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}

// Hàm lõi xử lý xác thực tài khoản đăng ký
function process_verification($input_otp, $email, $conn) {
    global $error_msg;
    try {
        // Tìm user dựa trên email và mã OTP nhập vào
        $sql = "SELECT * FROM users WHERE email = :email AND verification_code = :otp";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':email' => $email, ':otp' => $input_otp]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Khớp mã! Cập nhật trạng thái kích hoạt và xóa bỏ mã OTP cũ trong DB
            $update = $conn->prepare("UPDATE users SET is_verified = TRUE, verification_code = NULL WHERE id = :id");
            $update->execute([':id' => $user['id']]);

            // Thực hiện tự động tạo phiên đăng nhập trực tiếp
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'customer';

            // Xóa dọn dẹp các session rác dùng lúc xác thực
            unset($_SESSION['verify_email']);
            unset($_SESSION['verify_action']);

            // Sút thẳng về trang chủ theo đúng yêu cầu đồ án
            header("Location: index.php");
            exit();
        } else {
            $error_msg = "Mã xác thực OTP không chính xác hoặc đã hết hạn!";
        }
    } catch (PDOException $e) {
        $error_msg = "Lỗi hệ thống: " . $e->getMessage();
    }
}

// TRƯỜNG HỢP 1: Tự động bắt OTP khi người dùng CLICK LINK TỪ EMAIL (?otp=xxxxxx)
if (isset($_GET['otp'])) {
    process_verification(trim($_GET['otp']), $email, $conn);
}

// TRƯỜNG HỢP 2: Người dùng gõ tay vào ô rồi ấn nút kích hoạt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn-verify'])) {
    process_verification(trim($_POST['otp_code']), $email, $conn);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực tài khoản - Kimochi Shop</title>
    
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    
    <style>
        :root {
            --pink-primary: #ff69b4;
            --dark-bg: #1a1d20;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100% !important;
            width: 100% !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--dark-bg);
            overflow: hidden;
        }
        
        .auth-wrapper {
            display: flex !important;
            height: 100vh !important;
            width: 100vw !important;
        }
        
        /* BÊN TRÁI: VÙNG NỀN TRẮNG CHỨA FORM */
        .auth-right {
            flex: 1 !important;
            background-color: #ffffff !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important; 
            padding: 0 5% !important;
            box-shadow: 10px 0 30px rgba(0,0,0,0.25) !important; 
            min-width: 420px; /* Tăng nhẹ min-width giữ độ rộng cho form */
            height: 100vh !important;
            position: relative !important; /* Làm mốc cố định cho header */
        }

        /* CỤM LOGO SÁT NÓC PHẢI + DÒNG HIGHLIGHT HỒNG */
        .auth-header {
            position: absolute !important;
            top: 1.5rem !important;    
            left: 2rem !important;   /* Mở rộng sang bên trái để chứa nút quay lại */
            right: 2rem !important;  /* Ghim sát lề phải */
            display: flex !important;
            justify-content: space-between !important; /* Đẩy nút sang trái, logo sang phải */
            align-items: center !important; /* Căn chỉnh hai bên nằm thẳng hàng ngang */
            z-index: 10;
            
            /* Di chuyển thanh line hồng sang ôm trọn lề phải của Header */
            border-right: 4px solid var(--pink-primary) !important; 
            padding-right: 15px !important; 
        }

        /* Style nút quay lại hình dáng số 0 (Oval/Pill) */
        .back-to-home {
            color: #6c757d !important;
            text-decoration: none !important;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            
            /* Ma thuật biến thành hình số 0 ở đây */
            border: 1.5px solid #e0e2e5 !important; /* Màu viền mặc định nhẹ nhàng */
            border-radius: 50px !important;         /* Bo tròn tuyệt đối hai đầu */
            padding: 6px 16px !important;           /* Căn đều trên dưới và hai bên */
            
            background-color: #ffffff !important;
            transition: all 0.25s ease-in-out;
        }

        /* Hiệu ứng khi lướt chuột qua (Hover) */
        .back-to-home:hover {
            color: var(--pink-primary) !important;
            border-color: var(--pink-primary) !important; /* Viền sáng bừng lên màu hồng */
            background-color: #fff0f6 !important;         /* Nền hồng cực nhẹ tinh tế */
            transform: translateX(-3px);                  /* Nhích nhẹ sang trái tạo cảm giác động */
            box-shadow: 0 4px 12px rgba(255, 105, 180, 0.15) !important; /* Đổ bóng hồng nhẹ */
        }
        
        /* BIẾN ĐỔI FORM MƯỢT MÀ VÀ ÉP RỘNG 100% CHỐNG MÓP FORM */
        .form-container-box {
            width: 100% !important;
            max-width: 100% !important;
            margin-top: 2rem;
        }

        .form-box-fade {
            width: 100% !important;
            opacity: 1;
            transition: opacity 0.25s ease-in-out, transform 0.25s ease-in-out;
            transform: translateY(0);
        }

        .form-box-fade.d-none {
            display: none !important;
            opacity: 0;
            transform: translateY(10px);
        }

        .dynamic-title {
            font-weight: 700;
            letter-spacing: 1px;
            color: #212529;
            margin-bottom: 2rem;
            text-transform: uppercase;
            text-align: center;
        }
        
        /* BÊN PHẢI: VÙNG WELCOME NỀN TỐI */
        .auth-left {
            flex: 2 !important;
            background-color: var(--dark-bg) !important;
            color: #ffffff !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            padding: 0 8% !important;
        }
        
        /* CSS NÚT BẤM VÀ TEXT MÀU HỒNG */
        .btn-pink {
            background-color: var(--pink-primary) !important;
            color: white !important;
            border: none !important;
            transition: all 0.2s ease;
        }
        .btn-pink:hover {
            background-color: #e0529c !important;
            transform: translateY(-1px);
        }
        .text-pink {
            color: var(--pink-primary) !important;
        }
        .text-welcome {
            letter-spacing: 2px; 
            color: #ffffff !important; 
            opacity: 0.15; 
            user-select: none;
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    
    <div class="auth-right">
        
        <div class="auth-header">
            <a href="index.php" class="back-to-home" title="Quay lại trang chủ">
                <i class="fa-solid fa-house text-secondary" style="font-size: 0.95rem;"></i>
                <span class="d-none d-sm-inline small fw-semibold text-secondary">Home Page</span>
            </a>

            <div class="text-end">
                <h4 class="fw-bold text-dark mb-0" style="font-size: 1.35rem; letter-spacing: 0.5px;">
                    Kimochi <span class="text-pink">Shop</span>
                </h4>
            </div>
        </div>
        <div class="form-container-box">

            <?php 
            // Nếu có session success hoặc có tham số success từ URL truyền về
            if (isset($_GET['success']) && $_GET['success'] == 'verified') {
                $success = "Kích hoạt tài khoản thành công! Quý khách vui lòng đăng nhập lại.";
            }
            if (!empty($success)): 
            ?>
                <div class="alert alert-success py-2 small shadow-sm mb-3 text-center">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <div id="login-box" class="form-box-fade <?php echo $tab !== 'login' ? 'd-none' : ''; ?>">
                <h4 class="dynamic-title mb-1">ĐĂNG NHẬP</h4>
                <p class="text-muted small text-center mb-3">Chào mừng bạn quay lại!</p>

                <form action="auth.php" method="POST">
                    <?php if (isset($errors['register'])): ?>
                        <div class="alert alert-danger py-2 small shadow-sm text-center">
                            <?php echo $errors['register']; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Tên đăng nhập hoặc Email</label>
                        <input type="text" name="username" class="form-control py-2" placeholder="Nhập username..." required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary">Mật khẩu</label>
                        <input type="password" name="password" class="form-control py-2" placeholder="Nhập mật khẩu..." required>
                    </div>
                    <div class="text-end mb-4">
                        <a href="auth.php?tab=forgot" class="switch-btn small text-decoration-none text-muted" data-target="forgot" style="font-size: 0.85rem;">Quên mật khẩu?</a>
                    </div>
                    <button type="submit" name="btn-login" class="btn btn-pink w-100 fw-bold py-2 mb-3 shadow-sm">Đăng Nhập</button>
                </form>
                <div class="text-center mt-3">
                    <p class="small text-muted">
                        Chưa có tài khoản? 
                        <a href="auth.php?tab=register" class="switch-btn text-pink fw-bold text-decoration-none transition-link" data-target="register">Đăng ký ngay</a>
                    </p>
                </div>
            </div>

            <div id="register-box" class="form-box-fade <?php echo $tab !== 'register' ? 'd-none' : ''; ?>">
                <h4 class="dynamic-title mb-1">ĐĂNG KÝ TÀI KHOẢN</h4>
                <?php// p class="text-muted small text-center mb-2" Cảm ơn vì đã tin tưởng và lựa chọn Kimochi Shop!/p ?>
                
                <form action="auth.php" method="POST">

                    <div class="mb-4 position-relative"> <label class="form-label small fw-bold text-secondary">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" name="reg_username" class="form-control py-2 <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" placeholder="Tối thiểu 5 ký tự..." value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                        
                        <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback small fw-semibold position-absolute" style="bottom: -20px; left: 0; margin: 0; line-height: 1;">
                                <?php echo $errors['username']; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4 position-relative">
                        <label class="form-label small fw-bold text-secondary">Địa chỉ Email <span class="text-danger">*</span></label>
                        <input type="text" name="reg_email" class="form-control py-2 <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" placeholder="example@gmail.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback small fw-semibold position-absolute" style="bottom: -20px; left: 0; margin: 0; line-height: 1;">
                                <?php echo $errors['email']; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4 position-relative">
                        <label class="form-label small fw-bold text-secondary">Mật khẩu <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="reg_password" name="reg_password" class="form-control py-2" placeholder="Nhập mật khẩu bảo mật..." value="<?php echo htmlspecialchars($password ?? ''); ?>" required style="border-radius:  0.375rem 0 0 0.375rem;">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="reg_password" style="border-radius: 0 0.375rem 0.375rem 0; border-color: #ced4da;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 position-relative">
                        <label class="form-label small fw-bold text-secondary">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="reg_password_confirm" name="reg_password_confirm" class="form-control py-2 <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>" placeholder="Nhập lại mật khẩu..." value="<?php echo htmlspecialchars($password_confirm ?? ''); ?>" required style="border-radius:  0.375rem 0 0 0.375rem;">
                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="reg_password_confirm" style="border-radius: 0 0.375rem 0.375rem 0; border-color: #ced4da;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>

                        <?php if (isset($errors['password_confirm'])): ?>
                            <div class="invalid-feedback small fw-semibold position-absolute" style="bottom: -20px; left: 0; margin: 0; line-height: 1; display: block;">
                                <?php echo $errors['password_confirm']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="btn-register" class="btn btn-pink w-100 fw-bold py-2 mb-1 shadow-sm">Đăng Ký</button>
                </form>
                
                <div class="text-center mt-3">
                    <p class="small text-muted">
                        Đã có tài khoản? 
                        <a href="auth.php?tab=login" class="switch-btn text-pink fw-bold text-decoration-none transition-link" data-target="login">Đăng nhập ngay</a>
                    </p>
                </div>
            </div>

            <div id="forgot-box" class="form-box-fade <?php echo $tab !== 'forgot' ? 'd-none' : ''; ?>">
                <h4 class="dynamic-title mb-1">QUÊN MẬT KHẨU</h4>
                <p class="text-muted small text-center mb-4">Vui lòng nhập Email đã đăng ký để khôi phục mật khẩu.</p>
                <form action="auth.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Địa chỉ Email đăng ký</label>
                        <input type="email" name="forgot_email" class="form-control py-2" placeholder="Nhập email của bạn..." required>
                    </div>
                    <button type="submit" name="btn-forgot" class="btn btn-pink w-100 fw-bold py-2 mb-3 shadow-sm">Gửi Yêu Cầu</button>
                </form>
                <div class="text-center mt-3">
                    <a href="auth.php?tab=login" class="switch-btn small fw-bold text-decoration-none text-pink" data-target="login">
                        <i class="fa-solid fa-arrow-left me-2"></i>Quay lại Đăng nhập
                    </a>
                </div>
            </div>

        </div>
    </div>

    <div class="auth-left">
        <h1 class="display-1 fw-bold mb-2 text-uppercase text-welcome">WELCOME</h1>
        <h2 class="display-4 fw-bold mb-4 text-white">Kimochi <span class="text-pink">Shop</span></h2>
        <h5 class="fw-semibold text-white mb-4" style="opacity: 0.9;">Chào mừng bạn đến với cửa hàng online Việt Nam</h5>
        
        <p class="lead fs-6 lh-lg mb-4" style="max-width: 600px; color: #b0b3b8 !important;">
            Tại Kimochi Shop, chúng tôi cam kết mang đến những sản phẩm chất lượng tốt, an toàn và tinh tế. 
            Chúng tôi tin rằng mọi người đều xứng đáng được tận hưởng những giây phục tuyệt vời và thoải mái.
        </p>
        <p class="lead fs-6 lh-lg" style="max-width: 600px; color: #b0b3b8 !important;">
            Hãy khám phá bộ sưu tập đa dạng của chúng tôi để tìm kiếm những món đồ phù hợp với sở thích của bạn.
        </p>
    </div>

</div>

<script>
// 1. Hàm dùng chung để ẩn/hiện form dựa theo tên Tab (login/register/forgot)
function showForm(tabName) {
    const allTabs = ['login', 'register', 'forgot'];
    
    allTabs.forEach(tab => {
        const element = document.getElementById(`${tab}-box`);
        if (element) {
            if (tab === tabName) {
                element.classList.remove('d-none');
            } else {
                element.classList.add('d-none');
            }
        }
    });
}

// 2. Bắt sự kiện click vào các nút chuyển đổi có class .switch-btn
document.querySelectorAll('.switch-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault(); // Chặn load lại trang
        
        const targetForm = this.getAttribute('data-target'); // Lấy login, register hoặc forgot
        
        if (targetForm) {
            showForm(targetForm); // Gọi hàm đổi giao diện ngay lập tức
            
            // Đổi chữ trên thanh URL ảo
            const newUrl = `auth.php?tab=${targetForm}`;
            window.history.pushState({ path: newUrl }, '', newUrl);
        }
    });
});

// 3. Xử lý khi người dùng bấm nút BACK / FORWARD trên trình duyệt
window.addEventListener('popstate', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('tab') || 'login'; // Mặc định về login nếu không tìm thấy tab trên URL
    
    showForm(currentTab);
});
</script>



<script>
// Hiển thị password khi bấm vào, dựa vào data-target để biết được input nào cần đổi
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        // Lấy ra cái ô input mục tiêu dựa vào thuộc tính data-target
        const targetId = this.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        const icon = this.querySelector('i');
        
        // Thay đổi qua lại giữa 'password' và 'text'
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash'); // Đổi thành icon mắt gạch chéo
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye'); // Đổi về icon mắt mở
        }
    });
});
</script>
</body>
</html>