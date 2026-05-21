<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. CHẶN CỬA: Nếu không phải seller hoặc admin thì đá văng ra ngoài
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'admin')) {
    header("Location: index.php");
    exit();
}

require_once 'config/db_connect.php';

$errors = [];
$success = "";

// Lấy danh sách danh mục (categories) từ DB để đổ vào thẻ <select>
try {
    $cat_stmt = $conn->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// 2. XỬ LÝ KHI NGƯỜI DÙNG ẤN NÚT ĐĂNG SẢN PHẨM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn-add-product'])) {
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? 0);
    $seller_id = $_SESSION['user_id']; // Lấy ID của seller đang đăng nhập

    // Validate nhanh
    if (empty($name)) $errors['name'] = "Vui lòng nhập tên sản phẩm!";
    if (empty($category_id)) $errors['category_id'] = "Vui lòng chọn danh mục!";
    if (!is_numeric($price) || $price <= 0) $errors['price'] = "Giá sản phẩm phải là số lớn hơn 0!";
    if (!is_numeric($stock) || $stock < 0) $errors['stock'] = "Số lượng kho không hợp lệ!";

    // Xử lý Upload file ảnh sản phẩm
    $image_name = 'default-product.png'; // Ảnh mặc định nếu không up gì
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $original_name = $_FILES['product_image']['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed_exts)) {
            // Tạo tên ảnh độc nhất để không bị trùng ghi đè
            $image_name = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            
            // Đảm bảo thư mục lưu ảnh tồn tại (Tạo thư mục nếu chưa có)
            $upload_dir = 'uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            move_uploaded_path = $upload_dir . $image_name;
            move_uploaded_file($file_tmp, $upload_dir . $image_name);
        } else {
            $errors['image'] = "Định dạng ảnh không hợp lệ (Chỉ nhận JPG, PNG, WEBP)!";
        }
    }

    // Nếu không có lỗi, tiến hành bùa chú ghi vào PostgreSQL
    if (empty($errors)) {
        try {
            // Câu lệnh chuẩn khít với bảng products trong pgAdmin 4 của ông giáo
            // Lưu ý: Nếu bảng của ông giáo có thêm cột image_url hoặc hình ảnh thì bổ sung cột đó vào nhé
            $sql = "INSERT INTO products (seller_id, category_id, name, price, stock, status) 
                    VALUES (:seller_id, :category_id, :name, :price, :stock, 1)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':seller_id'   => $seller_id,
                ':category_id' => $category_id,
                ':name'        => $name,
                ':price'       => $price,
                ':stock'       => $stock
            ]);

            $success = "🎉 Đăng sản phẩm thành công sản phẩm lên sàn!";
            
            // Xóa trắng dữ liệu form sau khi đăng thành công để họ nhập món tiếp theo
            $name = $price = $stock = "";
        } catch (PDOException $e) {
            $errors['db'] = "Lỗi lưu vào cơ sở dữ liệu: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng sản phẩm mới - Kênh Người Bán</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css"> </head>
<body style="background-color: var(--bg-body); color: var(--text-main); padding: 40px 0;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col_md_8 col_lg_6">
            
            <div class="mb-3">
                <a href="profile.php" class="text-pink text-decoration-none small">
                    <i class="fa-solid fa-arrow-left me-1"></i> Quay lại Hồ sơ của tôi
                </a>
            </div>

            <div class="card discord-card p-4" style="background-color: var(--bg-card); border-radius: 8px;">
                <div class="text-center mb-4">
                    <i class="fa-solid fa-box-open text-pink mb-2" style="font-size: 40px;"></i>
                    <h3 class="fw-bold">Thêm Mặt Hàng Mới</h3>
                    <p class="text-muted small">Điền thông tin để đăng sản phẩm của bạn lên hệ thống</p>
                </div>

                <?php if(!empty($success)): ?>
                    <div class="alert alert-success py-2 small"><i class="fa-solid fa-circle-check me-2"></i><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if(!empty($errors['db'])): ?>
                    <div class="alert alert-danger py-2 small"><i class="fa-solid fa-circle-xmark me-2"></i><?php echo $errors['db']; ?></div>
                <?php endif; ?>

                <form action="seller_add_product.php" method="POST" enctype="multipart/form-data">
                    
                    <div class="mb-3">
                        <label class="form-label small text-uppercase fw-bold text-muted">Tên mặt hàng <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control input-custom" placeholder="Ví dụ: Đồng hồ Kimochi Luxury" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        <?php if(isset($errors['name'])): ?><div class="text-danger small mt-1"><?php echo $errors['name']; ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-uppercase fw-bold text-muted">Danh mục ngành hàng <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select input-custom" style="background-color: var(--bg-input); color: #fff;" required>
                            <option value="">-- Chọn danh mục sản phẩm --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(isset($errors['category_id'])): ?><div class="text-danger small mt-1"><?php echo $errors['category_id']; ?></div><?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small text-uppercase fw-bold text-muted">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control input-custom" placeholder="0" value="<?php echo htmlspecialchars($price ?? ''); ?>" required min="1">
                            <?php if(isset($errors['price'])): ?><div class="text-danger small mt-1"><?php echo $errors['price']; ?></div><?php endif; ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label small text-uppercase fw-bold text-muted">Số lượng trong kho</label>
                            <input type="number" name="stock" class="form-control input-custom" placeholder="0" value="<?php echo htmlspecialchars($stock ?? 0); ?>" min="0">
                            <?php if(isset($errors['stock'])): ?><div class="text-danger small mt-1"><?php echo $errors['stock']; ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small text-uppercase fw-bold text-muted">Hình ảnh sản phẩm</label>
                        <input type="file" name="product_image" class="form-control input-custom" accept="image/*">
                        <?php if(isset($errors['image'])): ?><div class="text-danger small mt-1"><?php echo $errors['image']; ?></div><?php endif; ?>
                    </div>

                    <button type="submit" name="btn-add-product" class="btn btn-pink w-100 py-2 fw-bold text-uppercase">Xác nhận Đăng Bán</button>
                </form>

            </div>

        </div>
    </div>
</div>

</body>
</html>