<?php
require 'connect.php';

$id = $_GET['id'] ?? 0;
if (!$id) die("Không tìm thấy sản phẩm");

$stmt = $conn->prepare("
    SELECT *
    FROM products
    WHERE id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) die("Sản phẩm không tồn tại");

$images = array_filter([
    $p['image_main'],
    $p['image_1'],
    $p['image_2'],
    $p['image_3']
]);
?>
<!DOCTYPE html>
<html lang='vi'>
<head>
<meta charset='UTF-8'>
<title><?= htmlspecialchars($p['name']) ?></title>

<style>
body {
    font-family: Arial;
    margin: 30px;
    background: #fafafa;
}
.main-image img {
    width: 420px;
    border-radius: 12px;
    border: 1px solid #ddd;
    object-fit: cover;
}
.thumb-list {
    display: flex;
    gap: 12px;
    margin-top: 10px;
}
.thumb-list img {
    width: 90px;
    height: 90px;
    border-radius: 8px;
    object-fit: cover;
    cursor: pointer;
    border: 2px solid transparent;
}
.thumb-list img:hover {
    border-color: #ff5722;
}
.product-info {
    margin-top: 20px;
}
.price {
    color: #e53935;
    font-size: 24px;
    font-weight: bold;
}
.old-price {
    color: #888;
    text-decoration: line-through;
    margin-left: 10px;
}
.btn {
    padding: 10px 16px;
    background: #ff5722;
    border-radius: 8px;
    color: white;
    text-decoration: none;
}
.btn:hover {
    opacity: 0.85;
}
</style>

<script>
function changeImage(src) {
    document.getElementById("mainImg").src = src;
}
</script>

</head>
<body>

<h2><?= htmlspecialchars($p['name']) ?></h2>

<div class="main-image">
    <img id="mainImg" src="<?= htmlspecialchars($p['image_main']) ?>">
</div>

<div class="thumb-list">
    <?php foreach ($images as $img): ?>
        <img src="<?= htmlspecialchars($img) ?>" onclick="changeImage('<?= htmlspecialchars($img) ?>')">
    <?php endforeach; ?>
</div>

<div class="product-info">
    <p class="price">
        <?= number_format($p['price'],0,',','.') ?>₫
        <?php if (!empty($p['old_price'])): ?>
            <span class="old-price"><?= number_format($p['old_price'],0,',','.') ?>₫</span>
        <?php endif; ?>
    </p>

    <p><b>Thương hiệu:</b> <?= htmlspecialchars($p['brand']) ?></p>
    <p><b>Chất liệu:</b> <?= htmlspecialchars($p['material']) ?></p>
    <p><b>Màu sắc:</b> <?= htmlspecialchars($p['color']) ?></p>
    <p><b>Họa tiết:</b> <?= htmlspecialchars($p['pattern']) ?></p>
    <p><b>Giới tính:</b> <?= htmlspecialchars($p['gender']) ?></p>
    <p><b>Size:</b> <?= htmlspecialchars($p['sizes']) ?></p>

    <p><b>Mô tả sản phẩm:</b></p>
    <p><?= nl2br(htmlspecialchars($p['description'] ?? 'Chưa có mô tả')) ?></p>

    <br>
    <a href="add_to_cart.php?id=<?= $p['id'] ?>" class="btn">Thêm vào giỏ hàng</a>
    <a href="trangchu.php" class="btn" style="background:#333;margin-left:10px;">Quay về</a>
</div>

</body>
</html>
