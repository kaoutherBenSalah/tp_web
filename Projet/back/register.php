<?php
session_start(); // Pour gérer la session si besoin

$host = 'localhost';
$dbname = 'tp_web';
$username = 'root';
$password = ''; // Mot de passe vide si XAMPP

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['insert'])) {
    try {
        // Connexion à la base de données
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Récupérer les données
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $login = trim($_POST['login'] ?? '');
        $rawPassword = $_POST['pwd'] ?? '';
        $hashedPassword = password_hash($_POST['pwd'], PASSWORD_DEFAULT);

        // Requête d'insertion
        $sql = "INSERT INTO `user` (`f_name`, `l_name`, `email`, `login`, `password`) 
                VALUES (:firstname, :lastname, :email, :login, :password)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            ':firstname' => $firstname,
            ':lastname' => $lastname,
            ':email' => $email,
            ':login' => $login,
            ':password' => $hashedPassword
        ]);

        if ($success) {
            // ✅ Redirection vers la page de connexion
            header("Location: ../html/login.html");
            exit();
        } else {
            echo "<div class='form'>
                    <h3>Échec de l'inscription. Veuillez réessayer.</h3>
                    <a href='../html/register.html' class='btn'>Retour</a>
                  </div>";
        }

    } catch (PDOException $e) {
        echo "<div class='form'><h3>Erreur : " . htmlspecialchars($e->getMessage()) . "</h3></div>";
    }
} else {
    echo "<div class='form'><h3>Accès non autorisé.</h3></div>";
}
?>
