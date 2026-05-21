<?php
// File con nằm trong includes/, kế thừa kết nối $conn và thông tin $user từ profile.php
$errors = $errors ?? [];

// Load danh mục sản phẩm từ DB đổ vào thẻ <select>
try {
    $cat_stmt = $conn->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>

<div class="p-2">
    <div class="text-start mb-4">
        <h3 class="fw-bold text-white"><i class="fa-solid fa-box-open text-pink me-2"></i>Đăng sản phẩm mới</h3>
        <p class="text-muted small">Cung ứng mặt hàng mới của bạn lên hệ thống cửa hàng Kimochi Shop</p>
    </div>

    <?php if (!empty($errors['db'])): ?>
        <div class="alert alert-danger py-2 small"><i class="fa-solid fa-circle-xmark me-2"></i><?php echo $errors['db']; ?></div>
    <?php endif; ?>

    <form action="profile.php" method="POST" enctype="multipart/form-data">
        
        <div class="mb-3">
            <label class="form-label small text-uppercase fw-bold text-muted">Tên mặt hàng <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control input-custom" placeholder="Ví dụ: Đồng hồ Kimochi Luxury" required>
            <?php if(isset($errors['name'])): ?><div class="text-danger small mt-1"><?php echo $errors['name']; ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label small text-uppercase fw-bold text-muted">Danh mục sản phẩm <span class="text-danger">*</span></label>
            <select name="category_id" class="form-select input-custom" style="background-color: var(--bg-input); color: #fff;" required>
                <option value="">-- Chọn danh mục --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if(isset($errors['category_id'])): ?><div class="text-danger small mt-1"><?php echo $errors['category_id']; ?></div><?php endif; ?>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label small text-uppercase fw-bold text-muted">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" name="price" class="form-control input-custom" placeholder="0" required min="1">
                <?php if(isset($errors['price'])): ?><div class="text-danger small mt-1"><?php echo $errors['price']; ?></div><?php endif; ?>
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label small text-uppercase fw-bold text-muted">Số lượng kho</label>
                <input type="number" name="stock" class="form-control input-custom" value="0" min="0">
                <?php if(isset($errors['stock'])): ?><div class="text-danger small mt-1"><?php echo $errors['stock']; ?></div><?php endif; ?>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small text-uppercase fw-bold text-muted">Hình ảnh sản phẩm</label>
            <input type="file" name="product_image" class="form-control input-custom" accept="image/*">
        </div>

        <button type="submit" name="btn-add-product" class="btn btn-pink w-100 py-2 fw-bold text-uppercase">Xác nhận Đăng Bán</button>
    </form>
</div>