<?php
// 1. Lấy danh sách danh mục từ Postgres đổ vào ô Select (Kế thừa biến $conn từ profile.php)
try {
    $cat_stmt = $conn->query("SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>

<form action="profile.php" method="POST" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">Tên mặt hàng</label>
        <input type="text" name="name" class="form-control input-custom" placeholder="Nhập tên mặt hàng..." required>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Danh mục mặt hàng</label>
        <select name="category_id" class="form-select input-custom" required>
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Giá tiền</label>
            <input type="number" name="price" class="form-control input-custom" placeholder="Nhập số tiền..." required min="1">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Số lượng</label>
            <input type="number" name="stock" class="form-control input-custom" placeholder="Nhập số lượng..." required min="0">
        </div>
    </div>

    <button type="submit" name="btn-add-product" class="btn btn-pink w-100 py-2 fw-bold text-uppercase mt-2">
        Lập tức đăng bán
    </button>
</form>