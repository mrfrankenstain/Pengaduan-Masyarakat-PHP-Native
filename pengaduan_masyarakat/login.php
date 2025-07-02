<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Sistem Pengaduan</title>
  <link rel="icon" type="image/ico" href="images/favicon.ico" />
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

    .login-wrapper {
      width: 100%;
      max-width: 400px;
      padding: 20px;
    }

    .login-container {
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
    input[type="password"] {
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

    .error {
      padding: 12px;
      border-radius: 6px;
      text-align: center;
      margin-bottom: 15px;
      animation: fadeIn 0.5s ease-in-out;
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
  <div class="login-wrapper">
    <div class="login-container">
      <h2>Login</h2>

      <?php
      session_start();
      include 'koneksi.php';

      if (isset($_SESSION['role'])) {
          if ($_SESSION['role'] == 'masyarakat') {
              header("Location: masyarakat_dashboard.php");
              exit;
          } elseif ($_SESSION['role'] == 'petugas') {
              if ($_SESSION['level'] == 'admin') {
                  header("Location: admin_dashboard.php");
                  exit;
              } else {
                  header("Location: petugas_dashboard.php");
                  exit;
              }
          }
      }

      if (isset($_POST['login'])) {
          $username = mysqli_real_escape_string($koneksi, $_POST['username']);
          $password = mysqli_real_escape_string($koneksi, $_POST['password']);

          $r1 = mysqli_query($koneksi, "SELECT * FROM masyarakat WHERE username = '$username' AND password = '$password'");
          $r2 = mysqli_query($koneksi, "SELECT * FROM petugas WHERE username = '$username' AND password = '$password'");

          if (mysqli_num_rows($r1) > 0) {
              $data = mysqli_fetch_assoc($r1);
              $_SESSION['nik'] = $data['nik'];
              $_SESSION['nama'] = $data['nama'];
              $_SESSION['role'] = 'masyarakat';
              header("Location: masyarakat_dashboard.php");
              exit;
          } elseif (mysqli_num_rows($r2) > 0) {
              $data = mysqli_fetch_assoc($r2);
              $_SESSION['id_petugas'] = $data['id_petugas'];
              $_SESSION['nama_petugas'] = $data['nama_petugas'];
              $_SESSION['level'] = $data['status'];
              $_SESSION['role'] = 'petugas';
              if ($data['status'] == 'admin') {
                  header("Location: admin_dashboard.php");
                  exit;
              } else {
                  header("Location: petugas_dashboard.php");
                  exit;
              }
          } else {
              echo "<div class='error'>Username atau Password salah!</div>";
          }
      }
      ?>

      <form action="" method="post">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" name="username" id="username" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" required>
        </div>
        <input type="submit" name="login" value="Login">
      </form>

      <div class="link">
        <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
      </div>

      <div class="back-button">
        <a href="index.php">⬅ Back</a>
      </div>
    </div>
  </div>
</body>
</html>