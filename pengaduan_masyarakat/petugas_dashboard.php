<?php
session_start();
include 'koneksi.php';

// Pastikan hanya petugas yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas' || $_SESSION['level'] === 'admin') {
    header("Location: login.php");
    exit;
}

// Proses update status via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['id_pengaduan']) && isset($_POST['status'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];

    $id_pengaduan = mysqli_real_escape_string($koneksi, $_POST['id_pengaduan']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    $status = ($status === 'Pending') ? '0' : strtolower($status); // Ubah 'Pending' jadi '0'

    $query = "UPDATE pengaduan SET status = '$status' WHERE id_pengaduan = '$id_pengaduan'";
    if (mysqli_query($koneksi, $query)) {
        $response['success'] = true;
    }

    echo json_encode($response);
    exit;
}

// Proses hapus pengaduan via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'delete_pengaduan' && isset($_POST['id_pengaduan'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];

    $id_pengaduan = mysqli_real_escape_string($koneksi, $_POST['id_pengaduan']);
    $query = "DELETE FROM pengaduan WHERE id_pengaduan = '$id_pengaduan'";
    if (mysqli_query($koneksi, $query)) {
        $response['success'] = true;
    }

    echo json_encode($response);
    exit;
}

// Ambil data pengaduan beserta tanggapan
$query = "SELECT p.*, m.nama, t.id_tanggapan, t.tanggapan 
          FROM pengaduan p 
          JOIN masyarakat m ON p.nik = m.nik 
          LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
          ORDER BY p.tgl_pengaduan DESC";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas</title>
    <link rel="icon" type="image/ico" href="images/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        .navbar {
            background-color: var(--card);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar span {
            font-weight: 500;
        }

        .navbar a {
            color: var(--text);
            background-color: var(--accent);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .navbar a:hover {
            background-color: #2563eb;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .card {
            background-color: var(--card);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        h2 {
            font-size: 22px;
            margin-bottom: 15px;
            color: var(--accent);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border: 1px solid #334155;
            text-align: left;
        }

        th {
            background-color: var(--accent);
            color: white;
        }

        tr:nth-child(even) {
            background-color: #273549;
        }

        .thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
        }

        .action-btn, .edit-btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
            margin-right: 5px;
        }

        .action-btn:hover, .edit-btn:hover {
            background-color: #2563eb;
        }

        .delete-btn {
            background-color: var(--danger);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .delete-btn:hover {
            background-color: #dc2626;
        }

        .status-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #334155;
            background-color: #273549;
            color: var(--text);
            cursor: pointer;
        }

        .fade-in {
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            table {
                font-size: 14px;
            }
            .thumbnail {
                width: 40px;
                height: 40px;
            }
            .action-btn, .edit-btn, .delete-btn, .status-select {
                padding: 4px 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <span>👷 Selamat datang, <?php echo $_SESSION['nama_petugas']; ?> (Petugas)</span>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <div class="card fade-in">
            <h2>Daftar Pengaduan</h2>
            <table id="pengaduan-table">
                <tr>
                    <th>Foto</th>
                    <th>Tanggal</th>
                    <th>Pengadu</th>
                    <th>Isi Laporan</th>
                    <th>Isi Tanggapan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <?php if (!empty($row['foto'])): ?>
                                <a href="uploads/<?php echo htmlspecialchars($row['foto']); ?>" target="_blank">
                                    <img src="uploads/<?php echo htmlspecialchars($row['foto']); ?>" alt="Foto Pengaduan" class="thumbnail">
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['tgl_pengaduan']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama']); ?></td>
                        <td><?php echo htmlspecialchars($row['isi_laporan']); ?></td>
                        <td><?php echo !empty($row['tanggapan']) ? htmlspecialchars($row['tanggapan']) : '-'; ?></td>
                        <td>
                            <select class="status-select" onchange="updateStatus('<?php echo $row['id_pengaduan']; ?>', this.value)">
                                <option value="Pending" <?php echo $row['status'] == '0' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Proses" <?php echo $row['status'] == 'proses' ? 'selected' : ''; ?>>Proses</option>
                                <option value="Selesai" <?php echo $row['status'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            </select>
                        </td>
                        <td>
                            <?php if (!empty($row['tanggapan'])): ?>
                                <a href="tanggapan.php?id=<?php echo $row['id_pengaduan']; ?>&edit=1&id_tanggapan=<?php echo $row['id_tanggapan']; ?>" class="edit-btn">Edit Tanggapan</a>
                            <?php else: ?>
                                <a href="tanggapan.php?id=<?php echo $row['id_pengaduan']; ?>" class="action-btn">Tanggapi</a>
                            <?php endif; ?>
                            <button class="delete-btn" onclick="confirmDelete('<?php echo $row['id_pengaduan']; ?>')">Hapus</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <script>
        function updateStatus(idPengaduan, status) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=update_status&id_pengaduan=${encodeURIComponent(idPengaduan)}&status=${encodeURIComponent(status)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil!', 'Status pengaduan telah diperbarui.', 'success');
                } else {
                    Swal.fire('Gagal!', 'Terjadi kesalahan saat memperbarui status.', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Gagal!', 'Terjadi kesalahan saat memperbarui status.', 'error');
                console.error('Error updating status:', err);
            });
        }

        function confirmDelete(idPengaduan) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#3b82f6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=delete_pengaduan&id_pengaduan=${encodeURIComponent(idPengaduan)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Berhasil!', 'Data telah dihapus.', 'success');
                            location.reload(); // Reload halaman untuk refresh tabel
                        } else {
                            Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error');
                        console.error('Error deleting data:', err);
                    });
                }
            });
        }
    </script>
</body>
</html>