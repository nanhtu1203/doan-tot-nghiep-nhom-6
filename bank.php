<?php
// bank.php
session_start();

// Lấy tên tài khoản người đăng nhập từ session
// Tùy bạn đặt key, mình thử lần lượt: user_name, ho_ten
$customerName = $_SESSION['user_name'] ?? $_SESSION['ho_ten'] ?? 'Quý khách';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Thanh toán ngân hàng</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4" style="max-width:700px">
  <h3 class="mb-3">Thanh toán chuyển khoản</h3>

  <div class="card p-3 mb-3">
    <h5 class="mb-3">Thông tin tài khoản nhận</h5>
    <p><b>Ngân hàng:</b> Vietcombank – Chi nhánh Hà Nội</p>
    <p><b>Chủ tài khoản:</b> CÔNG TY TNHH THẾ GIỚI GIẦY</p>
    <p><b>Số tài khoản:</b> 0123456789</p>
  </div>

  <div class="card p-3 mb-3">
    <h5 class="mb-3">Thông tin của bạn</h5>
    <p><b>Tên tài khoản (người chuyển):</b> <?php echo htmlspecialchars($customerName); ?></p>
    <p class="text-muted small mb-0">
      Vui lòng dùng đúng <b>tên tài khoản/người gửi</b> như trên để đối soát nhanh hơn.
    </p>
  </div>

  <div class="card p-3 mb-3">
    <h5 class="mb-3">Nội dung chuyển khoản gợi ý</h5>
    <p>
      <b>Nội dung:</b>
      <?php echo 'Thanh toán đơn hàng - ' . htmlspecialchars($customerName); ?>
    </p>
    <p class="text-muted small mb-0">
      Bạn có thể thêm mã đơn hàng (nếu có) vào cuối nội dung để dễ tra cứu.
    </p>
  </div>

  <a href="trangchu.php" class="btn btn-dark w-100 mt-2">Tôi đã chuyển khoản xong</a>
</div>

</body>
</html>
