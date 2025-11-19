/* admin/assets/js/admin.js */

document.addEventListener('DOMContentLoaded', () => {

    // --- 1. LOGIC TOGGLE THEME ---
    const themeToggle = document.getElementById('theme-toggle');
    const docElement = document.documentElement; // Lấy thẻ <html>

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            // Chuyển đổi (toggle) class 'dark-mode' trên thẻ <html>
            docElement.classList.toggle('dark-mode');

            // Lưu lựa chọn mới vào localStorage
            if (docElement.classList.contains('dark-mode')) {
                localStorage.setItem('adminTheme', 'dark');
            } else {
                localStorage.setItem('adminTheme', 'light');
            }
        });
    }
    // --- (HẾT) LOGIC TOGGLE THEME ---


    // --- 2. LOGIC BẢNG DỮ LIỆU (DataTables) ---
    const dataTable = document.getElementById('adminDataTable');
    
    // Chỉ chạy nếu tìm thấy bảng trên trang này
    if (dataTable) {
        
        // Kích hoạt thư viện Simple-DataTables
        new simpleDatatables.DataTable(dataTable, {
            searchable: true,  // Bật tìm kiếm
            sortable: true,    // Bật sắp xếp
            
            // Phân trang
            perPage: 10, // 10 mục mỗi trang
            perPageSelect: [10, 25, 50, 100], // Cho phép chọn số mục
            
            // Việt hóa các nhãn
            labels: {
                placeholder: "Tìm kiếm...",
                perPage: "{select} mục mỗi trang",
                noRows: "Không tìm thấy dữ liệu",
                info: "Hiển thị {start} đến {end} của {rows} mục",
            }
        });
    }
    // --- (HẾT) LOGIC BẢNG DỮ LIỆU ---

});     