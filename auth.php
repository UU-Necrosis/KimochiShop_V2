<?php
// Kiểm tra xem URL đang yêu cầu tab nào (Mặc định là login nếu không có)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'login';
// 1. Nhúng file kết nối database vào
require_once 'config/db_connect.php';

// Khởi tạo session để lưu trạng thái đăng nhập
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'login';
$errors = [];
$success = "";

// ========================================================
//  XỬ LÝ LOGIC ĐĂNG KÝ TÀI KHOẢN
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn-register'])) {
    $tab = 'register'; // Giữ người dùng ở lại tab register nếu có lỗi hoặc thành công
    
    // Lấy dữ liệu theo đúng thuộc tính name="…" trong file HTML gốc của ông giáo
    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['re_password'] ?? ''; // Fix theo name="re_password"

    // Validate cơ bản
    if (strlen($username) < 5) {
        $errors[] = "Tên đăng nhập phải chứa ít nhất 5 ký tự!";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Địa chỉ email không hợp lệ!";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Mật khẩu xác nhận không trùng khớp!";
    }

    if (empty($errors)) {
        try {
            // Kiểm tra xem username hoặc email đã tồn tại trong database kimochi_shop chưa
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :user OR email = :email");
            $stmt->execute(['user' => $username, 'email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Tên đăng nhập hoặc Email đã được sử dụng!";
            } else {
                //  Băm mật khẩu bảo mật trước khi ném vào DB
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Insert dữ liệu vào bảng users của ông giáo (cột password)
                $insertStmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:user, :email, :pass)");
                $insertStmt->execute([
                    'user'  => $username,
                    'email' => $email,
                    'pass'  => $hashed_password
                ]);

                $success = "Đăng ký tài khoản thành công! Đang chuyển sang Đăng nhập...";
                $tab = 'login'; // Đăng ký xong tự nhảy về form đăng nhập
                
                // Reset form trống
                $username = $email = "";
            }
        } catch (PDOException $e) {
            $errors[] = "Có lỗi xảy ra với hệ thống: " . $e->getMessage();
        }
    }
}

// ========================================================
//  XỬ LÝ LOGIC ĐĂNG NHẬP
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn-login'])) {
    $tab = 'login';
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        // Tìm user theo username
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :user");
        $stmt->execute(['user' => $username]);
        $user = $stmt->fetch();

        // 🛠️ ĐÃ FIX: So sánh mật khẩu băm dựa vào cột $user['password']
        if ($user && password_verify($password, $user['password'])) {
            // Lưu thông tin vào Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Nếu bảng cũ của ông chưa có cột role thì tạm thời comment dòng này lại hoặc để mặc định
            $_SESSION['role'] = $user['role'] ?? 'user'; 

            // Đăng nhập xong, nhảy về trang chủ index.php
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Tên đăng nhập hoặc mật khẩu không chính xác!";
        }
    } catch (PDOException $e) {
        $errors[] = "Lỗi hệ thống: " . $e->getMessage();
    }
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
                <i class="fa-solid"><span class="fa-arrow-left"></span><span class="d-none d-sm-inline small text-center">  Home Page</span></i>

            </a>

            <div class="text-end">
                <h4 class="fw-bold text-dark mb-0" style="font-size: 1.35rem; letter-spacing: 0.5px;">
                    Kimochi <span class="text-pink">Shop</span>
                </h4>
            </div>
        </div>

        <div class="form-container-box">
            
            <div id="login-box" class="form-box-fade <?php echo $tab !== 'login' ? 'd-none' : ''; ?>">
                <h4 class="dynamic-title mb-1">ĐĂNG NHẬP</h4>
                <p class="text-muted small text-center mb-3">Chào mừng bạn quay lại!</p>

                <form action="auth.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Tên đăng nhập</label>
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
                    <span class="small text-muted">Chưa có tài khoản? </span>
                    <a href="auth.php?tab=register" class="switch-btn small fw-bold text-decoration-none text-pink" data-target="register">Đăng ký ngay</a>
                </div>
            </div>

            <div id="register-box" class="form-box-fade <?php echo $tab !== 'register' ? 'd-none' : ''; ?>">
                <h4 class="dynamic-title mb-1">ĐĂNG KÝ TÀI KHOẢN</h4>
                <p class="text-muted small text-center mb-2">Cảm ơn vì đã tin tưởng và lựa chọn Kimochi Shop!</p>
                <form action="auth.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" name="reg_username" class="form-control py-2" placeholder="Tối thiểu 5 ký tự..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Địa chỉ Email <span class="text-danger">*</span></label>
                        <input type="email" name="reg_email" class="form-control py-2" placeholder="example@gmail.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="reg_password" class="form-control py-2" placeholder="Nhập mật khẩu bảo mật..." required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="reg_password_confirm" class="form-control py-2" placeholder="Nhập lại mật khẩu..." required>
                    </div>
                    <button type="submit" name="btn-register" class="btn btn-pink w-100 fw-bold py-2 mb-3 shadow-sm">Đăng Ký</button>
                </form>
                <div class="text-center mt-3">
                    <span class="small text-muted">Đã có tài khoản rồi? </span>
                    <a href="auth.php?tab=login" class="switch-btn small fw-bold text-decoration-none text-pink" data-target="login">Đăng nhập</a>
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
        <h5 class="fw-semibold text-white mb-4" style="opacity: 0.9;">Chào mừng bạn đến với thế giới người lớn</h5>
        
        <p class="lead fs-6 lh-lg mb-4" style="max-width: 600px; color: #b0b3b8 !important;">
            Tại Kimochi Shop, chúng tôi cam kết mang đến những sản phẩm người lớn chất lượng cao, an toàn và tinh tế. 
            Chúng tôi tin rằng mọi người đều xứng đáng được tận hưởng những giây phục thăng hoa và tự tin.
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

</body>
</html>