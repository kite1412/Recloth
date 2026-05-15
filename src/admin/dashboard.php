<?php
require_once __DIR__ . '/../config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel - Dashboard</title>
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
      --accent: #d4af37; /* Luxury Gold */
      --accent-light: rgba(212, 175, 55, 0.15);
      --sidebar-bg: linear-gradient(180deg, #0a120e 0%, #050806 100%);
      --sidebar-text: #a3b8ad;
      --sidebar-hover: rgba(212, 175, 55, 0.1);
      --sidebar-active-bg: linear-gradient(90deg, rgba(212, 175, 55, 0.15) 0%, transparent 100%);
      --sidebar-active-text: #d4af37;
      --main-bg: #fdfcfaf0; /* Slightly warm off-white */
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
      --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
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
        display: flex; min-height: 100vh; font-size: 14px; 
        position: relative; overflow-x: hidden;
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

    /* ── Sidebar ── */
    .sidebar {
      width: 260px; min-height: 100vh; background: var(--sidebar-bg);
      display: flex; flex-direction: column; padding: 32px 0;
      position: fixed; top: 0; left: 0; bottom: 0; z-index: 10;
      box-shadow: 4px 0 30px rgba(0,0,0,0.3);
      border-right: 1px solid rgba(255,255,255,0.03);
    }
    .sidebar::before {
        content: ''; position: absolute; inset: 0;
        background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0IiBoZWlnaHQ9IjQiPjxyZWN0IHdpZHRoPSI0IiBoZWlnaHQ9IjQiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wMiIvPjwvc3ZnPg==');
        opacity: 0.6; pointer-events: none;
    }
    .sidebar-brand { padding: 0 28px 40px; position: relative; }
    .sidebar-brand .brand-title {
      font-family: 'Symphony', sans-serif; font-size: 38px; font-weight: normal;
      color: var(--accent); letter-spacing: 1.5px;
      text-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
    }
    .sidebar-brand .brand-sub {
      display: block; font-size: 11px; font-weight: 700; color: #8fa399;
      text-transform: uppercase; letter-spacing: 3px; margin-top: 6px;
    }
    .sidebar-nav { flex: 1; padding: 0 16px; position: relative; }
    .nav-item {
      display: flex; align-items: center; gap: 14px; padding: 14px 18px;
      color: var(--sidebar-text); cursor: pointer; font-size: 13.5px; font-weight: 600;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 12px; margin: 4px 0;
      text-decoration: none; position: relative; overflow: hidden;
    }
    .nav-item::before {
        content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 0%;
        background: var(--sidebar-active-bg); transition: width 0.3s ease; z-index: 0;
    }
    .nav-item:hover::before { width: 100%; }
    .nav-item:hover { color: var(--accent); transform: translateX(4px); }
    .nav-item.active {
      color: var(--accent);
      box-shadow: inset 0 0 0 1px rgba(212, 175, 55, 0.2);
    }
    .nav-item.active::before { width: 100%; border-left: 3px solid var(--accent); }
    .nav-item svg, .nav-item span { position: relative; z-index: 1; }
    .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; transition: transform 0.3s; }
    .nav-item:hover svg { transform: scale(1.1); }
    .sidebar-bottom { padding: 20px 24px 0; border-top: 1px solid rgba(255,255,255,0.05); margin-top: 16px; position: relative; }
    .nav-logout { display: flex; align-items: center; gap: 12px; color: var(--sidebar-text); cursor: pointer; font-size: 13px; font-weight: 600; padding: 12px 6px; transition: all 0.3s; text-decoration: none; }
    .nav-logout:hover { color: #fca5a5; transform: translateX(4px); }

    /* ── Main ── */
    .main { margin-left: 260px; flex: 1; padding: 48px 52px; min-height: 100vh; position: relative; z-index: 1; }

    .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px; animation: slideDown 0.6s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: none; } }
    
    .page-title { 
        font-size: 34px; font-weight: 800; letter-spacing: -1px; 
        background: linear-gradient(135deg, var(--primary) 0%, #3a7c5c 100%);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        text-shadow: 0 4px 20px rgba(27,67,50,0.15);
    }

    /* ── Action Cards ── */
    .action-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 40px; }
    .action-card {
      border-radius: var(--radius); padding: 32px; color: var(--text-primary);
      cursor: pointer; position: relative; overflow: hidden;
      background: var(--card-bg); backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.6);
      box-shadow: var(--shadow);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .action-card::before {
        content: ''; position: absolute; inset: 0; border-radius: var(--radius);
        padding: 2px; background: linear-gradient(135deg, var(--accent), var(--primary));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor; mask-composite: exclude;
        opacity: 0; transition: opacity 0.4s;
    }
    .action-card:hover { transform: translateY(-8px) scale(1.02); box-shadow: var(--shadow-lg); }
    .action-card:hover::before { opacity: 1; }
    
    .action-card .card-icon {
      width: 60px; height: 60px; background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(27, 67, 50, 0.1));
      border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;
      border: 1px solid rgba(212, 175, 55, 0.2);
      transition: transform 0.4s;
    }
    .action-card:hover .card-icon { transform: rotateY(15deg) rotateX(15deg); }
    .action-card .card-icon svg { width: 28px; height: 28px; stroke: var(--accent); }
    .action-card h3 { font-size: 20px; font-weight: 800; margin-bottom: 8px; color: var(--primary); }
    .action-card p { font-size: 14px; color: var(--text-secondary); font-weight: 500; line-height: 1.5; }
    .action-card .arrow { position: absolute; top: 32px; right: 32px; font-size: 24px; color: var(--accent); transition: transform 0.4s, opacity 0.4s; opacity: 0.5; }
    .action-card:hover .arrow { transform: translateX(8px); opacity: 1; }

    /* ── Table ── */
    .table-card { 
        background: var(--card-bg); backdrop-filter: blur(16px); 
        border-radius: var(--radius); border: 1px solid rgba(255,255,255,0.6); 
        box-shadow: var(--shadow); overflow: hidden; 
    }
    .table-header { padding: 24px 32px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.4); }
    .table-header h3 { font-size: 18px; font-weight: 800; color: var(--primary); }

    table { width: 100%; border-collapse: collapse; }
    th { 
        text-align: left; padding: 16px 32px; font-size: 11.5px; color: var(--accent); 
        font-weight: 800; border-bottom: 2px solid var(--border); 
        text-transform: uppercase; letter-spacing: 1px; background: rgba(212, 175, 55, 0.03); 
    }
    td { padding: 18px 32px; font-size: 14px; color: var(--text-primary); border-bottom: 1px solid rgba(212, 175, 55, 0.1); transition: all 0.2s; }
    tr:last-child td { border-bottom: none; }
    tbody tr { transition: transform 0.3s, box-shadow 0.3s, background 0.3s; }
    tbody tr:hover { 
        background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(212, 175, 55, 0.05) 50%, rgba(255,255,255,0) 100%); 
        transform: scale(1.01); box-shadow: 0 4px 15px rgba(0,0,0,0.02); position: relative; z-index: 2;
    }
    td.order-id { font-weight: 700; font-family: var(--mono); font-size: 13.5px; color: var(--primary); }
    td.date { color: var(--text-secondary); font-weight: 500; }
    td.amount { font-weight: 800; color: #111827; }

    /* ── Badges ── */
    .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; box-shadow: inset 0 0 0 1px currentColor; }
    .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; box-shadow: 0 0 8px currentColor; }
    .badge.completed { background: rgba(16, 185, 129, 0.1); color: var(--green); }
    .badge.completed::before { background: var(--green); }
    .badge.processing { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .badge.processing::before { background: #3b82f6; }
    .badge.pending { background: rgba(245, 158, 11, 0.1); color: #d97706; }
    .badge.pending::before { background: var(--yellow); }
    .badge.cancelled { background: rgba(239, 68, 68, 0.1); color: var(--red); }
    .badge.cancelled::before { background: var(--red); }

    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }
    .action-card { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
    .action-card:nth-child(2) { animation-delay: 0.15s; }
    .table-card { animation: fadeInUp 0.6s 0.3s cubic-bezier(0.16, 1, 0.3, 1) both; }
  </style>
</head>
<body>

  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-title">Recloth</div>
      <div class="brand-sub">Admin Panel</div>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="nav-item active">
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

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Dashboard</h1>
    </div>

    <div class="action-cards">
      <div class="action-card blue" onclick="window.location.href='products.php'">
        <div class="card-icon">
          <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          </svg>
        </div>
        <h3>Manage Products</h3>
        <p>Add, edit, or remove products from your store</p>
      </div>
      <div class="action-card purple" onclick="window.location.href='categories.php'">
        <span class="arrow">→</span>
        <div class="card-icon">
          <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
          </svg>
        </div>
        <h3>Manage Categories</h3>
        <p>Organize your products with categories</p>
      </div>
    </div>

    <div class="table-card">
      <div class="table-header">
        <h3>Recent Orders</h3>
      </div>
      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>User ID</th>
            <th>Nama User</th>
            <th>Total</th>
            <th>Status</th>
            <th>Tanggal</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $pdo->query("
            SELECT o.id, o.user_id, u.name, o.total_price, o.status, DATE(o.created_at) as order_date
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
            LIMIT 5
          ");
          $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
          if (count($recent_orders) > 0) {
              foreach ($recent_orders as $order) {
                  $formatted_id = '#ORD-' . str_pad($order['id'], 3, '0', STR_PAD_LEFT);
                  $formatted_user_id = 'U' . str_pad($order['user_id'], 3, '0', STR_PAD_LEFT);
                  $formatted_price = 'Rp ' . number_format($order['total_price'], 0, ',', '.');
                  $status = strtolower($order['status'] ?? 'pending');
                  
                  $badge_class = 'pending';
                  $status_text = 'Menunggu';
                  if ($status === 'selesai' || $status === 'completed') { $badge_class = 'completed'; $status_text = 'Selesai'; }
                  else if ($status === 'diproses' || $status === 'processing') { $badge_class = 'processing'; $status_text = 'Diproses'; }
                  else if ($status === 'dikirim' || $status === 'shipped') { $badge_class = 'processing'; $status_text = 'Dikirim'; }
                  else if ($status === 'dibatalkan' || $status === 'cancelled') { $badge_class = 'cancelled'; $status_text = 'Dibatalkan'; }
                  else if ($status === 'menunggu' || $status === 'pending') { $badge_class = 'pending'; $status_text = 'Menunggu'; }

                  echo "<tr>";
                  echo "<td class='order-id'>{$formatted_id}</td>";
                  echo "<td>{$formatted_user_id}</td>";
                  echo "<td>" . htmlspecialchars($order['name']) . "</td>";
                  echo "<td class='amount'>{$formatted_price}</td>";
                  echo "<td><span class='badge {$badge_class}'>{$status_text}</span></td>";
                  echo "<td class='date'>" . htmlspecialchars($order['order_date']) . "</td>";
                  echo "</tr>";
              }
          } else {
              echo "<tr><td colspan='6' style='text-align: center; padding: 20px; color: var(--text-secondary);'>Belum ada pesanan</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>