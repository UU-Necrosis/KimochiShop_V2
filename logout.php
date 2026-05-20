<?php
// Bước 1: Khởi động lại session để hệ thống biết đang làm việc với phiên của ai
session_start();
// Bước 2: Xóa sạch toàn bộ dữ liệu trong mảng $_SESSION hiện tại (Xóa user_id, username, role...)
$_SESSION = array();
// Bước 3: Hủy bỏ hoàn toàn file session lưu trên bộ nhớ Server
session_destroy();
// Bước 4: Gửi người dùng quay trở lại trang đăng nhập (auth.php)
header("Location: auth.php?tab=login");
exit();
?>