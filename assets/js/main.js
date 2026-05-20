// Chờ toàn bộ trang web tải xong
document.addEventListener("DOMContentLoaded", function () {
    console.log("Kimochi Shop Script Loaded Successfully");

    // Xử lý tự động sửa lỗi layout khi click mở bảng Tìm kiếm
    const searchDropdown = document.getElementById("searchDropdown");
    if (searchDropdown) {
        searchDropdown.addEventListener("shown.bs.dropdown", function () {
            // Ép ô input tự động lấy tiêu điểm khi bảng hạ xuống
            const inputField = document.querySelector(".search-box-dropdown input");
            if (inputField) {
                inputField.focus();
            }
        });
    }
});