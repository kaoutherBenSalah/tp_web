<?php
session_start();

// Redirect if user not logged in
if (!isset($_SESSION['user'])) {
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

// Get user login from session
$login = $_SESSION['user']['login'];

// Retrieve user's products - Use login as user_id
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = :login");
$stmt->execute([':login' => $login]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Profile update processing
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

// Account deletion processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $password = $_POST['delete_password'] ?? '';
    
    // Verify password
    $stmt = $pdo->prepare("SELECT password FROM user WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data && password_verify($password, $user_data['password'])) {
        // Delete user's products first
        $stmt = $pdo->prepare("DELETE FROM products WHERE user_id = :login");
        $stmt->execute([':login' => $login]);
        
        // Delete user account
        $stmt = $pdo->prepare("DELETE FROM user WHERE login = :login");
        $stmt->execute([':login' => $login]);
        
        // Destroy session and redirect
        session_unset();
        session_destroy();
        header("Location: /tp_web/Projet/index.php?deleted=1");
        exit();
    } else {
        $delete_error = true;
    }
}

// Product addition processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name'] ?? '');
    $product_price = filter_var($_POST['product_price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $product_type = trim($_POST['product_type'] ?? '');
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
            ':user_id' => $login,
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
        header("Location: dashboard.php?product_added=1");
        exit();
    } else {
        $product_error = true;
    }
}

// Product deletion processing
if (isset($_GET['delete_product'])) {
    $product_id = filter_var($_GET['delete_product'], FILTER_VALIDATE_INT);
    
    if ($product_id) {
        // Get product name before deletion for consistency
        $stmt = $pdo->prepare("SELECT P_NAME FROM products WHERE id = :id AND user_id = :login");
        $stmt->execute([':id' => $product_id, ':login' => $login]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Delete the product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id AND user_id = :login");
            $stmt->execute([':id' => $product_id, ':login' => $login]);
            
            // Redirect
            header("Location: dashboard.php?product_deleted=1");
            exit();
        }
    }
}

// Retrieve products again after modifications
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = :login");
$stmt->execute([':login' => $login]);
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

    .product-list {
      margin-top: 20px;
    }

    .product-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px;
      border-bottom: 1px solid #eee;
    }

    .product-item:last-child {
      border-bottom: none;
    }

    .product-actions {
      display: flex;
      gap: 10px;
    }

    .product-price {
      font-weight: bold;
      color: #4a6fa5;
    }

    .dark-mode .product-price {
      color: #7ba6e9;
    }

    textarea.form-control {
      min-height: 100px;
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
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>Berbere Jewelry</h2>
    <ul>
      <li><a href="#" class="tab-link" data-tab="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
      <li><a href="#" class="tab-link" data-tab="profile"><i class="fas fa-user"></i> Mon Profil</a></li>
      <li><a href="#" class="tab-link" data-tab="products"><i class="fas fa-shopping-cart"></i> Mes Produits</a></li>
      <li><a href="#" class="tab-link" data-tab="add-product"><i class="fas fa-plus"></i> Ajouter un Produit</a></li>
      <li><a href="#" class="tab-link" data-tab="settings"><i class="fas fa-cog"></i> Paramètres</a></li>
      <li><a href="/tp_web/Projet/index.php" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul>
  </div>

  <div class="main-content">
    <button class="toggle-btn" onclick="document.body.classList.toggle('dark-mode')">
      <i class="fas fa-moon"></i> Theme
    </button>

    <div class="header">
      <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['user']['login']); ?>!</h1>
      <div class="notifications">
        <i class="fas fa-bell"></i>
      </div>
    </div>

    <!-- Notification messages -->
    <?php if (isset($update_success)): ?>
      <div class="alert alert-success">Votre profil a été mis à jour avec succès!</div>
    <?php endif; ?>
    
    <?php if (isset($password_error)): ?>
      <div class="alert alert-danger">Le mot de passe actuel est incorrect.</div>
    <?php endif; ?>
    
    <?php if (isset($delete_error)): ?>
      <div class="alert alert-danger">Impossible de supprimer le compte. Mot de passe incorrect.</div>
    <?php endif; ?>
    
    <?php if (isset($product_error)): ?>
      <div class="alert alert-danger">Veuillez remplir tous les champs obligatoires.</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['product_added'])): ?>
      <div class="alert alert-success">Le produit a été ajouté avec succès!</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['product_deleted'])): ?>
      <div class="alert alert-success">Le produit a été supprimé avec succès!</div>
    <?php endif; ?>

    <!-- Tabs Buttons -->
    <div class="tab-buttons">
      <button class="tab-btn active" data-tab="dashboard">Dashboard</button>
      <button class="tab-btn" data-tab="profile">Mon Profil</button>
      <button class="tab-btn" data-tab="products">Mes Produits</button>
      <button class="tab-btn" data-tab="settings">Paramètres</button>
    </div>

    <!-- Tab Content -->
    <div id="dashboard" class="tab-content active">
      <div class="cards">
        <div class="card">
          <h3><i class="fas fa-user-circle"></i> Information du Compte</h3>
          <p><strong>Login:</strong> <?php echo htmlspecialchars($_SESSION['user']['login']); ?></p>
          <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user']['email'] ?? 'Non défini'); ?></p>
        </div>

        <div class="card">
          <h3><i class="fas fa-box"></i> Mes Produits</h3>
          <p>Vous avez <?php echo count($products); ?> produit(s) enregistré(s).</p>
          <p><a href="#" class="tab-link" data-tab="products">Voir tous les produits</a></p>
        </div>

        <div class="card">
          <h3><i class="fas fa-envelope-open-text"></i> Messages</h3>
          <p>Vous avez 0 nouveaux messages.</p>
        </div>

        <div class="card">
          <h3><i class="fas fa-cog"></i> Paramètres du Compte</h3>
          <p>Mettez à jour vos préférences et votre mot de passe.</p>
          <p><a href="#" class="tab-link" data-tab="settings">Modifier les paramètres</a></p>
        </div>
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
        <?php else: ?>
          <div class="product-list">
            <?php foreach ($products as $product): ?>
              <div class="product-item">
                <div>
                  <strong><?php echo htmlspecialchars($product['P_NAME']); ?></strong>
                  <span class="product-price"><?php echo number_format($product['P_Price'], 2); ?> DA</span>
                </div>
                <div class="product-actions">
                  <a href="?delete_product=<?php echo $product['P_NAME']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit?');" class="btn btn-danger">
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
        // Fixed JavaScript error - proper string literal syntax
        const tabElement = document.querySelector(`.tab-btn[data-tab="${tabParam}"]`);
        if (tabElement) {
          tabElement.click();
        }
      }
    });
  </script>
</body>
</html>