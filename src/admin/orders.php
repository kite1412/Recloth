<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

$conn = new mysqli("localhost", "root", "", "recloth");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    
    if ($order_id > 0 && !empty($status)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
    }
    exit;
}

// Fetch orders
$orders_query = $conn->query("
    SELECT o.*, u.name as customer, u.email as email, DATE_FORMAT(o.created_at, '%d %b %Y') as formatted_date
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");

$orders = [];
while($row = $orders_query->fetch_assoc()) {
    $items_query = $conn->query("
        SELECT p.name, oi.quantity as qty, oi.price 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = " . $row['id']
    );
    
    $items = [];
    while($item = $items_query->fetch_assoc()) {
        $items[] = [
            'name' => $item['name'],
            'qty' => (int)$item['qty'],
            'price' => (float)$item['price']
        ];
    }
    
    $raw_status = strtolower($row['status'] ?: 'pending');
    $mapped_status = 'menunggu';
    if ($raw_status === 'selesai' || $raw_status === 'completed') $mapped_status = 'selesai';
    else if ($raw_status === 'diproses' || $raw_status === 'processing') $mapped_status = 'diproses';
    else if ($raw_status === 'dikirim' || $raw_status === 'shipped') $mapped_status = 'dikirim';
    else if ($raw_status === 'dibatalkan' || $raw_status === 'cancelled') $mapped_status = 'dibatalkan';

    $orders[] = [
        'id' => '#ORD-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
        'real_id' => $row['id'],
        'customer' => $row['customer'],
        'email' => $row['email'],
        'date' => $row['formatted_date'],
        'items' => $items,
        'status' => $mapped_status,
        'address' => $row['address'] ?: '-',
        'payment' => $row['payment_method'] ?: '-'
    ];
}
$orders_json = json_encode($orders);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel - Pesanan</title>
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
      --primary: #1b4332;
      --primary-hover: #081c15;
      --accent: #d4af37;
      --accent-light: rgba(212, 175, 55, 0.15);
      --sidebar-bg: linear-gradient(180deg, #0a120e 0%, #050806 100%);
      --sidebar-text: #a3b8ad;
      --sidebar-active-bg: linear-gradient(90deg, rgba(212, 175, 55, 0.15) 0%, transparent 100%);
      --main-bg: #fdfcfaf0;
      --card-bg: rgba(255, 255, 255, 0.85);
      --border: rgba(212, 175, 55, 0.2);
      --text-primary: #1a1f1c;
      --text-secondary: #606d66;
      --black: #0a120e;
      --green: #10b981;
      --green-light: #ecfdf5;
      --yellow: #f59e0b;
      --yellow-light: #fffbeb;
      --red: #ef4444;
      --red-light: #fef2f2;
      --gray: #9ca3af;
      --shadow: 0 8px 24px rgba(27,67,50,0.06);
      --shadow-lg: 0 16px 40px rgba(27,67,50,0.12);
      --radius: 20px;
      --radius-sm: 12px;
      --font: 'Montserrat', sans-serif;
      --font-title: 'Archivo Black', sans-serif;
      --mono: 'JetBrains Mono', monospace;
    }

  body { 
      font-family: var(--font); background: var(--main-bg); color: var(--text-primary); 
      display: flex; min-height: 100vh; font-size: 14px; position: relative; overflow-x: hidden;
  }
  body::before {
      content: ''; position: fixed; top: -20%; left: 10%; width: 50vw; height: 50vw;
      background: radial-gradient(circle, rgba(212, 175, 55, 0.05) 0%, transparent 60%);
      border-radius: 50%; animation: floatBg 15s infinite alternate ease-in-out; z-index: -1;
  }
  body::after {
      content: ''; position: fixed; bottom: -20%; right: -10%; width: 60vw; height: 60vw;
      background: radial-gradient(circle, rgba(27, 67, 50, 0.06) 0%, transparent 60%);
      border-radius: 50%; animation: floatBg 20s infinite alternate-reverse ease-in-out; z-index: -1;
  }
  @keyframes floatBg {
      0% { transform: translate(0, 0) scale(1); }
      100% { transform: translate(30px, 40px) scale(1.1); }
  }
  ::-webkit-scrollbar { width: 8px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.3); border-radius: 4px; }
  ::-webkit-scrollbar-thumb:hover { background: rgba(212, 175, 55, 0.6); }

  .sidebar {
    width: 260px; min-height: 100vh; background: var(--sidebar-bg);
    display: flex; flex-direction: column; padding: 32px 0;
    position: fixed; top: 0; left: 0; bottom: 0; z-index: 10;
    box-shadow: 4px 0 30px rgba(0,0,0,0.3); border-right: 1px solid rgba(255,255,255,0.03);
  }
  .sidebar::before { content: ''; position: absolute; inset: 0; background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0IiBoZWlnaHQ9IjQiPjxyZWN0IHdpZHRoPSI0IiBoZWlnaHQ9IjQiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wMiIvPjwvc3ZnPg=='); opacity: 0.6; pointer-events: none; }
  .sidebar-brand { padding: 0 28px 40px; position: relative; }
  .sidebar-brand .brand-title { font-family: 'Symphony', sans-serif; font-size: 38px; font-weight: normal; color: var(--accent); letter-spacing: 1.5px; text-shadow: 0 0 20px rgba(212, 175, 55, 0.4); }
  .sidebar-brand .brand-sub { display: block; font-size: 11px; font-weight: 700; color: #8fa399; text-transform: uppercase; letter-spacing: 3px; margin-top: 6px; }
  .sidebar-nav { flex: 1; padding: 0 16px; position: relative; }
  .nav-item {
    display: flex; align-items: center; gap: 14px; padding: 14px 18px;
    color: var(--sidebar-text); cursor: pointer; font-size: 13.5px; font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 12px; margin: 4px 0;
    text-decoration: none; position: relative; overflow: hidden;
  }
  .nav-item::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 0%; background: var(--sidebar-active-bg); transition: width 0.3s ease; z-index: 0; }
  .nav-item:hover::before { width: 100%; }
  .nav-item:hover { color: var(--accent); transform: translateX(4px); }
  .nav-item.active { color: var(--accent); box-shadow: inset 0 0 0 1px rgba(212, 175, 55, 0.2); }
  .nav-item.active::before { width: 100%; border-left: 3px solid var(--accent); }
  .nav-item svg, .nav-item span { position: relative; z-index: 1; }
  .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; transition: transform 0.3s; }
  .nav-item:hover svg { transform: scale(1.1); }
  .sidebar-bottom { padding: 20px 24px 0; border-top: 1px solid rgba(255,255,255,0.05); margin-top: 16px; position: relative; }
  .nav-logout { display: flex; align-items: center; gap: 12px; color: var(--sidebar-text); cursor: pointer; font-size: 13px; font-weight: 600; padding: 12px 6px; transition: all 0.3s; text-decoration: none; }
  .nav-logout:hover { color: #fca5a5; transform: translateX(4px); }

  .main { margin-left: 260px; flex: 1; padding: 48px 52px; min-height: 100vh; position: relative; z-index: 1; }

  .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px; animation: slideDown 0.6s ease-out; }
  @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: none; } }
  .page-title { 
      font-size: 34px; font-weight: 800; letter-spacing: -1px; 
      background: linear-gradient(135deg, var(--primary) 0%, #3a7c5c 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      text-shadow: 0 4px 20px rgba(27,67,50,0.15);
  }
  .btn-export {
    display: flex; align-items: center; gap: 10px; background: linear-gradient(135deg, var(--primary), #10b981, var(--primary));
    background-size: 200% auto; animation: gradientShift 3s ease infinite; color: #fff;
    border: 1px solid rgba(255,255,255,0.2); border-radius: var(--radius-sm); padding: 14px 26px;
    font-size: 13.5px; font-weight: 700; cursor: pointer; font-family: var(--font);
    box-shadow: 0 4px 15px rgba(16,185,129,0.4); transition: all 0.3s;
  }
  @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
  .btn-export:hover { box-shadow: 0 8px 25px rgba(16,185,129,0.6); transform: translateY(-3px); }
  .btn-export svg { width: 15px; height: 15px; }

  .filters-bar {
    background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 16px 20px; display: flex; gap: 12px; margin-bottom: 20px; box-shadow: var(--shadow);
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
    transition: border 0.16s; min-width: 130px;
  }
  .filter-select:focus { border-color: var(--blue); }

  .table-card { 
      background: var(--card-bg); backdrop-filter: blur(16px); 
      border-radius: var(--radius); border: 1px solid rgba(255,255,255,0.6); 
      box-shadow: var(--shadow); overflow: hidden; 
  }
  table { width: 100%; border-collapse: collapse; }
  thead { background: rgba(212, 175, 55, 0.03); }
  th { 
      padding: 16px 20px; text-align: left; font-size: 11.5px; 
      font-weight: 800; color: var(--accent); text-transform: uppercase; 
      letter-spacing: 1px; border-bottom: 2px solid var(--border); 
  }
  td { padding: 18px 20px; border-bottom: 1px solid rgba(212, 175, 55, 0.1); vertical-align: middle; transition: all 0.2s; color: var(--text-primary); }
  tr:last-child td { border-bottom: none; }
  tbody tr { transition: transform 0.3s, box-shadow 0.3s, background 0.3s; }
  tbody tr:hover { 
      background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(212, 175, 55, 0.05) 50%, rgba(255,255,255,0) 100%); 
      transform: scale(1.01); box-shadow: 0 4px 15px rgba(0,0,0,0.02); position: relative; z-index: 2;
  }

  .order-id { font-family: var(--mono); font-size: 13.5px; font-weight: 700; color: var(--primary); }
  .customer-name { font-weight: 700; font-size: 14px; }
  .customer-email { font-size: 12.5px; color: var(--text-secondary); margin-top: 2px; font-weight: 500; }
  .amount { font-weight: 800; font-size: 14.5px; font-family: var(--mono); color: #111827; }

  .badge {
    display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px;
    border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer;
    transition: all 0.15s; border: none; font-family: var(--font);
  }
  .badge:hover { opacity: 0.85; transform: scale(0.97); }
  .badge-dot { width: 6px; height: 6px; border-radius: 50%; }
  .badge.selesai { background: var(--green-light); color: var(--green); }
  .badge.selesai .badge-dot { background: var(--green); }
  .badge.diproses { background: var(--blue-light); color: var(--blue); }
  .badge.diproses .badge-dot { background: var(--blue); }
  .badge.menunggu { background: var(--yellow-light); color: var(--yellow); }
  .badge.menunggu .badge-dot { background: var(--yellow); }
  .badge.dibatalkan { background: var(--red-light); color: var(--red); }
  .badge.dibatalkan .badge-dot { background: var(--red); }
  .badge.dikirim { background: var(--purple-light); color: var(--purple); }
  .badge.dikirim .badge-dot { background: var(--purple); }

  .action-btn {
    background: none; border: none; cursor: pointer; padding: 6px; border-radius: 6px;
    color: var(--blue); transition: all 0.15s;
  }
  .action-btn:hover { background: var(--blue-light); color: #1d4ed8; }
  .action-btn svg { width: 17px; height: 17px; display: block; }

  .empty-state { text-align: center; padding: 48px 20px; color: var(--text-secondary); }
  .empty-state svg { width: 40px; height: 40px; margin-bottom: 12px; opacity: 0.3; }

  .modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(10,14,28,0.45);
    z-index: 100; align-items: center; justify-content: center; backdrop-filter: blur(3px);
  }
  .modal-overlay.open { display: flex; }
  .modal { background: var(--main-bg); border-radius: 18px; width: 520px; max-width: 95vw;
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
    background: var(--gray-light); border: none; border-radius: 8px; width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center; cursor: pointer;
    color: var(--text-secondary); transition: all 0.15s;
  }
  .modal-close:hover { background: var(--red-light); color: var(--red); }
  .modal-body { padding: 24px; }

  .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px; }
  .detail-item label { display: block; font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; }
  .detail-item .val { font-size: 14px; font-weight: 600; color: var(--text-primary); }
  .detail-item .val.mono { font-family: var(--mono); color: var(--blue); font-size: 13px; }

  .divider { height: 1px; background: var(--border); margin: 18px 0; }

  .items-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .items-table th { padding: 8px 10px; text-align: left; font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
  .items-table td { padding: 10px 10px; border-bottom: 1px solid #f0f3f8; }
  .items-table tr:last-child td { border-bottom: none; }
  .item-name { font-weight: 500; }
  .item-price { font-family: var(--mono); font-weight: 600; }

  .status-section { margin-top: 20px; }
  .status-section label { display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; }
  .status-options { display: flex; flex-wrap: wrap; gap: 8px; }
  .status-opt {
    padding: 6px 14px; border-radius: 20px; border: 2px solid transparent;
    font-size: 12.5px; font-weight: 600; cursor: pointer; font-family: var(--font); transition: all 0.15s;
  }
  .status-opt.selesai { background: var(--green-light); color: var(--green); }
  .status-opt.diproses { background: var(--blue-light); color: var(--blue); }
  .status-opt.menunggu { background: var(--yellow-light); color: var(--yellow); }
  .status-opt.dibatalkan { background: var(--red-light); color: var(--red); }
  .status-opt.dikirim { background: var(--purple-light); color: var(--purple); }
  .status-opt.selected.selesai { border-color: var(--green); }
  .status-opt.selected.diproses { border-color: var(--blue); }
  .status-opt.selected.menunggu { border-color: var(--yellow); }
  .status-opt.selected.dibatalkan { border-color: var(--red); }
  .status-opt.selected.dikirim { border-color: var(--purple); }

  .modal-footer { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; }
  .btn-cancel { background: var(--gray-light); border: none; border-radius: var(--radius-sm); padding: 9px 18px; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: var(--font); color: var(--text-secondary); transition: all 0.15s; }
  .btn-cancel:hover { background: var(--border); }
  .btn-save { background: var(--blue); border: none; border-radius: var(--radius-sm); padding: 9px 18px; font-size: 13.5px; font-weight: 600; cursor: pointer; font-family: var(--font); color: #fff; transition: all 0.15s; }
  .btn-save:hover { background: #1d4ed8; }

  .toast {
    position: fixed; bottom: 28px; right: 28px; background: #141928; color: #fff;
    padding: 12px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 500;
    box-shadow: var(--shadow-lg); z-index: 200; animation: toastIn 0.25s cubic-bezier(.34,1.56,.64,1);
    display: flex; align-items: center; gap: 10px;
  }
  .toast.hidden { display: none; }
  @keyframes toastIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }

  .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
  .stat-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px 20px; box-shadow: var(--shadow); }
  .stat-label { font-size: 11.5px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
  .stat-value { font-size: 22px; font-weight: 700; margin-top: 4px; letter-spacing: -0.5px; }
  .stat-value.blue { color: var(--blue); }
  .stat-value.green { color: var(--green); }
  .stat-value.yellow { color: var(--yellow); }
  .stat-value.purple { color: var(--purple); }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-title">Recloth</div>
    <div class="brand-sub">Admin Panel</div>
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
    <a href="categories.php" class="nav-item">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M3 10h18M3 14h18M9 3v18M15 3v18" stroke-linecap="round"/></svg>
      Categories
    </a>
    <a href="orders.php" class="nav-item active">
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

<main class="main">
  <div class="page-header">
    <h1 class="page-title">Pesanan</h1>
    <button class="btn-export" onclick="exportToCSV()">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" stroke-linecap="round" d="M12 3v13m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
      Ekspor Pesanan
    </button>
  </div>

  <div class="stats-row" id="statsRow"></div>

  <div class="filters-bar">
    <div class="search-wrap">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-width="2"/><path stroke-width="2" stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
      <input type="text" class="search-input" id="searchInput" placeholder="Cari pesanan..." oninput="filterOrders()">
    </div>
    <select class="filter-select" id="statusFilter" onchange="filterOrders()">
      <option value="">Semua Status</option>
      <option value="selesai">Selesai</option>
      <option value="diproses">Diproses</option>
      <option value="menunggu">Menunggu</option>
      <option value="dikirim">Dikirim</option>
      <option value="dibatalkan">Dibatalkan</option>
    </select>
  </div>

  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>ID Pesanan</th>
          <th>Pelanggan</th>
          <th>Tanggal</th>
          <th>Barang</th>
          <th>Total</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody id="ordersTableBody"></tbody>
    </table>
    <div class="empty-state" id="emptyState" style="display:none;">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      <p>Tidak ada pesanan ditemukan</p>
    </div>
  </div>
</main>

<div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
  <div class="modal" id="modalBox">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Detail Pesanan</div>
      <button class="modal-close" onclick="closeModal()">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15"><path stroke-width="2.5" stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal()">Tutup</button>
      <button class="btn-save" onclick="saveStatus()">Simpan Perubahan</button>
    </div>
  </div>
</div>

<div class="toast hidden" id="toast">
  <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-width="2.5" stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
  <span id="toastMsg"></span>
</div>

<script>
let orders = <?php echo $orders_json; ?>;

const statusLabel = {
  selesai: 'Selesai',
  diproses: 'Diproses',
  menunggu: 'Menunggu',
  dikirim: 'Dikirim',
  dibatalkan: 'Dibatalkan'
};

let currentOrderId = null;
let selectedStatus = null;
let filteredOrders = [...orders];

function getTotal(items) { return items.reduce((sum, i) => sum + i.price * i.qty, 0); }

function formatRupiah(val) {
  return 'Rp ' + val.toLocaleString('id-ID');
}

function renderStats() {
  const total = orders.length;
  const revenue = orders.filter(o => o.status !== 'dibatalkan').reduce((s, o) => s + getTotal(o.items), 0);
  const selesai = orders.filter(o => o.status === 'selesai').length;
  const menunggu = orders.filter(o => o.status === 'menunggu').length;
  document.getElementById('statsRow').innerHTML = `
    <div class="stat-card"><div class="stat-label">Total Pesanan</div><div class="stat-value blue">${total}</div></div>
    <div class="stat-card"><div class="stat-label">Pendapatan</div><div class="stat-value green" style="font-size:16px">${formatRupiah(revenue)}</div></div>
    <div class="stat-card"><div class="stat-label">Selesai</div><div class="stat-value green">${selesai}</div></div>
    <div class="stat-card"><div class="stat-label">Menunggu</div><div class="stat-value yellow">${menunggu}</div></div>
  `;
}

function renderTable() {
  const tbody = document.getElementById('ordersTableBody');
  const empty = document.getElementById('emptyState');
  if (filteredOrders.length === 0) { tbody.innerHTML = ''; empty.style.display = 'block'; return; }
  empty.style.display = 'none';
  tbody.innerHTML = filteredOrders.map(o => {
    const total = getTotal(o.items);
    const itemCount = o.items.reduce((s, i) => s + i.qty, 0);
    return `<tr>
      <td><span class="order-id">${o.id}</span></td>
      <td><div class="customer-name">${o.customer}</div><div class="customer-email">${o.email}</div></td>
      <td>${o.date}</td>
      <td>${itemCount} item</td>
      <td><span class="amount">${formatRupiah(total)}</span></td>
      <td><button class="badge ${o.status}" onclick="openModal('${o.id}')"><span class="badge-dot"></span>${statusLabel[o.status]}</button></td>
      <td>
        <button class="action-btn" onclick="openModal('${o.id}')" title="Lihat Detail">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        </button>
      </td>
    </tr>`;
  }).join('');
}

function filterOrders() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const st = document.getElementById('statusFilter').value;
  filteredOrders = orders.filter(o => {
    const matchQ = !q || o.id.toLowerCase().includes(q) || o.customer.toLowerCase().includes(q) || o.email.toLowerCase().includes(q);
    const matchSt = !st || o.status === st;
    return matchQ && matchSt;
  });
  renderTable();
}

function openModal(orderId) {
  currentOrderId = orderId;
  const o = orders.find(x => x.id === orderId);
  selectedStatus = o.status;
  const total = getTotal(o.items);
  document.getElementById('modalTitle').textContent = `Detail Pesanan ${o.id}`;
  document.getElementById('modalBody').innerHTML = `
    <div class="detail-grid">
      <div class="detail-item"><label>ID Pesanan</label><div class="val mono">${o.id}</div></div>
      <div class="detail-item"><label>Tanggal</label><div class="val">${o.date}</div></div>
      <div class="detail-item"><label>Pelanggan</label><div class="val">${o.customer}</div></div>
      <div class="detail-item"><label>Email</label><div class="val" style="font-size:13px">${o.email}</div></div>
      <div class="detail-item"><label>Alamat</label><div class="val" style="font-size:12.5px;font-weight:400">${o.address}</div></div>
      <div class="detail-item"><label>Pembayaran</label><div class="val">${o.payment}</div></div>
    </div>
    <div class="divider"></div>
    <div style="font-size:12px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:10px">Item Produk</div>
    <table class="items-table">
      <thead><tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
      <tbody>${o.items.map(it => `<tr><td class="item-name">${it.name}</td><td>${it.qty}</td><td class="item-price">${formatRupiah(it.price)}</td><td class="item-price">${formatRupiah(it.price * it.qty)}</td></tr>`).join('')}</tbody>
    </table>
    <div style="display:flex;justify-content:flex-end;margin-top:10px;padding-top:10px;border-top:1px solid var(--border)">
      <span style="font-size:13px;font-weight:700;color:var(--text-primary)">Total: <span style="font-family:var(--mono);color:var(--blue)">${formatRupiah(total)}</span></span>
    </div>
    <div class="divider"></div>
    <div class="status-section">
      <label>Ubah Status Pesanan</label>
      <div class="status-options" id="statusOptions">
        ${Object.entries(statusLabel).map(([key, label]) => `
          <button class="status-opt ${key} ${key === o.status ? 'selected' : ''}" onclick="selectStatus('${key}')">${label}</button>
        `).join('')}
      </div>
    </div>
  `;
  document.getElementById('modalOverlay').classList.add('open');
}

function selectStatus(status) {
  selectedStatus = status;
  document.querySelectorAll('.status-opt').forEach(b => {
    b.classList.remove('selected');
    const btnStatus = [...b.classList].find(c => statusLabel[c]);
    if (btnStatus === status) b.classList.add('selected');
  });
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  currentOrderId = null; selectedStatus = null;
}

function handleOverlayClick(e) {
  if (e.target === document.getElementById('modalOverlay')) closeModal();
}

function saveStatus() {
  if (!currentOrderId || !selectedStatus) return;
  const o = orders.find(x => x.id === currentOrderId);
  if (o.status === selectedStatus) { closeModal(); return; }
  
  const savedId = o.id;
  const realId = o.real_id;
  
  const formData = new FormData();
  formData.append('action', 'update_status');
  formData.append('order_id', realId);
  formData.append('status', selectedStatus);

  fetch('orders.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      o.status = selectedStatus;
      filterOrders(); renderStats(); closeModal();
      showToast(`Status ${savedId} diubah ke "${statusLabel[selectedStatus]}"`);
    } else {
      showToast('Gagal mengubah status pesanan');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Terjadi kesalahan jaringan');
  });
}

function showToast(msg) {
  const t = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 3000);
}

function exportToCSV() {
  const rows = [['ID Pesanan','Pelanggan','Email','Tanggal','Jumlah Barang','Total','Status','Alamat','Pembayaran']];
  orders.forEach(o => {
    rows.push([o.id, o.customer, o.email, o.date, o.items.reduce((s,i)=>s+i.qty,0), getTotal(o.items), statusLabel[o.status], o.address, o.payment]);
  });
  const csv = rows.map(r => r.map(v => `"${v}"`).join(',')).join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href = url; a.download = 'ekspor_pesanan.csv'; a.click();
  URL.revokeObjectURL(url);
  showToast('Pesanan berhasil diekspor ke CSV!');
}

renderStats();
renderTable();
</script>
</body>
</html>