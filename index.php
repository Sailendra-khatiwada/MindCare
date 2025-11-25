<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Mental Health Support Platform</title>

  <!-- New calm / therapeutic theme CSS -->
  <link rel="stylesheet" href="index.css" />

  <!-- Google fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Nunito:wght@400;600&display=swap" rel="stylesheet">

  <!-- icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="theme-calm">

  <header class="site-header">
    <div class="container header-inner">
      <a class="brand" href="index.php">
        <span class="brand-mark" aria-hidden="true">🕊️</span>
        <span class="brand-text">MindCare</span>
      </a>

      <nav class="nav" aria-label="Primary">
        <ul class="nav-list">
          <li><a href="index.php">Home</a></li>
          <li><a href="#about-us">About</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="#testimonials">Testimonials</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </nav>

      <div class="header-actions">
        <a class="btn btn-ghost" href="signup.php">Sign Up</a>
        <a class="btn btn-primary" href="login.php">Log In</a>
        <button id="menu-toggle" class="hamburger" aria-label="Open menu">
          <i class="fa fa-bars"></i>
        </button>
      </div>
    </div>

    <!-- Hero -->
    <div class="hero">
      <div class="container hero-grid">
        <div class="hero-card">
          <h1>We’re here to help you feel better</h1>
          <p class="lead">Accessible, confidential and compassionate mental health care — find a psychologist, book a session, and get support when you need it.</p>

          <div class="hero-cta">
            <a class="btn btn-xl btn-primary" href="signup.php">Get Started</a>
            <a class="btn btn-outline" href="#services">Explore Services</a>
          </div>

          <ul class="hero-features">
            <li><i class="fa fa-user-md"></i> Licensed professionals</li>
            <li><i class="fa fa-calendar-check"></i> Easy booking</li>
            <li><i class="fa fa-lock"></i> Private & secure</li>
          </ul>
        </div>

        <div class="hero-visual" aria-hidden="true">
          <!-- Soft illustration / card stack -->
          <div class="card-illustration">
            <div class="card card-1">Counseling</div>
            <div class="card card-2">Appointments</div>
            <div class="card card-3">Resources</div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main>
    <section class="search-section container">
      <div class="search-inner">
        <h2>Find a psychologist</h2>
        <form id="search-form" onsubmit="return false;">
          <label for="search-field" class="visually-hidden">Search psychologists</label>
          <input id="search-field" name="q" type="search" placeholder="Search by name, speciality or location…" />
          <button class="btn btn-search" onclick="openSearch()">Search</button>
        </form>
      </div>
    </section>

    <section id="services" class="container services-section">
      <h2>Our Services</h2>
      <div class="services-grid">
        <article class="service-card">
          <i class="fa fa-user-md icon"></i>
          <h3>Find Psychologists</h3>
          <p>Browse qualified professionals and choose a therapist who understands you.</p>
        </article>

        <article class="service-card">
          <i class="fa fa-calendar icon"></i>
          <h3>Book Appointments</h3>
          <p>Schedule sessions with flexible timings and get reminders.</p>
        </article>

        <article class="service-card">
          <i class="fa fa-pills icon"></i>
          <h3>Medication Management</h3>
          <p>Track and manage prescribed medications with gentle reminders.</p>
        </article>

        <article class="service-card">
          <i class="fa fa-hospital icon"></i>
          <h3>Hospital Suggestions</h3>
          <p>Get trusted referrals and local resources quickly.</p>
        </article>
      </div>
    </section>

    <section id="testimonials" class="container testimonials-section">
      <h2>What Our Users Say</h2>
      <div class="testi-grid">
        <blockquote class="testi">“This platform changed my life. The therapist listened and helped me through a hard time.”</blockquote>
        <blockquote class="testi">“I found the help I needed quickly and privately.”</blockquote>
        <blockquote class="testi">“Highly recommended — caring professionals and easy booking.”</blockquote>
      </div>
    </section>

    <section id="about-us" class="container about-section">
      <h2>About Us</h2>
      <div class="about-grid">
        <div class="about-card">
          <h3>Our Mission</h3>
          <p>To create an accessible and stigma-free space for mental health support.</p>
        </div>
        <div class="about-card">
          <h3>Our Values</h3>
          <ul>
            <li>Confidentiality</li>
            <li>Empathy</li>
            <li>Inclusivity</li>
            <li>Professionalism</li>
          </ul>
        </div>
        <div class="about-card">
          <h3>Why Choose Us</h3>
          <p>Evidence-based care, experienced professionals, and compassionate support.</p>
        </div>
      </div>
    </section>

    <section id="contact" class="container contact-section">
      <h2>Contact & Support</h2>
      <div class="contact-grid">
        <div class="contact-form">
          <iframe src="https://docs.google.com/forms/d/e/1FAIpQLSclDnkg-7qJVsjnCVr552m-216SjleTeCZX7GOfMjjl_EENUg/viewform?embedded=true"
            width="100%" height="520" frameborder="0" marginheight="0" marginwidth="0">Loading…</iframe>
        </div>
        <div class="contact-info">
          <h3>Emergency & Helpline</h3>
          <p>National Helpline for Suicide Prevention: <strong>1166</strong></p>
          <h4>Contact</h4>
          <p>Email: support@mentalhealth.com<br/>Phone: +977 9769761449</p>
          <div class="map">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.429463731249!2d85.33100831506106!3d27.705235982793842!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb1900bdaac9d3%3A0x3c8902fa7d70548b!2sBasundhara%2C%20Kathmandu%2044600!5e0!3m2!1sen!2snp!4v1697109398357!5m2!1sen!2snp"
              width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div class="footer-col">
        <h4>About MindCare</h4>
        <p>Trusted partner in mental health support — resources and services for emotional well-being.</p>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul class="footer-links">
          <li><a href="index.php">Home</a></li>
          <li><a href="#about-us">About</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <ul class="footer-links">
          <li>Email: support@mentalhealth.com</li>
          <li>Phone: +977 9769761449</li>
          <li>Address: Basundhara, Kathmandu</li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom container">
      <?php echo '&copy; ' . date('Y') . ' MindCare. All rights reserved.'; ?>
    </div>
  </footer>

  <!-- modal and scripts (reused) -->
  <div id="login-modal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <button class="modal-close" aria-label="Close">&times;</button>
      <h2>Login Required</h2>
      <p>You need to log in to search for a psychologist.</p>
      <a class="btn btn-primary" href="login.php">Log In</a>
    </div>
  </div>

  <script>
    // =========================
// MOBILE MENU TOGGLE
// =========================
const MenuBtn = document.getElementById("MenuBtn");
const navMenu = document.querySelector(".nav-list");

if (MenuBtn) {
    MenuBtn.addEventListener("click", () => {
        document.body.classList.toggle("mobile-nav-active");
    });
}

// Close menu when clicking outside
document.addEventListener("click", (event) => {
    if (
        MenuBtn &&
        !MenuBtn.contains(event.target) &&
        navMenu &&
        !navMenu.contains(event.target)
    ) {
        document.body.classList.remove("mobile-nav-active");
    }
});


// =========================
// LOGIN MODAL
// =========================
const modal = document.getElementById("login-modal");
const searchField = document.getElementById("search-field");
const closeModalBtn = document.querySelector(".modal-close");

if (searchField && modal) {
    searchField.addEventListener("click", () => {
        modal.style.display = "flex";
    });
}

if (closeModalBtn && modal) {
    closeModalBtn.addEventListener("click", () => {
        modal.style.display = "none";
    });
}

// Close modal by clicking outside
document.addEventListener("click", (event) => {
    if (modal && event.target === modal) {
        modal.style.display = "none";
    }
});


  </script>
</body>
</html>
