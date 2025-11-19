<?php
session_start();
$user = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Portal - Welcome</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ✅ CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="assets/favicon.png">
<link href="../home/home.css" rel="stylesheet">
<link rel="stylesheet" href="../footer/footer.css">

</head>

<body>

<!-- 🌐 Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container">
    <a class="navbar-brand" href="home.php"><i class="fa-solid fa-graduation-cap me-2"></i>Student Portal</a>
    <div class="ms-auto d-flex align-items-center">
      <button class="toggle-dark me-3" id="themeToggle" title="Toggle Dark Mode"><i class="fa-solid fa-moon"></i></button>

      <?php if ($user): ?>
        <span class="me-3 fw-medium welcome-text">Welcome, <?= htmlspecialchars($user); ?> (<?= htmlspecialchars($role); ?>)</span>
        <a href="../logout.php" class="btn btn-outline-secondary2">Logout</a>
      <?php else: ?>
        <a href="../login_register/login_register.php" class="btn btn-login me-2">Login</a>
        <a href="../login_register/login_register.php" class="btn btn-outline-secondary">Register</a>
      <?php endif; ?>

    </div>
  </div>
</nav>


<!-- 🌈 Hero -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-text" data-aos="fade-down">
      <h1>Welcome to Student Portal</h1>
      <p>Track your progress, access learning resources, and stay connected with your academic journey — all in one place.</p>
      <?php if(!$user): ?>
        <a href="../login_register/login_register.php" class="btn mt-2" data-aos="zoom-in">Get Started</a>
      <?php else: ?>
        <a href="<?= $role==='admin'?'../admin/dashboard.php':'../student/dashboard.php' ?>" class="btn mt-2" data-aos="zoom-in">Go to Dashboard</a>
      <?php endif; ?>
    </div>
    <div class="hero-video">
      <video autoplay muted loop playsinline>
        <source src="includes/assets/intro.mp4" type="video/mp4">
      </video>
    </div>
  </div>
</section>

<!-- 💫 Swiper Feature Section -->
<section id="features" class="container py-5">
  <h2 class="text-center mb-5 fw-bold" data-aos="fade-up">Explore Our Features</h2>
  <div class="swiper mySwiper" data-aos="fade-up">
    <div class="swiper-wrapper">

      <div class="swiper-slide">
        <div class="feature-card">
          <div class="icon-wrapper"><i class="fa-solid fa-user-graduate"></i></div>
          <h5 class="mt-3">Personal Dashboard</h5>
          <p>Monitor your courses, grades, and progress with interactive charts and insights.</p>
        </div>
      </div>

      <div class="swiper-slide">
        <div class="feature-card">
          <div class="icon-wrapper"><i class="fa-solid fa-book-open"></i></div>
          <h5 class="mt-3">Learning Resources</h5>
          <p>Access your lectures, assignments, and study materials anytime, anywhere.</p>
        </div>
      </div>

      <div class="swiper-slide">
        <div class="feature-card">
          <div class="icon-wrapper"><i class="fa-solid fa-chart-line"></i></div>
          <h5 class="mt-3">Progress Analytics</h5>
          <p>Visualize your learning trends with detailed analytics and reports.</p>
        </div>
      </div>

      <div class="swiper-slide">
        <div class="feature-card">
          <div class="icon-wrapper"><i class="fa-solid fa-bell"></i></div>
          <h5 class="mt-3">Smart Notifications</h5>
          <p>Receive instant alerts about deadlines, announcements, and academic events.</p>
        </div>
      </div>

    </div>
    <div class="swiper-pagination"></div>
  </div>
</section>

<!-- 🚀 Launch Section -->
<section class="launch-section">
  <video autoplay muted loop playsinline class="launch-video">
    <source src="includes/assets/intro2.mp4" type="video/mp4">
  </video>
  <div class="launch-overlay">
    <h2 data-aos="fade-up">Discover More</h2>
    <p data-aos="fade-up" data-aos-delay="200">Experience the next generation of learning with stunning visuals and seamless interaction.</p>
    <a href="../login_register/login_register.php" class="btn btn-light mt-3" data-aos="zoom-in">Learn More</a>
  </div>
</section>

<?php include __DIR__ . '/../footer/footer.php'; ?>



<!-- ✅ JS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
AOS.init({ duration:800, offset:100, easing:'ease-out-back' });

// 🌙 Dark mode toggle
const body=document.body, toggleBtn=document.getElementById('themeToggle');
if(localStorage.getItem('theme')==='dark'){body.classList.add('dark'); toggleBtn.innerHTML='<i class="fa-solid fa-sun"></i>';}
toggleBtn.onclick=()=>{body.classList.toggle('dark');
toggleBtn.innerHTML=body.classList.contains('dark')?'<i class="fa-solid fa-sun"></i>':'<i class="fa-solid fa-moon"></i>';
localStorage.setItem('theme', body.classList.contains('dark')?'dark':'light');};

// 🎥 Hero Parallax
const heroText=document.querySelector('.hero-text');
window.addEventListener('scroll', ()=>{
  const heroVideo=document.querySelector('.hero-video');
  if(heroVideo) heroVideo.style.transform=`translateY(${window.scrollY*0.2}px)`;
  if(heroText) heroText.style.transform=`translateY(${window.scrollY*0.1}px)`;
});

// 💡 Swiper 3D Coverflow (Sequential logic A→B→C→D)
const featureSwiper = new Swiper(".mySwiper", {
  effect: "coverflow",
  grabCursor: true,
  centeredSlides: true,
  loop: false,               // Sequential, not loop
  slidesPerView: "auto",
  spaceBetween: 60,
  initialSlide: 1,           // Start from 2nd slide (index 1)
  coverflowEffect: {
    rotate: 30,
    stretch: 0,
    depth: 200,
    modifier: 1,
    slideShadows: true
  },
  pagination: { el: ".swiper-pagination", clickable: true },
  breakpoints: { 768: { spaceBetween: 50 }, 1024: { spaceBetween: 80 } }
});


// 🚀 Launch Section Animation
const launch=document.querySelector('.launch-section');
window.addEventListener('scroll', ()=>{
  if(window.scrollY > launch.offsetTop - window.innerHeight/1.2){
    launch.style.opacity='1';
    launch.style.transform='translateY(0) scale(1)';
  } else {
    launch.style.opacity='0';
    launch.style.transform='translateY(50px) scale(0.95)';
  }
});
</script>

</body>
</html>
