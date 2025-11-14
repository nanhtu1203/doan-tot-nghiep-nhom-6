<?php
session_start();
require 'connect.php';

// Chặn người chưa đăng nhập seller
if (!isset($_SESSION['seller_id'])) {
    header("Location: seller_login.php");
    exit;
}

$seller_id = $_SESSION['seller_id'];

// Biến thông báo
$message = "";

// Dữ liệu mặc định cho form (khi thêm mới)
$editProduct = [
    'id'            => '',
    'name'          => '',
    'brand'         => 'TheGioiGiay',
    'price'         => '',
    'old_price'     => '',
    'sale_percent'  => '',
    'category'      => '',
    'gender'        => '',
    'material'      => '',
    'color'         => '',
    'pattern'       => '',
    'sizes'         => '',
    'image_main'    => ''
];

// Nếu có tham số edit_id thì load sản phẩm để sửa
if (isset($_GET['edit_id'])) {
    $pid = (int)$_GET['edit_id'];

    $st = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $st->execute([$pid, $seller_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $editProduct = $row;
    }
}

// Khi submit form Lưu sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $pid          = $_POST['product_id']    ?? '';
    $name         = $_POST['name']          ?? '';
    $brand        = $_POST['brand']         ?? 'TheGioiGiay';
    $price        = $_POST['price']         ?? 0;
    $old_price    = $_POST['old_price']     ?? null;
    $sale_percent = $_POST['sale_percent']  ?? null;
    $category     = $_POST['category']      ?? '';
    $gender       = $_POST['gender']        ?? '';
    $material     = $_POST['material']      ?? '';
    $color        = $_POST['color']         ?? '';
    $pattern      = $_POST['pattern']       ?? '';
    $sizes_arr    = $_POST['sizes']         ?? [];  // checkbox multiple
    $sizes_str    = implode(",", $sizes_arr);

    // Ảnh hiện tại (trường hợp sửa sản phẩm cũ)
    $image_main = $_POST['current_image'] ?? '';

    // Nếu upload ảnh mới thì ghi đè
    if (!empty($_FILES['image_main']['name'])) {
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        $fname  = time() . "_" . basename($_FILES['image_main']['name']);
        $target = "uploads/" . $fname;
        if (move_uploaded_file($_FILES['image_main']['tmp_name'], $target)) {
            $image_main = $target;
        }
    }

    // Nếu product_id rỗng => thêm mới
    if ($pid === '') {
        $sql = "INSERT INTO products
                (seller_id, name, brand, price, old_price, sale_percent, category, gender, material, color, pattern, sizes, image_main)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $st = $conn->prepare($sql);
        $st->execute([
            $seller_id,
            $name,
            $brand,
            $price,
            $old_price,
            $sale_percent,
            $category,
            $gender,
            $material,
            $color,
            $pattern,
            $sizes_str,
            $image_main
        ]);

        $message = "Đã thêm sản phẩm mới thành công.";
    } else {
        // Ngược lại => cập nhật sản phẩm đã có (đảm bảo đúng seller_id)
        $sql = "UPDATE products SET
                    name = ?,
                    brand = ?,
                    price = ?,
                    old_price = ?,
                    sale_percent = ?,
                    category = ?,
                    gender = ?,
                    material = ?,
                    color = ?,
                    pattern = ?,
                    sizes = ?,
                    image_main = ?
                WHERE id = ? AND seller_id = ?";
        $st = $conn->prepare($sql);
        $st->execute([
            $name,
            $brand,
            $price,
            $old_price,
            $sale_percent,
            $category,
            $gender,
            $material,
            $color,
            $pattern,
            $sizes_str,
            $image_main,
            $pid,
            $seller_id
        ]);

        $message = "Đã cập nhật sản phẩm #$pid.";
    }

    // quan trọng:
    // sau khi lưu xong đưa thẳng ra trangchu.php để xem sản phẩm đã public
    header("Location: trangchu.php");
    exit;
}

// Lấy danh sách sản phẩm của người bán đang đăng nhập để liệt kê bên dưới
$stList = $conn->prepare("SELECT id, name, price, image_main FROM products WHERE seller_id = ? ORDER BY id DESC");
$stList->execute([$seller_id]);
$allProducts = $stList->fetchAll(PDO::FETCH_ASSOC);

// Check message qua GET (trong trường hợp bạn vẫn muốn dùng, nhưng giờ redirect thẳng trangchu.php rồi nên phần này ít dùng)
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Chuẩn bị checkbox size
$current_sizes = explode(",", $editProduct['sizes']);

function checkedSize($arr, $val){
    return in_array($val, $arr) ? 'checked' : '';
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Bảng quản lý người bán</title>
<style>
body{font-family:sans-serif; line-height:1.5; background:#fafafa; padding:20px;}
label{display:block;margin-top:8px;font-size:14px;font-weight:600;}
input[type=text],
input[type=number],
input[type=file]{
    width:100%;
    max-width:320px;
    padding:6px 8px;
    border:1px solid #ccc;
    border-radius:4px;
    font-size:14px;
}
.size-box{display:inline-block;margin-right:10px;font-size:14px;}
.msg{color:green;font-size:14px;margin-bottom:10px;}
table{border-collapse:collapse;margin-top:20px;width:100%;max-width:700px;background:#fff;}
table,th,td{border:1px solid #ccc;}
th,td{padding:8px;font-size:14px;text-align:left;vertical-align:top;}
#logoutBox {
    display:none;
    position:fixed;left:0;top:0;width:100%;height:100%;
    background:rgba(0,0,0,0.6);color:#000;
    align-items:center;justify-content:center;
    z-index:9999;
}
#logoutBox .wrap {
    background:#fff;padding:20px;border-radius:8px;max-width:300px;margin:auto;text-align:center;
}
.logoutBtn{
    background:#ff6600;
    color:#fff;
    border:0;
    padding:8px 12px;
    cursor:pointer;
    margin:0 5px;
    border-radius:4px;
    font-size:14px;
    font-weight:600;
}
.logoutBtn:hover{
    background:#fff;
    color:#ff6600;
    border:1px solid #ff6600;
}
.saveBtn{
    background:#111;
    color:#fff;
    border:0;
    padding:10px 16px;
    border-radius:4px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    margin-top:12px;
}
.saveBtn:hover{
    background:#fff;
    color:#111;
    border:1px solid #111;
}
.headerRow{
    display:flex;
    justify-content:space-between;
    flex-wrap:wrap;
    align-items:flex-start;
    margin-bottom:20px;
}
.headerLeft h2{
    margin:0;
    font-size:18px;
}
.headerLeft small{
    font-size:13px;
    color:#666;
}
.headerRight a{
    font-size:13px;
    color:#ff6600;
    text-decoration:none;
    font-weight:600;
    margin-left:12px;
}
.headerRight a:hover{
    text-decoration:underline;
}
.blockCard{
    background:#fff;
    border:1px solid #ddd;
    border-radius:8px;
    padding:16px;
    max-width:720px;
    box-shadow:0 8px 24px rgba(0,0,0,.05);
}
.blockCard h3{
    margin-top:0;
    font-size:16px;
}
.productImgPreview{
    max-width:100px;
    display:block;
    margin-bottom:8px;
    border:1px solid #ddd;
    border-radius:4px;
}
</style>
</head>
<body>

<div class="headerRow">
    <div class="headerLeft">
        <h2>Quản lý sản phẩm</h2>
        <small>
          Chủ shop:
          <?php echo htmlspecialchars($_SESSION['shop_name'] ?? ''); ?>
        </small>
    </div>
    <div class="headerRight">
        <a href="trangchu.php" target="_blank">Xem trang chủ</a>
        <a onclick="showLogout()" href="#">Đăng xuất</a>
    </div>
</div>

<?php if ($message !== ""): ?>
<div class="msg"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="blockCard">
    <h3><?php echo $editProduct['id'] ? "Sửa sản phẩm #".$editProduct['id'] : "Thêm sản phẩm mới"; ?></h3>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($editProduct['id']); ?>">

        <label>Tên sản phẩm</label>
        <input type="text" name="name" required
               value="<?php echo htmlspecialchars($editProduct['name']); ?>">

        <label>Thương hiệu</label>
        <input type="text" name="brand"
               value="<?php echo htmlspecialchars($editProduct['brand']); ?>">

        <label>Giá hiện tại</label>
        <input type="number" step="1" min="0" name="price"
               value="<?php echo htmlspecialchars($editProduct['price']); ?>">

        <label>Giá cũ</label>
        <input type="number" step="1" min="0" name="old_price"
               value="<?php echo htmlspecialchars($editProduct['old_price']); ?>">

        <label>Giảm %</label>
        <input type="number" step="1" min="0" max="100" name="sale_percent"
               value="<?php echo htmlspecialchars($editProduct['sale_percent']); ?>">

        <label>Danh mục (ví dụ: the-thao, sneaker, chay-bo)</label>
        <input type="text" name="category"
               value="<?php echo htmlspecialchars($editProduct['category']); ?>">

        <label>Giới tính (ví dụ: nam, nữ, unisex)</label>
        <input type="text" name="gender"
               value="<?php echo htmlspecialchars($editProduct['gender']); ?>">

        <label>Chất liệu (ví dụ: da, vải, lưới)</label>
        <input type="text" name="material"
               value="<?php echo htmlspecialchars($editProduct['material']); ?>">

        <label>Màu sắc (ví dụ: đen, trắng, be, đỏ)</label>
        <input type="text" name="color"
               value="<?php echo htmlspecialchars($editProduct['color']); ?>">

        <label>Họa tiết (ví dụ: trơn, logo, phoi-mau)</label>
        <input type="text" name="pattern"
               value="<?php echo htmlspecialchars($editProduct['pattern']); ?>">

        <label>Size có sẵn</label>
        <div class="size-box">
            <input type="checkbox" name="sizes[]" value="35" <?php echo checkedSize($current_sizes,'35'); ?>> 35
        </div>
        <div class="size-box">
            <input type="checkbox" name="sizes[]" value="36" <?php echo checkedSize($current_sizes,'36'); ?>> 36
        </div>
        <div class="size-box">
            <input type="checkbox" name="sizes[]" value="37" <?php echo checkedSize($current_sizes,'37'); ?>> 37
        </div>
        <div class="size-box">
            <input type="checkbox" name="sizes[]" value="38" <?php echo checkedSize($current_sizes,'38'); ?>> 38
        </div>
        <div class="size-box">
            <input type="checkbox" name="sizes[]" value="39" <?php echo checkedSize($current_sizes,'39'); ?>> 39
        </div>
        <div class="size-box">
            <input type="checkbox" name="sizes[]" value="40" <?php echo checkedSize($current_sizes,'40'); ?>> 40
        </div>
        <div class="size-box">
            <input type="checkbox" name="sizes[]" value="41" <?php echo checkedSize($current_sizes,'41'); ?>> 41
        </div>
        <div class="size-box">
            <input type="checkbox" name="sizes[]" value="42" <?php echo checkedSize($current_sizes,'42'); ?>> 42
        </div>

        <label>Ảnh chính sản phẩm</label>
        <?php if ($editProduct['image_main']): ?>
            <img
              src="<?php echo htmlspecialchars($editProduct['image_main']); ?>"
              class="productImgPreview"
              alt="Ảnh sản phẩm">
        <?php endif; ?>
        <input type="file" name="image_main">
        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($editProduct['image_main']); ?>">

        <button type="submit" class="saveBtn">Lưu sản phẩm</button>
    </form>
</div>

<!-- Danh sách sản phẩm của shop -->
<div class="blockCard" style="margin-top:20px;">
    <h3>Sản phẩm của shop</h3>
    <table>
        <tr>
            <th width="60">ID</th>
            <th width="80">Ảnh</th>
            <th>Tên</th>
            <th width="90">Giá</th>
            <th width="60">Sửa</th>
        </tr>
        <?php foreach ($allProducts as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['id']); ?></td>
            <td>
                <?php if ($p['image_main']): ?>
                  <img src="<?php echo htmlspecialchars($p['image_main']); ?>"
                       style="max-width:60px;border:1px solid #ddd;border-radius:4px;">
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><?php echo htmlspecialchars($p['price']); ?></td>
            <td>
                <a href="seller_dashboard.php?edit_id=<?php echo urlencode($p['id']); ?>">Sửa</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- overlay xác nhận đăng xuất -->
<!-- overlay xác nhận đăng xuất -->
<div id="logoutBox">
    <div class="wrap">
        <p style="font-size:15px;margin-top:0;margin-bottom:12px;">Bạn có muốn đăng xuất không?</p>
        <button class="logoutBtn" id="confirmLogoutBtn">Có</button>
        <button class="logoutBtn" id="cancelLogoutBtn">Không</button>
    </div>
</div>

<script>
function showLogout(){
    document.getElementById('logoutBox').style.display='flex';
}
function hideLogout(){
    document.getElementById('logoutBox').style.display='none';
}

// khi bấm Có -> chuyển sang trang đăng nhập seller_login.php
document.getElementById('confirmLogoutBtn').addEventListener('click', function () {
    window.location = 'seller_login.php';
});

// khi bấm Không -> đóng popup
document.getElementById('cancelLogoutBtn').addEventListener('click', function () {
    hideLogout();
});
</script>


</body>
</html>
