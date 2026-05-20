<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu chưa đăng nhập thì đá về trang login ngay
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php?tab=login");
    exit();
}

// Kết nối database
require_once __DIR__ . '/config/db_connect.php'; 

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// ================= PROCESSING FORM SUBMISSION =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. XỬ LÝ ĐỔI THÔNG TIN (Username)
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $new_username = trim($_POST['username']);
        
        if (empty($new_username)) {
            $error_msg = "Tên đăng nhập không được để trống!";
        } else {
            try {
                // Kiểm tra xem tên đăng nhập mới có bị trùng với người khác không
                $check_sql = "SELECT id FROM users WHERE username = :username AND id <> :id";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->execute([':username' => $new_username, ':id' => $user_id]);
                
                if ($check_stmt->fetch()) {
                    $error_msg = "Tên đăng nhập này đã có người sử dụng!";
                } else {
                    // Tiến hành cập nhật
                    $update_sql = "UPDATE users SET username = :username WHERE id = :id";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->execute([':username' => $new_username, ':id' => $user_id]);
                    
                    $_SESSION['username'] = $new_username; // Cập nhật lại session hiển thị trên header
                    $success_msg = "Cập nhật thông tin tài khoản thành công!";
                }
            } catch (PDOException $e) {
                $error_msg = "Lỗi hệ thống: " . $e->getMessage();
            }
        }
    }

    // 2. XỬ LÝ ĐỔI MẬT KHẨU
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error_msg = "Vui lòng điền đầy đủ các trường mật khẩu!";
        } elseif ($new_password !== $confirm_password) {
            $error_msg = "Mật khẩu mới và xác nhận mật khẩu không khớp!";
        } else {
            try {
                // Lấy mật khẩu hiện tại trong DB ra để đối chiếu
                $pass_sql = "SELECT password FROM users WHERE id = :id";
                $pass_stmt = $conn->prepare($pass_sql);
                $pass_stmt->execute([':id' => $user_id]);
                $current_user = $pass_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($current_user && password_verify($old_password, $current_user['password'])) {
                    // Khớp mật khẩu cũ -> Mã hóa mật khẩu mới và cập nhật
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                    $update_pass_sql = "UPDATE users SET password = :password WHERE id = :id";
                    $update_pass_stmt = $conn->prepare($update_pass_sql);
                    $update_pass_stmt->execute([':password' => $hashed_password, ':id' => $user_id]);
                    
                    $success_msg = "Đổi mật khẩu thành công!";
                } else {
                    $error_msg = "Mật khẩu cũ không chính xác!";
                }
            } catch (PDOException $e) {
                $error_msg = "Lỗi hệ thống: " . $e->getMessage();
            }
        }
    }

    // 3. XỬ LÝ UPLOAD ẢNH ĐẠI DIỆN (AVATAR)
    if (isset($_POST['action']) && $_POST['action'] === 'update_avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['avatar']['tmp_name'];
            $file_name = $_FILES['avatar']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Các định dạng file được chấp nhận
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_exts)) {
                // Tạo tên file ngẫu nhiên để tránh trùng lặp dữ liệu
                $new_file_name = "avatar_" . $user_id . "_" . time() . "." . $file_ext;
                $upload_dir = __DIR__ . '/assets/uploads/avatars/';
                
                // Tự động tạo thư mục lưu trữ nếu chưa có
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $dest_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $dest_path)) {
                    try {
                        // Lưu đường dẫn tương đối vào Database
                        $db_path = 'assets/uploads/avatars/' . $new_file_name;
                        $avatar_sql = "UPDATE users SET avatar = :avatar WHERE id = :id";
                        $avatar_stmt = $conn->prepare($avatar_sql);
                        $avatar_stmt->execute([':avatar' => $db_path, ':id' => $user_id]);
                        
                        $success_msg = "Cập nhật ảnh đại diện thành công!";
                    } catch (PDOException $e) {
                        $error_msg = "Lỗi lưu database: " . $e->getMessage();
                    }
                } else {
                    $error_msg = "Không thể di chuyển file upload!";
                }
            } else {
                $error_msg = "Chỉ chấp nhận các định dạng ảnh: JPG, JPEG, PNG, GIF!";
            }
        } else {
            $error_msg = "Vui lòng chọn một file ảnh hợp lệ!";
        }
    }
}

// ================= FETCH FRESH USER DATA =================
try {
    $sql = "SELECT username, email, role, avatar, created_at FROM users WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "Người dùng không tồn tại!";
        exit();
    }
} catch (PDOException $e) {
    echo "Lỗi hệ thống: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân - <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: 'Segoe UI', sans-serif; }
        .profile-card { background-color: #1a1a1a; border: 1px solid #2b2b2b; border-radius: 16px; }
        .nav-tabs .nav-link { color: #aaa; border: none; }
        .nav-tabs .nav-link.active { background-color: transparent; color: #ff69b4; border-bottom: 3px solid #ff69b4; font-weight: bold; }
        .text-pink { color: #ff69b4 !important; }
        .btn-pink { background-color: #ff69b4; color: white; border: none; transition: 0.2s; }
        .btn-pink:hover { background-color: #ff1493; color: white; }
        .avatar-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #ff69b4; }
        .avatar-placeholder { width: 120px; height: 120px; border-radius: 50%; background-color: #2b2b2b; display: inline-flex; align-items: center; justify-content: center; font-size: 4rem; color: #ff69b4; border: 3px solid #ff69b4; }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 bg-success text-white mb-4" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?php echo $success_msg; ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger text-white mb-4" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="profile-card p-4 shadow">
                <div class="text-center mb-4">
                    <div class="position-relative d-inline-block">
                        <?php if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>?t=<?php echo time(); ?>" class="avatar-preview" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder"><i class="fa-solid fa-circle-user"></i></div>
                        <?php endif; ?>
                    </div>
                    <h3 class="mt-3 fw-bold mb-1"><?php echo htmlspecialchars($user['username']); ?></h3>
                    <span class="badge bg-secondary mb-3"><?php echo strtoupper(htmlspecialchars($user['role'] ?? 'USER')); ?></span>
                    <p class="text-white-50 small"><i class="fa-solid fa-calendar-days me-1"></i> Thành viên từ: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                </div>

                <ul class="nav nav-tabs mb-4 border-secondary" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab"><i class="fa-solid fa-id-card me-2"></i>Thông tin</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="avatar-tab" data-bs-toggle="tab" data-bs-target="#avatar-pane" type="button" role="tab"><i class="fa-solid fa-image me-2"></i>Đổi ảnh đại diện</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab"><i class="fa-solid fa-key me-2"></i>Đổi mật khẩu</button>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabsContent">
                    <div class="tab-pane fade show active" id="info-pane" role="tabpanel" aria-labelledby="info-tab">
                        <form action="profile.php" method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="mb-3">
                                <label class="form-label text-white-50">Địa chỉ Email (Không được sửa)</label>
                                <input type="email" class="form-control bg-dark border-secondary text-white-50" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-light">Tên đăng nhập / Tên hiển thị</label>
                                <input type="text" name="username" class="form-control bg-dark border-secondary text-white" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-pink"><i class="fa-solid fa-floppy-disk me-2"></i>Lưu thay đổi</button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="avatar-pane" role="tabpanel" aria-labelledby="avatar-tab">
                        <form action="profile.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_avatar">
                            <div class="mb-4">
                                <label class="form-label text-light">Chọn tệp hình ảnh mới</label>
                                <input type="file" name="avatar" class="form-control bg-dark border-secondary text-white" accept="image/*" required>
                                <div class="form-text text-white-50">Hỗ trợ định dạng định dạng JPG, PNG, GIF. Kích thước khuyến nghị hình vuông.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-pink"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Tải ảnh lên</button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="password-pane" role="tabpanel" aria-labelledby="password-tab">
                        <form action="profile.php" method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="mb-3">
                                <label class="form-label text-light">Mật khẩu hiện tại</label>
                                <input type="password" name="old_password" class="form-control bg-dark border-secondary text-white" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-light">Mật khẩu mới</label>
                                <input type="password" name="new_password" class="form-control bg-dark border-secondary text-white" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-light">Xác nhận mật khẩu mới</label>
                                <input type="password" name="confirm_password" class="form-control bg-dark border-secondary text-white" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-pink"><i class="fa-solid fa-lock me-2"></i>Cập nhật mật khẩu</button>
                            </div>
                        </form>
                    </div>
                </div>

                <hr class="border-secondary my-4">
                <div class="d-flex justify-content-between">
                    <a href="index.php" class="btn btn-outline-light"><i class="fa-solid fa-house me-2"></i>Về trang chủ</a>
                    <a href="logout.php" class="btn btn-danger"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>