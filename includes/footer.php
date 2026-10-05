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
<div class="cv-modal" id="cvModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cvModalTitle">
  <div class="cv-modal-backdrop" data-cv-modal-close></div>
  <div class="cv-modal-panel">
    <div class="cv-modal-header">
      <div class="cv-modal-title-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="cv-modal-icon" aria-hidden="true">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
          <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
        <div>
          <h2 id="cvModalTitle" class="cv-modal-title">Curriculum Vitae / Résumé</h2>
          <p class="cv-modal-subtitle">Rishikesh Rana</p>
        </div>
      </div>
      <div class="cv-modal-actions">
        <a href="#" id="cvModalDownloadBtn" class="btn btn-primary btn-sm cv-btn-download" download>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:4px;" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          <span>Download CV</span>
        </a>
        <a href="#" id="cvModalNewTabBtn" class="btn btn-ghost btn-sm cv-btn-newtab" target="_blank" rel="noopener noreferrer" title="Open in new window">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;margin-right:4px;" aria-hidden="true">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
            <polyline points="15 3 21 3 21 9"></polyline>
            <line x1="10" y1="14" x2="21" y2="3"></line>
          </svg>
          <span>Open in New Tab</span>
        </a>
        <button class="cv-modal-close" type="button" data-cv-modal-close aria-label="Close CV viewer">&times;</button>
      </div>
    </div>
    <div class="cv-modal-body">
      <iframe id="cvModalFrame" src="" title="CV Document Preview"></iframe>
    </div>
  </div>
</div>
<button class="back-to-top" id="backToTop" type="button" aria-label="Back to top" title="Back to top">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M18 15l-6-6-6 6"/>
  </svg>
</button>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js" defer></script>
<script src="<?= e(SITE_ROOT_URL) ?>/assets/js/main.js?v=<?= file_exists(__DIR__ . '/../assets/js/main.js') ? filemtime(__DIR__ . '/../assets/js/main.js') : time() ?>" defer></script>
</body>
</html>
