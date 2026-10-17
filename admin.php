<?php
session_start();

// Kiểm tra quyền: Phải là QuanTriVien hoặc BienTapVien mới được vào
if (!isset($_SESSION['MaND']) || ($_SESSION['VaiTro'] != 'QuanTriVien' && $_SESSION['VaiTro'] != 'BienTapVien')) {
    die("<div style='text-align:center; margin-top:50px; color:red;'>
            <h2>CẢNH BÁO: ANH KHÔNG CÓ QUYỀN TRUY CẬP KHU VỰC NÀY!</h2>
            <a href='index.php'>Quay về trang chủ</a>
         </div>");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bảng Điều Khiển - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Thanh Menu Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="admin.php">⚙️ ADMIN PANEL</a>
            <div class="d-flex text-white align-items-center">
                <span class="me-3">Xin chào: <strong><?= htmlspecialchars($_SESSION['HoTen']) ?></strong> (<?= $_SESSION['VaiTro'] ?>)</span>
                <a href="logout.php" class="btn btn-sm btn-light">Đăng xuất</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-5">
        <h2 class="mb-4">Anh muốn quản lý mục nào hôm nay?</h2>
        
        <div class="row">
            <!-- Quản lý chuyên mục (Chỉ Admin thấy) -->
            <?php if($_SESSION['VaiTro'] == 'QuanTriVien'): ?>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-primary h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">📂 Quản lý Chuyên mục</h5>
                        <p class="card-text">Thêm, sửa, xóa các chuyên mục.</p>
                        <a href="adminchuyenmuc.php" class="btn btn-light btn-sm fw-bold">Truy cập</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quản lý bài viết (Admin & BTV đều thấy) -->
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-success h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">📝 Quản lý Bài viết</h5>
                        <p class="card-text">Đăng bài, duyệt bài, sửa tin tức.</p>
                        <!-- TRỎ CHÍNH XÁC VÀO FILE admin_baiviet.php -->
                        <a href="admin_baiviet.php" class="btn btn-light btn-sm fw-bold">Truy cập</a>
                    </div>
                </div>
            </div>

            <!-- Quản lý Tài khoản (Chỉ Admin thấy) -->
            <?php if($_SESSION['VaiTro'] == 'QuanTriVien'): ?>
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-warning text-dark h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">👥 Quản lý Người dùng</h5>
                        <p class="card-text">Cấp quyền, khóa tài khoản.</p>
                        <a href="admin_taikhoan.php" class="btn btn-light btn-sm fw-bold">Truy cập</a>
                    </div>
                </div>
            </div>

            <!-- Quản lý Bình luận (Chỉ Admin thấy) -->
            <div class="col-md-3 mb-3">
                <div class="card text-white bg-secondary h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">💬 Quản lý Bình luận</h5>
                        <p class="card-text">Xóa bình luận vi phạm.</p>
                        <a href="admin_binhluan.php" class="btn btn-light btn-sm fw-bold">Truy cập</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-outline-dark">🌍 Ra xem trang chủ của Khách</a>
        </div>
    </div>

</body>
</html>