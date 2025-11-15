<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description"
        content="University Guidance Counseling Services - Your safe space for support and guidance" />
    <meta name="keywords" content="counseling, guidance, university, support, mental health, student wellness" />
    <title>Our Services - Counselign</title>
    <link rel="icon" href="<?= base_url('Photos/counselign.ico') ?>" sizes="16x16 32x32" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('css/services.css') ?>">
</head>

<body>
    <header class="text-white p-1" style="background-color: #060E57;">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="d-flex align-items-center">
                    <img src="Photos/counselign_logo.png" alt="UGC Logo" class="logo" />
                    <h1 class="h4 fw-bold ms-2 mb-0">Counselign</h1>
                    
                    <!-- Responsive Back Button -->
                    <nav class="navbar navbar-expand-lg navbar-dark ms-auto">
                        <ul class="navbar-nav nav-links">
                            <li class="nav-item">
                                <a class="nav-link back-button" href="<?= base_url() ?>">
                                    <i class="fas fa-arrow-left"></i>
                                    <span class="back-text">Back</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="content-panel">
            <h3 class="main-title"><b>Our Services</b></h3>
            <p class="dynamic-text intro-text">
                The University Guidance Counseling Center offers comprehensive support services designed to enhance your
                academic success, personal growth, and career development. Our professional counselors are here to help
                you navigate your university journey.
            </p>

            <div class="service-grid">
                <!-- Academic Counseling Card -->
                <div class="service-card">
                    <i class="fas fa-user-graduate service-icon"></i>
                    <h4 class="service-title">Academic Counseling</h4>
                    <p class="dynamic-text">Expert guidance for your academic journey and success strategies.</p>
                    <ul class="service-list">
                        <li><i class="fas fa-check"></i> Study skills development</li>
                        <li><i class="fas fa-check"></i> Time management coaching</li>
                        <li><i class="fas fa-check"></i> Test anxiety management</li>
                        <li><i class="fas fa-check"></i> Academic goal setting</li>
                    </ul>
                </div>

                <!-- Personal Counseling Card -->
                <div class="service-card">
                    <i class="fas fa-heart service-icon"></i>
                    <h4 class="service-title">Personal Counseling</h4>
                    <p class="dynamic-text">Confidential support for personal challenges and growth.</p>
                    <ul class="service-list">
                        <li><i class="fas fa-check"></i> Stress management</li>
                        <li><i class="fas fa-check"></i> Anxiety & depression support</li>
                        <li><i class="fas fa-check"></i> Relationship counseling</li>
                        <li><i class="fas fa-check"></i> Self-esteem building</li>
                    </ul>
                </div>

                <!-- Career Counseling Card -->
                <div class="service-card">
                    <i class="fas fa-briefcase service-icon"></i>
                    <h4 class="service-title">Career Counseling</h4>
                    <p class="dynamic-text">Professional guidance for your career development journey.</p>
                    <ul class="service-list">
                        <li><i class="fas fa-check"></i> Career assessment</li>
                        <li><i class="fas fa-check"></i> Resume writing support</li>
                        <li><i class="fas fa-check"></i> Interview preparation</li>
                        <li><i class="fas fa-check"></i> Professional networking</li>
                    </ul>
                </div>
            </div>

            <!-- Support Programs Section -->
            <div class="support-programs-section">
                <h4 class="support-title">Additional Support Programs</h4>
                <div class="support-grid">
                    <div class="support-card">
                        <i class="fas fa-users support-icon"></i>
                        <h5 class="support-card-title">Group Workshops</h5>
                        <p class="dynamic-text">Interactive sessions focusing on personal development and skill-building.</p>
                    </div>
                    <div class="support-card">
                        <i class="fas fa-graduation-cap support-icon"></i>
                        <h5 class="support-card-title">Peer Mentoring</h5>
                        <p class="dynamic-text">Connect with experienced student mentors for guidance and support.</p>
                    </div>
                    <div class="support-card">
                        <i class="fas fa-laptop support-icon"></i>
                        <h5 class="support-card-title">Online Resources</h5>
                        <p class="dynamic-text">Access our digital library of self-help materials and tools.</p>
                    </div>
                    <div class="support-card">
                        <i class="fas fa-medkit support-icon"></i>
                        <h5 class="support-card-title">Crisis Support</h5>
                        <p class="dynamic-text">24/7 emergency support for urgent mental health concerns.</p>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="cta-section">
                <h4 class="cta-title">Ready to Get Started?</h4>
                <p class="dynamic-text cta-text">Our services are confidential and available to all
                    university students.</p>
                    <a href="<?= base_url('?open=login') ?>" class="cta-button">Get Started</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="copyright">
                <b>© 2025 Counselign Team. All rights reserved.</b>
            </div>
        </div>
    </footer>

    <script src="<?= base_url('js/services.js') ?>"></script>
</body>

</html>