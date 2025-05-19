<?php
session_start();

// Redirect if user not logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../html/login.html");
    exit();
}
?>






<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Dashboard - Berbere Jewelry</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f0f2f5;
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    .sidebar {
      width: 250px;
      background-color: #111;
      color: #fff;
      padding: 30px 20px;
      display: flex;
      flex-direction: column;
    }

    .sidebar h2 {
      margin-bottom: 30px;
      font-size: 24px;
      color: #ffd700;
    }

    .sidebar ul {
      list-style: none;
    }

    .sidebar ul li {
      margin: 20px 0;
    }

    .sidebar ul li a {
      color: #bbb;
      text-decoration: none;
      font-size: 16px;
      display: flex;
      align-items: center;
    }

    .sidebar ul li a i {
      margin-right: 10px;
    }

    .sidebar ul li a:hover {
      color: #fff;
    }

    .main-content {
      flex: 1;
      padding: 30px;
      overflow-y: auto;
      position: relative;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header h1 {
      font-size: 28px;
      color: #333;
    }

    .notifications {
      font-size: 20px;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }

    .card {
      background-color: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transition: 0.3s ease-in-out;
    }

    .card:hover {
      transform: translateY(-5px);
    }

    .card h3 {
      margin-bottom: 10px;
      font-size: 20px;
    }

    .card p {
      color: #666;
      font-size: 14px;
    }

    .toggle-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      padding: 10px 15px;
      background-color: #ddd;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    /* Dark Mode */
    .dark-mode {
      background-color: #121212;
      color: #fff;
    }

    .dark-mode .card {
      background-color: #1e1e1e;
    }

    .dark-mode .sidebar {
      background-color: #000;
    }

    .dark-mode .sidebar ul li a {
      color: #ccc;
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none;
      }
      .main-content {
        padding: 15px;
      }
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>Berbere Jewelry</h2>
    <ul>
      <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
      <li><a href="#"><i class="fas fa-user"></i> My Profile</a></li>
      <li><a href="#"><i class="fas fa-shopping-cart"></i> Orders</a></li>
      <li><a href="#"><i class="fas fa-envelope"></i> Messages</a></li>
      <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
      <li><a href="logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </div>

  <div class="main-content">
    <button class="toggle-btn" onclick="document.body.classList.toggle('dark-mode')">Toggle Theme</button>

    <div class="header">
      <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']['login']); ?>!</h1>
      <div class="notifications">
        <i class="fas fa-bell"></i>
      </div>
    </div>

    <div class="cards">
      <div class="card">
        <h3><i class="fas fa-user-circle"></i> Account Info</h3>
        <p><strong>Login:</strong> <?php echo htmlspecialchars($_SESSION['user']['login']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user']['email']); ?></p>
      </div>

      <div class="card">
        <h3><i class="fas fa-box"></i> Recent Orders</h3>
        <p>You have 3 pending orders.</p>
      </div>

      <div class="card">
        <h3><i class="fas fa-envelope-open-text"></i> Messages</h3>
        <p>You have 2 new messages.</p>
      </div>

      <div class="card">
        <h3><i class="fas fa-star"></i> Wishlist</h3>
        <p>5 items saved for later.</p>
      </div>

      <div class="card">
        <h3><i class="fas fa-cog"></i> Account Settings</h3>
        <p>Update your preferences and password.</p>
      </div>
    </div>
  </div>

</body>
</html>
