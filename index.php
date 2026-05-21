<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =====================================================================================
// KHỞI TẠO VÀ KÉO DỮ LIỆU SẢN PHẨM TỪ DB POSTGRESQL
// =====================================================================================
global $conn;
// Dò tìm và include file cấu hình nếu file header chưa kéo vào
if (!isset($conn) || $conn === null) {
    if (file_exists('config/db.php')) include_once 'config/db.php';
    elseif (file_exists('config/config.php')) include_once 'config/config.php';
}

$products = [];
try {
    if (isset($conn) && $conn !== null) {
        // Lấy danh sách sản phẩm đang hoạt động, xếp cái mới đăng lên đầu
        $stmt = $conn->query("SELECT * FROM products WHERE status = 1 ORDER BY id DESC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Nếu lỗi DB thì mảng trống, không làm sập giao diện web
    $products = [];
}

include_once 'includes/header.php'; 
?>

<div class="container my-4">
    <div class="row g-4">
        <div class="col-12 col-md-8 col-lg-9">
            
            <div class="p-4 mb-4 rounded-3 shadow-sm border custom-banner">
                <h1 class="display-6 fw-bold">Kimochi Shop</h1>
                <p class="fs-6 opacity-75">Cửa hàng trực tuyến bán các sản phẩm người lớn</p>
                <hr class="my-3">
                <p class="mb-0">Trạng thái: <span class="badge bg-success">Đang cập nhật (PostgreSQL)</span></p>
            </div>

            <h3 class="mb-4">
                <i class="fa-solid fa-fire text-danger me-2"></i>Sản phẩm được ưa chuộng
            </h3>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $prod): ?>
                        <?php 
                            // 1. Ảnh mặc định dự phòng ban đầu nếu không có ảnh
                            try {
                                // SỬA LẠI SQL SELECT: Chuyển hết sang gọi cột p.image_url
                                $sql = "SELECT p.id, p.name, p.price, p.description, p.image_url, p.stock, c.name AS category_name 
                                        FROM products p
                                        LEFT JOIN categories c ON p.category_id = c.id
                                        WHERE p.status = 1 AND p.stock > 0 
                                        ORDER BY p.id DESC";
                                        
                                $stmt = $conn->query($sql);
                                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (PDOException $e) {
                                // Dòng này cứu cánh để ông biết chính xác lỗi gì nếu chưa hiện:
                                echo "<div class='alert alert-danger'>Lỗi hiển thị sản phẩm: " . $e->getMessage() . "</div>";
                                $products = [];
                            }
                        ?>
                        
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0 product-card">
                                <div class="position-relative" style="padding-top: 100%; overflow: hidden; background: #222;">
                                    <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                         class="card-img-top position-absolute top-0 start-0 w-100 h-100" 
                                         style="object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($prod['name']); ?>">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold text-truncate" title="<?php echo htmlspecialchars($prod['name']); ?>">
                                        <?php echo htmlspecialchars($prod['name']); ?>
                                    </h5>
                                    
                                    <?php if (!empty($prod['description'])): ?>
                                        <p class="card-text text-muted small text-truncate-2mb mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 13px;">
                                            <?php echo htmlspecialchars($prod['description']); ?>
                                        </p>
                                    <?php endif; ?>

                                    <p class="card-text text-danger fw-bold fs-5 mt-auto mb-3">
                                        <?php echo number_format($prod['price'], 0, ',', '.'); ?> đ
                                    </p>
                                    <a href="product_detail.php?id=<?php echo $prod['id']; ?>" class="btn btn-dark w-100">
                                        <i class="fa-solid fa-cart-plus me-2"></i>Mua ngay
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    
                    <div class="col-12 text-center py-5 opacity-75">
                        <i class="fa-regular fa-folder-open display-4 mb-3"></i>
                        <p class="fs-5 fw-bold">Hiện chưa có sản phẩm nào được đăng bán!</p>
                        <p class="small text-muted">Hãy vào trang Hồ sơ để đăng mặt hàng đầu tiên của ông giáo nhé.</p>
                    </div>
                    
                <?php endif; ?>
                
            </div> </div>
    </div>
</div>

<?php 
include_once 'includes/footer.php'; 
?>