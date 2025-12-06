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
// Nếu có tham số delete_id thì xoá sản phẩm
if (isset($_GET['delete_id'])) {
    $pid = (int)$_GET['delete_id'];

    // Lấy ảnh để xoá file nếu có
    $stmImg = $conn->prepare("SELECT image_main FROM products WHERE id = ? AND seller_id = ?");
    $stmImg->execute([$pid, $seller_id]);
    $imgRow = $stmImg->fetch(PDO::FETCH_ASSOC);

    // Xoá dữ liệu trong DB
    $stmDel = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
    $stmDel->execute([$pid, $seller_id]);

    // Xoá file ảnh (nếu tồn tại)
    if ($imgRow && !empty($imgRow['image_main']) && file_exists($imgRow['image_main'])) {
        unlink($imgRow['image_main']);
    }

    // Quay lại trang danh sách
    header("Location: seller_products.php?msg=Đã xoá sản phẩm #$pid");
    exit;
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

    // Sau khi lưu xong đưa thẳng ra trangchu.php để xem sản phẩm đã public
    header("Location: trangchu.php");
    exit;
}

// Lấy danh sách sản phẩm của người bán đang đăng nhập để liệt kê bên dưới
$stList = $conn->prepare("SELECT id, name, price, image_main FROM products WHERE seller_id = ? ORDER BY id DESC");
$stList->execute([$seller_id]);
$allProducts = $stList->fetchAll(PDO::FETCH_ASSOC);

// Check message qua GET (trong trường hợp vẫn muốn dùng)
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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap (tùy chọn) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS giao diện dashboard người bán -->
    <style>
        :root{
            --bg: #f3f4f6;
            --card-bg: #ffffff;
            --border: #e5e7eb;
            --primary: #111827;
            --accent: #ff6b00; /* màu cam cho Thế Giới Giày */
        }

        *{
            box-sizing: border-box;
        }

        body{
            margin:0;
            padding:20px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--primary);
        }

        .page-wrapper{
            max-width: 1100px;
            margin: 0 auto;
        }

        .headerRow{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            padding:16px 20px;
            background:#111827;
            color:#fff;
            border-radius:14px;
            box-shadow:0 12px 30px rgba(15,23,42,0.25);
            margin-bottom:24px;
        }
        .headerLeft h2{
            margin:0;
            font-size:20px;
            font-weight:700;
        }
        .headerLeft small{
            font-size:13px;
            opacity:0.85;
        }
        .headerRight a{
            font-size:13px;
            color:#f9fafb;
            text-decoration:none;
            font-weight:600;
            margin-left:16px;
            padding:6px 10px;
            border-radius:999px;
            border:1px solid rgba(249,250,251,0.3);
            transition:0.2s;
            background: rgba(15,23,42,0.4);
        }
        .headerRight a:hover{
            background:#f9fafb;
            color:#111827;
        }

        .blockCard{
            background:var(--card-bg);
            border:1px solid var(--border);
            border-radius:14px;
            padding:18px 18px 20px;
            box-shadow:0 10px 25px rgba(15,23,42,0.08);
            margin-bottom:20px;
        }
        .blockCard h3{
            margin-top:0;
            margin-bottom:14px;
            font-size:17px;
            font-weight:600;
        }

        .msg{
            color:#16a34a;
            font-size:14px;
            margin-bottom:10px;
        }

        label{
            display:block;
            margin-bottom:4px;
            margin-top:10px;
            font-size:13px;
            font-weight:600;
            color:#374151;
        }
        input[type=text],
        input[type=number],
        input[type=file]{
            width:100%;
            max-width:360px;
            padding:7px 10px;
            border:1px solid #d1d5db;
            border-radius:8px;
            font-size:14px;
            transition:0.2s;
            background:#f9fafb;
        }
        input[type=text]:focus,
        input[type=number]:focus,
        input[type=file]:focus{
            outline:none;
            border-color:#111827;
            background:#ffffff;
            box-shadow:0 0 0 3px rgba(15,23,42,0.08);
        }

        .size-box{
            display:inline-flex;
            align-items:center;
            gap:4px;
            margin-right:10px;
            margin-top:4px;
            font-size:13px;
        }
        .size-box input{
            width:auto;
        }

        .productImgPreview{
            max-width:110px;
            display:block;
            margin-bottom:8px;
            border:1px solid #e5e7eb;
            border-radius:8px;
        }

        .saveBtn{
            background:#111827;
            color:#fff;
            border:0;
            padding:9px 18px;
            border-radius:999px;
            font-size:14px;
            font-weight:600;
            cursor:pointer;
            margin-top:16px;
            transition:0.2s;
        }
        .saveBtn:hover{
            background:#fff;
            color:#111827;
            border:1px solid #111827;
        }

        /* nhóm checkbox danh mục */
        .cat-group{
            display:flex;
            flex-direction:column;
            gap:4px;
            margin-top:4px;
        }
        .cat-item{
            font-size:13px;
            display:flex;
            align-items:center;
            gap:6px;
        }
        .cat-item input{
            width:auto;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
            font-size:13px;
        }
        table thead{
            background:#f9fafb;
        }
        th,td{
            border:1px solid #e5e7eb;
            padding:8px 10px;
            vertical-align:middle;
        }
        th{
            font-weight:600;
            color:#374151;
        }
        td a{
            color:var(--accent);
            font-weight:600;
            text-decoration:none;
            font-size:13px;
        }
        td a:hover{
            text-decoration:underline;
        }

        /* overlay xác nhận đăng xuất */
        #logoutBox {
            display:none;
            position:fixed;left:0;top:0;width:100%;height:100%;
            background:rgba(0,0,0,0.55);
            align-items:center;justify-content:center;
            z-index:9999;
        }
        #logoutBox .wrap {
            background:#ffffff;
            padding:18px 20px;
            border-radius:12px;
            max-width:300px;
            text-align:center;
            box-shadow:0 20px 40px rgba(0,0,0,0.25);
        }
        #logoutBox p{
            margin:0 0 14px;
            font-size:14px;
            color:#111827;
        }
        .logoutBtn{
            background:var(--accent);
            color:#fff;
            border:0;
            padding:7px 14px;
            cursor:pointer;
            margin:0 6px;
            border-radius:999px;
            font-size:13px;
            font-weight:600;
            transition:0.2s;
        }
        .logoutBtn:nth-child(2){
            background:#e5e7eb;
            color:#111827;
        }
        .logoutBtn:hover{
            transform:translateY(-1px);
            box-shadow:0 6px 16px rgba(0,0,0,0.15);
        }

        /* Responsive */
        @media (max-width: 768px){
            body{
                padding:12px;
            }
            .headerRow{
                flex-direction:column;
                align-items:flex-start;
            }
            input[type=text],
            input[type=number],
            input[type=file]{
                max-width:100%;
            }
        }
    </style>
</head>
<body>

<div class="page-wrapper">

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

            <!-- DANH MỤC DẠNG CHECKBOX (chọn 1) -->
            <label>Danh mục sản phẩm</label>
            <div class="cat-group">
                <label class="cat-item">
                    <input
                        type="checkbox"
                        name="category"
                        value="giay-the-thao-da"
                        <?php echo ($editProduct['category'] === 'giay-the-thao-da') ? 'checked' : ''; ?>>
                    Giày thể thao làm bằng da
                </label>

                <label class="cat-item">
                    <input
                        type="checkbox"
                        name="category"
                        value="giay-the-thao-da-tong-hop"
                        <?php echo ($editProduct['category'] === 'giay-the-thao-da-tong-hop') ? 'checked' : ''; ?>>
                    Giày thể thao làm bằng da tổng hợp
                </label>

                <label class="cat-item">
                    <input
                        type="checkbox"
                        name="category"
                        value="giay-the-thao-vai-cao-cap"
                        <?php echo ($editProduct['category'] === 'giay-the-thao-vai-cao-cap') ? 'checked' : ''; ?>>
                    Giày thể thao làm bằng vải cao cấp
                </label>

                <label class="cat-item">
                    <input
                        type="checkbox"
                        name="category"
                        value="hang-moi-ve"
                        <?php echo ($editProduct['category'] === 'hang-moi-ve') ? 'checked' : ''; ?>>
                    Hàng mới về
                </label>
            </div>

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
    <div class="blockCard">
        <h3>Sản phẩm của shop</h3>
            <table>
                <thead>
                <tr>
                    <th width="60">ID</th>
                    <th width="80">Ảnh</th>
                    <th>Tên</th>
                    <th width="90">Giá</th>
                    <th width="120">Thao tác</th>
                </tr>
                </thead>

                <tbody>
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

                    <td><?php echo htmlspecialchars(number_format($p['price'])); ?></td>

                    <!-- CỘT THAO TÁC TÁCH RIÊNG -->
                    <td style="text-align:center;">
                        <a href="seller_products.php?edit_id=<?php echo urlencode($p['id']); ?>" style="color:#007bff;">
                            Sửa
                        </a>
                        |
                        <a href="seller_products.php?delete_id=<?php echo urlencode($p['id']); ?>"
                        onclick="return confirm('Bạn có chắc muốn xoá sản phẩm này?');"
                        style="color:red;">
                        Xoá
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

    </div>

</div> <!-- /.page-wrapper -->

<!-- overlay xác nhận đăng xuất -->
<div id="logoutBox">
    <div class="wrap">
        <p>Bạn có muốn đăng xuất không?</p>
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

document.getElementById('confirmLogoutBtn').addEventListener('click', function () {
    window.location = 'seller_logout.php'; 
});

document.getElementById('cancelLogoutBtn').addEventListener('click', function () {
    hideLogout();
});

// Chỉ cho phép chọn 1 danh mục (checkbox nhưng hành vi như radio)
document.querySelectorAll('.cat-group input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', () => {
        if (cb.checked) {
            document.querySelectorAll('.cat-group input[type="checkbox"]').forEach(other => {
                if (other !== cb) other.checked = false;
            });
        }
    });
});
</script>

</body>
</html>
