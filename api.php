<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan output error agar tidak merusak JSON
ini_set('log_errors', 1);
if (!isset($_SESSION['login'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir, silakan login ulang.']);
    exit;
}

header('Content-Type: application/json');
require 'koneksi.php';
require_once __DIR__ . '/vendor/autoload.php';

use phpseclib4\Net\SSH2;

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
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'readonly';

$write_actions = ['add', 'update', 'delete', 'add_user', 'update_user', 'delete_user', 'olt_reboot', 'update_settings', 'update_mk_settings'];
if (in_array($action, $write_actions) && $role !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'AKSES DITOLAK! Hanya Admin yang boleh melakukan aksi ini.']);
    exit;
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

    try {

        $ssh = new SSH2($conf['olt_ip']);

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
            $val = $conn->real_escape_string($data['olt_pass']);
            $conn->query("UPDATE settings SET setting_value='$val' WHERE setting_key='olt_pass'");
        }
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
            $val = $conn->real_escape_string($data['mikrotik_pass']);
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('mikrotik_pass', '$val') ON DUPLICATE KEY UPDATE setting_value='$val'");
        }
        $ssl = (!empty($data['mikrotik_ssl']) && ($data['mikrotik_ssl'] === '1' || $data['mikrotik_ssl'] === 'true')) ? '1' : '0';
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('mikrotik_ssl', '$ssl') ON DUPLICATE KEY UPDATE setting_value='$ssl'");
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
        "exit"
    ]);

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
    $result = $conn->query("SELECT * FROM pelanggan");
    $data = array();
    if ($result) {
        while($row = $result->fetch_assoc()) {
            // Normalisasi nama kolom agar selalu terbaca oleh JavaScript
            $data[] = array(
                'id'       => (int)($row['id'] ?? $row['no'] ?? 0),
                'client'   => $row['client'] ?? '',
                'paket'    => (int)($row['paket'] ?? 30),
                'sn'       => $row['sn'] ?? $row['sn_ont'] ?? '-',
                'id_pppoe' => $row['id_pppoe'] ?? '-',
                'port'     => $row['port'] ?? $row['port_olt'] ?? 'BELUM ADA'
            );
        }
    }
    echo json_encode($data);
} 
elseif ($action == 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) {
        $client = $data['client'];
        $paket  = (int)$data['paket'];
        $sn     = $data['sn'];
        $id_pppoe = $data['id_pppoe'];
        $port   = $data['port'];
        $stmt = $conn->prepare("INSERT INTO pelanggan (client, paket, sn, id_pppoe, port) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sisss', $client, $paket, $sn, $id_pppoe, $port);
            if ($stmt->execute()) echo json_encode(['status' => 'success']);
            else echo json_encode(['status' => 'error', 'message' => $conn->error]);
            $stmt->close();
        } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
} 
elseif ($action == 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['id'])) {
        $id = (int)$data['id'];
        $client = $data['client'];
        $paket  = (int)$data['paket'];
        $sn     = $data['sn'];
        $id_pppoe = $data['id_pppoe'];
        $port   = $data['port'];
        $stmt = $conn->prepare("UPDATE pelanggan SET client=?, paket=?, sn=?, id_pppoe=?, port=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param('sisssi', $client, $paket, $sn, $id_pppoe, $port, $id);
            if ($stmt->execute()) echo json_encode(['status' => 'success']);
            else echo json_encode(['status' => 'error', 'message' => $conn->error]);
            $stmt->close();
        } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
elseif ($action == 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['id'])) {
        $id = (int)$data['id'];
        $stmt = $conn->prepare("DELETE FROM pelanggan WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) echo json_encode(['status' => 'success']);
            else echo json_encode(['status' => 'error', 'message' => $conn->error]);
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
        $u = $data['username'];
        $r = $data['role'];
        $p = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sss', $u, $p, $r);
            if ($stmt->execute()) echo json_encode(['status' => 'success']);
            else echo json_encode(['status' => 'error', 'message' => $conn->error]);
            $stmt->close();
        } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
elseif ($action == 'update_user') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['id'])) {
        $id = (int)$data['id'];
        $u = $data['username'];
        $r = $data['role'];
        $ok = false;
        if (!empty($data['password'])) {
            $p = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('sssi', $u, $p, $r, $id); $ok = $stmt->execute(); $stmt->close(); }
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, role=? WHERE id=?");
            if ($stmt) { $stmt->bind_param('ssi', $u, $r, $id); $ok = $stmt->execute(); $stmt->close(); }
        }
        if ($ok) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
elseif ($action == 'delete_user') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['id'])) {
        $id = (int)$data['id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) echo json_encode(['status' => 'success']);
            else echo json_encode(['status' => 'error', 'message' => $conn->error]);
            $stmt->close();
        } else echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
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

    $conf = [];
    $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mikrotik_%'");
    while($r = $res->fetch_assoc()) { $conf[$r['setting_key']] = $r['setting_value']; }

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
        $API->disconnect();
        if (isset($active['error'])) { unset($active['error']); }
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
        ];
    }

    echo json_encode([
        'status'       => 'success',
        'online_total' => count($active),
        'online'       => $list,
        'error'        => $err
    ]);
    exit;
}
elseif ($action == 'mikrotik_add_secret') {
    require_once 'routeros_api.class.php';
    
    $conf = [];
    $res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'mikrotik_%'");
    while($r = $res->fetch_assoc()) { $conf[$r['setting_key']] = $r['setting_value']; }
    
    $m_ip   = $conf['mikrotik_ip'] ?? '192.168.88.1';
    $m_port = (!empty($conf['mikrotik_port'])) ? (int)$conf['mikrotik_port'] : 8728; // <-- Membaca port custom MikroTik dari database
    $m_user = $conf['mikrotik_user'] ?? 'admin';
    $m_pass = $conf['mikrotik_pass'] ?? '';

    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $profile  = $data['profile'] ?? 'default';
    $comment  = $data['comment'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username dan Password PPPoE wajib diisi!']);
        exit;
    }

    $API = new RouterosAPI();
    $API->debug = false;
    $API->ssl = (!empty($conf['mikrotik_ssl']) && ($conf['mikrotik_ssl'] === '1' || $conf['mikrotik_ssl'] === 'true'));

    if ($API->connect($m_ip, $m_user, $m_pass, $m_port)) {
        $API->write('/ppp/secret/print', false);
        $API->write("?=name=" . $username);
        $read = $API->read();
        
        if (!empty($read)) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal: User PPPoE "' . $username . '" sudah ada di MikroTik!']);
            $API->disconnect();
            exit;
        }

        $API->write('/ppp/secret/add', false);
        $API->write('=name=' . $username, false);
        $API->write('=password=' . $password, false);
        $API->write('=service=pppoe', false);
        $API->write('=profile=' . $profile, false);
        $API->write('=comment=' . $comment);
        $API->read();

        $API->disconnect();
        echo json_encode(['status' => 'success', 'message' => 'Sukses! Secret PPPoE berhasil ditambahkan ke MikroTik.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Gagal konek MikroTik API ke IP $m_ip:$m_port. " . ($API->lastError ?: 'Periksa koneksi/kredensial.')]);
    }
    exit;
}
?>