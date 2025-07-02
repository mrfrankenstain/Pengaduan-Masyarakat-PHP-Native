<?php
include 'koneksi.php';
$successMessage = '';
$errorMessage = '';

if (isset($_POST['register'])) {
    $nik      = mysqli_real_escape_string($koneksi, $_POST['nik']);
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $telp     = mysqli_real_escape_string($koneksi, $_POST['telp']);

    if (empty($nik) || empty($nama) || empty($username) || empty($password) || empty($telp)) {
        $errorMessage = "Semua field harus diisi!";
    } elseif (strlen($nik) != 16) {
        $errorMessage = "NIK harus 16 digit!";
    } else {
        $cekNik = mysqli_query($koneksi, "SELECT * FROM masyarakat WHERE nik='$nik'");
        if (mysqli_num_rows($cekNik) > 0) {
            $errorMessage = "NIK sudah terdaftar!";
        } else {
            $cekUser = mysqli_query($koneksi, "SELECT * FROM masyarakat WHERE username='$username'");
            if (mysqli_num_rows($cekUser) > 0) {
                $errorMessage = "Username sudah digunakan!";
            } else {
                $insert = mysqli_query($koneksi, "
                    INSERT INTO masyarakat (nik, nama, username, password, telp)
                    VALUES ('$nik','$nama','$username','$password','$telp')
                ");
                if ($insert) {
                    $successMessage = "Registrasi berhasil! Mengarahkan ke halaman login...";
                    header("refresh:3;url=login.php");
                } else {
                    $errorMessage = "Gagal registrasi: " . mysqli_error($koneksi);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrasi Masyarakat - Sistem Pengaduan</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background-color: #111827;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      overflow: hidden;
    }

    .register-wrapper {
      width: 100%;
      max-width: 400px;
      padding: 20px;
    }

    .register-container {
      background-color: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      opacity: 0;
      animation: fadeIn 0.8s ease forwards;
    }

    @keyframes fadeIn {
      to { opacity: 1; }
    }

    h2 {
      text-align: center;
      color: #111827;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .form-group {
      margin-bottom: 15px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      color: #4B5563;
      font-weight: 500;
    }

    input[type="text"],
    input[type="password"],
    input[type="tel"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 16px;
    }

    input[type="submit"] {
      width: 100%;
      padding: 12px;
      background-color: #3498db;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    input[type="submit"]:hover {
      background-color: #2980b9;
    }

    .success, .error {
      padding: 12px;
      border-radius: 6px;
      text-align: center;
      margin-bottom: 15px;
      animation: fadeIn 0.5s ease-in-out;
    }

    .success {
      background-color: #d1fae5;
      color: #065f46;
    }

    .error {
      background-color: #fee2e2;
      color: #991b1b;
    }

    .link {
      text-align: center;
      margin-top: 15px;
    }

    .link a {
      color: #3498db;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s;
    }

    .link a:hover {
      color: #2980b9;
    }

    .back-button {
      text-align: center;
      margin-top: 20px;
    }

    .back-button a {
      display: inline-block;
      padding: 10px 25px;
      background-color: #3498db;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      transition: background-color 0.3s;
    }

    .back-button a:hover {
      background-color: #2980b9;
    }
  </style>
</head>
<body>
  <div class="register-wrapper">
    <div class="register-container">
      <h2>Registrasi Masyarakat</h2>

      <?php if ($successMessage): ?>
        <div class="success"><?php echo $successMessage; ?></div>
      <?php endif; ?>

      <?php if ($errorMessage): ?>
        <div class="error"><?php echo $errorMessage; ?></div>
      <?php endif; ?>

      <form action="" method="post">
        <div class="form-group">
          <label for="nik">NIK</label>
          <input type="text" name="nik" id="nik" maxlength="16" required>
        </div>
        <div class="form-group">
          <label for="nama">Nama</label>
          <input type="text" name="nama" id="nama" maxlength="35" required>
        </div>
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" name="username" id="username" maxlength="25" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" maxlength="32" required>
        </div>
        <div class="form-group">
          <label for="telp">Nomor Telepon</label>
          <input type="tel" name="telp" id="telp" maxlength="13" required>
        </div>
        <input type="submit" name="register" value="Daftar">
      </form>

      <div class="link">
        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
      </div>

      <div class="back-button">
        <a href="index.php">⬅ Kembali</a>
      </div>
    </div>
  </div>
</body>
</html>
