<?php
session_start();

// Activer les erreurs (utile pour déboguer en local)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
$host = 'localhost';
$dbname = 'tp_web';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exc) {
    die('<div class="form"><h3 class="titre">Erreur de connexion : ' . htmlspecialchars($exc->getMessage()) . '</h3></div>');
}

// Vérifier si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pwdInput = $_POST['pwd'] ?? '';
}
    if (empty($login) || empty($pwdInput)) {
        // Rediriger vers la page de connexion avec un message d'erreur
        header("Location: ../html/login.html?error=empty_fields");
        exit();
    }

    // Vérifier l'existence de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM user WHERE login = :login");
    $stmt->execute([':login' => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Vérifier le mot de passe avec hash sécurisé
        if (password_verify($pwdInput, $user['password'])) {
            // Stocker les infos de l'utilisateur dans la session
            // Stocker les infos de l'utilisateur dans la session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'login' => $user['login'],
                'email' => $user['email']
            ];}
      if ($user) {
    // Vérifier le mot de passe avec hash sécurisé
    if (password_verify($pwdInput, $user['password'])) {
        // Stocker les infos de l'utilisateur dans la session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'login' => $user['login'],
            'email' => $user['email']
        ];

        // Rediriger vers la page d'admin si le login est 'admin', sinon vers le dashboard normal
        if ($user['login'] === 'admin') {
            header("Location: ../admin_dashboard.php");
        } else {
            header("Location: /tp_web/Projet/daashboard.php"); // Corrigé selon ton chemin
        }
        exit();
    } else {
        // Mot de passe incorrect
        header("Location: ../html/login.html?error=invalid_password");
        exit();
    }
} else {
    // Utilisateur introuvable
    header("Location: ../html/login.html?error=user_not_found");
    exit();
}}
?>