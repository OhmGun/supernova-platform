<?php
require 'koneksi.php';
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION['role'] ?? 'readonly';
$is_admin = ($role === 'admin');
$default_tab = $is_admin ? 'broadband' : 'pelanggan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Supernova OLT & MikroTik Provisioning</title>
<style>
  :root {
    --bg: #020617; --card: #0f172a; --border: #1e293b; --input: #1e293b;
    --text: #f8fafc; --muted: #94a3b8; --primary: #4f46e5; --primary-hover: #4338ca;
    --emerald: #10b981; --amber: #f59e0b; --rose: #f43f5e; --cyan: #06b6d4;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.5; padding-bottom: 40px; }
  header { background: var(--card); border-bottom: 1px solid var(--border); padding: 12px 20px; position: sticky; top: 0; z-index: 20; }
  .header-inner { max-width: 1240px; margin: 0 auto; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; }
  .brand { display: flex; align-items: center; gap: 12px; }
  .logo { width: 38px; height: 38px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-family: monospace; font-size: 13px; }
  .badge { font-size: 11px; background: rgba(79, 70, 229, 0.2); color: #a5b4fc; border: 1px solid rgba(79, 70, 229, 0.4); padding: 2px 8px; border-radius: 99px; font-family: monospace; text-transform:uppercase; }
  .tabs { display: flex; gap: 4px; background: var(--input); padding: 4px; border-radius: 8px; border: 1px solid #334155; }
  .tab-btn { background: transparent; color: var(--muted); border: none; padding: 7px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
  .tab-btn.active { background: var(--primary); color: #fff; }
  .tab-btn:hover:not(.active) { background: #334155; color: #fff; }
  .container { max-width: 1240px; margin: 20px auto; padding: 0 16px; }
  .grid-main { display: grid; grid-template-columns: 5fr 7fr; gap: 20px; }
  @media (max-width: 900px) { .grid-main { grid-template-columns: 1fr; } }
  .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 18px; margin-bottom: 16px; }
  .card-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; margin-bottom: 14px; border-bottom: 1px solid var(--border); }
  .card-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #818cf8; }
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .col-span-2 { grid-column: span 2; }
  label { display: block; font-size: 11px; color: var(--muted); margin-bottom: 4px; font-weight:600; }
  input, select { width: 100%; background: var(--input); border: 1px solid #334155; color: #fff; padding: 8px 10px; border-radius: 6px; font-size: 13px; font-family: monospace; }
  input:focus, select:focus { outline: none; border-color: var(--primary); }
  .btn { background: var(--primary); color: #fff; border: none; padding: 8px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
  .btn:hover { background: var(--primary-hover); }
  .btn-generate { background: #059669; color: #fff; width: 100%; padding: 12px; font-size: 14px; font-weight: 700; margin-top: 8px; border-radius: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3); }
  .btn-generate:hover { background: #047857; }
  .btn-sm { padding: 5px 10px; font-size: 12px; }
  
  .btn-emerald { background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }
  .btn-emerald:hover { background: rgba(16, 185, 129, 0.3); }
  .btn-amber { background: rgba(245, 158, 11, 0.15); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.3); }
  .btn-amber:hover { background: rgba(245, 158, 11, 0.3); }
  .btn-rose { background: rgba(244, 63, 94, 0.15); color: #fda4af; border: 1px solid rgba(244, 63, 94, 0.3); }
  .btn-rose:hover { background: rgba(244, 63, 94, 0.3); }
  .btn-outline-primary { background: rgba(79, 70, 229, 0.15); color: #a5b4fc; border: 1px solid rgba(79, 70, 229, 0.4); }
  .btn-outline-primary:hover { background: rgba(79, 70, 229, 0.3); }
  .btn-muted { background: #1e293b; color: #cbd5e1; border: 1px solid #334155; }
  
  pre { background: #020617; border: 1px solid var(--border); padding: 12px; border-radius: 8px; font-family: monospace; font-size: 11.5px; overflow-x: auto; white-space: pre; line-height: 1.5; max-height: 300px; }
  .alert { padding: 8px 12px; border-radius: 6px; font-size: 11.5px; font-family: monospace; }
  .alert-warn { background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35); color: #fcd34d; }
  .alert-ok { background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.35); color: #6ee7b7; }
  .quick-row { display: flex; justify-content: space-between; align-items: center; background: #020617; border: 1px solid var(--border); padding: 6px 10px; border-radius: 6px; margin-bottom: 6px; }
  .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px; }
  .stat-val { font-size: 22px; font-weight: 700; margin-top: 4px; }
  table { width: 100%; border-collapse: collapse; font-size: 12px; }
  th, td { padding: 9px 12px; text-align: left; border-bottom: 1px solid var(--border); }
  th { background: #1e293b; color: var(--muted); text-transform: uppercase; font-size: 11px; position: sticky; top: 0; cursor: pointer; user-select: none; }
  th:hover { color: #fff; background-color: #334155; }
  th::after { content: ' \2195'; font-size: 10px; opacity: 0.5; margin-left: 4px; }
  th.asc::after { content: ' \2191'; opacity: 1; color: #6ee7b7; }
  th.desc::after { content: ' \2193'; opacity: 1; color: #fda4af; }
  tr:hover { background: rgba(30, 41, 59, 0.5); }
  .hidden { display: none !important; }
  #toast { position: fixed; bottom: 20px; right: 20px; background: var(--primary); color: #fff; padding: 10px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; box-shadow: 0 10px 25px rgba(0,0,0,0.5); z-index: 99; transition: opacity 0.3s; }
  
  .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(2px); display: flex; justify-content: center; align-items: center; z-index: 100; }
  .modal-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 24px; width: 100%; max-width: 420px; box-shadow: 0 20px 40px rgba(0,0,0,0.6); }
  .modal-card-lg { max-width: 760px; }
  .term {
    background: #04110c; border: 1px solid #166534; border-radius: 10px;
    color: #86efac; font-family: Consolas, Menlo, monospace; font-size: 12.5px;
    line-height: 1.65; padding: 14px 16px; margin: 0;
    max-height: 440px; overflow: auto; white-space: pre; min-height: 60px;
    box-shadow: inset 0 0 30px rgba(16, 185, 129, 0.06);
  }
  .term::-webkit-scrollbar { width: 10px; height: 10px; }
  .term::-webkit-scrollbar-track { background: #071412; }
  .term::-webkit-scrollbar-thumb { background: #14532d; border-radius: 6px; }
  .term-loading::after { content: ''; display:inline-block; width:8px; height:14px; margin-left:4px; background:#86efac; animation: blink 1s steps(1) infinite; vertical-align:-2px; }
  @keyframes blink { 50% { opacity: 0; } }
  .term-title-pill {
    font-size: 11px; font-family: monospace; background: rgba(6, 182, 212, 0.15);
    color: #67e8f9; border: 1px solid rgba(6, 182, 212, 0.35); padding: 2px 10px;
    border-radius: 99px; letter-spacing: 0.5px;
  }

  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'readonly'): ?>
  <?php if (!$is_admin): ?>  .admin-only { display: none !important; }<?php endif; ?>
  <?php endif; ?>
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header>
  <div class="header-inner">
    <div class="brand">
      <div class="logo">OLT</div>
      <div>
        <div style="font-weight:700; font-size:16px;">
          SUPERNOVA <span class="badge">ZTE C320</span>
        </div>
        <div style="font-size:11px; color:var(--muted);">
          Logged in as: <b style="color:#6ee7b7"><?php echo $_SESSION['username']; ?></b> 
          (<span style="color:#fcd34d"><?php echo strtoupper($_SESSION['role']); ?></span>)
        </div>
      </div>
    </div>
    <div class="tabs">
      <?php if ($is_admin): ?>
      <button id="tab-broadband" class="tab-btn active" onclick="switchTab('broadband')">Broadband</button>
      <button id="tab-mikrotik" class="tab-btn" onclick="switchTab('mikrotik')"><i class="fa-solid fa-network-wired"></i> MikroTik PPPoE</button>
      <?php endif; ?>
      <button id="tab-pelanggan" class="tab-btn<?php echo $default_tab === 'pelanggan' ? ' active' : ''; ?>" onclick="switchTab('pelanggan')">Pelanggan (<span id="badge-count">0</span>)</button>
      <button id="tab-monitoring" class="tab-btn" onclick="switchTab('monitoring')"><i class="fa-solid fa-signal"></i> Monitoring</button>
      
      <?php if ($_SESSION['role'] === 'admin'): ?>
      <button id="tab-admin" class="tab-btn" onclick="switchTab('admin')"><i class="fa-solid fa-users"></i> Kelola Admin</button>
      <?php endif; ?>
      
      <a href="logout.php" style="text-decoration: none;">
        <button class="tab-btn" style="color: #fda4af; border-left: 1px solid #334155; border-radius: 0 6px 6px 0; margin-left: 4px;">
          <i class="fa-solid fa-power-off"></i> Keluar
        </button>
      </a>
    </div>
  </div>
</header>

<div class="container">
  
  <!-- TAB 1: BROADBAND -->
  <div id="view-broadband" class="grid-main<?php echo $default_tab === 'broadband' ? '' : ' hidden'; ?>">
    <div>
      <div class="card">
        <div class="card-header">
          <span class="card-title">Parameter Aktivasi Broadband</span>
          <button class="btn btn-sm btn-emerald admin-only" onclick="saveToDB()">+ Simpan ke MySQL</button>
        </div>
        <div class="form-grid">
          <div><label>Interface GPON-OLT</label><input type="text" id="bb-iface" value="1/2/4" oninput="checkConflictOnly()"></div>
          <div><label>ONU ID</label><input type="number" id="bb-onu" value="10" oninput="checkConflictOnly()"></div>
          <div id="bb-port-alert" class="col-span-2 alert"></div>
          <div><label>SN ONT</label><input type="text" id="bb-sn" value="ZTEGD6D940BB" style="color:#fcd34d"></div>
          <div><label>Tipe ONT</label><input type="text" id="bb-type" value="ALL-ONT"></div>
          <div class="col-span-2"><label>Nama Pelanggan (Description)</label><input type="text" id="bb-nama" value="LITTLE LODGE"></div>
          <div><label>User ID PPPoE</label><input type="text" id="bb-userid" value="121251720115" style="color:#6ee7b7"></div>
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
    </div>
    
    <div>
      <div class="card">
        <div class="card-header"><span class="card-title" style="color:#a78bfa">1. CEK ONU UNCONFIG (Cari SN Baru)</span><button class="btn btn-sm" onclick="copyText('out-bb-unconfig')">Copy Script</button></div>
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
        <pre id="out-bb-regis" style="color:#6ee7b7">Klik GENERATE di kiri...</pre>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title" style="color:#a5b4fc">5. CONFIG LAYANAN PPPoE</span><button class="btn btn-sm admin-only" onclick="copyText('out-bb-service')">Copy Script</button></div>
        <pre id="out-bb-service" style="color:#c7d2fe">Klik GENERATE di kiri...</pre>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title" style="color:#fbbf24">6. RESTART / REBOOT ONT</span><button class="btn btn-sm btn-muted admin-only" onclick="copyText('out-bb-reboot')">Copy Script</button></div>
        <pre id="out-bb-reboot" style="color:#fde68a; min-height:40px;">Klik GENERATE di kiri...</pre>
      </div>
    </div>
  </div>

  <!-- TAB BARU: MIKROTIK PPPOE PROVISIONING -->
  <div id="view-mikrotik" class="hidden">
    <div style="max-width: 600px; margin: 0 auto;">
      <div class="card" style="border-left: 4px solid #10b981;">
        <div class="card-header">
          <span class="card-title" style="color:#10b981;"><i class="fa-solid fa-network-wired"></i> Tambah Secret PPPoE ke MikroTik (API)</span>
        </div>
        <div class="form-grid">
          <div class="col-span-2">
            <label>Username PPPoE (User ID + Domain)</label>
            <input type="text" id="mk-username" placeholder="Contoh: 1251720115@supernovanet" autocomplete="off">
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
      <div class="card" style="margin:0"><div style="font-size:11px; color:var(--muted)">Rata-Rata Paket</div><div id="stat-avg" class="stat-val" style="color:#818cf8">0 Mbps</div></div>
    </div>

    <div class="card" style="display:flex; flex-wrap:wrap; gap:10px; justify-content:space-between; align-items:center;">
      <div style="display:flex; gap:10px; flex-grow:1; max-width:620px; flex-wrap:wrap;">
        <input type="text" id="search-pelanggan" placeholder="Cari nama pelanggan, SN ONT, ID PPPoE, atau port..." onkeydown="if(event.key==='Enter') doSearch()" style="flex:1; min-width:200px;">
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
     <div id="olt-terminal-modal" class="modal-overlay hidden">
    <div class="modal-card modal-card-lg">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">

            <h3 style="margin:0; color:#38bdf8; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <i class="fa-solid fa-terminal"></i>
                <span id="olt-terminal-title">Terminal OLT Live</span>
            </h3>

            <div style="display:flex; gap:8px;">

                <button
                    class="btn btn-sm btn-outline-primary"
                    onclick="copyTerminal()"
                    title="Salin hasil"
                >
                    <i class="fa-solid fa-copy"></i> Salin
                </button>

                <button
                    id="btn-export-offline"
                    class="btn btn-sm btn-emerald"
                    onclick="exportOfflineTXT(window.lastOfflineReport || '')"
                    style="display:none;"
                >
                    Export TXT
                </button>

                <button
                    class="btn btn-sm btn-rose"
                    onclick="closeTerminalModal()"
                >
                    Tutup
                </button>

            </div>

        </div>

        <pre id="olt-terminal-output" class="term">Menunggu koneksi OLT...</pre>

    </div>
</div>

<div id="history-modal" class="modal-overlay hidden">
  <div class="modal-card modal-card-lg">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <h3 style="margin:0; color:#38bdf8; display:flex; align-items:center; gap:10px;">
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
      <div>
        <table>
          <thead>
            <tr>
              <th onclick="sortTable('id')" id="th-id">No. ID</th>
              <th onclick="sortTable('client')" id="th-client">Nama Pelanggan</th>
              <th onclick="sortTable('paket')" id="th-paket">Paket</th>
              <th onclick="sortTable('sn')" id="th-sn">SN ONT</th>
              <th onclick="sortTable('id_pppoe')" id="th-id_pppoe">ID PPPoE</th>
              <th onclick="sortTable('port')" id="th-port">Port OLT</th>
              <th onclick="sortTable('status')" id="th-status">Status</th>
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
      <div style="display:flex; gap:8px;">
        <button class="btn btn-amber" onclick="loadMonitoring()"><i class="fa-solid fa-rotate"></i> Segarkan</button>
        <button class="btn btn-emerald" onclick="exportMonitoringCSV()"><i class="fa-solid fa-download"></i> Export CSV</button>
      </div>
    </div>

    <div id="mon-error" class="alert alert-warn hidden" style="margin-bottom:12px;"></div>

    <div class="card" style="padding:0; overflow:hidden;">
      <div>
        <table>
          <thead>
            <tr>
              <th>No. ID</th>
              <th>Status</th>
              <th>Nama Pelanggan</th>
              <th>ID PPPoE</th>
              <th>Port OLT</th>
              <th>Alamat IP</th>
              <th>Remote ID</th>
              <th>Uptime</th>
            </tr>
          </thead>
          <tbody id="monitor-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB 4: KELOLA ADMIN -->
  <?php if ($_SESSION['role'] === 'admin'): ?>
  <div id="view-admin" class="grid-main hidden">
    <div>
      <!-- Konfigurasi OLT -->
      <div class="card" style="border-left: 4px solid #06b6d4; margin-bottom:12px;">
        <div class="card-header"><span class="card-title" style="color:#06b6d4;"><i class="fa-solid fa-server"></i> Koneksi OLT (Telnet)</span></div>
        <div class="form-grid">
          <div><label>IP Address OLT</label><input type="text" id="conf-olt-ip"></div>
          <div><label>Port</label><input type="text" id="conf-olt-port"></div>
          <div><label>Username OLT</label><input type="text" id="conf-olt-user"></div>
          <div><label>Password OLT</label><input type="password" id="conf-olt-pass" placeholder="***"></div>
          <div class="col-span-2"><button class="btn btn-outline-primary" style="width:100%; color:#06b6d4; border-color:#06b6d4;" onclick="saveOltSettings()">Simpan Config OLT</button></div>
        </div>
      </div>

      <!-- Konfigurasi MikroTik API (BARU) -->
      <div class="card" style="border-left: 4px solid #f59e0b; margin-bottom:12px;">
        <div class="card-header"><span class="card-title" style="color:#f59e0b;"><i class="fa-solid fa-network-wired"></i> Koneksi MikroTik (API)</span></div>
        <div class="form-grid">
          <div><label>IP Address MikroTik</label><input type="text" id="conf-mk-ip" placeholder="192.168.88.1"></div>
          <div><label>Port API (Default: 8728)</label><input type="text" id="conf-mk-port" placeholder="8728"></div>
          <div><label>Username MikroTik</label><input type="text" id="conf-mk-user" placeholder="admin"></div>
          <div><label>Password MikroTik</label><input type="password" id="conf-mk-pass" placeholder="***"></div>
          <div class="col-span-2">
            <label style="display:flex; align-items:center; gap:8px; margin:0; cursor:pointer;">
              <input type="checkbox" id="conf-mk-ssl" style="width:auto; cursor:pointer;">
              Gunakan API SSL (centang jika API memakai TLS / port 8729)
            </label>
          </div>
          <div class="col-span-2"><button class="btn btn-amber" style="width:100%; color:#000;" onclick="saveMikrotikSettings()">Simpan Config MikroTik API</button></div>
        </div>
      </div>

      <!-- Notifikasi Telegram -->
      <div class="card" style="border-left: 4px solid #38bdf8; margin-bottom:12px;">
        <div class="card-header"><span class="card-title" style="color:#38bdf8;"><i class="fa-solid fa-paper-plane"></i> Notifikasi Telegram</span></div>
        <div class="form-grid">
          <div class="col-span-2"><label>Bot Token</label><input type="password" id="conf-tg-token" placeholder="123456:ABC-xyz..." autocomplete="off"></div>
          <div class="col-span-2"><label>Chat ID</label><input type="text" id="conf-tg-chat" placeholder="-1001234567890" autocomplete="off"></div>
          <div class="col-span-2"><button class="btn btn-outline-primary" style="width:100%; color:#38bdf8; border-color:#38bdf8;" onclick="saveTelegramSettings()">Simpan Config Telegram</button></div>
        </div>
      </div>

      <!-- Tambah User Admin -->
      <div class="card" style="border-left: 4px solid #10b981;">
        <div class="card-header"><span class="card-title" style="color:#10b981;"><i class="fa-solid fa-user-plus"></i> Tambah User Aplikasi</span></div>
        <div class="form-grid">
          <div class="col-span-2"><label>Username</label><input type="text" id="admin-username" placeholder="Username baru..." autocomplete="off"></div>
          <div class="col-span-2"><label>Password</label><input type="password" id="admin-password" placeholder="Password..." autocomplete="off"></div>
          <div class="col-span-2">
            <label>Role Akses</label>
            <select id="admin-role">
              <option value="admin">Admin (Full Akses)</option>
              <option value="readonly" selected>Read-Only (Hanya Lihat)</option>
            </select>
          </div>
          <div class="col-span-2"><button class="btn btn-emerald" style="width:100%; padding:10px;" onclick="saveUserDB()">Tambah Akun</button></div>
        </div>
      </div>

      <!-- Operasional Data -->
      <div class="card" style="border-left: 4px solid #a78bfa; margin-bottom:12px;">
        <div class="card-header"><span class="card-title" style="color:#a78bfa;"><i class="fa-solid fa-database"></i> Operasional Data</span></div>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
          <button class="btn btn-sm btn-emerald" onclick="window.open('api.php?action=backup_db','_blank')"><i class="fa-solid fa-download"></i> Backup Database</button>
          <button class="btn btn-sm btn-rose" onclick="clearAudit()"><i class="fa-solid fa-trash"></i> Bersihkan Log</button>
        </div>
        <div style="margin-top:10px;">
          <label>Import Pelanggan (format per baris: nama;pakeet;sn;idpppoe;port)</label>
          <textarea id="import-text" rows="4" style="font-family:monospace; font-size:12px;" placeholder="Contoh:&#10;MAWAR SEJATI;50;ZTEGD6D940BB;121251720200@supernovanet;1/2/7:18"></textarea>
          <button class="btn btn-sm btn-amber" style="width:100%; color:#000;" onclick="importCustomers()"><i class="fa-solid fa-file-import"></i> Import Data</button>
        </div>
      </div>
    </div>
    
    <div>
      <div class="card" style="padding:0; overflow:hidden; margin-bottom:14px;">
        <div style="max-height:600px; overflow-y:auto;">
          <table>
            <thead><tr><th>Username</th><th>Role Akses</th><th style="text-align:right">Aksi</th></tr></thead>
            <tbody id="users-tbody"></tbody>
          </table>
        </div>
      </div>

      <!-- Audit Log -->
      <div class="card" style="padding:0; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; border-bottom:1px solid var(--border);">
          <span class="card-title" style="margin:0; color:#6ee7b7;"><i class="fa-solid fa-book"></i> Audit Log</span>
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
  </div>
  <?php endif; ?>

</div>

<!-- MODAL EDIT DATA PELANGGAN -->
<div id="edit-modal" class="modal-overlay hidden">
  <div class="modal-card">
    <h3 style="margin-top:0; margin-bottom:18px; color:#818cf8;">Edit Data Pelanggan</h3>
    <input type="hidden" id="edit-id">
    <div style="margin-bottom:12px;"><label>Nama Pelanggan</label><input type="text" id="edit-nama"></div>
    <div style="margin-bottom:12px;"><label>Paket (Mbps)</label><input type="number" id="edit-paket"></div>
    <div style="margin-bottom:12px;"><label>SN ONT</label><input type="text" id="edit-sn" style="color:#fcd34d"></div>
    <div style="margin-bottom:12px;"><label>ID PPPoE</label><input type="text" id="edit-id-pppoe" style="color:#6ee7b7"></div>
    <div style="margin-bottom:12px;"><label>Port OLT</label><input type="text" id="edit-port"></div>
    <div style="margin-bottom:20px;">
      <label>Status</label>
      <select id="edit-status">
        <option value="Aktif">Aktif</option>
        <option value="Isolir">Isolir</option>
        <option value="Cabut">Cabut</option>
      </select>
    </div>
    <div style="display:flex; justify-content:flex-end; gap:10px;">
      <button class="btn btn-muted" onclick="closeEditModal()">Batal</button>
      <button class="btn btn-emerald admin-only" onclick="saveEditCustomerDB()">Simpan Perubahan</button>
    </div>
  </div>
</div>

<!-- MODAL EDIT AKUN ADMIN -->
<div id="edit-user-modal" class="modal-overlay hidden">
  <div class="modal-card">
    <h3 style="margin-top:0; margin-bottom:18px; color:#fcd34d;"><i class="fa-solid fa-user-pen"></i> Edit Akun Admin</h3>
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
  ['broadband', 'mikrotik', 'pelanggan', 'monitoring', 'admin'].forEach(t => {
    const view = document.getElementById('view-' + t);
    const btn = document.getElementById('tab-' + t);
    if(view) view.classList.add('hidden');
    if(btn) btn.classList.remove('active');
  });
  
  const tView = document.getElementById('view-' + tab);
  const tBtn = document.getElementById('tab-' + tab);
  if(tView) tView.classList.remove('hidden');
  if(tBtn) tBtn.classList.add('active');
  
  if (tab === 'pelanggan') renderCustomers();
  if (tab === 'mikrotik') loadMikrotikProfiles();
  if (tab === 'monitoring') loadMonitoring();
  if (tab === 'admin') { loadUsersDB(); loadOltSettings(); loadMikrotikSettings(); loadAudit(); }

  try { localStorage.setItem('supernova_active_tab', tab); } catch(e) {}
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

function statusBadge(st) {
  const map = {
    'Aktif':  ['Aktif', '#34d399', '#10b981', 'rgba(16,185,129,0.15)'],
    'Isolir': ['Isolir', '#fcd34d', '#f59e0b', 'rgba(245,158,11,0.12)'],
    'Cabut':  ['Cabut', '#fda4af', '#f43f5e', 'rgba(244,63,94,0.12)']
  };
  const m = map[st] || [st, '#94a3b8', '#64748b', 'rgba(100,116,139,0.15)'];
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
  const pass = document.getElementById('bb-pass').value.trim();
  const vlan = document.getElementById('bb-vlan').value.trim();
  const profDown = document.getElementById('bb-prof-down').value.trim();
  const vlanProf = document.getElementById('bb-vlan-prof').value.trim();

  checkConflictOnly();

  document.getElementById('out-bb-state').textContent = `show gpon onu state gpon-olt_${iface}`;
  document.getElementById('out-bb-delete').textContent = `configure t\ninterface gpon-olt_${iface}\nno onu ${onu}\nexit`;
  document.getElementById('out-bb-regis').textContent = `config t\ninterface gpon-olt_${iface}\nonu ${onu} type ${type} sn ${sn}\nexit`;
  
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

  document.getElementById('out-bb-reboot').textContent = `config t\npon-onu-mng gpon-onu_${iface}:${onu}\nreboot\nexit\nexit`;
  
  // Auto isi form MikroTik Tab sebelah
  document.getElementById('mk-username').value = userid + domain;
  document.getElementById('mk-password').value = pass;
  document.getElementById('mk-comment').value = nama;
  
  showToast('Script Broadband di-generate & disinkronkan ke form MikroTik!');
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
      return `
      <tr>
        <td style="font-family:monospace; color:var(--muted)">${esc(String(c.id_pppoe || '').split('@')[0] || '-')}</td>
        <td style="font-weight:600">${esc(c.client)}</td>
        <td style="font-family:monospace; color:#a5b4fc">${esc(c.paket)} Mbps</td>
        <td style="font-family:monospace; color:#fcd34d">${esc(c.sn || '-')}</td>
        <td style="font-family:monospace">${esc(c.id_pppoe)}</td>
        <td style="font-family:monospace; color:${validPort ? '#6ee7b7' : '#fda4af'}">${esc(c.port)}</td>
        <td>${statusBadge(c.status)}
            <select class="admin-only" style="width:auto; padding:2px 4px; font-size:11px; margin-left:6px;" onchange="setCustomerStatus(${c.id}, this.value)">
              <option value="Aktif"${c.status === 'Aktif' ? ' selected' : ''}>Aktif</option>
              <option value="Isolir"${c.status === 'Isolir' ? ' selected' : ''}>Isolir</option>
              <option value="Cabut"${c.status === 'Cabut' ? ' selected' : ''}>Cabut</option>
            </select>
        </td>
        <td style="text-align:right; min-width: 250px;">
          ${validPort ? `
            <button class="btn btn-sm btn-outline-primary" title="Cek Status Koneksi" onclick="reqOLT('status', '${jsStr(c.port)}')"><i class="fa-solid fa-wifi"></i></button>
            <button class="btn btn-sm btn-outline-primary" title="Cek Redaman DBm" onclick="reqOLT('power', '${jsStr(c.port)}')"><i class="fa-solid fa-bolt"></i></button>
            <button class="btn btn-sm btn-rose admin-only" title="Restart ONT Pelanggan" onclick="reqOLT('reboot', '${jsStr(c.port)}')"><i class="fa-solid fa-rotate-right"></i></button>
          ` : ''}
          <button class="btn btn-sm btn-outline-primary" style="margin-left:8px;" title="Muat Parameter" onclick="loadCustomer(${c.id})"><i class="fa-solid fa-arrow-up-right-from-square"></i></button>
          <button class="btn btn-sm btn-outline-primary" title="Riwayat" onclick="showHistory(${c.id})"><i class="fa-solid fa-clock-rotate-left"></i></button>
          <button class="btn btn-sm btn-amber admin-only" title="Edit" onclick="openEditModal(${c.id})"><i class="fa-solid fa-pen"></i></button>
          <button class="btn btn-sm btn-rose admin-only" title="Hapus" onclick="deleteCustomer(${c.id})"><i class="fa-solid fa-trash"></i></button>
        </td>
      </tr>
    `}).join('');

    document.getElementById('badge-count').textContent = dbTotal;
    document.getElementById('stat-total').textContent = dbTotal;
    const aktif = list.filter(c => c.status === 'Aktif').length;
    document.getElementById('stat-aktif').textContent = aktif;
    document.getElementById('stat-cabut').textContent = list.length - aktif;
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
    let color = '#fda4af';   // buruk (di bawah -25 dBm) default merah
    if (v >= -8 && v <= -25) color = '#4ade80';   // bagus
    else if (v > -8 && v <= -1) color = '#fcd34d'; // kelebihan daya (kuning/warning)
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

async function saveToDB() {
  const data = {
    client: document.getElementById('bb-nama').value.trim(),
    paket: Number(document.getElementById('bb-paket').value) || 30,
    sn: document.getElementById('bb-sn').value.trim().toUpperCase(),
    id_pppoe: document.getElementById('bb-userid').value.trim() + document.getElementById('bb-domain').value,
    port: document.getElementById('bb-iface').value.trim() + ':' + document.getElementById('bb-onu').value.trim()
  };
  const isEdit = editingId !== null && editingId !== undefined && editingId !== '';
  if (isEdit) data.id = editingId;
  try {
    let res = await fetch('api.php?action=' + (isEdit ? 'update' : 'add'), { method: 'POST', body: JSON.stringify(data) });
    let json = await res.json();
    if (json.status === 'success') {
      showToast(isEdit ? 'Data pelanggan diperbarui!' : 'Tersimpan di MySQL!');
      editingId = null;
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

function openEditModal(id) {
  const c = customers.find(x => x.id == id);
  if(!c) return;
  document.getElementById('edit-id').value = c.id;
  document.getElementById('edit-nama').value = c.client;
  document.getElementById('edit-paket').value = c.paket;
  document.getElementById('edit-sn').value = c.sn || '-';
  document.getElementById('edit-id-pppoe').value = c.id_pppoe;
  document.getElementById('edit-port').value = c.port;
  document.getElementById('edit-status').value = c.status || 'Aktif';
  document.getElementById('edit-modal').classList.remove('hidden');
}

function closeEditModal() { document.getElementById('edit-modal').classList.add('hidden'); }

async function saveEditCustomerDB() {
  const data = {
    id: parseInt(document.getElementById('edit-id').value),
    client: document.getElementById('edit-nama').value.trim(),
    paket: Number(document.getElementById('edit-paket').value) || 30,
    sn: document.getElementById('edit-sn').value.trim().toUpperCase() || '-',
    id_pppoe: document.getElementById('edit-id-pppoe').value.trim(),
    port: document.getElementById('edit-port').value.trim(),
    status: document.getElementById('edit-status').value
  };
  try {
    let res = await fetch('api.php?action=update', { method: 'POST', body: JSON.stringify(data) });
    let json = await res.json();
    if (json.status === 'success') {
        showToast('Data berhasil diupdate!');
        closeEditModal();
        loadDataDB(); 
    } else { alert("Gagal update: " + json.message); }
  } catch (e) { alert("Error saat update ke API MySQL"); }
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
    } catch(e) {}
}
async function saveOltSettings() {
    const data = {
        olt_ip: document.getElementById('conf-olt-ip').value.trim(),
        olt_port: document.getElementById('conf-olt-port').value.trim() || '23',
        olt_user: document.getElementById('conf-olt-user').value.trim(),
        olt_pass: document.getElementById('conf-olt-pass').value.trim()
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
        mikrotik_ssl: document.getElementById('conf-mk-ssl') && document.getElementById('conf-mk-ssl').checked ? '1' : '0'
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
    rows.push({ client: p[0], paket: p[1], sn: p[2] || '-', id_pppoe: p[3] || '', port: p[4] || 'BELUM ADA' });
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
  tbody.innerHTML = users_db.map(u => `
    <tr>
      <td style="font-weight:600; font-family:monospace;">${esc(u.username)}</td>
      <td>
        <span class="badge" style="${u.role === 'admin' ? 'color:#a5b4fc; border-color:#4f46e5;' : 'color:#fcd34d; border-color:#f59e0b; background:rgba(245,158,11,0.1)'}">
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
  
  // Auto sinkron ke form MikroTik
  document.getElementById('mk-username').value = c.id_pppoe;
  document.getElementById('mk-comment').value = c.client;

  switchTab('broadband');
  generateBroadbandScript();
  showToast('Data dimuat & disinkronkan ke form MikroTik!');
}

function resetBB() {
  editingId = null;
  document.getElementById('bb-nama').value = '';
  document.getElementById('bb-sn').value = '';
  document.getElementById('bb-userid').value = '';
  checkConflictOnly();
}

function exportCSV() {
  const rows = ['NO,CLIENT,PAKET,SN ONT,ID,PORT OLT'].concat(
    customers.map((c, i) => `${i+1},"${c.client}",${c.paket},${c.sn || '-'},${c.id_pppoe},${c.port}`)
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

  const allowed = <?php echo $is_admin ? "['broadband','mikrotik','pelanggan','monitoring','admin']" : "['pelanggan','monitoring']"; ?>;
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

// ================= MONITORING ONLINE/OFFLINE (MIKROTIK) =================
function monStatusBadge(online) {
  return online
    ? '<span class="badge" style="color:#6ee7b7; border-color:#10b981; background:rgba(16,185,129,0.15);">Online</span>'
    : '<span class="badge" style="color:#fcd34d; border-color:#f59e0b; background:rgba(245,158,11,0.1);">Offline</span>';
}

async function loadMonitoring() {
  const tbody = document.getElementById('monitor-tbody');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="8" style="color:var(--muted); text-align:center; padding:20px;">Memuat data dari MikroTik...</td></tr>';
  const errBox = document.getElementById('mon-error');
  if (errBox) errBox.classList.add('hidden');
  try {
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

    tbody.innerHTML = list.map((c, i) => `
      <tr>
        <td style="font-family:monospace; color:var(--muted)">${esc(String(c.id_pppoe || '').split('@')[0] || '-')}</td>
        <td>${monStatusBadge(c.online)}</td>
        <td style="font-weight:600">${esc(c.client)}</td>
        <td style="font-family:monospace">${esc(c.id_pppoe)}</td>
        <td style="font-family:monospace">${esc(c.port)}</td>
        <td style="font-family:monospace; color:#38bdf8">${esc(c.address || '-')}</td>
        <td style="font-family:monospace">${esc(c.caller_id || '-')}</td>
        <td style="font-family:monospace">${esc(c.uptime || '-')}</td>
      </tr>`).join('');
  } catch (e) {
    tbody.innerHTML = '<tr><td colspan="8" style="color:#fda4af">Error memuat data monitoring.</td></tr>';
  }
}

function exportMonitoringCSV() {
  const rows = document.querySelectorAll('#monitor-tbody tr');
  const lines = ['NO,STATUS,NAMA,ID PPPOE,PORT OLT,ALAMAT IP,REMOTE ID,UPTIME'];
  rows.forEach((tr, i) => {
    const cells = Array.from(tr.querySelectorAll('td')).map(td => {
      let txt = (td.textContent || '').trim();
      if (td.querySelector('.badge')) txt = (td.querySelector('.badge').textContent || '').trim();
      return '"' + txt.replace(/"/g, '""') + '"';
    });
    lines.push((i + 1) + ',' + cells.join(','));
  });
  const blob = new Blob([lines.join('\n')], { type: 'text/csv' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = 'MONITORING_PPPOE.csv';
  a.click();
}

setInterval(() => {
  const v = document.getElementById('view-monitoring');
  if (v && !v.classList.contains('hidden')) loadMonitoring();
}, 30000);
</script>
</body>
</html>