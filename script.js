// ===== NAVBAR SCROLL EFFECT =====
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  if (window.scrollY > 60) {
    navbar.classList.add('scrolled');
  } else {
    navbar.classList.remove('scrolled');
  }
  updateActiveNav();
});

// ===== HAMBURGER MENU =====
const hamburger = document.getElementById('hamburger');
const navLinks = document.querySelector('.nav-links');

hamburger.addEventListener('click', () => {
  navLinks.classList.toggle('open');
  const spans = hamburger.querySelectorAll('span');
  if (navLinks.classList.contains('open')) {
    spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
    spans[1].style.opacity = '0';
    spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
  } else {
    spans.forEach(s => { s.style.transform = ''; s.style.opacity = ''; });
  }
});

// Close menu on link click
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', () => {
    navLinks.classList.remove('open');
    const spans = hamburger.querySelectorAll('span');
    spans.forEach(s => { s.style.transform = ''; s.style.opacity = ''; });
  });
});

// Close menu on outside click
document.addEventListener('click', (e) => {
  if (!navbar.contains(e.target) && navLinks.classList.contains('open')) {
    navLinks.classList.remove('open');
    const spans = hamburger.querySelectorAll('span');
    spans.forEach(s => { s.style.transform = ''; s.style.opacity = ''; });
  }
});

// ===== ACTIVE NAV LINK =====
function updateActiveNav() {
  const sections = document.querySelectorAll('section[id]');
  const scrollY = window.scrollY + 100;

  sections.forEach(section => {
    const top = section.offsetTop;
    const height = section.offsetHeight;
    const id = section.getAttribute('id');
    const link = document.querySelector(`.nav-link[href="#${id}"]`);

    if (link) {
      if (scrollY >= top && scrollY < top + height) {
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
      }
    }
  });
}

// ===== SCROLL REVEAL ANIMATIONS =====
const observerOptions = {
  threshold: 0.12,
  rootMargin: '0px 0px -40px 0px'
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      setTimeout(() => {
        entry.target.classList.add('visible');
      }, entry.target.dataset.delay || 0);
      observer.unobserve(entry.target);
    }
  });
}, observerOptions);

// Add fade-up to animatable elements
const animatables = [
  '.service-card', '.gallery-card', '.review-card',
  '.about-text', '.about-image-col', '.hours-card',
  '.stat', '.booking-info', '.booking-form',
  '.section-header', '.hero-logo-block', '.hero-actions'
];

animatables.forEach(selector => {
  document.querySelectorAll(selector).forEach((el, i) => {
    el.classList.add('fade-up');
    el.dataset.delay = (i % 4) * 80;
    observer.observe(el);
  });
});

// ===== BOOKING FORM =====
const bookingForm = document.getElementById('bookingForm');
const modalOverlay = document.getElementById('modalOverlay');

// Set minimum date to today
const dateInput = document.getElementById('date');
if (dateInput) {
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  dateInput.min = `${yyyy}-${mm}-${dd}`;
}

bookingForm.addEventListener('submit', async (e) => {
  e.preventDefault();

  // Basic validation
  const required = bookingForm.querySelectorAll('[required]');
  let valid = true;

  required.forEach(field => {
    field.style.borderColor = '';
    if (!field.value.trim()) {
      field.style.borderColor = '#D4847A';
      valid = false;
    }
  });

  if (!valid) {
    bookingForm.style.animation = 'shake 0.4s ease';
    setTimeout(() => { bookingForm.style.animation = ''; }, 400);
    return;
  }

  const submitBtn = bookingForm.querySelector('button[type="submit"]');
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Confirming Booking...';

  const formData = {
    fullName: document.getElementById('fullName').value.trim(),
    phone: document.getElementById('phone').value.trim(),
    email: document.getElementById('email').value.trim(),
    address: document.getElementById('address').value.trim(),
    date: document.getElementById('date').value.trim(),
    time: document.getElementById('time').value.trim(),
    session: document.getElementById('session').value.trim(),
    notes: document.getElementById('notes').value.trim()
  };

  const endpoint = (window.location.protocol === 'file:') ? 'http://localhost:8080/book.php' : '/api/book';

  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    });

    if (!response.ok) {
      throw new Error(`Server returned HTTP ${response.status}`);
    }

    const result = await response.json();

    // Prepare WhatsApp Confirmation Link
    const waPhone = '233599969286';
    const waText = encodeURIComponent(
      `Hello Erniekay Beauty Lab! ✨\nI just booked an appointment on your website:\n\n` +
      `👤 Name: ${formData.fullName}\n` +
      `📞 Phone: ${formData.phone}\n` +
      `💅 Service: ${result.service || formData.session}\n` +
      `📅 Date: ${formData.date} at ${formData.time}\n` +
      `📍 Location: ${formData.address}`
    );
    const waUrl = `https://wa.me/${waPhone}?text=${waText}`;

    const waBtn = document.getElementById('modalWhatsAppBtn');
    if (waBtn) waBtn.href = waUrl;

    const detailsBox = document.getElementById('modalBookingDetails');
    if (detailsBox) {
      detailsBox.style.display = 'block';
      detailsBox.innerHTML = `
        <strong>Client:</strong> ${formData.fullName}<br/>
        <strong>Service:</strong> ${result.service || formData.session}<br/>
        <strong>Date & Time:</strong> ${formData.date} at ${formData.time}
      `;
    }

    // Show success modal
    modalOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Reset form
    bookingForm.reset();
  } catch (err) {
    console.error('Booking submission error:', err);
    alert('Booking could not be sent to the server. Please check your connection or contact us directly on WhatsApp.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalBtnText;
  }
});

function closeModal() {
  modalOverlay.classList.remove('active');
  document.body.style.overflow = '';
}

// Close modal on overlay click
modalOverlay.addEventListener('click', (e) => {
  if (e.target === modalOverlay) closeModal();
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeModal();
});

// ===== SMOOTH SCROLL FOR ALL ANCHOR LINKS =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', (e) => {
    const target = document.querySelector(anchor.getAttribute('href'));
    if (target) {
      e.preventDefault();
      const offset = 72;
      const top = target.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({ top, behavior: 'smooth' });
    }
  });
});

// ===== GALLERY VIDEO PLAY/PAUSE =====
document.querySelectorAll('.gallery-video-card').forEach(card => {
  const video = card.querySelector('video');
  if (!video) return;

  card.addEventListener('click', () => {
    // Pause all other videos first
    document.querySelectorAll('.gallery-video-card video').forEach(v => {
      if (v !== video) {
        v.pause();
        v.muted = true;
        v.closest('.gallery-video-card').classList.remove('playing');
      }
    });

    if (video.paused) {
      video.muted = false;
      video.play();
      card.classList.add('playing');
    } else {
      video.pause();
      card.classList.remove('playing');
    }
  });

  video.addEventListener('ended', () => {
    card.classList.remove('playing');
  });
});

// ===== GALLERY CARD ENTRANCE =====
const galleryCards = document.querySelectorAll('.gallery-card');
galleryCards.forEach((card, i) => {
  card.style.transitionDelay = `${i * 60}ms`;
});

// ===== ADD SHAKE KEYFRAME DYNAMICALLY =====
const styleSheet = document.createElement('style');
styleSheet.textContent = `
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-8px); }
    40% { transform: translateX(8px); }
    60% { transform: translateX(-4px); }
    80% { transform: translateX(4px); }
  }
`;
document.head.appendChild(styleSheet);

// ===== HERO GALLERY PARALLAX (subtle) =====
window.addEventListener('scroll', () => {
  const scrolled = window.scrollY;
  const heroGallery = document.querySelector('.hero-gallery');
  if (heroGallery && scrolled < window.innerHeight) {
    heroGallery.style.transform = `translateY(${scrolled * 0.08}px)`;
  }
});

// ===== NUMBER COUNTER ANIMATION =====
function animateCounter(el, target, duration = 1500) {
  let start = 0;
  const step = target / (duration / 16);
  const timer = setInterval(() => {
    start += step;
    if (start >= target) {
      start = target;
      clearInterval(timer);
    }
    const suffix = el.dataset.suffix || '';
    el.textContent = Math.floor(start) + suffix;
  }, 16);
}

const counterObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const nums = entry.target.querySelectorAll('.stat-num');
      nums.forEach(num => {
        const raw = num.textContent;
        const match = raw.match(/(\d+)(\+?)/);
        if (match) {
          num.dataset.suffix = match[2] || '';
          animateCounter(num, parseInt(match[1]), 1400);
        }
      });
      counterObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.5 });

const statsBlock = document.querySelector('.about-stats');
if (statsBlock) counterObserver.observe(statsBlock);

// ===== DYNAMIC COPYRIGHT YEAR =====
const yearEl = document.getElementById('currentYear');
if (yearEl) yearEl.textContent = new Date().getFullYear();

// ===== ADMIN QUICK ACCESS SHORTCUTS =====
// 1. Press Ctrl + Shift + A anywhere on the site
document.addEventListener('keydown', (e) => {
  if ((e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'a') || (e.altKey && e.key.toLowerCase() === 'a')) {
    e.preventDefault();
    window.location.href = 'admin.html';
  }
});

// 2. Triple-click on the navbar logo
const navLogo = document.querySelector('.nav-logo');
if (navLogo) {
  let clickCount = 0;
  let clickTimer = null;
  navLogo.addEventListener('click', () => {
    clickCount++;
    clearTimeout(clickTimer);
    if (clickCount >= 3) {
      window.location.href = 'admin.html';
      clickCount = 0;
    } else {
      clickTimer = setTimeout(() => { clickCount = 0; }, 800);
    }
  });
}

// ===== CONSOLE WELCOME =====
console.log('%c✨ Erniekay Beauty Lab', 'font-size:1.4rem;font-weight:bold;color:#C9A96E;');
console.log('%cKumasi, Ghana – Where Beauty Meets Confidence', 'color:#A0784A;');
