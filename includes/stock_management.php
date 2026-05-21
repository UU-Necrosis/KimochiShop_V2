<?php
// =====================================================================================
// FILE: includes/stock_management.php
// File con được nhúng vào profile.php mẹ, kế thừa hoàn toàn biến $conn và mảng $account.
// =====================================================================================
?>

<div class="p-1">
    <div class="text-start mb-4">
        <h3 class="fw-bold text-white">
            <i class="fa-solid fa-boxes-stacked text-pink me-2"></i>Kho hàng của tôi
        </h3>
        <p class="text-muted small">Quản lý danh sách, theo dõi tồn kho và cập nhật trạng thái các mặt hàng bạn đang bán</p>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle small" style="background-color: #111214; border-radius: 6px; overflow: hidden;">
            <thead>
                <tr class="text-muted text-uppercase" style="font-size: 11px; background-color: #1e1f22; border-bottom: 2px solid #2f3136;">
                    <th class="ps-3 py-3" style="width: 80px;">Hình ảnh</th>
                    <th>Thông tin sản phẩm</th>
                    <th>Giá bán</th>
                    <th>Tồn kho</th>
                    <th>Trạng thái</th>
                    <th class="pe-3 text-center" style="width: 100px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    // Đồng bộ tên biến dữ liệu với mảng $account của file profile.php mẹ
                    $seller_id = $account['id'] ?? $_SESSION['user_id'] ?? 0;
                    
                    // Cơ chế XÓA MỀM: Chỉ lấy ra những sản phẩm có status = 1
                    $prod_stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = :seller_id AND status = 1 ORDER BY id DESC");
                    $prod_stmt->execute([':seller_id' => $seller_id]);
                    $my_prods = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($my_prods) > 0) {
                        foreach ($my_prods as $p) {
                            // Xử lý đường dẫn ảnh động (hỗ trợ cả cột image_url hoặc image_path tùy ông đặt tên trong DB)
                            $img_file = $p['image_url'] ?? $p['image_path'] ?? 'default-product.png';
                            $img_src = "uploads/products/" . $img_file;
                            
                            // Nếu trong database ông lưu full đường dẫn chứa dấu gạch chéo thì giữ nguyên
                            if (strpos($img_file, '/') !== false) {
                                $img_src = $img_file;
                            }
                            ?>
                            <tr style="border-bottom: 1px solid #2f3136;">
                                <td class="ps-3">
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                         alt="Product Thumbnail" 
                                         class="rounded border border-secondary"
                                         style="width: 52px; height: 52px; object-fit: cover; background-color: #2b2d31;"
                                         onerror="this.src='uploads/products/default-product.png';">
                                </td>
                                
                                <td>
                                    <div class="fw-bold text-white"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <?php if (!empty($p['description'])): ?>
                                        <div class="text-muted text-truncate small mt-1" style="max-width: 280px;" title="<?php echo htmlspecialchars($p['description']); ?>">
                                            <?php echo htmlspecialchars($p['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-pink fw-bold">
                                    <?php echo number_format($p['price']); ?> VNĐ
                                </td>
                                
                                <td>
                                    <?php if ($p['stock'] > 0): ?>
                                        <span class="badge bg-dark text-light border border-secondary px-2 py-1.5">
                                            <?php echo htmlspecialchars($p['stock']); ?> cái
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger text-white px-2 py-1.5">Hết hàng</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                        <i class="fa-solid fa-circle-check me-1" style="font-size: 9px;"></i> Đang bán
                                    </span>
                                </td>
                                
                                <td class="pe-3 text-center">
                                    <form action="uploads_products.php" method="POST" onsubmit="return confirm('Bạn chắc chắn muốn gỡ mặt hàng này xuống khỏi hệ thống Kimochi Shop?');">
                                        <input type="hidden" name="delete_product_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" name="btn-delete-product" class="btn btn-sm btn-outline-danger border-0 py-1 px-2 custom-delete-btn">
                                            <i class="fa-solid fa-trash-can"></i> Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        // Giao diện thông báo khi kho trống rỗng
                        ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fa-solid fa-box-open d-block mb-2 fs-3 text-secondary"></i>
                                Kho hàng của bạn hiện tại đang trống rỗng.<br>
                                <span class="small text-muted">Hãy sang tab "Đăng sản phẩm mới" để cung ứng mặt hàng đầu tiên nhé!</span>
                            </td>
                        </tr>
                        <?php
                    }
                } catch (PDOException $e) {
                    echo "<tr><td colspan='6' class='text-danger ps-3 py-4'><i class='fa-solid fa-circle-exclamation me-2'></i>Lỗi kết nối cơ sở dữ liệu: ".$e->getMessage()."</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.custom-delete-btn:hover {
    background-color: #dc3545 !important;
    color: #fff !important;
}
.bg-success-subtle {
    background-color: rgba(25, 135, 84, 0.15) !important;
}
</style>