<?php
session_start();
require 'connect.php';

// Kiểm tra quyền truy cập (Chỉ cho phép QuanTriVien hoặc BienTapVien)
if (!isset($_SESSION['MaND']) || ($_SESSION['VaiTro'] != 'QuanTriVien' && $_SESSION['VaiTro'] != 'BienTapVien')) {
    header("Location: index.php");
    exit();
}

$vaiTro = $_SESSION['VaiTro'];
$maND = $_SESSION['MaND'];
$thongBao = "";

// 1. LẤY DANH SÁCH CHUYÊN MỤC ĐỂ ĐƯA VÀO DROPDOWN FORM
$stmtCM = $pdo->query("SELECT * FROM ChuyenMuc ORDER BY TenCM ASC");
$danhSachCM = $stmtCM->fetchAll();

// 2. XỬ LÝ THÊM BÀI VIẾT MỚI
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_dangbai'])) {
    $mabv = 'BV' . time();
    $tieude = trim($_POST['tieude']);
    $tomtat = trim($_POST['tomtat']);
    $noidung = trim($_POST['noidung']);
    $macm = $_POST['macm'];
    
    // Admin đăng bài sẽ lên thẳng trang chủ (DaDang), BTV phải chờ duyệt (ChoDuyet)
    $trangThai = ($vaiTro == 'QuanTriVien') ? 'DaDang' : 'ChoDuyet';

    // Xử lý upload hình ảnh minh họa
    $duongDanAnh = "";
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
        $thuMucUpload = 'upload/';
        if (!is_dir($thuMucUpload)) {
            mkdir($thuMucUpload, 0777, true);
        }
        $tenFile = time() . '_' . basename($_FILES['hinhanh']['name']);
        if (move_uploaded_file($_FILES['hinhanh']['tmp_name'], $thuMucUpload . $tenFile)) {
            $duongDanAnh = $tenFile;
        }
    }

    if (!empty($tieude) && !empty($noidung) && !empty($macm)) {
        try {
            $sql = "INSERT INTO BaiViet (MaBV, TieuDe, TomTat, NoiDung, HinhAnh, NgayTao, LuotXem, TrangThai, MaCM, MaND) 
                    VALUES (:mabv, :tieude, :tomtat, :noidung, :hinhanh, NOW(), 0, :trangthai, :macm, :mand)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'mabv' => $mabv,
                'tieude' => $tieude,
                'tomtat' => $tomtat,
                'noidung' => $noidung,
                'hinhanh' => $duongDanAnh,
                'trangthai' => $trangThai,
                'macm' => $macm,
                'mand' => $maND
            ]);
            
            $msg = ($trangThai == 'ChoDuyet') ? 'Đã gửi bài viết! Bài viết đang ở trạng thái Chờ duyệt.' : 'Đăng bài viết thành công!';
            $thongBao = "<div class='alert alert-success'>$msg</div>";
        } catch (PDOException $e) {
            $thongBao = "<div class='alert alert-danger'>Lỗi hệ thống: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $thongBao = "<div class='alert alert-warning'>Vui lòng điền đủ Chuyên mục, Tiêu đề và Nội dung!</div>";
    }
}

// 3. XỬ LÝ DUYỆT BÀI (CHỈ QUẢN TRỊ VIÊN CÓ QUYỀN DUYỆT)
if (isset($_GET['action']) && $_GET['action'] == 'duyet' && isset($_GET['id']) && $vaiTro == 'QuanTriVien') {
    try {
        $stmtDuyet = $pdo->prepare("UPDATE BaiViet SET TrangThai = 'DaDang' WHERE MaBV = :mabv");
        $stmtDuyet->execute(['mabv' => $_GET['id']]);
        header("Location: admin_baiviet.php");
        exit();
    } catch (PDOException $e) {
        $thongBao = "<div class='alert alert-danger'>Lỗi duyệt bài: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// 4. XỬ LÝ XÓA BÀI VIẾT (ADMIN XÓA TẤT CẢ, BTV CHỈ XÓA BÀI MÌNH)
if (isset($_GET['action']) && $_GET['action'] == 'xoa' && isset($_GET['id'])) {
    $maBVXoa = $_GET['id'];
    try {
        if ($vaiTro == 'QuanTriVien') {
            $stmtDel = $pdo->prepare("DELETE FROM BaiViet WHERE MaBV = :mabv");
            $stmtDel->execute(['mabv' => $maBVXoa]);
        } else {
            $stmtDel = $pdo->prepare("DELETE FROM BaiViet WHERE MaBV = :mabv AND MaND = :mand");
            $stmtDel->execute(['mabv' => $maBVXoa, 'mand' => $maND]);
        }
        header("Location: admin_baiviet.php");
        exit();
    } catch (PDOException $e) {
        $thongBao = "<div class='alert alert-danger'>Không thể xóa bài viết này vì có dữ liệu bình luận liên quan!</div>";
    }
}

// 5. TRUY VẤN BÀI VIẾT THEO QUYỀN
try {
    if ($vaiTro == 'QuanTriVien') {
        $sqlList = "SELECT bv.*, cm.TenCM, nd.HoTen AS TacGia 
                    FROM BaiViet bv 
                    LEFT JOIN ChuyenMuc cm ON bv.MaCM = cm.MaCM 
                    LEFT JOIN NguoiDung nd ON bv.MaND = nd.MaND 
                    ORDER BY bv.NgayTao DESC";
        $stmtList = $pdo->query($sqlList);
    } else {
        $sqlList = "SELECT bv.*, cm.TenCM, nd.HoTen AS TacGia 
                    FROM BaiViet bv 
                    LEFT JOIN ChuyenMuc cm ON bv.MaCM = cm.MaCM 
                    LEFT JOIN NguoiDung nd ON bv.MaND = nd.MaND 
                    WHERE bv.MaND = :mand 
                    ORDER BY bv.NgayTao DESC";
        $stmtList = $pdo->prepare($sqlList);
        $stmtList->execute(['mand' => $maND]);
    }
    $danhSachBV = $stmtList->fetchAll();
} catch (PDOException $e) {
    $danhSachBV = [];
    $thongBao .= "<div class='alert alert-danger'>Lỗi lấy danh sách bài viết: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Bài Viết</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar điều hướng -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="admin.php">Trang Quản Trị</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <?php if ($vaiTro == 'QuanTriVien'): ?>
                        <li class="nav-item"><a class="nav-link" href="adminchuyenmuc.php">Quản lý Chuyên mục</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link active fw-bold" href="admin_baiviet.php">Quản lý Bài viết</a></li>
                    <?php if ($vaiTro == 'QuanTriVien'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin_taikhoan.php">Quản lý Người dùng</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin_binhluan.php">Quản lý Bình luận</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="admin.php">Bảng Điều Khiển</a></li>
                </ul>
                <span class="text-white me-3">
                    <?= ($vaiTro == 'QuanTriVien') ? '👑 Admin' : '✍️ BTV' ?>: <strong><?= htmlspecialchars($_SESSION['HoTen']) ?></strong>
                </span>
                <a href="logout.php" class="btn btn-danger btn-sm">Đăng xuất</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-primary fw-bold mb-0">📝 QUẢN LÝ BÀI VIẾT</h3>
            <span class="badge bg-secondary fs-6">Quyền: <?= ($vaiTro == 'QuanTriVien') ? 'Quản Trị Viên (Toàn quyền)' : 'Biên Tập Viên (Bài cá nhân)' ?></span>
        </div>
        
        <?= $thongBao ?>

        <div class="row">
            <!-- Form Đăng Bài Viết -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white fw-bold">
                        Đăng Bài Viết Mới
                    </div>
                    <div class="card-body">
                        <form action="admin_baiviet.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Chuyên mục <span class="text-danger">*</span></label>
                                <select name="macm" class="form-select" required>
                                    <option value="">-- Chọn chuyên mục --</option>
                                    <?php foreach ($danhSachCM as $cm): ?>
                                        <option value="<?= htmlspecialchars($cm['MaCM']) ?>"><?= htmlspecialchars($cm['TenCM']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tiêu đề bài viết <span class="text-danger">*</span></label>
                                <input type="text" name="tieude" class="form-control" placeholder="Nhập tiêu đề..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tóm tắt ngắn</label>
                                <textarea name="tomtat" class="form-control" rows="2" placeholder="Tóm tắt nội dung chính..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nội dung chi tiết <span class="text-danger">*</span></label>
                                <textarea name="noidung" class="form-control" rows="6" placeholder="Nội dung bài viết..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Hình ảnh minh họa</label>
                                <input type="file" name="hinhanh" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" name="btn_dangbai" class="btn btn-success w-100 fw-bold">Đăng Bài Viết</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Bảng Danh Sách Bài Viết -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white fw-bold">
                        Danh Sách Bài Viết <?= ($vaiTro == 'QuanTriVien') ? '(Tất cả)' : '(Cá nhân)' ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Tiêu đề & Ngày tạo</th>
                                        <th>Chuyên mục</th>
                                        <th>Tác giả</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($danhSachBV as $bv): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-primary"><?= htmlspecialchars($bv['TieuDe']) ?></strong><br>
                                                <small class="text-muted">🕒 <?= $bv['NgayTao'] ?></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info text-dark"><?= htmlspecialchars($bv['TenCM'] ?? 'Chưa rõ') ?></span>
                                            </td>
                                            <td class="text-center fw-semibold">
                                                <?= htmlspecialchars($bv['TacGia'] ?? 'Ẩn danh') ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if (($bv['TrangThai'] ?? '') == 'DaDang'): ?>
                                                    <span class="badge bg-success">Đã đăng</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <!-- NÚT SỬA BÀI VIẾT NẰM Ở ĐÂY -->
                                                <a href="admin_suabaiviet.php?id=<?= $bv['MaBV'] ?>" class="btn btn-warning btn-sm me-1 fw-bold">Sửa</a>

                                                <!-- Nút Duyệt dành cho Admin khi bài đang Chờ duyệt -->
                                                <?php if ($vaiTro == 'QuanTriVien' && ($bv['TrangThai'] ?? '') == 'ChoDuyet'): ?>
                                                    <a href="admin_baiviet.php?action=duyet&id=<?= $bv['MaBV'] ?>" 
                                                       class="btn btn-success btn-sm me-1 fw-bold">Duyệt</a>
                                                <?php endif; ?>

                                                <a href="admin_baiviet.php?action=xoa&id=<?= $bv['MaBV'] ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Anh có chắc chắn muốn xóa bài viết này không?');">Xóa</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($danhSachBV)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Chưa có bài viết nào.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="admin.php" class="btn btn-outline-secondary">&larr; Quay lại Bảng điều khiển</a>
        </div>
    </div>

</body>
</html>