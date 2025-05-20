<?php
session_start();

// Redirect if user not logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../html/login.html");
    exit();
}

// Redirect if not admin user
if ($_SESSION['user']['login'] !== 'admin') {
    header("Location: dashboard.php");
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

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $user_login = $_GET['delete_user'];
    
    // Delete user's products first
    $stmt = $pdo->prepare("DELETE FROM products WHERE user_id = :login");
    $stmt->execute([':login' => $user_login]);
    
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM user WHERE login = :login");
    $stmt->execute([':login' => $user_login]);
    
    // Redirect with success message
    header("Location: admin_dashboard.php?user_deleted=1&tab=users");
    exit();
}

// Handle product deletion
if (isset($_GET['delete_product'])) {
    $product_id = filter_var($_GET['delete_product'], FILTER_VALIDATE_INT);
    
    if ($product_id) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        
        // Redirect with success message
        header("Location: admin_dashboard.php?product_deleted=1&tab=products");
        exit();
    }
}

// Handle product editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $product_name = trim($_POST['product_name'] ?? '');
    $product_price = filter_var($_POST['product_price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $product_description = trim($_POST['product_description'] ?? '');
    $product_user_id = $_POST['product_user_id'];
    
    if (!empty($product_name) && $product_price > 0 && $product_id) {
        $stmt = $pdo->prepare("UPDATE products SET name = :name, price = :price, description = :description, user_id = :user_id WHERE id = :id");
        $stmt->execute([
            ':name' => $product_name,
            ':price' => $product_price,
            ':description' => $product_description,
            ':user_id' => $product_user_id,
            ':id' => $product_id
        ]);
        
        // Redirect with success message
        header("Location: admin_dashboard.php?product_updated=1&tab=products");
        exit();
    }
}

// Handle product addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name'] ?? '');
    $product_price = filter_var($_POST['product_price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $product_description = trim($_POST['product_description'] ?? '');
    $product_user_id = $_POST['product_user_id'];
    
    if (!empty($product_name) && $product_price > 0) {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, description, user_id) VALUES (:name, :price, :description, :user_id)");
        $stmt->execute([
            ':name' => $product_name,
            ':price' => $product_price,
            ':description' => $product_description,
            ':user_id' => $product_user_id
        ]);
        
        // Redirect with success message
        header("Location: admin_dashboard.php?product_added=1&tab=products");
        exit();
    } else {
        $product_error = true;
    }
}

// Get product for editing if ID is provided
$edit_product = null;
if (isset($_GET['edit_product'])) {
    $product_id = filter_var($_GET['edit_product'], FILTER_VALIDATE_INT);
    
    if ($product_id) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Get all users - Moved outside conditional to always refresh data
$stmt = $pdo->prepare("SELECT login, email FROM user WHERE login != 'admin'");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all products
$stmt = $pdo->prepare("SELECT * FROM products");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
      margin-bottom: 20px;
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

    .btn-warning {
      background-color: #ffc107;
      color: #212529;
    }

    .btn-warning:hover {
      background-color: #e0a800;
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

    .user-list, .product-list {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    .user-list th, .product-list th {
      background-color: #f5f5f5;
      color: #333;
      text-align: left;
      padding: 12px;
      border-bottom: 2px solid #ddd;
    }

    .user-list td, .product-list td {
      padding: 12px;
      border-bottom: 1px solid #eee;
    }

    .user-list tr:hover, .product-list tr:hover {
      background-color: #f9f9f9;
    }

    .dark-mode .user-list th, .dark-mode .product-list th {
      background-color: #333;
      color: #eee;
      border-bottom: 2px solid #444;
    }

    .dark-mode .user-list td, .dark-mode .product-list td {
      border-bottom: 1px solid #444;
    }

    .dark-mode .user-list tr:hover, .dark-mode .product-list tr:hover {
      background-color: #2a2a2a;
    }

    textarea.form-control {
      min-height: 100px;
    }

    .action-btns {
      display: flex;
      gap: 5px;
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
      
      .cards {
        grid-template-columns: 1fr;
      }
      
      .tab-buttons {
        overflow-x: auto;
      }

      .action-btns {
        flex-direction: column;
      }
    }

    .admin-badge {
      background-color: #dc3545;
      color: white;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 12px;
      margin-left: 10px;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>Berbere Jewelry</h2>
    <ul>
      <li><a href="#" class="tab-link" data-tab="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
      <li><a href="#" class="tab-link" data-tab="users"><i class="fas fa-users"></i> Utilisateurs</a></li>
      <li><a href="#" class="tab-link" data-tab="products"><i class="fas fa-shopping-cart"></i> Produits</a></li>
      <li><a href="#" class="tab-link" data-tab="add-product"><i class="fas fa-plus"></i> Ajouter un Produit</a></li>
      <li><a href="dashboard.php"><i class="fas fa-user-circle"></i> Mon Profil</a></li>
      <li><a href="logout.php" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul>
  </div>

  <div class="main-content">
    <button class="toggle-btn" onclick="document.body.classList.toggle('dark-mode')">
      <i class="fas fa-moon"></i> Theme
    </button>

    <div class="header">
      <h1>Administration <span class="admin-badge">ADMIN</span></h1>
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
    
    <?php if (isset($_GET['product_updated'])): ?>
      <div class="alert alert-success">Le produit a été mis à jour avec succès!</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['product_added'])): ?>
      <div class="alert alert-success">Le produit a été ajouté avec succès!</div>
    <?php endif; ?>
    
    <?php if (isset($product_error)): ?>
      <div class="alert alert-danger">Veuillez remplir tous les champs obligatoires.</div>
    <?php endif; ?>

    <!-- Tabs Buttons -->
    <div class="tab-buttons">
      <button class="tab-btn active" data-tab="dashboard">Dashboard</button>
      <button class="tab-btn" data-tab="users">Utilisateurs</button>
      <button class="tab-btn" data-tab="products">Produits</button>
      <button class="tab-btn" data-tab="add-product">Ajouter un Produit</button>
    </div>

    <!-- Tab Content -->
    <div id="dashboard" class="tab-content active">
      <div class="cards">
        <div class="card">
          <h3><i class="fas fa-user-circle"></i> Information du Compte</h3>
          <p><strong>Login:</strong> <?php echo htmlspecialchars($_SESSION['user']['login']); ?> <span class="admin-badge">ADMIN</span></p>
          <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user']['email'] ?? 'Non défini'); ?></p>
        </div>

        <div class="card">
          <h3><i class="fas fa-users"></i> Utilisateurs</h3>
          <p>Il y a <?php echo count($users); ?> utilisateur(s) enregistré(s).</p>
          <p><a href="#" class="tab-link" data-tab="users">Gérer les utilisateurs</a></p>
        </div>

        <div class="card">
          <h3><i class="fas fa-box"></i> Produits</h3>
          <p>Il y a <?php echo count($products); ?> produit(s) enregistré(s).</p>
          <p><a href="#" class="tab-link" data-tab="products">Gérer les produits</a></p>
        </div>

        <div class="card">
          <h3><i class="fas fa-plus"></i> Ajouter un produit</h3>
          <p>Ajouter un nouveau produit au catalogue.</p>
          <p><a href="#" class="tab-link" data-tab="add-product">Ajouter un produit</a></p>
        </div>
      </div>
    </div>

    <div id="users" class="tab-content">
      <div class="card">
        <h3>Gestion des Utilisateurs</h3>
        <?php if (empty($users)): ?>
          <p>Aucun utilisateur enregistré.</p>
        <?php else: ?>
          <table class="user-list">
            <thead>
              <tr>
                <th>Login</th>
                <th>Email</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr>
                  <td><?php echo htmlspecialchars($user['login']); ?></td>
                  <td><?php echo htmlspecialchars($user['email'] ?? 'Non défini'); ?></td>
                  <td>
                    <a href="?delete_user=<?php echo urlencode($user['login']); ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur? Tous ses produits seront également supprimés.');" class="btn btn-danger">
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

    <div id="products" class="tab-content">
      <div class="card">
        <h3>Gestion des Produits</h3>
        <?php if (empty($products)): ?>
          <p>Aucun produit enregistré.</p>
        <?php else: ?>
          <table class="product-list">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prix</th>
                <th>Description</th>
                <th>Propriétaire</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $product): ?>
                <tr>
                  <td><?php echo isset($product['id']) ? $product['id'] : ''; ?></td>
                  <td><?php echo isset($product['name']) ? htmlspecialchars($product['name']) : ''; ?></td>
                  <td><?php echo isset($product['price']) ? number_format((float)$product['price'], 2) : '0.00'; ?> DA</td>
                  <td><?php echo isset($product['description']) ? htmlspecialchars(substr($product['description'], 0, 50) . (strlen($product['description']) > 50 ? '...' : '')) : ''; ?></td>
                  <td><?php echo isset($product['user_id']) ? htmlspecialchars($product['user_id']) : ''; ?></td>
                  <td class="action-btns">
                    <?php if(isset($product['id'])): ?>
                    <a href="?edit_product=<?php echo $product['id']; ?>" class="btn btn-warning">
                      <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="?delete_product=<?php echo $product['id']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit?');" class="btn btn-danger">
                      <i class="fas fa-trash"></i> Supprimer
                    </a>
                    <?php endif; ?>
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
        <?php if ($edit_product): ?>
          <h3>Modifier le Produit</h3>
          <form method="post" action="">
            <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
            
            <div class="form-group">
              <label for="product_name">Nom du produit</label>
              <input type="text" name="product_name" id="product_name" class="form-control" value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
            </div>
            
            <div class="form-group">
              <label for="product_price">Prix (DA)</label>
              <input type="number" name="product_price" id="product_price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($edit_product['price']); ?>" required>
            </div>
            
            <div class="form-group">
              <label for="product_description">Description</label>
              <textarea name="product_description" id="product_description" class="form-control"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
              <label for="product_user_id">Propriétaire</label>
              <select name="product_user_id" id="product_user_id" class="form-control" required>
                <option value="admin" <?php echo $edit_product['user_id'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                <?php foreach ($users as $user): ?>
                  <option value="<?php echo htmlspecialchars($user['login']); ?>" <?php echo $edit_product['user_id'] === $user['login'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($user['login']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <button type="submit" name="edit_product" class="btn btn-warning">Mettre à jour le produit</button>
            <a href="admin_dashboard.php" class="btn btn-primary">Annuler</a>
          </form>
        <?php else: ?>
          <h3>Ajouter un Nouveau Produit</h3>
          <form method="post" action="">
            <div class="form-group">
              <label for="product_name">Nom du produit</label>
              <input type="text" name="product_name" id="product_name" class="form-control" required>
            </div>
            
            <div class="form-group">
              <label for="product_price">Prix (DA)</label>
              <input type="number" name="product_price" id="product_price" class="form-control" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
              <label for="product_description">Description</label>
              <textarea name="product_description" id="product_description" class="form-control"></textarea>
            </div>
            
            <div class="form-group">
              <label for="product_user_id">Propriétaire</label>
              <select name="product_user_id" id="product_user_id" class="form-control" required>
                <option value="admin">admin</option>
                <?php foreach ($users as $user): ?>
                  <option value="<?php echo htmlspecialchars($user['login']); ?>">
                    <?php echo htmlspecialchars($user['login']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <button type="submit" name="add_product" class="btn btn-success">Ajouter le produit</button>
          </form>
        <?php endif; ?>
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
      
      // Auto-hide alert messages after 5 seconds
      const alerts = document.querySelectorAll('.alert');
      if (alerts.length > 0) {
        setTimeout(function() {
          alerts.forEach(alert => {
            alert.style.display = 'none';
          });
        }, 5000);
      }
      
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