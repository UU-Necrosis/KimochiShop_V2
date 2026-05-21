<form action="handle_add_product.php" method="POST" enctype="multipart/form-data">
    
    <div class="mb-3">
        <label class="form-label text-secondary small">Tên mặt hàng</label>
        <input type="text" name="name" class="form-control input-custom" placeholder="Nhập tên mặt hàng..." required>
    </div>

    <div class="mb-3">
        <label class="form-label text-secondary small">Mô tả chi tiết sản phẩm</label>
        <textarea name="description" class="form-control input-custom" rows="5" placeholder="Nhập mô tả chi tiết để khách hàng tin tưởng..." required></textarea>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label text-secondary small">Giá tiền (VNĐ)</label>
            <input type="number" name="price" class="form-control input-custom" placeholder="0" required min="0">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label text-secondary small">Số lượng kho</label>
            <input type="number" name="stock" class="form-control input-custom" placeholder="0" required min="1">
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label text-secondary small">Hình ảnh sản phẩm (Khách hàng mua bằng mắt đấy ông!)</label>
        <input type="file" name="product_image" class="form-control input-custom" accept="image/*" required>
        <div class="form-text text-muted" style="font-size: 11px;">Nên dùng ảnh vuông, kích thước < 2MB để tải nhanh.</div>
    </div>

    <div class="d-flex justify-content-center">
        <button type="submit" class="btn btn-pink w-100 py-2 fw-bold text-uppercase mt-2">
            Lập tức đăng bán
        </button>
    </div>
</form>