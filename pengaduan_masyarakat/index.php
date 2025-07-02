<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Selamat Datang - Sistem Pengaduan Masyarakat</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="icon" type="image/ico" href="images/favicon.ico" />
  <style>
    /* Reset dan font standar */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    
    /* Warna latar belakang utama */
    body {
      background-color: #f8f9fa;
      overflow-x: hidden;
    }

    /* Navbar */
    .navbar {
      background-color: rgba(17, 24, 39, 0.9);
      padding: 15px 30px;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 1000;
      backdrop-filter: blur(10px);
      transition: all 0.3s ease;
    }
    .navbar.scrolled {
      background-color: rgba(17, 24, 39, 1);
      transform: translateY(-5px);
    }
    .navbar a {
      color: white;
      text-decoration: none;
      padding: 12px 25px;
      margin: 0 10px;
      border-radius: 8px;
      font-weight: 500;
      background-color: rgba(255,255,255,0.1);
      border: 1px solid rgba(255,255,255,0.1);
      transition: all 0.3s ease;
    }
    .navbar a:hover {
      background-color: #3498db;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(52,152,219,0.3);
    }

    /* Hero */
    .hero {
      height: 100vh;
      background-color: #111827;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      overflow: hidden;
    }
    .hero-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 90%;
      max-width: 1200px;
      padding: 0 20px;
    }
    .hero-content {
      flex: 1;
      opacity: 0;
      animation: fadeIn 1.5s ease forwards;
      padding-right: 30px;
    }
    .hero-content h1 {
      font-size: 48px;
      margin-bottom: 20px;
      font-weight: 700;
    }
    .hero-content p {
      font-size: 20px;
      margin-bottom: 30px;
      font-weight: 300;
    }
    .hero-content a {
      display: inline-block;
      padding: 15px 35px;
      background-color: #3498db;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(52,152,219,0.3);
      animation: pulse 2s infinite;
    }
    .hero-content a:hover {
      background-color: #2980b9;
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(52,152,219,0.4);
    }

    /* Animasi fade-in untuk gambar */
    .hero-image {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }
    .hero-image img {
      width: 100%;
      max-width: 500px;
      height: auto;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      opacity: 0;
      transform: translateY(20px);
      animation: fadeInImage 1.5s ease forwards 0.5s, float 4s ease-in-out infinite;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .hero-image:hover img {
      transform: translateY(-10px) scale(1.05);
      box-shadow: 0 15px 40px rgba(0,0,0,0.4);
    }

    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInImage {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse {
      0% { transform: scale(1); box-shadow: 0 5px 15px rgba(52,152,219,0.3); }
      50% { transform: scale(1.05); box-shadow: 0 10px 20px rgba(52,152,219,0.4); }
      100% { transform: scale(1); box-shadow: 0 5px 15px rgba(52,152,219,0.3); }
    }
    @keyframes float {
      0% { transform: translateY(0); }
      50% { transform: translateY(-15px); }
      100% { transform: translateY(0); }
    }

    /* Tata Cara Pengaduan – warna disamakan dengan hero */
    .steps {
      background: #111827;
      padding: 60px 20px;
    }
    .steps-container {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }
    .step {
      background: #111827;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      opacity: 0;
      transform: translateY(40px);
      animation: slideUp 0.8s ease forwards;
      text-align: center; /* Pusatkan konten */
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      animation: pulseOpacity 3s infinite;
    }
    .step:nth-child(1) { animation-delay: 0.2s; }
    .step:nth-child(2) { animation-delay: 0.4s; }
    .step:nth-child(3) { animation-delay: 0.6s; }
    .step:nth-child(4) { animation-delay: 0.8s; }
    .step .icon {
      font-size: 2rem;
      width: 50px;
      height: 50px;
      background: rgba(52, 152, 219, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 15px;
      color: #3498db;
      animation: floatIcon 4s ease-in-out infinite;
    }
    .step h3 {
      margin-bottom: 10px;
      font-size: 20px;
      color: #fff;
    }
    .step p {
      font-size: 16px;
      color: #ddd;
    }
    .step:hover {
      transform: translateY(-10px) scale(1.05);
      box-shadow: 0 8px 20px rgba(0,0,0,0.4);
    }
    @keyframes slideUp {
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulseOpacity {
      0% { opacity: 0.9; }
      50% { opacity: 1; }
      100% { opacity: 0.9; }
    }
    @keyframes floatIcon {
      0% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
      100% { transform: translateY(0); }
    }

    /* Responsif */
    @media (max-width: 992px) {
      .hero-container { flex-direction: column; text-align: center; }
      .hero-content { padding-right: 0; margin-bottom: 30px; }
      .hero-image { margin-top: 30px; }
      .hero-image img { max-width: 80%; }
      .steps-container { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
      .steps-container { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar">
    <div class="hamburger" onclick="toggleMenu()"></div>
    <div class="nav-links">
      <a href="login.php">Masuk</a>
      <a href="register.php">Daftar</a>
    </div>
  </nav>

  <!-- Hero -->
  <div class="hero">
    <div class="hero-container">
      <div class="hero-content">
        <h1>Sistem Pengaduan Masyarakat</h1>
        <p>Laporkan keluhan Anda dengan mudah dan cepat. Kami siap membantu!</p>
        <a href="login.php">Mulai Sekarang</a>
      </div>
      <div class="hero-image">
        <img src="images/download (2).jpg" alt="Ilustrasi Belajar di Meja" />
      </div>
    </div>
  </div>

  <!-- Tata Cara Pengaduan -->
  <section class="steps">
    <div class="steps-container">
      <div class="step">
        <span class="icon">🔑</span>
        <h3>1. Masuk / Daftar</h3>
        <p>Login menggunakan akun Anda atau daftar jika belum memiliki.</p>
      </div>
      <div class="step">
        <span class="icon">📋</span>
        <h3>2. Pilih Kategori</h3>
        <p>Pilih kategori pengaduan sesuai kebutuhan Anda.</p>
      </div>
      <div class="step">
        <span class="icon">✍️</span>
        <h3>3. Isi Formulir</h3>
        <p>Lengkapi detail keluhan dan lampirkan bukti jika perlu.</p>
      </div>
      <div class="step">
        <span class="icon">🚀</span>
        <h3>4. Submit & Pantau</h3>
        <p>Kirim pengaduan dan pantau status respons dalam dashboard.</p>
      </div>
    </div>
  </section>

  <!-- Script -->
  <script>
    function toggleMenu() {
      document.querySelector('.nav-links').classList.toggle('active');
    }
    window.addEventListener('scroll', () => {
      document.querySelector('.navbar').classList.toggle('scrolled', window.scrollY > 50);
    });
  </script>
</body>
</html>