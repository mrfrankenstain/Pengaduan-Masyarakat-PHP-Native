<?php
session_start();
include 'koneksi.php';

// Pastikan hanya masyarakat yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'masyarakat') {
    header("Location: login.php");
    exit;
}

// Ambil data pengaduan milik masyarakat yang login dengan tanggapan
$nik = $_SESSION['nik'];
$query = "SELECT p.*, t.tanggapan 
          FROM pengaduan p 
          LEFT JOIN tanggapan t ON p.id_pengaduan = t.id_pengaduan 
          WHERE p.nik = '$nik' ORDER BY p.tgl_pengaduan DESC";
$result = mysqli_query($koneksi, $query);

// Proses update pengaduan via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'update_pengaduan' && isset($_POST['id_pengaduan'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $id_pengaduan = mysqli_real_escape_string($koneksi, $_POST['id_pengaduan']);
    $isi_laporan = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    $nik = $_SESSION['nik'];

    // Cek apakah pengaduan milik masyarakat yang login
    $query_check = "SELECT * FROM pengaduan WHERE id_pengaduan = '$id_pengaduan' AND nik = '$nik'";
    $result_check = mysqli_query($koneksi, $query_check);
    if (mysqli_num_rows($result_check) === 0) {
        $response['message'] = 'Pengaduan tidak ditemukan atau Anda tidak memiliki akses untuk mengedit.';
        echo json_encode($response);
        exit;
    }

    $pengaduan = mysqli_fetch_assoc($result_check);
    $foto_lama = $pengaduan['foto'];

    // Proses upload foto baru jika ada
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $foto_tmp = $_FILES['foto']['tmp_name'];
        $foto_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        if (in_array($foto_ext, $allowed_ext)) {
            $foto = uniqid() . '.' . $foto_ext;
            $foto_destination = 'uploads/' . $foto;
            if (move_uploaded_file($foto_tmp, $foto_destination)) {
                // Hapus foto lama jika ada
                if (!empty($foto_lama)) {
                    unlink('uploads/' . $foto_lama);
                }
            } else {
                $response['message'] = 'Gagal mengupload foto.';
                echo json_encode($response);
                exit;
            }
        } else {
            $response['message'] = 'Ekstensi file tidak diperbolehkan. Gunakan JPG, JPEG, atau PNG.';
            echo json_encode($response);
            exit;
        }
    } else {
        $foto = $foto_lama; // Gunakan foto lama jika tidak ada upload baru
    }

    // Update data pengaduan
    $query_update = "UPDATE pengaduan SET isi_laporan = '$isi_laporan'" . ($foto !== $foto_lama ? ", foto = '$foto'" : "") . " WHERE id_pengaduan = '$id_pengaduan'";
    if (mysqli_query($koneksi, $query_update)) {
        $response['success'] = true;
        $response['message'] = 'Pengaduan berhasil diperbarui.';
    } else {
        $response['message'] = 'Terjadi kesalahan saat memperbarui pengaduan.';
    }

    echo json_encode($response);
    exit;
}

// Proses buat pengaduan baru via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'create_pengaduan') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $isi_laporan = mysqli_real_escape_string($koneksi, $_POST['isi_laporan']);
    $nik = $_SESSION['nik'];
    $tgl_pengaduan = date('Y-m-d H:i:s');
    $foto = '';

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $foto_tmp = $_FILES['foto']['tmp_name'];
        $foto_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        if (in_array($foto_ext, $allowed_ext)) {
            $foto = uniqid() . '.' . $foto_ext;
            $foto_destination = 'uploads/' . $foto;
            if (!move_uploaded_file($foto_tmp, $foto_destination)) {
                $response['message'] = 'Gagal mengupload foto.';
                echo json_encode($response);
                exit;
            }
        } else {
            $response['message'] = 'Ekstensi file tidak diperbolehkan. Gunakan JPG, JPEG, atau PNG.';
            echo json_encode($response);
            exit;
        }
    }

    $query_insert = "INSERT INTO pengaduan (nik, tgl_pengaduan, isi_laporan, foto, status) 
                     VALUES ('$nik', '$tgl_pengaduan', '$isi_laporan', '$foto', '0')";
    if (mysqli_query($koneksi, $query_insert)) {
        $response['success'] = true;
        $response['message'] = 'Pengaduan berhasil dikirim.';
    } else {
        $response['message'] = 'Terjadi kesalahan saat mengirim pengaduan.';
    }

    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Masyarakat</title>
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

        .fade-in {
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--card);
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .modal-content h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--accent);
        }

        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .modal-content textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #334155;
            background-color: #273549;
            color: var(--text);
            resize: vertical;
            min-height: 120px;
        }

        .modal-content input[type="file"] {
            padding: 5px;
            color: var(--text);
        }

        .modal-content button {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .modal-content button:hover {
            background-color: #2563eb;
        }

        .modal-content .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .modal-content .close-btn:hover {
            color: var(--text);
        }

        /* Custom styling untuk SweetAlert */
        .swal2-popup {
            background-color: #1e293b !important;
            border-radius: 12px !important;
        }

        .swal2-confirm {
            background-color: #3b82f6 !important;
            color: #f1f5f9 !important;
            border-radius: 6px !important;
        }

        .swal2-confirm:hover {
            background-color: #2563eb !important;
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
            .action-btn, .edit-btn {
                padding: 4px 8px;
                font-size: 12px;
            }
            .modal-content {
                width: 95%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <span>👨‍💻 Selamat datang, <?php echo $_SESSION['nama']; ?> (Masyarakat)</span>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <div class="card fade-in">
            <h2>Daftar Pengaduan</h2>
            <table id="pengaduan-table">
                <tr>
                    <th>Foto</th>
                    <th>Tanggal</th>
                    <th>Isi Laporan</th>
                    <th>Isi Tanggapan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr data-id="<?php echo $row['id_pengaduan']; ?>">
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
                        <td><?php echo htmlspecialchars($row['isi_laporan']); ?></td>
                        <td><?php echo !empty($row['tanggapan']) ? htmlspecialchars($row['tanggapan']) : '-'; ?></td>
                        <td><?php echo $row['status'] == '0' ? 'Pending' : ucfirst($row['status']); ?></td>
                        <td>
                            <button class="edit-btn" onclick="openEditModal('<?php echo $row['id_pengaduan']; ?>', '<?php echo htmlspecialchars(addslashes($row['isi_laporan'])); ?>')">Edit</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <div class="card fade-in" style="margin-top: 20px;">
            <button class="action-btn" onclick="openCreateModal()">Buat Pengaduan</button>
        </div>
    </div>

    <!-- Modal untuk Edit Pengaduan -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('editModal')">×</span>
            <h3>Edit Pengaduan</h3>
            <form id="editForm" enctype="multipart/form-data">
                <input type="hidden" id="editIdPengaduan" name="id_pengaduan">
                <textarea id="editIsiLaporan" name="isi_laporan" required></textarea>
                <input type="file" id="editFoto" name="foto" accept="image/jpeg,image/png">
                <button type="submit">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <!-- Modal untuk Buat Pengaduan -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('createModal')">×</span>
            <h3>Buat Pengaduan Baru</h3>
            <form id="createForm" enctype="multipart/form-data">
                <textarea id="createIsiLaporan" name="isi_laporan" placeholder="Masukkan laporan Anda..." required></textarea>
                <input type="file" id="createFoto" name="foto" accept="image/jpeg,image/png">
                <button type="submit">Kirim Pengaduan</button>
            </form>
        </div>
    </div>

    <script>
        // Fungsi untuk membuka modal
        function openModal(modalId, idPengaduan = '', isiLaporan = '') {
            const modal = document.getElementById(modalId);
            if (modalId === 'editModal') {
                document.getElementById('editIdPengaduan').value = idPengaduan;
                document.getElementById('editIsiLaporan').value = isiLaporan;
            }
            modal.style.display = 'flex';
        }

        // Fungsi untuk menutup modal
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'none';
            document.getElementById(modalId === 'editModal' ? 'editForm' : 'createForm').reset();
        }

        // Submit form edit via AJAX
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            formData.append('action', 'update_pengaduan');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                closeModal('editModal');
                if (data.success) {
                    Swal.fire({
                        html: `
                            <div style="text-align: center; padding: 20px;">
                                <div style="width: 80px; height: 80px;
                                            background: rgba(16, 185, 129, 0.1);
                                            border-radius: 50%; display: flex;
                                            align-items: center; justify-content: center;
                                            margin: 0 auto 20px;">
                                    <span style="font-size: 2.5rem;">✅</span>
                                </div>
                                <h2 style="color: #f1f5f9; margin-bottom: 12px; font-size: 1.5rem;">
                                    Berhasil!
                                </h2>
                                <p style="color: #cbd5e1; margin-bottom: 0;">
                                    ${data.message}
                                </p>
                            </div>
                        `,
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'swal2-popup'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message || 'Terjadi kesalahan saat memperbarui pengaduan.',
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'swal2-popup',
                            confirmButton: 'swal2-confirm'
                        }
                    });
                }
            })
            .catch(err => {
                closeModal('editModal');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat memperbarui pengaduan.',
                    confirmButtonText: 'OK',
                    customClass: {
                        popup: 'swal2-popup',
                        confirmButton: 'swal2-confirm'
                    }
                });
                console.error('Error updating pengaduan:', err);
            });
        });

        // Submit form create via AJAX
        document.getElementById('createForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            formData.append('action', 'create_pengaduan');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                closeModal('createModal');
                if (data.success) {
                    Swal.fire({
                        html: `
                            <div style="text-align: center; padding: 20px;">
                                <div style="width: 80px; height: 80px;
                                            background: rgba(16, 185, 129, 0.1);
                                            border-radius: 50%; display: flex;
                                            align-items: center; justify-content: center;
                                            margin: 0 auto 20px;">
                                    <span style="font-size: 2.5rem;">✅</span>
                                </div>
                                <h2 style="color: #f1f5f9; margin-bottom: 12px; font-size: 1.5rem;">
                                    Berhasil!
                                </h2>
                                <p style="color: #cbd5e1; margin-bottom: 0;">
                                    ${data.message}
                                </p>
                            </div>
                        `,
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'swal2-popup'
                        }
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message || 'Terjadi kesalahan saat mengirim pengaduan.',
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'swal2-popup',
                            confirmButton: 'swal2-confirm'
                        }
                    });
                }
            })
            .catch(err => {
                closeModal('createModal');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat mengirim pengaduan.',
                    confirmButtonText: 'OK',
                    customClass: {
                        popup: 'swal2-popup',
                        confirmButton: 'swal2-confirm'
                    }
                });
                console.error('Error creating pengaduan:', err);
            });
        });

        // Tutup modal jika klik di luar modal
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target == modal) {
                    closeModal(modal.id);
                }
            }
        }

        // Buka modal create saat tombol "Buat Pengaduan" diklik
        function openCreateModal() {
            openModal('createModal');
        }

        // Buka modal edit saat tombol "Edit" diklik
        function openEditModal(idPengaduan, isiLaporan) {
            openModal('editModal', idPengaduan, isiLaporan);
        }
    </script>
</body>
</html>