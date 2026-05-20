<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php?tab=login");
    exit();
}

require_once __DIR__ . '/config/db_connect.php'; 

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
    <title>Hồ sơ cá nhân - <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Ép toàn bộ trang vừa khít viewport không sinh thanh cuộn dọc */
        html, body { 
            height: 100%; 
            overflow: hidden; 
            background-color: #111214; 
            color: #f2f3f5; 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
        }
        
        /* Layout flex-column linh hoạt trừ đi chiều cao của thanh Header */
        .page-wrapper {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px; /* Thu hẹp padding */
        }

        /* Discord Card Compact Profile */
        .discord-card { 
            background-color: #1e1f22; 
            border-radius: 12px; 
            overflow: hidden; 
            border: 1px solid #2b2d31; 
            width: 100%;
            max-width: 580px; /* Định kích cỡ vừa vặn sang xịn mịn */
        }
        
        /* Hạ chiều cao banner xuống để tiết kiệm diện tích */
        .discord-banner { 
            height: 75px; 
            background: linear-gradient(135deg, #ff69b4, #b9bbbe); 
            position: relative; 
        }
        
        .discord-avatar-container { 
            position: relative; 
            display: inline-block; 
            margin-top: -38px; 
            margin-left: 20px; 
        }
        
        /* Thu nhỏ kích cỡ avatar */
        .discord-avatar { 
            width: 76px; 
            height: 76px; 
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

        .discord-body { padding: 15px 20px 20px 20px; background-color: #1e1f22; }
        .discord-info-box { background-color: #2b2d31; border-radius: 8px; padding: 12px 16px; margin-top: 10px; }
        
        /* Custom Input thanh thoát, form nhỏ lại */
        .input-custom { 
            background-color: #111214 !important; 
            border: 1px solid #111214 !important; 
            color: #f2f3f5 !important; 
            border-radius: 4px; 
            padding: 7px 10px; 
            font-size: 14px;
        }
        .input-custom:focus { border-color: #5865f2 !important; box-shadow: none !important; }
        .form-label-custom { color: #949ba4; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; }
        
        .btn-discord-save { background-color: #248046; color: white; font-weight: 500; border: none; padding: 6px 18px; border-radius: 3px; font-size: 14px; transition: 0.2s; }
        .btn-discord-save:hover { background-color: #1a6535; }
        .btn-discord-logout { background-color: transparent; color: #da373c; border: 1px solid #da373c; padding: 6px 18px; border-radius: 3px; font-size: 14px; transition: 0.2s; text-decoration: none; }
        .btn-discord-logout:hover { background-color: #da373c; color: white; }
        
        .alert-toast { position: absolute; top: 75px; right: 20px; z-index: 9999; max-width: 350px; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        
        <div class="alert-toast">
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success bg-success text-white border-0 py-2 small shadow shadow-sm"><i class="fa-solid fa-circle-check me-2"></i><?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger bg-danger text-white border-0 py-2 small shadow shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?></div>
            <?php endif; ?>
        </div>

        <form action="profile.php" method="POST" enctype="multipart/form-data" class="w-100 d-flex justify-content-center">
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
                            <p class="text-secondary m-0" style="font-size: 12px;">Gia nhập: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                        </div>
                        <span class="badge bg-dark border border-secondary text-white px-2 py-1" style="font-size: 11px;"><i class="fa-solid fa-skull me-1 text-pink"></i><?php echo strtoupper(htmlspecialchars($user['role'] ?? 'USER')); ?></span>
                    </div>

                    <div class="discord-info-box">
                        <div class="mb-2">
                            <label class="form-label-custom">Địa chỉ Email</label>
                            <input type="text" class="form-control input-custom text-white-50" value="<?php echo htmlspecialchars($user['email']); ?>" style="cursor: not-allowed;" readonly>
                        </div>

                        <div class="mb-2">
                            <label class="form-label-custom">Tên hiển thị / Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control input-custom" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <hr class="border-secondary my-2">

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

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <a href="index.php" class="text-white-50 text-decoration-none small" style="font-size: 13px;"><i class="fa-solid fa-arrow-left me-1"></i> Quay lại trang chủ</a>
                        <div>
                            <a href="logout.php" class="btn-discord-logout me-2">Đăng xuất</a>
                            <button type="submit" class="btn-discord-save">Lưu thay đổi</button>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </main>
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