    // Navbar scroll effect
    window.addEventListener('scroll', function() {
    const navbar = document.getElementById('navbar');
    if (window.scrollY > 50) {
    navbar.classList.add('scrolled');
} else {
    navbar.classList.remove('scrolled');
}
});

    // Portfolio slider
    let currentSlideIndex = 0;
    const slides = document.querySelectorAll('.portfolio-slide');
    const dots = document.querySelectorAll('.slider-dot');

    function showSlide(index) {
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));

    slides[index].classList.add('active');
    dots[index].classList.add('active');
}

    function currentSlide(index) {
    currentSlideIndex = index - 1;
    showSlide(currentSlideIndex);
}

    // Auto slide
    setInterval(() => {
    currentSlideIndex = (currentSlideIndex + 1) % slides.length;
    showSlide(currentSlideIndex);
}, 5000);

    // Counter animation
    function animateCounters() {
    const counters = document.querySelectorAll('[data-count]');

    counters.forEach(counter => {
    const target = parseInt(counter.getAttribute('data-count'));
    const increment = target / 100;
    let current = 0;

    const timer = setInterval(() => {
    current += increment;
    if (current >= target) {
    counter.textContent = target;
    clearInterval(timer);
} else {
    counter.textContent = Math.ceil(current);
}
}, 30);
});
}

    // Intersection Observer for counter animation
    const statsSection = document.querySelector('.stats');
    const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            observer.unobserve(entry.target);
        }
    });
});
    observer.observe(statsSection);

    // Modal functions
    function openModal() {
    document.getElementById('contactModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

    function closeModal() {
    document.getElementById('contactModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

    // Close modal on outside click
    window.onclick = function(event) {
    const modal = document.getElementById('contactModal');
    if (event.target === modal) {
    closeModal();
}
}

    // Contact form submission
    document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Відправляється...';
    submitBtn.disabled = true;

    try {
    const response = await fetch('controllers/HomeController.php?action=contact', {
    method: 'POST',
    body: formData
});

    const result = await response.json();

    if (result.success) {
    // Show success message
    alert('Дякуємо! Ваше повідомлення відправлено. Ми зв\'яжемося з вами найближчим часом.');
    this.reset();
    closeModal();
} else {
    alert('Помилка при відправці повідомлення. Спробуйте ще раз.');
}
} catch (error) {
    console.error('Error:', error);
    alert('Помилка при відправці повідомлення. Спробуйте ще раз.');
} finally {
    // Restore button state
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
}
});

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

    // Parallax effect for hero section
    window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const parallax = document.querySelector('.hero');
    const speed = scrolled * 0.5;
    parallax.style.transform = `translateY(${speed}px)`;
});

    // Add animation on scroll
    const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

    const fadeObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

    // Observe elements for fade-in animation
    document.querySelectorAll('.service-card, .section-title, .section-subtitle').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    fadeObserver.observe(el);
});

    // Phone number formatting
    document.getElementById('phone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.startsWith('380')) {
    value = value.substring(3);
}
    if (value.length > 0) {
    if (value.length <= 3) {
    value = `+380 (${value}`;
} else if (value.length <= 6) {
    value = `+380 (${value.substring(0, 3)}) ${value.substring(3)}`;
} else if (value.length <= 8) {
    value = `+380 (${value.substring(0, 3)}) ${value.substring(3, 6)}-${value.substring(6)}`;
} else {
    value = `+380 (${value.substring(0, 3)}) ${value.substring(3, 6)}-${value.substring(6, 8)}-${value.substring(8, 10)}`;
}
}
    e.target.value = value;
});

    // Add loading animation
    window.addEventListener('load', function() {
    document.body.classList.add('loaded');
});

