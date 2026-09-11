<?php 
include 'config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEUST Navigation and Information System</title>
    <link rel="icon" href="../img/neust_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .navbar.bg-custom {
            background: linear-gradient(135deg, #14369C, #0B2270) !important;
            border-bottom: 3px solid #FFCE00;
        }
        .navbar-brand {
            font-family: 'Manrope', 'Inter', sans-serif;
            font-weight: 800;
            color: #fff !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-brand img { width: 32px; height: 32px; }
        .navbar-brand small {
            display: block;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 10px;
            color: #FFE271;
            letter-spacing: .08em;
        }
    </style>
</head>
<body>
<nav class="navbar sticky-top navbar-dark bg-custom shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="../dashboard.php">
      <img src="../img/neust_logo.png" alt="NEUST">
      <span>NEUST Navigation &amp; Information System<small>SCIENCE &amp; TECHNOLOGY CAMPUS PORTAL</small></span>
    </a>
  </div>
</nav>
</body>
</html>