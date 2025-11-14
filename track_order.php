<form method="GET" action="">
  <input type="text" name="ma_don" placeholder="Nhập mã đơn hàng" required>
  <button type="submit">Tra cứu</button>
</form>

<?php
if (isset($_GET['ma_don'])) {
  $ma_don = mysqli_real_escape_string($conn, $_GET['ma_don']);
  $sql = "SELECT * FROM thongtin WHERE ma_don='$ma_don'";
  $res = mysqli_query($conn, $sql);
  if (mysqli_num_rows($res) > 0) {
      $row = mysqli_fetch_assoc($res);
      echo "<h3>Thông tin đơn hàng:</h3>";
      echo "Mã đơn: " . $row['ma_don'] . "<br>";
      echo "Tên: " . $row['name'] . "<br>";
      echo "Địa chỉ: " . $row['address'] . "<br>";
      echo "Số điện thoại: " . $row['phone'] . "<br>";
      echo "Hình thức thanh toán: " . $row['payment_method'] . "<br>";
      echo "Trạng thái: " . $row['status'] . "<br>";
  } else {
      echo "<p>Không tìm thấy đơn hàng với mã này.</p>";
  }
}
?>
