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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel - Pelanggan</title>
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
    --yellow: #ca8a04;
    --yellow-light: #fef9c3;
    --red: #dc2626;
    --red-light: #fee2e2;
    --gray: #64748b;
    --gray-light: #f1f5f9;
    --shadow: 0 1px 4px rgba(0,0,0,0.07), 0 4px 16px rgba(0,0,0,0.05);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.13);
    --radius: 14px;
    --radius-sm: 8px;
    --font: 'DM Sans', sans-serif;
    --mono: 'JetBrains Mono', monospace;
  }

  body { font-family: var(--font); background: var(--main-bg); color: var(--text-primary); display: flex; min-height: 100vh; font-size: 14px; }

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
  .nav-item.active { background: var(--sidebar-active); color: #fff; border-radius: 0 8px 8px 0; margin-right: 12px; }
  .nav-item svg { width: 17px; height: 17px; flex-shrink: 0; }
  .sidebar-bottom { padding: 16px 24px 0; border-top: 1px solid #1e2535; margin-top: 16px; }
  .nav-logout { display: flex; align-items: center; gap: 10px; color: #5a6480; cursor: pointer; font-size: 13.5px; font-weight: 500; padding: 8px 0; transition: color 0.15s; }
  .nav-logout:hover { color: var(--red); }

  .main { margin-left: 230px; flex: 1; padding: 36px 40px; min-height: 100vh; }

  .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
  .page-title { font-size: 28px; font-weight: 700; letter-spacing: -0.6px; }
  .btn-export {
    display: flex; align-items: center; gap: 8px; background: var(--blue);
    color: #fff; border: none; border-radius: var(--radius-sm); padding: 11px 20px;
    font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: var(--font);
    box-shadow: 0 2px 8px rgba(37,99,235,0.25); transition: all 0.18s;
  }
  .btn-export:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(37,99,235,0.35); }

  .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
  .stat-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 20px; box-shadow: var(--shadow); }
  .stat-label { font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
  .stat-value { font-size: 22px; font-weight: 700; margin-top: 4px; letter-spacing: -0.5px; }
  .stat-value.blue { color: var(--blue); }
  .stat-value.green { color: var(--green); }
  .stat-value.red { color: var(--red); }
  .stat-value.yellow { color: var(--yellow); }

  .filters-bar {
    background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 16px 20px; display: flex; gap: 12px; margin-bottom: 24px; box-shadow: var(--shadow);
  }
  .search-wrap { flex: 1; position: relative; }
  .search-wrap svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: #a0aab8; pointer-events: none; }
  .search-input {
    width: 100%; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    padding: 9px 14px 9px 38px; font-size: 13.5px; font-family: var(--font);
    color: var(--text-primary); background: var(--main-bg); outline: none; transition: border 0.16s;
  }
  .search-input:focus { border-color: var(--blue); background: #fff; }
  .search-input::placeholder { color: #a0aab8; }
  .filter-select {
    border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    padding: 9px 14px; font-size: 13.5px; font-family: var(--font);
    color: var(--text-secondary); background: var(--main-bg); cursor: pointer; outline: none;
    transition: border 0.16s; min-width: 140px;
  }
  .filter-select:focus { border-color: var(--blue); }

  .customers-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
  @media (max-width: 1200px) { .customers-grid { grid-template-columns: repeat(2, 1fr); } }

  .customer-card {
    background: var(--card-bg); border: 1.5px solid var(--border); border-radius: var(--radius);
    padding: 22px; box-shadow: var(--shadow); transition: all 0.2s; position: relative;
    overflow: hidden;
  }
  .customer-card:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); border-color: #d0d8ee; }
  .customer-card.blocked { border-color: #fca5a5; background: #fffafa; opacity: 0.85; }
  .blocked-ribbon {
    position: absolute; top: 14px; right: -24px; background: var(--red);
    color: #fff; font-size: 10px; font-weight: 700; padding: 3px 32px;
    transform: rotate(35deg); letter-spacing: 0.8px; text-transform: uppercase;
  }

  .card-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 18px; }
  .customer-info { display: flex; align-items: center; gap: 14px; }
  .avatar {
    width: 52px; height: 52px; border-radius: 50%; background: #e8eeff;
    display: flex; align-items: center; justify-content: center; font-size: 26px;
    flex-shrink: 0; border: 2px solid #d0d8f5;
  }
  .avatar.blocked-av { background: #fee2e2; border-color: #fca5a5; filter: grayscale(0.4); }
  .customer-name { font-size: 16px; font-weight: 700; letter-spacing: -0.2px; }
  .customer-id { font-size: 12px; color: var(--text-secondary); margin-top: 2px; font-family: var(--mono); }
  .view-btn {
    background: none; border: none; cursor: pointer; color: var(--blue); padding: 4px;
    border-radius: 6px; transition: all 0.15s; flex-shrink: 0;
  }
  .view-btn:hover { background: var(--blue-light); }
  .view-btn svg { width: 19px; height: 19px; display: block; }

  .card-contacts { display: flex; flex-direction: column; gap: 7px; margin-bottom: 18px; }
  .contact-row { display: flex; align-items: center; gap: 9px; font-size: 13px; color: var(--text-secondary); }
  .contact-row svg { width: 14px; height: 14px; flex-shrink: 0; color: #9daac8; }

  .card-divider { height: 1px; background: var(--border); margin-bottom: 16px; }

  .card-stats { display: flex; gap: 28px; margin-bottom: 14px; }
  .cstat-label { font-size: 11.5px; color: var(--text-secondary); font-weight: 500; margin-bottom: 3px; }
  .cstat-value { font-size: 16px; font-weight: 700; letter-spacing: -0.3px; }
  .cstat-value.green { color: var(--green); }

  .card-footer { display: flex; align-items: center; justify-content: space-between; }
  .joined-date { font-size: 12px; color: var(--text-secondary); }

  .status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 600;
  }
  .status-badge.aktif { background: var(--green-light); color: var(--green); }
  .status-badge.diblokir { background: var(--red-light); color: var(--red); }
  .status-dot { width: 6px; height: 6px; border-radius: 50%; }
  .status-badge.aktif .status-dot { background: var(--green); }
  .status-badge.diblokir .status-dot { background: var(--red); }

  .status-badge.completed { background: var(--green-light); color: var(--green); }
  .status-badge.processing { background: var(--blue-light); color: var(--blue); }
  .status-badge.pending { background: var(--yellow-light); color: var(--yellow); }
  .status-badge.cancelled { background: var(--red-light); color: var(--red); }
  .status-badge.shipped { background: #ede9fe; color: #7c3aed; }

  .empty-state { text-align: center; padding: 60px 20px; color: var(--text-secondary); grid-column: 1/-1; }
  .empty-state svg { width: 44px; height: 44px; margin-bottom: 12px; opacity: 0.25; }

  .modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(10,14,28,0.5);
    z-index: 100; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
  }
  .modal-overlay.open { display: flex; }
  .modal {
    background: #fff; border-radius: 18px; width: 540px; max-width: 95vw;
    box-shadow: 0 24px 64px rgba(0,0,0,0.18); animation: slideUp 0.22s cubic-bezier(.34,1.56,.64,1);
    max-height: 90vh; overflow-y: auto;
  }
  @keyframes slideUp { from { opacity: 0; transform: translateY(24px) scale(0.97); } to { opacity: 1; transform: none; } }

  .modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 22px 24px 18px; border-bottom: 1px solid var(--border);
  }
  .modal-title { font-size: 17px; font-weight: 700; letter-spacing: -0.3px; }
  .modal-close {
    background: var(--gray-light); border: none; border-radius: 8px;
    width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: var(--text-secondary); transition: all 0.15s;
  }
  .modal-close:hover { background: var(--red-light); color: var(--red); }
  .modal-body { padding: 24px; }

  .modal-profile { display: flex; align-items: center; gap: 18px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
  .modal-avatar { width: 64px; height: 64px; border-radius: 50%; background: #e8eeff; display: flex; align-items: center; justify-content: center; font-size: 32px; border: 2.5px solid #d0d8f5; flex-shrink: 0; }
  .modal-name { font-size: 20px; font-weight: 700; letter-spacing: -0.3px; }
  .modal-cid { font-size: 13px; color: var(--text-secondary); font-family: var(--mono); margin-top: 2px; }
  .modal-status-wrap { margin-top: 6px; }

  .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px; }
  .detail-item label { display: block; font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; }
  .detail-item .val { font-size: 14px; font-weight: 600; color: var(--text-primary); }
  .detail-item .val.mono { font-family: var(--mono); color: var(--blue); font-size: 13px; }
  .detail-item .val.green { color: var(--green); }

  .section-title { font-size: 12px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.7px; margin-bottom: 12px; }

  .order-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
  .order-row {
    display: flex; align-items: center; justify-content: space-between;
    background: var(--main-bg); border: 1px solid var(--border); border-radius: var(--radius-sm);
    padding: 10px 14px; font-size: 13px;
  }
  .order-row-id { font-family: var(--mono); color: var(--blue); font-weight: 500; font-size: 12px; }
  .order-row-date { color: var(--text-secondary); font-size: 12px; }
  .order-row-amount { font-weight: 700; font-family: var(--mono); }

  .block-section { margin-top: 20px; padding: 18px; border-radius: var(--radius-sm); border: 1.5px solid var(--border); }
  .block-section.is-blocked { background: #fff8f8; border-color: #fca5a5; }
  .block-header { display: flex; align-items: center; justify-content: space-between; }
  .block-title { font-size: 14px; font-weight: 700; color: var(--text-primary); }
  .block-desc { font-size: 12.5px; color: var(--text-secondary); margin-top: 3px; }
  .block-desc.red { color: var(--red); }

  .btn-block {
    display: flex; align-items: center; gap: 7px; padding: 9px 16px;
    border-radius: var(--radius-sm); font-size: 13px; font-weight: 600;
    cursor: pointer; font-family: var(--font); border: none; transition: all 0.18s; white-space: nowrap;
  }
  .btn-block.do-block { background: var(--red-light); color: var(--red); }
  .btn-block.do-block:hover { background: var(--red); color: #fff; }
  .btn-block.do-unblock { background: var(--green-light); color: var(--green); }
  .btn-block.do-unblock:hover { background: var(--green); color: #fff; }
  .btn-block svg { width: 14px; height: 14px; }

  .block-reason-wrap { margin-top: 14px; }
  .block-reason-wrap label { display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
  .block-reason-select {
    width: 100%; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    padding: 9px 12px; font-size: 13px; font-family: var(--font); color: var(--text-primary);
    background: #fff; outline: none; cursor: pointer;
  }
  .block-reason-select:focus { border-color: var(--red); }
  .block-note-input {
    width: 100%; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    padding: 9px 12px; font-size: 13px; font-family: var(--font); color: var(--text-primary);
    background: #fff; outline: none; resize: vertical; margin-top: 8px; min-height: 70px;
  }
  .block-note-input::placeholder { color: #a0aab8; }
  .block-note-input:focus { border-color: var(--red); }
  .btn-confirm-block {
    margin-top: 10px; width: 100%; background: var(--red); color: #fff; border: none;
    border-radius: var(--radius-sm); padding: 10px; font-size: 13.5px; font-weight: 700;
    cursor: pointer; font-family: var(--font); transition: all 0.15s;
  }
  .btn-confirm-block:hover { background: #b91c1c; }

  .modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; }
  .btn-close-modal { background: var(--gray-light); border: none; border-radius: var(--radius-sm); padding: 9px 20px; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: var(--font); color: var(--text-secondary); transition: all 0.15s; }
  .btn-close-modal:hover { background: var(--border); }

  .confirm-modal {
    background: #fff; border-radius: 16px; width: 400px; max-width: 92vw;
    box-shadow: 0 20px 56px rgba(0,0,0,0.2); animation: slideUp 0.2s cubic-bezier(.34,1.56,.64,1);
    padding: 28px; text-align: center;
  }
  .confirm-icon { font-size: 40px; margin-bottom: 14px; }
  .confirm-title { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
  .confirm-desc { font-size: 13.5px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 22px; }
  .confirm-actions { display: flex; gap: 10px; justify-content: center; }
  .btn-confirm-yes { background: var(--red); color: #fff; border: none; border-radius: var(--radius-sm); padding: 10px 22px; font-size: 13.5px; font-weight: 700; cursor: pointer; font-family: var(--font); transition: all 0.15s; }
  .btn-confirm-yes:hover { background: #b91c1c; }
  .btn-confirm-yes.green { background: var(--green); }
  .btn-confirm-yes.green:hover { background: #15803d; }
  .btn-confirm-no { background: var(--gray-light); color: var(--text-secondary); border: none; border-radius: var(--radius-sm); padding: 10px 22px; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: var(--font); }

  .toast {
    position: fixed; bottom: 28px; right: 28px; background: #141928; color: #fff;
    padding: 12px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 500;
    box-shadow: var(--shadow-lg); z-index: 300; display: flex; align-items: center; gap: 10px;
    animation: toastIn 0.25s cubic-bezier(.34,1.56,.64,1);
  }
  .toast.hidden { display: none; }
  @keyframes toastIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
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
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <rect x="3" y="3" width="7" height="7" rx="1.5" stroke-width="2"/>
        <rect x="14" y="3" width="7" height="7" rx="1.5" stroke-width="2"/>
        <rect x="3" y="14" width="7" height="7" rx="1.5" stroke-width="2"/>
        <rect x="14" y="14" width="7" height="7" rx="1.5" stroke-width="2"/>
      </svg>
      Dashboard
    </a>
    <a href="products.php" class="nav-item">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-width="2" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM16 3H8a2 2 0 00-2 2v2h12V5a2 2 0 00-2-2z"/>
      </svg>
      Products
    </a>
    <a href="categories.php" class="nav-item">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-width="2" d="M3 10h18M3 14h18M9 3v18M15 3v18" stroke-linecap="round"/>
      </svg>
      Categories
    </a>
    <a href="orders.php" class="nav-item">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-width="2" d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18M16 10a4 4 0 01-8 0"/>
      </svg>
      Orders
    </a>
    <a href="customers.php" class="nav-item active">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="8" r="4" stroke-width="2"/>
        <path stroke-width="2" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
      </svg>
      Customers
    </a>
  </nav>
  <div class="sidebar-bottom">
    <a href="../config/logout.php" class="nav-logout" style="text-decoration: none;">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
        <path stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      Logout
    </a>
  </div>
</aside>

<main class="main">
  <div class="page-header">
    <h1 class="page-title">Pelanggan</h1>
    <button class="btn-export" onclick="exportCSV()">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15"><path stroke-width="2" stroke-linecap="round" d="M12 3v13m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
      Ekspor Pelanggan
    </button>
  </div>

  <div class="stats-row" id="statsRow"></div>

  <div class="filters-bar">
    <div class="search-wrap">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
      <input type="text" class="search-input" id="searchInput" placeholder="Cari pelanggan..." oninput="filterCustomers()">
    </div>
    <select class="filter-select" id="statusFilter" onchange="filterCustomers()">
      <option value="">Semua Status</option>
      <option value="aktif">Aktif</option>
      <option value="diblokir">Diblokir</option>
    </select>
    <select class="filter-select" id="sortFilter" onchange="filterCustomers()">
      <option value="name">Urutkan: Nama</option>
      <option value="orders">Urutkan: Pesanan Terbanyak</option>
      <option value="spent">Urutkan: Pengeluaran Terbesar</option>
      <option value="joined">Urutkan: Terbaru Bergabung</option>
    </select>
  </div>

  <div class="customers-grid" id="customersGrid"></div>
</main>

<div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
  <div class="modal" id="modalBox">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Detail Pelanggan</div>
      <button class="modal-close" onclick="closeModal()">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15"><path stroke-width="2.5" stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-footer">
      <button class="btn-close-modal" onclick="closeModal()">Tutup</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="confirmOverlay">
  <div class="confirm-modal" id="confirmBox">
    <div class="confirm-icon" id="confirmIcon">🚫</div>
    <div class="confirm-title" id="confirmTitle">Buka Blokir Akun?</div>
    <div class="confirm-desc" id="confirmDesc">Akun ini akan diaktifkan kembali.</div>
    <div class="confirm-actions">
      <button class="btn-confirm-no" onclick="closeConfirm()">Batal</button>
      <button class="btn-confirm-yes" id="confirmYesBtn" onclick="doConfirmAction()">Ya, Buka Blokir</button>
    </div>
  </div>
</div>

<div class="toast hidden" id="toast">
  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-width="2.5" stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
  <span id="toastMsg"></span>
</div>

<script>
let customers = [
  {
    id: 'PLGN-0001', name: 'Budi Santoso', emoji: '🧑', email: 'budi@example.com',
    phone: '+62 812 3456 7890', location: 'Jakarta, Indonesia', joined: '15 Jan 2025',
    orders: 12, spent: 45600000, status: 'aktif', blockReason: '', blockNote: '',
    orderHistory: [
      { id: '#PSN-001', date: '15 Apr 2026', amount: 1455000, status: 'completed' },
      { id: '#PSN-008', date: '22 Mar 2026', amount: 22500000, status: 'completed' },
      { id: '#PSN-015', date: '10 Feb 2026', amount: 950000, status: 'completed' },
    ]
  },
  {
    id: 'PLGN-0002', name: 'Siti Rahayu', emoji: '👩', email: 'siti@example.com',
    phone: '+62 813 2345 6789', location: 'Bandung, Indonesia', joined: '20 Feb 2025',
    orders: 8, spent: 32500000, status: 'aktif', blockReason: '', blockNote: '',
    orderHistory: [
      { id: '#PSN-002', date: '16 Apr 2026', amount: 22500000, status: 'processing' },
      { id: '#PSN-009', date: '01 Mar 2026', amount: 9800000, status: 'completed' },
    ]
  },
  {
    id: 'PLGN-0003', name: 'Agus Wijaya', emoji: '🧔', email: 'agus@example.com',
    phone: '+62 821 9876 5432', location: 'Surabaya, Indonesia', joined: '10 Nov 2024',
    orders: 15, spent: 78900000, status: 'aktif', blockReason: '', blockNote: '',
    orderHistory: [
      { id: '#PSN-003', date: '17 Apr 2026', amount: 3800000, status: 'pending' },
      { id: '#PSN-010', date: '15 Mar 2026', amount: 18500000, status: 'completed' },
      { id: '#PSN-016', date: '28 Jan 2026', amount: 1750000, status: 'completed' },
    ]
  },
  {
    id: 'PLGN-0004', name: 'Dewi Kusuma', emoji: '👱‍♀️', email: 'dewi@example.com',
    phone: '+62 857 1234 5678', location: 'Medan, Indonesia', joined: '05 Mar 2025',
    orders: 5, spent: 27400000, status: 'aktif', blockReason: '', blockNote: '',
    orderHistory: [
      { id: '#PSN-004', date: '17 Apr 2026', amount: 18500000, status: 'completed' },
    ]
  },
  {
    id: 'PLGN-0005', name: 'Hendra Pratama', emoji: '🧑‍💼', email: 'hendra@example.com',
    phone: '+62 878 5678 1234', location: 'Yogyakarta, Indonesia', joined: '20 Agu 2024',
    orders: 21, spent: 112000000, status: 'aktif', blockReason: '', blockNote: '',
    orderHistory: [
      { id: '#PSN-005', date: '18 Apr 2026', amount: 5150000, status: 'shipped' },
      { id: '#PSN-011', date: '25 Feb 2026', amount: 22500000, status: 'completed' },
    ]
  },
  {
    id: 'PLGN-0006', name: 'Rina Melati', emoji: '👸', email: 'rina@example.com',
    phone: '+62 895 4321 8765', location: 'Bandung, Indonesia', joined: '01 Des 2024',
    orders: 3, spent: 9800000, status: 'diblokir', blockReason: 'Penipuan / Fraud', blockNote: 'Melakukan chargeback palsu sebanyak 3 kali.',
    orderHistory: [
      { id: '#PSN-006', date: '18 Apr 2026', amount: 9800000, status: 'cancelled' },
    ]
  },
  {
    id: 'PLGN-0007', name: 'Fajar Nugroho', emoji: '🧑‍🎨', email: 'fajar@example.com',
    phone: '+62 812 8765 4321', location: 'Semarang, Indonesia', joined: '01 Apr 2025',
    orders: 2, spent: 2850000, status: 'aktif', blockReason: '', blockNote: '',
    orderHistory: [
      { id: '#PSN-007', date: '19 Apr 2026', amount: 2850000, status: 'processing' },
    ]
  },
  {
    id: 'PLGN-0008', name: 'Fitri Handayani', emoji: '👩‍🦰', email: 'fitri@example.com',
    phone: '+62 838 1111 2222', location: 'Makassar, Indonesia', joined: '14 Sep 2024',
    orders: 7, spent: 41500000, status: 'diblokir', blockReason: 'Spam / Penyalahgunaan', blockNote: 'Mengirim ribuan request palsu ke sistem.',
    orderHistory: [
      { id: '#PSN-020', date: '10 Mar 2026', amount: 22500000, status: 'completed' },
    ]
  },
];

const statusOrderLabel = {
  completed: 'Selesai',
  processing: 'Diproses',
  pending: 'Menunggu',
  cancelled: 'Dibatalkan',
  shipped: 'Dikirim'
};

let filteredCustomers = [...customers];
let activeCustomerId = null;
let pendingAction = null;

function fmt(val) {
  return 'Rp ' + val.toLocaleString('id-ID');
}

function renderStats() {
  const total = customers.length;
  const active = customers.filter(c => c.status === 'aktif').length;
  const blocked = customers.filter(c => c.status === 'diblokir').length;
  const revenue = customers.reduce((s, c) => s + c.spent, 0);
  document.getElementById('statsRow').innerHTML = `
    <div class="stat-card"><div class="stat-label">Total Pelanggan</div><div class="stat-value blue">${total}</div></div>
    <div class="stat-card"><div class="stat-label">Aktif</div><div class="stat-value green">${active}</div></div>
    <div class="stat-card"><div class="stat-label">Diblokir</div><div class="stat-value red">${blocked}</div></div>
    <div class="stat-card"><div class="stat-label">Total Pendapatan</div><div class="stat-value green" style="font-size:16px">${fmt(revenue)}</div></div>
  `;
}

function renderGrid() {
  const grid = document.getElementById('customersGrid');
  if (filteredCustomers.length === 0) {
    grid.innerHTML = `<div class="empty-state">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke-width="1.5"/><path stroke-width="1.5" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
      <p>Tidak ada pelanggan ditemukan</p>
    </div>`;
    return;
  }
  grid.innerHTML = filteredCustomers.map(c => `
    <div class="customer-card ${c.status === 'diblokir' ? 'blocked' : ''}" id="card-${c.id}">
      ${c.status === 'diblokir' ? '<div class="blocked-ribbon">Diblokir</div>' : ''}
      <div class="card-header">
        <div class="customer-info">
          <div class="avatar ${c.status === 'diblokir' ? 'blocked-av' : ''}">${c.emoji}</div>
          <div>
            <div class="customer-name">${c.name}</div>
            <div class="customer-id">Pelanggan #${c.id.split('-')[1]}</div>
          </div>
        </div>
        <button class="view-btn" onclick="openModal('${c.id}')" title="Lihat Detail">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        </button>
      </div>
      <div class="card-contacts">
        <div class="contact-row">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          ${c.email}
        </div>
        <div class="contact-row">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          ${c.phone}
        </div>
        <div class="contact-row">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          ${c.location}
        </div>
      </div>
      <div class="card-divider"></div>
      <div class="card-stats">
        <div>
          <div class="cstat-label">Total Pesanan</div>
          <div class="cstat-value">${c.orders}</div>
        </div>
        <div>
          <div class="cstat-label">Total Belanja</div>
          <div class="cstat-value green" style="font-size:13px">${fmt(c.spent)}</div>
        </div>
      </div>
      <div class="card-footer">
        <span class="joined-date">Bergabung: ${c.joined}</span>
        <span class="status-badge ${c.status}">
          <span class="status-dot"></span>${c.status === 'aktif' ? 'Aktif' : 'Diblokir'}
        </span>
      </div>
    </div>
  `).join('');
}

function filterCustomers() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const st = document.getElementById('statusFilter').value;
  const sort = document.getElementById('sortFilter').value;
  filteredCustomers = customers.filter(c => {
    const matchQ = !q || c.name.toLowerCase().includes(q) || c.email.toLowerCase().includes(q) || c.id.toLowerCase().includes(q) || c.location.toLowerCase().includes(q);
    const matchSt = !st || c.status === st;
    return matchQ && matchSt;
  });
  if (sort === 'orders') filteredCustomers.sort((a, b) => b.orders - a.orders);
  else if (sort === 'spent') filteredCustomers.sort((a, b) => b.spent - a.spent);
  else if (sort === 'joined') filteredCustomers.sort((a, b) => b.id.localeCompare(a.id));
  else filteredCustomers.sort((a, b) => a.name.localeCompare(b.name));
  renderGrid();
}

function openModal(customerId) {
  activeCustomerId = customerId;
  const c = customers.find(x => x.id === customerId);
  document.getElementById('modalTitle').textContent = 'Detail Pelanggan';

  const isBlocked = c.status === 'diblokir';
  document.getElementById('modalBody').innerHTML = `
    <div class="modal-profile">
      <div class="modal-avatar">${c.emoji}</div>
      <div>
        <div class="modal-name">${c.name}</div>
        <div class="modal-cid">${c.id}</div>
        <div class="modal-status-wrap">
          <span class="status-badge ${c.status}">
            <span class="status-dot"></span>${c.status === 'aktif' ? 'Aktif' : 'Diblokir'}
          </span>
        </div>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-item"><label>Email</label><div class="val" style="font-size:13px">${c.email}</div></div>
      <div class="detail-item"><label>Telepon</label><div class="val">${c.phone}</div></div>
      <div class="detail-item"><label>Lokasi</label><div class="val">${c.location}</div></div>
      <div class="detail-item"><label>Bergabung</label><div class="val">${c.joined}</div></div>
      <div class="detail-item"><label>Total Pesanan</label><div class="val">${c.orders}</div></div>
      <div class="detail-item"><label>Total Belanja</label><div class="val green" style="font-size:13px">${fmt(c.spent)}</div></div>
    </div>

    <div style="height:1px;background:var(--border);margin-bottom:18px"></div>
    <div class="section-title">Riwayat Pesanan Terbaru</div>
    <div class="order-list">
      ${c.orderHistory.map(o => `
        <div class="order-row">
          <span class="order-row-id">${o.id}</span>
          <span class="order-row-date">${o.date}</span>
          <span class="order-row-amount">${fmt(o.amount)}</span>
          <span class="status-badge ${o.status}" style="font-size:11px;padding:2px 8px">${statusOrderLabel[o.status] || o.status}</span>
        </div>
      `).join('')}
    </div>

    <div style="height:1px;background:var(--border);margin-bottom:18px"></div>
    <div class="section-title">Manajemen Akun</div>

    <div class="block-section ${isBlocked ? 'is-blocked' : ''}" id="blockSection">
      ${isBlocked ? `
        <div class="block-header">
          <div class="block-info">
            <div class="block-title">🔒 Akun Diblokir</div>
            <div class="block-desc red">Alasan: ${c.blockReason}</div>
            ${c.blockNote ? `<div class="block-desc" style="margin-top:3px;font-size:12px">${c.blockNote}</div>` : ''}
          </div>
          <button class="btn-block do-unblock" onclick="promptUnblock('${c.id}')">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
            Buka Blokir
          </button>
        </div>
      ` : `
        <div class="block-header">
          <div class="block-info">
            <div class="block-title">Blokir Akun</div>
            <div class="block-desc">Pelanggan tidak bisa login & bertransaksi setelah diblokir.</div>
          </div>
          <button class="btn-block do-block" onclick="showBlockForm('${c.id}')">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            Blokir Akun
          </button>
        </div>
        <div class="block-reason-wrap" id="blockForm" style="display:none">
          <label>Alasan Pemblokiran</label>
          <select class="block-reason-select" id="blockReasonSel">
            <option value="">-- Pilih alasan --</option>
            <option>Penipuan / Fraud</option>
            <option>Spam / Penyalahgunaan</option>
            <option>Pelanggaran Ketentuan</option>
            <option>Permintaan Pelanggan</option>
            <option>Aktivitas Mencurigakan</option>
            <option>Lainnya</option>
          </select>
          <textarea class="block-note-input" id="blockNoteInput" placeholder="Catatan tambahan (opsional)..."></textarea>
          <button class="btn-confirm-block" onclick="submitBlock('${c.id}')">🚫 Konfirmasi Blokir Akun</button>
        </div>
      `}
    </div>
  `;
  document.getElementById('modalOverlay').classList.add('open');
}

function showBlockForm(id) {
  const form = document.getElementById('blockForm');
  if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function submitBlock(id) {
  const reason = document.getElementById('blockReasonSel').value;
  const note = document.getElementById('blockNoteInput').value;
  if (!reason) { showToast('Pilih alasan pemblokiran terlebih dahulu!', true); return; }
  const c = customers.find(x => x.id === id);
  c.status = 'diblokir';
  c.blockReason = reason;
  c.blockNote = note;
  closeModal();
  renderStats();
  filterCustomers();
  showToast(`Akun ${c.name} berhasil diblokir.`);
}

function promptUnblock(id) {
  pendingAction = { type: 'unblock', id };
  const c = customers.find(x => x.id === id);
  document.getElementById('confirmIcon').textContent = '🔓';
  document.getElementById('confirmTitle').textContent = 'Buka Blokir Akun?';
  document.getElementById('confirmDesc').textContent = `Akun ${c.name} akan diaktifkan kembali dan dapat login serta bertransaksi.`;
  const btn = document.getElementById('confirmYesBtn');
  btn.textContent = 'Ya, Buka Blokir';
  btn.className = 'btn-confirm-yes green';
  document.getElementById('confirmOverlay').classList.add('open');
}

function doConfirmAction() {
  if (!pendingAction) return;
  const c = customers.find(x => x.id === pendingAction.id);
  if (pendingAction.type === 'unblock') {
    c.status = 'aktif';
    c.blockReason = '';
    c.blockNote = '';
    closeConfirm();
    closeModal();
    renderStats();
    filterCustomers();
    showToast(`Akun ${c.name} berhasil dibuka blokir.`);
  }
  pendingAction = null;
}

function closeConfirm() {
  document.getElementById('confirmOverlay').classList.remove('open');
  pendingAction = null;
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  activeCustomerId = null;
}

function handleOverlayClick(e) {
  if (e.target === document.getElementById('modalOverlay')) closeModal();
}

function exportCSV() {
  const rows = [['ID Pelanggan','Nama','Email','Telepon','Lokasi','Bergabung','Total Pesanan','Total Belanja','Status','Alasan Blokir']];
  customers.forEach(c => {
    rows.push([c.id, c.name, c.email, c.phone, c.location, c.joined, c.orders, c.spent, c.status === 'aktif' ? 'Aktif' : 'Diblokir', c.blockReason]);
  });
  const csv = rows.map(r => r.map(v => `"${v}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href = url; a.download = 'ekspor_pelanggan.csv'; a.click();
  URL.revokeObjectURL(url);
  showToast('Pelanggan berhasil diekspor ke CSV!');
}

function showToast(msg, isError = false) {
  const t = document.getElementById('toast');
  t.style.background = isError ? '#dc2626' : '#141928';
  document.getElementById('toastMsg').textContent = msg;
  t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 3000);
}

filterCustomers();
renderStats();
</script>
</body>
</html>