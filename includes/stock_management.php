
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
                                    $prod_stmt = $conn->prepare("SELECT * FROM products WHERE seller_id = :seller_id AND status = 1 ORDER BY id DESC");
                                    $prod_stmt->execute([':seller_id' => $user['id']]);
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