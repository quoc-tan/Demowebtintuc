<?php
session_start();
require 'connect.php';

// 1. Lấy danh sách chuyên mục từ Database để hiển thị lên thanh Menu
$sqlCM = "SELECT * FROM ChuyenMuc";
$stmtCM = $pdo->query($sqlCM);
$danhSachCM = $stmtCM->fetchAll();

// 2. Lấy danh sách bài viết (Có lọc theo chuyên mục nếu anh bấm vào menu)
if (isset($_GET['macm'])) {
    $maCM = $_GET['macm'];
    $sql = "SELECT MaBV, TieuDe, TomTat, NgayTao FROM BaiViet WHERE TrangThai = 'DaDang' AND MaCM = :macm ORDER BY NgayTao DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['macm' => $maCM]);
} else {
    // Nếu không bấm chọn chuyên mục thì hiển thị tất cả bài viết
    $sql = "SELECT MaBV, TieuDe, TomTat, NgayTao FROM BaiViet WHERE TrangThai = 'DaDang' ORDER BY NgayTao DESC";
    $stmt = $pdo->query($sql);
}
$danhSachBaiViet = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ - Website Tin Tức</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Thanh Menu -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">TinTức24h</a>
            <div class="collapse navbar-collapse" id="navbarNav">
                
                <!-- Hiển thị Danh mục động từ Database -->
                <ul class="navbar-nav me-auto">
                    <!-- Nút Tất cả -->
                    <li class="nav-item">
                        <a class="nav-link <?= !isset($_GET['macm']) ? 'active fw-bold' : '' ?>" href="index.php">Tất cả tin</a>
                    </li>
                    
                    <!-- Vòng lặp in các chuyên mục -->
                    <?php foreach ($danhSachCM as $cm): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (isset($_GET['macm']) && $_GET['macm'] == $cm['MaCM']) ? 'active fw-bold text-info' : '' ?>" 
                               href="index.php?macm=<?= $cm['MaCM'] ?>">
                               <?= htmlspecialchars($cm['TenCM']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="d-flex align-items-center">
                    <form class="d-flex me-4" action="timkiem.php" method="GET">
                        <input class="form-control me-2" type="search" name="tukhoa" placeholder="Tìm kiếm tin tức..." required>
                        <button class="btn btn-outline-light" type="submit">Tìm</button>
                    </form>
                    
                    <!-- KHU VỰC HIỂN THỊ TÀI KHOẢN VÀ CÁC NÚT THAO TÁC -->
                    <div class="d-flex align-items-center">
                        <?php if (isset($_SESSION['MaND'])): ?>
                            <span class="text-white me-3 text-nowrap">Xin chào, <strong><?= htmlspecialchars($_SESSION['HoTen']) ?></strong>!</span>
                            
                            <!-- NÚT TIN ĐÃ LƯU -->
                            <a href="baiviet_yeuthich.php" class="btn btn-outline-light btn-sm me-2 text-nowrap">❤️ Tin đã lưu</a>

                            <!-- NÚT ĐỔI MẬT KHẨU -->
                            <a href="doimatkhau.php" class="btn btn-outline-warning btn-sm me-2 text-nowrap">🔑 Đổi MK</a>

                            <!-- PHÂN BIỆT NÚT DÀNH CHO ADMIN VÀ BIÊN TẬP VIÊN -->
                            <?php if (isset($_SESSION['VaiTro'])): ?>
                                <?php if ($_SESSION['VaiTro'] == 'QuanTriVien'): ?>
                                    <a href="admin.php" class="btn btn-warning btn-sm me-2 fw-bold text-nowrap">⚙️ Quản Trị</a>
                                <?php elseif ($_SESSION['VaiTro'] == 'BienTapVien'): ?>
                                    <a href="admin_baiviet.php" class="btn btn-info btn-sm me-2 fw-bold text-nowrap text-dark">✍️ Biên Tập</a>
                                <?php endif; ?>
                            <?php endif; ?>

                            <a href="logout.php" class="btn btn-danger btn-sm text-nowrap">Đăng xuất</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-light btn-sm me-2 text-nowrap">Đăng nhập</a>
                            <a href="register.php" class="btn btn-primary btn-sm text-nowrap">Đăng ký</a>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </nav>

    <!-- Nội dung chính -->
    <div class="container mt-4 mb-5">
        <h3 class="mb-4 fw-bold text-primary">📰 Tin Tức Cập Nhật</h3>
        <div class="row">
            
            <?php if (count($danhSachBaiViet) > 0): ?>
                <?php foreach ($danhSachBaiViet as $baiViet): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title fw-bold text-primary"><?= htmlspecialchars($baiViet['TieuDe']) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted small">🕒 <?= $baiViet['NgayTao'] ?></h6>
                                <p class="card-text text-secondary mt-3"><?= htmlspecialchars($baiViet['TomTat']) ?></p>
                            </div>
                            <div class="card-footer bg-white border-0 pb-3">
                                <a href="chitiet.php?id=<?= $baiViet['MaBV'] ?>" class="btn btn-outline-primary btn-sm">Đọc tiếp &rarr;</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">Hiện chưa có bài viết nào trong chuyên mục này!</div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>