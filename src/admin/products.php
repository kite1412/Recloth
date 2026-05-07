<?php
// ══════════════════════════════════════════════════════════════════
//  Konfigurasi Database
// ══════════════════════════════════════════════════════════════════
$host    = 'localhost';
$db      = 'recloth';
$user    = 'root';   // sesuaikan
$pass    = '';       // sesuaikan
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=$charset",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('<p style="color:red;font-family:sans-serif;padding:40px">Koneksi DB gagal: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

// ══════════════════════════════════════════════════════════════════
//  Helper: simpan base64 image → file di disk
// ══════════════════════════════════════════════════════════════════
function saveBase64Image(string $base64): ?string {
    if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $m)) return null;
    $ext  = $m[1] === 'jpeg' ? 'jpg' : $m[1];
    $data = base64_decode(substr($base64, strpos($base64, ',') + 1));
    if (!$data) return null;
    $dir  = __DIR__ . '/uploads/products/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = 'uploads/products/' . uniqid('prod_', true) . '.' . $ext;
    file_put_contents(__DIR__ . '/' . $filename, $data);
    return $filename;
}

// ══════════════════════════════════════════════════════════════════
//  Handle POST: Add / Edit / Delete
// ══════════════════════════════════════════════════════════════════
$action  = $_POST['action'] ?? '';
$message = '';
$msgType = 'success';

if ($action === 'add') {
    $imageData = null;
    if (!empty($_POST['image_base64']) && strpos($_POST['image_base64'], 'data:image') === 0) {
        $imageData = saveBase64Image($_POST['image_base64']);
    }
    $stmt = $pdo->prepare("
        INSERT INTO products
            (name, description, gender, condition_status, size_label,
             production_year, material, image, price, stock,
             discount_percent, category_id, created_at)
        VALUES
            (:name, :description, :gender, :condition_status, :size_label,
             :production_year, :material, :image, :price, :stock,
             :discount_percent, :category_id, NOW())
    ");
    $stmt->execute([
        ':name'             => trim($_POST['name'] ?? ''),
        ':description'      => trim($_POST['description'] ?? ''),
        ':gender'           => $_POST['gender'] ?? '',
        ':condition_status' => $_POST['condition_status'] ?? '',
        ':size_label'       => trim($_POST['size_label'] ?? ''),
        ':production_year'  => ($_POST['production_year'] !== '') ? (int)$_POST['production_year'] : null,
        ':material'         => trim($_POST['material'] ?? ''),
        ':image'            => $imageData,
        ':price'            => (float)($_POST['price'] ?? 0),
        ':stock'            => (int)($_POST['stock'] ?? 0),
        ':discount_percent' => (float)($_POST['discount_percent'] ?? 0),
        ':category_id'      => ($_POST['category_id'] !== '') ? (int)$_POST['category_id'] : null,
    ]);
    $message = 'Produk berhasil ditambahkan!';
}

if ($action === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $stmtOld = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmtOld->execute([$id]);
    $old       = $stmtOld->fetch();
    $imageData = $old['image'] ?? null;
    if (!empty($_POST['image_base64']) && strpos($_POST['image_base64'], 'data:image') === 0) {
        $newImg = saveBase64Image($_POST['image_base64']);
        if ($newImg) {
            if ($imageData && file_exists(__DIR__ . '/' . $imageData)) unlink(__DIR__ . '/' . $imageData);
            $imageData = $newImg;
        }
    }
    $stmt = $pdo->prepare("
        UPDATE products SET
            name             = :name,
            description      = :description,
            gender           = :gender,
            condition_status = :condition_status,
            size_label       = :size_label,
            production_year  = :production_year,
            material         = :material,
            image            = :image,
            price            = :price,
            stock            = :stock,
            discount_percent = :discount_percent,
            category_id      = :category_id
        WHERE id = :id
    ");
    $stmt->execute([
        ':name'             => trim($_POST['name'] ?? ''),
        ':description'      => trim($_POST['description'] ?? ''),
        ':gender'           => $_POST['gender'] ?? '',
        ':condition_status' => $_POST['condition_status'] ?? '',
        ':size_label'       => trim($_POST['size_label'] ?? ''),
        ':production_year'  => ($_POST['production_year'] !== '') ? (int)$_POST['production_year'] : null,
        ':material'         => trim($_POST['material'] ?? ''),
        ':image'            => $imageData,
        ':price'            => (float)($_POST['price'] ?? 0),
        ':stock'            => (int)($_POST['stock'] ?? 0),
        ':discount_percent' => (float)($_POST['discount_percent'] ?? 0),
        ':category_id'      => ($_POST['category_id'] !== '') ? (int)$_POST['category_id'] : null,
        ':id'               => $id,
    ]);
    $message = 'Produk berhasil diperbarui!';
}

if ($action === 'delete') {
    $id      = (int)($_POST['id'] ?? 0);
    $stmtOld = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmtOld->execute([$id]);
    $old = $stmtOld->fetch();
    if ($old && $old['image'] && file_exists(__DIR__ . '/' . $old['image'])) {
        unlink(__DIR__ . '/' . $old['image']);
    }
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    $message = 'Produk berhasil dihapus!';
}

// ══════════════════════════════════════════════════════════════════
//  Ambil data dari DB untuk ditampilkan
// ══════════════════════════════════════════════════════════════════
$search   = trim($_GET['search'] ?? '');
$sqlList  = "SELECT * FROM products";
$params   = [];
if ($search !== '') {
    $sqlList .= " WHERE name LIKE :s OR size_label LIKE :s2";
    $params[':s']  = "%$search%";
    $params[':s2'] = "%$search%";
}
$sqlList .= " ORDER BY created_at DESC";
$stmtList  = $pdo->prepare($sqlList);
$stmtList->execute($params);
$products  = $stmtList->fetchAll();

// Ambil kategori (jika tabel categories ada)
$categories = [];
try {
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
} catch (Exception $e) { /* tabel belum ada */ }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel – Products</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --sb-bg: #0f1117; --sb-txt: #a0a8b8; --sb-active: #2563eb;
      --bg: #f4f6fb; --card: #ffffff; --border: #e5e9f2;
      --txt: #141928; --txt2: #6b7694;
      --blue: #2563eb; --blue-l: #dbeafe;
      --green: #16a34a; --green-l: #dcfce7;
      --red: #dc2626; --red-l: #fee2e2;
      --yel: #a16207; --yel-l: #fef9c3;
      --gray: #f1f5f9;
      --shadow: 0 1px 4px rgba(0,0,0,.07), 0 4px 16px rgba(0,0,0,.05);
      --r: 14px; --rs: 8px;
      --font: 'DM Sans', sans-serif; --mono: 'JetBrains Mono', monospace;
    }
    body { font-family: var(--font); background: var(--bg); color: var(--txt); display: flex; min-height: 100vh; font-size: 14px; }

    /* ── Sidebar ─── */
    .sidebar { width: 230px; min-height: 100vh; background: var(--sb-bg); display: flex; flex-direction: column; padding: 28px 0; position: fixed; top: 0; left: 0; bottom: 0; z-index: 10; }
    .sb-brand { padding: 0 24px 32px; }
    .sb-brand .title { font-size: 17px; font-weight: 700; color: #fff; letter-spacing: -.3px; }
    .sb-brand .sub   { font-size: 11.5px; color: #5a6480; margin-top: 2px; }
    .nav { flex: 1; }
    .nav a { display: flex; align-items: center; gap: 12px; padding: 11px 20px 11px 24px; color: var(--sb-txt); font-size: 14px; font-weight: 500; transition: all .18s; border-left: 3px solid transparent; margin: 1px 0; text-decoration: none; }
    .nav a:hover { background: rgba(255,255,255,.05); color: #fff; }
    .nav a.active { background: var(--sb-active); color: #fff; border-radius: 0 8px 8px 0; margin-right: 12px; border-left: 3px solid transparent; }
    .nav a svg { width: 17px; height: 17px; flex-shrink: 0; }
    .sb-bottom { padding: 16px 24px 0; border-top: 1px solid #1e2535; margin-top: 16px; }
    .sb-bottom a { display: flex; align-items: center; gap: 10px; color: #5a6480; font-size: 13.5px; font-weight: 500; padding: 8px 0; transition: color .15s; text-decoration: none; }
    .sb-bottom a:hover { color: var(--red); }

    /* ── Main ─── */
    .main { margin-left: 230px; flex: 1; padding: 36px 40px; overflow-y: auto; min-height: 100vh; }
    .page-hd { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .page-hd h1 { font-size: 28px; font-weight: 700; letter-spacing: -.6px; }
    .btn-add { background: var(--blue); color: #fff; border: none; padding: 11px 20px; border-radius: var(--rs); font-size: 13.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; font-family: var(--font); box-shadow: 0 2px 8px rgba(37,99,235,.25); transition: all .18s; }
    .btn-add:hover { background: #1d4ed8; transform: translateY(-1px); }

    /* ── Alert ─── */
    .alert { padding: 12px 18px; border-radius: var(--rs); margin-bottom: 20px; font-size: 13.5px; font-weight: 500; display: flex; align-items: center; gap: 8px; animation: fadeIn .3s; transition: opacity .5s; }
    .alert-success { background: var(--green-l); color: var(--green); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }

    /* ── Search ─── */
    .search-box { background: var(--card); border-radius: var(--r); border: 1px solid var(--border); padding: 14px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: var(--shadow); }
    .search-box svg { width: 18px; height: 18px; flex-shrink: 0; color: #a0aab8; }
    .search-box input { border: none; outline: none; font-size: 13.5px; color: var(--txt); width: 100%; background: transparent; font-family: var(--font); }
    .search-box input::placeholder { color: #a0aab8; }

    /* ── Table ─── */
    .tcard { background: var(--card); border-radius: var(--r); border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow); }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 14px 20px; font-size: 11.5px; color: var(--txt2); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--border); }
    td { padding: 13px 20px; font-size: 14px; color: var(--txt); border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: #fafbff; }
    .pc { display: flex; align-items: center; gap: 12px; }
    .pimg { width: 44px; height: 44px; border-radius: 10px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; overflow: hidden; border: 1px solid var(--border); }
    .pimg img { width: 100%; height: 100%; object-fit: cover; }
    .pname { font-weight: 600; }
    .pmeta { font-size: 11.5px; color: var(--txt2); margin-top: 2px; font-family: var(--mono); }
    .badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 600; }
    .b-new  { background: var(--green-l); color: var(--green); }
    .b-used { background: var(--yel-l); color: var(--yel); }
    .disc   { font-size: 11px; color: var(--green); margin-left: 4px; }
    .acts   { display: flex; gap: 6px; }
    .ab { width: 32px; height: 32px; border-radius: var(--rs); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; background: transparent; transition: background .15s; }
    .ab svg { width: 15px; height: 15px; }
    .ab.v { color: var(--txt2); } .ab.v:hover { background: var(--gray); }
    .ab.e { color: var(--blue); } .ab.e:hover { background: var(--blue-l); }
    .ab.d { color: var(--red);  } .ab.d:hover { background: var(--red-l); }
    .empty td { text-align: center; padding: 48px; color: var(--txt2); }

    /* ── Overlay / Modal ─── */
    .overlay { position: fixed; inset: 0; background: rgba(10,14,28,.52); display: none; align-items: center; justify-content: center; z-index: 100; backdrop-filter: blur(4px); }
    .overlay.show { display: flex; }
    .modal { background: #fff; border-radius: 18px; width: 520px; max-width: 95vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0,0,0,.18); animation: su .22s cubic-bezier(.34,1.56,.64,1); }
    .modal-sm { width: 380px; }
    @keyframes su { from { opacity:0; transform: translateY(24px) scale(.97); } to { opacity:1; transform: none; } }
    .mhd { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: #fff; z-index: 1; }
    .mhd h3 { font-size: 17px; font-weight: 700; letter-spacing: -.3px; }
    .mcls { background: var(--gray); border: none; border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--txt2); font-size: 16px; transition: all .15s; }
    .mcls:hover { background: var(--red-l); color: var(--red); }
    .mbody { padding: 24px; }
    .mfoot { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; }

    /* ── Form ─── */
    .fg { margin-bottom: 16px; }
    .fg label { display: block; font-size: 11.5px; font-weight: 600; color: var(--txt2); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
    .fg input, .fg select, .fg textarea { width: 100%; padding: 9px 12px; border: 1.5px solid var(--border); border-radius: var(--rs); font-size: 14px; outline: none; color: var(--txt); background: #fff; font-family: var(--font); transition: border-color .15s, box-shadow .15s; }
    .fg input:focus, .fg select:focus, .fg textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.08); }
    .fr2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .fr3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

    /* ── Image Upload ─── */
    .iu-wrap { border: 2px dashed var(--border); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; position: relative; overflow: hidden; }
    .iu-wrap:hover { border-color: var(--blue); background: #eff6ff; }
    .iu-wrap.has { border-style: solid; padding: 0; }
    .iu-wrap input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .iu-preview { width: 100%; height: 160px; object-fit: cover; border-radius: 8px; display: none; }
    .iu-preview.show { display: block; }
    .iu-ph svg { width: 32px; height: 32px; color: #9ca3af; display: block; margin: 0 auto 8px; }
    .iu-ph p { font-size: 13px; color: var(--txt2); }
    .iu-ph span { font-size: 12px; color: #9ca3af; }
    .iu-hint { font-size: 11px; color: var(--txt2); text-align: center; margin-top: 6px; display: none; }

    /* ── Buttons ─── */
    .btn { padding: 9px 18px; border-radius: var(--rs); font-size: 13.5px; font-weight: 600; cursor: pointer; border: none; font-family: var(--font); transition: all .15s; }
    .btn-p { background: var(--blue); color: #fff; } .btn-p:hover { background: #1d4ed8; }
    .btn-s { background: var(--gray); color: var(--txt2); border: 1px solid var(--border); } .btn-s:hover { background: var(--border); }
    .btn-d { background: var(--red); color: #fff; } .btn-d:hover { background: #b91c1c; }

    /* ── View detail rows ─── */
    .vi-img { width: 88px; height: 88px; border-radius: 14px; background: var(--bg); display: flex; align-items: center; justify-content: center; font-size: 44px; margin-bottom: 18px; border: 1px solid var(--border); overflow: hidden; }
    .vi-img img { width: 100%; height: 100%; object-fit: cover; }
    .dr { display: flex; justify-content: space-between; align-items: flex-start; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; gap: 16px; }
    .dr:last-child { border-bottom: none; }
    .dl { color: var(--txt2); white-space: nowrap; flex-shrink: 0; }
    .dv { font-weight: 600; text-align: right; }

    /* ── Confirm ─── */
    .ci { text-align: center; font-size: 52px; margin-bottom: 12px; }
    .ct { text-align: center; font-size: 15px; font-weight: 700; margin-bottom: 4px; }
    .cs { text-align: center; color: var(--txt2); font-size: 13px; }
  </style>
</head>
<body>

<!-- ══ Sidebar ══ -->
<aside class="sidebar">
  <div class="sb-brand">
    <div class="title">Admin Panel</div>
    <div class="sub">E-Commerce Dashboard</div>
  </div>
  <nav class="nav">
    <a href="dashboard.php">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke-width="2"/></svg>
      Dashboard
    </a>
    <a href="products.php" class="active">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/></svg>
      Products
    </a>
    <a href="categories.php">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M3 10h18M3 14h18M9 3v18M15 3v18" stroke-linecap="round"/></svg>
      Categories
    </a>
    <a href="orders.php">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18M16 10a4 4 0 01-8 0"/></svg>
      Orders
    </a>
    <a href="customers.php">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke-width="2"/><path stroke-width="2" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
      Customers
    </a>
  </nav>
  <div class="sb-bottom">
    <a href="logout.php">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Logout
    </a>
  </div>
</aside>

<!-- ══ Main ══ -->
<div class="main">
  <div class="page-hd">
    <h1>Products</h1>
    <button class="btn-add" onclick="openAdd()">
      <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Product
    </button>
  </div>

  <?php if ($message): ?>
  <div class="alert alert-success" id="alertBox">✓ <?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Search -->
  <form method="GET" action="">
    <div class="search-box">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="search" placeholder="Cari nama produk atau ukuran..." value="<?= htmlspecialchars($search) ?>" oninput="this.form.requestSubmit()"/>
    </div>
  </form>

  <!-- Table -->
  <div class="tcard">
    <table>
      <thead>
        <tr>
          <th>Produk</th>
          <th>Kategori</th>
          <th>Gender</th>
          <th>Kondisi</th>
          <th>Harga</th>
          <th>Stok</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
        <tr class="empty"><td colspan="7">🛍️ Tidak ada produk ditemukan.</td></tr>
        <?php else: ?>
        <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <div class="pc">
              <div class="pimg">
                <?php if ($p['image'] && file_exists(__DIR__ . '/' . $p['image'])): ?>
                  <img src="<?= htmlspecialchars($p['image']) ?>" alt=""/>
                <?php else: ?>🛍️<?php endif; ?>
              </div>
              <div>
                <div class="pname"><?= htmlspecialchars($p['name']) ?></div>
                <div class="pmeta">#<?= $p['id'] ?> · <?= htmlspecialchars($p['size_label'] ?: '-') ?></div>
              </div>
            </div>
          </td>
          <td style="color:var(--txt2)"><?php
            if ($p['category_id']) {
              $cn = '#' . $p['category_id'];
              foreach ($categories as $c) { if ($c['id'] == $p['category_id']) { $cn = htmlspecialchars($c['name']); break; } }
              echo $cn;
            } else { echo '-'; }
          ?></td>
          <td style="color:var(--txt2)"><?= htmlspecialchars(ucfirst($p['gender'] ?: '-')) ?></td>
          <td>
            <?php if ($p['condition_status'] === 'new'): ?>
              <span class="badge b-new">New</span>
            <?php elseif ($p['condition_status'] === 'used'): ?>
              <span class="badge b-used">Used</span>
            <?php else: ?>-<?php endif; ?>
          </td>
          <td>
            Rp <?= number_format((float)$p['price'], 0, ',', '.') ?>
            <?php if ($p['discount_percent'] > 0): ?>
              <span class="disc">-<?= (int)$p['discount_percent'] ?>%</span>
            <?php endif; ?>
          </td>
          <td><?= (int)$p['stock'] ?></td>
          <td>
            <div class="acts">
              <button class="ab v" title="Lihat" onclick="openView(<?= $p['id'] ?>)">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
              <button class="ab e" title="Edit" onclick="openEdit(<?= $p['id'] ?>)">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <button class="ab d" title="Hapus" onclick="openDelete(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>')">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══ Modal: View Detail ══ -->
<div class="overlay" id="viewModal">
  <div class="modal">
    <div class="mhd">
      <h3>Detail Produk</h3>
      <button class="mcls" onclick="closeAll()">✕</button>
    </div>
    <div class="mbody" id="viewBody"></div>
    <div class="mfoot">
      <button class="btn btn-s" onclick="closeAll()">Tutup</button>
    </div>
  </div>
</div>

<!-- ══ Modal: Add / Edit ══ -->
<div class="overlay" id="formModal">
  <div class="modal">
    <div class="mhd">
      <h3 id="formTitle">Tambah Produk</h3>
      <button class="mcls" onclick="closeAll()">✕</button>
    </div>
    <form method="POST" action="" id="productForm">
      <input type="hidden" name="action"       id="fAction" value="add"/>
      <input type="hidden" name="id"           id="fId"/>
      <input type="hidden" name="image_base64" id="fImageBase64"/>
      <div class="mbody">

        <!-- Foto -->
        <div class="fg">
          <label>Foto Produk</label>
          <div class="iu-wrap" id="iuWrap">
            <input type="file" id="fImage" accept="image/*" onchange="handleImg(event)"/>
            <img class="iu-preview" id="iuPreview" src="" alt="Preview"/>
            <div class="iu-ph" id="iuPh">
              <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              <p>Klik untuk upload foto</p>
              <span>PNG, JPG, WEBP · maks 5MB</span>
            </div>
          </div>
          <p class="iu-hint" id="iuHint">Klik gambar untuk ganti foto</p>
        </div>

        <!-- Nama -->
        <div class="fg">
          <label>Nama Produk <span style="color:var(--red)">*</span></label>
          <input type="text" name="name" id="fName" placeholder="Cth: Kemeja Flannel Premium" required/>
        </div>

        <!-- Kategori + Gender -->
        <div class="fr2">
          <div class="fg">
            <label>Kategori</label>
            <?php if ($categories): ?>
            <select name="category_id" id="fCat">
              <option value="">— Pilih —</option>
              <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php else: ?>
            <input type="number" name="category_id" id="fCat" placeholder="ID Kategori"/>
            <?php endif; ?>
          </div>
          <div class="fg">
            <label>Gender</label>
            <select name="gender" id="fGender">
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="unisex">Unisex</option>
            </select>
          </div>
        </div>

        <!-- Kondisi + Ukuran -->
        <div class="fr2">
          <div class="fg">
            <label>Kondisi</label>
            <select name="condition_status" id="fCond">
              <option value="new">New</option>
              <option value="used">Used</option>
            </select>
          </div>
          <div class="fg">
            <label>Ukuran</label>
            <input type="text" name="size_label" id="fSize" placeholder="S / M / L / XL / 38 ..."/>
          </div>
        </div>

        <!-- Harga + Stok + Diskon -->
        <div class="fr3">
          <div class="fg">
            <label>Harga (Rp) <span style="color:var(--red)">*</span></label>
            <input type="number" name="price" id="fPrice" placeholder="299000" min="0" required/>
          </div>
          <div class="fg">
            <label>Stok</label>
            <input type="number" name="stock" id="fStock" placeholder="50" min="0"/>
          </div>
          <div class="fg">
            <label>Diskon (%)</label>
            <input type="number" name="discount_percent" id="fDisc" placeholder="0" min="0" max="100"/>
          </div>
        </div>

        <!-- Material + Tahun -->
        <div class="fr2">
          <div class="fg">
            <label>Material</label>
            <input type="text" name="material" id="fMat" placeholder="Cotton, Polyester..."/>
          </div>
          <div class="fg">
            <label>Tahun Produksi</label>
            <input type="number" name="production_year" id="fYear" placeholder="2023" min="1900" max="2099"/>
          </div>
        </div>

        <!-- Deskripsi -->
        <div class="fg">
          <label>Deskripsi</label>
          <textarea name="description" id="fDesc" rows="3" placeholder="Deskripsi singkat produk..."></textarea>
        </div>
      </div><!-- /mbody -->
      <div class="mfoot">
        <button type="button" class="btn btn-s" onclick="closeAll()">Batal</button>
        <button type="submit" class="btn btn-p">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ Modal: Confirm Delete ══ -->
<div class="overlay" id="delModal">
  <div class="modal modal-sm">
    <div class="mhd">
      <h3>Hapus Produk</h3>
      <button class="mcls" onclick="closeAll()">✕</button>
    </div>
    <div class="mbody" style="text-align:center;padding:32px 24px">
      <div class="ci">🗑️</div>
      <p class="ct">Yakin ingin menghapus?</p>
      <p id="delName" style="margin:8px 0 4px;font-weight:700"></p>
      <p class="cs">Tindakan ini tidak dapat dibatalkan.</p>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="delete"/>
      <input type="hidden" name="id"     id="delId"/>
      <div class="mfoot">
        <button type="button" class="btn btn-s" onclick="closeAll()">Batal</button>
        <button type="submit" class="btn btn-d">Hapus</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ Data produk → JS (untuk modal view & edit tanpa request ulang) ══ -->
<script>
const PRODUCTS   = <?= json_encode($products,   JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP) ?>;
const CATEGORIES = <?= json_encode($categories, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP) ?>;

// ─ Helpers ─
function esc(s) {
  if (s === null || s === undefined) return '-';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function catName(id) {
  if (!id) return '-';
  const c = CATEGORIES.find(x => x.id == id);
  return c ? esc(c.name) : '#' + id;
}
function closeAll() {
  document.querySelectorAll('.overlay').forEach(el => el.classList.remove('show'));
}
document.querySelectorAll('.overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) closeAll(); });
});

// ─ View Detail ─
function openView(id) {
  const p = PRODUCTS.find(x => x.id == id);
  if (!p) return;
  const imgHtml = p.image
    ? `<img src="${esc(p.image)}" alt="" style="width:100%;height:100%;object-fit:cover"/>`
    : '🛍️';
  const rows = [
    ['ID',             '#' + p.id],
    ['Nama',           p.name],
    ['Kategori',       catName(p.category_id)],
    ['Gender',         p.gender],
    ['Kondisi',        p.condition_status],
    ['Ukuran',         p.size_label],
    ['Material',       p.material],
    ['Tahun Produksi', p.production_year],
    ['Harga',          'Rp ' + Number(p.price).toLocaleString('id-ID')],
    ['Diskon',         (p.discount_percent || 0) + '%'],
    ['Stok',           (p.stock || 0) + ' pcs'],
    ['Deskripsi',      p.description],
    ['Dibuat',         p.created_at],
  ].map(([l,v]) =>
    `<div class="dr"><span class="dl">${l}</span><span class="dv">${esc(v)}</span></div>`
  ).join('');
  document.getElementById('viewBody').innerHTML =
    `<div class="vi-img">${imgHtml}</div>${rows}`;
  document.getElementById('viewModal').classList.add('show');
}

// ─ Add ─
function openAdd() {
  document.getElementById('formTitle').textContent = 'Tambah Produk';
  document.getElementById('fAction').value = 'add';
  document.getElementById('fId').value     = '';
  document.getElementById('productForm').reset();
  resetImg(null);
  document.getElementById('formModal').classList.add('show');
}

// ─ Edit ─
function openEdit(id) {
  const p = PRODUCTS.find(x => x.id == id);
  if (!p) return;
  document.getElementById('formTitle').textContent = 'Edit Produk';
  document.getElementById('fAction').value = 'edit';
  document.getElementById('fId').value     = p.id;
  document.getElementById('fName').value   = p.name   || '';
  document.getElementById('fGender').value = p.gender || 'unisex';
  document.getElementById('fCond').value   = p.condition_status || 'new';
  document.getElementById('fSize').value   = p.size_label       || '';
  document.getElementById('fPrice').value  = p.price            || '';
  document.getElementById('fStock').value  = p.stock            || '';
  document.getElementById('fDisc').value   = p.discount_percent || 0;
  document.getElementById('fMat').value    = p.material         || '';
  document.getElementById('fYear').value   = p.production_year  || '';
  document.getElementById('fDesc').value   = p.description      || '';
  const catEl = document.getElementById('fCat');
  if (catEl) catEl.value = p.category_id || '';
  resetImg(p.image || null);
  document.getElementById('formModal').classList.add('show');
}

// ─ Delete ─
function openDelete(id, name) {
  document.getElementById('delId').value        = id;
  document.getElementById('delName').textContent = name;
  document.getElementById('delModal').classList.add('show');
}

// ─ Image Upload ─
function handleImg(event) {
  const file = event.target.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) { alert('Ukuran file maksimal 5MB'); return; }
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('fImageBase64').value = e.target.result;
    showPreview(e.target.result);
  };
  reader.readAsDataURL(file);
}
function showPreview(src) {
  const prev = document.getElementById('iuPreview');
  prev.src = src; prev.classList.add('show');
  document.getElementById('iuPh').style.display   = 'none';
  document.getElementById('iuWrap').classList.add('has');
  document.getElementById('iuHint').style.display  = 'block';
}
function resetImg(existingPath) {
  document.getElementById('fImageBase64').value = '';
  document.getElementById('fImage').value = '';
  const prev = document.getElementById('iuPreview');
  if (existingPath) {
    showPreview(existingPath);
  } else {
    prev.src = ''; prev.classList.remove('show');
    document.getElementById('iuPh').style.display  = 'block';
    document.getElementById('iuWrap').classList.remove('has');
    document.getElementById('iuHint').style.display = 'none';
  }
}

// ─ Auto-hide alert ─
const alertEl = document.getElementById('alertBox');
if (alertEl) setTimeout(() => { alertEl.style.transition = 'opacity .5s'; alertEl.style.opacity = '0'; }, 3500);
</script>
</body>
</html>