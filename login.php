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
$maxAttempts = 5;

// Cek lock aktif (brute-force)
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
        $error = 'Sesi tidak valid, silakan coba lagi.';
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
                    // Fallback akun lama ber-hash md5 + upgrade otomatis
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
<title>Login - Supernova OLT</title>
<style>
  body { background: #020617; color: #f8fafc; font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
  .card { background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; padding: 35px 30px; width: 100%; max-width: 360px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
  .logo-box { display: flex; justify-content: center; margin-bottom: 15px; }
  .logo { width: 50px; height: 50px; background: #4f46e5; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-family: monospace; font-size: 16px; }
  h2 { text-align: center; margin-top: 0; margin-bottom: 25px; font-size: 18px; letter-spacing: 1px; }
  label { font-size: 11px; color: #94a3b8; display: block; margin-bottom: 6px; text-transform: uppercase; font-weight: 600; }
  input { width: 100%; background: #1e293b; border: 1px solid #334155; color: #fff; padding: 10px 12px; border-radius: 6px; margin-bottom: 18px; box-sizing: border-box; font-family: monospace; font-size: 14px; }
  input:focus { outline: none; border-color: #4f46e5; }
  .btn { background: #4f46e5; color: #fff; border: none; padding: 12px; width: 100%; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s; letter-spacing: 1px; }
  .btn:hover { background: #4338ca; }
  .error { color: #fda4af; background: rgba(244, 63, 94, 0.2); padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 12px; text-align: center; border: 1px solid rgba(244,63,94,0.3); }
</style>
</head>
<body>
  <div class="card">
    <div class="logo-box"><div class="logo">OLT</div></div>
    <h2>SUPERNOVA <span style="color:#a5b4fc">C320</span></h2>
    <?php if($error) echo "<div class='error'>" . htmlspecialchars($error) . "</div>"; ?>
    <form method="POST">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrfToken()); ?>">
      <label>Username</label>
      <input type="text" name="username" required autocomplete="off" autofocus>
      <label>Password</label>
      <input type="password" name="password" required>
      <button type="submit" class="btn">LOGIN</button>
    </form>
  </div>
</body>
</html>