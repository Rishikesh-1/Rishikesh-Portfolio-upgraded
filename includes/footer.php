</main>
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <p class="footer-name">Rishikesh Rana</p>
      <p class="footer-tag">Manager · Creative Direction · Digital Marketing</p>
    </div>
    <div class="footer-social">
      <?php
      $stmt = $pdo->query('SELECT platform, url, icon_class FROM social_links WHERE is_visible = 1 ORDER BY sort_order ASC');
      foreach ($stmt->fetchAll() as $s): ?>
        <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener noreferrer"><?= social_icon($s['platform']) ?><span><?= e($s['platform']) ?></span></a>
      <?php endforeach; ?>
    </div>
    <p class="footer-copy">&copy; <?= date('Y') ?> Rishikesh Rana. All rights reserved.</p>
  </div>
</footer>
<div class="project-modal" id="projectModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="projectModalTitle">
  <div class="project-modal-backdrop" data-modal-close></div>
  <div class="project-modal-panel">
    <button class="modal-close" type="button" data-modal-close aria-label="Close project details">&times;</button>
    <img class="modal-image" data-modal-image alt="">
    <p class="eyebrow" data-modal-category></p>
    <h2 id="projectModalTitle" data-modal-title></h2>
    <p class="modal-description" data-modal-description></p>
  </div>
</div>
<button class="back-to-top" id="backToTop" type="button" aria-label="Back to top" title="Back to top">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M18 15l-6-6-6 6"/>
  </svg>
</button>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js" defer></script>
<script src="<?= e(SITE_ROOT_URL) ?>/assets/js/main.js?v=20261004-3" defer></script>
</body>
</html>
