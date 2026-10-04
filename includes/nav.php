<header class="site-nav">
  <div class="nav-inner">
    <a href="<?= e(SITE_ROOT_URL) ?>/" class="nav-brand">Rishikesh Rana</a>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <nav class="nav-links" id="navLinks">
      <a href="<?= e(SITE_ROOT_URL) ?>/">Home</a>
      <a href="<?= e(SITE_ROOT_URL) ?>/page.php?view=work">Work</a>
      <a href="<?= e(SITE_ROOT_URL) ?>/page.php?view=services">Services</a>
      <a href="<?= e(SITE_ROOT_URL) ?>/page.php?view=experience">Experience</a>
      <a href="<?= e(SITE_ROOT_URL) ?>/blog.php">Blog</a>
      <a href="<?= e(SITE_ROOT_URL) ?>/page.php?view=contact" class="nav-cta">Get in touch</a>
      <button class="theme-toggle" id="themeToggle" type="button" aria-label="Switch to light mode" aria-pressed="false"><span aria-hidden="true">☼</span><span class="theme-toggle-label">Light</span></button>
    </nav>
  </div>
</header>
