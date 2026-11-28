<?php
require 'koneksi.php';
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION['role'] ?? 'readonly';
$is_admin = ($role === 'admin');
$default_tab = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supernova OLT & MikroTik Provisioning</title>
<style>
  :root {
    --bg: #000000; --card: rgba(28,28,36,.55); --card-solid: #16161d;
    --border: rgba(255,255,255,.09); --input: rgba(255,255,255,.09);
    --text: #f5f5f7; --muted: #98989f;
    --primary: #0A84FF; --primary-hover: #268fff;
    --emerald: #30D158; --amber: #FF9F0A; --rose: #FF453A; --cyan: #64D2FF;
    --purple: #BF5AF2; --yellow: #FFD60A; --teal: #64D2FF; --pink: #FF375F;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background:
      radial-gradient(1100px 600px at 85% -10%, rgba(10,132,255,.08), transparent 55%),
      #000;
    color: var(--text);
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", "Segoe UI", Roboto, sans-serif;
    font-size: 14px; line-height: 1.5; padding-bottom: 40px;
    -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility;
  }
  header {
    background: rgba(18,18,24,.92);
    border-bottom: 1px solid rgba(255,255,255,.08);
    padding: 10px 20px; position: sticky; top: 0; z-index: 20;
  }
  .header-inner { max-width: 1240px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; }
  .brand { display: flex; align-items: center; gap: 12px; }
  .traffic { display: flex; gap: 7px; margin-right: 6px; padding-right: 14px; border-right: 1px solid rgba(255,255,255,.1); }
  .traffic i { width: 12px; height: 12px; border-radius: 50%; display: block; }
  .traffic .t-red { background: #FF5F57; } .traffic .t-yel { background: #FEBC2E; } .traffic .t-grn { background: #28C840; }
  .logo { width: 38px; height: 38px; background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.14); border-radius: 11px; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 18px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.12); }
  .badge { font-size: 10px; background: rgba(10,132,255,.16); color: #64D2FF; border: 1px solid rgba(10,132,255,.4); padding: 2px 9px; border-radius: 99px; font-family: "SF Mono", SFMono-Regular, ui-monospace, Menlo, monospace; text-transform: uppercase; letter-spacing: .3px; }
  .tabs { display: flex; gap: 2px; background: rgba(255,255,255,.06); padding: 3px; border-radius: 10px; border: 1px solid rgba(255,255,255,.08); }
  .tab-btn { background: transparent; color: var(--muted); border: none; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: .18s; }
  .tab-btn.active { background: rgba(10,132,255,.92); color: #fff; box-shadow: 0 2px 10px rgba(10,132,255,.4); }
  .tab-btn:hover:not(.active) { background: rgba(255,255,255,.1); color: #fff; }
  .container { max-width: 1240px; margin: 20px auto; padding: 0 16px; }
  .grid-main { display: grid; grid-template-columns: 5fr 7fr; gap: 20px; }
  .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .sec-label { display:flex; align-items:center; gap:8px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--muted); margin:4px 0 10px; }
  .sec-label::after { content:''; flex:1; height:1px; background:rgba(255,255,255,.1); }
  .hint { font-size:11px; color:var(--muted); line-height:1.5; }
  .chip { display:inline-flex; align-items:center; gap:6px; font-size:11px; padding:3px 10px; border-radius:99px; border:1px solid rgba(255,255,255,.14); color:#d9d9e3; background:rgba(255,255,255,.07); }
    @media (max-width: 900px) { .grid-main { grid-template-columns: 1fr; } }
    @media (max-width: 1100px) { .two-col { grid-template-columns: 1fr; } }
  .card {
    background: linear-gradient(180deg, rgba(38,38,48,.92), rgba(22,22,28,.95));
    border: 1px solid rgba(255,255,255,.09);
    border-radius: 16px; padding: 18px; margin-bottom: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.05);
  }
  .card-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; margin-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,.08); }
  .card-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #86868b; }
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .col-span-2 { grid-column: span 2; }
  label { display: block; font-size: 11px; color: var(--muted); margin-bottom: 5px; font-weight: 600; letter-spacing: .2px; }
  input, select, textarea {
    width: 100%; background: rgba(255,255,255,.09);
    border: 1px solid rgba(255,255,255,.1); color: #fff;
    padding: 9px 12px; border-radius: 10px; font-size: 13px; font-family: "SF Mono", SFMono-Regular, ui-monospace, Menlo, monospace;
    transition: border-color .15s, box-shadow .15s;
  }
  input:focus, select:focus, textarea:focus { outline: none; border-color: rgba(10,132,255,.7); box-shadow: 0 0 0 3px rgba(10,132,255,.2); }
  select option, option { background: #16161d; color: #f5f5f7; }
  select option:checked { background: rgba(10,132,255,.85); color: #fff; }
  .search-wrap { position: relative; }
  .search-wrap input { padding-right: 34px; }
  .search-clear {
    position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
    background: rgba(255,255,255,.15); border: none; color: #e5e5ea;
    width: 20px; height: 20px; border-radius: 50%; font-size: 15px; line-height: 1;
    cursor: pointer; display: none; align-items: center; justify-content: center;
    font-family: Arial, sans-serif; padding: 0;
  }
  .search-clear:hover { background: rgba(255,69,58,.8); color: #fff; }
  .blink-red { animation: blinkRed 1s infinite; display:inline-block; }
  @keyframes blinkRed { 0%, 100% { opacity: 1; } 50% { opacity: .2; } }
  .btn {
    background: rgba(10,132,255,.9); color: #fff; border: none;
    padding: 9px 16px; border-radius: 10px; font-size: 12px; font-weight: 600; cursor: pointer;
    transition: transform .12s, filter .15s, box-shadow .15s, background .15s;
    box-shadow: 0 3px 12px rgba(0,0,0,.3), inset 0 1px 0 rgba(255,255,255,.14);
    display: inline-flex; align-items: center; gap: 6px;
  }
  .btn:hover { filter: brightness(1.1); transform: translateY(-1px); }
  .btn:active { transform: translateY(0); }
  .btn-generate { background: linear-gradient(180deg, #30D158, #23a94a); color: #fff; width: 100%; padding: 12px; font-size: 14px; font-weight: 700; margin-top: 8px; border-radius: 12px; box-shadow: 0 4px 14px rgba(48,209,88,.3), inset 0 1px 0 rgba(255,255,255,.2); }
  .btn-generate:hover { background: linear-gradient(180deg, #37e063, #28bd54); }
  .btn-sm { padding: 5px 11px; font-size: 12px; border-radius: 8px; }
  
  .btn-emerald { background: rgba(48,209,88,.16); color: #63e6a5; border: 1px solid rgba(48,209,88,.35); box-shadow: none; }
  .btn-emerald:hover { background: rgba(48,209,88,.3); }
  .btn-amber { background: rgba(255,159,10,.16); color: #ffc15e; border: 1px solid rgba(255,159,10,.35); box-shadow: none; }
  .btn-amber:hover { background: rgba(255,159,10,.3); }
  .btn-rose { background: rgba(255,69,58,.16); color: #ff8d86; border: 1px solid rgba(255,69,58,.35); box-shadow: none; }
  .btn-rose:hover { background: rgba(255,69,58,.3); }
  .btn-outline-primary { background: rgba(10,132,255,.14); color: #64D2FF; border: 1px solid rgba(10,132,255,.4); box-shadow: none; }
  .btn-outline-primary:hover { background: rgba(10,132,255,.28); }
  .btn-muted { background: rgba(255,255,255,.1); color: #e5e5ea; border: 1px solid rgba(255,255,255,.12); box-shadow: none; }
  .btn-muted:hover { background: rgba(255,255,255,.18); }
  
  pre { background: rgba(0,0,0,.5); border: 1px solid var(--border); padding: 12px; border-radius: 10px; font-family: "SF Mono", ui-monospace, Menlo, monospace; font-size: 11.5px; overflow-x: auto; white-space: pre; line-height: 1.5; max-height: 300px; }
  .alert { padding: 8px 12px; border-radius: 8px; font-size: 11.5px; font-family: "SF Mono", ui-monospace, Menlo, monospace; }
  .alert-warn { background: rgba(255,159,10,.12); border: 1px solid rgba(255,159,10,.35); color: #ffc15e; }
  .alert-ok { background: rgba(48,209,88,.12); border: 1px solid rgba(48,209,88,.35); color: #63e6a5; }
  .quick-row { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,.04); border: 1px solid var(--border); padding: 7px 11px; border-radius: 10px; margin-bottom: 6px; }
  .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 16px; }
  .stat-val { font-size: 24px; font-weight: 700; margin-top: 4px; letter-spacing: -0.3px; }
  table { width: 100%; border-collapse: collapse; font-size: 12px; }
  th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,.07); }
  th { background: rgba(255,255,255,.045); color: var(--muted); text-transform: uppercase; font-size: 10.5px; letter-spacing: .5px; position: sticky; top: 0; cursor: pointer; user-select: none; }
  th:hover { color: #fff; background-color: rgba(255,255,255,.1); }
  th::after { content: ' \2195'; font-size: 10px; opacity: 0.5; margin-left: 4px; }
  th.asc::after { content: ' \2191'; opacity: 1; color: #63e6a5; }
  th.desc::after { content: ' \2193'; opacity: 1; color: #ff8d86; }
  tr:hover { background: rgba(255,255,255,.045); }
  tbody tr:nth-child(even) { background: rgba(255,255,255,.02); }
  .table-scroll { max-height: 72vh; overflow: auto; }
  input:focus-visible, select:focus-visible, button:focus-visible, a:focus-visible { outline: 2px solid rgba(10,132,255,.6); outline-offset: 2px; }
  .skel { background: linear-gradient(90deg, rgba(255,255,255,.06) 25%, rgba(255,255,255,.14) 50%, rgba(255,255,255,.06) 75%); background-size: 200% 100%; animation: shimmer 1.2s infinite; border-radius: 6px; height: 12px; }
  @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
  .quick-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
  .bar-track { background: rgba(255,255,255,.1); height: 10px; border-radius: 6px; overflow: hidden; }
  .bar-fill { height: 100%; border-radius: 6px; transition: width .4s ease; }
  .mon-head { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; padding: 8px 0; border-bottom: 1px solid var(--border); }
  .mon-item { display: flex; justify-content: space-between; gap: 8px; padding: 7px 10px; font-size: 12px; border-radius: 8px; border-bottom: 1px solid rgba(255,255,255,.05); }
  .mon-item span { color: var(--muted); font-family: "SF Mono", ui-monospace, Menlo, monospace; }
  .mon-item:hover { background: rgba(255,255,255,.06); }
  .mon-empty { color: var(--muted); font-size: 12px; padding: 16px 10px; text-align: center; }
  .mon-list-box { max-height: 360px; overflow: auto; margin-top: 4px; }
  @media (max-width: 640px) {
    .tabs { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
    .tab-btn { white-space: nowrap; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .form-grid { grid-template-columns: 1fr; }
    .col-span-2 { grid-column: span 1; }
    .grid-main { grid-template-columns: 1fr; }
    .container { padding: 0 10px; }
    .header-inner { flex-direction: column; align-items: flex-start; }
  }
  .hidden { display: none !important; }
  #toast { position: fixed; bottom: 22px; right: 22px; background: rgba(28,28,36,.95); color: #fff; padding: 11px 18px; border-radius: 12px; font-size: 12.5px; font-weight: 600; box-shadow: 0 16px 40px rgba(0,0,0,.6), inset 0 1px 0 rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.1); z-index: 99; transition: opacity 0.3s; }
  
  .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,.7); display: flex; justify-content: center; align-items: center; z-index: 1000; }
  .modal-card {
    background: linear-gradient(180deg, rgba(44,44,54,.92), rgba(20,20,26,.95));
    border: 1px solid rgba(255,255,255,.12); border-radius: 18px; padding: 24px; width: 100%; max-width: 440px;
    box-shadow: 0 30px 80px rgba(0,0,0,.7), inset 0 1px 0 rgba(255,255,255,.1);
  }
  .modal-card-lg { max-width: 780px; }
  .term {
    background: rgba(0,0,0,.55); border: 1px solid rgba(48,209,88,.3); border-radius: 12px;
    color: #63e6a5; font-family: "SF Mono", ui-monospace, Menlo, monospace; font-size: 12.5px;
    line-height: 1.65; padding: 14px 16px; margin: 0;
    max-height: 440px; overflow: auto; white-space: pre; min-height: 60px;
    box-shadow: inset 0 0 30px rgba(48,209,88,.05);
  }
  .term::-webkit-scrollbar { width: 10px; height: 10px; }
  .term::-webkit-scrollbar-track { background: rgba(0,0,0,.3); }
  .term::-webkit-scrollbar-thumb { background: rgba(48,209,88,.35); border-radius: 6px; }
  .term-loading::after { content: ''; display:inline-block; width:8px; height:14px; margin-left:4px; background:#63e6a5; animation: blink 1s steps(1) infinite; vertical-align:-2px; }
  @keyframes blink { 50% { opacity: 0; } }
  .term-title-pill {
    font-size: 11px; font-family: "SF Mono", ui-monospace, Menlo, monospace; background: rgba(100,210,255,.14);
    color: #7dd4ff; border: 1px solid rgba(100,210,255,.35); padding: 2px 10px;
    border-radius: 99px; letter-spacing: 0.5px;
  }

  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'readonly'): ?>
  <?php if (!$is_admin): ?>  .admin-only { display: none !important; }<?php endif; ?>
  <?php endif; ?>

  /* ===== APPLE POLISH: depth, frosted glass & vibrancy ===== */
  header { border-bottom: 1px solid rgba(255,255,255,.08); }
  .stats-grid .card { background: linear-gradient(180deg, rgba(40,40,50,.7), rgba(22,22,28,.82)); }
  .tab-btn.active { background: rgba(10,132,255,.92); }
  .card-header { border-bottom: 1px solid rgba(255,255,255,.08); }
  .btn { border: 1px solid rgba(255,255,255,.06); }
  .alert-ok { border-left: 4px solid #30D158; }
  .alert-warn { border-left: 4px solid #FF9F0A; }
  ::-webkit-scrollbar { width: 10px; height: 10px; }
  ::-webkit-scrollbar-track { background: rgba(0,0,0,.3); }
  ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.18); border-radius: 6px; border: 2px solid rgba(0,0,0,.4); }
  ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,.28); }

  /* ===== APP SHELL: SIDEBAR + TOPBAR ala macOS ===== */
  .app-shell { display: flex; min-height: 100vh; }
  .sidebar {
    width: 240px; flex-shrink: 0; position: sticky; top: 0; height: 100vh;
    background: rgba(18,18,24,.96);
    border-right: 1px solid rgba(255,255,255,.08);
    display: flex; flex-direction: column; padding: 16px 12px; z-index: 40;
  }
  .sidebar-head { display: flex; align-items: center; gap: 10px; padding: 4px 8px 16px; border-bottom: 1px solid rgba(255,255,255,.07); }
  .sidebar-title { font-size: 14px; font-weight: 800; letter-spacing: .3px; }
  .sidebar-sub { font-size: 10.5px; color: var(--muted); }
  .sidebar-section { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); padding: 16px 10px 6px; }
  .sidebar-nav { display: flex; flex-direction: column; gap: 2px; overflow-y: auto; flex: 1; padding-bottom: 10px; }
  .nav-item {
    display: flex; align-items: center; gap: 11px; width: 100%;
    padding: 9px 11px; border: none; border-radius: 9px; cursor: pointer;
    background: transparent; color: #d0d0da; font-size: 13px; font-weight: 600;
    text-align: left; transition: background .15s, color .15s; font-family: inherit;
  }
  .nav-item i { width: 18px; text-align: center; font-size: 14px; color: var(--muted); transition: color .15s; }
  .nav-item:hover { background: rgba(255,255,255,.07); color: #fff; }
  .nav-item.active { background: rgba(10,132,255,.9); color: #fff; box-shadow: 0 3px 12px rgba(10,132,255,.35); }
  .nav-item.active i { color: #fff; }
  .nav-item.logout { color: #ff8d86; margin-top: 4px; }
  .nav-item.logout:hover { background: rgba(255,69,58,.14); color: #ff8d86; }
  .sidebar-foot { border-top: 1px solid rgba(255,255,255,.07); padding-top: 12px; }
  .user-box { display: flex; align-items: center; gap: 10px; padding: 4px 8px 12px; }
  .avatar {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(180deg, #0A84FF, #0060df); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800; box-shadow: 0 3px 10px rgba(10,132,255,.4);
  }
  .main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
  .topbar {
    display: flex; align-items: center; gap: 14px;
    padding: 12px 24px; position: sticky; top: 0; z-index: 30;
    background: rgba(18,18,24,.96);
    border-bottom: 1px solid rgba(255,255,255,.08);
  }
  .topbar-title { font-size: 15px; font-weight: 800; letter-spacing: .2px; }
  .topbar-actions { margin-left: auto; display: flex; align-items: center; gap: 10px; }
  .icon-btn {
    width: 34px; height: 34px; border-radius: 9px; border: 1px solid rgba(255,255,255,.1);
    background: rgba(255,255,255,.07); color: #e5e5ea; cursor: pointer; font-size: 14px;
    display: none; align-items: center; justify-content: center;
  }
  .sidebar-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 39; display: none; }
  .sidebar-open .sidebar-backdrop { display: block; }
  .container { max-width: 1320px; margin: 0 auto; padding: 24px 24px 40px; width: 100%; }
  @media (max-width: 900px) {
    .sidebar { position: fixed; left: -260px; transition: left .25s ease; }
    .sidebar-open .sidebar { left: 0; }
    .icon-btn { display: flex; }
    .topbar .traffic { display: none; }
  }
  @media (max-width: 640px) { .container { padding: 14px 12px 40px; } }
</style>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

<div class="app-shell">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-head" style="justify-content:center; padding:8px 0 14px; border-bottom:1px solid rgba(255,255,255,.07);">
      <img src="img/logo.png" alt="Supernova" style="width:150px; height:60px; object-fit:contain;">
    </div>
    <div class="sidebar-section">Menu</div>
    <nav class="sidebar-nav">
      <button id="tab-dashboard" class="nav-item<?php echo $default_tab === 'dashboard' ? ' active' : ''; ?>" onclick="switchTab('dashboard')"><i class="fa-solid fa-gauge-high"></i> Dashboard</button>
      <?php if ($is_admin): ?>
      <button id="tab-broadband" class="nav-item<?php echo $default_tab === 'broadband' ? ' active' : ''; ?>" onclick="switchTab('broadband')"><i class="fa-solid fa-tower-broadcast"></i> Broadband</button>
      <button id="tab-mikrotik" class="nav-item" onclick="switchTab('mikrotik')"><i class="fa-solid fa-network-wired"></i> MikroTik PPPoE</button>
      <?php endif; ?>
      <button id="tab-pelanggan" class="nav-item" onclick="switchTab('pelanggan')"><i class="fa-solid fa-users"></i> Pelanggan <span class="chip" style="margin-left:auto; padding:1px 7px;" id="badge-count">0</span></button>
      <button id="tab-monitoring" class="nav-item" onclick="switchTab('monitoring')"><i class="fa-solid fa-signal"></i> Monitoring</button>
      <?php if ($_SESSION['role'] === 'admin'): ?>
      <button id="tab-admin" class="nav-item" onclick="switchTab('admin')"><i class="fa-solid fa-gear"></i> Kelola Admin</button>
      <?php endif; ?>
    </nav>
    <div class="sidebar-foot">
      <div class="user-box">
        <div class="avatar"><?php echo strtoupper(substr(trim($_SESSION['username']), 0, 2)); ?></div>
        <div style="min-width:0;">
          <div style="font-size:13px; font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
          <div style="font-size:10.5px; color:var(--muted); text-transform:uppercase;"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
        </div>
      </div>
      <a href="logout.php" style="text-decoration:none;"><button class="nav-item logout"><i class="fa-solid fa-power-off"></i> Keluar</button></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <button class="icon-btn" onclick="toggleSidebar()" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
      <div class="traffic" title="Supernova"><i class="t-red"></i><i class="t-yel"></i><i class="t-grn"></i></div>
      <div class="topbar-title" id="topbar-title">Dashboard</div>
      <div class="topbar-actions">
        <span class="chip" style="color:#63e6a5; border-color:rgba(48,209,88,.35); background:rgba(48,209,88,.1);"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Live</span>
        <button class="btn btn-sm btn-amber" onclick="loadDashboardRealtime()"><i class="fa-solid fa-rotate"></i> Segarkan</button>
      </div>
    </header>

<div class="container">

  <!-- TAB 0: DASHBOARD -->
  <div id="view-dashboard" class="<?php echo $default_tab === 'dashboard' ? '' : 'hidden'; ?>">
    <div class="stats-grid">
      <div class="card" style="margin:0"><div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;"><span style="width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:rgba(10,132,255,.16); color:#64D2FF;"><i class="fa-solid fa-users" style="font-size:13px;"></i></span><div style="font-size:11px; color:var(--muted)">Total Pelanggan</div></div><div id="dash-total" class="stat-val"><div class="skel" style="width:45%"></div></div></div>
      <div class="card" style="margin:0"><div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;"><span style="width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:rgba(48,209,88,.16); color:#63e6a5;"><i class="fa-solid fa-circle-check" style="font-size:13px;"></i></span><div style="font-size:11px; color:var(--muted)">Aktif</div></div><div id="dash-aktif" class="stat-val" style="color:#63e6a5"><div class="skel" style="width:45%"></div></div></div>
      <div class="card" style="margin:0"><div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;"><span style="width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:rgba(255,159,10,.16); color:#ffc15e;"><i class="fa-solid fa-ban" style="font-size:13px;"></i></span><div style="font-size:11px; color:var(--muted)">Isolir</div></div><div id="dash-isolir" class="stat-val" style="color:#ffc15e"><div class="skel" style="width:45%"></div></div></div>
      <div class="card" style="margin:0"><div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;"><span style="width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:rgba(255,69,58,.16); color:#ff8d86;"><i class="fa-solid fa-power-off" style="font-size:13px;"></i></span><div style="font-size:11px; color:var(--muted)">Cabut / Nonaktif</div></div><div id="dash-cabut" class="stat-val" style="color:#ff8d86"><div class="skel" style="width:45%"></div></div></div>
      <div class="card" style="margin:0"><div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;"><span style="width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:rgba(48,209,88,.16); color:#63e6a5;"><i class="fa-solid fa-wifi" style="font-size:13px;"></i></span><div style="font-size:11px; color:var(--muted)">Online</div></div><div id="dash-mon-online" class="stat-val" style="color:#63e6a5"><div class="skel" style="width:45%"></div></div></div>
      <div class="card" style="margin:0;"><div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;"><span style="width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:rgba(255,159,10,.16); color:#ffc15e;"><i class="fa-solid fa-wifi-slash" style="font-size:13px;"></i></span><div style="font-size:11px; color:var(--muted)">Offline</div></div><div id="dash-mon-offline" class="stat-val" style="color:#ffc15e"><div class="skel" style="width:45%"></div></div></div>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin-bottom:12px; font-size:12px; color:var(--muted);">
      <span><i class="fa-solid fa-rotate"></i> <span id="dash-mon-ts">memuat...</span></span>
      <button class="btn btn-sm btn-amber" id="dash-refresh-btn" onclick="loadDashboardRealtime()"><i class="fa-solid fa-rotate"></i> Segarkan Status</button>
      <span id="dash-mon-err" class="hidden" style="color:#ff8d86;"></span>
    </div>

    <div class="card" style="margin-bottom:16px;">
      <div class="card-header"><span class="card-title">Komposisi Status</span></div>
      <div style="display:flex; gap:8px; margin-bottom:14px;">
        <div class="bar-track" style="flex:2"><div class="bar-fill" id="bar-aktif" style="width:0; background:linear-gradient(90deg,#30D158,#248a3d);"></div></div>
        <div class="bar-track" style="flex:1"><div class="bar-fill" id="bar-isolir" style="width:0; background:linear-gradient(90deg,#FF9F0A,#b36e00);"></div></div>
        <div class="bar-track" style="flex:1"><div class="bar-fill" id="bar-cabut" style="width:0; background:linear-gradient(90deg,#FF453A,#b52a22);"></div></div>
      </div>
      <div style="display:flex; gap:20px; flex-wrap:wrap; font-size:12px; color:var(--muted);">
        <span><span style="color:#63e6a5;">■</span> <span id="lbl-aktif">Aktif 0</span></span>
        <span><span style="color:#ffc15e;">■</span> <span id="lbl-isolir">Isolir 0</span></span>
        <span><span style="color:#ff8d86;">■</span> <span id="lbl-cabut">Cabut 0</span></span>
        <span style="color:var(--muted)">Port OLT terisi: <b id="dash-port" style="color:#64D2FF">0</b></span>
      </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
      <div class="card-header">
        <span class="card-title"><i class="fa-solid fa-map-location-dot" style="color:#64D2FF;"></i> Peta Pelanggan</span>
        <span class="chip" id="dash-map-count">0 titik</span>
      </div>
      <div id="dash-map" style="height:460px; border-radius:12px; overflow:hidden; border:1px solid rgba(255,255,255,.1); background:#0a0a0f;"></div>
      <p class="hint" style="margin:8px 0 0;"><i class="fa-solid fa-circle-info"></i> Menampilkan pelanggan yang sudah diisi koordinat latitude/longitude (tombol <b>Detail</b> di menu Pelanggan untuk menunjuk lokasi).</p>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">Aksi Cepat</span></div>
      <div class="quick-grid">
        <button class="btn" style="padding:16px; background:rgba(48,209,88,.14); border:1px solid rgba(48,209,88,.35); color:#63e6a5; border-radius:14px; flex-direction:column; gap:6px;" onclick="switchTab('monitoring')"><i class="fa-solid fa-signal" style="font-size:18px;"></i>Monitoring Online/Offline</button>
        <button class="btn" style="padding:16px; background:rgba(255,159,10,.14); border:1px solid rgba(255,159,10,.35); color:#ffc15e; border-radius:14px; flex-direction:column; gap:6px;" onclick="scanOfflineONU()"><i class="fa-solid fa-magnifying-glass" style="font-size:18px;"></i>Scan ONU Offline / LOS</button>
        <button class="btn" style="padding:16px; background:rgba(10,132,255,.14); border:1px solid rgba(10,132,255,.35); color:#64D2FF; border-radius:14px; flex-direction:column; gap:6px;" onclick="switchTab('pelanggan')"><i class="fa-solid fa-users" style="font-size:18px;"></i>Data Pelanggan</button>
        <?php if ($is_admin): ?>
        <button class="btn" style="padding:16px; background:rgba(255,69,58,.14); border:1px solid rgba(255,69,58,.35); color:#ff8d86; border-radius:14px; flex-direction:column; gap:6px;" onclick="window.open('api.php?action=backup_db','_blank')"><i class="fa-solid fa-download" style="font-size:18px;"></i>Backup Database</button>
        <button class="btn" style="padding:16px; background:rgba(191,90,242,.14); border:1px solid rgba(191,90,242,.35); color:#d9a5f7; border-radius:14px; flex-direction:column; gap:6px;" onclick="switchTab('admin')"><i class="fa-solid fa-sliders" style="font-size:18px;"></i>Pengaturan</button>
        <?php endif; ?>
      </div>
    </div>

    <div class="card" style="margin-top:16px;">
      <div class="card-header"><span class="card-title"><i class="fa-solid fa-signal"></i> Status Pelanggan per Kategori</span></div>
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
        <div>
          <div class="mon-head" style="color:#63e6a5;"><i class="fa-solid fa-wifi"></i> Online</div>
          <div class="mon-list-box" id="dash-online-list"></div>
        </div>
        <div>
          <div class="mon-head" style="color:#ffc15e;"><i class="fa-solid fa-wifi-slash"></i> Offline</div>
          <div class="mon-list-box" id="dash-offline-list"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 1: BROADBAND -->  <div id="view-broadband" class="grid-main<?php echo $default_tab === 'broadband' ? '' : ' hidden'; ?>">
    <div>
      <div class="card">
        <div class="card-header">
          <span class="card-title">Parameter Aktivasi Broadband</span>
          <div style="display:flex; gap:8px; align-items:center;">
            <span id="bb-edit-badge" class="hidden" style="font-size:11px; padding:3px 10px; border-radius:99px; background:rgba(52,211,153,0.15); color:#34d399; border:1px solid rgba(52,211,153,0.4);">↻ Update Pelanggan</span>
            <button class="btn btn-sm btn-emerald admin-only" onclick="saveToDB()">+ Simpan ke MySQL</button>
          </div>
        </div>
        <div class="form-grid">
          <div><label>Interface GPON-OLT</label><input type="text" id="bb-iface" value="1/2/4" oninput="checkConflictOnly()"></div>
          <div><label>ONU ID</label><input type="number" id="bb-onu" value="10" oninput="checkConflictOnly()"></div>
          <div id="bb-port-alert" class="col-span-2 alert"></div>
          <div><label>SN ONT</label><input type="text" id="bb-sn" placeholder="ZTEGD6D940BB" style="color:#ffc15e"></div>
          <div><label>Tipe ONT</label><input type="text" id="bb-type" value="ALL-ONT"></div>
          <div class="col-span-2"><label>Nama Pelanggan (Description)</label><input type="text" id="bb-nama" placeholder="Nama Pelanggan"></div>
          <div><label>User ID PPPoE</label><input type="text" id="bb-userid" placeholder="121251720115" style="color:#63e6a5"></div>
          <div>
            <label>Domain PPPoE</label>
            <select id="bb-domain">
              <option value="@supernovanet">@supernovanet</option>
              <option value="@librahomenet">@librahomenet</option>
              <option value="@supernova">@supernova</option>
              <option value="">(Tanpa Domain)</option>
            </select>
          </div>
          <div><label>Password Internet</label><input type="text" id="bb-pass" value="supernova"></div>
          <div><label>VLAN ID</label><input type="number" id="bb-vlan" value="210"></div>
          <div><label>TCONT Profile</label><input type="text" id="bb-prof-down" value="pppoe"></div>
          <div><label>VLAN Profile (WAN)</label><input type="text" id="bb-vlan-prof" value="PPPOE"></div>
          <div>
            <label>Paket Bandwidth (DB)</label>
            <select id="bb-paket">
              <option value="10">10 Mbps</option>
              <option value="20">20 Mbps</option>
              <option value="30" selected>30 Mbps</option>
              <option value="50">50 Mbps</option>
              <option value="100">100 Mbps</option>
              <option value="200">200 Mbps</option>
            </select>
          </div>
          <div style="display:flex; align-items:flex-end;">
            <button class="btn btn-muted" style="width:100%; padding:8px;" onclick="resetBB()">Bersihkan</button>
          </div>
          <div class="col-span-2">
            <button class="btn btn-generate" onclick="generateBroadbandScript()">⚡ GENERATE SCRIPT OLT</button>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title" style="color:#fbbf24">CARI ONU BY SN</span><button class="btn btn-sm btn-muted admin-only" onclick="copyText('out-bb-reboot')">Copy Script</button></div>
        <pre id="out-bb-reboot" style="color:#fde68a; min-height:40px;">Klik GENERATE di kiri...</pre>
      </div>
    </div>
    
    <div>
      <div class="card">
        <div class="card-header"><span class="card-title" style="color:#BF5AF2">1. CEK ONU UNCONFIG (Cari SN Baru)</span><button class="btn btn-sm" onclick="copyText('out-bb-unconfig')">Copy Script</button></div>
        <pre id="out-bb-unconfig" style="color:#ddd6fe; min-height:40px;">show gpon onu un</pre>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title" style="color:#38bdf8">2. CEK STATE GPON (Cari ONU Kosong)</span><button class="btn btn-sm" onclick="copyText('out-bb-state')">Copy Script</button></div>
        <pre id="out-bb-state" style="color:#38bdf8; min-height:40px;">Klik GENERATE di kiri...</pre>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title" style="color:#fb7185">3. HAPUS ONT (No ONU)</span><button class="btn btn-sm btn-rose admin-only" onclick="copyText('out-bb-delete')">Copy Script</button></div>
        <pre id="out-bb-delete" style="color:#fecdd3; min-height:40px;">Klik GENERATE di kiri...</pre>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title" style="color:#34d399">4. REGISTRASI SN ONT</span><button class="btn btn-sm admin-only" onclick="copyText('out-bb-regis')">Copy Script</button></div>
        <pre id="out-bb-regis" style="color:#63e6a5">Klik GENERATE di kiri...</pre>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title" style="color:#64D2FF">5. CONFIG LAYANAN PPPoE</span><button class="btn btn-sm admin-only" onclick="copyText('out-bb-service')">Copy Script</button></div>
        <pre id="out-bb-service" style="color:#d9a5f7">Klik GENERATE di kiri...</pre>
        <div style="margin-top:10px;">
          <button class="btn btn-generate admin-only" style="background:linear-gradient(180deg,#FF375F,#c81e4e); box-shadow:0 4px 14px rgba(255,55,95,.35), inset 0 1px 0 rgba(255,255,255,.2);" onclick="deployBroadband()">
            <i class="fa-solid fa-rocket"></i> DEPLOY 3-4-5 KE OLT (1 Klik)
          </button>
          <p class="hint" style="margin:8px 0 0;"><i class="fa-solid fa-circle-info"></i> Menjalankan langkah 3 (hapus ONU) + 4 (registrasi SN) + 5 (config PPPoE) sekaligus ke OLT.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB BARU: MIKROTIK PPPOE PROVISIONING -->
  <div id="view-mikrotik" class="hidden">
    <div style="max-width: 600px; margin: 0 auto;">
      <div class="card" style="border-left: 4px solid #30D158;">
        <div class="card-header">
          <span class="card-title" style="color:#30D158;"><i class="fa-solid fa-network-wired"></i> Tambah Secret PPPoE ke MikroTik (API)</span>
        </div>
        <div class="form-grid">
          <div class="col-span-2">
            <label>Username PPPoE (User ID + Domain)</label>
            <div style="display:flex; gap:8px;">
              <input type="text" id="mk-username" placeholder="Contoh: 121251720115@supernovanet" autocomplete="off" style="flex:1">
              <button type="button" class="btn btn-sm btn-emerald" style="height:38px; white-space:nowrap;" onclick="generateMkUsername()" title="Auto-generate ID unik (cek MikroTik + database)">
                <i class="fa-solid fa-dice"></i> GENERATE ID
              </button>
            </div>
            <div id="mk-username-status" style="font-size:11px; margin-top:2px;"></div>
          </div>
          <div class="col-span-2">
            <label>Password PPPoE</label>
            <input type="text" id="mk-password" placeholder="Password internet pelanggan..." value="supernova" autocomplete="off">
          </div>
         <div class="col-span-2" style="display:flex; justify-content:space-between; align-items:flex-end; gap:8px;">
            <div style="flex-grow:1;">
              <label>Profile / Paket Bandwidth di MikroTik</label>
              <select id="mk-profile">
                <option value="default">-- Memuat profile dari MikroTik... --</option>
              </select>
            </div>
            <button class="btn btn-sm btn-muted" style="height:36px;" onclick="loadMikrotikProfiles()" title="Refresh List Profile"><i class="fa-solid fa-rotate"></i></button>
          </div>
          <div class="col-span-2">
            <label>Keterangan / Nama Pelanggan (Comment)</label>
            <input type="text" id="mk-comment" placeholder="Contoh: LITTLE LODGE" autocomplete="off">
          </div>
          <div class="col-span-2">
            <button class="btn btn-emerald" style="width:100%; padding:12px; font-weight:bold;" onclick="createMikrotikSecret()">
              <i class="fa-solid fa-cloud-arrow-up"></i> SIMPAN KE MIKROTIK VIA API
            </button>
          </div>
        </div>
      </div>
      <div id="mk-response-box" class="card hidden" style="border-left: 4px solid #38bdf8; background:#000;">
        <div class="card-header"><span class="card-title" style="color:#38bdf8;">Respon API MikroTik</span></div>
        <pre id="mk-response-text" style="color:#0f0; margin:0; border:none; background:transparent;"></pre>
      </div>
    </div>
  </div>

  <!-- TAB 3: DATA PELANGGAN -->
  <div id="view-pelanggan" class="<?php echo $default_tab === 'pelanggan' ? '' : 'hidden'; ?>">
    <div class="stats-grid">
      <div class="card" style="margin:0"><div style="font-size:11px; color:var(--muted)">Total Pelanggan</div><div id="stat-total" class="stat-val">0</div></div>
      <div class="card" style="margin:0"><div style="font-size:11px; color:var(--muted)">Port OLT Aktif</div><div id="stat-aktif" class="stat-val" style="color:#34d399">0</div></div>
      <div class="card" style="margin:0"><div style="font-size:11px; color:var(--muted)">Status CABUT / Belum Ada</div><div id="stat-cabut" class="stat-val" style="color:#fbbf24">0</div></div>
      <div class="card" style="margin:0; border-color:rgba(255,69,58,.4);"><div style="font-size:11px; color:var(--muted)">Data Kurang Lengkap</div><div id="stat-incomplete" class="stat-val" style="color:#ff8d86">0</div></div>
      <div class="card" style="margin:0"><div style="font-size:11px; color:var(--muted)">Rata-Rata Paket</div><div id="stat-avg" class="stat-val" style="color:#64D2FF">0 Mbps</div></div>
    </div>

    <div class="card" style="display:flex; flex-wrap:wrap; gap:10px; justify-content:space-between; align-items:center;">
      <div style="display:flex; gap:10px; flex-grow:1; max-width:620px; flex-wrap:wrap;">
        <div style="flex:1; min-width:200px; position:relative;">
          <input type="text" id="search-pelanggan" placeholder="Cari nama pelanggan, SN ONT, ID PPPoE, atau port..." oninput="liveSearch()" onkeydown="if(event.key==='Enter') doSearch()" style="width:100%;">
        </div>
        <select id="filter-status" style="max-width:140px;" onchange="renderCustomers()">
          <option value="">Semua Status</option>
          <option value="Aktif">Aktif</option>
          <option value="Isolir">Isolir</option>
          <option value="Cabut">Cabut</option>
        </select>
        <select id="filter-port" style="max-width:180px;" onchange="renderCustomers()">
          <option value="">Semua Port</option>
        </select>
        <button class="btn btn-sm btn-muted" onclick="doSearch()"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
      </div>
    <button class="btn btn-emerald admin-only"
        onclick="openAddModal()">
        <i class="fa-solid fa-user-plus"></i>
        Tambah Pelanggan
    </button>

    <button class="btn btn-amber"
        onclick="scanOfflineONU()">
        <i class="fa-solid fa-magnifying-glass"></i>
        Scan ONU Offline
    </button>

    <button class="btn btn-emerald"
        onclick="exportCSV()">
        <i class="fa-solid fa-download"></i>
        Export CSV
    </button>

    <div style="display:flex; gap:8px; align-items:center;" id="pag-controls">
      <span id="pag-info" style="color:var(--muted); font-size:12px;"></span>
    </div>

</div>

    <div class="card" style="padding:0; overflow:hidden;">
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th onclick="sortTable('id')" id="th-id" style="white-space:nowrap;">No. ID</th>
              <th onclick="sortTable('client')" id="th-client">Nama Pelanggan</th>
              <th onclick="sortTable('paket')" id="th-paket" style="white-space:nowrap;">Paket</th>
              <th onclick="sortTable('sn')" id="th-sn" style="white-space:nowrap;">SN ONT</th>
              <th onclick="sortTable('id_pppoe')" id="th-id_pppoe" style="white-space:nowrap;">ID PPPoE</th>
              <th onclick="sortTable('port')" id="th-port" style="white-space:nowrap;">Port OLT</th>
              <th onclick="sortTable('status')" id="th-status" style="white-space:nowrap;">Status</th>
              <th style="text-align:right">Aksi</th>
            </tr>
          </thead>
          <tbody id="customer-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>
  
  <!-- TAB: MONITORING ONLINE/OFFLINE (MIKROTIK) -->
  <div id="view-monitoring" class="hidden">
    <div class="stats-grid">
      <div class="card" style="margin:0"><div style="font-size:11px; color:var(--muted)">Total Pelanggan</div><div id="mon-total" class="stat-val">0</div></div>
      <div class="card" style="margin:0"><div style="font-size:11px; color:var(--muted)">Online</div><div id="mon-online" class="stat-val" style="color:#34d399">0</div></div>
      <div class="card" style="margin:0"><div style="font-size:11px; color:var(--muted)">Offline</div><div id="mon-offline" class="stat-val" style="color:#fbbf24">0</div></div>
      <div class="card" style="margin:0"><div style="font-size:11px; color:var(--muted)">Sesi Aktif MikroTik</div><div id="mon-mk" class="stat-val" style="color:#38bdf8">0</div></div>
    </div>

    <div class="card" style="display:flex; flex-wrap:wrap; gap:10px; justify-content:space-between; align-items:center;">
      <div style="font-size:12px; color:var(--muted);">
        Status diambil langsung dari sesi aktif PPPoE MikroTik (dicocokkan via ID PPPoE). Auto-refresh tiap 30 detik.
      </div>
      <div style="display:flex; gap:8px; align-items:center;">
        <div style="min-width:230px; position:relative;">
          <input type="text" id="mon-search" placeholder="Cari nama / IP..." oninput="filterMonitoring()" style="min-width:230px; padding:8px 12px; border-radius:8px; border:1px solid var(--border); background:#0b1220; color:var(--text); font-size:12px;">
        </div>
        <button class="btn btn-amber" onclick="loadMonitoring()"><i class="fa-solid fa-rotate"></i> Segarkan</button>
        <button class="btn btn-emerald" onclick="exportMonitoringCSV()"><i class="fa-solid fa-download"></i> Export CSV</button>
      </div>
    </div>

    <div id="mon-error" class="alert alert-warn hidden" style="margin-bottom:12px;"></div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; align-items:start;">
      <div class="card" style="padding:0; overflow:hidden; min-width:0;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border-bottom:1px solid var(--border);">
          <span style="font-weight:700; color:#34d399; font-size:13px;"><i class="fa-solid fa-wifi"></i> Online</span>
          <span class="badge" style="color:#34d399; border-color:#30D158; background:rgba(16,185,129,.15);" id="mon-online-count">0</span>
        </div>
        <div class="table-scroll">
          <table style="width:100%; table-layout:fixed;">
            <thead>
              <tr>
                <th style="white-space:nowrap; width:44px;">No.</th>
                <th style="white-space:nowrap;">Nama Pelanggan</th>
                <th style="white-space:nowrap; width:130px;">Uptime</th>
                <th style="white-space:nowrap; width:170px;">TX / RX (Realtime)</th>
              </tr>
            </thead>
            <tbody id="monitor-tbody-online"></tbody>
          </table>
        </div>
      </div>

      <div class="card" style="padding:0; overflow:hidden; min-width:0;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; border-bottom:1px solid var(--border);">
          <span style="font-weight:700; color:#fbbf24; font-size:13px;"><i class="fa-solid fa-wifi-slash"></i> Offline</span>
          <span class="badge" style="color:#ffc15e; border-color:#FF9F0A; background:rgba(245,158,11,.12);" id="mon-offline-count">0</span>
        </div>
        <div class="table-scroll">
          <table style="width:100%; table-layout:fixed;">
            <thead>
              <tr>
                <th style="white-space:nowrap; width:44px;">No.</th>
                <th style="white-space:nowrap;">Nama Pelanggan</th>
                <th style="white-space:nowrap; width:130px;">Uptime</th>
              </tr>
            </thead>
            <tbody id="monitor-tbody-offline"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 4: KELOLA ADMIN -->
  <?php if ($_SESSION['role'] === 'admin'): ?>
  <div id="view-admin" class="hidden">
      <!-- Header + ringkasan -->
      <div class="card" style="padding:16px 18px; margin-bottom:14px; background:linear-gradient(135deg, rgba(34,45,66,.7), rgba(15,23,42,.85));">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
          <div>
            <div style="display:flex; align-items:center; gap:10px;">
              <span style="width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; background:rgba(99,102,241,.16); border:1px solid rgba(99,102,241,.4); color:#64D2FF;"><i class="fa-solid fa-gear"></i></span>
              <div>
                <div style="font-size:15px; font-weight:800; color:#fff;">Kelola Admin</div>
                <div class="hint">Konfigurasi sistem, akun pengguna, dan operasional data.</div>
              </div>
            </div>
          </div>
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <span class="chip" style="color:#34d399; border-color:rgba(16,185,129,.35); background:rgba(16,185,129,.1);"><i class="fa-solid fa-user-gear"></i> <span id="admin-count-chip">0</span> Akun</span>
            <span class="chip" style="color:#38bdf8; border-color:rgba(56,189,248,.35); background:rgba(56,189,248,.1);"><i class="fa-solid fa-paper-plane"></i> Notifikasi TG</span>
          </div>
        </div>
      </div>

      <!-- Koneksi Perangkat (selebar halaman) -->
      <div class="sec-label"><i class="fa-solid fa-plug"></i> Koneksi Perangkat</div>
      <div class="two-col" style="margin-bottom:16px;">
        <div class="card" style="border-left: 4px solid #06b6d4; margin-bottom:0;">
          <div class="card-header"><span class="card-title" style="color:#06b6d4;"><i class="fa-solid fa-server"></i> Koneksi OLT (Telnet)</span></div>
          <div class="form-grid">
            <div><label>IP Address OLT</label><input type="text" id="conf-olt-ip" placeholder="192.168.1.10"></div>
            <div><label>Port</label><input type="text" id="conf-olt-port" placeholder="23"></div>
            <div><label>Username OLT</label><input type="text" id="conf-olt-user" placeholder="admin"></div>
            <div><label>Password OLT</label><input type="password" id="conf-olt-pass" placeholder="***"></div>
            <div><label>Latitude</label><input type="text" id="conf-olt-lat" placeholder="Contoh: -8.341083°S"></div>
            <div><label>Longitude</label><input type="text" id="conf-olt-lng" placeholder="Contoh: 116.038155°E"></div>
            <div class="col-span-2"><button class="btn btn-outline-primary" style="width:100%; color:#06b6d4; border-color:#06b6d4;" onclick="saveOltSettings()"><i class="fa-solid fa-floppy-disk"></i> Simpan Config OLT</button></div>
          </div>
        </div>

        <div class="card" style="border-left: 4px solid #FF9F0A; margin-bottom:0;">
          <div class="card-header"><span class="card-title" style="color:#FF9F0A;"><i class="fa-solid fa-network-wired"></i> Koneksi MikroTik (API)</span></div>
          <div class="form-grid">
            <div><label>IP Address MikroTik</label><input type="text" id="conf-mk-ip" placeholder="192.168.88.1"></div>
            <div><label>Port API (Default: 8728)</label><input type="text" id="conf-mk-port" placeholder="8728"></div>
            <div><label>Username MikroTik</label><input type="text" id="conf-mk-user" placeholder="admin"></div>
            <div><label>Password MikroTik</label><input type="password" id="conf-mk-pass" placeholder="***"></div>
            <div><label>Latitude</label><input type="text" id="conf-mk-lat" placeholder="Contoh: -8.341083°S"></div>
            <div><label>Longitude</label><input type="text" id="conf-mk-lng" placeholder="Contoh: 116.038155°E"></div>
            <div class="col-span-2">
              <label style="display:flex; align-items:center; gap:8px; margin:0; cursor:pointer;">
                <input type="checkbox" id="conf-mk-ssl" style="width:auto; cursor:pointer;">
                Gunakan API SSL (centang jika API memakai TLS / port 8729)
              </label>
            </div>
            <div class="col-span-2"><button class="btn btn-amber" style="width:100%; color:#000;" onclick="saveMikrotikSettings()"><i class="fa-solid fa-floppy-disk"></i> Simpan Config MikroTik API</button></div>
          </div>
        </div>
      </div>

      <!-- Daftar Akun | Tambah User -->
      <div class="two-col" style="margin-bottom:16px; align-items:start;">
        <div class="card" style="padding:0; overflow:hidden; margin-bottom:0;">
          <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; border-bottom:1px solid var(--border);">
            <span class="card-title" style="margin:0; color:#63e6a5;"><i class="fa-solid fa-users"></i> Daftar Akun</span>
            <span class="chip" id="users-count-chip">0</span>
          </div>
          <div style="max-height:340px; overflow-y:auto;">
            <table>
              <thead><tr><th>Username</th><th>Role Akses</th><th style="text-align:right">Aksi</th></tr></thead>
              <tbody id="users-tbody"></tbody>
            </table>
          </div>
        </div>

        <div class="card" style="border-left: 4px solid #30D158; margin-bottom:0;">
          <div class="card-header"><span class="card-title" style="color:#30D158;"><i class="fa-solid fa-user-plus"></i> Tambah User Aplikasi</span></div>
          <div class="form-grid">
            <div class="col-span-2"><label>Username</label><input type="text" id="admin-username" placeholder="Username baru..." autocomplete="off"></div>
            <div class="col-span-2"><label>Password</label><input type="password" id="admin-password" placeholder="Minimal 6 karakter..." autocomplete="off"></div>
            <div class="col-span-2">
              <label>Role Akses</label>
              <select id="admin-role">
                <option value="admin">Admin (Full Akses)</option>
                <option value="readonly" selected>Read-Only (Hanya Lihat)</option>
              </select>
            </div>
            <div class="col-span-2"><button class="btn btn-emerald" style="width:100%; padding:10px;" onclick="saveUserDB()"><i class="fa-solid fa-user-plus"></i> Tambah Akun</button></div>
          </div>
        </div>
      </div>

      <!-- Notifikasi Telegram | Operasional Data -->
      <div class="two-col" style="margin-bottom:16px; align-items:start;">
        <div class="card" style="border-left: 4px solid #38bdf8; margin-bottom:0;">
          <div class="card-header">
            <span class="card-title" style="color:#38bdf8;"><i class="fa-solid fa-paper-plane"></i> Notifikasi Telegram</span>
            <span class="chip" style="color:#34d399; border-color:rgba(16,185,129,.35); background:rgba(16,185,129,.1);"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Aktif</span>
          </div>
          <div class="form-grid">
            <div class="col-span-2"><label>Bot Token</label><input type="password" id="conf-tg-token" placeholder="123456:ABC-xyz..." autocomplete="off"></div>
            <div class="col-span-2"><label>Chat ID</label><input type="text" id="conf-tg-chat" placeholder="-1001234567890" autocomplete="off"></div>
            <div class="col-span-2"><p class="hint" style="margin:0;"><i class="fa-solid fa-circle-info"></i> Mengirim notifikasi otomatis ke grup Telegram: pelanggan baru/diubah/dihapus, ubah status, restart ONU, perubahan akun, dan import data. Klik simpan untuk mengirim pesan uji.</p></div>
            <div class="col-span-2"><button class="btn btn-outline-primary" style="width:100%; color:#38bdf8; border-color:#38bdf8;" onclick="saveTelegramSettings()"><i class="fa-solid fa-paper-plane"></i> Simpan & Kirim Uji</button></div>
          </div>
        </div>

        <div class="card" style="border-left: 4px solid #BF5AF2; margin-bottom:0;">
          <div class="card-header"><span class="card-title" style="color:#BF5AF2;"><i class="fa-solid fa-database"></i> Operasional Data</span></div>
          <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
            <button class="btn btn-sm btn-emerald" onclick="window.open('api.php?action=backup_db','_blank')"><i class="fa-solid fa-download"></i> Backup Database</button>
            <button class="btn btn-sm btn-rose" onclick="clearAudit()"><i class="fa-solid fa-trash"></i> Bersihkan Log</button>
          </div>
          <label>Import Pelanggan <span class="hint">(format per baris: nama;paket;sn;idpppoe;port;lat;lng — lat/lng opsional)</span></label>
          <textarea id="import-text" rows="3" style="font-family:monospace; font-size:12px; margin-top:4px;" placeholder="Contoh:&#10;MAWAR SEJATI;50;ZTEGD6D940BB;121251720200@supernovanet;1/2/7:18;-8.6499;115.2201"></textarea>
          <button class="btn btn-sm btn-amber" style="width:100%; color:#000; margin-top:8px;" onclick="importCustomers()"><i class="fa-solid fa-file-import"></i> Import Data</button>
        </div>
      </div>

      <!-- Audit Log (paling bawah) -->
      <div class="card" style="padding:0; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; border-bottom:1px solid var(--border);">
          <span class="card-title" style="margin:0; color:#63e6a5;"><i class="fa-solid fa-book"></i> Audit Log</span>
          <button class="btn btn-sm btn-outline-primary" onclick="loadAudit()"><i class="fa-solid fa-rotate"></i> Muat Ulang</button>
        </div>
        <div style="max-height:420px; overflow-y:auto;">
          <table>
            <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Detail</th></tr></thead>
            <tbody id="audit-tbody"></tbody>
          </table>
        </div>
      </div>
  </div>
  <?php endif; ?>

  </div>
  </div><!-- /.main -->
</div><!-- /.app-shell -->

<!-- MODAL TERMINAL OLT LIVE -->
<div id="olt-terminal-modal" class="modal-overlay hidden">
  <div class="modal-card modal-card-lg">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <h3 style="margin:0; color:#64D2FF; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <i class="fa-solid fa-terminal"></i>
        <span id="olt-terminal-title">Terminal OLT Live</span>
      </h3>
      <div style="display:flex; gap:8px;">
        <button class="btn btn-sm btn-outline-primary" onclick="copyTerminal()" title="Salin hasil"><i class="fa-solid fa-copy"></i> Salin</button>
        <button id="btn-export-offline" class="btn btn-sm btn-emerald" onclick="exportOfflineTXT(window.lastOfflineReport || '')" style="display:none;">Export TXT</button>
        <button class="btn btn-sm btn-rose" onclick="closeTerminalModal()">Tutup</button>
      </div>
    </div>
    <pre id="olt-terminal-output" class="term">Menunggu koneksi OLT...</pre>
  </div>
</div>

<!-- MODAL RIWAYAT -->
<div id="history-modal" class="modal-overlay hidden">
  <div class="modal-card modal-card-lg">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <h3 style="margin:0; color:#64D2FF; display:flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span id="history-title">Riwayat</span>
      </h3>
      <button class="btn btn-sm btn-rose" onclick="document.getElementById('history-modal').classList.add('hidden')">Tutup</button>
    </div>
    <div style="max-height:420px; overflow:auto;">
      <table>
        <thead><tr><th>Waktu</th><th>Aksi</th><th>Detail</th><th>Oleh</th></tr></thead>
        <tbody id="history-tbody"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL EDIT / TAMBAH DATA PELANGGAN -->
<div id="edit-modal" class="modal-overlay hidden">
  <div class="modal-card">
    <h3 id="edit-modal-title" style="margin-top:0; margin-bottom:18px; color:#64D2FF;">Edit Data Pelanggan</h3>
    <input type="hidden" id="edit-id">
    <div style="margin-bottom:12px;"><label>Nama Pelanggan</label><input type="text" id="edit-nama"></div>
    <div style="margin-bottom:12px;">
      <label>Paket (Mbps) <span id="edit-paket-status" style="font-weight:normal; font-size:11px; color:var(--muted);"></span></label>
      <select id="edit-paket"><option value="30">30 Mbps</option></select>
    </div>
    <div style="margin-bottom:12px;"><label>SN ONT</label><input type="text" id="edit-sn" style="color:#ffc15e" placeholder="Contoh: ZTEGD6D940BB"></div>
    <div style="margin-bottom:12px;">
      <label>ID PPPoE</label>
      <div style="display:flex; gap:8px;">
        <input type="text" id="edit-id-pppoe" style="color:#63e6a5; flex:1;" placeholder="Contoh: 121251720115@supernovanet">
        <button type="button" id="btn-edit-generate" class="btn btn-muted" style="padding:8px 12px; white-space:nowrap;" onclick="generateEditPppoe()" title="Auto-generate ID unik (dicek ke MikroTik + database)"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate</button>
      </div>
      <div id="edit-pppoe-status" style="font-size:11px; margin-top:4px; color:var(--muted); min-height:14px;"></div>
    </div>
    <div style="margin-bottom:12px;"><label>Password PPPoE</label><input type="text" id="edit-pppoe-pass" value="supernova" style="color:#fbbf24"></div>
    <div style="margin-bottom:12px;"><label>Port OLT</label><input type="text" id="edit-port" placeholder="Contoh: 1/2/8:1 (atau kosongkan)"></div>
    <div style="margin-bottom:12px;">
      <label>Latitude (koordinat peta)</label>
      <div style="display:flex; gap:8px;">
        <input type="text" id="edit-lat" placeholder="Contoh: 8.341083°S" style="color:#7dd4ff; flex:1;" oninput="editCoordPreview()">
        <button type="button" class="btn btn-muted" style="padding:8px 12px; white-space:nowrap;" onclick="geoPickFromMap()" title="Ambil titik dari peta"><i class="fa-solid fa-location-crosshairs"></i> Pilih</button>
      </div>
    </div>
    <div style="margin-bottom:12px;"><label>Longitude (koordinat peta)</label><input type="text" id="edit-lng" placeholder="Contoh: 116.038155°E" style="color:#7dd4ff" oninput="editCoordPreview()"></div>
    <div id="edit-coord-hint" class="hint" style="margin-bottom:12px; min-height:14px;"></div>
    <div style="margin-bottom:20px;">
      <label>Status</label>
      <select id="edit-status">
        <option value="Aktif">Aktif</option>
        <option value="Isolir">Isolir</option>
        <option value="Cabut">Cabut</option>
      </select>
    </div>
    <div style="margin-bottom:16px;">
      <label id="edit-mk-create-wrap" style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600;">
        <input type="checkbox" id="edit-mk-create" checked style="width:auto;">
        <span><i class="fa-solid fa-router"></i> Buat PPPoE juga di MikroTik</span>
      </label>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:10px;">
      <button class="btn btn-muted" onclick="closeEditModal()">Batal</button>
      <button id="edit-modal-save" class="btn btn-emerald admin-only" onclick="saveEditCustomerDB()">Simpan Perubahan</button>
    </div>
  </div>
</div>

<!-- MODAL PILIH KOORDINAT DARI PETA -->
<div id="pick-map-modal" class="modal-overlay hidden">
  <div class="modal-card modal-card-lg">
    <h3 style="margin-top:0; margin-bottom:6px; color:#64D2FF;"><i class="fa-solid fa-location-crosshairs"></i> Pilih Titik di Peta</h3>
    <p class="hint" style="margin:0 0 12px;">Geser marker ke lokasi pelanggan, lalu klik "Pakai Titik Ini".</p>
    <div id="pick-map" style="height:360px; border-radius:12px; overflow:hidden; border:1px solid rgba(255,255,255,.1); background:#0a0a0f;"></div>
    <div style="display:flex; align-items:center; gap:12px; margin-top:12px; font-family:monospace; font-size:13px; flex-wrap:wrap;">
      <span style="color:var(--muted);">Latitude: <b id="pick-lat" style="color:#7dd4ff">-8.6472000</b></span>
      <span style="color:var(--muted);">Longitude: <b id="pick-lng" style="color:#7dd4ff">115.2077000</b></span>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
      <button class="btn btn-muted" onclick="geoPickCancel()">Batal</button>
      <button class="btn btn-emerald" onclick="geoPickApply()"><i class="fa-solid fa-location-dot"></i> Pakai Titik Ini</button>
    </div>
  </div>
</div>

<!-- MODAL DETAIL PELANGGAN -->
<div id="detail-modal" class="modal-overlay hidden">
  <div class="modal-card modal-card-lg" style="max-width:720px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
      <h3 style="margin:0; color:#64D2FF;"><i class="fa-solid fa-circle-info"></i> Detail Pelanggan</h3>
      <button class="btn btn-muted" onclick="closeDetailModal()">Tutup</button>
    </div>
    <div id="detail-body"></div>
  </div>
</div>

<!-- MODAL EDIT AKUN ADMIN -->
<div id="edit-user-modal" class="modal-overlay hidden">
  <div class="modal-card">
    <h3 style="margin-top:0; margin-bottom:18px; color:#ffc15e;"><i class="fa-solid fa-user-pen"></i> Edit Akun Admin</h3>
    <input type="hidden" id="edit-user-id">
    <div style="margin-bottom:12px;"><label>Username</label><input type="text" id="edit-user-username" autocomplete="off"></div>
    <div style="margin-bottom:12px;"><label>Password Baru <span style="color:var(--muted); font-weight:normal;">(Kosongkan jika tak diubah)</span></label><input type="password" id="edit-user-password" placeholder="***" autocomplete="off"></div>
    <div style="margin-bottom:20px;">
      <label>Role Akses</label>
      <select id="edit-user-role">
        <option value="admin">Admin (Full Akses)</option>
        <option value="readonly">Read-Only (Hanya Lihat)</option>
      </select>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:10px;">
      <button class="btn btn-muted" onclick="closeEditUserModal()">Batal</button>
      <button class="btn btn-amber" onclick="saveEditUserDB()">Simpan Akun</button>
    </div>
  </div>
</div>

<!-- MODAL TERMINAL OLT LIVE -->

<div id="toast" class="hidden">Notifikasi</div>

<script>
let customers = [];
let users_db = [];
let sortCol = 'id';
let sortAsc = false;
let editingId = null;
let pageDb = 1;
let qDb = '';
let dbTotal = 0;
let monSortCol = 'status';
let monSortAsc = true;
let monBusy = false;
const pageSize = 50;
const CSRF_TOKEN = '<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES); ?>';

// Semua request non-GET otomatis membawa token CSRF
(function() {
  const origFetch = window.fetch;
  window.fetch = function(url, opts) {
    opts = opts || {};
    if ((opts.method || 'GET').toUpperCase() !== 'GET') {
      opts.headers = Object.assign({}, opts.headers, { 'X-CSRF-Token': CSRF_TOKEN });
    }
    return origFetch.call(this, url, opts);
  };
})();

function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
  }[c]));
}
function jsStr(s) {
  return String(s ?? '')
    .replace(/\\/g, '\\\\')
    .replace(/'/g, "\\'")
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
} 

function switchTab(tab) {
  ['dashboard', 'broadband', 'mikrotik', 'pelanggan', 'monitoring', 'admin'].forEach(t => {
    const view = document.getElementById('view-' + t);
    const btn = document.getElementById('tab-' + t);
    if(view) view.classList.add('hidden');
    if(btn) btn.classList.remove('active');
  });
  
  const tView = document.getElementById('view-' + tab);
  const tBtn = document.getElementById('tab-' + tab);
  if(tView) tView.classList.remove('hidden');
  if(tBtn) tBtn.classList.add('active');

  const titles = {
    dashboard: 'Dashboard', broadband: 'Broadband', mikrotik: 'MikroTik PPPoE',
    pelanggan: 'Data Pelanggan', monitoring: 'Monitoring', admin: 'Kelola Admin'
  };
  const tt = document.getElementById('topbar-title');
  if (tt) tt.textContent = titles[tab] || 'Dashboard';
  // tutup sidebar di mobile
  document.body.classList.remove('sidebar-open');
  
  if (tab === 'dashboard') loadDashboard();
  if (tab === 'pelanggan') renderCustomers();
  if (tab === 'mikrotik') loadMikrotikProfiles();
  if (tab === 'monitoring') loadMonitoring();
  if (tab === 'admin') { loadUsersDB(); loadOltSettings(); loadMikrotikSettings(); loadAudit(); }

  try { localStorage.setItem('supernova_active_tab', tab); } catch(e) {}
}

function toggleSidebar() {
  document.body.classList.toggle('sidebar-open');
}

async function loadDataDB() {
  try {
    const res = await fetch('api.php?action=get&q=' + encodeURIComponent(qDb) + '&page=' + pageDb + '&limit=' + pageSize);
    const j = await res.json();
    customers = j.data || [];
    const total = j.total || 0;
    dbTotal = total;
    const info = document.getElementById('pag-info');
    if (info) info.textContent = total ? ((pageDb-1)*pageSize+1) + ' - ' + Math.min(total, pageDb*pageSize) + ' dari ' + total + ' data' : '0 data';
    const pag = document.getElementById('pag-controls');
    if (pag) {
      const totalPages = Math.max(1, Math.ceil(total / pageSize));
      pag.innerHTML = `
        <button class="btn btn-sm btn-muted" onclick="pageDb = Math.max(1, pageDb-1); loadDataDB();">← Sebelum</button>
        <span style="color:var(--muted);font-size:12px;">${totalPages > 1 ? 'Halaman ' + pageDb + '/' + totalPages : ''}</span>
        <button class="btn btn-sm btn-muted" onclick="pageDb = ${Math.min(totalPages, pageDb + 1)}; loadDataDB();">Berikut →</button>`;
    }
    renderCustomers();
    checkConflictOnly();
  } catch(e) { console.error("Gagal load dari DB", e); }
}

function doSearch() {
  qDb = document.getElementById('search-pelanggan').value.trim();
  pageDb = 1;
  loadDataDB();
}

// Pencarian langsung saat mengetik (seperti di halaman Monitoring), dengan debounce 300ms
let searchTimer = null;
function liveSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(doSearch, 300);
}

// Tombol tanda silang (×) untuk menghapus isi field pencarian
function attachSearchClear(inputId, onClear) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const wrap = input.parentElement;
  if (wrap) wrap.classList.add('search-wrap');
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'search-clear';
  btn.innerHTML = '&times;';
  btn.title = 'Hapus pencarian';
  btn.addEventListener('click', () => {
    input.value = '';
    btn.style.display = 'none';
    if (typeof onClear === 'function') onClear();
    input.focus();
  });
  if (wrap) wrap.appendChild(btn);
  const update = () => { btn.style.display = input.value ? 'flex' : 'none'; };
  input.addEventListener('input', update);
  input.addEventListener('keyup', update);
  update();
}
attachSearchClear('search-pelanggan', doSearch);
attachSearchClear('mon-search', filterMonitoring);

function statusBadge(st) {
  const map = {
    'Aktif':  ['Aktif', '#63e6a5', '#30D158', 'rgba(48,209,88,0.15)'],
    'Isolir': ['Isolir', '#ffc15e', '#FF9F0A', 'rgba(255,159,10,0.12)'],
    'Cabut':  ['Cabut', '#ff8d86', '#FF453A', 'rgba(255,69,58,0.12)']
  };
  const m = map[st] || [st, '#98989f', '#63636b', 'rgba(255,255,255,0.08)'];
  return `<span class="badge" style="color:${m[1]};border-color:${m[2]};background:${m[3]};">${esc(m[0])}</span>`;
}

function sortTable(column) {
  if (sortCol === column) sortAsc = !sortAsc;
  else { sortCol = column; sortAsc = true; }
  updateSortIcons();
  renderCustomers();
}

function updateSortIcons() {
  ['id', 'client', 'paket', 'sn', 'id_pppoe', 'port', 'status'].forEach(col => {
    const th = document.getElementById(`th-${col}`);
    if(th) {
        th.classList.remove('asc', 'desc');
        if (col === sortCol) th.classList.add(sortAsc ? 'asc' : 'desc');
    }
  });
}

function checkConflictOnly() {
  try {
    const iface = document.getElementById('bb-iface').value.trim();
    const onu = document.getElementById('bb-onu').value.trim();
    const target = `${iface}:${onu}`;
    const found = customers.find(c => c.port === target);
    const el = document.getElementById('bb-port-alert');
    if (found) {
      el.className = 'col-span-2 alert alert-warn';
      el.textContent = `! Port ${target} sudah dipakai: ${found.client} (${found.id_pppoe})`;
    } else {
      const used = customers
        .filter(c => c.port.startsWith(iface + ':'))
        .map(c => parseInt(c.port.split(':')[1]))
        .filter(n => !isNaN(n))
        .sort((a,b) => a - b);
      const info = used.length ? `ONU terpakai: [${used.join(', ')}]` : 'Port masih kosong';
      el.className = 'col-span-2 alert alert-ok';
      el.textContent = `✓ Port ${target} tersedia (${info})`;
    }
  } catch(e) {}
}

function generateBroadbandScript() {
  const iface = document.getElementById('bb-iface').value.trim();
  const onu = document.getElementById('bb-onu').value.trim();
  const sn = document.getElementById('bb-sn').value.trim().toUpperCase();
  const type = document.getElementById('bb-type').value.trim();
  const nama = document.getElementById('bb-nama').value.trim();
  const userid = document.getElementById('bb-userid').value.trim();
  const domain = document.getElementById('bb-domain').value;
  const pass = document.getElementById('bb-pass').value.trim() || 'supernova';
  const vlan = document.getElementById('bb-vlan').value.trim() || '210';
  const profDown = document.getElementById('bb-prof-down').value.trim() || 'pppoe';
  const vlanProf = document.getElementById('bb-vlan-prof').value.trim() || 'PPPOE';

  checkConflictOnly();

  document.getElementById('out-bb-state').textContent = `show gpon onu state gpon-olt_${iface}`;
  document.getElementById('out-bb-delete').textContent = `configure t\ninterface gpon-olt_${iface}\nno onu ${onu}\nexit`;
  // Step 4: tanpa 'config t' karena setelah step 3 shell sudah dalam mode config
  document.getElementById('out-bb-regis').textContent = `interface gpon-olt_${iface}\nonu ${onu} type ALL-ONT sn ${sn}\nexit`;
  
  document.getElementById('out-bb-service').textContent = 
`interface gpon-onu_${iface}:${onu}
Description ${nama}_${userid}
tcont 1 name supernova profile ${profDown}
gemport 1 name supernova tcont 1
switchport mode hybrid vport 1
service-port 1 vport 1 user-vlan ${vlan} vlan ${vlan}
port-identification format DSL-FORUM-PON vport 1
port-identification sub-option remote-id enable vport 1
pppoe-intermediate-agent enable vport 1
pppoe-intermediate-agent trust true replace vport 1
exit
pon-onu-mng gpon-onu_${iface}:${onu}
service supernova gemport 1 iphost 1 vlan ${vlan}
wan-ip mode pppoe username ${userid}${domain} password ${pass} vlan-profile ${vlanProf} host 1
ip-host 2 dhcp-enable enable ping-response enable traceroute-response enable
exit
exit
Show gpon remote wan-ip gpon-onu_${iface}:${onu}
end
write`;

  document.getElementById('out-bb-reboot').textContent = `show gpon onu by sn ${sn}`;
  
  // Auto isi form MikroTik Tab sebelah
  document.getElementById('mk-username').value = userid + domain;
  document.getElementById('mk-password').value = pass || 'supernova';
  document.getElementById('mk-comment').value = nama;
  
  showToast('Script Broadband di-generate & disinkronkan ke form MikroTik!');
}

// Deploy gabungan langkah 3+4+5 ke OLT dalam 1 klik
async function deployBroadband() {
  const iface = document.getElementById('bb-iface').value.trim();
  const onu = document.getElementById('bb-onu').value.trim();
  const sn = document.getElementById('bb-sn').value.trim().toUpperCase();
  const userid = document.getElementById('bb-userid').value.trim();

  if (!iface || !onu || !sn || !userid) {
    alert('Lengkapi dulu: Interface GPON-OLT, ONU ID, SN ONT, dan User ID PPPoE (tekan GENERATE SCRIPT OLT).');
    return;
  }
  if (!/^\d+$/.test(onu)) { alert('ONU ID harus berupa angka.'); return; }
  if (!confirm('Deploy ke OLT sekarang?\n\nInterface : ' + iface + '\nONU ID    : ' + onu + '\nSN ONT    : ' + sn + '\n\nLangkah 3 (hapus ONU) + 4 (registrasi SN) + 5 (config PPPoE) akan dijalankan sekaligus.')) {
    return;
  }

  const btn = event && event.currentTarget ? event.currentTarget : null;
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deploy...'; }

  setTerminal('Deploy Broadband ke OLT — ' + iface + ':' + onu, true);
  const out = document.getElementById('olt-terminal-output');
  if (out) out.textContent = '> mengirim langkah 3-4-5 ke OLT...';

  const data = {
    iface: iface, onu: onu, sn: sn,
    type: document.getElementById('bb-type').value.trim() || 'ALL-ONT',
    nama: document.getElementById('bb-nama').value.trim(),
    userid: userid,
    domain: document.getElementById('bb-domain').value || '',
    pass: document.getElementById('bb-pass').value.trim() || 'supernova',
    vlan: document.getElementById('bb-vlan').value.trim() || '210',
    profDown: document.getElementById('bb-prof-down').value.trim() || 'pppoe',
    vlanProf: document.getElementById('bb-vlan-prof').value.trim() || 'PPPOE'
  };

  try {
    const res = await fetch('api.php?action=olt_deploy', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const json = await res.json();
    if (out) out.classList.remove('term-loading');
    const raw = json.status === 'success'
      ? (json.data || json.message || '(Respons kosong)')
      : 'Gagal: ' + (json.message || 'respons tidak valid');
    if (out) out.textContent = raw;
    if (json.status === 'success') {
      showToast('Deploy ke OLT selesai!');
    }
  } catch (e) {
    if (out) { out.classList.remove('term-loading'); out.textContent = 'ERROR: ' + e.toString(); }
    alert('Gagal terhubung ke API deploy.');
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-rocket"></i> DEPLOY 3-4-5 KE OLT (1 Klik)'; }
  }
}

// ================= FUNGSI AUTO-GENERATE ID PPPoE (cek MikroTik + DB) =================
async function generateMkUsername() {
  const btn = event && event.currentTarget ? event.currentTarget : null;
  const statusEl = document.getElementById('mk-username-status');
  const setStatus = (txt, color) => {
    if (statusEl) { statusEl.textContent = txt; statusEl.style.color = color || 'var(--muted)'; }
  };
  if (btn) btn.disabled = true;
  setStatus('Men-generate ID unik... cek MikroTik + database...', '#38bdf8');
  try {
    const res = await fetch('api.php?action=mikrotik_generate_username', { method: 'POST', body: '{}' });
    const json = await res.json();
    if (json.status === 'success' && json.username) {
      document.getElementById('mk-username').value = json.username;
      setStatus('✓ ID ' + json.id + ' — dicek ke MikroTik' + (json.checked_mikrotik ? ' & database, tidak konflik' : ' (router tidak terhubung, dicek DB saja)'), '#34d399');
      showToast('ID auto-generate: ' + json.username);
    } else {
      setStatus('[X] ' + (json.message || 'Gagal generate ID'), '#f87171');
      alert('[X] ' + (json.message || 'Gagal generate ID'));
    }
  } catch (e) {
    setStatus('[X] Error: gagal hubungi API generate.', '#f87171');
  } finally {
    if (btn) btn.disabled = false;
  }
}

// ================= FUNGSI CREATE MIKROTIK SECRET VIA API =================
async function createMikrotikSecret() {
    const username = document.getElementById('mk-username').value.trim();
    const password = document.getElementById('mk-password').value.trim();
    const profile = document.getElementById('mk-profile').value.trim() || 'default';
    const comment = document.getElementById('mk-comment').value.trim();

    if (!username || !password) {
        alert("Username dan Password PPPoE wajib diisi!");
        return;
    }

    const respBox = document.getElementById('mk-response-box');
    const respText = document.getElementById('mk-response-text');
    
    respBox.classList.remove('hidden');
    respText.textContent = "Menghubungkan ke MikroTik API...";

    try {
        let res = await fetch('api.php?action=mikrotik_add_secret', {
            method: 'POST',
            body: JSON.stringify({ username, password, profile, comment })
        });
        let json = await res.json();
        
        if (json.status === 'success') {
            respText.textContent = "[✔] " + json.message;
            showToast("Berhasil tambah user PPPoE ke MikroTik!");
        } else {
            respText.textContent = "[X] " + json.message;
        }
    } catch(e) {
        respText.textContent = "[X] Error: Gagal terhubung ke endpoint API server lokal.";
    }
}
// =========================================================================

function renderCustomers() {
  try {
    const pf = document.getElementById('filter-port').value;
    const sf = document.getElementById('filter-status').value;
    const sel = document.getElementById('filter-port');

    if (sel.options.length <= 1) {
      const ports = [...new Set(customers.map(c => c.port.split(':')[0]).filter(p => p && p.includes('/')))].sort();
      ports.forEach(p => {
        const o = document.createElement('option'); o.value = p; o.textContent = 'Port ' + p; sel.appendChild(o);
      });
    }

    let list = customers.filter(c => {
      const mp = !pf || (c.port && c.port.startsWith(pf + ':'));
      const ms = !sf || c.status === sf;
      return mp && ms;
    });

    list.sort((a, b) => {
      let valA = a[sortCol]; let valB = b[sortCol];
      if (sortCol === 'paket' || sortCol === 'id') { valA = Number(valA); valB = Number(valB); } 
      else { if(typeof valA==='string') valA=valA.toLowerCase(); if(typeof valB==='string') valB=valB.toLowerCase(); }
      if (valA < valB) return sortAsc ? -1 : 1;
      if (valA > valB) return sortAsc ? 1 : -1;
      return 0;
    });

    document.getElementById('customer-tbody').innerHTML = list.map((c, index) => {
      let validPort = c.port && c.port.includes(':');
      // Data dianggap lengkap bila SN, ID PPPoE, dan Port OLT terisi valid
      const sn = (c.sn || '').trim();
      const pppoe = (c.id_pppoe || '').trim();
      const port = (c.port || '').trim();
      const dataComplete = sn !== '' && sn !== '-' &&
                           pppoe !== '' && pppoe !== '-' &&
                           port !== '' && port !== 'BELUM ADA' && port !== '-' && port.includes(':');
      const leftAccent = !dataComplete ? 'box-shadow:inset 3px 0 0 #FF453A;' : '';
      return `
      <tr>
        <td style="font-family:monospace; color:var(--muted); ${leftAccent}">
          ${!dataComplete ? '<span class="badge blink-red" style="color:#ff8d86;border-color:#FF453A;background:rgba(255,69,58,.15); margin-bottom:4px;">⚠ Data Kurang</span><br>' : ''}
          ${esc(String(c.id_pppoe || '').split('@')[0] || '-')}
        </td>
        <td style="font-weight:600">${esc(c.client)}</td>
        <td style="font-family:monospace; color:#64D2FF; white-space:nowrap;" title="${esc(c.paket_profile || (c.paket + ' Mbps'))}">${esc(c.paket_profile || (c.paket + ' Mbps'))}</td>
        <td style="font-family:monospace; color:#ffc15e">${esc(c.sn || '-')}</td>
        <td style="font-family:monospace">${esc(c.id_pppoe)}</td>
        <td style="font-family:monospace; color:${validPort ? '#63e6a5' : '#ff8d86'}">${esc(c.port)}</td>
        <td style="white-space:nowrap;">
          ${statusBadge(c.status)}
            <select class="admin-only" style="width:auto; padding:2px 4px; font-size:11px; margin-left:6px; vertical-align:middle;" onchange="setCustomerStatus(${c.id}, this.value)">
              <option value="Aktif"${c.status === 'Aktif' ? ' selected' : ''}>Aktif</option>
              <option value="Isolir"${c.status === 'Isolir' ? ' selected' : ''}>Isolir</option>
              <option value="Cabut"${c.status === 'Cabut' ? ' selected' : ''}>Cabut</option>
            </select>
        </td>
        <td style="text-align:right; white-space:nowrap;">
          ${validPort ? `
            <button class="btn btn-sm btn-outline-primary" title="Cek Status Koneksi" onclick="reqOLT('status', '${jsStr(c.port)}')"><i class="fa-solid fa-wifi"></i></button>
            <button class="btn btn-sm btn-outline-primary" title="Cek Redaman DBm" onclick="reqOLT('power', '${jsStr(c.port)}')"><i class="fa-solid fa-bolt"></i></button>
            <button class="btn btn-sm btn-rose admin-only" title="Restart ONT Pelanggan" onclick="reqOLT('reboot', '${jsStr(c.port)}')"><i class="fa-solid fa-rotate-right"></i></button>
          ` : ''}
          <button class="btn btn-sm btn-outline-primary" title="Muat Parameter" onclick="loadCustomer(${c.id})"><i class="fa-solid fa-arrow-up-right-from-square"></i></button>
          <button class="btn btn-sm btn-emerald" title="Detail & Lokasi di Peta" onclick="openDetailModal(${c.id})"><i class="fa-solid fa-circle-info"></i></button>
          <button class="btn btn-sm btn-outline-primary" title="Riwayat" onclick="showHistory(${c.id})"><i class="fa-solid fa-clock-rotate-left"></i></button>
          <button class="btn btn-sm btn-amber admin-only" title="Edit" onclick="openEditModal(${c.id})"><i class="fa-solid fa-pen"></i></button>
          <button class="btn btn-sm btn-rose admin-only" title="Hapus" onclick="deleteCustomer(${c.id})"><i class="fa-solid fa-trash"></i></button>
        </td>
      </tr>
    `}).join('');

    if (!list.length) {
      document.getElementById('customer-tbody').innerHTML =
        '<tr><td colspan="8" style="text-align:center; color:var(--muted); padding:28px;">Belum ada data pelanggan. Gunakan tombol "Simpan ke MySQL" di Broadband, atau menu Import di Kelola Admin.</td></tr>';
    }

    document.getElementById('badge-count').textContent = dbTotal;
    document.getElementById('stat-total').textContent = dbTotal;
    const aktif = list.filter(c => c.status === 'Aktif').length;
    document.getElementById('stat-aktif').textContent = aktif;
    document.getElementById('stat-cabut').textContent = list.length - aktif;
    // Hitung pelanggan dengan data tidak lengkap (SN / ID PPPoE / Port OLT kosong)
    const incomplete = list.filter(c => {
      const sn = (c.sn || '').trim(), pp = (c.id_pppoe || '').trim(), port = (c.port || '').trim();
      return sn === '' || sn === '-' || pp === '' || pp === '-' ||
             port === '' || port === 'BELUM ADA' || port === '-' || !port.includes(':');
    }).length;
    const statInc = document.getElementById('stat-incomplete');
    if (statInc) { statInc.textContent = incomplete; statInc.style.color = incomplete > 0 ? '#ff8d86' : '#34d399'; }
    const avg = list.reduce((a, c) => a + (Number(c.paket) || 0), 0) / (list.length || 1);
    document.getElementById('stat-avg').textContent = avg.toFixed(1) + ' Mbps';
  } catch(e) {}
}

async function setCustomerStatus(id, status) {
  const c = customers.find(x => x.id == id);
  if (!c) return;
  const label = {Aktif:'AKTIFKAN kembali layanannya?', Isolir:'ISOLIR (blokir layanan)?', Cabut:'CABUT (hapus layanan)?'}[status] || '';
  if (!confirm(c.client + ' — ' + label)) { renderCustomers(); return; }
  try {
    const res = await fetch('api.php?action=set_status', { method: 'POST', body: JSON.stringify({ id, status }) });
    const j = await res.json();
    showToast(j.message || (j.status === 'success' ? 'Status diubah' : 'Gagal'));
    loadDataDB();
  } catch(e) { showToast('Error: ' + e); }
}

async function showHistory(id) {
  try {
    const res = await fetch('api.php?action=get_history&id=' + id);
    const j = await res.json();
    const box = document.getElementById('history-tbody');
    const c = customers.find(x => x.id == id);
    document.getElementById('history-title').textContent = 'Riwayat — ' + (c ? c.client : '#' + id);
    box.innerHTML = (j.data || []).length ? j.data.map(h => `
      <tr>
        <td style="color:var(--muted); font-family:monospace; white-space:nowrap;">${esc(h.waktu)}</td>
        <td><span class="badge">${esc(h.action)}</span></td>
        <td style="font-family:monospace;">${esc(h.detail)}</td>
        <td style="color:var(--muted)">${esc(h.username)}</td>
      </tr>`).join('') : '<tr><td colspan="4" style="color:var(--muted); text-align:center;">Belum ada riwayat</td></tr>';
    document.getElementById('history-modal').classList.remove('hidden');
  } catch(e) { showToast('Error memuat riwayat'); }
}

function setTerminal(title, loading) {
  const t = document.getElementById('olt-terminal-title');
  if (t) t.innerHTML = (loading
    ? '<span class="term-title-pill"><i class="fa-solid fa-spinner fa-spin"></i> Menghubungi OLT</span> '
    : '') + '<span>' + esc(title) + '</span>';
  const out = document.getElementById('olt-terminal-output');
  if (out) out.classList.toggle('term-loading', !!loading);
  const modal = document.getElementById('olt-terminal-modal');
  if (modal) modal.classList.remove('hidden');
}

function copyTerminal() {
  const el = document.getElementById('olt-terminal-output');
  if (!el) return;
  navigator.clipboard.writeText(el.textContent).then(() => showToast('Hasil disalin!'));
}

// Pewarnaan hasil perintah redaman OLT (nilai dBm di-highlight sesuai kualitas)
function colorizePower(text) {
  const safe = esc(text);
  let out = safe.replace(/(-?\d+(?:\.\d+)?)\s*(dBm|dB)/gi, (m, num, unit) => {
    const v = parseFloat(num);
    let color = '#ff8d86';   // buruk (di bawah -25 dBm) default merah
    if (v >= -8 && v <= -25) color = '#4ade80';   // bagus
    else if (v > -8 && v <= -1) color = '#ffc15e'; // kelebihan daya (kuning/warning)
    return `<span style="color:${color}; font-weight:700;">${num} ${unit}</span>`;
  });
  if (/att|rx|receive|power|dBm/i.test(text)) {
    out += '\n\n<span style="color:#64748b;">Referensi RX Power ONU: -8 s/d -25 dBm = baik (hijau) | di atas -8 = kelebihan daya (kuning) | di bawah -25 = buruk/LOS (merah)</span>';
  }
  return out;
}

const TERM_TITLES = {
  status: 'Status Koneksi',
  power: 'Redaman / Daya Optik',
  reboot: 'Reboot ONT'
};

async function reqOLT(action, port)
{
    if (action === 'reboot') {
        if (!confirm('Yakin restart ONU ' + port + ' ?')) {
            return;
        }
    }

    setTerminal((TERM_TITLES[action] || 'Cek OLT') + ' — ' + port, true);
    const out = document.getElementById('olt-terminal-output');
    if (out) out.textContent = port ? ('> olt_' + action + ' gpon-onu_' + port) : '';

    try {
        let res = await fetch('api.php?action=olt_' + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ port: port })
        });
        let json = await res.json();
        out.classList.remove('term-loading');
        const rawData = json.status === 'success'
            ? (json.data || json.message || '(Respons kosong)')
            : 'Gagal: ' + (json.message || 'respons tidak valid');
        if (action === 'power') {
            out.innerHTML = colorizePower(rawData);
        } else {
            out.textContent = rawData;
        }
    }
    catch (e) {
        if (out) {
            out.classList.remove('term-loading');
            out.textContent = 'ERROR: ' + e.toString();
        }
    }
}

async function scanOfflineONU()
{
    setTerminal('Scan ONU Offline / LOS', true);
    const out = document.getElementById('olt-terminal-output');
    if (out) out.textContent = 'Scanning seluruh PON...';

    try
    {
        let res =
            await fetch(
                'api.php?action=scan_offline'
            );

        let json =
            await res.json();

        let txt =
            'ONU OFFLINE / LOS REPORT\n\n';

        if (
            !json.offline ||
            json.offline.length === 0
        )
        {
            txt +=
                'Tidak ada ONU bermasalah';
        }
        else
        {
            let los =
                json.offline.filter(
                    x => x.state === 'LOS'
                );

            let offline =
                json.offline.filter(
                    x => x.state === 'OFFLINE'
                );

            txt +=
                'SUMMARY\n' +
                '=====================\n' +
                'TOTAL LOS      : ' + los.length + '\n' +
                'TOTAL OFFLINE  : ' + offline.length + '\n' +
                'TOTAL TROUBLE  : ' +
                json.offline.length +
                '\n\n';

            if (los.length > 0)
            {
                txt +=
                    '========== LOS ==========\n\n';

                los.forEach(x => {

                    txt +=
                        '[LOS] ' +
                        x.client +
                        ' - ' +
                        x.port +
                        '\n';

                });

                txt += '\n';
            }

            if (offline.length > 0)
            {
                txt +=
                    '======= OFFLINE =======\n\n';

                offline.forEach(x => {

                    txt +=
                        '[OFFLINE] ' +
                        x.client +
                        ' - ' +
                        x.port +
                        '\n';

                });
            }

            txt +=
                '\n\nTOTAL : ' +
                json.offline.length;
        }

        document.getElementById(
            'olt-terminal-output'
        ).textContent = txt;
        document.getElementById('olt-terminal-output').classList.remove('term-loading');

        window.lastOfflineReport = txt;

        document.getElementById(
            'btn-export-offline'
        ).style.display =
            'inline-block';
    }
    catch(e)
    {
        const out = document.getElementById('olt-terminal-output');
        if (out) {
            out.classList.remove('term-loading');
            out.textContent = 'ERROR : ' + e.toString();
        }
    }
}

function exportOfflineTXT(text)
{
    let blob = new Blob(
        [text],
        {
            type: 'text/plain'
        }
    );

    let a =
        document.createElement('a');

    a.href =
        URL.createObjectURL(blob);

    a.download =
        'OFFLINE_REPORT.txt';

    a.click();
}

function closeTerminalModal() { document.getElementById('olt-terminal-modal').classList.add('hidden'); }

// ============================================================
// Sinkronisasi Broadband <-> Pelanggan
// Menampilkan badge saat sedang meng-update pelanggan tertentu
// ============================================================
function updateEditBadge() {
  const badge = document.getElementById('bb-edit-badge');
  if (!badge) return;
  if (editingId != null && editingId !== '' && editingId !== undefined) {
    const c = customers.find(x => x.id == editingId);
    badge.textContent = '↻ Update Pelanggan' + (c ? ': ' + esc(c.client) : ' #' + editingId);
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
}

// Cocokkan pelanggan existing berdasarkan nama atau ID PPPoE (case-insensitive)
function findMatchingCustomer(nama, id_pppoe) {
  const n = String(nama || '').trim().toLowerCase();
  const ip = String(id_pppoe || '').trim().toLowerCase();
  if (!n && !ip) return null;
  let best = null;
  for (const c of customers) {
    const cn = String(c.client || '').trim().toLowerCase();
    const ci = String(c.id_pppoe || '').trim().toLowerCase();
    // prioritas: ID PPPoE persis, lalu nama persis, lalu nama mengandung (dua arah)
    if (ip && ci === ip) return c;
    if (!best && n && cn === n) best = c;
  }
  return best;
}

async function saveToDB() {
  const nama = document.getElementById('bb-nama').value.trim();
  const userid = document.getElementById('bb-userid').value.trim();
  const domain = document.getElementById('bb-domain').value;
  const id_pppoe = userid + domain;
  if (!nama) { alert("Nama pelanggan (Description) wajib diisi."); return; }
  if (!userid) { alert("User ID PPPoE wajib diisi (bisa auto-generate via tombol Generate)."); return; }
  const data = {
    client: nama,
    paket: Number(document.getElementById('bb-paket').value) || 30,
    sn: document.getElementById('bb-sn').value.trim().toUpperCase() || '-',
    id_pppoe,
    port: (document.getElementById('bb-iface').value.trim() && document.getElementById('bb-onu').value.trim())
      ? document.getElementById('bb-iface').value.trim() + ':' + document.getElementById('bb-onu').value.trim()
      : 'BELUM ADA'
  };
  // Jika editingId ter-set (dari menu Pelanggan -> Muat Parameter), kirim ke action update.
  // Jika tidak, kirim ke action add — API di sisi server otomatis mendeteksi duplikat
  // (id_pppoe / nama sama) dan meng-update pelanggan yang sudah ada (bukan insert baru).
  const isEdit = editingId != null && editingId !== '' && editingId !== undefined;
  if (isEdit) data.id = Number(editingId);
  try {
    let res = await fetch('api.php?action=' + (isEdit ? 'update' : 'add'), { method: 'POST', body: JSON.stringify(data) });
    let json = await res.json();
    if (json.status === 'success') {
      showToast(json.updated || isEdit ? 'Data pelanggan diperbarui & sinkron ke menu Pelanggan!' : 'Tersimpan di MySQL!');
      editingId = null;
      updateEditBadge();
      loadDataDB();
    } else { alert("Gagal simpan: " + json.message); }
  } catch (e) { alert("Error API MySQL"); }
}

async function deleteCustomer(id) {
  if (confirm('Yakin ingin menghapus data ini?')) {
    try {
        let res = await fetch('api.php?action=delete', { method: 'POST', body: JSON.stringify({id: id}) });
        let json = await res.json();
        if (json.status === 'success') { showToast('Dihapus dari database!'); loadDataDB(); } 
        else { alert("Gagal menghapus: " + json.message); }
    } catch (e) { alert("Error API MySQL"); }
  }
}

// Auto-generate ID PPPoE (dengan domain @supernovanet) + password supernova
async function generateEditPppoe() {
  const btn = document.getElementById('btn-edit-generate');
  const statusEl = document.getElementById('edit-pppoe-status');
  const setStatus = (txt, color) => { if (statusEl) { statusEl.textContent = txt; statusEl.style.color = color || 'var(--muted)'; } };
  if (btn) { btn.disabled = true; btn.style.opacity = '.6'; }
  setStatus('Men-generate ID unik... cek MikroTik + database...', '#38bdf8');
  try {
    const res = await fetch('api.php?action=mikrotik_generate_username', { method: 'POST', body: '{}' });
    const json = await res.json();
    if (json.status === 'success' && json.username) {
      document.getElementById('edit-id-pppoe').value = json.username;
      document.getElementById('edit-pppoe-pass').value = 'supernova';
      setStatus('✓ ID ' + json.id + '@supernovanet — tidak konflik' + (json.checked_mikrotik ? '' : ' (router tidak terhubung, dicek DB saja)'), '#34d399');
      showToast('ID auto-generate: ' + json.username);
    } else {
      setStatus('[X] ' + (json.message || 'Gagal generate ID'), '#f87171');
      alert('[X] ' + (json.message || 'Gagal generate ID'));
    }
  } catch (e) {
    setStatus('[X] Error: gagal hubungi API generate.', '#f87171');
  } finally {
    if (btn) { btn.disabled = false; btn.style.opacity = ''; }
  }
}

const FALLBACK_PAKET = [10, 20, 30, 50, 100, 200];

// Muat daftar paket dari profile PPPoE MikroTik (nama profile asli, mis. 'Bisnis 50M').
// Bila router off, pakai daftar standar (Mbps).
// Layer peta dasar: Satelit (Google, fallback Esri) + Jalan (OSM), dengan kontrol toggle
function createSatLayer() {
  const l = L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
    attribution: '&copy; Google', maxZoom: 19
  });
  let fails = 0;
  l.on('tileerror', () => {
    fails++;
    if (fails >= 6) {
      l.setUrl('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', 'Tiles &copy; Esri — Maxar, Earthstar Geographics');
    }
  });
  return l;
}

function addBaseLayers(map, defaultLayer) {
  const layers = {
    'Satelit': createSatLayer(),
    'Jalan': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap', maxZoom: 19
    })
  };
  (layers[defaultLayer] || layers['Satelit']).addTo(map);
  L.control.layers(layers, null, { position: 'topright', collapsed: false }).addTo(map);
  return layers;
}

// Parser koordinat fleksibel: "8.341083", "-8.341083", "8.341083°S", "8°20'27.9\"S", "116.038155°E"
function parseCoord(input) {  const s = String(input ?? '').trim();
  if (!s) return NaN;
  let neg = 1;
  if (/[SW]/i.test(s)) neg = -1;                       // Selatan / Barat => negatif
  let clean = s.replace(/[°˚′'`"]/g, ' ').replace(/[NSWE]/gi, ' ').replace(/[^0-9.\s-]/g, ' ');
  const nums = clean.split(/\s+/).filter(Boolean).map(Number);
  if (!nums.length || nums.some(isNaN)) return NaN;
  let val;
  if (nums.length === 1) val = nums[0];
  else if (nums.length === 2) val = nums[0] + nums[1] / 60;
  else if (nums.length >= 3) val = nums[0] + nums[1] / 60 + nums[2] / 3600;
  else return NaN;
  return neg < 0 ? -Math.abs(val) : val;
}

function editCoordPreview() {
  const h = document.getElementById('edit-coord-hint');
  if (!h) return;
  const lat = parseCoord(document.getElementById('edit-lat').value);
  const lng = parseCoord(document.getElementById('edit-lng').value);
  if (isFinite(lat) && isFinite(lng) && lat !== 0 && lng !== 0) {
    if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
      h.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#63e6a5;"></i> <span style="color:#63e6a5;">Koordinat valid:</span> ' + lat.toFixed(7) + ', ' + lng.toFixed(7);
    } else {
      h.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color:#ffc15e;"></i> <span style="color:#ffc15e;">Latitude -90..90 · Longitude -180..180</span>';
    }
  } else if (document.getElementById('edit-lat').value !== '' || document.getElementById('edit-lng').value !== '') {
    h.innerHTML = '<span style="color:var(--muted);">Isi keduanya — contoh: <b>8.341083°S</b> dan <b>116.038155°E</b></span>';
  } else {
    h.innerHTML = '';
  }
}

// Pilih titik koordinat dari peta (modal picker)
function geoPickFromMap() {
  const curLat = parseFloat(document.getElementById('edit-lat').value) || -8.6472;
  const curLng = parseFloat(document.getElementById('edit-lng').value) || 115.2077;
  document.getElementById('pick-map-modal').classList.remove('hidden');
  requestAnimationFrame(() => {
    if (!window._pickMap) {
      window._pickMap = L.map('pick-map').setView([curLat, curLng], 15);
      addBaseLayers(window._pickMap, 'Satelit');
      window._pickMarker = L.marker([curLat, curLng], { draggable: true }).addTo(window._pickMap);
      window._pickMarker.on('dragend', () => {
        const p = window._pickMarker.getLatLng();
        document.getElementById('pick-lat').textContent = p.lat.toFixed(7);
        document.getElementById('pick-lng').textContent = p.lng.toFixed(7);
      });
    } else {
      window._pickMap.setView([curLat, curLng], 15);
      window._pickMarker.setLatLng([curLat, curLng]);
      document.getElementById('pick-lat').textContent = curLat.toFixed(7);
      document.getElementById('pick-lng').textContent = curLng.toFixed(7);
    }
    setTimeout(() => window._pickMap.invalidateSize(), 150);
  });
}
function geoPickCancel() { document.getElementById('pick-map-modal').classList.add('hidden'); }
function geoPickApply() {
  document.getElementById('edit-lat').value = document.getElementById('pick-lat').textContent;
  document.getElementById('edit-lng').value = document.getElementById('pick-lng').textContent;
  editCoordPreview();
  geoPickCancel();
}

async function loadEditPaketOptions(selected) {
  const select = document.getElementById('edit-paket');
  const statusEl = document.getElementById('edit-paket-status');
  selected = String(selected || '');
  const renderNumeric = () => {
    select.innerHTML = FALLBACK_PAKET.map(p => `<option value="${p}">${p} Mbps</option>`).join('');
    if (selected && !/^\d+$/.test(selected)) {
      // Mode edit: nama profile lama tetap dipertahankan walau router tidak terhubung
      select.innerHTML += `<option value="${esc(selected)}" selected>${esc(selected)}</option>`;
    } else {
      const sel = /^\d+$/.test(selected) ? selected : '30';
      if (!FALLBACK_PAKET.includes(Number(sel))) select.innerHTML += `<option value="${sel}" selected>${sel} Mbps</option>`;
      select.value = sel;
    }
  };
  if (statusEl) statusEl.textContent = 'memuat profile MikroTik...';
  try {
    const res = await fetch('api.php?action=mikrotik_get_profiles');
    const json = await res.json();
    if (json.status === 'success' && Array.isArray(json.data) && json.data.length) {
      select.innerHTML = json.data.map(p => `<option value="${esc(p)}">${esc(p)}</option>`).join('');
      if (selected) {
        const found = Array.from(select.options).some(o => o.value === selected);
        if (found) {
          select.value = selected;
        } else {
          // Paket lama tidak ada di daftar MikroTik: TETAP dipilih, jangan ganti ke profile lain
          const label = /^\d+$/.test(selected) ? selected + ' Mbps' : selected;
          select.innerHTML += `<option value="${esc(selected)}" selected>${esc(label)}</option>`;
        }
      } else {
        // Tambah baru tanpa default: pilih profile pertama sebagai bawaan
        select.value = select.options[0].value;
      }
      if (statusEl) statusEl.textContent = 'dari profile MikroTik';
    } else {
      renderNumeric();
      if (statusEl) statusEl.textContent = 'gagal ambil profile, pakai daftar standar';
    }
  } catch (e) {
    renderNumeric();
    if (statusEl) statusEl.textContent = 'error koneksi, pakai daftar standar';
  }
}

function openAddModal() {
  window._editOldPppoe = '';
  document.getElementById('edit-id').value = '';
  document.getElementById('edit-nama').value = '';
  loadEditPaketOptions(30);
  document.getElementById('edit-sn').value = '';
  document.getElementById('edit-id-pppoe').value = '';
  document.getElementById('edit-pppoe-pass').value = 'supernova';
  document.getElementById('edit-port').value = '';
  document.getElementById('edit-lat').value = '';
  document.getElementById('edit-lng').value = '';
  editCoordPreview();
  document.getElementById('edit-status').value = 'Aktif';
  document.getElementById('edit-modal-title').textContent = 'Tambah Pelanggan Baru';
  document.getElementById('edit-mk-create-wrap').style.display = 'flex';
  document.getElementById('edit-mk-create').checked = true;
  document.getElementById('edit-modal-save').innerHTML = '<i class="fa-solid fa-plus"></i> Simpan Pelanggan Baru';
  document.getElementById('edit-modal').classList.remove('hidden');
  setTimeout(() => document.getElementById('edit-nama').focus(), 60);
}

function openEditModal(id) {
  const c = customers.find(x => x.id == id);
  if(!c) return;
  window._editOldPppoe = c.id_pppoe || '';
  document.getElementById('edit-id').value = c.id;
  document.getElementById('edit-nama').value = c.client;
  loadEditPaketOptions(c.paket_profile || c.paket);
  document.getElementById('edit-sn').value = c.sn || '-';
  document.getElementById('edit-id-pppoe').value = c.id_pppoe;
  document.getElementById('edit-pppoe-pass').value = 'supernova';
  document.getElementById('edit-port').value = c.port;
  document.getElementById('edit-lat').value = (c.latitude && c.latitude != 0) ? c.latitude : '';
  document.getElementById('edit-lng').value = (c.longitude && c.longitude != 0) ? c.longitude : '';
  editCoordPreview();
  document.getElementById('edit-status').value = c.status || 'Aktif';
  document.getElementById('edit-modal-title').textContent = 'Edit Data Pelanggan';
  document.getElementById('edit-mk-create-wrap').style.display = 'none';
  document.getElementById('edit-modal-save').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan';
  document.getElementById('edit-modal').classList.remove('hidden');
}

function closeEditModal() { document.getElementById('edit-modal').classList.add('hidden'); }

async function saveEditCustomerDB() {
  const id = document.getElementById('edit-id').value;
  const rawPaket = document.getElementById('edit-paket').value;
  const mNum = String(rawPaket).match(/(\d+)/);
  const data = {
    client: document.getElementById('edit-nama').value.trim(),
    paket: mNum ? parseInt(mNum[1], 10) : 30,
    paket_profile: /^\d+$/.test(rawPaket) ? '' : rawPaket,
    sn: document.getElementById('edit-sn').value.trim().toUpperCase() || '-',
    id_pppoe: document.getElementById('edit-id-pppoe').value.trim(),
    port: document.getElementById('edit-port').value.trim() || 'BELUM ADA',
    latitude: parseCoord(document.getElementById('edit-lat').value) || '',
    longitude: parseCoord(document.getElementById('edit-lng').value) || '',
    status: document.getElementById('edit-status').value
  };
  if (id) data.id = parseInt(id);
  const action = id ? 'update' : 'add';
  try {
    let res = await fetch('api.php?action=' + action, { method: 'POST', body: JSON.stringify(data) });
    let json = await res.json();
    if (json.status === 'success') {
        const isNew = !id && !json.updated;
        if (isNew && document.getElementById('edit-mk-create').checked) {
            const pass = document.getElementById('edit-pppoe-pass').value.trim();
            if (!pass) {
                alert('Pelanggan tersimpan di database, tetapi Password PPPoE kosong — PPPoE tidak dibuat di MikroTik.');
            } else {
                const mkProfile = data.paket_profile || 'default';
                const mkComment = data.client + ' | Port ' + (data.port && data.port !== 'BELUM ADA' ? data.port : 'BELUM ADA') + (data.latitude && data.longitude ? ' | Map ' + data.latitude + ',' + data.longitude : '');
                try {
                    const mkRes = await fetch('api.php?action=mikrotik_add_secret', { method: 'POST', body: JSON.stringify({
                        username: data.id_pppoe,
                        password: pass,
                        profile: mkProfile,
                        comment: mkComment,
                        skip_db_check: 1
                    })});
                    const mkJson = await mkRes.json();
                    if (mkJson.status === 'success') {
                        showToast('Pelanggan tersimpan & PPPoE berhasil dibuat di MikroTik!');
                    } else {
                        alert('Pelanggan tersimpan di database, TAPI gagal buat PPPoE di MikroTik: ' + mkJson.message);
                    }
                } catch (e) {
                    alert('Pelanggan tersimpan di database, TAPI gagal hubungi MikroTik untuk buat PPPoE.');
                }
            }
        } else if (!isNew && id) {
            // EDIT: sinkronkan nama + port OLT + paket + username + koordinat ke secret di MikroTik
            const mkComment = data.client + ' | Port ' + (data.port && data.port !== 'BELUM ADA' ? data.port : 'BELUM ADA') + (data.latitude && data.longitude ? ' | Map ' + data.latitude + ',' + data.longitude : '');
            const syncBody = { username: window._editOldPppoe || data.id_pppoe, comment: mkComment };
            // Bila ID PPPoE diubah: minta MikroTik mengganti nama secret juga
            if (window._editOldPppoe && window._editOldPppoe !== data.id_pppoe) {
                syncBody.newname = data.id_pppoe;
            }
            if (data.paket_profile) syncBody.profile = data.paket_profile;
            try {
                const syncRes = await fetch('api.php?action=mikrotik_sync_secret', { method: 'POST', body: JSON.stringify(syncBody) });
                const syncJson = await syncRes.json();
                if (syncJson.status === 'success') {
                    showToast('Data berhasil diupdate & disinkronkan ke MikroTik!');
                } else if (syncJson.message && syncJson.message.includes('tidak ditemukan')) {
                    showToast('Data berhasil diupdate (secret MikroTik tidak ada, sinkron dilewati).');
                } else {
                    alert('Data terupdate di database, TAPI gagal sinkron ke MikroTik: ' + syncJson.message);
                }
            } catch (e) {
                alert('Data terupdate di database, TAPI gagal hubungi MikroTik untuk sinkronisasi.');
            }
        } else {
            showToast(id ? 'Data berhasil diupdate!' : (json.updated ? 'Nama/ID PPPoE sudah ada — data diperbarui!' : 'Pelanggan baru berhasil ditambahkan!'));
        }
        closeEditModal();
        loadDataDB(); 
    } else { alert("Gagal simpan: " + json.message); }
  } catch (e) { alert("Error saat simpan ke API MySQL"); }
}

// Konfigurasi OLT & MikroTik Settings
async function loadOltSettings() {
    <?php if ($_SESSION['role'] !== 'admin') echo "return;"; ?>
    try {
        const res = await fetch('api.php?action=get_settings');
        const s = await res.json();
        document.getElementById('conf-olt-ip').value = s.olt_ip || '';
        document.getElementById('conf-olt-port').value = s.olt_port || '23';
        document.getElementById('conf-olt-user').value = s.olt_user || '';
        document.getElementById('conf-olt-lat').value = s.olt_latitude || '';
        document.getElementById('conf-olt-lng').value = s.olt_longitude || '';
    } catch(e) {}
}
async function saveOltSettings() {
    const data = {
        olt_ip: document.getElementById('conf-olt-ip').value.trim(),
        olt_port: document.getElementById('conf-olt-port').value.trim() || '23',
        olt_user: document.getElementById('conf-olt-user').value.trim(),
        olt_pass: document.getElementById('conf-olt-pass').value.trim(),
        olt_latitude: parseCoord(document.getElementById('conf-olt-lat').value) || '',
        olt_longitude: parseCoord(document.getElementById('conf-olt-lng').value) || ''
    };
    let res = await fetch('api.php?action=update_settings', { method: 'POST', body: JSON.stringify(data) });
    let json = await res.json();
    if (json.status === 'success') { showToast('Config OLT Disimpan!'); document.getElementById('conf-olt-pass').value = ''; }
}

async function loadMikrotikSettings() {
    <?php if ($_SESSION['role'] !== 'admin') echo "return;"; ?>
    try {
        const res = await fetch('api.php?action=get_settings');
        const s = await res.json();
        document.getElementById('conf-mk-ip').value = s.mikrotik_ip || '';
        document.getElementById('conf-mk-port').value = s.mikrotik_port || '8728';
        document.getElementById('conf-mk-user').value = s.mikrotik_user || '';
        const sslBox = document.getElementById('conf-mk-ssl');
        if (sslBox) sslBox.checked = (s.mikrotik_ssl === '1' || s.mikrotik_ssl === 'true');
        document.getElementById('conf-mk-lat').value = s.mikrotik_latitude || '';
        document.getElementById('conf-mk-lng').value = s.mikrotik_longitude || '';
        if (document.getElementById('conf-tg-token')) document.getElementById('conf-tg-token').value = s.telegram_bot_token || '';
        if (document.getElementById('conf-tg-chat')) document.getElementById('conf-tg-chat').value = s.telegram_chat_id || '';
    } catch(e) {}
}
async function saveMikrotikSettings() {
    const data = {
        mikrotik_ip: document.getElementById('conf-mk-ip').value.trim(),
        mikrotik_port: document.getElementById('conf-mk-port').value.trim() || '8728',
        mikrotik_user: document.getElementById('conf-mk-user').value.trim(),
        mikrotik_pass: document.getElementById('conf-mk-pass').value.trim(),
        mikrotik_ssl: document.getElementById('conf-mk-ssl') && document.getElementById('conf-mk-ssl').checked ? '1' : '0',
        mikrotik_latitude: parseCoord(document.getElementById('conf-mk-lat').value) || '',
        mikrotik_longitude: parseCoord(document.getElementById('conf-mk-lng').value) || ''
    };
    try {
        let res = await fetch('api.php?action=update_mk_settings', { method: 'POST', body: JSON.stringify(data) });
        let json = await res.json();
        if (json.status === 'success') { showToast('Config MikroTik Disimpan!'); document.getElementById('conf-mk-pass').value = ''; }
    } catch(e) { alert("Gagal simpan config mikrotik"); }
}

async function saveTelegramSettings() {
    const data = {
        telegram_bot_token: document.getElementById('conf-tg-token').value.trim(),
        telegram_chat_id: document.getElementById('conf-tg-chat').value.trim()
    };
    try {
        let res = await fetch('api.php?action=update_telegram', { method: 'POST', body: JSON.stringify(data) });
        let json = await res.json();
        showToast(json.status === 'success' ? 'Config Telegram Disimpan!' : 'Gagal: ' + json.message);
    } catch(e) { alert('Gagal simpan config telegram'); }
}

async function loadAudit() {
  try {
    const res = await fetch('api.php?action=get_audit');
    const j = await res.json();
    const tbody = document.getElementById('audit-tbody');
    if (!tbody) return;
    tbody.innerHTML = (j.data || []).length ? j.data.map(a => `
      <tr>
        <td style="color:var(--muted); font-family:monospace; white-space:nowrap;">${esc(a.waktu)}</td>
        <td style="font-family:monospace;">${esc(a.username)}</td>
        <td><span class="badge">${esc(a.action)}</span></td>
        <td style="font-family:monospace;">${esc(a.detail)}${a.ip ? ' <span style="color:#64748b">(' + esc(a.ip) + ')</span>' : ''}</td>
      </tr>`).join('') : '<tr><td colspan="4" style="color:var(--muted); text-align:center;">Belum ada aktivitas</td></tr>';
  } catch(e) {}
}

async function clearAudit() {
  if (!confirm('Yakin menghapus seluruh audit log?')) return;
  try {
    const res = await fetch('api.php?action=clear_audit', { method: 'POST' });
    const j = await res.json();
    showToast(j.status === 'success' ? 'Log dibersihkan' : 'Gagal');
    loadAudit();
  } catch(e) {}
}

async function importCustomers() {
  const text = document.getElementById('import-text').value.trim();
  if (!text) return alert('Tempel data terlebih dahulu.');
  const rows = [];
  text.split(/\r?\n/).forEach(line => {
    line = line.trim();
    if (!line) return;
    const p = line.split(';').map(x => x.trim());
    if (p.length < 2) return;
    rows.push({ client: p[0], paket: p[1], sn: p[2] || '-', id_pppoe: p[3] || '', port: p[4] || 'BELUM ADA', latitude: p[5] || '', longitude: p[6] || '' });
  });
  if (!rows.length) return alert('Format tidak dikenali.');
  if (!confirm('Import ' + rows.length + ' baris? Data dengan ID PPPoE duplikat akan dilewati.')) return;
  try {
    const res = await fetch('api.php?action=import_customers', { method: 'POST', body: JSON.stringify({ rows }) });
    const j = await res.json();
    showToast('Import selesai: ' + (j.sukses||0) + ' sukses, ' + (j.gagal||0) + ' gagal');
    document.getElementById('import-text').value = '';
    loadDataDB();
  } catch(e) { showToast('Error import'); }
}

// User Admin Management
async function loadUsersDB() {
  <?php if ($_SESSION['role'] !== 'admin') echo "return;"; ?>
  try {
    const res = await fetch('api.php?action=get_users');
    users_db = await res.json();
    renderUsers();
  } catch(e) {}
}

function renderUsers() {
  const tbody = document.getElementById('users-tbody');
  if(!tbody) return;
  const chip1 = document.getElementById('users-count-chip');
  const chip2 = document.getElementById('admin-count-chip');
  if (chip1) chip1.textContent = users_db.length + ' Akun';
  if (chip2) chip2.textContent = users_db.length;
  if (!users_db.length) {
    tbody.innerHTML = '<tr><td colspan="3" style="color:var(--muted); text-align:center; padding:18px;">Belum ada akun</td></tr>';
    return;
  }
  tbody.innerHTML = users_db.map(u => `
    <tr>
      <td style="font-weight:600; font-family:monospace;">${esc(u.username)}</td>
      <td>
        <span class="badge" style="${u.role === 'admin' ? 'color:#64D2FF; border-color:#0A84FF;' : 'color:#ffc15e; border-color:#FF9F0A; background:rgba(245,158,11,0.1)'}">
          ${esc(u.role).toUpperCase()}
        </span>
      </td>
      <td style="text-align:right;">
        <button class="btn btn-sm btn-amber" style="margin-right:4px;" onclick="openEditUserModal(${u.id})">Edit</button>
        ${u.username === '<?php echo $_SESSION['username']; ?>' ? 
          `<span style="color:var(--muted); font-size:11px;">(Akun Anda)</span>` : 
          `<button class="btn btn-sm btn-rose" onclick="deleteUserDB(${u.id}, '${jsStr(u.username)}')">Hapus</button>`}
      </td>
    </tr>
  `).join('');
}

async function saveUserDB() {
  const username = document.getElementById('admin-username').value.trim();
  const password = document.getElementById('admin-password').value.trim();
  const role = document.getElementById('admin-role').value;
  if(!username || !password) return alert("Username dan Password wajib diisi!");
  try {
    let res = await fetch('api.php?action=add_user', { method: 'POST', body: JSON.stringify({username, password, role}) });
    let json = await res.json();
    if (json.status === 'success') { showToast('User ditambahkan!'); document.getElementById('admin-username').value = ''; document.getElementById('admin-password').value = ''; loadUsersDB(); } 
    else { alert("Gagal"); }
  } catch (e) {}
}

function openEditUserModal(id) {
  const u = users_db.find(x => x.id == id);
  if(!u) return;
  document.getElementById('edit-user-id').value = u.id;
  document.getElementById('edit-user-username').value = u.username;
  document.getElementById('edit-user-password').value = ''; 
  document.getElementById('edit-user-role').value = u.role;
  document.getElementById('edit-user-modal').classList.remove('hidden');
}
function closeEditUserModal() { document.getElementById('edit-user-modal').classList.add('hidden'); }

async function saveEditUserDB() {
  const id = document.getElementById('edit-user-id').value;
  const username = document.getElementById('edit-user-username').value.trim();
  const password = document.getElementById('edit-user-password').value.trim();
  const role = document.getElementById('edit-user-role').value;
  try {
    let res = await fetch('api.php?action=update_user', { method: 'POST', body: JSON.stringify({id, username, password, role}) });
    let json = await res.json();
    if (json.status === 'success') { showToast('Akun diperbarui!'); closeEditUserModal(); loadUsersDB(); }
  } catch (e) {}
}

async function deleteUserDB(id, username) {
  if (confirm("Yakin ingin menghapus user '" + username + "'?")) {
    let res = await fetch('api.php?action=delete_user', { method: 'POST', body: JSON.stringify({id}) });
    let json = await res.json();
    if (json.status === 'success') { showToast('User dihapus!'); loadUsersDB(); }
  }
}

function loadCustomer(id) {
  const c = customers.find(x => x.id == id);
  if (!c) return;
  editingId = c.id;
  if (c.port && c.port.includes(':')) {
    const [iface, onu] = c.port.split(':');
    document.getElementById('bb-iface').value = iface;
    document.getElementById('bb-onu').value = onu;
  }
  document.getElementById('bb-nama').value = c.client;
  if (c.id_pppoe && c.id_pppoe !== '-') {
    const parts = c.id_pppoe.split('@');
    document.getElementById('bb-userid').value = parts[0];
    if (parts[1]) document.getElementById('bb-domain').value = '@' + parts[1];
  }
  document.getElementById('bb-paket').value = c.paket || 30;
  if (c.sn && c.sn !== '-') document.getElementById('bb-sn').value = c.sn;
  
  updateEditBadge();

  // Auto sinkron ke form MikroTik
  document.getElementById('mk-username').value = c.id_pppoe;
  document.getElementById('mk-comment').value = c.client;

  switchTab('broadband');
  generateBroadbandScript();
  showToast('Data dimuat & disinkronkan ke form MikroTik!');
}

function resetBB() {
  editingId = null;
  updateEditBadge();
  document.getElementById('bb-nama').value = '';
  document.getElementById('bb-sn').value = '';
  document.getElementById('bb-userid').value = '';
  checkConflictOnly();
}

function exportCSV() {
  const rows = ['NO,CLIENT,PAKET,SN ONT,ID,PORT OLT'].concat(
    customers.map((c, i) => `${i+1},"${c.client}",${c.paket_profile ? '"' + c.paket_profile + '"' : c.paket},${c.sn || '-'},${c.id_pppoe},${c.port}`)
  );
  const blob = new Blob([rows.join('\n')], { type: 'text/csv' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'DATA_PELANGGAN_SUPERNOVA.csv';
  a.click();
}

function copyText(id) { navigator.clipboard.writeText(document.getElementById(id).textContent).then(() => showToast('Disalin!')); }
function copyRaw(txt) { navigator.clipboard.writeText(txt).then(() => showToast('Arahan disalin!')); }
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 2200);
}

window.onload = function() {
  updateSortIcons();
  loadDataDB();
  generateBroadbandScript();

  const allowed = <?php echo $is_admin ? "['dashboard','broadband','mikrotik','pelanggan','monitoring','admin']" : "['dashboard','pelanggan','monitoring']"; ?>;
  let saved = null;
  try { saved = localStorage.getItem('supernova_active_tab'); } catch(e) {}
  const target = (saved && document.getElementById('view-' + saved) && allowed.indexOf(saved) !== -1) ? saved : '<?php echo $default_tab; ?>';
  switchTab(target);
};

async function loadMikrotikProfiles() {
    const select = document.getElementById('mk-profile');
    try {
        let res = await fetch('api.php?action=mikrotik_get_profiles');
        let json = await res.json();
        
        if (json.status === 'success' && json.data.length > 0) {
            select.innerHTML = json.data.map(p => `<option value="${p}">${p}</option>`).join('');
        } else {
            select.innerHTML = `<option value="default">default (Gagal ambil list)</option>`;
        }
    } catch(e) {
        select.innerHTML = `<option value="default">default (Error koneksi API)</option>`;
    }
}

// ================= DASHBOARD =================
async function loadDashboard() {
  try {
    const res = await fetch('api.php?action=get_stats');
    const j = await res.json();
    const s = (j.stats || {});
    const total = s.total || 0, aktif = s.aktif || 0, isolir = s.isolir || 0, cabut = s.cabut || 0;
    document.getElementById('dash-total').textContent = total;
    document.getElementById('dash-aktif').textContent = aktif;
    document.getElementById('dash-isolir').textContent = isolir;
    document.getElementById('dash-cabut').textContent = cabut;
    document.getElementById('dash-port').textContent = s.port_terisi || 0;
    document.getElementById('lbl-aktif').textContent = 'Aktif ' + aktif;
    document.getElementById('lbl-isolir').textContent = 'Isolir ' + isolir;
    document.getElementById('lbl-cabut').textContent = 'Cabut ' + cabut;
    const base = total || 1;
    document.getElementById('bar-aktif').style.width = Math.round(aktif / base * 100) + '%';
    document.getElementById('bar-isolir').style.width = Math.round(isolir / base * 100) + '%';
    document.getElementById('bar-cabut').style.width = Math.round(cabut / base * 100) + '%';
  } catch(e) {}
  loadDashboardRealtime();
  loadDashboardMap();
}

// ================= PETA =================
let dashMap = null, dashMarkers = [], dashPolys = [];

async function loadDashboardMap() {
  const el = document.getElementById('dash-map');
  if (!el) return;
  try {
    // Ambil data koordinat + status online/offline dari MikroTik secara paralel
    const [resMap, resMon] = await Promise.all([
      fetch('api.php?action=get_map'),
      fetch('api.php?action=mikrotik_monitor')
    ]);
    const j = await resMap.json();
    const mon = await resMon.json().catch(() => null);
    const items = (j.data || []).filter(x => x.latitude && x.longitude);
    const countEl = document.getElementById('dash-map-count');
    if (countEl) countEl.textContent = items.length + ' titik';

    // Set id_pppoe yang sedang online (monitor sukses), atau null jika router tidak terjangkau
    let onlineSet = null;
    if (mon && mon.status === 'success' && Array.isArray(mon.online)) {
      onlineSet = new Set(mon.online.filter(x => x.online).map(x => String(x.id_pppoe).trim().toLowerCase()));
    }

    if (!dashMap) {
      // Tampilan awal: pulau Gili Trawangan agak ke atas, zoom 14.5
      dashMap = L.map('dash-map', { zoomSnap: 0.5 }).setView([-8.348, 116.039723], 14.5);
      addBaseLayers(dashMap, 'Satelit');
    }
    dashMarkers.forEach(m => dashMap.removeLayer(m));
    dashMarkers = [];
    dashPolys.forEach(p => dashMap.removeLayer(p));
    dashPolys = [];
    const bounds = [];
    const custOnMap = [];
    items.forEach(c => {
      let color = '#FF453A';        // default offline (merah)
      let label = 'Offline';
      if (onlineSet) {
        if (onlineSet.has(String(c.id_pppoe).trim().toLowerCase())) { color = '#30D158'; label = 'Online'; }
      } else {
        // router tidak terjangkau: pakai status DB sebagai fallback
        color = c.status === 'Aktif' ? '#30D158' : c.status === 'Isolir' ? '#FF9F0A' : '#FF453A';
        label = c.status;
      }
      const icon = L.divIcon({
        className: '',
        html: `<div style="width:16px;height:16px;border-radius:50%;border:2px solid #fff;background:${color};box-shadow:0 2px 8px rgba(0,0,0,.5);"></div>`,
        iconSize: [16, 16], iconAnchor: [8, 8]
      });
      const m = L.marker([c.latitude, c.longitude], { icon }).addTo(dashMap);
      m.bindPopup(
        '<b>' + esc(c.client) + '</b><br>' +
        (c.paket_profile || c.paket + ' Mbps') + '<br>' +
        'ID: ' + esc(c.id_pppoe) + '<br>' +
        'Port: ' + esc(c.port) + '<br>' +
        (label === 'Online' ? '<span style="color:#63e6a5">🟢 Online</span>' : '<span style="color:#ff8d86">🔴 Offline</span>')
      );
      dashMarkers.push(m);
      bounds.push([c.latitude, c.longitude]);
      custOnMap.push({ lat: c.latitude, lng: c.longitude, color: color });
    });
    // Tampilan peta tetap pada posisi pulau — tidak auto-fit agar tidak terpotong/bergeser
    if (!bounds.length) dashMap.setView([-8.348, 116.039723], 14.5);

    // Penanda perangkat (OLT & MikroTik)
    let mkPos = null;
    (j.devices || []).forEach(d => {
      if (!d.latitude || !d.longitude) return;
      if (d.type === 'mikrotik') mkPos = [d.latitude, d.longitude];
      const isOlt = d.type === 'olt';
      const icon = L.divIcon({
        className: '',
        html: `<div style="width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #fff;background:${isOlt ? 'linear-gradient(180deg,#BF5AF2,#7f2fd0)' : 'linear-gradient(180deg,#0A84FF,#005fc7)'};box-shadow:0 3px 12px rgba(0,0,0,.6);color:#fff;font-size:14px;">${isOlt ? '<i class="fa-solid fa-server"></i>' : '<i class="fa-solid fa-network-wired"></i>'}</div>`,
        iconSize: [30, 30], iconAnchor: [15, 15]
      });
      const m = L.marker([d.latitude, d.longitude], { icon }).addTo(dashMap);
      m.bindPopup(
        '<div style="text-align:center;"><i class="fa-solid ' + (isOlt ? 'fa-server' : 'fa-network-wired') + '" style="font-size:18px;color:' + (isOlt ? '#d9a5f7' : '#64D2FF') + ';"></i><br><b style="color:#fff;">' + esc(d.name) + '</b><br><span style="color:#98989f;">' + (isOlt ? 'OLT' : 'Router MikroTik') + '</span></div>'
      );
      dashMarkers.push(m);
    });

    // Garis koneksi: tiap pelanggan -> Router MikroTik, warna sesuai status (hijau online / merah offline)
    if (mkPos) {
      custOnMap.forEach(cp => {
        const poly = L.polyline([[cp.lat, cp.lng], mkPos], {
          color: cp.color,
          weight: 2,
          opacity: 0.55,
          dashArray: '6 6'
        }).addTo(dashMap);
        dashPolys.push(poly);
      });
    }

    setTimeout(() => dashMap.invalidateSize(), 100);
  } catch(e) {}
}

function openDetailModal(id) {
  const c = customers.find(x => x.id == id) || (window.allCustomers ? window.allCustomers.find(x => x.id == id) : null);
  if (!c) return alert('Data pelanggan tidak ditemukan. Muat ulang halaman dulu.');
  const lat = parseFloat(c.latitude) || 0;
  const lng = parseFloat(c.longitude) || 0;
  const body = document.getElementById('detail-body');
  body.innerHTML = `
    <div class="form-grid" style="margin-bottom:14px;">
      <div class="col-span-2"><label>Nama Pelanggan</label><div style="font-size:16px; font-weight:800; color:#fff;">${esc(c.client)}</div></div>
      <div><label>Status</label><div>${statusBadge(c.status)}</div></div>
      <div><label>Paket</label><div style="color:#fff;">${esc(c.paket_profile || (c.paket + ' Mbps'))}</div></div>
      <div><label>ID PPPoE</label><div style="font-family:monospace; color:#63e6a5;">${esc(c.id_pppoe)}</div></div>
      <div><label>Port OLT</label><div style="font-family:monospace; color:#7dd4ff;">${esc(c.port)}</div></div>
      <div><label>SN ONT</label><div style="font-family:monospace; color:#ffc15e;">${esc(c.sn || '-')}</div></div>
      <div><label>Koordinat</label><div style="font-family:monospace;">${lat ? lat.toFixed(7) + ', ' + lng.toFixed(7) : '<span style="color:var(--muted)">Belum diisi</span>'}</div></div>
    </div>
    ${lat && lng ? '<div id="detail-map" style="height:280px; border-radius:12px; overflow:hidden; border:1px solid rgba(255,255,255,.1);"></div>' : '<div class="alert alert-warn" style="margin-bottom:0;">Pelanggan ini belum punya koordinat. Edit pelanggan lalu isi Latitude/Longitude atau gunakan tombol Pilih di peta.</div>'}
  `;
  document.getElementById('detail-modal').classList.remove('hidden');
  if (lat && lng) {
    requestAnimationFrame(() => {
      const dmap = L.map('detail-map').setView([lat, lng], 16);
      addBaseLayers(dmap, 'Satelit');
      L.marker([lat, lng]).addTo(dmap).bindPopup(esc(c.client)).openPopup();
      setTimeout(() => dmap.invalidateSize(), 150);
    });
  }
}
function closeDetailModal() { document.getElementById('detail-modal').classList.add('hidden'); }

async function loadDashboardRealtime() {
  if (monBusy) return;
  monBusy = true;
  const on = document.getElementById('dash-mon-online');
  const off = document.getElementById('dash-mon-offline');
  const ts = document.getElementById('dash-mon-ts');
  const er = document.getElementById('dash-mon-err');
  const btn = document.getElementById('dash-refresh-btn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memuat...'; }
  if (er) er.classList.add('hidden');
  if (!window.dashLoaded) {
    if (on) on.innerHTML = '<div class="skel" style="width:45%"></div>';
    if (off) off.innerHTML = '<div class="skel" style="width:45%"></div>';
    renderDashLists(true);
  }
  try {
    const res = await fetch('api.php?action=mikrotik_monitor');
    const j = await res.json();
    const list = j.online || [];
    window.dashMon = list;
    window.dashLoaded = true;
    const online = list.filter(x => x.online).length;
    if (on) on.textContent = online;
    if (off) off.textContent = list.length - online;
    if (ts) ts.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');
    if (er && j.error) { er.textContent = '⚠ ' + j.error; er.classList.remove('hidden'); }
    if (er && j.status !== 'success') { er.textContent = '⚠ ' + (j.message || 'Gagal'); er.classList.remove('hidden'); }
    else if (er) er.classList.add('hidden');
    renderDashLists(false);
  } catch(e) {
    if (ts && !window.dashLoaded) ts.textContent = 'Gagal ambil data';
  } finally {
    monBusy = false;
    if (btn) { btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Segarkan Status'; btn.disabled = false; }
  }
}

function renderDashLists(loading) {
  const onlineBox = document.getElementById('dash-online-list');
  const offlineBox = document.getElementById('dash-offline-list');
  if (!onlineBox || !offlineBox) return;
  if (loading && !window.dashLoaded) {
    onlineBox.innerHTML = '<div class="skel" style="margin:8px 10px;"></div>'.repeat(4);
    offlineBox.innerHTML = '<div class="skel" style="margin:8px 10px;"></div>'.repeat(4);
    return;
  }
  if (loading) return;
  const mk = (x) => `<div class="mon-item"><span style="font-weight:600;">${esc(x.client)}</span><span>${esc(String(x.id_pppoe || '').split('@')[0] || '-')}</span></div>`;
  const list = window.dashMon || [];
  const online = list.filter(x => x.online).sort((a, b) => (a.client || '').localeCompare(b.client || ''));
  const offline = list.filter(x => !x.online).sort((a, b) => (a.client || '').localeCompare(b.client || ''));
  onlineBox.innerHTML = online.length ? online.map(mk).join('') : '<div class="mon-empty">Tidak ada yang online</div>';
  offlineBox.innerHTML = offline.length ? offline.map(mk).join('') : '<div class="mon-empty">Tidak ada yang offline</div>';
}

// ================= MONITORING ONLINE/OFFLINE (MIKROTIK) =================
function monStatusBadge(online) {
  return online
    ? '<span class="badge" style="color:#63e6a5; border-color:#30D158; background:rgba(16,185,129,0.15);">Online</span>'
    : '<span class="badge" style="color:#ffc15e; border-color:#FF9F0A; background:rgba(245,158,11,0.1);">Offline</span>';
}

let lastMonDeep = 0;

async function loadMonitoring() {
  const onTb = document.getElementById('monitor-tbody-online');
  const offTb = document.getElementById('monitor-tbody-offline');
  const errBox = document.getElementById('mon-error');
  if (errBox) errBox.classList.add('hidden');
  // Tampilkan skeleton hanya saat belum ada data (refresh pertama), agar refresh 4dt seamless
  const hasData = (window.monData && window.monData.length > 0);
  if (!hasData) {
    const skRows = '<tr><td colspan="4" style="padding:14px 12px;">' + '<div class="skel" style="margin:6px 0;"></div>'.repeat(4) + '</td></tr>';
    const skRowsOff = '<tr><td colspan="3" style="padding:14px 12px;">' + '<div class="skel" style="margin:6px 0;"></div>'.repeat(4) + '</td></tr>';
    if (onTb) onTb.innerHTML = skRows;
    if (offTb) offTb.innerHTML = skRowsOff;
  }
  try {
    // Cepat: hanya sesi MikroTik (cache 10 dtk) -> respons instan
    let res = await fetch('api.php?action=mikrotik_monitor');
    let json = await res.json();

    if (errBox) {
      if (json.error) { errBox.classList.remove('hidden'); errBox.textContent = json.error; }
      else if (json.status !== 'success') { errBox.classList.remove('hidden'); errBox.textContent = json.message || 'Gagal memuat data monitoring.'; }
      else errBox.classList.add('hidden');
    }

    const list = json.online || [];
    const onlineCount = list.filter(x => x.online).length;
    document.getElementById('mon-total').textContent = list.length;
    document.getElementById('mon-online').textContent = onlineCount;
    document.getElementById('mon-offline').textContent = list.length - onlineCount;
    document.getElementById('mon-mk').textContent = json.online_total || 0;

    window.monData = list;
    filterMonitoring();

    // Deep check (verifikasi WAN IP di OLT) di latar belakang — maksimal sekali per ~90 detik,
    // agar auto-refresh 4 detik tetap ringan.
    if (Date.now() - lastMonDeep > 85000) {
      lastMonDeep = Date.now();
      fetch('api.php?action=mikrotik_monitor&deep=1')
        .then(r => r.json())
        .then(dj => {
          if (dj && dj.status === 'success' && Array.isArray(dj.online)) {
            const dlist = dj.online;
            document.getElementById('mon-total').textContent = dlist.length;
            document.getElementById('mon-online').textContent = dlist.filter(x => x.online).length;
            document.getElementById('mon-offline').textContent = dlist.length - dlist.filter(x => x.online).length;
            document.getElementById('mon-mk').textContent = dj.online_total || 0;
            window.monData = dlist;
            filterMonitoring();
            if (Array.isArray(window.dashMon)) window.dashMon = dlist;
            renderDashLists();
          }
        })
        .catch(() => {});
    }
  } catch (e) {
    if (onTb) onTb.innerHTML = '<tr><td colspan="4" style="color:#ff8d86">Error memuat data monitoring.</td></tr>';
    if (offTb) offTb.innerHTML = '<tr><td colspan="3" style="color:#ff8d86">Error memuat data monitoring.</td></tr>';
  }
}

function parseUptime(u) {
  u = String(u || '');
  let d = 0, h = 0, mi = 0, s = 0, m;
  if ((m = u.match(/(\d+)d/))) d = +m[1];
  if ((m = u.match(/(\d+)h/))) h = +m[1];
  if ((m = u.match(/(\d+)m/))) mi = +m[1];
  if ((m = u.match(/(\d+)s/))) s = +m[1];
  return ((d * 24 + h) * 60 + mi) * 60 + s;
}

function monSortKey(c) {
  switch (monSortCol) {
    case 'numid':     return Number(String(c.id_pppoe || '').split('@')[0] || 0);
    case 'status':    return c.online ? 1 : 0;
    case 'client':    return (c.client || '').toLowerCase();
    case 'id':        return (c.id_pppoe || '').toLowerCase();
    case 'port':      return (c.port || '').toLowerCase();
    case 'address':   return (c.address || '').toLowerCase();
    case 'caller_id': return (c.caller_id || '').toLowerCase();
    case 'uptime':    return parseUptime(c.uptime);
    default:          return (c.client || '').toLowerCase();
  }
}

function renderMonitoring(list) {
  if (!list) list = window.monData || [];
  const onlineBox = document.getElementById('monitor-tbody-online');
  const offlineBox = document.getElementById('monitor-tbody-offline');
  if (!onlineBox || !offlineBox) return;

  document.getElementById('mon-online-count').textContent = list.filter(x => x.online).length;
  document.getElementById('mon-offline-count').textContent = list.filter(x => !x.online).length;

  const cmp = (a, b) => {
    const va = monSortKey(a.c);
    const vb = monSortKey(b.c);
    if (typeof va === 'number' && typeof vb === 'number') return monSortAsc ? va - vb : vb - va;
    return monSortAsc ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va));
  };

  const mk = (c, i, isOnline) => {
    const name = `<td style="font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${esc(c.client)}</td>`;
    const uptime = `<td style="font-family:monospace; white-space:nowrap;">${esc(c.uptime || '-')}</td>`;
    const no = `<td style="font-family:monospace; color:var(--muted); white-space:nowrap;">${i + 1}</td>`;
    if (isOnline) {
      // Kecepatan traffic SAAT INI dari queue MikroTik (bps) -> tampilkan sebagai rate, sps. interface detail
      const fmtRate = (bps) => {
        if (bps >= 1000000) return (bps/1000000).toFixed(2)+' Mbps';
        if (bps >= 1000) return (bps/1000).toFixed(1)+' Kbps';
        return bps+' bps';
      };
      const tx = c.tx_rate || 0, rx = c.rx_rate || 0;
      const rxCls = rx > 0 ? '#34d399' : '#94a3b8';
      const txCls = tx > 0 ? '#38bdf8' : '#94a3b8';
      return `<tr>
        ${no}
        ${name}
        ${uptime}
        <td style="white-space:nowrap; font-size:12px;">
          <div style="color:${txCls};"><b>▲ TX:</b> ${fmtRate(tx)}</div>
          <div style="color:${rxCls};"><b>▼ RX:</b> ${fmtRate(rx)}</div>
        </td>
      </tr>`;
    }
    return `<tr>${no}${name}${uptime}</tr>`;
  };
  const emptyRow = (txt, cols) => '<tr><td colspan="' + cols + '" style="text-align:center; color:var(--muted); padding:24px;">' + txt + '</td></tr>';

  const onlineRows = list.filter(x => x.online).map(c => ({ c })).sort(cmp);
  const offlineRows = list.filter(x => !x.online).map(c => ({ c })).sort(cmp);

  onlineBox.innerHTML = onlineRows.length ? onlineRows.map((r, i) => mk(r.c, i, true)).join('') : emptyRow('Tidak ada pelanggan online', 4);
  offlineBox.innerHTML = offlineRows.length ? offlineRows.map((r, i) => mk(r.c, i, false)).join('') : emptyRow('Tidak ada pelanggan offline', 3);
}

function filterMonitoring() {
  const q = (document.getElementById('mon-search')?.value || '').trim().toLowerCase();
  const all = window.monData || [];
  const filtered = q ? all.filter(c =>
    String(c.client || '').toLowerCase().includes(q) ||
    String(c.id_pppoe || '').toLowerCase().includes(q) ||
    String(c.address || '').toLowerCase().includes(q) ||
    String(c.port || '').toLowerCase().includes(q)
  ) : all;
  renderMonitoring(filtered);
}

function sortMonitoring(col) {
  if (monSortCol === col) monSortAsc = !monSortAsc;
  else { monSortCol = col; monSortAsc = true; }
  filterMonitoring();
}

function exportMonitoringCSV() {
  const rows = document.querySelectorAll('#monitor-tbody-online tr, #monitor-tbody-offline tr');
  const lines = ['NO,NAMA,UPTIME,TX,RX'];
  rows.forEach((tr, i) => {
    const cells = Array.from(tr.querySelectorAll('td')).map(td => '"' + (td.textContent || '').trim().replace(/"/g, '""') + '"');
    lines.push((i + 1) + ',' + cells.join(','));
  });
  const blob = new Blob([lines.join('\n')], { type: 'text/csv' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'MONITORING_PPPOE.csv';
  a.click();
}

// Auto-refresh Monitoring tiap 4 detik agar traffic (rate TX/RX) terlihat seamless
setInterval(() => {
  const mv = document.getElementById('view-monitoring');
  if (mv && !mv.classList.contains('hidden')) loadMonitoring();
}, 4000);

setInterval(() => {
  const dv = document.getElementById('view-dashboard');
  if (dv && !dv.classList.contains('hidden')) loadDashboardRealtime();
}, 30000);
</script>
</body>
</html>
