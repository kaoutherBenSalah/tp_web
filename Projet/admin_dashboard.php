<?php
session_start();

// Redirect if user not logged in or not admin
if (!isset($_SESSION['user']) || $_SESSION['user']['login'] !== 'admin') {
    header("Location: ../html/login.html");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'tp_web';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exc) {
    die('Erreur de connexion : ' . htmlspecialchars($exc->getMessage()));
}

// Get admin login from session
$login = $_SESSION['user']['login'];

// User deletion processing
if (isset($_GET['delete_user'])) {
    $user_login = $_GET['delete_user'];
    
    if ($user_login && $user_login !== 'admin') { // Prevent admin deletion
        // First delete all products from this user
        $stmt = $pdo->prepare("DELETE FROM products WHERE user_id = :login");
        $stmt->execute([':login' => $user_login]);
        
        // Then delete the user
        $stmt = $pdo->prepare("DELETE FROM user WHERE login = :login");
        $stmt->execute([':login' => $user_login]);
        
        // Redirect
        header("Location: admin_dashboard.php?user_deleted=1");
        exit();
    }
}

// Product deletion processing
if (isset($_GET['delete_product'])) {
    $product_id = filter_var($_GET['delete_product'], FILTER_VALIDATE_INT);
    $product_name = isset($_GET['product_name']) ? $_GET['product_name'] : '';
    
    if ($product_id) {
        // Delete the product by ID
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        
        // Redirect
        header("Location: admin_dashboard.php?product_deleted=1");
        exit();
    } elseif (!empty($product_name)) {
        // Delete the product by name
        $stmt = $pdo->prepare("DELETE FROM products WHERE P_NAME = :name");
        $stmt->execute([':name' => $product_name]);
        
        // Redirect
        header("Location: admin_dashboard.php?product_deleted=1");
        exit();
    }
}

// Product addition processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name'] ?? '');
    $product_price = filter_var($_POST['product_price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $product_type = trim($_POST['product_type'] ?? '');
    $product_user = trim($_POST['product_user'] ?? $login); // Default to admin if not specified
    $product_status = trim($_POST['product_status'] ?? 'Available'); // Default status
    
    // File upload handling for product image
    $has_image = false;
    $product_image = null;
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $image_tmp = $_FILES['product_image']['tmp_name'];
        $product_image = file_get_contents($image_tmp);
        $has_image = true;
    }
    
    if (!empty($product_name) && $product_price > 0) {
        // Insert product with appropriate fields
        $stmt = $pdo->prepare("INSERT INTO products (user_id, P_NAME, P_Price, P_Type, P_Status, P_picture) VALUES (:user_id, :name, :price, :type, :status, :picture)");
        
        $params = [
            ':user_id' => $product_user,
            ':name' => $product_name,
            ':price' => $product_price,
            ':type' => $product_type,
            ':status' => $product_status
        ];
        
        if ($has_image) {
            $params[':picture'] = $product_image;
        } else {
            $params[':picture'] = null;
        }
        
        $stmt->execute($params);
        
        // Redirect to avoid form resubmission
        header("Location: admin_dashboard.php?product_added=1");
        exit();
    } else {
        $product_error = true;
    }
}

// Admin profile update processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM user WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data && password_verify($current_password, $user_data['password'])) {
        // Update email if provided and valid
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("UPDATE user SET email = :email WHERE login = :login");
            $stmt->execute([':email' => $email, ':login' => $login]);
            $_SESSION['user']['email'] = $email;
        }
        
        // Update password if provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE user SET password = :password WHERE login = :login");
            $stmt->execute([':password' => $hashed_password, ':login' => $login]);
        }
        
        $update_success = true;
    } else {
        $password_error = true;
    }
}

// Retrieve all users
$stmt = $pdo->prepare("SELECT * FROM user ORDER BY login ASC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Get the accurate product count directly from the database
$stmt_count = $pdo->prepare("SELECT COUNT(*) as product_count FROM products");
$stmt_count->execute();
$product_count = $stmt_count->fetch(PDO::FETCH_ASSOC)['product_count'];

// Retrieve all products (correction: we need all products, not just those from admin)
$stmt = $pdo->prepare("SELECT p.*, u.login FROM products p LEFT JOIN user u ON p.user_id = u.login ORDER BY p.P_NAME DESC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to generate base64 image data for display
function getImageData($blob) {
    if ($blob) {
        return 'data:image/jpeg;base64,' . base64_encode($blob);
    }
    return '../images/placeholder.png'; // Default placeholder image
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard - Berbere Jewelry</title>
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
      margin-bottom: 20px;
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
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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
      margin-bottom: 15px;
      font-size: 20px;
      color: #333;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }

    .card p {
      color: #666;
      font-size: 14px;
      margin-bottom: 8px;
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
      color: #eee;
    }

    .dark-mode .header h1 {
      color: #fff;
    }

    .dark-mode .sidebar {
      background-color: #000;
    }

    .dark-mode .sidebar ul li a {
      color: #ccc;
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }

    .form-control {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-family: inherit;
    }

    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: 500;
      transition: background-color 0.3s;
    }

    .btn-primary {
      background-color: #4a6fa5;
      color: white;
    }

    .btn-primary:hover {
      background-color: #3a5680;
    }

    .btn-danger {
      background-color: #dc3545;
      color: white;
    }

    .btn-danger:hover {
      background-color: #bb2d3b;
    }

    .btn-success {
      background-color: #28a745;
      color: white;
    }

    .btn-success:hover {
      background-color: #218838;
    }

    .alert {
      padding: 10px 15px;
      border-radius: 4px;
      margin-bottom: 15px;
    }

    .alert-success {
      background-color: #d1e7dd;
      color: #0f5132;
      border: 1px solid #badbcc;
    }

    .alert-danger {
      background-color: #f8d7da;
      color: #842029;
      border: 1px solid #f5c2c7;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .tab-buttons {
      display: flex;
      margin-bottom: 20px;
      border-bottom: 1px solid #ddd;
    }

    .tab-btn {
      padding: 10px 20px;
      background: none;
      border: none;
      border-bottom: 3px solid transparent;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s;
    }

    .tab-btn.active {
      border-color: #4a6fa5;
      color: #4a6fa5;
    }

    .tab-btn:hover {
      background-color: #f0f0f0;
    }

    .dark-mode .tab-btn:hover {
      background-color: #252525;
    }

    .dark-mode .tab-btn.active {
      color: #7ba6e9;
      border-color: #7ba6e9;
    }

    /* Table Styles */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    .data-table th,
    .data-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    .data-table th {
      background-color: #f8f9fa;
      font-weight: 600;
    }

    .dark-mode .data-table th {
      background-color: #252525;
    }

    .data-table tr:hover {
      background-color: #f1f1f1;
    }

    .dark-mode .data-table tr:hover {
      background-color: #333;
    }

    .data-table .actions {
      display: flex;
      gap: 10px;
    }

    .admin-badge {
      background-color: #ffd700;
      color: #000;
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
    }
    
    /* Product Item Styles */
    .product-item {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .product-image {
      width: 60px;
      height: 60px;
      border-radius: 8px;
      object-fit: cover;
    }
    
    .product-details {
      flex: 1;
    }
    
    .product-name {
      font-weight: 500;
      margin-bottom: 3px;
    }
    
    .product-owner {
      font-size: 12px;
      color: #777;
    }
    
    .product-type {
      font-size: 12px;
      color: #777;
    }
    
    .product-status {
      font-size: 12px;
      padding: 2px 8px;
      border-radius: 10px;
      background: #e8f4fd;
      color: #4a6fa5;
      display: inline-block;
      margin-top: 3px;
    }
    
    .product-status.available {
      background: #e8f8e8;
      color: #28a745;
    }
    
    .product-status.sold {
      background: #ffe8e8;
      color: #dc3545;
    }

    @media (max-width: 768px) {
      body {
        flex-direction: column;
        overflow: auto;
        height: auto;
      }
      
      .sidebar {
        width: 100%;
        padding: 15px;
      }
      
      .main-content {
        padding: 15px;
      }
      
      .tab-buttons {
        overflow-x: auto;
      }

      .data-table {
        display: block;
        overflow-x: auto;
      }
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>Admin Dashboard</h2>
    <ul>
      <li><a href="#" class="tab-link" data-tab="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
      <li><a href="#" class="tab-link" data-tab="users"><i class="fas fa-users"></i> Utilisateurs</a></li>
      <li><a href="#" class="tab-link" data-tab="products"><i class="fas fa-shopping-cart"></i> Produits</a></li>
      <li><a href="#" class="tab-link" data-tab="add-product"><i class="fas fa-plus"></i> Ajouter un Produit</a></li>
      <li><a href="#" class="tab-link" data-tab="profile"><i class="fas fa-user-circle"></i> Mon Profil</a></li>
      <li><a href="/tp_web/Projet/index.php" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul>
  </div>

  <div class="main-content">
    <button class="toggle-btn" onclick="document.body.classList.toggle('dark-mode')">
      <i class="fas fa-moon"></i> Theme
    </button>

    <div class="header">
      <h1>Administration - Berbere Jewelry</h1>
      <div class="notifications">
        <i class="fas fa-bell"></i>
      </div>
    </div>

    <!-- Notification messages -->
    <?php if (isset($_GET['user_deleted'])): ?>
      <div class="alert alert-success">L'utilisateur a été supprimé avec succès!</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['product_deleted'])): ?>
      <div class="alert alert-success">Le produit a été supprimé avec succès!</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['product_added'])): ?>
      <div class="alert alert-success">Le produit a été ajouté avec succès!</div>
    <?php endif; ?>
    
    <?php if (isset($update_success)): ?>
      <div class="alert alert-success">Votre profil a été mis à jour avec succès!</div>
    <?php endif; ?>
    
    <?php if (isset($password_error)): ?>
      <div class="alert alert-danger">Le mot de passe actuel est incorrect.</div>
    <?php endif; ?>
    
    <?php if (isset($product_error)): ?>
      <div class="alert alert-danger">Veuillez remplir tous les champs obligatoires pour ajouter un produit.</div>
    <?php endif; ?>

    <!-- Tabs Buttons -->
    <div class="tab-buttons">
      <button class="tab-btn active" data-tab="dashboard">Dashboard</button>
      <button class="tab-btn" data-tab="users">Utilisateurs</button>
      <button class="tab-btn" data-tab="products">Produits</button>
      <button class="tab-btn" data-tab="add-product">Ajouter un Produit</button>
      <button class="tab-btn" data-tab="profile">Mon Profil</button>
    </div>

    <!-- Tab Content -->
    <div id="dashboard" class="tab-content active">
      <div class="cards">
        <div class="card">
          <h3><i class="fas fa-users"></i> Utilisateurs</h3>
          <p>Il y a <?php echo count($users); ?> utilisateurs enregistrés.</p>
          <p><a href="#" class="tab-link" data-tab="users">Gérer les utilisateurs</a></p>
        </div>

        <div class="card">
          <h3><i class="fas fa-shopping-cart"></i> Produits</h3>
         <p>Il y a <?php echo $product_count; ?> produits enregistrés.</p> 
                  <p><a href="#" class="tab-link" data-tab="products">Gérer les produits</a></p>
        </div>

        <div class="card">
          <h3><i class="fas fa-plus-circle"></i> Ajouter un produit</h3>
          <p>Ajouter un nouveau produit à la base de données.</p>
          <p><a href="#" class="tab-link" data-tab="add-product">Ajouter un produit</a></p>
        </div>

        <div class="card">
          <h3><i class="fas fa-cog"></i> Système</h3>
          <p>Version du système: 1.0.0</p>
          <p>Dernière mise à jour: <?php echo date('d/m/Y'); ?></p>
        </div>
      </div>
    </div>

    <div id="users" class="tab-content">
      <div class="card">
        <h3>Liste des Utilisateurs</h3>
        
        <?php if (empty($users)): ?>
          <p>Aucun utilisateur enregistré.</p>
        <?php else: ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Login</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr>
                  <td>
                    <?php echo htmlspecialchars($user['login']); ?>
                    <?php if ($user['login'] === 'admin'): ?>
                      <span class="admin-badge">Admin</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($user['F_NAME'] ?? 'Non défini'); ?></td>
                  <td><?php echo htmlspecialchars($user['L_NAME'] ?? 'Non défini'); ?></td>
                  <td><?php echo htmlspecialchars($user['email'] ?? 'Non défini'); ?></td>
                  <td class="actions">
                    <?php if ($user['login'] !== 'admin'): ?>
                      <a href="?delete_user=<?php echo urlencode($user['login']); ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur et tous ses produits?');" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Supprimer
                      </a>
                    <?php else: ?>
                      <span class="btn btn-danger" style="opacity: 0.5; cursor: not-allowed;">
                        <i class="fas fa-trash"></i> Supprimer
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <div id="products" class="tab-content">
      <div class="card">
        <h3>Liste des Produits</h3>
        
        <?php if (empty($products)): ?>
          <p>Aucun produit enregistré.</p>
        <?php else: ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Image</th>
                <th>Nom</th>
                <th>Prix</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Propriétaire</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $product): ?>
                <tr>
                  <td>
                    <?php if (isset($product['P_picture'])): ?>
                      <img src="<?php echo getImageData($product['P_picture']); ?>" class="product-image" alt="Product Image">
                    <?php else: ?>
                      <img src="../images/placeholder.png" class="product-image" alt="No Image">
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($product['P_NAME'] ?? $product['name'] ?? 'Non défini'); ?></td>
                  <td><?php echo number_format($product['P_Price'] ?? $product['price'] ?? 0, 2); ?> DA</td>
                  <td><?php echo htmlspecialchars($product['P_Type'] ?? 'Non défini'); ?></td>
                  <td>
                    <?php if (isset($product['P_Status'])): ?>
                      <span class="product-status <?php echo strtolower($product['P_Status']); ?>">
                        <?php echo htmlspecialchars($product['P_Status']); ?>
                      </span>
                    <?php else: ?>
                      <span class="product-status">Non défini</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($product['user_id'] ?? $product['login'] ?? 'Non défini'); ?></td>
                  <td class="actions">
                    <?php 
                      $productId = $product['id'] ?? null;
                      $productName = $product['P_NAME'] ?? $product['name'] ?? null;
                    ?>
                    <a href="?delete_product=<?php echo $productId; ?>&product_name=<?php echo urlencode($productName); ?>" 
                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit?');" 
                       class="btn btn-danger">
                      <i class="fas fa-trash"></i> Supprimer
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
    
    <div id="add-product" class="tab-content">
      <div class="card">
        <h3>Ajouter un Nouveau Produit</h3>
        <form method="post" action="" enctype="multipart/form-data">
          <div class="form-group">
            <label for="product_name">Nom du produit</label>
            <input type="text" name="product_name" id="product_name" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="product_price">Prix (DA)</label>
            <input type="number" name="product_price" id="product_price" class="form-control" step="0.01" min="0" required>
          </div>
          
          <div class="form-group">
            <label for="product_type">Type de produit</label>
            <select name="product_type" id="product_type" class="form-control">
              <option value="Bracelet">Bracelet - Ameclux</option>
              <option value="Broche">Broche - Avzim</option>
              <option value="Collier">Collier</option>
              <option value="Bague">Bague</option>
              <option value="Autre">Autre</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="product_user">Propriétaire (utilisateur)</label>
            <select name="product_user" id="product_user" class="form-control">
              <?php foreach ($users as $user): ?>
                <option value="<?php echo htmlspecialchars($user['login']); ?>">
                  <?php echo htmlspecialchars($user['login']); ?>
                  <?php if ($user['login'] === 'admin'): ?> (Admin)<?php endif; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="product_status">Statut</label>
            <select name="product_status" id="product_status" class="form-control">
              <option value="Available">Disponible</option>
              <option value="Sold">Vendu</option>
              <option value="Reserved">Réservé</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="product_image">Image du produit</label>
            <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*">
          </div>
          
          <button type="submit" name="add_product" class="btn btn-success">Ajouter le produit</button>
        </form>
      </div>
    </div>

    <div id="profile" class="tab-content">
      <div class="card">
        <h3>Modifier Mon Profil</h3>
        <form method="post" action="">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?>" required>
          </div>
          
               <div class="form-group">
            <label for="current_password">Mot de passe actuel</label>
            <input type="password" name="current_password" id="current_password" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="new_password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" name="new_password" id="new_password" class="form-control">
          </div>
          
          <button type="submit" name="update_profile" class="btn btn-primary">Mettre à jour mon profil</button>
        </form>
      </div>
    </div>

    <div id="products" class="tab-content">
      <div class="card">
        <h3>Mes Produits</h3>
        
        <?php if (empty($products)): ?>
          <p>Vous n'avez pas encore ajouté de produits.</p>
          <button class="btn btn-primary tab-link" data-tab="add-product">Ajouter un produit</button>
        <?php else: ?>
          <div class="product-list">
            <?php foreach ($products as $product): ?>
              <div class="product-item">
                <img src="<?php echo getImageData($product['P_picture'] ?? null); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['P_NAME'] ?? 'Produit'); ?>">
                
                <div class="product-details">
                  <div class="product-name"><?php echo htmlspecialchars($product['P_NAME'] ?? 'Sans nom'); ?></div>
                  <div class="product-type"><?php echo htmlspecialchars($product['P_Type'] ?? 'Type non défini'); ?></div>
                  <span class="product-status <?php echo strtolower($product['P_Status'] ?? 'available'); ?>">
                    <?php echo htmlspecialchars($product['P_Status'] ?? 'Disponible'); ?>
                  </span>
                </div>
                
                <div class="product-price"><?php echo number_format($product['P_Price'] ?? 0, 2); ?> DA</div>
                
                <div class="product-actions">
                  <a href="?delete_product=<?php echo $product['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit?');" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Supprimer
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div id="add-product" class="tab-content">
      <div class="card">
        <h3>Ajouter un Nouveau Produit</h3>
        <form method="post" action="" enctype="multipart/form-data">
          <div class="form-group">
            <label for="product_name">Nom du produit</label>
            <input type="text" name="product_name" id="product_name" class="form-control" required>
          </div>
          
          <div class="form-group">
            <label for="product_price">Prix (DA)</label>
            <input type="number" name="product_price" id="product_price" class="form-control" step="0.01" min="0" required>
          </div>
          
          <div class="form-group">
            <label for="product_type">Type de produit</label>
            <select name="product_type" id="product_type" class="form-control">
              <option value="Bracelet">Bracelet - Ameclux</option>
              <option value="Broche">Broche - Avzim</option>
              <option value="Collier">Collier</option>
              <option value="Bague">Bague</option>
              <option value="Autre">Autre</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="product_status">Statut</label>
            <select name="product_status" id="product_status" class="form-control">
              <option value="Available">Disponible</option>
              <option value="Sold">Vendu</option>
              <option value="Reserved">Réservé</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="product_image">Image du produit</label>
            <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*">
          </div>
          
          <button type="submit" name="add_product" class="btn btn-success">Ajouter le produit</button>
        </form>
      </div>
    </div>

    <div id="settings" class="tab-content">
      <div class="card">
        <h3>Supprimer Mon Compte</h3>
        <p>Attention! Cette action est irréversible et supprimera toutes vos données et produits.</p>
        
        <form method="post" action="" onsubmit="return confirm('Êtes-vous vraiment sûr de vouloir supprimer votre compte? Cette action est irréversible.');">
          <div class="form-group">
            <label for="delete_password">Entrez votre mot de passe pour confirmer</label>
            <input type="password" name="delete_password" id="delete_password" class="form-control" required>
          </div>
          
          <button type="submit" name="delete_account" class="btn btn-danger">
            <i class="fas fa-user-minus"></i> Supprimer mon compte définitivement
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Tab Navigation
    document.addEventListener('DOMContentLoaded', function() {
      // Handle tab buttons
      const tabButtons = document.querySelectorAll('.tab-btn, .tab-link');
      const tabContents = document.querySelectorAll('.tab-content');
      
      tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          
          const tabId = this.getAttribute('data-tab');
          
          // Update active button state for tab-btn elements
          document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-tab') === tabId) {
              btn.classList.add('active');
            }
          });
          
          // Show the selected tab
          tabContents.forEach(tab => {
            tab.classList.remove('active');
          });
          document.getElementById(tabId).classList.add('active');
        });
      });
      
      // Handle URL parameters for tab selection
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab');
      
      if (tabParam) {
        const tabElement = document.querySelector(`.tab-btn[data-tab="${tabParam}"]`);
        if (tabElement) {
          tabElement.click();
        }
      }
    });
  </script>
</body>
</html>