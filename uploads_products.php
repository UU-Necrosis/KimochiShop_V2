<?php
// handle_add_product.php
session_start();

// 1. Kiểm tra xem user có quyền seller/admin không (Chặn cửa)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'seller' && $_SESSION['role'] !== 'admin')) {
    header("Location: index.php"); // Hoặc trang báo lỗi quyền
    exit();
}

require_once 'config/db_connect.php'; // Đường dẫn file kết nối DB của ông

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // A. Hứng dữ liệu text cơ bản
    $name = trim($_POST['name']);
    $description = trim($_POST['description']); // Trường mới
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $seller_id = $_SESSION['user_id']; // Lấy ID của người đang đăng

    // Validate cơ bản (ông cần làm kỹ hơn phần này)
    if (empty($name) || empty($description) || $price < 0 || $stock < 1) {
        die("Vui lòng nhập đầy đủ thông tin hợp lệ.");
    }

    // B. XỬ LÝ UPLOAD ẢNH (Phần quan trọng nhất)
    $image_path_for_db = null; // Biến này sẽ lưu tên file để ghi vào DB

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $file = $_FILES['product_image'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Lấy đuôi file (jpg, png...)
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        // Các đuôi file cho phép
        $allowedExts = array("jpg", "jpeg", "png", "webp");

        if (in_array($fileExt, $allowedExts)) {
            if ($fileSize < 2097152) { // Giới hạn 2MB (2 * 1024 * 1024)
                // Tạo tên file mới duy nhất: ví dụ prod_65f12a34b5e67.jpg
                $newFileName = "prod_" . uniqid() . "." . $fileExt;
                
                // Thư mục lưu ảnh thật trên server. Ông nhớ tạo thư mục này!
                $uploadDirectory = "assets/uploads/products/"; 
                
                // Tạo thư mục nếu chưa tồn tại
                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0777, true);
                }

                $destination = $uploadDirectory . $newFileName;

                // Di chuyển file từ thư mục tạm sang thư mục thật
                if (move_uploaded_file($fileTmpName, $destination)) {
                    // Thành công! Lưu đường dẫn tương đối để ghi vào DB
                    // (Ví dụ lưu là 'assets/uploads/products/prod_abc.jpg' để sau này index.php gọi ra được)
                    $image_path_for_db = $destination; 
                } else {
                    die("Lỗi: Không thể di chuyển file ảnh vào thư mục lưu trữ.");
                }
            } else {
                die("Lỗi: File ảnh quá lớn (giới hạn 2MB).");
            }
        } else {
            die("Lỗi: Chỉ chấp nhận định dạng ảnh JPG, JPEG, PNG, WEBP.");
        }
    } else {
        die("Lỗi: Không nhận được ảnh sản phẩm hoặc ảnh bị lỗi.");
    }

    // C. LƯU VÀO DATABASE (Cập nhật câu SQL INSERT)
    if ($image_path_for_db !== null) {
        try {
            // Chuẩn bị câu lệnh INSERT bao gồm cả mô tả và đường dẫn ảnh
            $sql = "INSERT INTO products (seller_id, category_id, name, description, price, stock, image_path, status) 
                    VALUES (:seller_id, :category_id, :name, :description, :price, :stock, :image_path, 1)";
            
            $stmt = $conn->prepare($sql);
            
            // Bind các giá trị
            $stmt->bindParam(':seller_id', $seller_id);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description); // Mới
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->bindParam(':image_path', $image_path_for_db); // Mới
            
            $stmt->execute();

            // Thành công! Quay về trang profile hoặc kho hàng
            header("Location: profile.php?tab=stock&status=success"); 
            exit();

        } catch (PDOException $e) {
            // Nếu lỗi DB, nhớ xóa cái ảnh vừa upload đi để đỡ rác server
            if (file_exists($image_path_for_db)) {
                unlink($image_path_for_db);
            }
            die("Lỗi Database: " . $e->getMessage());
        }
    }
}
?>