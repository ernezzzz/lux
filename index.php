<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="css/styles.css" />
  <title>Grupo Lux</title>
  <style>
    /* Reset */

  </style>
</head>
<body class="bindex">
  <!-- Header -->
  <header>
    <h1>GRUPO LUX</h1>
    <button class="btn-login"><a href="backend/loginform.php">Iniciar Sesión</button>
  </header>

  <!-- Hero con carrusel -->
  <section class="hero">
    <div class="carousel">
      <img src="https://tecnyfarma.com/wp-content/uploads/2023/09/DSC1737-scaled.jpg" class="carousel-img active" alt="Imagen 1">
      <img src="https://concentra.com.ar/wp-content/uploads/cuantas-librerias-existen-en-mexico.webp" class="carousel-img" alt="Imagen 2">
      <img src="https://hailekitchen.com/wp-content/uploads/2021/12/web_4030SW155thTerr_02.jpeg" class="carousel-img" alt="Imagen 3">
      <img src="https://investin.org/cdn/shop/articles/Doctor_Holding_Stethoscope.jpg?v=1645111758" class="carousel-img" alt="Imagen 4">
      <div class="hero-text">
        <h2>TUS NECESIDADES</h2>
        <p>con tan solo un click</p>
      </div>
    </div>
  </section>

  <!-- Icon Buttons -->
  <section class="icons">
    <div class="icon-btn"> <a href="backend/loginform.php">
      <i>➕</i>
      <p>FARMACIA</p> </a>
    </div>
    <div class="icon-btn"> <a href="libreria/dashboard.php">
      <i>📖</i>
      <p>LIBRERÍA Y PAPELERÍA</p> </a>
    </div>
    <div class="icon-btn"> <a href="backend/loginform.php">
      <i>💻</i>
      <p>ELECTRODOMÉSTICOS Y TECNOLOGÍA</p> </a>
    </div>
    <div class="icon-btn"> <a href="backend/loginform.php">
      <i>🏥</i>
      <p>CONSULTORIO</p> </a>
    </div>
  </section>


 <script>
    // Carrusel de imágenes con fade/crossfade
    const images = document.querySelectorAll('.carousel-img');
    let current = 0;
    const interval = 5000;

    function showImage(idx) {
      images.forEach((img, i) => {
        img.classList.toggle('active', i === idx);
      });
    }

    setInterval(() => {
      current = (current + 1) % images.length;
      showImage(current);
    }, interval);

    // Dinamismo en los botones
    document.querySelectorAll('.icon-btn').forEach(btn => {
      btn.addEventListener('mouseenter', () => {
        btn.style.boxShadow = '0px 8px 16px rgba(0,0,0,0.2)';
      });
      btn.addEventListener('mouseleave', () => {
        btn.style.boxShadow = 'none';
      });
    });
  </script>

</body>
</html>

