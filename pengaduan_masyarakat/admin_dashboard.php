<?php
session_start();
include 'koneksi.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas' || $_SESSION['level'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fungsi untuk ambil data statistik (dipanggil via AJAX atau langsung)
function getStats($koneksi) {
    $stats = [
        'total_pengaduan' => 0,
        'pending' => 0,
        'proses' => 0,
        'selesai' => 0,
        'total_masyarakat' => 0,
        'total_petugas' => 0,
        'recent_pengaduan' => [],
        'recent_tanggapan' => [],
        'data_masyarakat' => [],
        'data_petugas' => [],
        'data_pengaduan' => [],
        'data_tanggapan' => []
    ];

    // Total pengaduan
    $result = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pengaduan");
    $stats['total_pengaduan'] = mysqli_fetch_assoc($result)['total'];

    // Status pengaduan
    $result = mysqli_query($koneksi, "SELECT status, COUNT(*) as count FROM pengaduan GROUP BY status");
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['status'] == '0') $stats['pending'] = $row['count'];
        elseif ($row['status'] == 'proses') $stats['proses'] = $row['count'];
        elseif ($row['status'] == 'selesai') $stats['selesai'] = $row['count'];
    }

    // Total masyarakat
    $result = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM masyarakat");
    $stats['total_masyarakat'] = mysqli_fetch_assoc($result)['count'];

    // Total petugas (exclude admin)
    $result = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM petugas WHERE status = 'petugas'");
    $stats['total_petugas'] = mysqli_fetch_assoc($result)['count'];

    // Data masyarakat
    $result = mysqli_query($koneksi, "SELECT nik, nama, username, telp FROM masyarakat");
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['data_masyarakat'][] = [
            'nik' => htmlspecialchars($row['nik']),
            'nama' => htmlspecialchars($row['nama']),
            'username' => htmlspecialchars($row['username']),
            'telp' => htmlspecialchars($row['telp'])
        ];
    }

    // Data petugas
    $result = mysqli_query($koneksi, "SELECT id_petugas, nama_petugas, username, telp, status FROM petugas");
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['data_petugas'][] = [
            'id_petugas' => htmlspecialchars($row['id_petugas']),
            'nama_petugas' => htmlspecialchars($row['nama_petugas']),
            'username' => htmlspecialchars($row['username']),
            'telp' => htmlspecialchars($row['telp']),
            'status' => htmlspecialchars($row['status'])
        ];
    }

    // Data pengaduan untuk tabel
    $result = mysqli_query($koneksi, "SELECT p.id_pengaduan, m.nama, p.isi_laporan, p.tgl_pengaduan, p.status FROM pengaduan p JOIN masyarakat m ON p.nik = m.nik ORDER BY p.tgl_pengaduan DESC LIMIT 10");
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['data_pengaduan'][] = [
            'id_pengaduan' => htmlspecialchars($row['id_pengaduan']),
            'nama' => htmlspecialchars($row['nama']),
            'isi_laporan' => htmlspecialchars($row['isi_laporan']),
            'tgl_pengaduan' => $row['tgl_pengaduan'],
            'status' => $row['status']
        ];
    }

    // Data tanggapan untuk tabel
    $result = mysqli_query($koneksi, "SELECT t.id_tanggapan, p.id_pengaduan, m.nama, t.tanggapan, t.tgl_tanggapan FROM tanggapan t JOIN pengaduan p ON t.id_pengaduan = p.id_pengaduan JOIN masyarakat m ON p.nik = m.nik ORDER BY t.tgl_tanggapan DESC LIMIT 10");
    while ($row = mysqli_fetch_assoc($result)) {
        $stats['data_tanggapan'][] = [
            'id_tanggapan' => htmlspecialchars($row['id_tanggapan']),
            'id_pengaduan' => htmlspecialchars($row['id_pengaduan']),
            'nama' => htmlspecialchars($row['nama']),
            'tanggapan' => htmlspecialchars($row['tanggapan']),
            'tgl_tanggapan' => $row['tgl_tanggapan']
        ];
    }

    return $stats;
}

// Proses CRUD dan Delete via AJAX
if (
    isset($_POST['action']) &&
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    function sanitize($koneksi, $input) {
        return mysqli_real_escape_string($koneksi, trim($input));
    }

    $action = $_POST['action'];

    if ($action === 'create_masyarakat') {
        $nik = sanitize($koneksi, $_POST['nik']);
        $nama = sanitize($koneksi, $_POST['nama']);
        $username = sanitize($koneksi, $_POST['username']);
        $telp = sanitize($koneksi, $_POST['telp']);
        $password = sanitize($koneksi, $_POST['password']);

        if (empty($nik) || empty($nama) || empty($username) || empty($telp) || empty($password)) {
            $response['message'] = "Semua field wajib diisi.";
            echo json_encode($response);
            exit;
        }
        $cekNik = mysqli_query($koneksi, "SELECT nik FROM masyarakat WHERE nik='$nik'");
        if (mysqli_num_rows($cekNik) > 0) {
            $response['message'] = "NIK sudah terdaftar.";
            echo json_encode($response);
            exit;
        }
        $cekUser  = mysqli_query($koneksi, "SELECT username FROM masyarakat WHERE username='$username'");
        if (mysqli_num_rows($cekUser ) > 0) {
            $response['message'] = "Username sudah digunakan.";
            echo json_encode($response);
            exit;
        }
        $query = "INSERT INTO masyarakat (nik, nama, username, password, telp) VALUES ('$nik', '$nama', '$username', '$password', '$telp')";
        if (mysqli_query($koneksi, $query)) {
            $response['success'] = true;
            $response['message'] = "Data masyarakat berhasil ditambahkan.";
        } else {
            $response['message'] = "Gagal menambah data: " . mysqli_error($koneksi);
        }
        echo json_encode($response);
        exit;
    }

    if ($action === 'update_masyarakat') {
        $nik = sanitize($koneksi, $_POST['nik']);
        $nama = sanitize($koneksi, $_POST['nama']);
        $username = sanitize($koneksi, $_POST['username']);
        $telp = sanitize($koneksi, $_POST['telp']);
        $password = isset($_POST['password']) ? sanitize($koneksi, $_POST['password']) : '';

        if (empty($nik) || empty($nama) || empty($username) || empty($telp)) {
            $response['message'] = "Semua field wajib diisi.";
            echo json_encode($response);
            exit;
        }

        $password_sql = $password !== '' ? ", password = '$password'" : "";

        $query = "UPDATE masyarakat SET nama = '$nama', username = '$username', telp = '$telp' $password_sql WHERE nik = '$nik'";
        if (mysqli_query($koneksi, $query)) {
            $response['success'] = true;
            $response['message'] = "Data masyarakat berhasil diperbarui.";
        } else {
            $response['message'] = "Gagal memperbarui data: " . mysqli_error($koneksi);
        }
        echo json_encode($response);
        exit;
    }

    if ($action === 'delete_masyarakat') {
        $nik = sanitize($koneksi, $_POST['nik']);
        $query = "DELETE FROM masyarakat WHERE nik = '$nik'";
        if (mysqli_query($koneksi, $query)) {
            $response['success'] = true;
            $response['message'] = "Data masyarakat berhasil dihapus.";
        } else {
            $response['message'] = "Gagal menghapus data: ".mysqli_error($koneksi);
        }
        echo json_encode($response);
        exit;
    }

    if ($action === 'create_petugas') {
        $nama_petugas = sanitize($koneksi, $_POST['nama_petugas']);
        $username = sanitize($koneksi, $_POST['username']);
        $telp = sanitize($koneksi, $_POST['telp']);
        $status = sanitize($koneksi, $_POST['status']);
        $password = sanitize($koneksi, $_POST['password']);

        if (empty($nama_petugas) || empty($username) || empty($telp) || empty($status) || empty($password)) {
            $response['message'] = "Semua field wajib diisi.";
            echo json_encode($response);
            exit;
        }

        $cekUser  = mysqli_query($koneksi, "SELECT username FROM petugas WHERE username='$username'");
        if (mysqli_num_rows($cekUser ) > 0) {
            $response['message'] = "Username sudah digunakan.";
            echo json_encode($response);
            exit;
        }

        $query = "INSERT INTO petugas (nama_petugas, username, telp, status, password) 
                  VALUES ('$nama_petugas', '$username', '$telp', '$status', '$password')";
        if (mysqli_query($koneksi, $query)) {
            $response['success'] = true;
            $response['message'] = "Data petugas berhasil ditambahkan.";
        } else {
            $response['message'] = "Gagal menambah data: " . mysqli_error($koneksi);
        }
        echo json_encode($response);
        exit;
    }

    if ($action === 'update_petugas') {
        $id_petugas = sanitize($koneksi, $_POST['id_petugas']);
        $nama_petugas = sanitize($koneksi, $_POST['nama_petugas']);
        $username = sanitize($koneksi, $_POST['username']);
        $telp = sanitize($koneksi, $_POST['telp']);
        $status = sanitize($koneksi, $_POST['status']);
        $password = isset($_POST['password']) ? sanitize($koneksi, $_POST['password']) : '';

        if (empty($id_petugas) || empty($nama_petugas) || empty($username) || empty($telp) || empty($status)) {
            $response['message'] = "Semua field wajib diisi.";
            echo json_encode($response);
            exit;
        }

        $password_sql = $password !== '' ? ", password = '$password'" : "";

        $query = "UPDATE petugas SET nama_petugas = '$nama_petugas', username = '$username', telp = '$telp', status = '$status' $password_sql WHERE id_petugas = '$id_petugas'";
        if (mysqli_query($koneksi, $query)) {
            $response['success'] = true;
            $response['message'] = "Data petugas berhasil diperbarui.";
        } else {
            $response['message'] = "Gagal memperbarui data: " . mysqli_error($koneksi);
        }
        echo json_encode($response);
        exit;
    }

    if ($action === 'delete_petugas') {
        $id_petugas = sanitize($koneksi, $_POST['id_petugas']);
        $query = "DELETE FROM petugas WHERE id_petugas = '$id_petugas'";
        if (mysqli_query($koneksi, $query)) {
            $response['success'] = true;
            $response['message'] = "Data petugas berhasil dihapus.";
        } else {
            $response['message'] = "Gagal menghapus data: ".mysqli_error($koneksi);
        }
        echo json_encode($response);
        exit;
    }

    if ($action === 'delete_pengaduan') {
        $id_pengaduan = sanitize($koneksi, $_POST['id_pengaduan']);
        $query_tanggapan = "DELETE FROM tanggapan WHERE id_pengaduan = '$id_pengaduan'";
        $query_pengaduan = "DELETE FROM pengaduan WHERE id_pengaduan = '$id_pengaduan'";
        if (mysqli_query($koneksi, $query_tanggapan) && mysqli_query($koneksi, $query_pengaduan)) {
            $response['success'] = true;
            $response['message'] = "Pengaduan berhasil dihapus.";
        } else {
            $response['message'] = "Gagal menghapus pengaduan: ".mysqli_error($koneksi);
        }
        echo json_encode($response);
        exit;
    }

    if ($action === 'delete_tanggapan') {
        $id_tanggapan = sanitize($koneksi, $_POST['id_tanggapan']);
        $query = "DELETE FROM tanggapan WHERE id_tanggapan = '$id_tanggapan'";
        if (mysqli_query($koneksi, $query)) {
            $response['success'] = true;
            $response['message'] = "Tanggapan berhasil dihapus.";
        } else {
            $response['message'] = "Gagal menghapus tanggapan: ".mysqli_error($koneksi);
        }
        echo json_encode($response);
        exit;
    }

    echo json_encode($response);
    exit;
}

$stats = getStats($koneksi);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Admin</title>
    <link rel="icon" type="image/ico" href="images/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --danger: #ef4444;
            --success: #10b981;
            --border-radius: 12px;
            --shadow: rgba(0, 0, 0, 0.8);
        }
        * {
            margin: 0; padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .navbar {
            background-color: var(--card);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
            box-shadow: 0 5px 15px var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1100;
        }
        .navbar span {
            font-weight: 600;
            font-size: 1.25rem;
        }
        .navbar a {
            color: var(--text);
            font-weight: 600;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: var(--border-radius);
            background-color: var(--accent);
            box-shadow: 0 4px 14px var(--accent);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        .navbar a:hover {
            background-color: #2563eb;
            box-shadow: 0 6px 20px #2563eb;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto 60px;
            padding: 0 20px;
        }
        h1 {
            font-weight: 700;
            font-size: 2.8rem;
            margin-bottom: 32px;
            color: var(--accent);
            text-shadow: 0 3px 6px rgba(59,130,246,0.7);
            user-select: none;
        }
        h2 {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: var(--accent);
            user-select: none;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 48px;
            max-width: 720px;
            margin-left: auto;
            margin-right: auto;
        }
        .stat-card {
            background-color: var(--card);
            padding: 26px 22px;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 24px var(--shadow);
            text-align: center;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: var(--text-muted);
            user-select: none;
            cursor: default;
            transition: box-shadow 0.4s ease;
        }
        .stat-card:hover {
            box-shadow: 0 10px 30px var(--accent);
        }
        .stat-card .icon {
            font-size: 2.5rem;
            width: 60px;
            height: 60px;
            background-color: rgba(59, 130, 246, 0.15);
            border-radius: 50%;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-card h3 {
            font-weight: 700;
            font-size: 1.15rem;
            margin-bottom: 8px;
            color: var(--text-muted);
        }
        .stat-card .number {
            font-weight: 700;
            font-size: 2.1rem;
            color: var(--accent);
        }
        section {
            margin-bottom: 56px;
        }
        .scrollable-table {
            max-height: 380px;
            overflow-y: auto;
            border-radius: var(--border-radius);
            box-shadow: 0 8px 20px var(--shadow);
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 12px;
        }
        th, td {
            padding: 14px 18px;
            background-color: var(--card);
            color: var(--text);
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: transparent;
            color: var(--accent);
            font-weight: 700;
            font-size: 1rem;
            user-select: none;
        }
        tr td:first-child,
        tr th:first-child {
            border-top-left-radius: var(--border-radius);
            border-bottom-left-radius: var(--border-radius);
        }
        tr td:last-child,
        tr th:last-child {
            border-top-right-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
        }
        tr {
            box-shadow: 0 1px 5px var(--shadow);
            transition: background-color 0.25s ease;
        }
        tr:hover {
            background-color: #334155;
        }
        .btn {
            display: inline-block;
            padding: 9px 22px;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            user-select: none;
            border: none;
            outline: none;
            color: var(--text);
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.6);
            background-color: var(--accent);
        }
        .btn:hover {
            background-color: #2563eb;
            box-shadow: 0 6px 20px #2563eb;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background-color: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
            box-shadow: none;
        }
        .btn-secondary:hover {
            color: var(--text);
            background-color: var(--accent);
            box-shadow: 0 6px 20px #2563eb;
            transform: translateY(-2px);
        }
        .btn-danger {
            background-color: var(--danger);
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.6);
        }
        .btn-danger:hover {
            background-color: #dc2626;
            box-shadow: 0 6px 20px #ef4444;
            transform: translateY(-2px);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1200;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.85);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background-color: var(--card);
            color: var(--text);
            border-radius: var(--border-radius);
            padding: 28px 32px;
            width: 90%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            box-shadow: 0 8px 28px var(--shadow);
            animation: slideIn 0.3s ease forwards;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .modal-header {
            margin-bottom: 26px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            font-weight: 700;
            font-size: 1.5rem;
            user-select: none;
        }
        .modal-close {
            cursor: pointer;
            font-size: 1.9rem;
            color: var(--text-muted);
            border: none;
            background: transparent;
            font-weight: 700;
            line-height: 1;
            padding: 0;
            transition: color 0.25s ease;
        }
        .modal-close:hover {
            color: var(--accent);
        }
        label {
            display: block;
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 6px;
            user-select: none;
        }
        input[type="text"],
        input[type="password"],
        select {
            display: block;
            width: 100%;
            padding: 12px 14px;
            font-size: 1.1rem;
            border: 1.5px solid transparent;
            border-radius: var(--border-radius);
            color: var(--text);
            background-color: #273549;
            outline-offset: 4px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 10px var(--accent);
            background-color: #1e293b;
        }
        form div {
            margin-bottom: 16px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 16px;
            margin-top: 12px;
        }
        .scrollable-table::-webkit-scrollbar {
            width: 10px;
        }
        .scrollable-table::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollable-table::-webkit-scrollbar-thumb {
            background-color: var(--accent);
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                max-width: 100%;
            }
            .scrollable-table {
                max-height: 280px;
            }
            th, td {
                font-size: 14px;
                padding: 10px 14px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar" role="banner">
        <span>👑 Selamat datang, <?php echo $_SESSION['nama_petugas']; ?> (Admin)</span>
        <a href="logout.php" role="link" aria-label="Logout">Logout</a>
    </div>

    <main class="container">
        <!-- Statistik 3x3 center -->
        <section class="stats-grid" aria-label="Statistik Pengaduan">
            <div class="stat-card" role="region" aria-labelledby="totalPengaduanTitle">
                <div class="icon" aria-hidden="true">📋</div>
                <h3 id="totalPengaduanTitle">Total Pengaduan</h3>
                <div class="number" id="total-pengaduan"><?php echo $stats['total_pengaduan']; ?></div>
            </div>
            <div class="stat-card" role="region" aria-labelledby="pendingTitle">
                <div class="icon" aria-hidden="true">⏳</div>
                <h3 id="pendingTitle">Pending</h3>
                <div class="number" id="pending"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card" role="region" aria-labelledby="prosesTitle">
                <div class="icon" aria-hidden="true">🔄</div>
                <h3 id="prosesTitle">Proses</h3>
                <div class="number" id="proses"><?php echo $stats['proses']; ?></div>
            </div>
            <div class="stat-card" role="region" aria-labelledby="selesaiTitle">
                <div class="icon" aria-hidden="true">✅</div>
                <h3 id="selesaiTitle">Selesai</h3>
                <div class="number" id="selesai"><?php echo $stats['selesai']; ?></div>
            </div>
            <div class="stat-card" role="region" aria-labelledby="masyarakatTitle">
                <div class="icon" aria-hidden="true">👥</div>
                <h3 id="masyarakatTitle">Jumlah Masyarakat</h3>
                <div class="number" id="total-masyarakat"><?php echo $stats['total_masyarakat']; ?></div>
            </div>
            <div class="stat-card" role="region" aria-labelledby="petugasTitle">
                <div class="icon" aria-hidden="true">👮</div>
                <h3 id="petugasTitle">Jumlah Petugas</h3>
                <div class="number" id="total-petugas"><?php echo $stats['total_petugas']; ?></div>
            </div>
            <div></div>
            <div></div>
            <div></div>
        </section>

        <!-- Section Data Masyarakat -->
        <section aria-label="Data Masyarakat">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2>Data Masyarakat</h2>
                <button class="btn" onclick="openModal('masyarakatModal', 'create')">Tambah Masyarakat</button>
            </div>
            <div class="scrollable-table" role="region" aria-labelledby="tableMasyarakat">
                <table aria-describedby="tableMasyarakat" role="grid" id="table-masyarakat" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th>NIK</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Telp</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['data_masyarakat'] as $masyarakat): ?>
                        <tr data-nik="<?php echo $masyarakat['nik']; ?>">
                            <td><?php echo $masyarakat['nik']; ?></td>
                            <td><?php echo $masyarakat['nama']; ?></td>
                            <td><?php echo $masyarakat['username']; ?></td>
                            <td><?php echo $masyarakat['telp']; ?></td>
                            <td>
                                <button class="btn btn-secondary" onclick='openModal("masyarakatModal", "edit", <?php echo json_encode($masyarakat); ?>)'>Edit</button>
                                <button class="btn btn-danger" onclick='confirmDelete("masyarakat", "<?php echo $masyarakat['nik']; ?>")'>Hapus</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Section Data Petugas -->
        <section aria-label="Data Petugas">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2>Data Petugas</h2>
                <button class="btn" onclick="openModal('petugasModal', 'create')">Tambah Petugas</button>
            </div>
            <div class="scrollable-table" role="region" aria-labelledby="tablePetugas">
                <table aria-describedby="tablePetugas" role="grid" id="table-petugas" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th>ID Petugas</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Telp</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['data_petugas'] as $petugas): ?>
                        <tr data-id_petugas="<?php echo $petugas['id_petugas']; ?>">
                            <td><?php echo $petugas['id_petugas']; ?></td>
                            <td><?php echo $petugas['nama_petugas']; ?></td>
                            <td><?php echo $petugas['username']; ?></td>
                            <td><?php echo $petugas['telp']; ?></td>
                            <td><?php echo ucfirst($petugas['status']); ?></td>
                            <td>
                                <button class="btn btn-secondary" onclick='openModal("petugasModal", "edit", <?php echo json_encode($petugas); ?>)'>Edit</button>
                                <button class="btn btn-danger" onclick='confirmDelete("petugas", "<?php echo $petugas['id_petugas']; ?>")'>Hapus</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Section Data Pengaduan -->
        <section aria-label="Data Pengaduan">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2>Data Pengaduan</h2>
            </div>
            <div class="scrollable-table" role="region" aria-labelledby="tablePengaduan">
                <table aria-describedby="tablePengaduan" role="grid" id="table-pengaduan" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Masyarakat</th>
                            <th>Isi Laporan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stats['data_pengaduan'] as $pengaduan): ?>
                        <tr data-id_pengaduan="<?php echo $pengaduan['id_pengaduan']; ?>">
                            <td><?php echo $pengaduan['id_pengaduan']; ?></td>
                            <td><?php echo $pengaduan['nama']; ?></td>
                            <td><?php echo $pengaduan['isi_laporan']; ?></td>
                            <td><?php echo $pengaduan['tgl_pengaduan']; ?></td>
                            <td><?php echo ucfirst($pengaduan['status']); ?></td>
                            <td>
                                <button class="btn btn-danger" onclick='confirmDelete("pengaduan", "<?php echo $pengaduan['id_pengaduan']; ?>")'>Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Section Data Tanggapan -->
        <section aria-label="Data Tanggapan">
            <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h2>Data Tanggapan</h2>
            </div>
            <div class="scrollable-table" role="region" aria-labelledby="tableTanggapan">
                <table aria-describedby="tableTanggapan" role="grid" id="table-tanggapan" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th>ID Tanggapan</th>
                            <th>ID Pengaduan</th>
                            <th>Nama Masyarakat</th>
                            <th>Tanggapan</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stats['data_tanggapan'] as $tanggapan): ?>
                        <tr data-id_tanggapan="<?php echo $tanggapan['id_tanggapan']; ?>">
                            <td><?php echo $tanggapan['id_tanggapan']; ?></td>
                            <td><?php echo $tanggapan['id_pengaduan']; ?></td>
                            <td><?php echo $tanggapan['nama']; ?></td>
                            <td><?php echo $tanggapan['tanggapan']; ?></td>
                            <td><?php echo $tanggapan['tgl_tanggapan']; ?></td>
                            <td>
                                <button class="btn btn-danger" onclick='confirmDelete("tanggapan", "<?php echo $tanggapan['id_tanggapan']; ?>")'>Hapus</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Modal Masyarakat -->
    <div class="modal" id="masyarakatModal" role="dialog" aria-modal="true" aria-labelledby="masyarakatModalTitle" tabindex="-1">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="masyarakatModalTitle">Tambah Masyarakat</h3>
                <button class="modal-close" aria-label="Tutup modal" onclick="closeModal('masyarakatModal')">&times;</button>
            </div>
            <form id="masyarakatForm" autocomplete="off">
                <input type="hidden" id="masyarakatFormMode" name="mode" value="create" />
                <div>
                    <label for="masyarakatNIK">NIK</label>
                    <input type="text" id="masyarakatNIK" name="nik" maxlength="16" required pattern="[0-9]{16}" title="NIK harus 16 digit angka" autocomplete="off" />
                </div>
                <div>
                    <label for="masyarakatNama">Nama</label>
                    <input type="text" id="masyarakatNama" name="nama" maxlength="35" required autocomplete="off" />
                </div>
                <div>
                    <label for="masyarakatUsername">Username</label>
                    <input type="text" id="masyarakatUsername" name="username" maxlength="25" required autocomplete="off" />
                </div>
                <div>
                    <label for="masyarakatTelp">Telp</label>
                    <input type="text" id="masyarakatTelp" name="telp" maxlength="13" required pattern="[0-9]+" title="Harus angka" autocomplete="off" />
                </div>
                <div>
                    <label for="masyarakatPassword">Password <small>(isi jika ingin mengubah)</small></label>
                    <input type="password" id="masyarakatPassword" name="password" maxlength="32" autocomplete="new-password" />
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('masyarakatModal')">Batal</button>
                    <button type="submit" class="btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Petugas -->
    <div class="modal" id="petugasModal" role="dialog" aria-modal="true" aria-labelledby="petugasModalTitle" tabindex="-1">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="petugasModalTitle">Tambah Petugas</h3>
                <button class="modal-close" aria-label="Tutup modal" onclick="closeModal('petugasModal')">&times;</button>
            </div>
            <form id="petugasForm" autocomplete="off">
                <input type="hidden" id="petugasFormMode" name="mode" value="create" />
                <input type="hidden" id="petugasId" name="id_petugas" />
                <div>
                    <label for="petugasNama">Nama</label>
                    <input type="text" id="petugasNama" name="nama_petugas" maxlength="35" required autocomplete="off" />
                </div>
                <div>
                    <label for="petugasUsername">Username</label>
                    <input type="text" id="petugasUsername" name="username" maxlength="25" required autocomplete="off" />
                </div>
                <div>
                    <label for="petugasTelp">Telp</label>
                    <input type="text" id="petugasTelp" name="telp" maxlength="13" required pattern="[0-9]+" title="Harus angka" autocomplete="off" />
                </div>
                <div>
                    <label for="petugasStatus">Status</label>
                    <select id="petugasStatus" name="status" required>
                        <option value="">-- Pilih Status --</option>
                        <option value="admin">Admin</option>
                        <option value="petugas">Petugas</option>
                    </select>
                </div>
                <div>
                    <label for="petugasPassword">Password <small>(isi jika ingin mengubah)</small></label>
                    <input type="password" id="petugasPassword" name="password" maxlength="32" autocomplete="new-password" />
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('petugasModal')">Batal</button>
                    <button type="submit" class="btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId, mode, data = null) {
            const modal = document.getElementById(modalId);
            const form = modal.querySelector('form');
            modal.classList.add('active');
            modal.focus();

            if (modalId === 'masyarakatModal') {
                document.getElementById('masyarakatFormMode').value = mode;
                const title = document.getElementById('masyarakatModalTitle');
                if (mode === 'create') {
                    title.textContent = 'Tambah Masyarakat';
                    form.reset();
                    document.getElementById('masyarakatNIK').removeAttribute('readonly');
                } else {
                    title.textContent = 'Edit Masyarakat';
                    form.reset();
                    if (data) {
                        document.getElementById('masyarakatNIK').value = data.nik;
                        document.getElementById('masyarakatNIK').setAttribute('readonly', true);
                        document.getElementById('masyarakatNama').value = data.nama;
                        document.getElementById('masyarakatUsername').value = data.username;
                        document.getElementById('masyarakatTelp').value = data.telp;
                        document.getElementById('masyarakatPassword').value = '';
                    }
                }
            }

            if (modalId === 'petugasModal') {
                document.getElementById('petugasFormMode').value = mode;
                const title = document.getElementById('petugasModalTitle');
                if (mode === 'create') {
                    title.textContent = 'Tambah Petugas';
                    form.reset();
                    document.getElementById('petugasId').value = '';
                } else {
                    title.textContent = 'Edit Petugas';
                    form.reset();
                    if (data) {
                        document.getElementById('petugasId').value = data.id_petugas;
                        document.getElementById('petugasNama').value = data.nama_petugas;
                        document.getElementById('petugasUsername').value = data.username;
                        document.getElementById('petugasTelp').value = data.telp;
                        document.getElementById('petugasStatus').value = data.status;
                        document.getElementById('petugasPassword').value = '';
                    }
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
        }

        async function sendData(formData) {
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await response.json();
                return data;
            } catch (err) {
                console.error('Request failed', err);
                return { success: false, message: 'Request error' };
            }
        }

        document.getElementById('masyarakatForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const mode = document.getElementById('masyarakatFormMode').value;
            const formData = new FormData(e.target);
            formData.set('action', mode === 'create' ? 'create_masyarakat' : 'update_masyarakat');

            const result = await sendData(formData);
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: result.message,
                    position: 'center',
                    toast: true,
                    timer: 2500,
                    showConfirmButton: false,
                    timerProgressBar: true,
                    backdrop: false,
                    didOpen: (toast) => {
                        toast.style.zIndex = 13000;
                    }
                });
                closeModal('masyarakatModal');
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire('Gagal', result.message, 'error');
            }
        });

        document.getElementById('petugasForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const mode = document.getElementById('petugasFormMode').value;
            const formData = new FormData(e.target);
            formData.set('action', mode === 'create' ? 'create_petugas' : 'update_petugas');

            const result = await sendData(formData);
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: result.message,
                    position: 'center',
                    toast: true,
                    timer: 2500,
                    showConfirmButton: false,
                    timerProgressBar: true,
                    backdrop: false,
                    didOpen: (toast) => {
                        toast.style.zIndex = 13000;
                    }
                });
                closeModal('petugasModal');
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire('Gagal', result.message, 'error');
            }
        });

        function confirmDelete(type, id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#3b82f6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    let action = '', field = '', value = '';
                    if (type === 'masyarakat') {
                        action = 'delete_masyarakat';
                        field = 'nik';
                        value = id;
                    } else if (type === 'petugas') {
                        action = 'delete_petugas';
                        field = 'id_petugas';
                        value = id;
                    } else if (type === 'pengaduan') {
                        action = 'delete_pengaduan';
                        field = 'id_pengaduan';
                        value = id;
                    } else if (type === 'tanggapan') {
                        action = 'delete_tanggapan';
                        field = 'id_tanggapan';
                        value = id;
                    }
                    formData.append('action', action);
                    formData.append(field, value);
                    const response = await sendData(formData);
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message,
                            position: 'center',
                            toast: true,
                            timer: 2500,
                            showConfirmButton: false,
                            timerProgressBar: true,
                            backdrop: false,
                            didOpen: (toast) => {
                                toast.style.zIndex = 13000;
                            }
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', response.message, 'error');
                    }
                }
            });
        }

        window.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                ['masyarakatModal', 'petugasModal'].forEach(id => {
                    const modal = document.getElementById(id);
                    if (modal.classList.contains('active')) {
                        closeModal(id);
                    }
                });
            }
        });

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal(modal.id);
                }
            });
        });
    </script>
</body>
</html>
