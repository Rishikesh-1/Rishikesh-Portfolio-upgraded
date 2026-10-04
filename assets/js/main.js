// main.js — no framework, no build step, kept intentionally tiny for speed.
document.addEventListener('DOMContentLoaded', function () {
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  var navigationEntry = window.performance && performance.getEntriesByType ? performance.getEntriesByType('navigation')[0] : null;
  if (navigationEntry && navigationEntry.type === 'reload') {
    window.setTimeout(function () {
      if (window.location.hash) window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
      window.scrollTo(0, 0);
    }, 0);
  }

  var savedTheme = window.localStorage.getItem('portfolio-theme') || 'dark';
  var themeToggle = document.getElementById('themeToggle');
  var applyTheme = function (theme) {
    document.body.dataset.theme = theme;
    window.localStorage.setItem('portfolio-theme', theme);
    if (themeToggle) {
      var isLight = theme === 'light';
      themeToggle.setAttribute('aria-pressed', isLight ? 'true' : 'false');
      themeToggle.setAttribute('aria-label', isLight ? 'Switch to dark mode' : 'Switch to light mode');
      themeToggle.querySelector('.theme-toggle-label').textContent = isLight ? 'Dark' : 'Light';
      themeToggle.querySelector('span:first-child').textContent = isLight ? '☾' : '☼';
    }
  };
  applyTheme(savedTheme);
  if (themeToggle) themeToggle.addEventListener('click', function () { applyTheme(document.body.dataset.theme === 'light' ? 'dark' : 'light'); });

  var finishPreloader = function () {
    var preloader = document.getElementById('preloader');
    if (!preloader) return;
    preloader.classList.add('is-done');
    preloader.style.opacity = '0';
    preloader.style.visibility = 'hidden';
    preloader.style.pointerEvents = 'none';
    preloader.style.display = 'none';
  };
  window.setTimeout(finishPreloader, reduceMotion ? 0 : 650);
  // Keep a delayed fallback so a blocked or slow animation CDN can never trap the page behind the loader.
  window.setTimeout(function () {
    var preloader = document.getElementById('preloader');
    if (preloader) {
      preloader.classList.add('is-done');
      preloader.style.opacity = '0';
      preloader.style.visibility = 'hidden';
      preloader.style.pointerEvents = 'none';
      preloader.style.display = 'none';
    }
  }, reduceMotion ? 50 : 1100);

  if (window.gsap && !reduceMotion) {
    window.gsap.from('.hero-home .hero-photo', { duration: .9, y: 24, opacity: 0, delay: .35, ease: 'power3.out' });
  }

  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      var isOpen = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    links.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { links.classList.remove('open'); });
    });
  }

  // Reveal hero name letters/words with a staggered delay if data-reveal is present.
  document.querySelectorAll('[data-reveal]').forEach(function (el, i) {
    el.style.animationDelay = (i * 0.06) + 's';
  });

  if (window.AOS) {
    document.querySelectorAll('.section, .project-card, .service-item, .timeline-item, .social-card, .testimonial-card, .blog-card').forEach(function (element, index) {
      if (!element.hasAttribute('data-aos')) element.setAttribute('data-aos', index % 2 ? 'fade-up' : 'fade-right');
    });
    window.AOS.init({ duration: reduceMotion ? 0 : 760, easing: 'ease-out-cubic', once: true, offset: 70, disable: reduceMotion });
  }

  var typingTarget = document.querySelector('[data-typing]');
  if (typingTarget && !reduceMotion) {
    var typingText = typingTarget.dataset.typing;
    var typingIndex = 0;
    typingTarget.textContent = '';
    var typeNext = function () {
      if (typingIndex >= typingText.length) return;
      typingTarget.textContent += typingText.charAt(typingIndex++);
      window.setTimeout(typeNext, typingText.charAt(typingIndex - 1) === ',' ? 120 : 38);
    };
    window.setTimeout(typeNext, 850);
  } else if (typingTarget) {
    typingTarget.textContent = typingTarget.dataset.typing;
  }

  var skillTarget = document.querySelector('[data-skill-rotate]');
  if (skillTarget && !reduceMotion) {
    var skills = ['creative direction', 'digital marketing', 'web experiences', 'team leadership'];
    var skillIndex = 0;
    window.setInterval(function () {
      skillTarget.style.opacity = '0';
      skillTarget.style.transform = 'translateY(5px)';
      window.setTimeout(function () {
        skillIndex = (skillIndex + 1) % skills.length;
        skillTarget.textContent = skills[skillIndex];
        skillTarget.style.opacity = '1';
        skillTarget.style.transform = 'translateY(0)';
      }, 220);
    }, 2800);
  }

  document.querySelectorAll('[data-social-slider]').forEach(function (slider) {
    var track = slider.querySelector('[data-social-track]');
    var cards = track ? Array.from(track.children) : [];
    var count = slider.querySelector('[data-social-count]');
    var current = 0;
    var getVisible = function () { return window.innerWidth <= 480 ? 1 : (window.innerWidth <= 780 ? 2 : 3); };
    var update = function () {
      if (!track || !cards.length) return;
      var visible = getVisible();
      var max = Math.max(0, cards.length - visible);
      current = Math.min(current, max);
      var gap = 16;
      var cardWidth = (track.clientWidth - gap * (visible - 1)) / visible;
      track.style.transform = 'translateX(-' + (current * (cardWidth + gap)) + 'px)';
      if (count) count.textContent = (current + 1) + ' / ' + cards.length;
    };
    slider.querySelector('[data-social-prev]')?.addEventListener('click', function () { current = Math.max(0, current - 1); update(); });
    slider.querySelector('[data-social-next]')?.addEventListener('click', function () { current += 1; update(); });
    var touchStartX = 0;
    var touchStartY = 0;
    slider.addEventListener('touchstart', function (event) {
      var touch = event.changedTouches[0];
      touchStartX = touch.clientX;
      touchStartY = touch.clientY;
    }, { passive: true });
    slider.addEventListener('touchend', function (event) {
      var touch = event.changedTouches[0];
      var deltaX = touch.clientX - touchStartX;
      var deltaY = touch.clientY - touchStartY;
      if (Math.abs(deltaX) < 45 || Math.abs(deltaX) < Math.abs(deltaY)) return;
      if (deltaX < 0) current += 1;
      else current = Math.max(0, current - 1);
      update();
    }, { passive: true });
    window.addEventListener('resize', update, { passive: true });
    update();
  });

  var field = document.getElementById('particleField');
  if (field && !reduceMotion) {
    var context = field.getContext('2d');
    var particles = [];
    var resizeField = function () {
      var ratio = Math.min(window.devicePixelRatio || 1, 2);
      field.width = window.innerWidth * ratio;
      field.height = window.innerHeight * ratio;
      context.setTransform(ratio, 0, 0, ratio, 0, 0);
      particles = Array.from({ length: Math.min(42, Math.max(18, Math.floor(window.innerWidth / 25))) }, function () {
        return { x: Math.random() * window.innerWidth, y: Math.random() * window.innerHeight, size: Math.random() * 1.8 + .5, speed: Math.random() * .18 + .04, drift: Math.random() * .4 - .2 };
      });
    };
    var drawParticles = function () {
      context.clearRect(0, 0, window.innerWidth, window.innerHeight);
      particles.forEach(function (particle) {
        particle.y -= particle.speed;
        particle.x += particle.drift * .08;
        if (particle.y < -8) particle.y = window.innerHeight + 8;
        context.beginPath();
        context.fillStyle = 'rgba(92, 225, 255, .42)';
        context.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
        context.fill();
      });
      window.requestAnimationFrame(drawParticles);
    };
    resizeField();
    window.addEventListener('resize', resizeField, { passive: true });
    drawParticles();
  }

  document.querySelectorAll('[data-project-open]').forEach(function (button) {
    button.addEventListener('click', function () {
      var card = button.closest('[data-project-card]');
      var modal = document.getElementById('projectModal');
      if (!card || !modal) return;
      modal.querySelector('[data-modal-title]').textContent = card.dataset.projectTitle || '';
      modal.querySelector('[data-modal-category]').textContent = card.dataset.projectCategory || 'Project';
      modal.querySelector('[data-modal-description]').textContent = card.dataset.projectDescription || '';
      var modalImage = modal.querySelector('[data-modal-image]');
      if (card.dataset.projectImage) { modalImage.src = card.dataset.projectImage; modalImage.hidden = false; } else { modalImage.hidden = true; }
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      modal.querySelector('.modal-close').focus();
    });
  });
  document.querySelectorAll('[data-modal-close]').forEach(function (closeButton) {
    closeButton.addEventListener('click', function () {
      var modal = document.getElementById('projectModal');
      if (modal) { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); }
    });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      var modal = document.getElementById('projectModal');
      if (modal) { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); }
    }
  });

  document.addEventListener('click', function (event) {
    if (reduceMotion || !event.target.closest('.btn, .project-open')) return;
    var ripple = document.createElement('span');
    ripple.className = 'click-ripple';
    ripple.style.left = event.clientX + 'px';
    ripple.style.top = event.clientY + 'px';
    document.body.appendChild(ripple);
    window.setTimeout(function () { ripple.remove(); }, 650);
  });

  if (reduceMotion) return;

  // Reveal content as it enters the viewport instead of animating the whole page at once.
  var revealItems = document.querySelectorAll('.section, .page-intro, .project-card, .service-item, .timeline-item, .skill-pill, .social-card, .testimonial-card');
  if ('IntersectionObserver' in window) {
    var revealObserver = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -36px' });
    revealItems.forEach(function (el, i) {
      el.dataset.motion = 'reveal';
      el.style.transitionDelay = Math.min(i * 0.035, 0.28) + 's';
      revealObserver.observe(el);
    });
  } else {
    revealItems.forEach(function (el) { el.classList.add('is-visible'); });
  }

  if (!canHover) return;

  var parallaxItems = document.querySelectorAll('[data-parallax-section]');
  var parallaxFrame = null;
  window.addEventListener('scroll', function () {
    if (parallaxFrame) return;
    parallaxFrame = window.requestAnimationFrame(function () {
      parallaxItems.forEach(function (item) {
        var shift = Math.min(window.scrollY * .08, 42);
        item.style.setProperty('--parallax-shift', shift + 'px');
      });
      parallaxFrame = null;
    });
  }, { passive: true });

  // Let the background light and cards acknowledge the pointer without competing with content.
  var pointerFrame = null;
  document.addEventListener('pointermove', function (event) {
    if (pointerFrame) return;
    pointerFrame = window.requestAnimationFrame(function () {
      document.documentElement.style.setProperty('--pointer-x', event.clientX + 'px');
      document.documentElement.style.setProperty('--pointer-y', event.clientY + 'px');
      pointerFrame = null;
    });
  }, { passive: true });

  document.querySelectorAll('.project-card, .service-item, .social-card, .testimonial-card').forEach(function (card) {
    card.dataset.motion = 'tilt';
    card.addEventListener('pointermove', function (event) {
      var bounds = card.getBoundingClientRect();
      var x = (event.clientX - bounds.left) / bounds.width - 0.5;
      var y = (event.clientY - bounds.top) / bounds.height - 0.5;
      card.style.transform = 'perspective(900px) rotateX(' + (-y * 2.2).toFixed(2) + 'deg) rotateY(' + (x * 2.2).toFixed(2) + 'deg)';
    });
    card.addEventListener('pointerleave', function () {
      card.style.transform = '';
    });
  });
});
