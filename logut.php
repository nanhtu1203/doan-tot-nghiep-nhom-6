<?php
session_start();

// nếu người dùng nhấn "Có" để đăng xuất
if (isset($_POST['confirm'])) {
    session_unset();
    session_destroy();
    header("Location: login.php?message=Đăng xuất thành công");
    exit();
}

// nếu người dùng nhấn "Không"
if (isset($_POST['cancel'])) {
    header("Location: trangchu.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Xác nhận đăng xuất</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="height:100vh">
  <div class="card shadow p-4 text-center" style="max-width:400px">
    <h5 class="mb-3">Bạn có chắc muốn đăng xuất?</h5>
    <form method="post">
      <div class="d-flex justify-content-center gap-3">
        <button type="submit" name="confirm" class="btn btn-danger">Có</button>
        <button type="submit" name="cancel" class="btn btn-secondary">Không</button>
      </div>
    </form>
  </div>
</body>
</html>
