<?php
// =====================================================================================
// FILE: includes/supply_product.php
// BẢN VÁ LỖI PHẠM VI BIẾN (SCOPE) VÀ TỰ ĐỘNG KẾT NỐI DATABASE BẤT TỬ
// =====================================================================================
global $conn; // Kích hoạt biến toàn cục để tránh lỗi Scope khi include

// BẢO HIỂM 1: Nếu chưa tìm thấy $conn, tự động dò và kéo file cấu hình trong thư mục config vào luôn
if (!isset($conn) || $conn === null) {
    if (file_exists(__DIR__ . '/../config/db.php')) {
        include_once __DIR__ . '/../config/db.php';
    } elseif (file_exists(__DIR__ . '/../config/config.php')) {
        include_once __DIR__ . '/../config/config.php';
    } elseif (file_exists(__DIR__ . '/../config/connection.php')) {
        include_once __DIR__ . '/../config/connection.php';
    }
}

$categories = [];

// BẢO HIỂM 2: Dùng cấu trúc Try-Catch đa tầng để chặn đứng mọi nguy cơ Fatal Error gây trắng trang
try {
    if (isset($conn) && $conn !== null) {
        $cat_stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $categories = [];
} catch (Error $e) {
    $categories = []; // Bắt mọi Fatal Error (Call to a member function query on null) nếu DB sập
}
?>

<form action="" method="POST" enctype="multipart/form-data">

    <div class="mb-3">
        <label class="form-label small text-uppercase fw-bold">Tên mặt hàng <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control input-custom" placeholder="Ví dụ: Đồng hồ Kimochi Luxury Edition V2" required>
    </div>

    <div class="mb-3">
        <label class="form-label small text-uppercase fw-bold">Danh mục mặt hàng <span class="text-danger">*</span></label>
        <select name="category_id" class="form-select input-custom" required>
            <option value="" disabled selected hidden>-- Chọn danh mục sản phẩm tương ứng --</option>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="" disabled class="text-danger">⚠️ Hệ thống chưa có danh mục dữ liệu!</option>
            <?php endif; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label small text-uppercase fw-bold">Mô tả chi tiết sản phẩm <span class="text-danger">*</span></label>
        <textarea name="description" class="form-control input-custom" rows="5" placeholder="Nhập thông số kỹ thuật, chất liệu, tính năng, hoặc chính sách bảo hành..." required></textarea>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label small text-uppercase fw-bold">Giá bán (VNĐ) <span class="text-danger">*</span></label>
            <input type="number" name="price" class="form-control input-custom" placeholder="Nhập số tiền bán..." required min="1">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label small text-uppercase fw-bold">Số lượng nhập kho <span class="text-danger">*</span></label>
            <input type="number" name="stock" class="form-control input-custom" placeholder="Nhập số lượng tồn..." required min="0">
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label small text-uppercase fw-bold">Hình ảnh sản phẩm đại diện <span class="text-danger">*</span></label>
        <input type="file" name="product_image" class="form-control input-custom" accept="image/*" required>
        <div class="small opacity-50 mt-1" style="font-size: 11px;">
            <i class="fa-solid fa-circle-info me-1"></i> Định dạng file cho phép: JPG, PNG, WEBP. Dung lượng tệp giới hạn dưới 2MB.
        </div>
    </div>

    <button type="submit" name="btn-add-product" class="btn btn-pink w-100 py-2 fw-bold text-uppercase mt-2">
        <i class="fa-solid fa-rocket me-2"></i> Lập tức đăng bán
    </button>
</form>

<style>
/* Nút hồng cá tính của Kimochi Shop */
.btn-pink {
    background-color: #ff477e !important;
    color: #fff !important;
    border: none !important;
    transition: all 0.2s ease-in-out;
}
.btn-pink:hover {
    background-color: #f72585 !important;
    opacity: 0.9;
}
</style>