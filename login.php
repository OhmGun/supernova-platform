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
    margin: 0; min-height: 100vh; background: #020617; overflow: hidden;
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif; color: #f8fafc;
    display: flex; justify-content: center; align-items: center; position: relative;
  }
  /* latar dekoratif */
  .glow { position: fixed; border-radius: 50%; filter: blur(90px); opacity: .5; z-index: 0; }
  .glow-1 { width: 420px; height: 420px; background: #4f46e5; top: -140px; left: -120px; }
  .glow-2 { width: 380px; height: 380px; background: #0891b2; bottom: -160px; right: -100px; }
  .glow-3 { width: 260px; height: 260px; background: #0d9488; top: 40%; left: 55%; opacity: .25; }
  .grid-line {
    position: fixed; inset: 0; z-index: 0; pointer-events: none;
    background-image: linear-gradient(rgba(148,163,184,.06) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(148,163,184,.06) 1px, transparent 1px);
    background-size: 44px 44px;
  }

  .card {
    position: relative; z-index: 1; width: 100%; max-width: 390px; margin: 0 16px;
    background: rgba(15, 23, 42, 0.78); backdrop-filter: blur(18px);
    border: 1px solid rgba(148, 163, 184, 0.16); border-radius: 20px;
    padding: 38px 32px 28px; box-shadow: 0 30px 60px rgba(0,0,0,0.55), 0 0 0 1px rgba(99,102,241,0.08);
  }
  .logo-box { display: flex; justify-content: center; margin-bottom: 4px; }
  .logo {
    width: 62px; height: 62px; border-radius: 18px;
    background: rgba(6,11,25,.6); border: 1px solid rgba(139,92,246,.5);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 12px 30px rgba(79,70,229,.35);
  }
  h1 { text-align: center; margin: 16px 0 2px; font-size: 22px; letter-spacing: 1px; }
  .subtitle { text-align: center; color: #94a3b8; font-size: 12px; margin: 0 0 26px; letter-spacing: .3px; }
  .subtitle b { color: #a5b4fc; font-weight: 600; }

  .field { margin-bottom: 18px; }
  .field label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .8px; font-weight: 600; margin-bottom: 7px; }
  .input { position: relative; }
  .input i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 13px; }
  .input input {
    width: 100%; background: rgba(30,41,59,.7); border: 1px solid #334155;
    color: #f8fafc; padding: 12px 14px 12px 38px; border-radius: 10px;
    font-family: monospace; font-size: 14px; transition: border-color .2s, box-shadow .2s;
  }
  .input input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.25); }
  .input .toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 13px; padding: 4px; }
  .input .toggle:hover { color: #a5b4fc; }

  .btn {
    width: 100%; margin-top: 6px; padding: 13px; border: none; border-radius: 10px;
    background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff;
    font-size: 14px; font-weight: 700; letter-spacing: 1px; cursor: pointer;
    transition: filter .2s, transform .1s;
  }
  .btn:hover { filter: brightness(1.12); }
  .btn:active { transform: translateY(1px); }

  .error {
    color: #fda4af; background: rgba(244,63,94,.12); border: 1px solid rgba(244,63,94,.35);
    padding: 10px 12px; border-radius: 10px; margin-bottom: 18px;
    font-size: 12px; text-align: center; display: flex; gap: 8px; align-items: center; justify-content: center;
  }
  .footer { text-align: center; color: #475569; font-size: 11px; margin-top: 22px; letter-spacing: .4px; }

  @media (max-width: 480px) {
    .card { margin: 14px; padding: 30px 22px 22px; }
  }
  @media (prefers-color-scheme: light) { body { /* tetap gelap agar konsisten */ } }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
  <div class="glow glow-1"></div>
  <div class="glow glow-2"></div>
  <div class="glow glow-3"></div>
  <div class="grid-line"></div>

  <div class="card">
    <div class="logo-box"><div class="logo"><svg viewBox="0 0 64 64" width="38" height="38" aria-hidden="true"><defs><linearGradient id="snLogo" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8b5cf6"/><stop offset=".55" stop-color="#6366f1"/><stop offset="1" stop-color="#06b6d4"/></linearGradient></defs><circle cx="32" cy="32" r="24" fill="none" stroke="url(#snLogo)" stroke-width="1.6" opacity=".55"/><g stroke="url(#snLogo)" stroke-width="2.4" stroke-linecap="round"><line x1="32" y1="8" x2="32" y2="20"/><line x1="32" y1="44" x2="32" y2="56"/><line x1="8" y1="32" x2="20" y2="32"/><line x1="44" y1="32" x2="56" y2="32"/></g><g fill="#22d3ee"><circle cx="56" cy="32" r="3.2"/><circle cx="32" cy="8" r="3.2"/><circle cx="8" cy="32" r="3.2"/><circle cx="32" cy="56" r="3.2"/></g><g fill="#a5b4fc"><circle cx="46" cy="18" r="2.6"/><circle cx="18" cy="18" r="2.6"/><circle cx="18" cy="46" r="2.6"/><circle cx="46" cy="46" r="2.6"/></g><circle cx="32" cy="32" r="11" fill="url(#snLogo)"/><circle cx="28.5" cy="28.5" r="4" fill="#fff" opacity=".92"/></svg></div></div>
    <h1>SUPERNOVA</h1>
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