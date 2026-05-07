<?php
// =============================================
// Database Connection
// =============================================
$host = 'localhost';
$dbname = 'recloth';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// =============================================
// AJAX Handler
// =============================================
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'list') {
        $q = '%' . ($_GET['q'] ?? '') . '%';
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE name LIKE ? ORDER BY id ASC");
        $stmt->execute([$q]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

if ($action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    if (!$name) { echo json_encode(['error' => 'Nama kategori harus diisi']); exit; }

    // Cari ID kosong
    $stmt = $pdo->query("
        SELECT t1.id + 1 AS next_id
        FROM categories t1
        LEFT JOIN categories t2 ON t1.id + 1 = t2.id
        WHERE t2.id IS NULL
        ORDER BY t1.id ASC
        LIMIT 1
    ");
    $row = $stmt->fetch();

    $newId = $row ? $row['next_id'] : 1;

    // Insert pakai ID manual
    $stmt = $pdo->prepare("INSERT INTO categories (id, name, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$newId, $name]);

    echo json_encode(['success' => true, 'id' => $newId]);
    exit;
}

    if ($action === 'edit') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        if (!$id || !$name) { echo json_encode(['error' => 'Data tidak valid']); exit; }
        $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = (int)($data['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID tidak valid']); exit; }
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Action tidak dikenal']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel - Categories</title>
  <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Montserrat:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    @font-face {
      font-family: 'Symphony';
      src: url('../../public/fonts/symphony-pro-regular.otf') format('opentype');
      font-weight: normal;
      font-style: normal;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --sidebar-bg: #ffffff;
      --sidebar-text: #6f6f6f;
      --sidebar-active: #f4f4f4;
      --main-bg: #f4f4f4;
      --card-bg: #ffffff;
      --border: #e6e6e6;
      --text-primary: #121212;
      --text-secondary: #6f6f6f;
      --black: #111111;
      --blue: #111111;
      --blue-light: #f4f4f4;
      --green: #1ea672;
      --red: #d24e4e;
      --red-light: #fbeeee;
      --gray-light: #f1f1f1;
      --shadow: 0 8px 18px rgba(17, 17, 17, 0.04);
      --radius: 16px;
      --radius-sm: 8px;
      --font: 'Montserrat', sans-serif;
    }

    body { font-family: var(--font); background: var(--main-bg); color: var(--text-primary); display: flex; min-height: 100vh; font-size: 14px; overflow-y: auto; }

    .sidebar {
      width: 230px; min-height: 100vh; background: var(--sidebar-bg);
      display: flex; flex-direction: column; padding: 28px 0;
      position: fixed; top: 0; left: 0; bottom: 0; z-index: 10; border-right: 1px solid var(--border);
    }
    .sidebar-brand { padding: 0 24px 32px; }
    .sidebar-brand .brand-title { font-family: 'Symphony', sans-serif; font-size: 30px; font-weight: normal; color: var(--black); letter-spacing: 1px; }
    .sidebar-brand .brand-sub { display: none; }
    .sidebar-nav { flex: 1; }
    .nav-item {
      display: flex; align-items: center; gap: 12px; padding: 11px 20px 11px 24px;
      color: var(--sidebar-text); cursor: pointer; font-size: 14px; font-weight: 500;
      transition: all 0.18s; border-left: 3px solid transparent; margin: 1px 0;
      text-decoration: none;
    }
    .nav-item:hover { background: #fafafa; color: var(--black); }
    .nav-item.active { background: var(--sidebar-active); color: var(--black); border-radius: 0 8px 8px 0; margin-right: 12px; border-left: 3px solid var(--black); font-weight: 600; }
    .nav-item svg { width: 17px; height: 17px; flex-shrink: 0; }
    .sidebar-bottom { padding: 16px 24px 0; border-top: 1px solid var(--border); margin-top: 16px; }
    .nav-logout { display: flex; align-items: center; gap: 10px; color: var(--sidebar-text); cursor: pointer; font-size: 13.5px; font-weight: 500; padding: 8px 0; transition: color 0.15s; }
    .nav-logout:hover { color: var(--red); }

    .main { margin-left: 230px; flex: 1; padding: 36px 40px; overflow-y: auto; min-height: 100vh; }

    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .page-header h1 { font-size: 28px; font-weight: 700; letter-spacing: -0.6px; color: var(--black); }
    .btn-add {
      background: var(--black); color: #fff; border: none; padding: 11px 20px;
      border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 600;
      cursor: pointer; display: flex; align-items: center; gap: 6px;
      font-family: var(--font); box-shadow: 0 2px 8px rgba(17,17,17,0.25); transition: all 0.18s;
    }
    .btn-add:hover { background: #333; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(17,17,17,0.35); }

    .search-box {
      background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border);
      padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;
      box-shadow: var(--shadow);
    }
    .search-box svg { width: 18px; height: 18px; flex-shrink: 0; color: #a0aab8; }
    .search-box input { border: none; outline: none; font-size: 13.5px; color: var(--text-primary); width: 100%; background: transparent; font-family: var(--font); }
    .search-box input::placeholder { color: #a0aab8; }

    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .cat-card {
      background: var(--card-bg); border-radius: var(--radius); border: 1.5px solid var(--border);
      padding: 20px; display: flex; flex-direction: column; transition: all 0.2s; box-shadow: var(--shadow);
    }
    .cat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); border-color: #d0d8ee; }
    .card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    .cat-icon {
      width: 48px; height: 48px; border-radius: 12px; background: var(--blue-light);
      display: flex; align-items: center; justify-content: center;
    }
    .cat-icon svg { width: 22px; height: 22px; color: var(--blue); }
    .card-actions { display: flex; gap: 6px; }
    .icon-btn { width: 32px; height: 32px; border: none; background: transparent; border-radius: var(--radius-sm); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
    .icon-btn svg { width: 15px; height: 15px; }
    .icon-btn.edit { color: var(--blue); } .icon-btn.edit:hover { background: var(--blue-light); }
    .icon-btn.del  { color: var(--red); }  .icon-btn.del:hover  { background: var(--red-light); }
    .cat-name { font-size: 17px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; cursor: pointer; }
    .cat-name:hover { color: var(--blue); }
    .cat-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid var(--border); margin-top: auto; }
    .cat-footer span { font-size: 12px; color: var(--text-secondary); font-family: 'JetBrains Mono', monospace; }
    .empty { grid-column: 1 / -1; text-align: center; color: var(--text-secondary); padding: 48px; font-size: 15px; }
    .loading { grid-column: 1 / -1; text-align: center; color: var(--text-secondary); padding: 48px; font-size: 15px; }

    .toast {
      position: fixed; bottom: 28px; right: 28px; background: #1e2535; color: #fff;
      padding: 12px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 500;
      z-index: 999; transform: translateY(80px); opacity: 0; transition: all 0.3s cubic-bezier(.34,1.56,.64,1);
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast.success { background: var(--green); }
    .toast.error { background: var(--red); }

    .overlay { position: fixed; inset: 0; background: rgba(10,14,28,0.5); display: none; align-items: center; justify-content: center; z-index: 100; backdrop-filter: blur(4px); }
    .overlay.show { display: flex; }
    .modal { background: #fff; border-radius: 18px; width: 460px; max-width: 95vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0,0,0,0.18); animation: slideUp 0.22s cubic-bezier(.34,1.56,.64,1); }
    @keyframes slideUp { from { opacity: 0; transform: translateY(24px) scale(0.97); } to { opacity: 1; transform: none; } }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .modal-header h3 { font-size: 17px; font-weight: 700; color: var(--text-primary); letter-spacing: -0.3px; }
    .modal-close { background: var(--gray-light); border: none; border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); transition: all 0.15s; font-size: 16px; }
    .modal-close:hover { background: var(--red-light); color: var(--red); }
    .modal-body { padding: 24px; }
    .modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .form-group input {
      width: 100%; padding: 9px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
      font-size: 14px; outline: none; color: var(--text-primary); background: #fff; font-family: var(--font);
    }
    .form-group input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.08); }

    .btn { padding: 9px 18px; border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 600; cursor: pointer; border: none; font-family: var(--font); transition: all 0.15s; }
    .btn-primary { background: var(--blue); color: #fff; } .btn-primary:hover { background: #1d4ed8; }
    .btn-secondary { background: var(--gray-light); color: var(--text-secondary); border: 1px solid var(--border); } .btn-secondary:hover { background: var(--border); }
    .btn-danger { background: var(--red); color: #fff; } .btn-danger:hover { background: #b91c1c; }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; }

    .view-hero { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
    .view-icon-big { width: 64px; height: 64px; border-radius: 14px; background: var(--blue-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .view-icon-big svg { width: 28px; height: 28px; color: var(--blue); }
    .view-title { font-size: 20px; font-weight: 700; color: var(--text-primary); }
    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: var(--text-secondary); }
    .detail-value { font-weight: 600; color: var(--text-primary); font-family: 'JetBrains Mono', monospace; font-size: 13px; }

    .confirm-icon { text-align: center; font-size: 48px; margin-bottom: 12px; }
    .confirm-text { text-align: center; font-size: 15px; font-weight: 600; color: var(--text-primary); }
    .confirm-sub { text-align: center; font-size: 13px; color: var(--text-secondary); margin-top: 6px; }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-title">Admin Panel</div>
    <div class="brand-sub">E-Commerce Dashboard</div>
  </div>
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="nav-item">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1.5" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1.5" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1.5" stroke-width="2"/></svg>
      Dashboard
    </a>
    <a href="products.php" class="nav-item">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/></svg>
      Products
    </a>
    <a href="categories.php" class="nav-item active">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M3 10h18M3 14h18M9 3v18M15 3v18" stroke-linecap="round"/></svg>
      Categories
    </a>
    <a href="orders.php" class="nav-item">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18M16 10a4 4 0 01-8 0"/></svg>
      Orders
    </a>
    <a href="customers.php" class="nav-item">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke-width="2"/><path stroke-width="2" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
      Customers
    </a>
  </nav>
  <div class="sidebar-bottom">
    <a href="../config/logout.php" class="nav-logout" style="text-decoration: none;">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Logout
    </a>
  </div>
</aside>

<div class="main">
  <div class="page-header">
    <h1>Categories</h1>
    <button class="btn-add" onclick="openAdd()">
      <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" width="16" height="16">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Add Category
    </button>
  </div>

  <div class="search-box">
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
      <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </svg>
    <input type="text" id="searchInput" placeholder="Search categories..." oninput="debounceSearch()"/>
  </div>

  <div class="grid" id="catGrid">
    <div class="loading">Memuat data...</div>
  </div>
</div>

<!-- View Modal -->
<div class="overlay" id="viewModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Detail Kategori</h3>
      <button class="modal-close" onclick="closeAll()">✕</button>
    </div>
    <div class="modal-body" id="viewBody"></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeAll()">Tutup</button>
    </div>
  </div>
</div>

<!-- Form Modal -->
<div class="overlay" id="formModal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="formTitle">Tambah Kategori</h3>
      <button class="modal-close" onclick="closeAll()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editId"/>
      <div class="form-group">
        <label>Nama Kategori</label>
        <input type="text" id="fName" placeholder="Cth: Atasan Wanita"/>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeAll()">Batal</button>
      <button class="btn btn-primary" id="saveBtn" onclick="saveCategory()">Simpan</button>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="overlay" id="deleteModal">
  <div class="modal" style="width:360px">
    <div class="modal-header">
      <h3>Hapus Kategori</h3>
      <button class="modal-close" onclick="closeAll()">✕</button>
    </div>
    <div class="modal-body" style="text-align:center;padding:32px 24px">
      <div class="confirm-icon">🗑️</div>
      <p class="confirm-text">Yakin ingin menghapus kategori ini?</p>
      <p id="delName" style="font-size:15px;font-weight:700;color:var(--text-primary);text-align:center;margin-top:8px"></p>
      <p class="confirm-sub">Tindakan ini tidak dapat dibatalkan.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeAll()">Batal</button>
      <button class="btn btn-danger" id="delBtn" onclick="confirmDelete()">Hapus</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
  let delTarget = null;
  let searchTimer = null;

  const ICON_SVG = `<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M3 10h18M3 14h18M9 3v18M15 3v18" stroke-linecap="round"/></svg>`;

  // ── Toast ─────────────────────────────────────────────
  function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 3000);
  }

  // ── Format date ───────────────────────────────────────
  function fmtDate(str) {
    if (!str) return '-';
    const d = new Date(str);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  }

  // ── Load & render ─────────────────────────────────────
  async function loadGrid() {
    const q = document.getElementById('searchInput').value;
    const grid = document.getElementById('catGrid');
    try {
      const res = await fetch(`categories.php?action=list&q=${encodeURIComponent(q)}`);
      const data = await res.json();
      if (data.error) { grid.innerHTML = `<div class="empty">⚠️ ${data.error}</div>`; return; }
      if (!data.length) { grid.innerHTML = `<div class="empty">Tidak ada kategori ditemukan.</div>`; return; }
      grid.innerHTML = data.map(c => `
        <div class="cat-card">
          <div class="card-top">
            <div class="cat-icon">${ICON_SVG}</div>
            <div class="card-actions">
              <button class="icon-btn edit" title="Edit" onclick="openEdit(${c.id}, ${JSON.stringify(c.name).replace(/"/g,'&quot;')})">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>
              <button class="icon-btn del" title="Hapus" onclick="openDelete(${c.id}, ${JSON.stringify(c.name).replace(/"/g,'&quot;')})">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
              </button>
            </div>
          </div>
          <div class="cat-name" onclick="openView(${c.id}, ${JSON.stringify(c.name).replace(/"/g,'&quot;')}, ${JSON.stringify(c.created_at || '').replace(/"/g,'&quot;')})">${escHtml(c.name)}</div>
          <div class="cat-footer">
            <span>ID: ${c.id}</span>
            <span>${fmtDate(c.created_at)}</span>
          </div>
        </div>
      `).join('');
    } catch (e) {
      grid.innerHTML = `<div class="empty">⚠️ Gagal memuat data. Periksa koneksi database.</div>`;
    }
  }

  function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadGrid, 300);
  }

  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── View ──────────────────────────────────────────────
  function openView(id, name, created_at) {
    document.getElementById('viewBody').innerHTML = `
      <div class="view-hero">
        <div class="view-icon-big">${ICON_SVG}</div>
        <div>
          <div class="view-title">${escHtml(name)}</div>
        </div>
      </div>
      <div class="detail-row"><span class="detail-label">ID</span><span class="detail-value">#${id}</span></div>
      <div class="detail-row"><span class="detail-label">Nama Kategori</span><span class="detail-value">${escHtml(name)}</span></div>
      <div class="detail-row"><span class="detail-label">Dibuat</span><span class="detail-value">${fmtDate(created_at)}</span></div>
    `;
    document.getElementById('viewModal').classList.add('show');
  }

  // ── Add ───────────────────────────────────────────────
  function openAdd() {
    document.getElementById('formTitle').textContent = 'Tambah Kategori';
    document.getElementById('editId').value = '';
    document.getElementById('fName').value = '';
    document.getElementById('formModal').classList.add('show');
    setTimeout(() => document.getElementById('fName').focus(), 100);
  }

  // ── Edit ──────────────────────────────────────────────
  function openEdit(id, name) {
    document.getElementById('formTitle').textContent = 'Edit Kategori';
    document.getElementById('editId').value = id;
    document.getElementById('fName').value = name;
    document.getElementById('formModal').classList.add('show');
    setTimeout(() => document.getElementById('fName').focus(), 100);
  }

  // ── Save ──────────────────────────────────────────────
  async function saveCategory() {
    const name = document.getElementById('fName').value.trim();
    if (!name) { showToast('Nama kategori harus diisi!', 'error'); return; }
    const editId = document.getElementById('editId').value;
    const btn = document.getElementById('saveBtn');
    btn.disabled = true; btn.textContent = 'Menyimpan...';
    try {
      const action = editId ? 'edit' : 'add';
      const body = editId ? { id: parseInt(editId), name } : { name };
      const res = await fetch(`categories.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const data = await res.json();
      if (data.error) { showToast(data.error, 'error'); return; }
      showToast(editId ? 'Kategori berhasil diupdate!' : 'Kategori berhasil ditambahkan!');
      closeAll();
      loadGrid();
    } catch (e) {
      showToast('Gagal menyimpan. Coba lagi.', 'error');
    } finally {
      btn.disabled = false; btn.textContent = 'Simpan';
    }
  }

  // ── Delete ────────────────────────────────────────────
  function openDelete(id, name) {
    delTarget = id;
    document.getElementById('delName').textContent = name;
    document.getElementById('deleteModal').classList.add('show');
  }

  async function confirmDelete() {
    if (!delTarget) return;
    const btn = document.getElementById('delBtn');
    btn.disabled = true; btn.textContent = 'Menghapus...';
    try {
      const res = await fetch(`categories.php?action=delete`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: delTarget })
      });
      const data = await res.json();
      if (data.error) { showToast(data.error, 'error'); return; }
      showToast('Kategori berhasil dihapus!');
      closeAll();
      loadGrid();
    } catch (e) {
      showToast('Gagal menghapus. Coba lagi.', 'error');
    } finally {
      btn.disabled = false; btn.textContent = 'Hapus';
      delTarget = null;
    }
  }

  // ── Close ─────────────────────────────────────────────
  function closeAll() {
    document.querySelectorAll('.overlay').forEach(el => el.classList.remove('show'));
  }

  document.querySelectorAll('.overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) closeAll(); });
  });

  // ── Enter key in form ─────────────────────────────────
  document.getElementById('fName').addEventListener('keydown', e => {
    if (e.key === 'Enter') saveCategory();
  });

  // ── Init ──────────────────────────────────────────────
  loadGrid();
</script>
</body>
</html>