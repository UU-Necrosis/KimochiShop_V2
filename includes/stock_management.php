
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle small" style="background-color: #111214;">
                        <thead>
                            <tr class="text-muted text-uppercase" style="font-size: 11px;">
                                <th>Sản phẩm</th>
                                <th>Giá bán</th>
                                <th>Kho hàng</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $prod_stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = :seller_id AND status = 1 ORDER BY id DESC");                                    $prod_stmt->execute([':seller_id' => $user['id']]);
                                $my_prods = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (count($my_prods) > 0) {
                                    foreach ($my_prods as $p) {
                                        echo "<tr>";
                                        echo "<td class='fw-bold text-white'>".htmlspecialchars($p['name'])."</td>";
                                        echo "<td class='text-pink'>".number_format($p['price'])." VNĐ</td>";
                                        echo "<td>".htmlspecialchars($p['stock'])." cái</td>";
                                        echo "<td><span class='badge bg-success'>Đang bán</span></td>";
                                        echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center text-muted py-4'></td></tr>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='4' class='text-danger'>Lỗi: ".$e->getMessage()."</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
<div class="p-1">
    <div class="text-start mb-4">
        <h3 class="fw-bold text-white"><i class="fa-solid fa-boxes-stacked text-pink me-2"></i>Kho hàng của tôi</h3>
        <p class="text-muted small">Quản lý các mặt hàng bạn đang cung ứng trên hệ thống</p>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle small" style="background-color: #111214; border-radius: 6px; overflow: hidden;">
            <thead>
                <tr class="text-muted text-uppercase" style="font-size: 11px; background-color: #1e1f22; border-bottom: 2px solid #2f3136;">
                    <th class="ps-3 py-3">Sản phẩm</th>
                    <th>Giá bán</th>
                    <th>Kho hàng</th>
                    <th>Trạng thái</th>
                    <th class="pe-3 text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $seller_id = $account['id'] ?? $_SESSION['user_id'];
                    
                    // Chỉ lấy các sản phẩm chưa bị xóa mềm (status = 1)
                    $prod_stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = :seller_id AND status = 1 ORDER BY id DESC");
                    $prod_stmt->execute([':seller_id' => $seller_id]);
                    $my_prods = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($my_prods) > 0) {
                        foreach ($my_prods as $p) {
                            echo "<tr style='border-bottom: 1px solid #2f3136;'>";
                            echo "<td class='fw-bold text-white ps-3'>".htmlspecialchars($p['name'])."</td>";
                            echo "<td class='text-pink'>".number_format($p['price'])." VNĐ</td>";
                            echo "<td><span class='badge bg-dark text-light px-2 py-1.5'>".htmlspecialchars($p['stock'])." cái</span></td>";
                            echo "<td><span class='badge bg-success'>Đang bán</span></td>";
                            
                            // Form nút xóa bắn lệnh sang uploads_products.php
                            echo "<td class='pe-3 text-center'>
                                    <form action='uploads_products.php' method='POST' onsubmit='return confirm(\"Bạn chắc chắn muốn gỡ mặt hàng này xuống?\");' style='display:inline;'>
                                        <input type='hidden' name='delete_product_id' value='".$p['id']."'>
                                        <button type='submit' name='btn-delete-product' class='btn btn-sm btn-outline-danger border-0 py-1 px-2'>
                                            <i class='fa-solid fa-trash-can'></i> Xóa
                                        </button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center text-muted py-5'>Kho hàng đang trống. Hãy sang tab đăng bán sản phẩm nhé!</td></tr>";
                    }
                } catch (PDOException $e) {
                    echo "<tr><td colspan='5' class='text-danger ps-3'>Lỗi tải kho hàng: ".$e->getMessage()."</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>