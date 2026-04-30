<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel - Products</title>
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
      --green-light: #dcfce7;
      --red: #dc2626;
      --red-light: #fee2e2;
      --gray-light: #f1f5f9;
      --shadow: 0 1px 4px rgba(0,0,0,0.07), 0 4px 16px rgba(0,0,0,0.05);
      --radius: 14px;
      --radius-sm: 8px;
      --font: 'DM Sans', sans-serif;
      --mono: 'JetBrains Mono', monospace;
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
    .page-header h1 { font-size: 28px; font-weight: 700; letter-spacing: -0.6px; color: var(--text-primary); }
    .btn-add {
      background: var(--blue); color: #fff; border: none; padding: 11px 20px;
      border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 600;
      cursor: pointer; display: flex; align-items: center; gap: 6px;
      font-family: var(--font); box-shadow: 0 2px 8px rgba(37,99,235,0.25); transition: all 0.18s;
    }
    .btn-add:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(37,99,235,0.35); }

    .search-box {
      background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border);
      padding: 16px 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
      box-shadow: var(--shadow);
    }
    .search-box svg { width: 18px; height: 18px; flex-shrink: 0; color: #a0aab8; }
    .search-box input {
      border: none; outline: none; font-size: 13.5px; color: var(--text-primary);
      width: 100%; background: transparent; font-family: var(--font);
    }
    .search-box input::placeholder { color: #a0aab8; }

    .table-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow); }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 14px 20px; font-size: 12px; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
    td { padding: 16px 20px; font-size: 14px; color: var(--text-primary); border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    .prod-cell { display: flex; align-items: center; gap: 12px; }
    .prod-img { width: 44px; height: 44px; border-radius: 10px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; overflow: hidden; border: 1px solid var(--border); }
    .prod-img img { width: 100%; height: 100%; object-fit: cover; }
    .prod-name { font-weight: 600; font-size: 14px; }
    .prod-sku { font-size: 12px; color: var(--text-secondary); margin-top: 2px; font-family: var(--mono); }
    td.cat { color: var(--text-secondary); }

    .actions { display: flex; gap: 8px; align-items: center; }
    .action-btn { width: 32px; height: 32px; border-radius: var(--radius-sm); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; background: transparent; }
    .action-btn svg { width: 16px; height: 16px; }
    .action-btn.view { color: var(--text-secondary); } .action-btn.view:hover { background: var(--gray-light); }
    .action-btn.edit { color: var(--blue); } .action-btn.edit:hover { background: var(--blue-light); }
    .action-btn.del  { color: var(--red); }  .action-btn.del:hover  { background: var(--red-light); }

    .overlay { position: fixed; inset: 0; background: rgba(10,14,28,0.5); display: none; align-items: center; justify-content: center; z-index: 100; backdrop-filter: blur(4px); }
    .overlay.show { display: flex; }
    .modal { background: #fff; border-radius: 18px; width: 480px; max-width: 95vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 24px 64px rgba(0,0,0,0.18); animation: slideUp 0.22s cubic-bezier(.34,1.56,.64,1); }
    @keyframes slideUp { from { opacity: 0; transform: translateY(24px) scale(0.97); } to { opacity: 1; transform: none; } }
    .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .modal-header h3 { font-size: 17px; font-weight: 700; color: var(--text-primary); letter-spacing: -0.3px; }
    .modal-close { background: var(--gray-light); border: none; border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-secondary); transition: all 0.15s; font-size: 16px; line-height: 1; }
    .modal-close:hover { background: var(--red-light); color: var(--red); }
    .modal-body { padding: 24px; }
    .modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; }

    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .form-group input, .form-group select, .form-group textarea {
      width: 100%; padding: 9px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
      font-size: 14px; outline: none; color: var(--text-primary); background: #fff; font-family: var(--font);
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.08); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    .img-upload-area { border: 2px dashed var(--border); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; position: relative; overflow: hidden; }
    .img-upload-area:hover { border-color: var(--blue); background: #eff6ff; }
    .img-upload-area.has-img { border-style: solid; border-color: var(--border); padding: 0; }
    .img-upload-area input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .img-upload-preview { width: 100%; height: 160px; object-fit: cover; border-radius: 8px; display: none; }
    .img-upload-preview.show { display: block; }
    .img-upload-placeholder { pointer-events: none; }
    .img-upload-placeholder svg { width: 32px; height: 32px; color: #9ca3af; margin: 0 auto 8px; }
    .img-upload-placeholder p { font-size: 13px; color: var(--text-secondary); }
    .img-upload-placeholder span { font-size: 12px; color: #9ca3af; }
    .img-change-hint { font-size: 11px; color: var(--text-secondary); text-align: center; margin-top: 6px; }

    .btn { padding: 9px 18px; border-radius: var(--radius-sm); font-size: 13.5px; font-weight: 600; cursor: pointer; border: none; font-family: var(--font); transition: all 0.15s; }
    .btn-primary { background: var(--blue); color: #fff; } .btn-primary:hover { background: #1d4ed8; }
    .btn-secondary { background: var(--gray-light); color: var(--text-secondary); border: 1px solid var(--border); } .btn-secondary:hover { background: var(--border); }
    .btn-danger { background: var(--red); color: #fff; } .btn-danger:hover { background: #b91c1c; }

    .view-img { width: 80px; height: 80px; border-radius: 14px; background: var(--main-bg); display: flex; align-items: center; justify-content: center; font-size: 40px; margin-bottom: 16px; border: 1px solid var(--border); overflow: hidden; }
    .view-img img { width: 100%; height: 100%; object-fit: cover; }
    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: var(--text-secondary); }
    .detail-value { font-weight: 600; color: var(--text-primary); }

    .confirm-icon { text-align: center; font-size: 48px; margin-bottom: 12px; }
    .confirm-text { text-align: center; color: var(--text-primary); font-size: 15px; font-weight: 600; margin-bottom: 4px; }
    .confirm-sub { text-align: center; color: var(--text-secondary); font-size: 13px; }
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
    <a href="products.php" class="nav-item active">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/></svg>
      Products
    </a>
    <a href="categories.php" class="nav-item">
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
    <div class="nav-logout">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Logout
    </div>
  </div>
</aside>

<div class="main">
  <div class="page-header">
    <h1>Products</h1>
    <button class="btn-add" onclick="openAdd()">
      <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" width="16" height="16">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      Add Product
    </button>
  </div>

  <div class="search-box">
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
      <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
    </svg>
    <input type="text" placeholder="Search products..." id="searchInput" oninput="filterTable()"/>
  </div>

  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="tableBody"></tbody>
    </table>
  </div>
</div>

<div class="overlay" id="viewModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Detail Produk</h3>
      <button class="modal-close" onclick="closeAll()">✕</button>
    </div>
    <div class="modal-body">
      <div class="view-img" id="viewImgWrap"></div>
      <div id="viewDetails"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeAll()">Tutup</button>
    </div>
  </div>
</div>

<div class="overlay" id="formModal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="formTitle">Tambah Produk</h3>
      <button class="modal-close" onclick="closeAll()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editId"/>
      <div class="form-group">
        <label>Foto Produk</label>
        <div class="img-upload-area" id="imgUploadArea">
          <input type="file" id="fImage" accept="image/*" onchange="handleImageUpload(event)"/>
          <img class="img-upload-preview" id="imgPreview" src="" alt="Preview"/>
          <div class="img-upload-placeholder" id="imgPlaceholder">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
            <p>Klik untuk upload foto</p>
            <span>PNG, JPG, WEBP hingga 5MB</span>
          </div>
        </div>
        <p class="img-change-hint" id="imgChangeHint" style="display:none">Klik gambar untuk ganti foto</p>
      </div>
      <div class="form-group">
        <label>Nama Produk</label>
        <input type="text" id="fName" placeholder="Cth: Kemeja Flannel Premium"/>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>ID Produk</label>
          <input type="text" id="fProductId" placeholder="PROD-001"/>
        </div>
        <div class="form-group">
          <label>Kategori</label>
          <select id="fCat">
            <option>Atasan</option><option>Bawahan</option><option>Outerwear</option>
            <option>Sepatu</option><option>Aksesoris</option><option>Dress</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Harga (Rp)</label>
          <input type="number" id="fPrice" placeholder="299000"/>
        </div>
        <div class="form-group">
          <label>Stok</label>
          <input type="number" id="fStock" placeholder="50"/>
        </div>
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea id="fDesc" rows="3" placeholder="Deskripsi singkat produk..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeAll()">Batal</button>
      <button class="btn btn-primary" onclick="saveProduct()">Simpan</button>
    </div>
  </div>
</div>

<div class="overlay" id="deleteModal">
  <div class="modal" style="width:360px">
    <div class="modal-header">
      <h3>Hapus Produk</h3>
      <button class="modal-close" onclick="closeAll()">✕</button>
    </div>
    <div class="modal-body" style="text-align:center;padding:32px 24px">
      <div class="confirm-icon">🗑️</div>
      <p class="confirm-text">Yakin ingin menghapus produk ini?</p>
      <p class="confirm-sub" id="deleteProductName" style="margin-top:6px;font-weight:600;color:var(--text-primary)"></p>
      <p class="confirm-sub" style="margin-top:4px">Tindakan ini tidak dapat dibatalkan.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeAll()">Batal</button>
      <button class="btn btn-danger" onclick="confirmDelete()">Hapus</button>
    </div>
  </div>
</div>

<script>
  let products = [
    {id:1,name:"Kemeja Flannel Premium",productId:"PROD-001",cat:"Atasan",price:299000,stock:45,img:null,emoji:"👕",desc:"Kemeja flannel bahan tebal hangat, cocok untuk gaya kasual outdoor."},
    {id:2,name:"High-Waist Mom Jeans",productId:"PROD-002",cat:"Bawahan",price:349000,stock:32,img:null,emoji:"👖",desc:"Jeans high-waist potongan slim, nyaman dan stylish untuk sehari-hari."},
    {id:3,name:"Bomber Jacket Varsity",productId:"PROD-003",cat:"Outerwear",price:549000,stock:18,img:null,emoji:"🧥",desc:"Jaket bomber varsity dengan aksen stripe pada lengan, premium look."},
    {id:4,name:"Sneakers Canvas Putih",productId:"PROD-004",cat:"Sepatu",price:459000,stock:60,img:null,emoji:"👟",desc:"Sneakers canvas putih klasik, ringan dan serbaguna untuk daily wear."},
    {id:5,name:"Tote Bag Kanvas",productId:"PROD-005",cat:"Aksesoris",price:129000,stock:80,img:null,emoji:"👜",desc:"Tote bag kanvas tebal dengan inner pouch, kapasitas besar."},
    {id:6,name:"Midi Dress Floral",productId:"PROD-006",cat:"Dress",price:389000,stock:25,img:null,emoji:"👗",desc:"Dress midi bermotif bunga, bahan rayon adem cocok untuk musim panas."},
  ];
  let nextId = 7;
  let deleteTargetId = null;
  let currentImageData = null;

  function getProductThumb(p) {
    if (p.img) return `<img src="${p.img}" alt="${p.name}"/>`;
    return p.emoji || '🛍️';
  }

  function renderTable(data) {
    const tbody = document.getElementById('tableBody');
    if (!data.length) {
      tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:32px">Tidak ada produk ditemukan.</td></tr>`;
      return;
    }
    tbody.innerHTML = data.map(p => `
      <tr>
        <td>
          <div class="prod-cell">
            <div class="prod-img">${getProductThumb(p)}</div>
            <div>
              <div class="prod-name">${p.name}</div>
              <div class="prod-sku">${p.productId}</div>
            </div>
          </div>
        </td>
        <td class="cat">${p.cat}</td>
        <td>Rp ${p.price.toLocaleString('id-ID')}</td>
        <td>${p.stock}</td>
        <td>
          <div class="actions">
            <button class="action-btn view" title="Lihat" onclick="openView(${p.id})">
              <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button class="action-btn edit" title="Edit" onclick="openEdit(${p.id})">
              <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <button class="action-btn del" title="Hapus" onclick="openDelete(${p.id})">
              <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    renderTable(products.filter(p =>
      p.name.toLowerCase().includes(q) ||
      p.cat.toLowerCase().includes(q) ||
      p.productId.toLowerCase().includes(q)
    ));
  }

  function handleImageUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
      currentImageData = e.target.result;
      const preview = document.getElementById('imgPreview');
      preview.src = currentImageData;
      preview.classList.add('show');
      document.getElementById('imgPlaceholder').style.display = 'none';
      document.getElementById('imgUploadArea').classList.add('has-img');
      document.getElementById('imgChangeHint').style.display = 'block';
    };
    reader.readAsDataURL(file);
  }

  function resetImageUpload(existingImg) {
    currentImageData = existingImg || null;
    const preview = document.getElementById('imgPreview');
    document.getElementById('fImage').value = '';
    if (existingImg) {
      preview.src = existingImg; preview.classList.add('show');
      document.getElementById('imgPlaceholder').style.display = 'none';
      document.getElementById('imgUploadArea').classList.add('has-img');
      document.getElementById('imgChangeHint').style.display = 'block';
    } else {
      preview.src = ''; preview.classList.remove('show');
      document.getElementById('imgPlaceholder').style.display = 'block';
      document.getElementById('imgUploadArea').classList.remove('has-img');
      document.getElementById('imgChangeHint').style.display = 'none';
    }
  }

  function openView(id) {
    const p = products.find(x => x.id === id);
    const wrap = document.getElementById('viewImgWrap');
    wrap.innerHTML = p.img ? `<img src="${p.img}" alt="${p.name}" style="width:100%;height:100%;object-fit:cover"/>` : (p.emoji || '🛍️');
    document.getElementById('viewDetails').innerHTML = `
      <div class="detail-row"><span class="detail-label">Nama</span><span class="detail-value">${p.name}</span></div>
      <div class="detail-row"><span class="detail-label">ID Produk</span><span class="detail-value">${p.productId}</span></div>
      <div class="detail-row"><span class="detail-label">Kategori</span><span class="detail-value">${p.cat}</span></div>
      <div class="detail-row"><span class="detail-label">Harga</span><span class="detail-value">Rp ${p.price.toLocaleString('id-ID')}</span></div>
      <div class="detail-row"><span class="detail-label">Stok</span><span class="detail-value">${p.stock} pcs</span></div>
      <div class="detail-row"><span class="detail-label">Deskripsi</span><span class="detail-value" style="max-width:260px;text-align:right">${p.desc}</span></div>
    `;
    document.getElementById('viewModal').classList.add('show');
  }

  function openAdd() {
    document.getElementById('formTitle').textContent = 'Tambah Produk';
    document.getElementById('editId').value = '';
    ['fName','fProductId','fDesc'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('fPrice').value = '';
    document.getElementById('fStock').value = '';
    document.getElementById('fCat').value = 'Atasan';
    resetImageUpload(null);
    document.getElementById('formModal').classList.add('show');
  }

  function openEdit(id) {
    const p = products.find(x => x.id === id);
    document.getElementById('formTitle').textContent = 'Edit Produk';
    document.getElementById('editId').value = id;
    document.getElementById('fName').value = p.name;
    document.getElementById('fProductId').value = p.productId;
    document.getElementById('fCat').value = p.cat;
    document.getElementById('fPrice').value = p.price;
    document.getElementById('fStock').value = p.stock;
    document.getElementById('fDesc').value = p.desc;
    resetImageUpload(p.img || null);
    document.getElementById('formModal').classList.add('show');
  }

  function saveProduct() {
    const name = document.getElementById('fName').value.trim();
    const price = parseInt(document.getElementById('fPrice').value) || 0;
    const stock = parseInt(document.getElementById('fStock').value) || 0;
    if (!name) { alert('Nama produk harus diisi!'); return; }
    const data = {
      name, productId: document.getElementById('fProductId').value.trim() || 'PROD-' + String(nextId).padStart(3,'0'),
      cat: document.getElementById('fCat').value, price, stock,
      img: currentImageData, emoji: '🛍️', desc: document.getElementById('fDesc').value.trim()
    };
    const editId = document.getElementById('editId').value;
    if (editId) {
      const i = products.findIndex(x => x.id === parseInt(editId));
      products[i] = { ...products[i], ...data };
    } else {
      products.push({ id: nextId++, ...data });
    }
    closeAll(); filterTable();
  }

  function openDelete(id) {
    deleteTargetId = id;
    document.getElementById('deleteProductName').textContent = products.find(x => x.id === id).name;
    document.getElementById('deleteModal').classList.add('show');
  }

  function confirmDelete() {
    products = products.filter(x => x.id !== deleteTargetId);
    closeAll(); filterTable();
  }

  function closeAll() {
    document.querySelectorAll('.overlay').forEach(el => el.classList.remove('show'));
  }

  document.querySelectorAll('.overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) closeAll(); });
  });

  renderTable(products);
</script>
</body>
</html>