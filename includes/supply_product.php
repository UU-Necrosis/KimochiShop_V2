<?php
// =====================================================================================
// FILE: includes/supply_product.php (Bản vá lỗi hiển thị Sáng / Tối)
// =====================================================================================
try {
    $cat_stmt = $conn->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>

<style>
    /* Khi ở CHẾ ĐỘ TỐI (Dark Mode) */
    [data-bs-theme="dark"] .input-custom, .dark-mode .input-custom {
        background-color: #1e1f22 !important;
        color: #f2f3f5 !important;
        border: 1px solid #2b2d31 !important;
    }
    [data-bs-theme="dark"] .theme-text-adaptive, .dark-mode .theme-text-adaptive {
        color: #ffffff !important;
    }

    /* Khi ở CHẾ ĐỘ SÁNG (Light Mode) */
    [data-bs-theme="light"] .input-custom, .light-mode .input-custom {
        background-color: #fff !important;
        color: #313338 !important;
        border: 1px solid #ced4da !important;
    }
    [data-bs-theme="light"] .theme-text-adaptive, .light-mode .theme-text-adaptive {
        color: #313338 !important;
    }
    [data-bs-theme="light"] .input-custom::placeholder, .light-mode .input-custom::placeholder {
        color: #949ba4 !important;
    }
    [data-bs-theme="light"] select.input-custom option, .light-mode select.input-custom option {
        background-color: #fff !important;
        color: #313338 !important;
    }
</style>

<div class="p-1">
    <div class="text-start mb-4">
        <h3 class="fw-bold theme-text-adaptive">
            <i class="fa-solid fa-box-open text-pink me-2"></i>Đăng sản phẩm mới
        </h3>
        <p class="text-muted small">Cung ứng mặt hàng mới của bạn lên hệ thống cửa hàng Kimochi Shop đầy đủ hình ảnh và thông số</p>
    </div>

    <form action="uploads_products.php" method="POST" enctype="multipart/form-data">
        
        <div class="mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Tên mặt hàng <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control input-custom" placeholder="Ví dụ: Đồng hồ Kimochi Luxury Edition V2" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Danh mục mặt hàng <span class="text-danger">*</span></label>
            <select name="category_id" class="form-select input-custom" required>
                <option value="" class="text-muted">-- Chọn danh mục sản phẩm tương ứng --</option>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled class="text-danger">⚠️ Hệ thống chưa có danh mục sản phẩm!</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label text-muted small text-uppercase fw-bold">Mô tả chi tiết sản phẩm <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control input-custom" rows="5" placeholder="Nhập thông số kỹ thuật, chất liệu, tính năng, hoặc chính sách bảo hành..." required></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label text-muted small text-uppercase fw-bold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" name="price" class="form-control input-custom" placeholder="Nhập số tiền bán..." required min="1">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label text-muted small text-uppercase fw-bold">Số lượng nhập kho <span class="text-danger">*</span></label>
                <input type="number" name="stock" class="form-control input-custom" placeholder="Nhập số lượng tồn..." required min="0">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label text-muted small text-uppercase fw-bold">Hình ảnh sản phẩm đại diện <span class="text-danger">*</span></label>
            <input type="file" name="product_image" class="form-control input-custom" accept="image/*" required>
            <div class="form-text text-muted" style="font-size: 11px; margin-top: 6px;">
                <i class="fa-solid fa-circle-info me-1"></i> Định dạng file cho phép: JPG, PNG, WEBP. Dung lượng tệp giới hạn dưới 2MB.
            </div>
        </div>

        <button type="submit" name="btn-add-product" class="btn btn-pink w-100 py-2 fw-bold text-uppercase mt-2">
            <i class="fa-solid fa-rocket me-2"></i> Lập tức đăng bán
        </button>
    </form>
</div>