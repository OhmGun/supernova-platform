<?php 
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan output error agar tidak merusak JSON
ini_set('log_errors', 1);
require 'koneksi.php';
secureSessionStart();
header('Content-Type: application/json');
if (empty($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir, silakan login ulang.']);
    exit;
}
require_once __DIR__ . '/vendor/autoload.php';

use phpseclib4\Net\SSH2;

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'readonly';
$is_admin = ($role === 'admin');

// Pastikan semua pelanggan punya kolom status (Aktif / Isolir / Cabut)
$colCheck = $conn->query("SHOW COLUMNS FROM pelanggan LIKE 'status'");
if (!$colCheck || $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE pelanggan ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Aktif'");
}

// Matikan error reporting HTML agar tidak merusak format JSON
mysqli_report(MYSQLI_REPORT_OFF);

// =========================================================================
// AUTO-FIX STRUKTUR TABEL & AUTO-IMPORT 108 DATA PELANGGAN (TANPA PHPMYADMIN)
// =========================================================================
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
)");
$conn->query("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
    ('olt_ip', '192.168.1.100'), ('olt_port', '23'), ('olt_user', 'admin'), ('olt_pass', 'admin')");

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'readonly',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$user_cek = $conn->query("SELECT COUNT(*) AS total FROM users");
$user_jml = $user_cek ? $user_cek->fetch_assoc() : ['total' => 0];
if ((int)$user_jml['total'] === 0) {
    $default_pass = password_hash('admin', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
    if ($stmt) { $stmt->bind_param('s', $default_pass); $stmt->execute(); $stmt->close(); }
}

$conn->query("CREATE TABLE IF NOT EXISTS pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client VARCHAR(100) NOT NULL,
    paket INT NOT NULL DEFAULT 30,
    sn VARCHAR(50) DEFAULT '-',
    id_pppoe VARCHAR(100) DEFAULT '-',
    port VARCHAR(50) DEFAULT 'BELUM ADA',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Jika tabel masih menggunakan nama kolom versi awal (no, sn_ont, port_olt), ubah otomatis
$col_check = $conn->query("SHOW COLUMNS FROM pelanggan LIKE 'no'");
if ($col_check && $col_check->num_rows > 0) {
    $conn->query("ALTER TABLE pelanggan CHANGE `no` `id` INT AUTO_INCREMENT");
    $conn->query("ALTER TABLE pelanggan CHANGE `sn_ont` `sn` VARCHAR(50) DEFAULT '-'");
    $conn->query("ALTER TABLE pelanggan CHANGE `port_olt` `port` VARCHAR(50) DEFAULT 'BELUM ADA'");
}

// Kolom nama profile MikroTik (contoh: 'Bisnis 50M', 'Home 30M') — dipisah dari paket (angka Mbps)
$colPp = $conn->query("SHOW COLUMNS FROM pelanggan LIKE 'paket_profile'");
if (!$colPp || $colPp->num_rows === 0) {
    $conn->query("ALTER TABLE pelanggan ADD COLUMN paket_profile VARCHAR(50) NOT NULL DEFAULT '' AFTER paket");
}

// Kolom koordinat peta (latitude / longitude)
$colLat = $conn->query("SHOW COLUMNS FROM pelanggan LIKE 'latitude'");
if (!$colLat || $colLat->num_rows === 0) {
    $conn->query("ALTER TABLE pelanggan ADD COLUMN latitude DECIMAL(10,7) NOT NULL DEFAULT 0 AFTER port");
}
$colLng = $conn->query("SHOW COLUMNS FROM pelanggan LIKE 'longitude'");
if (!$colLng || $colLng->num_rows === 0) {
    $conn->query("ALTER TABLE pelanggan ADD COLUMN longitude DECIMAL(10,7) NOT NULL DEFAULT 0 AFTER latitude");
}

// Cek jumlah data pelanggan, jika 0 maka otomatis masukkan 108 data pelanggan
$cek_isi = $conn->query("SELECT COUNT(*) AS total FROM pelanggan");
$row_isi = $cek_isi ? $cek_isi->fetch_assoc() : ['total' => 0];

if ((int)$row_isi['total'] === 0) {
    $seed_sql = <<<'SQL'
INSERT INTO pelanggan (id, client, paket, sn, id_pppoe, port) VALUES
(1, 'PONDOK WAHYU', 20, '-', '1251720014@supernovanet', '1/2/14:24'),
(2, 'PAK CAHYO', 30, '-', '1251720007@supernovanet', '1/2/13:1'),
(3, 'NASRI', 10, '-', '1251720008@supernovanet', '1/2/6:6'),
(4, 'ERNA LAILI', 10, '-', '1251720009@supernovanet', '1/2/1:13'),
(5, 'JOSEPHINES', 30, '-', '1251720012@supernovanet', '1/2/13:2'),
(6, 'HJ. SANIANG', 10, '-', '1251720013@supernovanet', '1/2/11:14'),
(7, 'RANI', 50, '-', '1251720015@supernovanet', '1/2/14:4'),
(8, 'IWAN', 20, '-', '1251720021@supernovanet', '1/2/8:1'),
(9, 'HAERUL ANWAR (GILI DESA)', 50, '-', '1251720025@supernovanet', '1/2/1:6'),
(10, 'LUKMAN SALEH', 20, '-', '1251720026@supernovanet', '1/2/1:7'),
(11, 'SUKMAWATI', 20, '-', '1251720029@supernovanet', '1/2/1:8'),
(12, 'RIFQI ALFIAN', 50, '-', '1251720032@supernovanet', '1/2/14:8'),
(13, 'SURIANI', 20, '-', '1251720033@supernovanet', 'CABUT'),
(14, 'ANDIKA PRANATA', 10, '-', '1251720034@supernovanet', '1/2/4:1'),
(15, 'H.M NASIR', 50, '-', '1251720035@supernovanet', '1/2/13:4'),
(16, 'Black Fish Tattoo Parlour (sofian)', 50, '-', '1251720045@supernovanet', '1/2/7:1'),
(17, 'Lisnawati (Gili Mo Spa)', 30, '-', '1251720063@supernovanet', '1/2/13:6'),
(18, 'CASA VINTAGE', 100, '-', '1251720052@supernovanet', '1/2/1:10'),
(19, 'SALSABILAH 2 (AHMAD KHOFI MUHAJIR)', 30, '-', '1251720112@supernovanet', '1/2/13:5'),
(20, 'CHIKAL', 20, '-', '1251720011@supernovanet', '1/2/12:10'),
(21, 'INDRAWATI', 30, '-', '1251720057@supernovanet', '1/2/11:3'),
(22, 'HARIATI', 20, '-', '1251720058@supernovanet', '1/2/3:2'),
(23, 'ADI PUTRA', 30, '-', '1251720059@supernovanet', '1/2/3:3'),
(24, 'ARYA RESTU ANUGRAH 1', 100, '-', '1251720060@supernovanet', '1/2/15:2'),
(25, 'EKO YULIANTO', 20, '-', '1251720061@supernovanet', '1/2/1:19'),
(26, 'AYUNAH', 30, '-', '1251720062@supernovanet', '1/2/1:17'),
(27, 'JONATAN', 30, '-', '1251720099@supernovanet', '1/2/1:23'),
(28, 'MOHAMMED ABDULKAREEM', 100, '-', '1251720066@supernovanet', '1/2/11:4'),
(29, 'LINDA SARI', 100, '-', '1251720067@supernovanet', '1/2/11:19'),
(30, 'SAFARI MAHDAN, S.Kom', 30, '-', '1251720068@supernovanet', '1/2/11:6'),
(31, 'IQBAL AMIN', 20, '-', '1251720069@supernovanet', '1/2/15:3'),
(32, 'DIDIN HAERONI', 20, '-', '1251720071@supernovanet', '1/2/1:4'),
(33, 'HERMAN', 30, '-', '1251720075@supernovanet', '1/2/4:5'),
(34, 'HENDI HARIAWAN', 20, '-', '1251720076@supernovanet', '1/2/6:4'),
(35, 'PARHANUDIN', 20, '-', '1251720078@supernovanet', '1/2/3:5'),
(36, 'PARNIAH', 30, '-', '1251720080@supernovanet', '1/2/14:14'),
(37, 'MAHDAN', 20, '-', '1251720081@supernovanet', '1/2/14:15'),
(38, 'SAMSICI', 20, '-', '1251720082@supernovanet', '1/2/14:16'),
(39, 'HERLI JOHAN', 20, '-', '1251720083@supernovanet', '1/2/1:14'),
(40, 'HARI EDI SUBANDI', 20, '-', '1251720084@supernovanet', '1/2/10:1'),
(41, 'HAKIKI', 30, '-', '1251720087@supernovanet', '1/2/14:19'),
(42, 'RIKARDUS JUMAS', 20, '-', '1251720090@supernovanet', '1/2/1:16'),
(43, 'RICHARD TOBING 1', 30, '-', '1251720091@supernovanet', '1/2/12:8'),
(44, 'SAMSUL HIDAYAT', 100, '-', '1251720093@supernovanet', '1/2/4:2'),
(45, 'ODAH CAFFEE', 100, '-', '1251720094@supernovanet', '1/2/5:7'),
(46, 'SRI ANDRIYANI', 20, '-', '1251720092@supernovanet', '1/2/15:7'),
(47, 'MUAMAD ALI AUFA (BORA BORA CAFE)', 200, '-', '1251720097@supernovanet', '1/2/14:20'),
(48, 'JONATAN (VILLA DEEP HOUSE)', 100, '-', '1251720099@supernovanet', '1/2/1:18'),
(49, 'SAOPI HARIADI', 30, '-', '1251720100@supernovanet', '1/2/4:6'),
(50, 'I KETUT PANDE AGUS', 30, '-', '1251720103@supernovanet', '1/2/16:3'),
(51, 'RAIMON DANIEL MARTINES MARTI', 50, '-', '1251720104@supernovanet', '1/2/13:11'),
(52, 'HADANIA', 30, '-', '1251720105@supernovanet', '1/2/7:5'),
(53, 'NUNUNG SETIYANI', 30, '-', '1251720108@supernovanet', '1/2/3:7'),
(54, 'BASSOK SANGKAR (AFRISA JULIA INDRI)', 30, '-', '1251720116@supernovanet', '1/2/5:2'),
(55, 'NARCIS MORALES', 30, '-', '1251720111@supernovanet', '1/2/13:8'),
(56, 'ALFRED ARMANDO GASPERSZ (LITTLE LODGE)', 30, '-', '121251720115@supernovanet', '1/2/4:10'),
(57, 'NILA FEBRIANA ISWARA (MAKUTA VILLA)', 30, '-', '121251720116@supernovanet', '1/2/12:6'),
(58, 'JULPAH (ALWI VILLA)', 30, '-', '121251720118@supernovanet', '1/2/1:5'),
(59, 'LOCHIE', 30, '-', '121251720119@supernovanet', '1/2/13:7'),
(60, 'OMBAK BAR', 50, '-', '121251720120@supernovanet', '1/2/15:4'),
(61, 'EL HAMDAOUI NOUREDDINE', 50, '-', '121251720121@supernovanet', '1/2/1:12'),
(62, 'FAMUNAYA HOMESTAY', 50, '-', '1251720006@supernovanet', '1/2/14:12'),
(63, 'ABDUL KADIR SALEH BAGIS (BLACK SAND HOMESTAY)', 30, '-', '121251720122@supernovanet', '1/2/14:6'),
(64, 'FADLI PAKIDI (PONDOK MARIANA)', 10, '-', '121251720125@supernovanet', 'CABUT'),
(65, 'Arya Restu Anugrah 2 (VILLA SENDY)', 100, '-', '121251720126@supernovanet', '1/2/11:8'),
(66, 'LOCHIE (Little Woodstock)', 30, '-', '121251720127@supernovanet', '1/2/2:1'),
(67, 'WELL ROUGER', 50, '-', '121251720130@supernovanet', '1/2/12:12'),
(68, 'IRWAN', 30, '-', '121251720131@supernovanet', '1/2/13:13'),
(69, 'MUHAMMAD SYUKUR', 30, '-', '121251720132@supernovanet', '1/2/13:14'),
(70, 'RICHARD TOBING (RAFIKI VILLAS)', 30, '-', '121251720134@supernovanet', '1/2/11:24'),
(71, 'MOHAMMED ABDULKAREEM 2', 30, '-', '121251720135@supernovanet', '1/2/11:24'),
(72, 'ENDANG', 30, '-', '121251720139@supernovanet', '1/2/5:4'),
(73, 'IKA DIANTI 1', 50, '-', '1251720110@supernovanet', '1/2/7:6'),
(74, 'SARIF MUNANDAR', 30, '-', '121251720141@supernovanet', '1/2/1:27'),
(75, 'HAERUL RIZAL', 30, '-', '121251720142@supernovanet', '1/2/4:3'),
(76, 'MUHAJIR', 30, '-', '121251720143@supernovanet', '1/2/4:8'),
(77, 'IKA DIANTI 2', 30, '-', '121251720140@supernovanet', '1/2/1:25'),
(78, 'ERDINWISS', 30, '-', '121251720144@supernovanet', '1/2/3:10'),
(79, 'SHEILA AULIA', 100, '-', '121251720145@supernovanet', '1/2/3:9'),
(80, 'VITALY-BUDHA DIVE', 100, '-', '121251720146@supernovanet', '1/2/10:2'),
(81, 'KASPUL KHAIR', 30, '-', '121251720148@supernovanet', '1/2/1:28'),
(82, 'DAYU', 100, '-', '121251720149@supernova', '1/2/1:30'),
(83, 'SALSABILAH 3', 30, '-', '121251720152@supernovanet', '1/2/13:18'),
(84, 'MATLA''AH', 30, '-', '121251720153@supernovanet', '1/2/4:7'),
(85, 'JULPAH( ALWI VILA ) 2', 50, '-', '12125172015314@supernovanet', '1/2/1:15'),
(86, 'RAHUL ARDIANSYAH 2', 30, '-', '-', '1/2/13:10'),
(87, 'ARYA RESTU ANUGRAH 2 (PAK FAJAR)', 30, '-', '1251720086@supernovanet', '1/2/4:4'),
(88, 'HJ. SANIANG 2', 30, '-', '-', 'CABUT'),
(89, 'I KETUT SUDANA', 30, '-', '121251720157@supernovanet', '1/2/11:10'),
(90, 'SALSABILAH 2 ( Salsabila 2. site 4)', 50, '-', '121251720159@supernovanet', '1/2/13:21'),
(91, 'WEST WOOD BUNGALOW', 100, '-', '121251720160@supernovanet', '1/2/11:15'),
(92, 'COCONUT DREAM', 100, '-', '121251720161@supernovanet', 'BELUM ADA'),
(93, 'ARYA RESTU ANUGRAH 3', 100, '-', '121251720162@supernovanet', '1/2/15:10'),
(94, 'AYLAN HOUSE', 50, '-', '121251720163@supernovanet', '1/2/11:22'),
(95, 'GORILLAZY', 50, '-', '121251720164@supernovanet', '1/2/11:16'),
(96, 'GILI KHAYANGAN', 200, '-', '121251720165@supernovanet', '1/2/11:17'),
(97, 'PUTERA ANUGRAH', 50, '-', '121251720166@supernovanet', '1/2/14:22'),
(98, 'BALLA NAILA', 50, '-', '121251720167@supernovanet', '1/2/11:18'),
(99, 'FITRATURAHMAH', 50, '-', '121251720168@supernovanet', '1/2/14:23'),
(100, 'LISA', 50, '-', '121251720170@supernovanet', '1/2/15:11'),
(101, 'SALSABILAH MART & HOMESTAY', 50, '-', '121251720171@supernovanet', '1/2/7:8'),
(102, 'HOME ONE VILLA', 200, '-', '121251720169@supernovanet', '1/2/6:10'),
(103, 'SERENITY VILLA', 30, '-', '121251720172@supernovanet', '1/2/8:5'),
(104, 'SINGA UTARA', 50, '-', '121251720173@supernovanet', '1/2/12:17'),
(105, 'NOVIYADI', 10, '-', '1271650002@librahomenet', 'BELUM ADA'),
(106, 'RAHUL ARDIANSYAH', 30, '-', '1271650003@librahomenet', 'BELUM ADA'),
(107, 'MOON BAR', 10, '-', '1271650004@librahomenet', 'BELUM ADA'),
(108, 'NINA HENDRIASARI', 10, '-', '1271650005@librahomenet', 'BELUM ADA')
SQL;
    $conn->query($seed_sql);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Aksi admin: penulisan (butuh CSRF) + membaca sensitif (audit/backup)
$admin_write = ['add', 'update', 'delete', 'add_user', 'update_user', 'delete_user', 'olt_reboot', 'update_settings', 'update_mk_settings', 'set_status', 'import_customers', 'clear_audit', 'update_telegram'];
$admin_read  = ['get_audit', 'backup_db'];

if (in_array($action, $admin_read) && !$is_admin) {
    echo json_encode(['status' => 'error', 'message' => 'AKSES DITOLAK! Hanya Admin yang dibolehkan.']);
    exit;
}
if (in_array($action, $admin_write)) {
    if (!$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'AKSES DITOLAK! Hanya Admin yang boleh melakukan aksi ini.']);
        exit;
    }
    $csrf = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
    if (!csrfCheck($csrf)) {
        echo json_encode(['status' => 'error', 'message' => 'Token keamanan tidak valid (CSRF). Muat ulang halaman dan coba lagi.']);
        exit;
    }
}
if ($action == 'olt_test') {
    $res = execute_zte_ssh([
        'show pon power attenuation gpon-onu_1/2/8:5'
    ]);

    echo json_encode([
        'status' => 'success',
        'data'   => $res
    ]);

    exit;
}


// ==============================================
// CORE FUNCTION: KONEKSI SSH KE OLT / ROUTER
// ==============================================
// Validasi & normalisasi data pelanggan (server-side). Return false + $err jika tidak valid.
function parseCoordInput($input) {
    // Menerima: "8.341083", "-8.341083", "8.341083°S", "8°20'27.9\"S", "116°E", "8.341083 S"
    $s = trim((string)$input);
    if ($s === '') return 0;
    $neg = 1;
    if (preg_match('/[SW]/i', $s)) $neg = -1;               // Selatan / Barat => negatif
    $clean = preg_replace('/[°˚′\'`"]/', ' ', $s);          // simbol derajat/menit/detik => spasi
    $clean = preg_replace('/[NSWE]/i', ' ', $clean);        // huruf arah => spasi
    $clean = preg_replace('/[^0-9.\s-]/', ' ', $clean);     // sisanya buang
    $parts = preg_split('/\s+/', trim($clean));
    $nums = array_map('floatval', $parts);
    foreach ($nums as $n) if (!is_finite($n)) return 0;
    if (count($nums) === 1) $val = $nums[0];
    elseif (count($nums) === 2) $val = $nums[0] + $nums[1] / 60;
    elseif (count($nums) >= 3) $val = $nums[0] + $nums[1] / 60 + $nums[2] / 3600;
    else return 0;
    return ($neg < 0) ? -abs($val) : $val;
}

function normalizeCustomer($data, &$err) {
    $err = '';
    $client   = trim((string)($data['client'] ?? ''));
    $paket    = (int)($data['paket'] ?? 0);
    $paket_profile = trim((string)($data['paket_profile'] ?? ''));
    $sn       = strtoupper(trim((string)($data['sn'] ?? '')));
    $id_pppoe = trim((string)($data['id_pppoe'] ?? ''));
    $port     = trim((string)($data['port'] ?? ''));
    $status   = in_array(($data['status'] ?? ''), ['Aktif', 'Isolir', 'Cabut'], true) ? $data['status'] : 'Aktif';

    if ($client === '' || strlen($client) > 100) { $err = 'Nama pelanggan wajib diisi (maks 100 karakter).'; return false; }
    if ($paket < 1 || $paket > 100000) { $err = 'Paket tidak valid (1–100000 Mbps).'; return false; }
    if (strlen($paket_profile) > 50) { $err = 'Nama profile paket maks 50 karakter.'; return false; }
    if ($sn !== '' && !preg_match('/^[A-Z0-9\-]{8,20}$/', $sn)) { $err = 'Format SN ONT tidak valid (alphanumerik 8-20, contoh ZTEGD6D940BB).'; return false; }
    if ($id_pppoe === '' || strlen($id_pppoe) > 80) { $err = 'ID PPPoE wajib diisi (maks 80).'; return false; }
    if ($port !== '' && strtoupper($port) !== 'BELUM ADA' && strtoupper($port) !== '-' && !preg_match('#^\d+/\d+/\d+:\d+$#', $port)) { $err = 'Format port OLT tidak valid (contoh 1/2/8:1).'; return false; }

    // Koordinat peta (opsional). Terima berbagai format: desimal (-8.6499), "8.341083°S", DMS "8°20'27.9\"S"
    $latitude  = 0;
    $longitude = 0;
    if (isset($data['latitude']) && trim((string)$data['latitude']) !== '') {
        $latitude = parseCoordInput($data['latitude']);
        if ($latitude < -90 || $latitude > 90) { $err = 'Latitude harus antara -90 s/d 90.'; return false; }
    }
    if (isset($data['longitude']) && trim((string)$data['longitude']) !== '') {
        $longitude = parseCoordInput($data['longitude']);
        if ($longitude < -180 || $longitude > 180) { $err = 'Longitude harus antara -180 s/d 180.'; return false; }
    }

    if ($sn === '') $sn = '-';
    if ($port === '' || strtoupper($port) === '-' || strtoupper($port) === 'BELUM ADA') $port = 'BELUM ADA';
    return ['client' => $client, 'paket' => $paket, 'paket_profile' => $paket_profile, 'sn' => $sn, 'id_pppoe' => $id_pppoe, 'port' => $port, 'status' => $status, 'latitude' => $latitude, 'longitude' => $longitude];
}

// Dekripsi otomatis field kredensial (fallback plaintext lama).
function decryptConf($conf, $keys) {
    foreach ($keys as $k) {
        if (isset($conf[$k]) && $conf[$k] !== '') {
            $d = app_decrypt($conf[$k]);
            if ($d !== null) $conf[$k] = $d;
        }
    }
    return $conf;
}

// Cache server-side singkat (mengurangi beban koneksi ke MikroTik saat banyak permintaan beruntun)
function cacheRead($key, $ttlSec) {
    $f = sys_get_temp_dir() . '/snv_' . md5($key) . '.json';
    if (is_file($f) && (time() - @filemtime($f)) < $ttlSec) {
        $d = @file_get_contents($f);
        if ($d !== false && trim($d) !== '') return $d;
    }
    return null;
}
function cacheWrite($key, $json) {
    @file_put_contents(sys_get_temp_dir() . '/snv_' . md5($key) . '.json', $json);
}

function execute_zte_ssh($cmd_array)
{
    global $conn;

    $conf = [];

    $res = $conn->query("
        SELECT setting_key, setting_value
        FROM settings
    ");

    while ($r = $res->fetch_assoc()) {
        $conf[$r['setting_key']] = $r['setting_value'];
    }
    $conf = decryptConf($conf, ['olt_pass']);

    try {

        $ssh = new SSH2($conf['olt_ip'], (int)($conf['olt_port'] ?: 22));

        if (!$ssh->login($conf['olt_user'], $conf['olt_pass'])) {
            return 'LOGIN GAGAL';
        }

        $ssh->setTerminal('vt100');
        $ssh->enablePTY();
        $ssh->setTimeout(3);

        $ssh->openShell();

        usleep(500000);

        $output = $ssh->read();

        foreach ($cmd_array as $cmd) {

            $ssh->write($cmd . chr(13));

            usleep(500000);

            $output .= "\n\n==== CMD ====\n";
            $output .= $cmd . "\n";

            $output .= $ssh->read('Supernova-GT#');
        }

        return $output;

    } catch (Exception $e) {

        return 'ERROR : ' . $e->getMessage();

    }
}


// ==============================================
// PENGATURAN KREDENSIAL OLT & Mikrotik
// ==============================================
if ($action == 'get_settings') {
    if ($role !== 'admin') exit;
    $res = $conn->query("SELECT setting_key, setting_value FROM settings");
    $data = [];
    if ($res) {
        while($row = $res->fetch_assoc()) { $data[$row['setting_key']] = $row['setting_value']; }
    }
    $data = decryptConf($data, ['olt_pass', 'mikrotik_pass']);
    echo json_encode($data);
}
elseif ($action == 'update_settings') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) {
        foreach(['olt_ip', 'olt_port', 'olt_user'] as $key) {
            $val = $conn->real_escape_string($data[$key]);
            $conn->query("UPDATE settings SET setting_value='$val' WHERE setting_key='$key'");
        }
        if (!empty($data['olt_pass'])) {
            $val = $conn->real_escape_string(app_encrypt($data['olt_pass']));
            $conn->query("UPDATE settings SET setting_value='$val' WHERE setting_key='olt_pass'");
        }
        addAudit($conn, 'UBAH_SETTING', 'Konfigurasi OLT diperbarui');
        echo json_encode(['status' => 'success']);
    }
}
elseif ($action == 'update_mk_settings') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) {
        foreach(['mikrotik_ip', 'mikrotik_port', 'mikrotik_user'] as $key) {
            $val = $conn->real_escape_string($data[$key] ?? '');
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
        }
        if (!empty($data['mikrotik_pass'])) {
            $val = $conn->real_escape_string(app_encrypt($data['mikrotik_pass']));
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('mikrotik_pass', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
        }
        $ssl = (!empty($data['mikrotik_ssl']) && ($data['mikrotik_ssl'] === '1' || $data['mikrotik_ssl'] === 'true')) ? '1' : '0';
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('mikrotik_ssl', '$ssl') ON DUPLICATE KEY UPDATE setting_value='$ssl'");
        addAudit($conn, 'UBAH_SETTING', 'Konfigurasi MikroTik API diperbarui');
        sendTelegram($conn, "⚙️ Config MikroTik API Diperbarui\n━━━━━━━━━━━━━━━━\n🌐 IP : " . ($data['mikrotik_ip'] ?? '') . ":" . ($data['mikrotik_port'] ?? '') . "\n👤 User : " . ($data['mikrotik_user'] ?? '') . "\n👨‍💼 Oleh : " . ($_SESSION['username'] ?? '?'));
        echo json_encode(['status' => 'success']);
    }
}

// ==============================================
// API DATA PELANGGAN & USER
// ==============================================
elseif ($action == 'olt_power') {

    $data = json_decode(file_get_contents('php://input'), true);

    $port = trim($data['port']);

    $power_output = execute_zte_ssh([
        "show pon power attenuation gpon-onu_$port"
    ]);

    echo json_encode([
        'status' => 'success',
        'data'   => $power_output
    ]);

    exit;
}

elseif ($action == 'olt_status') {

    $data = json_decode(file_get_contents('php://input'), true);

    $port = trim($data['port']);

    $wan_output = execute_zte_ssh([
        "show gpon remote wan-ip gpon-onu_$port"
    ]);

    echo json_encode([
        'status' => 'success',
        'data'   => $wan_output
    ]);

    exit;
}

elseif ($action == 'olt_reboot') {

    $data = json_decode(file_get_contents('php://input'), true);

    $port = trim($data['port']);

    $output = execute_zte_ssh([
        "config t",
        "pon-onu-mng gpon-onu_$port",
        "reboot",
        "yes",
        "exit",
        "exit"
    ]);

    addAudit($conn, 'REBOOT_ONT', 'Restart ONU port ' . $port);
    sendTelegram($conn, "🔄 Restart ONU/ONT\n━━━━━━━━━━━━━━━━\n📌 Port OLT : " . $port . "\n👨‍💼 Oleh : " . ($_SESSION['username'] ?? '?'));

    echo json_encode([
        'status' => 'success',
        'data'   => $output
    ]);

    exit;
}

elseif ($action == 'scan_offline') {

    $commands = [];

    for ($i = 1; $i <= 16; $i++) {
        $commands[] =
            "show gpon onu state gpon-olt_1/2/$i";
    }

    $output = execute_zte_ssh($commands);

    $offline = [];

    $result = $conn->query(
        "SELECT client, port FROM pelanggan"
    );

    while ($row = $result->fetch_assoc()) {

        $port = trim($row['port']);

        if (
            empty($port) ||
            strpos($port, ':') === false
        ) {
            continue;
        }

        foreach (explode("\n", $output) as $line) {

            $status = strtolower($line);

            if (
                strpos($line, $port) !== false &&
                (
                    strpos($status, 'offline') !== false ||
                    strpos($status, 'los') !== false
                )
            ) {

                $offline[] = [
                    'client' => $row['client'],
                    'port'   => $port,
                    'state'  =>
                        strpos($status, 'los') !== false
                            ? 'LOS'
                            : 'OFFLINE'
                ];

                break;
            }
        }
    }

    echo json_encode([
        'status'  => 'success',
        'offline' => $offline
    ]);

    exit;
}

elseif ($action == 'get') {
    $q     = isset($_GET['q']) ? trim($_GET['q']) : '';
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(200, max(5, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $where = '';
    if ($q !== '') {
        $like = '%' . $conn->real_escape_string($q) . '%';
        $where = "WHERE id_pppoe LIKE '$like' OR client LIKE '$like' OR port LIKE '$like' OR sn LIKE '$like' OR status LIKE '$like'";
    }
    $total = 0;
    $tr = $conn->query("SELECT COUNT(*) AS c FROM pelanggan $where");
    if ($tr) $total = (int)$tr->fetch_assoc()['c'];

    $result = $conn->query("SELECT * FROM pelanggan $where ORDER BY id ASC LIMIT $offset, $limit");
    $data = array();
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $data[] = array(
                'id'            => (int)($row['id'] ?? 0),
                'client'        => $row['client'] ?? '',
                'paket'         => (int)($row['paket'] ?? 30),
                'paket_profile' => $row['paket_profile'] ?? '',
                'sn'            => $row['sn'] ?? '-',
                'id_pppoe'      => $row['id_pppoe'] ?? '-',
                'port'          => $row['port'] ?? 'BELUM ADA',
                'status'        => $row['status'] ?? 'Aktif',
                'latitude'      => (float)($row['latitude'] ?? 0),
                'longitude'     => (float)($row['longitude'] ?? 0)
            );
        }
    }
    echo json_encode(['total' => $total, 'page' => $page, 'limit' => $limit, 'data' => $data]);
}
elseif ($action == 'get_map') {
    // Data koordinat semua pelanggan untuk peta (dashboard / detail)
    $rows = [];
    $r = $conn->query("SELECT id, client, paket_profile, paket, id_pppoe, port, status, latitude, longitude FROM pelanggan WHERE latitude <> 0 AND longitude <> 0 ORDER BY client ASC");
    if ($r) while ($row = $r->fetch_assoc()) {
        $rows[] = array(
            'id'            => (int)$row['id'],
            'client'        => $row['client'],
            'paket'         => (int)$row['paket'],
            'paket_profile' => $row['paket_profile'] ?? '',
            'id_pppoe'      => $row['id_pppoe'] ?? '-',
            'port'          => $row['port'] ?? 'BELUM ADA',
            'status'        => $row['status'] ?? 'Aktif',
            'latitude'      => (float)$row['latitude'],
            'longitude'     => (float)$row['longitude']
        );
    }
    echo json_encode(['status' => 'success', 'data' => $rows]);
    exit;
}
elseif ($action == 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $norm = normalizeCustomer($data, $err);
    if (!$norm) {
        echo json_encode(['status' => 'error', 'message' => $err]);
        exit;
    }
    // Sinkronisasi Broadband -> Pelanggan:
    // Jika id_pppoe ATAU client sudah ada, jangan insert baru (hindari duplikat),
    // cukup update data pelanggan yang sudah ada.
    $existing = null;
    $byPppoe = $conn->prepare("SELECT id FROM pelanggan WHERE id_pppoe = ? LIMIT 1");
    if ($byPppoe) {
        $byPppoe->bind_param('s', $norm['id_pppoe']);
        $byPppoe->execute();
        $r = $byPppoe->get_result();
        if ($row = $r->fetch_assoc()) $existing = $row['id'];
        $byPppoe->close();
    }
    if (!$existing) {
        $byClient = $conn->prepare("SELECT id FROM pelanggan WHERE client = ? LIMIT 1");
        if ($byClient) {
            $byClient->bind_param('s', $norm['client']);
            $byClient->execute();
            $r = $byClient->get_result();
            if ($row = $r->fetch_assoc()) $existing = $row['id'];
            $byClient->close();
        }
    }
    if ($existing) {
        $stmt = $conn->prepare("UPDATE pelanggan SET client=?, paket=?, paket_profile=?, sn=?, id_pppoe=?, port=?, status='Aktif', latitude=?, longitude=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param('sisssssddi', $norm['client'], $norm['paket'], $norm['paket_profile'], $norm['sn'], $norm['id_pppoe'], $norm['port'], $norm['latitude'], $norm['longitude'], $existing);
            if ($stmt->execute()) {
                addAudit($conn, 'UBAH_PELANGGAN_BROADBAND', $norm['client'] . ' | ' . $norm['id_pppoe'], $existing);
                echo json_encode(['status' => 'success', 'updated' => true, 'id' => $existing]);
            } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
            $stmt->close();
        } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO pelanggan (client, paket, paket_profile, sn, id_pppoe, port, status, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('sisssssdd', $norm['client'], $norm['paket'], $norm['paket_profile'], $norm['sn'], $norm['id_pppoe'], $norm['port'], $norm['status'], $norm['latitude'], $norm['longitude']);
        if ($stmt->execute()) {
            addAudit($conn, 'TAMBAH_PELANGGAN', $norm['client'] . ' | ' . $norm['id_pppoe'], $stmt->insert_id);
            sendTelegram($conn, "🆕 Pelanggan Baru\n━━━━━━━━━━━━━━━━\n🏠 Nama : " . $norm['client'] . "\n📦 Paket : " . ($norm['paket_profile'] ?: $norm['paket'] . ' Mbps') . "\n📌 Port OLT : " . $norm['port'] . "\n👤 ID PPPoE : " . $norm['id_pppoe'] . "\n📊 Status : " . $norm['status'] . "\n👨‍💼 Oleh : " . ($_SESSION['username'] ?? '?'));
            echo json_encode(['status' => 'success']);
        } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
        $stmt->close();
    } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
} 
elseif ($action == 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['id'])) {
        $id = (int)$data['id'];
        $norm = normalizeCustomer($data, $err);
        if (!$norm) {
            echo json_encode(['status' => 'error', 'message' => $err]);
            exit;
        }
        $stmt = $conn->prepare("UPDATE pelanggan SET client=?, paket=?, paket_profile=?, sn=?, id_pppoe=?, port=?, status=?, latitude=?, longitude=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param('sisssssddi', $norm['client'], $norm['paket'], $norm['paket_profile'], $norm['sn'], $norm['id_pppoe'], $norm['port'], $norm['status'], $norm['latitude'], $norm['longitude'], $id);
            if ($stmt->execute()) {
                addAudit($conn, 'UBAH_PELANGGAN', $norm['client'] . ' | ' . $norm['id_pppoe'], $id);
                sendTelegram($conn, "✏️ Pelanggan Diperbarui\n━━━━━━━━━━━━━━━━\n🏠 Nama : " . $norm['client'] . "\n📦 Paket : " . ($norm['paket_profile'] ?: $norm['paket'] . ' Mbps') . "\n📌 Port OLT : " . $norm['port'] . "\n👤 ID PPPoE : " . $norm['id_pppoe'] . "\n📊 Status : " . $norm['status'] . "\n👨‍💼 Oleh : " . ($_SESSION['username'] ?? '?'));
                echo json_encode(['status' => 'success']);
            } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
            $stmt->close();
        } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
elseif ($action == 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['id'])) {
        $id = (int)$data['id'];
        $nm = $conn->query("SELECT client FROM pelanggan WHERE id=$id");
        $clientName = ($nm && $nm->num_rows) ? $nm->fetch_assoc()['client'] : 'id ' . $id;
        $stmt = $conn->prepare("DELETE FROM pelanggan WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                addAudit($conn, 'HAPUS_PELANGGAN', $clientName, $id);
                sendTelegram($conn, "🗑 Pelanggan Dihapus\n━━━━━━━━━━━━━━━━\n🏠 Nama : " . $clientName . "\n👨‍💼 Oleh : " . ($_SESSION['username'] ?? '?'));
                echo json_encode(['status' => 'success']);
            } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
            $stmt->close();
        } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
elseif ($action == 'get_users') {
    if ($role !== 'admin') exit;
    $result = $conn->query("SELECT id, username, role FROM users ORDER BY id ASC");
    $data = array();
    while($row = $result->fetch_assoc()) $data[] = $row;
    echo json_encode($data);
}
elseif ($action == 'add_user') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) {
        $u = trim((string)($data['username'] ?? ''));
        $r = ($data['role'] ?? 'readonly') === 'admin' ? 'admin' : 'readonly';
        if ($u === '' || strlen($u) > 50) { echo json_encode(['status' => 'error', 'message' => 'Username wajib diisi (maks 50).']); exit; }
        if (empty($data['password']) || strlen($data['password']) < 6) { echo json_encode(['status' => 'error', 'message' => 'Password minimal 6 karakter.']); exit; }
        $p = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sss', $u, $p, $r);
            if ($stmt->execute()) { 
                addAudit($conn, 'TAMBAH_USER', 'User: ' . $u . ' (' . $r . ')'); 
                sendTelegram($conn, "🔐 Akun Baru Dibuat\n━━━━━━━━━━━━━━━━\n👤 Username : " . $u . "\n🎖 Role : " . ($r === 'admin' ? 'Admin (Full Akses)' : 'Read-Only') . "\n👨‍💼 Oleh : " . ($_SESSION['username'] ?? '?'));
                echo json_encode(['status' => 'success']); 
            }
            else echo json_encode(['status' => 'error', 'message' => $conn->error]);
            $stmt->close();
        } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
elseif ($action == 'update_user') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['id'])) {
        $id = (int)$data['id'];
        $u = trim((string)($data['username'] ?? ''));
        $r = ($data['role'] ?? 'readonly') === 'admin' ? 'admin' : 'readonly';
        if ($u === '' || strlen($u) > 50) { echo json_encode(['status' => 'error', 'message' => 'Username wajib diisi (maks 50).']); exit; }
        $old = $conn->query("SELECT id, username FROM users WHERE id=$id");
        $oldName = ($old && $old->num_rows) ? $old->fetch_assoc()['username'] : 'id ' . $id;
        $ok = false;
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) { echo json_encode(['status' => 'error', 'message' => 'Password minimal 6 karakter.']); exit; }
            $p = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('sssi', $u, $p, $r, $id); $ok = $stmt->execute(); $stmt->close(); }
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, role=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ssi', $u, $r, $id); $ok = $stmt->execute(); $stmt->close(); }
        }
        if ($ok) { addAudit($conn, 'UBAH_USER', $oldName . ' -> ' . $u); echo json_encode(['status' => 'success']); }
        else echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
elseif ($action == 'delete_user') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['id'])) {
        $id = (int)$data['id'];
        $me = $conn->query("SELECT id FROM users WHERE username='" . $conn->real_escape_string($_SESSION['username']) . "'");
        $meId = ($me && $me->num_rows) ? (int)$me->fetch_assoc()['id'] : 0;
        if ($id === $meId) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak dapat menghapus akun yang sedang Anda gunakan.']);
            exit;
        }
        $nm = $conn->query("SELECT username FROM users WHERE id=$id");
        $uname = ($nm && $nm->num_rows) ? $nm->fetch_assoc()['username'] : 'id ' . $id;
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) { 
                addAudit($conn, 'HAPUS_USER', 'User: ' . $uname); 
                sendTelegram($conn, "🔒 Akun Dihapus\n━━━━━━━━━━━━━━━━\n👤 Username : " . $uname . "\n👨‍💼 Oleh : " . ($_SESSION['username'] ?? '?'));
                echo json_encode(['status' => 'success']); 
            }
            else echo json_encode(['status' => 'error', 'message' => $conn->error]);
            $stmt->close();
        } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}

// ==============================================
// AKSI OPERASIONAL ISP (STATUS, AUDIT, IMPORT, BACKUP, TELEGRAM)
// ==============================================
elseif ($action == 'set_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    $st = isset($data['status']) ? $data['status'] : '';
    if (!in_array($st, ['Aktif', 'Isolir', 'Cabut'], true)) {
        echo json_encode(['status' => 'error', 'message' => 'Status tidak valid.']); exit;
    }
    $row = $conn->query("SELECT id, client, id_pppoe FROM pelanggan WHERE id=$id");
    if (!$row || $row->num_rows === 0) { echo json_encode(['status' => 'error', 'message' => 'Pelanggan tidak ditemukan.']); exit; }
    $r = $row->fetch_assoc();

    $stmt = $conn->prepare("UPDATE pelanggan SET status=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param('si', $st, $id);
        if (!$stmt->execute()) { echo json_encode(['status' => 'error', 'message' => $conn->error]); exit; }
        $stmt->close();
    }

    // Upaya sinkron MikroTik: disable/enable secret PPPoE (teraplikasi bila koneksi tersedia)
    $mkNote = '';
    $uname = trim((string)($r['id_pppoe'] ?? ''));
    if ($uname !== '-' && $uname !== '') {
        $ba = strpos($uname, '@');
        if ($ba !== false) $uname = substr($uname, 0, $ba);
        $mkConf = [];
        $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mikrotik_%'");
        if ($res) while($m = $res->fetch_assoc()) { $mkConf[$m['setting_key']] = $m['setting_value']; }
        $mkConf = decryptConf($mkConf, ['mikrotik_pass']);
        $mk_ip = $mkConf['mikrotik_ip'] ?? '';
        if ($mk_ip !== '') {
            require_once 'routeros_api.class.php';
            $mk_port = !empty($mkConf['mikrotik_port']) ? (int)$mkConf['mikrotik_port'] : 8728;
            $mk_user = $mkConf['mikrotik_user'] ?? '';
            $mk_pass = $mkConf['mikrotik_pass'] ?? '';
            $API = new RouterosAPI();
            $API->ssl = (!empty($mkConf['mikrotik_ssl']) && ($mkConf['mikrotik_ssl'] === '1' || $mkConf['mikrotik_ssl'] === 'true'));
            if ($API->connect($mk_ip, $mk_user, $mk_pass, $mk_port)) {
                if ($st === 'Isolir') {
                    $API->write('/ppp/secret/disable', false);
                    $API->write('=numbers=' . $uname);
                } elseif ($st === 'Cabut') {
                    // Cabut: hapus secret bila ada
                    $API->write('/ppp/secret/remove', false);
                    $API->write('=numbers=' . $uname);
                } else {
                    $API->write('/ppp/secret/enable', false);
                    $API->write('=numbers=' . $uname);
                }
                $API->read();
                $API->disconnect();
                $mkNote = ' MikroTik: OK';
            } else {
                $mkNote = ' MikroTik: tidak terjangkau (status tetap diupdate lokal).';
            }
        }
    }

    addAudit($conn, 'SET_STATUS', $r['client'] . ' -> ' . $st, $id);
    $stIcon = ['Aktif' => '🟢', 'Isolir' => '🔴', 'Cabut' => '⚫'][$st] ?? '📝';
    sendTelegram($conn, $stIcon . " Status Pelanggan Diubah\n━━━━━━━━━━━━━━━━\n🏠 Nama : " . $r['client'] . "\n📊 Status : " . $st . "\n👨‍💼 Oleh : " . ($_SESSION['username'] ?? '?'));
    echo json_encode(['status' => 'success', 'message' => "Status pelanggan diubah menjadi $st." . $mkNote]);
    exit;
}

elseif ($action == 'get_stats') {
    $st = array('total' => 0, 'aktif' => 0, 'isolir' => 0, 'cabut' => 0, 'port_terisi' => 0);
    $r = $conn->query("SELECT status, COUNT(*) c FROM pelanggan GROUP BY status");
    if ($r) while ($row = $r->fetch_assoc()) {
        $k = strtolower($row['status']);
        if ($k === 'aktif') $st['aktif'] = (int)$row['c'];
        elseif ($k === 'isolir') $st['isolir'] = (int)$row['c'];
        elseif ($k === 'cabut') $st['cabut'] = (int)$row['c'];
    }
    $t = $conn->query("SELECT COUNT(*) c FROM pelanggan");
    if ($t) $st['total'] = (int)$t->fetch_assoc()['c'];
    $p = $conn->query("SELECT COUNT(*) c FROM pelanggan WHERE port NOT IN ('BELUM ADA','-')");
    if ($p) $st['port_terisi'] = (int)$p->fetch_assoc()['c'];
    echo json_encode(['status' => 'success', 'stats' => $st]);
    exit;
}

elseif ($action == 'mikrotik_traffic') {
    require_once 'routeros_api.class.php';

    $iface = isset($_GET['interface']) && $_GET['interface'] !== '' ? preg_replace('/[^A-Za-z0-9_\.\/\-]/', '', $_GET['interface']) : 'vlan210';

    $cacheKey = 'traffic_' . $iface;
    if (($cache = cacheRead($cacheKey, 4)) !== null) { echo $cache; exit; }

    $conf = [];
    $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mikrotik_%'");
    if ($res) while($row = $res->fetch_assoc()) { $conf[$row['setting_key']] = $row['setting_value']; }
    $d = app_decrypt($conf['mikrotik_pass'] ?? '');
    if ($d !== null) $conf['mikrotik_pass'] = $d;

    $mkConf = $conf;

    // Baca counter rx/tx-byte milik $iface. Koneksi BARU per percobaan (anti-socket terkontaminasi/tunnel lambat).
    $bacaCounter = function($iface) use ($mkConf) {
        for ($i = 1; $i <= 3; $i++) {
            $API = new RouterosAPI();
            $API->ssl = (!empty($mkConf['mikrotik_ssl']) && ($mkConf['mikrotik_ssl'] === '1' || $mkConf['mikrotik_ssl'] === 'true'));
            $mk_ip = $mkConf['mikrotik_ip'] ?? '';
            $mk_port = !empty($mkConf['mikrotik_port']) ? (int)$mkConf['mikrotik_port'] : 8728;
            if ($mk_ip !== '' && $API->connect($mk_ip, $mkConf['mikrotik_user'] ?? '', $mkConf['mikrotik_pass'] ?? '', $mk_port)) {
                $API->setSocketTimeout(4);
                $API->write('/interface/print');
                $rows = $API->read();
                $API->disconnect();
                foreach ($rows as $row) {
                    if (is_array($row) && isset($row['name']) && $row['name'] === $iface) {
                        if (isset($row['rx-byte']) && isset($row['tx-byte'])) {
                            return array('rx' => (float)$row['rx-byte'], 'tx' => (float)$row['tx-byte']);
                        }
                    }
                }
            }
            usleep(150000);
        }
        return null;
    };

    $s1 = $bacaCounter($iface);
    if ($s1 === null) {
        echo json_encode(['status' => 'error', 'message' => 'Interface "' . $iface . '" tidak terbaca (periksa nama interface di MikroTik).']);
        exit;
    }
    usleep(1100000);
    $s2 = $bacaCounter($iface);
    if ($s2 === null) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membaca sampel kedua traffic "' . $iface . '".']);
        exit;
    }

    $dt = 1.1;
    $rxBps = $s2['rx'] >= $s1['rx'] ? (($s2['rx'] - $s1['rx']) * 8) / $dt : 0;
    $txBps = $s2['tx'] >= $s1['tx'] ? (($s2['tx'] - $s1['tx']) * 8) / $dt : 0;

    $payload = json_encode([
        'status'    => 'success',
        'interface' => $iface,
        'rx'        => (int)round($rxBps),
        'tx'        => (int)round($txBps)
    ]);
    cacheWrite($cacheKey, $payload);
    echo $payload;
    exit;
}

elseif ($action == 'get_audit') {
    $limit = min(500, max(10, (int)($_GET['limit'] ?? 200)));
    $res = $conn->query("SELECT id, username, action, detail, customer_id, ip, waktu FROM audit_log ORDER BY id DESC LIMIT $limit");
    $rows = [];
    if ($res) while($row = $res->fetch_assoc()) { $rows[] = $row; }
    echo json_encode(['status' => 'success', 'data' => $rows]);
    exit;
}

elseif ($action == 'clear_audit') {
    $conn->query("DELETE FROM audit_log");
    addAudit($conn, 'BERSIHKAN_LOG', 'Audit log dibersihkan');
    echo json_encode(['status' => 'success']);
    exit;
}

elseif ($action == 'get_history') {
    $cid = (int)($_GET['id'] ?? 0);
    $res = $conn->query("SELECT id, username, action, detail, waktu FROM audit_log WHERE customer_id=$cid ORDER BY id DESC LIMIT 50");
    $rows = [];
    if ($res) while($row = $res->fetch_assoc()) { $rows[] = $row; }
    echo json_encode(['status' => 'success', 'data' => $rows]);
    exit;
}

elseif ($action == 'update_telegram') {
    $data = json_decode(file_get_contents('php://input'), true);
    foreach (['telegram_bot_token', 'telegram_chat_id'] as $k) {
        $v = $conn->real_escape_string($data[$k] ?? '');
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value='$v'");
    }
    addAudit($conn, 'UBAH_SETTING', 'Konfigurasi Telegram diperbarui');
    // Kirim pesan uji agar operator tahu konfigurasi berfungsi
    $tgl = date('d/m/Y H:i');
    sendTelegram($conn, "✅ Konfigurasi Telegram Aplikasi Diperbarui\n━━━━━━━━━━━━━━━━\n💬 Notifikasi aplikasi aktif dan terkirim.\n🕒 Waktu : " . $tgl . "\n👨‍💼 Oleh : " . ($_SESSION['username'] ?? '?'));
    echo json_encode(['status' => 'success']);
    exit;
}

elseif ($action == 'import_customers') {
    $data = json_decode(file_get_contents('php://input'), true);
    $rows = isset($data['rows']) ? $data['rows'] : [];
    if (!is_array($rows) || count($rows) === 0) { echo json_encode(['status' => 'error', 'message' => 'Tidak ada data untuk diimpor.']); exit; }
    if (count($rows) > 2000) { echo json_encode(['status' => 'error', 'message' => 'Maksimal 2000 baris per import.']); exit; }
    $sukses = 0; $gagal = 0; $errors = [];
    foreach ($rows as $item) {
        if (!is_array($item)) { $gagal++; continue; }
        $norm = normalizeCustomer($item, $err);
        if (!$norm) { $gagal++; if (count($errors) < 10) $errors[] = $err; continue; }
        $cek = $conn->prepare("SELECT id FROM pelanggan WHERE id_pppoe=? LIMIT 1");
        $dup = false;
        if ($cek) {
            $cek->bind_param('s', $norm['id_pppoe']); $cek->execute();
            $dup = ($cek->get_result()->num_rows > 0); $cek->close();
        }
        if ($dup) { $gagal++; continue; }
        $stmt = $conn->prepare("INSERT INTO pelanggan (client, paket, sn, id_pppoe, port, status, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sissssdd', $norm['client'], $norm['paket'], $norm['sn'], $norm['id_pppoe'], $norm['port'], $norm['status'], $norm['latitude'], $norm['longitude']);
            if ($stmt->execute()) { $sukses++; addAudit($conn, 'IMPORT_PELANGGAN', $norm['client'] . ' | ' . $norm['id_pppoe'], $stmt->insert_id); }
            else $gagal++;
            $stmt->close();
        } else $gagal++;
    }
    addAudit($conn, 'IMPORT_CSV', "Import: $sukses sukses, $gagal gagal");
    sendTelegram($conn, "📥 Import Data Pelanggan\n━━━━━━━━━━━━━━━━\n✅ Sukses : " . $sukses . "\n❌ Gagal : " . $gagal . "\n👨‍💼 Oleh : " . ($_SESSION['username'] ?? '?'));
    echo json_encode(['status' => 'success', 'sukses' => $sukses, 'gagal' => $gagal, 'errors' => $errors]);
    exit;
}

elseif ($action == 'backup_db') {
    addAudit($conn, 'BACKUP_DB', 'Backup database diunduh');
    $tables = [];
    $tr = $conn->query("SHOW TABLES");
    if ($tr) while($row = $tr->fetch_array()) { $tables[] = $row[0]; }
    $out = "-- Supernova DB Backup --\n-- Tanggal: " . date('Y-m-d H:i:s') . "\nSET FOREIGN_KEY_CHECKS=0;\n";
    foreach ($tables as $tbl) {
        $out .= "\nDROP TABLE IF EXISTS `$tbl`;\n";
        $ct = $conn->query("SHOW CREATE TABLE `$tbl`");
        if (!$ct) continue;
        $row = $ct->fetch_row();
        $out .= $row[1] . ";\n";
        $dt = $conn->query("SELECT * FROM `$tbl`");
        if (!$dt) continue;
        while ($d = $dt->fetch_assoc()) {
            $cols = array_map(function($c){ return "`$c`"; }, array_keys($d));
            $vals = array_map(function($v) use ($conn){ return "'" . $conn->real_escape_string((string)$v) . "'"; }, array_values($d));
            $out .= "INSERT INTO `$tbl` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
        }
    }
    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="supernova_backup_' . date('Ymd_His') . '.sql"');
    echo $out;
    exit;
}

// ==============================================
// ROUTING API MIKROTIK PPPOE SECRET
// ==============================================
// ============== AMBIL LIST PROFILE MIKROTIK ==============
// ==============================================
// ROUTING API MIKROTIK PROFILES & SECRET
// ==============================================
elseif ($action == 'mikrotik_get_profiles') {
    require_once 'routeros_api.class.php';
    
    $conf = [];
    $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mikrotik_%'");
    while($r = $res->fetch_assoc()) { $conf[$r['setting_key']] = $r['setting_value']; }
    $conf = decryptConf($conf, ['mikrotik_pass']);
    
    $m_ip   = $conf['mikrotik_ip'] ?? '192.168.88.1';
    $m_port = (!empty($conf['mikrotik_port'])) ? (int)$conf['mikrotik_port'] : 8728; // <-- Membaca port custom MikroTik dari database
    $m_user = $conf['mikrotik_user'] ?? 'admin';
    $m_pass = $conf['mikrotik_pass'] ?? '';

    $API = new RouterosAPI();
    $API->debug = false;
    $API->ssl = (!empty($conf['mikrotik_ssl']) && ($conf['mikrotik_ssl'] === '1' || $conf['mikrotik_ssl'] === 'true'));

    // Menghubungkan ke MikroTik menggunakan port custom
    if ($API->connect($m_ip, $m_user, $m_pass, $m_port)) {
        $API->write('/ppp/profile/print');
        $profiles = $API->read();
        $API->disconnect();
        
        $list = [];
        foreach ($profiles as $p) {
            if (isset($p['name'])) {
                $list[] = $p['name'];
            }
        }
        echo json_encode(['status' => 'success', 'data' => $list]);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Gagal konek MikroTik API ke IP $m_ip:$m_port. " . ($API->lastError ?: 'Periksa koneksi/kredensial.')]);
    }
    exit;
}

elseif ($action == 'mikrotik_monitor') {
    require_once 'routeros_api.class.php';

    if (($cache = cacheRead('monitor', 3)) !== null) { echo $cache; exit; }

    $conf = [];
    $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mikrotik_%'");
    while($r = $res->fetch_assoc()) { $conf[$r['setting_key']] = $r['setting_value']; }
    $conf = decryptConf($conf, ['mikrotik_pass']);

    $m_ip   = $conf['mikrotik_ip'] ?? '192.168.88.1';
$m_port = (!empty($conf['mikrotik_port'])) ? (int)$conf['mikrotik_port'] : 8728;
    $m_user = $conf['mikrotik_user'] ?? 'admin';
    $m_pass = $conf['mikrotik_pass'] ?? '';

    $active = [];
    $err = null;

    $API = new RouterosAPI();
    $API->debug = false;
    $API->ssl = (!empty($conf['mikrotik_ssl']) && ($conf['mikrotik_ssl'] === '1' || $conf['mikrotik_ssl'] === 'true'));
    if ($API->connect($m_ip, $m_user, $m_pass, $m_port)) {
        $API->write('/ppp/active/print');
        $active = $API->read();
        if (isset($active['error'])) { unset($active['error']); }

        // Baca queue simple dinamis per pelanggan untuk kecepatan TX/RX realtime (bps)
        // queue dinamis PPPoE namanya <pppoe-<username>>, field 'rate' = upload/download bps.
        $rateByUser = [];
        $bytesByUser = [];
        try {
            $API->write('/queue/simple/print');
            $queues = $API->read();
            if (is_array($queues) && !isset($queues['error'])) {
                foreach ($queues as $qq) {
                    $qname = $qq['name'] ?? '';
                    if (!preg_match('/^<pppoe-(.+)>$/', $qname, $m2)) continue;
                    $uname = strtolower(trim($m2[1]));
                    if ($uname === '') continue;
                    if (isset($qq['rate']) && strpos($qq['rate'], '/') !== false) {
                        list($up, $down) = explode('/', (string)$qq['rate']);
                        $rateByUser[$uname] = [(float)$up, (float)$down];
                    }
                    if (isset($qq['bytes']) && strpos($qq['bytes'], '/') !== false) {
                        list($upB, $downB) = explode('/', (string)$qq['bytes']);
                        $bytesByUser[$uname] = [(float)$upB, (float)$downB];
                    }
                }
            }
        } catch (Throwable $e) { /* queue tidak tersedia, biarkan kosong */ }

        $API->disconnect();
    } else {
        $err = "Gagal terhubung ke MikroTik ($m_ip:$m_port). " . ($API->lastError ?: 'Periksa koneksi/kredensial.');
    }

    $cust = [];
    $res2 = $conn->query("SELECT id, client, paket, id_pppoe, port FROM pelanggan");
    if ($res2) while($r = $res2->fetch_assoc()) { $cust[] = $r; }

    $activeByUser = [];
    foreach ($active as $a) {
        $n = isset($a['name']) ? trim($a['name']) : '';
        if ($n !== '') $activeByUser[strtolower($n)] = $a;
    }

    $list = [];
    foreach ($cust as $c) {
        $key = strtolower(trim($c['id_pppoe'] ?? ''));
        $act = isset($activeByUser[$key]) ? $activeByUser[$key] : null;
        $list[] = [
            'id'         => (int)$c['id'],
            'client'     => $c['client'],
            'paket'      => (int)$c['paket'],
            'id_pppoe'   => $c['id_pppoe'],
            'port'       => $c['port'],
            'online'     => $act ? true : false,
            'address'    => $act && isset($act['address']) ? $act['address'] : null,
            'caller_id'  => $act && isset($act['caller-id']) ? $act['caller-id'] : null,
            'interface'  => $act && isset($act['interface']) ? $act['interface'] : null,
            'uptime'     => $act && isset($act['uptime']) ? $act['uptime'] : null,
            'login_time' => $act && isset($act['login-time']) ? $act['login-time'] : null,
            'rx_rate'    => isset($rateByUser[$key]) ? $rateByUser[$key][1] : 0,   // download bps
            'tx_rate'    => isset($rateByUser[$key]) ? $rateByUser[$key][0] : 0,   // upload bps
            'rx_byte'    => isset($bytesByUser[$key]) ? $bytesByUser[$key][1] : 0, // download bytes kumulatif
            'tx_byte'    => isset($bytesByUser[$key]) ? $bytesByUser[$key][0] : 0, // upload bytes kumulatif,
        ];
    }

    $payload = json_encode([
        'status'       => 'success',
        'online_total' => count($active),
        'online'       => $list,
        'error'        => $err
    ]);
    if ($err === null) cacheWrite('monitor', $payload);
    echo $payload;
    exit;
}
elseif ($action == 'mikrotik_generate_username') {
    require_once 'routeros_api.class.php';

    $conf = [];
    $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mikrotik_%'");
    while($r = $res->fetch_assoc()) { $conf[$r['setting_key']] = $r['setting_value']; }
    $conf = decryptConf($conf, ['mikrotik_pass']);

    $m_ip   = $conf['mikrotik_ip'] ?? '192.168.88.1';
    $m_port = (!empty($conf['mikrotik_port'])) ? (int)$conf['mikrotik_port'] : 8728;
    $m_user = $conf['mikrotik_user'] ?? 'admin';
    $m_pass = $conf['mikrotik_pass'] ?? '';

    $domain = '@supernovanet';
    $used = [];

    // 1. Kumpulkan ID dari database pelanggan
    $rDb = $conn->query("SELECT id_pppoe FROM pelanggan WHERE id_pppoe IS NOT NULL AND TRIM(id_pppoe) != '' AND id_pppoe != '-'");
    if ($rDb) { while ($row = $rDb->fetch_assoc()) { $used[] = trim($row['id_pppoe']); } }

    // 2. Ambil SEMUA secret PPPoE dari MikroTik (semua service, bandingkan nama utuh)
    $connMkErr = null;
    $API = new RouterosAPI();
    $API->debug = false;
    $API->ssl = (!empty($conf['mikrotik_ssl']) && ($conf['mikrotik_ssl'] === '1' || $conf['mikrotik_ssl'] === 'true'));
    if ($API->connect($m_ip, $m_user, $m_pass, $m_port)) {
        // Ambil semua nama secret (print polos; proplist tidak didukung class ini secara langsung)
        $API->write('/ppp/secret/print', false);
        $read = $API->read();
        $API->disconnect();
        if (is_array($read) && !isset($read['error'])) {
            foreach ($read as $s) {
                if (isset($s['name']) && trim($s['name']) !== '') $used[] = trim($s['name']);
            }
        }
    } else {
        $connMkErr = "Gagal konek MikroTik API ke $m_ip:$m_port. " . ($API->lastError ?: '');
    }

    // 3. Hitung ID numerik terbesar dari seluruh username (bagian sebelum @)
    $maxNum = 0;
    foreach ($used as $u) {
        $core = explode('@', $u)[0];
        if (ctype_digit($core)) {
            $n = (int)$core;
            if ($n > $maxNum) $maxNum = $n;
        }
    }

    // 4. Cari slot berikutnya yang belum dipakai (cek penuh, case-insensitive)
    $usedLower = array_map('strtolower', $used);
    $userId = '';
    for ($i = 1; $i <= 200000; $i++) {
        $candKey = $maxNum + $i;
        $full = (string)$candKey . $domain;
        if (!in_array(strtolower($full), $usedLower, true)) {
            $userId = (string)$candKey;
            break;
        }
    }
    if ($userId === '') {
        echo json_encode(['status' => 'error', 'message' => 'Gagal generate: tidak ada slot ID tersisa.', 'mikrotik_error' => $connMkErr]);
        exit;
    }

    echo json_encode([
        'status'   => 'success',
        'id'       => $userId,
        'username' => $userId . $domain,
        'checked_mikrotik' => ($connMkErr === null),
        'mikrotik_error'    => $connMkErr
    ]);
    exit;
}
elseif ($action == 'mikrotik_add_secret') {
    require_once 'routeros_api.class.php';
    
    $conf = [];
    $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mikrotik_%'");
    while($r = $res->fetch_assoc()) { $conf[$r['setting_key']] = $r['setting_value']; }
    $conf = decryptConf($conf, ['mikrotik_pass']);
    
    $m_ip   = $conf['mikrotik_ip'] ?? '192.168.88.1';
    $m_port = (!empty($conf['mikrotik_port'])) ? (int)$conf['mikrotik_port'] : 8728; // <-- Membaca port custom MikroTik dari database
    $m_user = $conf['mikrotik_user'] ?? 'admin';
    $m_pass = $conf['mikrotik_pass'] ?? '';

    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $profile  = $data['profile'] ?? 'default';
    $comment  = $data['comment'] ?? '';
    // Dari modal Tambah Pelanggan: DB sudah disimpan lebih dulu, jadi cek duplikat DB dilewati
    // (cek duplikat di MikroTik tetap dijalankan).
    $skipDb  = !empty($data['skip_db_check']);

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username dan Password PPPoE wajib diisi!']);
        exit;
    }

    $API = new RouterosAPI();
    $API->debug = false;
    $API->ssl = (!empty($conf['mikrotik_ssl']) && ($conf['mikrotik_ssl'] === '1' || $conf['mikrotik_ssl'] === 'true'));

    if ($API->connect($m_ip, $m_user, $m_pass, $m_port)) {
        // Cek duplikat di MikroTik (semua secret, bukan hanya pppoe service)
        // FORMAT BENAR: query filter digabung dalam SATU write
        $API->write('/ppp/secret/print', '?name=' . $username);
        $read = $API->read();
        
        if (!empty($read) && !isset($read['error'])) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal: User PPPoE "' . $username . '" sudah ada di MikroTik!']);
            $API->disconnect();
            exit;
        }

        // Cek duplikat di database pelanggan (kecuali dipanggil dari modal Tambah Pelanggan)
        if (!$skipDb) {
            $cekDb = $conn->prepare("SELECT id FROM pelanggan WHERE id_pppoe = ? LIMIT 1");
            $cekDb->bind_param('s', $username); $cekDb->execute();
            if ($cekDb->get_result()->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal: Username PPPoE "' . $username . '" sudah dipakai pelanggan lain di database!']);
                $API->disconnect();
                exit;
            }
        }

        // TAMBAH SECRET - SATU write dengan semua parameter (format API benar)
        $API->write('/ppp/secret/add', array(
            '=name'     => $username,
            '=password' => $password,
            '=service'  => 'pppoe',
            '=profile'  => $profile,
            '=comment'  => $comment
        ));
        $addResp = $API->read();
        $API->disconnect();

        // Cek trap/fatal error dari router (misal profile tidak ada, dsb)
        if (is_array($addResp) && isset($addResp['error'])) {
            $msg = 'Gagal tambah secret di MikroTik';
            if (isset($addResp[0]['message'])) $msg .= ': ' . $addResp[0]['message'];
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => 'Sukses! Secret PPPoE berhasil ditambahkan ke MikroTik.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Gagal konek MikroTik API ke IP $m_ip:$m_port. " . ($API->lastError ?: 'Periksa koneksi/kredensial.')]);
    }
    exit;
}
elseif ($action == 'mikrotik_sync_secret') {
    // Sinkronkan data secret PPPoE yang sudah ada (dipanggil saat edit pelanggan di aplikasi).
    if (!$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak: hanya admin.']);
        exit;
    }
    require_once 'routeros_api.class.php';

    $conf = [];
    $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mikrotik_%'");
    while($r = $res->fetch_assoc()) { $conf[$r['setting_key']] = $r['setting_value']; }
    $conf = decryptConf($conf, ['mikrotik_pass']);

    $m_ip   = $conf['mikrotik_ip'] ?? '192.168.88.1';
    $m_port = (!empty($conf['mikrotik_port'])) ? (int)$conf['mikrotik_port'] : 8728;
    $m_user = $conf['mikrotik_user'] ?? 'admin';
    $m_pass = $conf['mikrotik_pass'] ?? '';

    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $comment  = $data['comment'] ?? null;
    $profile  = $data['profile'] ?? null;

    if ($username === '') {
        echo json_encode(['status' => 'error', 'message' => 'Username PPPoE wajib diisi!']);
        exit;
    }
    if ($comment === null && $profile === null) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang perlu disinkronkan.']);
        exit;
    }

    $API = new RouterosAPI();
    $API->debug = false;
    $API->ssl = (!empty($conf['mikrotik_ssl']) && ($conf['mikrotik_ssl'] === '1' || $conf['mikrotik_ssl'] === 'true'));

    if ($API->connect($m_ip, $m_user, $m_pass, $m_port)) {
        $API->write('/ppp/secret/print', '?name=' . $username);
        $read = $API->read();

        if (empty($read) || isset($read['error']) || !isset($read[0]['.id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Secret PPPoE "' . $username . '" tidak ditemukan di MikroTik.']);
            $API->disconnect();
            exit;
        }

        $attrs = ['=.id' => $read[0]['.id']];
        if ($comment !== null) $attrs['=comment'] = $comment;
        if ($profile !== null && $profile !== '') $attrs['=profile'] = $profile;

        $API->write('/ppp/secret/set', $attrs);
        $setResp = $API->read();
        $API->disconnect();

        if (is_array($setResp) && isset($setResp['error'])) {
            $msg = 'Gagal sinkronkan secret di MikroTik';
            if (isset($setResp[0]['message'])) $msg .= ': ' . $setResp[0]['message'];
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit;
        }

        echo json_encode(['status' => 'success', 'message' => 'Secret PPPoE berhasil disinkronkan ke MikroTik.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Gagal konek MikroTik API ke IP $m_ip:$m_port. " . ($API->lastError ?: 'Periksa koneksi/kredensial.')]);
    }
    exit;
}
?>