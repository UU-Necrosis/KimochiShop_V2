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
    
    // 1. Cập nhật thông tin cơ bản & đổi mật khẩu (Tab 1)
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
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

        // Xử lý đổi mật khẩu nếu có nhập
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
    }

    // 2. Cập nhật nâng cao: Vai trò người sử dụng (Tab 2)
    if (isset($_POST['action']) && $_POST['action'] === 'update_advanced') {
        $new_role = trim($_POST['role']);
        if (in_array($new_role, ['customer', 'seller', 'shipper', 'admin'])) {
            try {
                $role_sql = "UPDATE users SET role = :role WHERE id = :id";
                $role_stmt = $conn->prepare($role_sql);
                $role_stmt->execute([':role' => $new_role, ':id' => $user_id]);
                $_SESSION['role'] = $new_role; // Cập nhật lại session quyền
                $success_msg = "Cập nhật quyền tài khoản thành công!";
            } catch (PDOException $e) {
                $error_msg = "Lỗi cập nhật vai trò: " . $e->getMessage();
            }
        } else {
            $error_msg = "Vai trò chọn không hợp lệ!";
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
<html lang="vi" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống - <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Khóa cứng chiều cao trang chuẩn app Desktop phẳng */
        html, body { 
            height: 100%; 
            overflow: hidden; 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
        }
        
        .page-wrapper { display: flex; flex-direction: column; height: 100vh; }
        .discord-container { flex: 1; display: flex; height: calc(100vh - 56px); }

        /* CỘT TRÁI (1/5) Layout định hình */
        .discord-sidebar { flex: 0 0 20%; padding: 40px 10px 20px 30px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; padding-left: 10px; }
        
        .sidebar-menu-item { display: flex; align-items: center; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: 500; margin-bottom: 4px; border: none; background: transparent; width: 100%; text-align: left; transition: 0.2s; }
        .sidebar-menu-item i { margin-right: 8px; width: 16px; text-align: center; }

        /* CỘT PHẢI (4/5) Layout định hình */
        .discord-main-panel { flex: 0 0 80%; padding: 40px 40px 20px 40px; overflow-y: auto; }
        .discord-card { border-radius: 8px; overflow: hidden; width: 100%; max-width: 660px; }
        .discord-banner { height: 80px; background: linear-gradient(135deg, #ff69b4, #b9bbbe); position: relative; }
        
        .discord-avatar-container { position: relative; display: inline-block; margin-top: -40px; margin-left: 20px; }
        .discord-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg-card); }
        
        .avatar-edit-badge { position: absolute; top: 0; right: 0; background: #313338; border: 2px solid #1e1f22; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; color: #dbdee1; cursor: pointer; }
        .discord-body { padding: 20px; }
        .discord-info-box { border-radius: 8px; padding: 16px; margin-top: 12px; }
        
        .input-custom { border-radius: 4px; padding: 8px 12px; font-size: 14px; }
        .input-custom:focus { border-color: #5865f2 !important; box-shadow: none !important; }
        .form-label-custom { font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 6px; }
        
        .btn-discord-save { background-color: #248046; color: white; font-weight: 500; border: none; padding: 8px 24px; border-radius: 3px; font-size: 14px; }
        .btn-discord-save:hover { background-color: #1a6535; }
        .btn-discord-logout { background-color: transparent; color: #da373c; border: 1px solid #da373c; padding: 6px 16px; border-radius: 3px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .btn-discord-logout:hover { background-color: #da373c; color: white; }
        
        .alert-toast { position: absolute; top: 70px; right: 20px; z-index: 9999; max-width: 320px; }
        .text-pink { color: #ff69b4 !important; }
    </style>
</head>
<body>

<div class="page-wrapper">
    <?php include 'includes/header.php'; ?>

    <div class="discord-container">
        
        <aside class="discord-sidebar">
            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <div class="sidebar-title">Cài đặt người dùng</div>
                
                <button class="sidebar-menu-item active" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile" aria-selected="true">
                    <i class="fa-solid fa-user-gear"></i> Thông tin tài khoản
                </button>
                
                <button class="sidebar-menu-item" id="v-pills-advanced-tab" data-bs-toggle="pill" data-bs-target="#v-pills-advanced" type="button" role="tab" aria-controls="v-pills-advanced" aria-selected="false">
                    <i class="fa-solid fa-user-shield"></i> Cập nhật nâng cao
                </button>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'seller'): ?>
                    <button class="sidebar-menu-item " id="v-pills-supply-tab" data-bs-toggle="pill" data-bs-target="#v-pills-supply" type="button" role="tab" aria-controls="v-pills-supply" aria-selected="true">
                        <i class="fa-solid fa-plus-circle me-1"></i> Đăng sản phẩm mới
                    </button>

                    <button class="sidebar-menu-item " id="v-pills-stock-tab" data-bs-toggle="pill" data-bs-target="#v-pills-stock" type="button" role="tab" aria-controls="v-pills-stock" aria-selected="true">
                        <i class="fa-solid fa-boxes-stacked me-1"></i> Kho hàng của tôi
                    </button>
                <?php endif; ?>


                <a href="index.php" class="sidebar-menu-item text-secondary mt-2">
                    <i class="fa-solid fa-house"></i> Quay lại trang chủ
                </a>
            </div>
            
            <div>
                <a href="logout.php" class="btn-discord-logout w-100"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a>
            </div>
        </aside>

        <main class="discord-main-panel tab-content" id="v-pills-tabContent">
            
            <div class="alert-toast">
                <?php if(!empty($success_msg)): ?>
                    <div class="alert alert-success bg-success text-white border-0 py-2 small shadow-sm"><i class="fa-solid fa-circle-check me-2"></i><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if(!empty($error_msg)): ?>
                    <div class="alert alert-danger bg-danger text-white border-0 py-2 small shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error_msg; ?></div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                <h4 class="fw-bold mb-4">Hồ sơ của tôi</h4>
                
                <form action="profile.php" method="POST" enctype="multipart/form-data" class="w-100">
                    <input type="hidden" name="action" value="update_profile">
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
                                    <h5 class="fw-bold m-0"><?php echo htmlspecialchars($user['username']); ?></h5>
                                    <p class="text-secondary m-0" style="font-size: 12px;">Thành viên từ: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                                </div>
                                <span class="badge bg-dark border border-secondary text-white px-2 py-1" style="font-size: 11px;"><i class="fa-solid fa-skull me-1 text-pink"></i><?php echo strtoupper(htmlspecialchars($user['role'] ?? 'USER')); ?></span>
                            </div>

                            <div class="discord-info-box">
                                <div class="mb-3">
                                    <label class="form-label-custom">Địa chỉ Email</label>
                                    <input type="text" class="form-control input-custom text-muted w-100" value="<?php echo htmlspecialchars($user['email']); ?>" style="cursor: not-allowed;" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label-custom">Tên hiển thị / Tên đăng nhập</label>
                                    <input type="text" name="username" class="form-control input-custom w-100" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                <hr class="border-secondary my-3">
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label-custom">Mật khẩu mới</label>
                                        <input type="password" name="new_password" class="form-control input-custom w-100" placeholder="Bỏ trống nếu không đổi">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label-custom">Mật khẩu hiện tại</label>
                                        <input type="password" name="old_password" class="form-control input-custom w-100" placeholder="Nhập pass cũ để lưu">
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn-discord-save">Lưu thay đổi</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="v-pills-advanced" role="tabpanel" aria-labelledby="v-pills-advanced-tab">
                <h4 class="fw-bold mb-4">Cài đặt nâng cao</h4>
                
                <div class="discord-card p-4 shadow-lg">
                    <div class="mb-4">
                        <label class="form-label-custom d-block mb-2"><i class="fa-solid fa-palette me-1"></i> Chế độ hiển thị Giao diện</label>
                        <div class="discord-info-box mt-0 d-flex align-items-center justify-content-between">
                            <div>
                                <span class="d-block fw-bold small">Chuyển đổi Dark / Light mode</span>
                                <span class="text-secondary small" style="font-size: 12px;">Điều chỉnh độ sáng phù hợp với môi trường của bạn.</span>
                            </div>
                            <div class="form-check form-switch fs-5">
                                <input class="form-check-input" type="checkbox" role="switch" id="themeToggleScript" style="cursor: pointer;">
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary my-4">

                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="update_advanced">
                        <div class="mb-3">
                            <label class="form-label-custom d-block mb-2"><i class="fa-solid fa-user-shield me-1"></i> Vai trò tài khoản (Role Privilege)</label>
                            <p class="text-secondary small" style="font-size: 12px; margin-top:-5px;">Thay đổi quyền hạn tài khoản trực tiếp (Dùng cho quá trình chạy thử nghiệm Demo đồ án).</p>
                            
                            <select name="role" class="form-select input-custom w-100" style="cursor: pointer;">
                                <option value="customer" <?php echo ($user['role'] === 'customer') ? 'selected' : ''; ?>>CUSTOMER (Khách hàng)</option>
                                <option value="seller" <?php echo ($user['role'] === 'seller') ? 'selected' : ''; ?>>SELLER (Người bán hàng)</option>
                                <option value="shipper" <?php echo ($user['role'] === 'shipper') ? 'selected' : ''; ?>>SHIPPER (Người giao hàng)</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn-discord-save">Lập tức áp dụng quyền</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="v-pills-supply" role="tabpanel" aria-labelledby="v-pills-supply-tab">
                <?php 
                if (($user['role'] ?? '') === 'seller' || ($user['role'] ?? '') === 'admin') {
                    // Nhúng file supply_product.php vào làm nguồn đọc
                    if (file_exists('supply_product.php')) {
                        include_once 'supply_product.php';
                    } else {
                        echo "<p class='text-muted text-center py-4'>Chưa tìm thấy file supply_product.php</p>";
                    }
                } else {
                    echo "<div class='alert alert-danger m-3'>Bạn không có quyền sử dụng chức năng này!</div>";
                }
                ?>
            </div>

            <div class="tab-pane fade" id="v-pills-stock" role="tabpanel" aria-labelledby="v-pills-stock-tab">
                <?php if (($user['role'] ?? '') === 'seller' || ($user['role'] ?? '') === 'admin'): ?>
                    <h3 class="fw-bold mb-3" style="color: var(--text-main);">Kho hàng của tôi</h3>
                    <p class="text-muted small mb-4">Danh sách các mặt hàng bạn đang đăng bán trên Kimochi Shop</p>
                    
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle small" style="background-color: #111214;">
                            <thead>
                                <tr class="text-muted text-uppercase" style="font-size: 11px;">
                                    <th>Sản phẩm</th>
                                    <th>Giá bán</th>
                                    <th>Kho hàng</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $prod_stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = :seller_id ORDER BY id DESC");
                                    $prod_stmt->execute([':seller_id' => $user['id']]);
                                    $my_prods = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (count($my_prods) > 0) {
                                        foreach ($my_prods as $p) {
                                            echo "<tr>";
                                            echo "<td class='fw-bold text-white'>".htmlspecialchars($p['name'])."</td>";
                                            echo "<td class='text-pink'>".number_format($p['price'])." VNĐ</td>";
                                            echo "<td>".htmlspecialchars($p['stock'])." cái</td>";
                                            echo "<td><span class='badge bg-success'>Đang bán</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center text-muted py-4'>Bạn chưa đăng mặt hàng nào cả.</td></tr>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='4' class='text-danger'>Lỗi: ".$e->getMessage()."</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger m-3">Bạn không có quyền sử dụng chức năng này!</div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<script>
// 1. Logic lưu trạng thái Light/Dark mode vào bộ nhớ Trình duyệt (localStorage)
const htmlElement = document.documentElement;
const themeToggle = document.getElementById('themeToggleScript');

// Kiểm tra bộ nhớ xem trước đó user chọn nền gì
const savedTheme = localStorage.getItem('discord-theme') || 'dark';
htmlElement.setAttribute('data-theme', savedTheme);
if (savedTheme === 'light') {
    themeToggle.checked = true;
}

// Bắt sự kiện khi click gạt công tắc
themeToggle.addEventListener('change', function() {
    if (this.checked) {
        htmlElement.setAttribute('data-theme', 'light');
        localStorage.setItem('discord-theme', 'light');
    } else {
        htmlElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('discord-theme', 'dark');
    }
});

// 2. Hàm xem trước ảnh đại diện ngay khi chọn file
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