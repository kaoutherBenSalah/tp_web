<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BerbereJewelery - Bijoux Traditionnels</title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #bf9b30;
            --secondary-color: #70592b;
            --dark-color: #333;
            --light-color: #f9f5ea;
            --text-color: #555;
            --heading-font: 'Playfair Display', serif;
            --body-font: 'Roboto', sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--body-font);
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--light-color);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* ----- HEADER ----- */
        .top-bar {
            background-color: var(--dark-color);
            padding: 8px 0;
            color: white;
        }
        
        .top-bar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .social-icons a {
            color: white;
            margin-right: 15px;
            font-size: 16px;
            transition: color 0.3s ease;
        }
        
        .social-icons a:hover {
            color: var(--primary-color);
        }
        
        .auth-buttons a {
            background-color: transparent;
            border: 1px solid white;
            color: white;
            padding: 5px 12px;
            margin-left: 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .auth-buttons a:hover {
            background-color: white;
            color: var(--dark-color);
        }
        
        .main-header {
            padding: 20px 0;
            background-color: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 60px;
            margin-right: 15px;
        }
        
        .logo-text {
            font-family: var(--heading-font);
        }
        
        .logo-text h1 {
            font-size: 24px;
            margin: 0;
            color: var(--dark-color);
        }
        
        .logo-text span {
            color: var(--primary-color);
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 25px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            font-size: 15px;
            position: relative;
            padding-bottom: 5px;
            transition: color 0.3s ease;
        }
        
        nav ul li a:hover, nav ul li.active a {
            color: var(--primary-color);
        }
        
        nav ul li.active a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        /* ----- HERO SLIDER ----- */
        .hero-slider {
            position: relative;
            height: 500px;
            overflow: hidden;
        }
        
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease;
            background-size: cover;
            background-position: center;
        }
        
        .slide.active {
            opacity: 1;
        }
        
        .slide-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
        }
        
        .slide-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            margin: 0 6px;
            cursor: pointer;
        }
        
        .slide-dot.active {
            background-color: var(--primary-color);
        }
        
        .slide-arrows {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            transform: translateY(-50%);
        }
        
        .slide-arrows button {
            background-color: rgba(0, 0, 0, 0.3);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }
        
        .slide-arrows button:hover {
            background-color: rgba(0, 0, 0, 0.6);
        }
        
        /* ----- FEATURED PRODUCTS ----- */
        .section-title {
            text-align: center;
            margin: 50px 0 30px;
            position: relative;
        }
        
        .section-title h2 {
            font-family: var(--heading-font);
            font-size: 32px;
            color: var(--dark-color);
            display: inline-block;
            position: relative;
        }
        
        .section-title h2 span {
            color: var(--primary-color);
        }
        
        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            width: 60%;
            height: 2px;
            background-color: var(--primary-color);
            transform: translateX(-50%);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin: 40px 0;
        }
        
        .product-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .product-image {
            height: 280px;
            width: 100%;
            overflow: hidden;
            position: relative;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-details {
            padding: 20px;
            text-align: center;
        }
        
        .product-title {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        .product-price {
            color: var(--primary-color);
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .add-to-cart {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .add-to-cart:hover {
            background-color: var(--secondary-color);
        }

        /* ----- Content Pages ----- */
        .page-content {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 30px;
            margin: 30px 0;
        }
        
        /* ----- FOOTER ----- */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 60px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            color: white;
            margin-bottom: 20px;
            font-family: var(--heading-font);
            font-size: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: var(--primary-color);
        }
        
        .footer-column h3 span {
            color: var(--primary-color);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        .contact-info p {
            margin-bottom: 10px;
            color: #ccc;
            display: flex;
            align-items: flex-start;
        }
        
        .contact-info i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .footer-social a {
            display: inline-block;
            margin-right: 15px;
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            width: 36px;
            height: 36px;
            text-align: center;
            line-height: 36px;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }
        
        .footer-social a:hover {
            background-color: var(--primary-color);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            color: #aaa;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
            }
            
            nav ul {
                margin-top: 20px;
            }
            
            .hero-slider {
                height: 400px;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .top-bar-content {
                flex-direction: column;
                gap: 10px;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            nav ul li {
                margin: 5px 10px;
            }
            
            .hero-slider {
                height: 300px;
            }
        }
        
    </style>
</head>
<body>

<?php
    $conn = mysqli_connect("127.0.0.1", "root", "", "tp_web", 3306);
    if (!$conn) {
        die("Erreur de connexion : " . mysqli_connect_error());
    }

    $page = isset($_GET['page']) ? $_GET['page'] : '';
    $hideMenu = in_array($page, ['login', 'register']);
?>

<?php if (!$hideMenu): ?>
<div class="top-bar">
    <div class="container top-bar-content">
        <div class="social-icons">
            <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
            <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
            <a href="https://linkedin.com" target="_blank"><i class="fab fa-linkedin-in"></i></a>
        </div>
        <div class="auth-buttons">
            <a href="index.php?page=login">Connexion</a>
            <a href="index.php?page=register">Inscription</a>
        </div>
    </div>
</div>

<header class="main-header">
    <div class="container header-content">
        <div class="logo">
            <img src="images/logo.png" alt="BerbereJewelery Logo">
            <div class="logo-text">
                <h1>Berbere<span>Jewelery</span></h1>
            </div>
        </div>
        <nav>
            <ul>
                <li class="<?= ($page == '') ? 'active' : '' ?>"><a href="index.php">Accueil</a></li>
                <li class="<?= ($page == 'products') ? 'active' : '' ?>"><a href="index.php?page=products">Produits</a></li>
                <li class="<?= ($page == 'offres') ? 'active' : '' ?>"><a href="index.php?page=offres">Offres</a></li>
                <li class="<?= ($page == 'agences') ? 'active' : '' ?>"><a href="index.php?page=agences">Nos agences</a></li>
                <li class="<?= ($page == 'contacts') ? 'active' : '' ?>"><a href="index.php?page=contacts">Contact</a></li>
            </ul>
        </nav>
    </div>
</header>
<?php endif; ?>

<main>
    <?php
        $allowedPages = ['products', 'offres', 'login', 'register', 'contacts', 'agences'];
        if ($page && in_array($page, $allowedPages)) {
            echo '<div class="container page-content">';
            include("html/$page.html");
            echo '</div>';
        } else if (!$page) {
    ?>
        <!-- Page d'accueil -->
        <section class="hero-slider">
            <div class="slide active" style="background-image: url('images/slider/jpg/image3.jpg')"></div>
            <div class="slide" style="background-image: url('images/slider/jpg/image2.jpg')"></div>
            <div class="slide" style="background-image: url('images/slider/jpg/image4.jpg')"></div>
            
            <div class="slide-arrows">
                <button class="prev-slide"><i class="fas fa-chevron-left"></i></button>
                <button class="next-slide"><i class="fas fa-chevron-right"></i></button>
            </div>
            
            <div class="slide-controls">
                <div class="slide-dot active"></div>
                <div class="slide-dot"></div>
                <div class="slide-dot"></div>
            </div>
        </section>

        <section class="featured-products">
            <div class="container">
                <div class="section-title">
                    <h2>Nos Meilleurs <span>Produits</span></h2>
                </div>
                
                <div class="products-grid">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="images/cartes/bague.png" alt="Bague Kabyle">
                        </div>
                        <div class="product-details">
                            <h3 class="product-title">Bague Kabyle en Argent</h3>
                            <p class="product-price">9 500 DA</p>
                            <a href="#" class="add-to-cart">Ajouter au panier</a>
                        </div>
                    </div>
                    
                    <div class="product-card">
                        <div class="product-image">
                            <img src="images/cartes/bracelet.png" alt="Bracelet Ameclux">
                        </div>
                        <div class="product-details">
                            <h3 class="product-title">Bracelet "Ameclux"</h3>
                            <p class="product-price">139 500 DA</p>
                            <a href="#" class="add-to-cart">Ajouter au panier</a>
                        </div>
                    </div>
                    
                    <div class="product-card">
                        <div class="product-image">
                            <img src="images/cartes/parure.png" alt="Parure">
                        </div>
                        <div class="product-details">
                            <h3 class="product-title">Parure</h3>
                            <p class="product-price">205 000 DA</p>
                            <a href="#" class="add-to-cart">Ajouter au panier</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php 
        } else {
            echo '<div class="container page-content"><h2>Page non trouvée</h2></div>';
        }
    ?>
</main>

<?php if (!$hideMenu): ?>
<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-column">
                <h3>Berbere<span>jewelery</span></h3>
                <p>BerbereJewelery est une entreprise spécialisée dans la vente de bijoux traditionnels berbères avec une touche moderne.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Liens rapides</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="index.php?page=products">Produits</a></li>
                    <li><a href="index.php?page=offres">Offres</a></li>
                    <li><a href="index.php?page=agences">Nos agences</a></li>
                    <li><a href="index.php?page=contacts">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contactez-nous</h3>
                <div class="contact-info">
                    <p><i class="fas fa-map-marker-alt"></i> Adresse, Ville, Pays</p>
                    <p><i class="fas fa-phone"></i> +XX XX XX XX XX</p>
                    <p><i class="fas fa-envelope"></i> contact@berberejewelery.com</p>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>Copyright &copy; <?php echo date('Y'); ?> BerbereJewelery. Tous droits réservés.</p>
        </div>
    </div>
</footer>
<?php endif; ?>

<script>
    // Hero Slider functionality
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slide-dot');
    
    function showSlide(n) {
        // Remove active class from all slides and dots
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        // Set current slide index
        currentSlide = (n + slides.length) % slides.length;
        
        // Add active class to current slide and dot
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }
    
    // Add click event to dots if they exist
    if (dots.length > 0) {
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => showSlide(index));
        });
    }
    
    // Add click event to arrows if they exist
    const prevButton = document.querySelector('.prev-slide');
    const nextButton = document.querySelector('.next-slide');
    
    if (prevButton && nextButton) {
        prevButton.addEventListener('click', () => showSlide(currentSlide - 1));
        nextButton.addEventListener('click', () => showSlide(currentSlide + 1));
        
        // Auto slide
        setInterval(() => showSlide(currentSlide + 1), 5000);
    }
</script>

</body>
</html>