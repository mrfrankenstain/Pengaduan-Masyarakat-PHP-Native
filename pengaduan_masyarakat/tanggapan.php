<?php
session_start();
include 'koneksi.php';

// Pastikan hanya petugas yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas' || $_SESSION['level'] === 'admin') {
    header("Location: login.php");
    exit;
}

// Pastikan ada ID pengaduan
if (!isset($_GET['id'])) {
    header("Location: petugas_dashboard.php");
    exit;
}

$id_pengaduan = mysqli_real_escape_string($koneksi, $_GET['id']);
$is_edit = isset($_GET['edit']) && $_GET['edit'] == '1';
$existing_tanggapan = '';
$id_tanggapan = isset($_GET['id_tanggapan']) ? mysqli_real_escape_string($koneksi, $_GET['id_tanggapan']) : null;

// Ambil data pengaduan
$query = "SELECT p.*, m.nama FROM pengaduan p JOIN masyarakat m ON p.nik = m.nik WHERE p.id_pengaduan = '$id_pengaduan'";
$result = mysqli_query($koneksi, $query);
$pengaduan = mysqli_fetch_assoc($result);

if (!$pengaduan) {
    header("Location: petugas_dashboard.php");
    exit;
}

// Jika mode edit, ambil data tanggapan
if ($is_edit && $id_tanggapan) {
    $query = "SELECT tanggapan FROM tanggapan WHERE id_tanggapan = '$id_tanggapan' AND id_pengaduan = '$id_pengaduan'";
    $result = mysqli_query($koneksi, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $existing_tanggapan = $row['tanggapan'];
    } else {
        header("Location: petugas_dashboard.php");
        exit;
    }
}

// Proses tanggapan (baik insert maupun update)
$alert_message = '';
if (isset($_POST['submit'])) {
    $tanggapan = mysqli_real_escape_string($koneksi, $_POST['tanggapan']);
    $id_petugas = $_SESSION['id_petugas'];
    $tgl_tanggapan = date('Y-m-d H:i:s');

    if ($is_edit && $id_tanggapan) {
        // Mode update tanggapan
        $query = "UPDATE tanggapan SET tanggapan = '$tanggapan', tgl_tanggapan = '$tgl_tanggapan' WHERE id_tanggapan = '$id_tanggapan'";
        if (mysqli_query($koneksi, $query)) {
            // Update status pengaduan ke "selesai"
            $update_status = "UPDATE pengaduan SET status = 'selesai' WHERE id_pengaduan = '$id_pengaduan'";
            mysqli_query($koneksi, $update_status);

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Tanggapan berhasil diperbarui'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui tanggapan'
            ];
        }
    } else {
        // Mode insert tanggapan baru
        $query = "INSERT INTO tanggapan (id_pengaduan, tgl_tanggapan, tanggapan, id_petugas) 
                  VALUES ('$id_pengaduan', '$tgl_tanggapan', '$tanggapan', '$id_petugas')";
        if (mysqli_query($koneksi, $query)) {
            // Update status pengaduan ke "selesai"
            $update_status = "UPDATE pengaduan SET status = 'selesai' WHERE id_pengaduan = '$id_pengaduan'";
            mysqli_query($koneksi, $update_status);

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Tanggapan berhasil dikirim'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => 'Terjadi kesalahan saat mengirim tanggapan'
            ];
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=$id_pengaduan" . ($is_edit ? "&edit=1&id_tanggapan=$id_tanggapan" : ""));
    exit;
}

// Ambil alert dari session jika ada
$alert = isset($_SESSION['alert']) ? $_SESSION['alert'] : null;
unset($_SESSION['alert']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Tanggapan' : 'Tanggapan Pengaduan'; ?></title>
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

        .container {
            max-width: 900px;
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
            margin-bottom: 20px;
            color: var(--accent);
            text-align: center;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .detail-item {
            padding: 10px;
            background-color: #273549;
            border-radius: 8px;
        }

        .detail-item p {
            margin: 5px 0;
            color: var(--text-muted);
        }

        .detail-item p strong {
            color: var(--text);
        }

        .detail-item img {
            max-width: 100%;
            border-radius: 6px;
            margin-top: 10px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #334155;
            background-color: #273549;
            color: var(--text);
            resize: vertical;
            min-height: 150px;
        }

        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        button, .back-btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        button:hover, .back-btn:hover {
            background-color: #2563eb;
        }

        .fade-in {
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
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
            .detail-grid {
                grid-template-columns: 1fr;
            }
            .detail-item {
                padding: 8px;
            }
            button, .back-btn {
                padding: 8px 16px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card fade-in">
            <h2><?php echo $is_edit ? 'Edit Tanggapan' : 'Tanggapan Pengaduan'; ?></h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <p><strong>Pengadu:</strong> <?php echo htmlspecialchars($pengaduan['nama']); ?></p>
                    <p><strong>Tanggal Pengaduan:</strong> <?php echo htmlspecialchars($pengaduan['tgl_pengaduan']); ?></p>
                </div>
                <div class="detail-item">
                    <p><strong>Isi Laporan:</strong> <?php echo htmlspecialchars($pengaduan['isi_laporan']); ?></p>
                </div>
                <?php if (!empty($pengaduan['foto'])): ?>
                    <div class="detail-item">
                        <p><strong>Foto:</strong></p>
                        <a href="uploads/<?php echo htmlspecialchars($pengaduan['foto']); ?>" target="_blank">
                            <img src="uploads/<?php echo htmlspecialchars($pengaduan['foto']); ?>" alt="Foto Pengaduan">
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <form method="POST">
                <textarea name="tanggapan" rows="5" placeholder="<?php echo $is_edit ? '' : 'Masukkan tanggapan Anda...'; ?>" required><?php echo htmlspecialchars($existing_tanggapan); ?></textarea>
                <div class="button-group">
                    <button type="submit" name="submit"><?php echo $is_edit ? 'Update Tanggapan' : 'Kirim Tanggapan'; ?></button>
                    <a href="petugas_dashboard.php" class="back-btn">Back</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($alert): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if ('<?php echo $alert['type']; ?>' === 'success') {
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
                                    <?php echo htmlspecialchars($alert['message']); ?>
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
                        window.location.href = 'petugas_dashboard.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: '<?php echo htmlspecialchars($alert['message']); ?>',
                        confirmButtonText: 'OK',
                        customClass: {
                            popup: 'swal2-popup',
                            confirmButton: 'swal2-confirm'
                        }
                    });
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>