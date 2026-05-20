<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<?php 
include_once 'includes/header.php'; 
?>

        <div class="container my-4">
            <div class="row g-4">
                <div class="col-12 col-md-8 col-lg-9">
                    <div class="p-4 mb-4 bg-white rounded-3 shadow-sm border custom-banner">
                        <h1 class="display-6 fw-bold text-dark">Kimochi Shop</h1>
                        <p class="fs-6 text-muted">Cửa hàng trực tuyến bán các sản phẩm người lớn</p>
                        <hr class="my-3">
                        <p class="mb-0">Trạng thái: <span class="badge bg-success">Đang cập nhật</span></p>
                    </div>

                    <h3 class="mb-4"><i class="fa-solid fa-fire text-danger me-2"></i>Sản phẩm được ưa chuộng</h3>


                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                        
                        <div class="col">
                            <div class="card h-100 shadow-sm border-0 product-card">
                                <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500" class="card-img-top" alt="Sản phẩm demo">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fw-bold">Đồng hồ Kimochi Luxury</h5>
                                    <p class="card-text text-danger fw-bold fs-5 mt-auto">999.000 đ</p>
                                    <a href="#" class="btn btn-dark w-100"><i class="fa-solid fa-cart-plus me-2"></i>Mua ngay</a>
                                </div>
                            </div>
                        </div>
                    </div>



                </div>
            </div>
        </div>

<?php 
include_once 'includes/footer.php'; 
?>