<?php
require 'koneksi.php';
if (!secureSessionStart()) {
    session_destroy();
}

if (isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$lockMinutes = 15;

function isLocked($conn, $ip, $minutes) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM login_attempts WHERE ip = ? AND success = 0 AND waktu > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    if (!$stmt) return true;
    $stmt->bind_param('si', $ip, $minutes);
    $stmt->execute();
    $c = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$c['c'] >= 5;
}
function lockRemainingMin($conn, $ip, $minutes) {
    $stmt = $conn->prepare("SELECT TIMESTAMPDIFF(MINUTE, MAX(waktu), NOW()) AS ago FROM login_attempts WHERE ip = ? AND success = 0 AND waktu > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    if (!$stmt) return 0;
    $stmt->bind_param('si', $ip, $minutes);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($minutes - (int)$r['ago']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    } elseif (isLocked($conn, $ip, $lockMinutes)) {
        $rem = max(1, lockRemainingMin($conn, $ip, $lockMinutes));
        $error = "Terlalu banyak percobaan. Coba lagi dalam $rem menit.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            if ($user) {
                $ok = password_verify($password, $user['password']);
                if (!$ok) {
                    $ok = is_string($user['password']) && strtolower($user['password']) === md5($password);
                    if ($ok) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        if ($up) {
                            $up->bind_param('si', $newHash, $user['id']);
                            $up->execute();
                            $up->close();
                        }
                    }
                }

                if ($ok) {
                    $conn->query("DELETE FROM login_attempts WHERE ip = '" . $conn->real_escape_string($ip) . "'");
                    session_regenerate_id(true);
                    $_SESSION['login'] = true;
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['csrf'] = bin2hex(random_bytes(24));
                    $_SESSION['last_activity'] = time();
                    addAudit($conn, 'LOGIN', 'Login sukses: ' . $user['username']);
                    header("Location: index.php");
                    exit;
                }
            }

            $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip, success) VALUES (?, ?, 0)");
            if ($stmt) { $stmt->bind_param('ss', $username, $ip); $stmt->execute(); $stmt->close(); }
            addAudit($conn, 'LOGIN_GAGAL', 'Percobaan login gagal: ' . $username);
            $error = 'Username atau password salah!';
        } else {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Masuk - Supernova OLT</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; background: #000; overflow: hidden;
    font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Helvetica Neue", "Segoe UI", Roboto, sans-serif;
    color: #f5f5f7; -webkit-font-smoothing: antialiased;
    display: flex; justify-content: center; align-items: center; position: relative;
  }
  /* latar dekoratif ala macOS */
  .glow { position: fixed; border-radius: 50%; filter: blur(100px); opacity: .45; z-index: 0; }
  .glow-1 { width: 460px; height: 460px; background: #0A84FF; top: -160px; left: -140px; }
  .glow-2 { width: 420px; height: 420px; background: #BF5AF2; bottom: -180px; right: -120px; }
  .glow-3 { width: 280px; height: 280px; background: #64D2FF; top: 42%; left: 52%; opacity: .18; }
  .grid-line {
    position: fixed; inset: 0; z-index: 0; pointer-events: none;
    background-image: linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
    background-size: 48px 48px;
  }

  .card {
    position: relative; z-index: 1; width: 100%; max-width: 390px; margin: 0 16px;
    background: rgba(28,28,36,.6); backdrop-filter: blur(28px) saturate(180%);
    -webkit-backdrop-filter: blur(28px) saturate(180%);
    border: 1px solid rgba(255,255,255,.1); border-radius: 22px;
    padding: 34px 32px 28px; box-shadow: 0 40px 90px rgba(0,0,0,.7), inset 0 1px 0 rgba(255,255,255,.1);
  }
  .traffic { display: flex; gap: 8px; margin-bottom: 6px; }
  .traffic i { width: 12px; height: 12px; border-radius: 50%; display: block; }
  .traffic .t-red { background: #FF5F57; } .traffic .t-yel { background: #FEBC2E; } .traffic .t-grn { background: #28C840; }
  .logo-box { display: flex; justify-content: center; margin-bottom: 4px; }
  .logo {
    width: 66px; height: 66px; border-radius: 18px;
    background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.14);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 14px 34px rgba(10,132,255,.25), inset 0 1px 0 rgba(255,255,255,.14);
  }
  h1 { text-align: center; margin: 16px 0 2px; font-size: 23px; letter-spacing: .5px; font-weight: 700; }
  .subtitle { text-align: center; color: #98989f; font-size: 12px; margin: 0 0 26px; letter-spacing: .2px; }
  .subtitle b { color: #64D2FF; font-weight: 600; }

  .field { margin-bottom: 18px; }
  .field label { display: block; font-size: 11px; color: #98989f; text-transform: uppercase; letter-spacing: .8px; font-weight: 600; margin-bottom: 7px; }
  .input { position: relative; }
  .input i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #86868b; font-size: 13px; }
  .input input {
    width: 100%; background: rgba(255,255,255,.09); border: 1px solid rgba(255,255,255,.12);
    color: #f5f5f7; padding: 12px 14px 12px 38px; border-radius: 12px;
    font-family: "SF Mono", ui-monospace, Menlo, monospace; font-size: 14px; transition: border-color .2s, box-shadow .2s;
  }
  .input input:focus { outline: none; border-color: rgba(10,132,255,.75); box-shadow: 0 0 0 3px rgba(10,132,255,.25); }
  .input .toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #86868b; cursor: pointer; font-size: 13px; padding: 4px; }
  .input .toggle:hover { color: #64D2FF; }

  .btn {
    width: 100%; margin-top: 6px; padding: 13px; border: none; border-radius: 12px;
    background: linear-gradient(180deg, #0A84FF, #0071e3); color: #fff;
    font-size: 14px; font-weight: 700; letter-spacing: 1px; cursor: pointer;
    transition: filter .2s, transform .1s; box-shadow: 0 6px 18px rgba(10,132,255,.35), inset 0 1px 0 rgba(255,255,255,.2);
  }
  .btn:hover { filter: brightness(1.1); }
  .btn:active { transform: translateY(1px); }

  .error {
    color: #ff8d86; background: rgba(255,69,58,.12); border: 1px solid rgba(255,69,58,.35);
    padding: 10px 12px; border-radius: 12px; margin-bottom: 18px;
    font-size: 12px; text-align: center; display: flex; gap: 8px; align-items: center; justify-content: center;
  }
  .footer { text-align: center; color: #63636b; font-size: 11px; margin-top: 22px; letter-spacing: .4px; }

  @media (max-width: 480px) {
    .card { margin: 14px; padding: 28px 22px 22px; }
  }
  @media (prefers-color-scheme: light) { body { /* tetap gelap agar konsisten */ } }
</style>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>
</head>
<body>
  <div class="glow glow-1"></div>
  <div class="glow glow-2"></div>
  <div class="glow glow-3"></div>
  <div class="grid-line"></div>

  <div class="card">
    <div class="traffic"><i class="t-red"></i><i class="t-yel"></i><i class="t-grn"></i></div>
    <div class="logo-box"><img src="img/logo.png" alt="Supernova" style="width:250px; height:auto; object-fit:contain;"></div>
    <p class="subtitle">Platform Operasional ISP — <b>ZTE C320</b> &amp; <b>MikroTik</b></p>

    <?php if($error): ?>
      <div class="error"><i class="fa-solid fa-circle-exclamation"></i><span><?php echo htmlspecialchars($error); ?></span></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrfToken()); ?>">

      <div class="field">
        <label>Username</label>
        <div class="input">
          <i class="fa-solid fa-user"></i>
          <input type="text" name="username" placeholder="Masukkan username" required autofocus>
        </div>
      </div>

      <div class="field">
        <label>Password</label>
        <div class="input">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="pw" placeholder="Masukkan password" required>
          <button type="button" class="toggle" id="pwToggle" tabindex="-1"><i id="pwIcon" class="fa-solid fa-eye"></i></button>
        </div>
      </div>

      <button type="submit" class="btn">MASUK</button>
    </form>

    <div class="footer">Supernova <i class="fa-solid fa-bolt" style="font-size:8px;"></i> OLT Management</div>
  </div>

  <script>
    const t = document.getElementById('pw');
    document.getElementById('pwToggle').addEventListener('click', () => {
      const show = t.type === 'password';
      t.type = show ? 'text' : 'password';
      document.getElementById('pwIcon').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
    });
  </script>
</body>
</html>