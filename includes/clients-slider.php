<?php
/**
 * clients-slider.php — Continuous infinite logo marquee
 */
$showSlider = get_setting($pdo, 'show_clients_slider', '1');
if ($showSlider === '0' || $showSlider === 'off') {
    return;
}

$clientsHeading = get_setting($pdo, 'clients_heading', 'Trusted by companies, brands & partners');
$clientsSubheading = get_setting($pdo, 'clients_subheading', '');

$stmt = $pdo->query('SELECT * FROM clients WHERE is_visible = 1 ORDER BY sort_order ASC, id DESC');
$clients = $stmt->fetchAll();

if (empty($clients)) {
    return;
}

// Ensure the group has enough items to create a smooth, continuous track on wide screens
$marqueeItems = $clients;
while (count($marqueeItems) < 8) {
    $marqueeItems = array_merge($marqueeItems, $clients);
}
?>
<section class="clients-slider-section">
  <div class="container">
    <?php if (!empty($clientsHeading)): ?>
      <div class="clients-slider-header" data-motion="reveal">
        <h3 class="clients-slider-title"><?= e($clientsHeading) ?></h3>
        <?php if (!empty($clientsSubheading)): ?>
          <p class="clients-slider-subtitle"><?= e($clientsSubheading) ?></p>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="clients-marquee-wrapper" aria-label="Client and partner logos">
    <div class="clients-marquee-track">
      <!-- First sequence -->
      <div class="clients-marquee-group">
        <?php foreach ($marqueeItems as $c): ?>
          <div class="clients-marquee-item">
            <?php if (!empty($c['website_url'])): ?>
              <a href="<?= e($c['website_url']) ?>" target="_blank" rel="noopener noreferrer" title="<?= e($c['name']) ?>">
                <img src="<?= e(UPLOAD_URL . $c['logo_image']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
              </a>
            <?php else: ?>
              <img src="<?= e(UPLOAD_URL . $c['logo_image']) ?>" alt="<?= e($c['name']) ?>" title="<?= e($c['name']) ?>" loading="lazy">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Second duplicate sequence for seamless infinite loop -->
      <div class="clients-marquee-group" aria-hidden="true">
        <?php foreach ($marqueeItems as $c): ?>
          <div class="clients-marquee-item">
            <?php if (!empty($c['website_url'])): ?>
              <a href="<?= e($c['website_url']) ?>" target="_blank" rel="noopener noreferrer" tabindex="-1">
                <img src="<?= e(UPLOAD_URL . $c['logo_image']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
              </a>
            <?php else: ?>
              <img src="<?= e(UPLOAD_URL . $c['logo_image']) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
