<?php
// Kế thừa biến kết nối $conn từ file profile.php mẹ bên ngoài
try {
    $cat_stmt = $conn->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>

<form action="uploads_products.php" method="POST" enctype="multipart/form-data">
    
    <div class="mb-3">
        <label class="form-label text-muted small text-uppercase fw-bold">Tên mặt hàng <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control input-custom" placeholder="Ví dụ: Đồng hồ Kimochi Luxury V2" required>
    </div>
    
    <div class="mb-3">
        <label class="form-label text-muted small text-uppercase fw-bold">Danh mục mặt hàng <span class="text-danger">*</span></label>
        <select name="category_id" class="form-select input-custom" style="background-color: var(--bg-input); color: #fff;" required>
            <option value="">-- Chọn danh mục sản phẩm --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label text-muted small text-uppercase fw-bold">Mô tả chi tiết <span class="text-danger">*</span></label>
        <textarea name="description" class="form-control input-custom" rows="4" placeholder="Nhập thông số kỹ thuật, bảo hành, mô tả sản phẩm..." required></textarea>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
            <input type="number" name="price" class="form-control input-custom" placeholder="0" required min="1">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Số lượng kho <span class="text-danger">*</span></label>
            <input type="number" name="stock" class="form-control input-custom" placeholder="0" required min="0">
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label text-muted small text-uppercase fw-bold">Hình ảnh sản phẩm <span class="text-danger">*</span></label>
        <input type="file" name="product_image" class="form-control input-custom" accept="image/*" required>
        <div class="form-text text-muted" style="font-size: 11px;">Chấp nhận file định dạng JPG, PNG, WEBP. Dung lượng < 2MB.</div>
    </div>

    <button type="submit" name="btn-add-product" class="btn btn-pink w-100 py-2 fw-bold text-uppercase">
        Lập tức đăng bán
    </button>
</form>