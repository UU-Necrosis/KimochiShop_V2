<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php?tab=login");
    exit();
}

require_once __DIR__ . '/config/database.php'; 

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// ================= PROCESSING FORM SUBMISSION =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Cập nhật Username
    if (isset($_POST['username'])) {
        $new_username = trim($_POST['username']);
        if (!empty($new_username)) {
            try {
                $check_sql = "SELECT id FROM users WHERE username = :username AND id <> :id";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->execute([':username' => $new_username, ':id' => $user_id]);
                
                if ($check_stmt->fetch()) {
                    $error_msg = "Tên đăng nhập này đã có người sử dụng!";
                } else {
                    $update_sql = "UPDATE users SET username = :username WHERE id = :id";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->execute([':username' => $new_username, ':id' => $user_id]);
                    $_SESSION['username'] = $new_username;
                    $success_msg = "Cập nhật hồ sơ thành công!";
                }
            } catch (PDOException $e) {
                $error_msg = "Lỗi: " . $e->getMessage();
            }
        }
    }

    // 2. Cập nhật Mật khẩu
    if (!empty($_POST['new_password'])) {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        
        if (empty($old_password)) {
            $error_msg = "Vui lòng nhập Mật khẩu cũ để xác nhận!";
        } else {
            try {
                $pass_sql = "SELECT password FROM users WHERE id = :id";
                $pass_stmt = $conn->prepare($pass_sql);
                $pass_stmt->execute([':id' => $user_id]);
                $curr = $pass_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($curr && password_verify($old_password, $curr['password'])) {
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $update_p = "UPDATE users SET password = :pass WHERE id = :id";
                    $update_p_stmt = $conn->prepare($update_p);
                    $update_p_stmt->execute([':pass' => $hashed, ':id' => $user_id]);
                    $success_msg = "Cập nhật mật khẩu mới thành công!";
                } else {
                    $error_msg = "Mật khẩu cũ không chính xác!";
                }
            } catch (PDOException $e) {
                $error_msg = "Lỗi đổi mật khẩu: " . $e->getMessage();
            }
        }
    }

    // 3. Cập nhật Ảnh đại diện (Avatar)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed)) {
            $new_file_name = "avatar_" . $user_id . "_" . time() . "." . $file_ext;
            $upload_dir = __DIR__ . '/assets/uploads/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                try {
                    $db_path = 'assets/uploads/avatars/' . $new_file_name;
                    $avatar_sql = "UPDATE users SET avatar = :avatar WHERE id = :id";
                    $avatar_stmt = $conn->prepare($avatar_sql);
                    $avatar_stmt->execute([':avatar' => $db_path, ':id' => $user_id]);
                    $success_msg = "Cập nhật ảnh đại diện thành công!";
                } catch (PDOException $e) {
                    $error_msg = "Lỗi lưu avatar: " . $e->getMessage();
                }
            }
        } else {
            $error_msg = "Định dạng file ảnh không hợp lệ!";
        }
    }
}

// ================= FETCH FRESH USER DATA =================
try {
    $sql = "SELECT username, email, role, avatar, created_at FROM users WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>Cài đặt tài khoản - <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Khóa cứng chiều cao trang chuẩn app Desktop */
        html, body { 
            height: 100%; 
            overflow: hidden; 
            background-color: #111214; 
            color: #f2f3f5; 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
        }
        
        .page-wrapper {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Container chia 2 cột */
        .discord-container {
            flex: 1;
            display: flex;
            height: calc(100vh - 56px); /* Trừ đi thanh Header */
        }

        /* --- 1/5 CỘT TRÁI: MENU MỤC LỤC --- */
        .discord-sidebar {
            flex: 0 0 20%; /* Chiếm đúng 1/5 chiều rộng */
            background-color: #2b2d31;
            padding: 40px 10px 20px 30px;
            border-right: 1px solid #1f2023;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sidebar-title {
            color: #949ba4;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            padding-left: 10px;
        }

        .sidebar-menu-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            color: #949ba4;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
            transition: 0.2s;
        }
        .sidebar-menu-item i { margin-right: 8px; width: 16px; text-align: center; }
        .sidebar-menu-item:hover { background-color: #35373c; color: #dbdee1; }
        .sidebar-menu-item.active { background-color: #404249; color: #fff; }

        /* --- 4/5 CỘT PHẢI: BẢNG THÔNG TIN CHI TIẾT --- */
        .discord-main-panel {
            flex: 0 0 80%; /* Chiếm đúng 4/5 chiều rộng */
            background-color: #313338;
            padding: 40px 40px 20px 40px;
            overflow-y: auto; /* Nếu nội dung dài tự cuộn bên trong box phải */
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        /* Discord Card Form */
        .discord-card { 
            background-color: #1e1f22; 
            border-radius: 8px; 
            overflow: hidden; 
            border: 1px solid #2b2d31; 
            width: 100%;
            max-width: 660px;
        }
        
        .discord-banner { 
            height: 80px; 
            background: linear-gradient(135deg, #ff69b4, #b9bbbe); 
            position: relative; 
        }
        
        .discord-avatar-container { 
            position: relative; 
            display: inline-block; 
            margin-top: -40px; 
            margin-left: 20px; 
        }
        
        .discord-avatar { 
            width: 80px; 
            height: 80px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 4px solid #1e1f22; 
            background-color: #2b2d31; 
        }
        
        .avatar-edit-badge { 
            position: absolute; 
            top: 0; 
            right: 0; 
            background: #313338; 
            border: 2px solid #1e1f22; 
            border-radius: 50%; 
            width: 24px; 
            height: 24px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: #dbdee1; 
            cursor: pointer; 
            transition: 0.2s; 
        }
        .avatar-edit-badge:hover { background: #4e5058; color: #fff; }

        .discord-body { padding: 20px; background-color: #1e1f22; }
        .discord-info-box { background-color: #2b2d31; border-radius: 8px; padding: 16px; margin-top: 12px; }
        
        /* Custom Input */
        .input-custom { 
            background-color: #111214 !important; 
            border: 1px solid #111214 !important; 
            color: #f2f3f5 !important; 
            border-radius: 4px; 
            padding: 8px 12px; 
            font-size: 14px;
        }
        .input-custom:focus { border-color: #5865f2 !important; box-shadow: none !important; }
        .form-label-custom { color: #949ba4; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 6px; }
        
        .btn-discord-save { background-color: #248046; color: white; font-weight: 500; border: none; padding: 8px 24px; border-radius: 3px; font-size: 14px; transition: 0.2s; }
        .btn-discord-save:hover { background-color: #1a6535; }
        .btn-discord-logout { background-color: transparent; color: #da373c; border: 1px solid #da373c; padding: 6px 16px; border-radius: 3px; font-size: 14px; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-discord-logout:hover { background-color: #da373c; color: white; }
        
        .alert-toast { position: absolute; top: 70px; right: 20px; z-index: 9999; max-width: 320px; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <?php include 'includes/header.php'; ?>

    <div class="discord-container">
        
        <aside class="discord-sidebar">
            <div>
                <div class="sidebar-title">Cài đặt người dùng</div>
                <a href="profile.php" class="sidebar-menu-item active">
                    <i class="fa-solid fa-user-gear"></i> Thông tin tài khoản
                </a>
                <a href="#" class="sidebar-menu-item" onclick="alert('Tính năng đang cập nhật cập nhật!')">
                    <i class="fa-solid fa-user-shield"></i> Cập nhật nâng cao
                </a>
                <a href="index.php" class="sidebar-menu-item">
                    <i class="fa-solid fa-house"></i> Quay lại trang chủ
                </a>
            </div>
            
            <div>
                <a href="logout.php" class="btn-discord-logout w-100">
                    <i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất
                </a>
            </div>
        </aside>

        <main class="discord-main-panel">
            
            <div class="alert-toast">
                <?php if(!empty($success_msg)): ?>
                    <div class="alert alert-success bg-success text-white border-0 py-2 small shadow-sm"><i class="fa-solid fa-circle-check me-2"></i><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if(!empty($error_msg)): ?>
                    <div class="alert alert-danger bg-danger text-white border-0 py-2 small shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?></div>
                <?php endif; ?>
            </div>

            <h4 class="fw-bold mb-4" style="color: #fff;">Hồ sơ của tôi</h4>

            <form action="profile.php" method="POST" enctype="multipart/form-data" class="w-100">
                <div class="discord-card shadow-lg">
                    <div class="discord-banner"></div>
                    
                    <div class="discord-avatar-container">
                        <?php if(!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>?t=<?php echo time(); ?>" class="discord-avatar" id="avatarImage">
                        <?php else: ?>
                            <div class="discord-avatar d-flex align-items-center justify-content-center text-white fs-4" id="avatarPlaceholder" style="background-color: #ff69b4;"><i class="fa-solid fa-user"></i></div>
                        <?php endif; ?>
                        
                        <label for="avatarInput" class="avatar-edit-badge" title="Thay đổi ảnh đại diện">
                            <i class="fa-solid fa-pencil text-white" style="font-size: 10px;"></i>
                        </label>
                        <input type="file" id="avatarInput" name="avatar" class="d-none" accept="image/*" onchange="previewImage(this)">
                    </div>

                    <div class="discord-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h5 class="fw-bold m-0 text-white"><?php echo htmlspecialchars($user['username']); ?></h5>
                                <p class="text-secondary m-0" style="font-size: 12px;">Thành viên từ: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                            </div>
                            <span class="badge bg-dark border border-secondary text-white px-2 py-1" style="font-size: 11px;"><i class="fa-solid fa-skull me-1 text-pink"></i><?php echo strtoupper(htmlspecialchars($user['role'] ?? 'USER')); ?></span>
                        </div>

                        <div class="discord-info-box">
                            <div class="mb-3">
                                <label class="form-label-custom">Địa chỉ Email</label>
                                <input type="text" class="form-control input-custom text-white-50" value="<?php echo htmlspecialchars($user['email']); ?>" style="cursor: not-allowed;" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label-custom">Tên hiển thị / Tên đăng nhập</label>
                                <input type="text" name="username" class="form-control input-custom" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>

                            <hr class="border-secondary my-3">

                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label-custom">Mật khẩu mới</label>
                                    <input type="password" name="new_password" class="form-control input-custom" placeholder="Bỏ trống nếu không đổi">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label-custom">Mật khẩu hiện tại</label>
                                    <input type="password" name="old_password" class="form-control input-custom" placeholder="Nhập pass cũ để lưu">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn-discord-save">Lưu thay đổi</button>
                        </div>

                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('avatarImage');
            var placeholder = document.getElementById('avatarPlaceholder');
            if(img) {
                img.src = e.target.result;
            } else if(placeholder) {
                placeholder.outerHTML = '<img src="'+e.target.result+'" class="discord-avatar" id="avatarImage">';
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>