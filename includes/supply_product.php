<?php
// =====================================================================================
// FILE: includes/supply_product.php (Bản vá lỗi hiển thị Tàng hình chữ ở Mode Tối)
// =====================================================================================
try {
    $cat_stmt = $conn->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>

<style>
    /* ==========================================
       1. CẤU HÌNH CHO CHẾ ĐỘ TỐI (DARK MODE) 
       ========================================== */
    /* Tự động kích hoạt nếu wrapper có class dark hoặc không có class light-mode */
    [data-bs-theme="dark"] .form-label-adaptive, 
    .dark-mode .form-label-adaptive,
    body:not(.light-mode) .form-label-adaptive {
        color: #e3e5e8 !important; /* Chữ Label sáng màu trắng xám */
    }
    
    [data-bs-theme="dark"] .input-custom, 
    .dark-mode .input-custom,
    body:not(.light-mode) .input-custom {
        background-color: #1e1f22 !important; /* Nền input tối */
        color: #f2f3f5 !important; /* Chữ nhập vào màu trắng */
        border: 1px solid #2b2d31 !important;
    }

    [data-bs-theme="dark"] .input-custom::placeholder, 
    .dark-mode .input-custom::placeholder,
    body:not(.light-mode) .input-custom::placeholder {
        color: #6d6f78 !important; /* Chữ placeholder xám dễ nhìn, không bị đen */
    }

    [data-bs-theme="dark"] .form-text-adaptive,
    body:not(.light-mode) .form-text-adaptive {
        color: #949ba4 !important;
    }

    /* ==========================================
       2. CẤU HÌNH CHO CHẾ ĐỘ SÁNG (LIGHT MODE)
       ========================================== */
    [data-bs-theme="light"] .form-label-adaptive, 
    .light-mode .form-label-adaptive {
        color: #313338 !important; /* Chữ Label tối hẳn xuống để nổi trên nền trắng */
    }

    [data-bs-theme="light"] .input-custom, 
    .light-mode .input-custom {
        background-color: #ffffff !important;
        color: #212529 !important;
        border: 1px solid #ced4da !important;
    }

    [data-bs-theme="light"] .input-custom::placeholder, 
    .light-mode .input-custom::placeholder {
        color: #adb5bd !important;
    }
    
    [data-bs-theme="light"] .form-text-adaptive,
    .light-mode .form-text-adaptive {
        color: #6c757d !important;
    }

    /* Định dạng chung cho nút bấm */
    .btn-pink {
        background-color: #ff477e !important;
        color: white !important;
        border: none;
    }
    .btn-pink:hover {
        background-color: #f72585 !important;
        opacity: 0.9;
    }
</style>

<div class="p-1">
    <div class="text-start mb-4">
        <h3 class="fw-bold form-label-adaptive">
            <i class="fa-solid fa-box-open text-pink me-2"></i>Đăng sản phẩm mới
        </h3>
        <p class="form-text-adaptive small">Cung ứng mặt hàng mới của bạn lên hệ thống cửa hàng Kimochi Shop đầy đủ hình ảnh và thông số</p>
    </div>

    <form action="uploads_products.php" method="POST" enctype="multipart/form-data">
        
        <div class="mb-3">
            <label class="form-label form-label-adaptive small text-uppercase fw-bold">Tên mặt hàng <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control input-custom" placeholder="Ví dụ: Đồng hồ Kimochi Luxury Edition V2" required>
        </div>
        
        <div class="mb-3">
            <label class="form-label form-label-adaptive small text-uppercase fw-bold">Danh mục mặt hàng <span class="text-danger">*</span></label>
            <select name="category_id" class="form-select input-custom" required>
                <option value="">-- Chọn danh mục sản phẩm tương ứng --</option>
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
            <label class="form-label form-label-adaptive small text-uppercase fw-bold">Mô tả chi tiết sản phẩm <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control input-custom" rows="5" placeholder="Nhập thông số kỹ thuật, chất liệu, tính năng, hoặc chính sách bảo hành..." required></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label form-label-adaptive small text-uppercase fw-bold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" name="price" class="form-control input-custom" placeholder="Nhập số tiền bán..." required min="1">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label form-label-adaptive small text-uppercase fw-bold">Số lượng nhập kho <span class="text-danger">*</span></label>
                <input type="number" name="stock" class="form-control input-custom" placeholder="Nhập số lượng tồn..." required min="0">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label form-label-adaptive small text-uppercase fw-bold">Hình ảnh sản phẩm đại diện <span class="text-danger">*</span></label>
            <input type="file" name="product_image" class="form-control input-custom" accept="image/*" required>
            <div class="form-text-adaptive small" style="font-size: 11px; margin-top: 6px;">
                <i class="fa-solid fa-circle-info me-1"></i> Định dạng file cho phép: JPG, PNG, WEBP. Dung lượng tệp giới hạn dưới 2MB.
            </div>
        </div>

        <button type="submit" name="btn-add-product" class="btn btn-pink w-100 py-2 fw-bold text-uppercase mt-2">
            <i class="fa-solid fa-rocket me-2"></i> Lập tức đăng bán
        </button>
    </form>
</div>