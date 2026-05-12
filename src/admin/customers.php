<?php
$conn = new mysqli("localhost", "root", "", "recloth");

if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

$result = $conn->query("SELECT id, name, email, role, created_at FROM users WHERE role = 'user'");
$customers = [];

while ($row = $result->fetch_assoc()) {
  $row['addresses'] = [];
  $customers[$row['id']] = $row;
}

if (!empty($customers)) {
  $userIds = implode(',', array_keys($customers));
  $addrResult = $conn->query("SELECT id, user_id, label, address, zip_code, is_default FROM user_addresses WHERE user_id IN ($userIds) ORDER BY is_default DESC, created_at ASC");
  while ($addr = $addrResult->fetch_assoc()) {
    if (isset($customers[$addr['user_id']])) {
      $customers[$addr['user_id']]['addresses'][] = $addr;
    }
  }
}

$customers = array_values($customers);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel - Pelanggan</title>
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
            --primary: #6a7f52;
            --primary-hover: #526340;
      --sidebar-bg: #bac6a9;
      --sidebar-text: #4e5a42;
      --sidebar-active: #a8b696;
      --main-bg: #f3eddf;
      --card-bg: #bac6a9;
      --border: #a4b391;
      --text-primary: #2e3522;
      --text-secondary: #4e5a42;
      --black: #36442c;
      --blue: #36442c;
      --blue-light: #bac6a9;
      --green: #1ea672;
      --green-light: #e8f6f1;
      --yellow: #ca8a04;
      --yellow-light: #fef9c3;
      --red: #d24e4e;
      --red-light: #fbeeee;
      --gray: #6f6f6f;
      --gray-light: #f1f1f1;
      --shadow: 0 8px 18px rgba(17, 17, 17, 0.04);
      --radius: 16px;
      --radius-sm: 8px;
      --font: 'Montserrat', sans-serif;
      --font-title: 'Archivo Black', sans-serif;
      --mono: 'JetBrains Mono', monospace;
    }

  body { font-family: var(--font); background: var(--main-bg); color: var(--text-primary); display: flex; min-height: 100vh; font-size: 14px; }

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

  .main { margin-left: 230px; flex: 1; padding: 36px 40px; min-height: 100vh; }

  .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
  .page-title { font-size: 28px; font-weight: 700; letter-spacing: -0.6px; color: var(--black); }
  .btn-export {
    display: flex; align-items: center; gap: 8px; background: var(--primary);
    color: #fff; border: none; border-radius: var(--radius-sm); padding: 11px 20px;
    font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: var(--font);
    box-shadow: 0 2px 8px rgba(17,17,17,0.25); transition: all 0.18s;
  }
  .btn-export:hover { background: #333; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(17,17,17,0.35); }

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
    width: 100%; border: none;
    padding: 9px 14px 9px 38px; font-size: 13.5px; font-family: var(--font);
    color: var(--text-primary); background: transparent; outline: none;
  }
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

  .address-section { margin-top: 12px; }
  .address-section-title { font-size: 11px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
  .address-section-title svg { width: 13px; height: 13px; color: #9daac8; }
  .address-list { display: flex; flex-direction: column; gap: 6px; }
  .address-item {
    background: var(--main-bg); border: 1px solid var(--border); border-radius: 8px;
    padding: 8px 12px; font-size: 12.5px; color: var(--text-primary); line-height: 1.5;
    display: flex; align-items: flex-start; gap: 8px;
  }
  .address-item.is-default { border-color: #a3cfbb; background: #f4faf7; }
  .address-label {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;
    padding: 2px 8px; border-radius: 4px; background: var(--gray-light); color: var(--text-secondary);
    white-space: nowrap; flex-shrink: 0; margin-top: 1px;
  }
  .address-label.default { background: var(--green-light); color: var(--green); }
  .address-text { flex: 1; word-break: break-word; }
  .no-address { font-size: 12px; color: var(--text-secondary); font-style: italic; padding: 6px 0; }

  .modal-address-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
  .modal-address-item {
    background: var(--main-bg); border: 1px solid var(--border); border-radius: var(--radius-sm);
    padding: 12px 14px; font-size: 13px; color: var(--text-primary); line-height: 1.55;
    display: flex; align-items: flex-start; gap: 10px;
  }
  .modal-address-item.is-default { border-color: #a3cfbb; background: #f4faf7; }
  .modal-address-label {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;
    padding: 3px 10px; border-radius: 5px; background: var(--gray-light); color: var(--text-secondary);
    white-space: nowrap; flex-shrink: 0; margin-top: 1px;
  }
  .modal-address-label.default { background: var(--green-light); color: var(--green); }
  .modal-address-text { flex: 1; word-break: break-word; }

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
  .modal { background: var(--main-bg); border-radius: 18px; width: 540px; max-width: 95vw;
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
  let customers = <?php echo json_encode($customers); ?>;

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

  document.getElementById('statsRow').innerHTML = `
    <div class="stat-card">
      <div class="stat-label">Total Pelanggan</div>
      <div class="stat-value blue">${total}</div>
    </div>
  `;
}

function renderGrid() {
  const grid = document.getElementById('customersGrid');

  if (filteredCustomers.length === 0) {
    grid.innerHTML = `<div class="empty-state">
      <p>Tidak ada pelanggan ditemukan</p>
    </div>`;
    return;
  }

  grid.innerHTML = filteredCustomers.map(c => {
    const addresses = c.addresses || [];
    let addressHtml = '';
    if (addresses.length > 0) {
      addressHtml = `<div class="address-section">
        <div class="address-section-title">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3" stroke-width="2"/></svg>
          Alamat (${addresses.length})
        </div>
        <div class="address-list">
          ${addresses.map(a => `
            <div class="address-item ${a.is_default == 1 ? 'is-default' : ''}">
              <span class="address-label ${a.is_default == 1 ? 'default' : ''}">${a.label}${a.is_default == 1 ? ' ✓' : ''}</span>
              <span class="address-text">${a.address}</span>
            </div>
          `).join('')}
        </div>
      </div>`;
    } else {
      addressHtml = `<div class="address-section">
        <div class="address-section-title">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3" stroke-width="2"/></svg>
          Alamat
        </div>
        <div class="no-address">Belum ada alamat</div>
      </div>`;
    }

    return `
    <div class="customer-card">
      <div class="card-header">
        <div class="customer-info">
          <div class="avatar">👤</div>
          <div>
            <div class="customer-name">${c.name}</div>
            <div class="customer-id">ID: ${c.id}</div>
          </div>
        </div>
        <button class="view-btn" onclick="openModal('${c.id}')">
          👁
        </button>
      </div>

      <div class="card-contacts">
        <div class="contact-row">📧 ${c.email}</div>
        <div class="contact-row">Role: ${c.role}</div>
      </div>

      <div class="card-divider"></div>
      ${addressHtml}

      <div class="card-footer" style="margin-top: 14px;">
        <span class="joined-date">
          Bergabung: ${c.created_at}
        </span>
      </div>
    </div>
  `;
  }).join('');
}

function filterCustomers() {
  const q = document.getElementById('searchInput').value.toLowerCase();

  filteredCustomers = customers.filter(c => {
    return !q || 
      c.name.toLowerCase().includes(q) || 
      c.email.toLowerCase().includes(q) || 
      c.id.toString().includes(q);
  });

  renderGrid();
}

function openModal(customerId) {
  const c = customers.find(x => x.id == customerId);
  const addresses = c.addresses || [];

  let addressListHtml = '';
  if (addresses.length > 0) {
    addressListHtml = `<div class="modal-address-list">
      ${addresses.map(a => `
        <div class="modal-address-item ${a.is_default == 1 ? 'is-default' : ''}">
          <span class="modal-address-label ${a.is_default == 1 ? 'default' : ''}">${a.label}${a.is_default == 1 ? ' ✓' : ''}</span>
          <span class="modal-address-text">${a.address}</span>
        </div>
      `).join('')}
    </div>`;
  } else {
    addressListHtml = `<div class="no-address" style="margin-bottom: 20px;">Belum ada alamat tersimpan</div>`;
  }

  document.getElementById('modalBody').innerHTML = `
    <div class="modal-profile">
      <div class="modal-avatar">👤</div>
      <div>
        <div class="modal-name">${c.name}</div>
        <div class="modal-cid">ID: ${c.id}</div>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-item">
        <label>Email</label>
        <div class="val">${c.email}</div>
      </div>

      <div class="detail-item">
        <label>Role</label>
        <div class="val">${c.role}</div>
      </div>

      <div class="detail-item">
        <label>Bergabung</label>
        <div class="val">${c.created_at}</div>
      </div>
    </div>

    <div class="section-title">Alamat (${addresses.length})</div>
    ${addressListHtml}

    <button onclick="deleteUser(${c.id})" style="margin-top:15px;background:red;color:#fff;padding:10px;border:none;border-radius:6px;cursor:pointer">
      Hapus User
    </button>
  `;

  document.getElementById('modalOverlay').classList.add('open');
}

function deleteUser(id) {
  if (!confirm("Yakin hapus user ini?")) return;

  fetch('delete_user.php?id=' + id)
    .then(res => res.text())
    .then(() => {
      location.reload();
    });
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