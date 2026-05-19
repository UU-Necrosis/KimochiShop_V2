<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Include database connection and utility functions
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kimochi Shop</title>
        <link href="assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet" /*Bootstrap_CSS*/>
        <link href="assets/vendor/fontawesome/css/all.min.css" rel="stylesheet" /*FontAwesome_CSS*/>
        <link href="assets/css/main.css" rel="stylesheet" /*Custom_CSS*/>
    <style>
            body { background-color: var(--bg); color: var(--text); }
            .navbar-brand { font-weight: bold; color: var(--brand) !important; }
            
            /* Định dạng riêng cho cụm Icon Tiện Ích */
            .nav-icons-group {
                display: flex;
                align-items: center;
                gap: 18px; /* Tạo khoảng cách rộng rãi giữa các icon */
                margin-left: 20px; /* Đẩy cụm icon tách biệt khỏi cụm nút chữ */
            }
            
            .nav-icon-link {
                color: #f8f9fa !important; /* Đổi màu trắng sáng cho rõ nét */
                font-size: 1.25rem; /* Phóng to icon lên nhìn cho dễ */
                transition: all 0.2s ease;
                text-decoration: none;
            }
            
            .nav-icon-link:hover {
                color: var(--brand) !important; /* Di chuột vào đổi màu hồng/xanh tùy theme */
                transform: scale(1.1); /* Hiệu ứng phóng to nhẹ khi hover */
            }
        </style>
    </head>
    <body>

        <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="fa-solid fa-store me-2"></i>Kimochi Shop
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <div class="ms-auto d-flex flex-column flex-lg-row align-items-center">
                        
                        <ul class="navbar-nav align-items-center">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li class="nav-item text-white me-3">
                                    Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                                </li>
                                <li class="nav-item">
                                    <a class="btn btn-outline-danger btn-sm" href="auth.php?action=logout">Đăng xuất</a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item me-2">
                                    <a class="btn btn-outline-light btn-sm" href="register.php">Đăng nhập</a>
                                </li>
                                <li class="nav-item">
                                    <a class="btn btn-danger btn-sm" href="register.php?tab=register">Đăng ký</a>
                                </li>
                            <?php endif; ?>
                        </ul>

                        <hr class="text-white-50 d-lg-none my-2 w-100">

                        <div class="nav-icons-group">
                            <div class="dropdown">
                                <a class="nav-icon-link" href="#" title="Tìm kiếm" id="searchDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end p-3 shadow border-0 search-box-dropdown" aria-labelledby="searchDropdown">
                                    <form action="index.php" method="GET" class="d-flex flex-column gap-2">
                                        <label class="form-label small fw-bold text-secondary mb-1">TÌM KIẾM SẢN PHẨM</label>
                                        <div class="input-group">
                                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Nhập tên sản phẩm cần tìm..." required>
                                            <button class="btn btn-brand btn-sm" type="submit">
                                                <i class="fa-solid fa-magnifying-glass text-white"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <a class="nav-icon-link position-relative" href="#" title="Thông báo">
                                <i class="fa-solid fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                            </a>
                            
                            <a class="nav-icon-link" href="#" title="Danh mục phụ">
                                <i class="fa-solid fa-bars"></i>
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </nav>