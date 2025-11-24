<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mental Health Support Platform</title>
    <link rel="stylesheet" href="index.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>

<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="#about-us">About Us</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="signup.php">Sign Up</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <i class="fas fa-bars" id="MenuBtn"></i>
        </nav>
        <section class="banner">
            <div class="banner-content">
                <h1>We’re Here to Help You</h1>
                <p>Your mental health matters. Find support today.</p>
            </div>
        </section>
    </header>

    <main>
        <section class="find-psychologist">
            <h2>Find a Psychologist</h2>
            <input type="text" placeholder="Search psychologists..." id="search-field">
        </section>

        <section class="tips">
            <h2>Mental Health Tips</h2>
            <div class="tip-icons">
                <div class="tip">Take Breaks to Recharge</div>
                <div class="tip">Stay Connected with Loved One</div>
                <div class="tip">Practice Daily Gratitude</div>
            </div>
        </section>

        <section class="testimonials">
            <h2>What Our Users Say</h2>
            <div class="testimonial">
                <p>"This platform changed my life."</p>
            </div>
            <div class="testimonial">
                <p>"I found the help I needed."</p>
            </div>
            <div class="testimonial">
                <p>"Highly recommended for anyone in need."</p>
            </div>
        </section>

        <section id="about-us">
            <h2>About Us</h2>
            <div class="about-content">
                <div class="about-box">
                    <h3>Our Mission</h3>
                    <p>Our mission is to provide a safe and supportive environment where individuals can find the mental health support they need. We aim to break the stigma around mental health by making professional help more accessible.</p>
                </div>
                <div class="about-box">
                    <h3>Our Values</h3>
                    <ul>
                        <li><strong>Confidentiality:</strong> Ensuring private and secure interactions.</li>
                        <li><strong>Empathy:</strong> Understanding unique challenges.</li>
                        <li><strong>Inclusivity:</strong> Support for all, regardless of background.</li>
                        <li><strong>Professionalism:</strong> Connecting with qualified professionals.</li>
                    </ul>
                </div>
                <div class="about-box">
                    <h3>Why Choose Us</h3>
                    <p>We ensure you have access to the best mental health support. Let us help you take the next step in your mental health journey.</p>
                </div>
            </div>
        </section>

        <section id="services">
            <h2>Our Services</h2>
            <div class="services-container">
                <!-- Find Psychologists -->
                <div class="service">
                    <h3>Find Psychologists</h3>
                    <p>Browse our directory of qualified psychologists and find the right match for your mental health needs.</p>
                </div>
                <!-- Book Appointments -->
                <div class="service">
                    <h3>Book Appointments</h3>
                    <p>Easily schedule appointments with psychologists through our user-friendly booking system.</p>
                </div>
                <!-- Medication Management -->
                <div class="service">
                    <h3>Manage Medications</h3>
                    <p>Track and manage prescribed medications with personalized reminders and information.</p>
                </div>
                <!-- Hospital Suggestions -->
                <div class="service">
                    <h3>Hospital Suggestions</h3>
                    <p>Get recommendations for trusted hospitals and mental health facilities.</p>
                </div>
            </div>
        </section>

        <section id="contact">
            <h1>Any Questions?</h1>
            <div class="contact-form-container">
                <iframe src="https://docs.google.com/forms/d/e/1FAIpQLSclDnkg-7qJVsjnCVr552m-216SjleTeCZX7GOfMjjl_EENUg/viewform?embedded=true"
                    width="100%" height="600" frameborder="0" marginheight="0" marginwidth="0">Loading…</iframe>
            </div>
            <div class="contact-map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.429463731249!2d85.33100831506106!3d27.705235982793842!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb1900bdaac9d3%3A0x3c8902fa7d70548b!2sBasundhara%2C%20Kathmandu%2044600!5e0!3m2!1sen!2snp!4v1697109398357!5m2!1sen!2snp"
                    allowfullscreen=""
                    loading="lazy">
                </iframe>
            </div>
        </section>

    </main>

    <footer>
        <div class="footer-container">
            <!-- About Section -->
            <div class="footer-section">
                <h3>About Us</h3>
                <p>Your trusted partner in mental health support, providing resources and services to foster emotional well-being.</p>
            </div>

            <!-- Quick Links Section -->
            <div class="footer-section">
                <h3>Quick Links</h3>
                <li><a href="index.php">Home</a></li>
                <li><a href="#about-us">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#contact">Contact</a></li>
            </div>

            <!-- Contact Section -->
            <div class="footer-section">
                <h3>Contact Us</h3>
                <li>Email: support@mentalhealth.com</li>
                <li>Phone: +977 9769761449</li>
                <li>Address: Basundhara, Kathmandu</li>
                <li>1166 National Helpline for Suicide Prevention</li>
            </div>
        </div>

        <div class="footer-bottom">
        <?php echo '&copy; ' . date('Y') . ' Mental Health Support Platform. All rights reserved.'; ?>
        </div>
    </footer>

    <!-- Modal for login prompt -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Login Required</h2><br>
            <p>You need to log in to search for a psychologist.</p>
            <a href="login.php"><button id="login-btn">Log In</button>
        </div>
    </div>
    <script src="index.js"></script>
</body>

</html>