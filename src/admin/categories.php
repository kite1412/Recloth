<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel - Categories</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --sidebar-bg: #0f1117;
      --sidebar-text: #a0a8b8;
      --sidebar-active: #2563eb;
      --main-bg: #f4f6fb;
      --card-bg: #ffffff;
      --border: #e5e9f2;
      --text-primary: #141928;
      --text-secondary: #6b7694;
      --blue: #2563eb;
      --blue-light: #dbeafe;
      --green: #16a34a;
      --red: #dc2626;
      --red-light: #fee2e2;
      --gray-light: #f1f5f9;
      --shadow: 0 1px 4px rgba(0,0,0,0.07), 0 4px 16px rgba(0,0,0,0.05);
      --radius: 14px;
      --radius-sm: 8px;
      --font: 'DM Sans', sans-serif;
    }

    body { font-family: var(--font); background: var(--main-bg); color: var(--text-primary); display: flex; min-height: 100vh; font-size: 14px; overflow: hidden; }

    .sidebar {
      width: 230px; min-height: 100vh; background: var(--sidebar-bg);
      display: flex; flex-direction: column; padding: 28px 0;
      position: fixed; top: 0; left: 0; bottom: 0; z-index: 10;
    }
    .sidebar-brand { padding: 0 24px 32px; }
    .sidebar-brand .brand-title { font-size: 17px; font-weight: 700; color: #fff; letter-spacing: -0.3px; }
    .sidebar-brand .brand-sub { font-size: 11.5px; color: #5a6480; margin-top: 2px; }
    .sidebar-nav { flex: 1; }
    .nav-item {
      display: flex; align-items: center; gap: 12px; padding: 11px 20px 11px 24px;
      color: var(--sidebar-text); cursor: pointer; font-size: 14px; font-weight: 500;
      transition: all 0.18s; border-left: 3px solid transparent; margin: 1px 0;
      text-decoration: none;
    }
    .nav-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
    .nav-item.active { background: var(--sidebar-active); color: #fff; border-radius: 0 8px 8px 0; margin-right: 12px; border-left: 3px solid transparent; }
    .nav-item svg { width: 17px; height: 17px; flex-shrink: 0; }
    .sidebar-bottom { padding: 16px 24px 0; border-top: 1px solid #1e2535; margin-top: 16px; }
    .nav-logout { display: flex; align-items: center; gap: 10px; color: #5a6480; cursor: pointer; font-size: 13.5px; font-weight: 500; padding: 8px 0; transition: color 0.15s; }
    .nav-logout:hover { color: var(--red); }

    .main { margin-left: 230px; flex: 1; padding: 36px 40px; overflow-y: auto; min-height: 100vh; }

    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .page-header h1 { font-size: 28px; font-weight: 700; letter-spacing: -0.6px; }
    .btn-add {
      background: var(--blue); color: #fff; border: none; padding: 11px 20px;
      border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 600;
      cursor: pointer; display: flex; align-items: center; gap: 6px;
      font-family: var(--font); box-shadow: 0 2px 8px rgba(37,99,235,0.25); transition: all 0.18s;
    }
    .btn-add:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(37,99,235,0.35); }

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
    .cat-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; }
    .card-actions { display: flex; gap: 6px; }
    .icon-btn { width: 32px; height: 32px; border: none; background: transparent; border-radius: var(--radius-sm); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
    .icon-btn svg { width: 15px; height: 15px; }
    .icon-btn.edit { color: var(--blue); } .icon-btn.edit:hover { background: var(--blue-light); }
    .icon-btn.del  { color: var(--red); }  .icon-btn.del:hover  { background: var(--red-light); }
    .cat-name { font-size: 17px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px; cursor: pointer; }
    .cat-name:hover { color: var(--blue); }
    .cat-desc { font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; min-height: 36px; line-height: 1.5; }
    .cat-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 14px; border-top: 1px solid var(--border); }
    .cat-footer span { font-size: 13px; color: var(--text-secondary); }
    .cat-footer strong { font-size: 16px; font-weight: 700; color: var(--blue); }
    .empty { grid-column: 1 / -1; text-align: center; color: var(--text-secondary); padding: 48px; font-size: 15px; }

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
    .form-group input, .form-group textarea, .form-group select {
      width: 100%; padding: 9px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
      font-size: 14px; outline: none; color: var(--text-primary); background: #fff; font-family: var(--font);
    }
    .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.08); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    .emoji-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .emoji-opt { width: 36px; height: 36px; border-radius: 8px; border: 1.5px solid var(--border); background: var(--main-bg); cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
    .emoji-opt.selected, .emoji-opt:hover { border-color: var(--blue); background: var(--blue-light); }

    .btn { padding: 9px 18px; border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 600; cursor: pointer; border: none; font-family: var(--font); transition: all 0.15s; }
    .btn-primary { background: var(--blue); color: #fff; } .btn-primary:hover { background: #1d4ed8; }
    .btn-secondary { background: var(--gray-light); color: var(--text-secondary); border: 1px solid var(--border); } .btn-secondary:hover { background: var(--border); }
    .btn-danger { background: var(--red); color: #fff; } .btn-danger:hover { background: #b91c1c; }

    .view-hero { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
    .view-icon-big { width: 72px; height: 72px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 36px; flex-shrink: 0; }
    .view-title { font-size: 20px; font-weight: 700; color: var(--text-primary); }
    .view-sub { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }
    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: var(--text-secondary); }
    .detail-value { font-weight: 600; color: var(--text-primary); }

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
    <input type="text" id="searchInput" placeholder="Search categories..." oninput="renderGrid()"/>
  </div>

  <div class="grid" id="catGrid"></div>
</div>

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
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea id="fDesc" rows="2" placeholder="Deskripsi singkat kategori..."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Jumlah Produk</label>
          <input type="number" id="fCount" placeholder="0"/>
        </div>
        <div class="form-group">
          <label>Warna Ikon</label>
          <select id="fColor">
            <option value="#eff6ff|#dbeafe">Biru</option>
            <option value="#fdf4ff|#f3e8ff">Ungu</option>
            <option value="#fff7ed|#ffedd5">Oranye</option>
            <option value="#f0fdf4|#dcfce7">Hijau</option>
            <option value="#fdf2f8|#fce7f3">Pink</option>
            <option value="#fffbeb|#fef3c7">Kuning</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Pilih Emoji</label>
        <div class="emoji-grid" id="emojiGrid"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeAll()">Batal</button>
      <button class="btn btn-primary" onclick="saveCategory()">Simpan</button>
    </div>
  </div>
</div>

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
      <button class="btn btn-danger" onclick="confirmDelete()">Hapus</button>
    </div>
  </div>
</div>

<script>
  const EMOJIS = ['👕','👖','🧥','👗','👟','👠','👜','👒','🧣','🧤','🧦','👙','🩱','🩲','🩳','👚','🥻','🩴','👛','💍','💎','🕶️','⌚','🎀'];
  const COLORS = {
    '#eff6ff|#dbeafe': '#eff6ff', '#fdf4ff|#f3e8ff': '#fdf4ff',
    '#fff7ed|#ffedd5': '#fff7ed', '#f0fdf4|#dcfce7': '#f0fdf4',
    '#fdf2f8|#fce7f3': '#fdf2f8', '#fffbeb|#fef3c7': '#fffbeb',
  };

  let selEmoji = '👕';
  let categories = [
    { id:1, name:"Atasan",    desc:"Kaos, kemeja, blouse, dan tops lainnya.",          count:45, emoji:"👕", color:"#eff6ff" },
    { id:2, name:"Bawahan",   desc:"Celana, rok, jeans, dan shorts.",                  count:38, emoji:"👖", color:"#fdf4ff" },
    { id:3, name:"Outerwear", desc:"Jaket, blazer, cardigan, dan coat.",               count:22, emoji:"🧥", color:"#fff7ed" },
    { id:4, name:"Dress",     desc:"Midi dress, mini dress, dan maxi dress.",          count:31, emoji:"👗", color:"#fdf2f8" },
    { id:5, name:"Sepatu",    desc:"Sneakers, heels, sandal, dan boots fashion.",      count:60, emoji:"👟", color:"#f0fdf4" },
    { id:6, name:"Aksesoris", desc:"Tas, topi, perhiasan, dan pelengkap outfit.",      count:78, emoji:"👜", color:"#fffbeb" },
  ];
  let nextId = 7;
  let delTarget = null;

  function renderGrid() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const filtered = categories.filter(c =>
      c.name.toLowerCase().includes(q) || c.desc.toLowerCase().includes(q)
    );
    const grid = document.getElementById('catGrid');
    if (!filtered.length) {
      grid.innerHTML = `<div class="empty">Tidak ada kategori ditemukan.</div>`; return;
    }
    grid.innerHTML = filtered.map(c => `
      <div class="cat-card">
        <div class="card-top">
          <div class="cat-icon" style="background:${c.color}">${c.emoji}</div>
          <div class="card-actions">
            <button class="icon-btn edit" title="Edit" onclick="openEdit(${c.id})">
              <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button class="icon-btn del" title="Hapus" onclick="openDelete(${c.id})">
              <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
          </div>
        </div>
        <div class="cat-name" onclick="openView(${c.id})">${c.name}</div>
        <div class="cat-desc">${c.desc}</div>
        <div class="cat-footer">
          <span>Products</span>
          <strong>${c.count}</strong>
        </div>
      </div>
    `).join('');
  }

  function buildEmojiGrid(selected) {
    selEmoji = selected || EMOJIS[0];
    document.getElementById('emojiGrid').innerHTML = EMOJIS.map(e => `
      <div class="emoji-opt${e === selEmoji ? ' selected' : ''}" onclick="selectEmoji('${e}', this)">${e}</div>
    `).join('');
  }

  function selectEmoji(e, el) {
    selEmoji = e;
    document.querySelectorAll('.emoji-opt').forEach(x => x.classList.remove('selected'));
    el.classList.add('selected');
  }

  function openView(id) {
    const c = categories.find(x => x.id === id);
    document.getElementById('viewBody').innerHTML = `
      <div class="view-hero">
        <div class="view-icon-big" style="background:${c.color}">${c.emoji}</div>
        <div>
          <div class="view-title">${c.name}</div>
          <div class="view-sub">${c.desc}</div>
        </div>
      </div>
      <div class="detail-row"><span class="detail-label">Nama Kategori</span><span class="detail-value">${c.name}</span></div>
      <div class="detail-row"><span class="detail-label">Deskripsi</span><span class="detail-value" style="max-width:260px;text-align:right">${c.desc}</span></div>
      <div class="detail-row"><span class="detail-label">Jumlah Produk</span><span class="detail-value" style="color:var(--blue);font-weight:700">${c.count} produk</span></div>
      <div class="detail-row"><span class="detail-label">Emoji</span><span class="detail-value">${c.emoji}</span></div>
    `;
    document.getElementById('viewModal').classList.add('show');
  }

  function openAdd() {
    document.getElementById('formTitle').textContent = 'Tambah Kategori';
    document.getElementById('editId').value = '';
    document.getElementById('fName').value = '';
    document.getElementById('fDesc').value = '';
    document.getElementById('fCount').value = '';
    document.getElementById('fColor').value = '#eff6ff|#dbeafe';
    buildEmojiGrid('👕');
    document.getElementById('formModal').classList.add('show');
  }

  function openEdit(id) {
    const c = categories.find(x => x.id === id);
    document.getElementById('formTitle').textContent = 'Edit Kategori';
    document.getElementById('editId').value = id;
    document.getElementById('fName').value = c.name;
    document.getElementById('fDesc').value = c.desc;
    document.getElementById('fCount').value = c.count;
    const colorKey = Object.keys(COLORS).find(k => COLORS[k] === c.color) || '#eff6ff|#dbeafe';
    document.getElementById('fColor').value = colorKey;
    buildEmojiGrid(c.emoji);
    document.getElementById('formModal').classList.add('show');
  }

  function saveCategory() {
    const name = document.getElementById('fName').value.trim();
    if (!name) { alert('Nama kategori harus diisi!'); return; }
    const colorKey = document.getElementById('fColor').value;
    const data = {
      name, desc: document.getElementById('fDesc').value.trim() || '-',
      count: parseInt(document.getElementById('fCount').value) || 0,
      emoji: selEmoji, color: COLORS[colorKey] || '#eff6ff'
    };
    const editId = document.getElementById('editId').value;
    if (editId) {
      const i = categories.findIndex(x => x.id === parseInt(editId));
      categories[i] = { ...categories[i], ...data };
    } else {
      categories.push({ id: nextId++, ...data });
    }
    closeAll(); renderGrid();
  }

  function openDelete(id) {
    delTarget = id;
    document.getElementById('delName').textContent = categories.find(x => x.id === id).name;
    document.getElementById('deleteModal').classList.add('show');
  }

  function confirmDelete() {
    categories = categories.filter(x => x.id !== delTarget);
    closeAll(); renderGrid();
  }

  function closeAll() {
    document.querySelectorAll('.overlay').forEach(el => el.classList.remove('show'));
  }

  document.querySelectorAll('.overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) closeAll(); });
  });

  renderGrid();
</script>
</body>
</html>