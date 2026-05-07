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
      position: fixed; top: 0; left: 0; bottom: 0; z-index: 10;
      border-right: 1px solid var(--border);
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

    .action-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
    .action-card {
      border-radius: var(--radius); padding: 24px; color: var(--text-primary);
      cursor: pointer; position: relative; overflow: hidden;
      background: var(--card-bg); border: 1px solid var(--border);
      box-shadow: var(--shadow);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .action-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08); }
    .action-card .card-icon {
      width: 48px; height: 48px; background: var(--gray-light);
      border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;
    }
    .action-card .card-icon svg { width: 24px; height: 24px; stroke: var(--black); }
    .action-card h3 { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
    .action-card p  { font-size: 13px; color: var(--text-secondary); font-weight: 500; }
    .action-card .arrow { position: absolute; top: 20px; right: 20px; font-size: 18px; color: var(--text-secondary); }

    .table-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow); }
    .table-header { padding: 20px 24px; border-bottom: 1px solid var(--border); }
    .table-header h3 { font-size: 17px; font-weight: 600; color: var(--text-primary); }

    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: 12px 24px; font-size: 13px; color: var(--text-secondary); font-weight: 500; border-bottom: 1px solid var(--border); text-transform: uppercase; letter-spacing: 0.5px; }
    td { padding: 14px 24px; font-size: 14px; color: var(--text-primary); border-bottom: 1px solid #f3f4f6; }
    tr:last-child td { border-bottom: none; }
    td.order-id { font-weight: 600; font-family: var(--mono); font-size: 13px; color: var(--black); }
    td.date { color: var(--text-secondary); }
    td.amount { font-weight: 600; }

    .badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge.completed  { background: var(--green-light); color: var(--green); }
    .badge.processing { background: var(--gray-light); color: var(--black); border: 1px solid var(--border); }
    .badge.pending    { background: var(--yellow-light); color: var(--yellow); }
    .badge.cancelled  { background: var(--red-light); color: var(--red); }
  </style>
</head>
<body>

  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-title">Admin Panel</div>
      <div class="brand-sub">E-Commerce Dashboard</div>
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