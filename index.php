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
<body>
  <!-- Header -->
  <header>
    <h1>GRUPO LUX</h1>
    <button class="btn-login" onclick="alert('Iniciar sesión presionado')">Iniciar Sesión</button>
  </header>

  <!-- Hero con carrusel -->
  <section class="hero">
    <div class="carousel">
      <img src="imagenes/20f620f7e65a445102a3d63a76cb1afc.jpg" class="carousel-img active" alt="Imagen 1">
      <img src="imagenes/78c3f248040747417f5d43a999ba5a58.jpg" class="carousel-img" alt="Imagen 2">
      <img src="imagenes/3.jpg" class="carousel-img" alt="Imagen 3">
      <img src="https://picsum.photos/id/1024/1600/900" class="carousel-img" alt="Imagen 4">
      <div class="hero-text">
        <h2>TUS NECESIDADES</h2>
        <p>con tan solo un click</p>
      </div>
    </div>
  </section>

  <!-- Icon Buttons -->
  <section class="icons">
    <div class="icon-btn" onclick="alert('Farmacia seleccionada')">
      <i>➕</i>
      <p>FARMACIA</p>
    </div>
    <div class="icon-btn" onclick="alert('Librería y papelería seleccionada')">
      <i>📖</i>
      <p>LIBRERÍA Y PAPELERÍA</p>
    </div>
    <div class="icon-btn" onclick="alert('Electrodomésticos y tecnología seleccionada')">
      <i>💻</i>
      <p>ELECTRODOMÉSTICOS Y TECNOLOGÍA</p>
    </div>
    <div class="icon-btn" onclick="alert('Consultorio seleccionado')">
      <i>🏥</i>
      <p>CONSULTORIO</p>
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

