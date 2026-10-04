<?php
/**
 * products-lib.php — Ready Projects & Digital Products for Sale Management System.
 * Supports discounted offer prices, live previews, feature checklists, customization notes, and instant ordering.
 */

if (!function_exists('ensure_products_schema')) {
function ensure_products_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (get_setting($pdo, 'products_schema_version') === '1') {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            slug VARCHAR(220) NOT NULL UNIQUE,
            badge_text VARCHAR(100) DEFAULT "Special Offer",
            sale_price VARCHAR(100) NOT NULL,
            regular_price VARCHAR(100) DEFAULT NULL,
            tagline VARCHAR(255) DEFAULT NULL,
            category_name VARCHAR(100) DEFAULT "Ready Project",
            cover_image VARCHAR(255) DEFAULT NULL,
            demo_url VARCHAR(500) DEFAULT NULL,
            short_description TEXT DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            features_list TEXT DEFAULT NULL,
            customization_note VARCHAR(255) DEFAULT "Exact turnkey project as live demo. Extra feature customizations available on request.",
            is_featured TINYINT(1) NOT NULL DEFAULT 1,
            is_visible TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    // Check if initial sample products need to be seeded
    $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($count <= 1) {
        $pdo->exec('DELETE FROM products');
        $stmt = $pdo->prepare(
            'INSERT INTO products (title, slug, badge_text, sale_price, regular_price, tagline, category_name, demo_url, short_description, features_list, customization_note, is_featured, is_visible, sort_order)
             VALUES (:title, :slug, :badge_text, :sale_price, :regular_price, :tagline, :category_name, :demo_url, :short_description, :features_list, :customization_note, :is_featured, 1, :sort_order)'
        );
        // Item 1
        $stmt->execute([
            ':title'              => 'Complete Portfolio CMS & Admin Panel',
            ':slug'               => 'turnkey-portfolio-cms',
            ':badge_text'         => '66% OFF • FLASH SALE',
            ':sale_price'         => 'Rs. 10,000',
            ':regular_price'      => 'Rs. 30,000',
            ':tagline'            => 'Turnkey portfolio with admin CMS & blog',
            ':category_name'      => 'Portfolio & CMS',
            ':demo_url'           => SITE_ROOT_URL,
            ':short_description'  => 'Production-ready portfolio with content management system, blog, media library, and analytics.',
            ':features_list'      => "Complete PHP & MySQL source code\nAdmin CMS Dashboard with instant editing\nDark & light mode toggle included",
            ':customization_note' => 'Exact project as demo. Extra changes charged as per requirement.',
            ':is_featured'        => 1,
            ':sort_order'         => 1,
        ]);
        // Item 2
        $stmt->execute([
            ':title'              => 'Business & Agency Website Platform',
            ':slug'               => 'business-agency-platform',
            ':badge_text'         => '60% OFF',
            ':sale_price'         => 'Rs. 12,000',
            ':regular_price'      => 'Rs. 30,000',
            ':tagline'            => 'Modern lead-generation business portal',
            ':category_name'      => 'Business Portal',
            ':demo_url'           => SITE_ROOT_URL . '/#services',
            ':short_description'  => 'High-converting business layout with service tier builders, quote calculators, and inquiry forms.',
            ':features_list'      => "Full front & back-office codebase\nWhatsApp & email lead capture\n100% Responsive & SEO optimized",
            ':customization_note' => 'Exact project as demo. Extra changes charged as per requirement.',
            ':is_featured'        => 1,
            ':sort_order'         => 2,
        ]);
        // Item 3
        $stmt->execute([
            ':title'              => 'E-Commerce & Digital Store Starter',
            ':slug'               => 'digital-store-starter',
            ':badge_text'         => 'HOT DEAL',
            ':sale_price'         => 'Rs. 15,000',
            ':regular_price'      => 'Rs. 35,000',
            ':tagline'            => 'Direct product showcase with checkout',
            ':category_name'      => 'Digital Store',
            ':demo_url'           => SITE_ROOT_URL . '/page.php?view=services',
            ':short_description'  => 'Ready online product showcase with direct WhatsApp/card ordering, category filters, and offer badges.',
            ':features_list'      => "Product catalog & inventory manager\nDirect WhatsApp checkout integration\nFast page speed & mobile first",
            ':customization_note' => 'Exact project as demo. Extra changes charged as per requirement.',
            ':is_featured'        => 1,
            ':sort_order'         => 3,
        ]);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = :v2'
    );
    $stmt->execute([':k' => 'products_schema_version', ':v' => '1', ':v2' => '1']);
}
}

if (!function_exists('render_product_card')) {
/** Render a high-converting Ready Project / Product card */
function render_product_card(array $p, int $index = 0, ?string $whatsappNum = null): void
{
    $title = $p['title'] ?? 'Ready Project';
    $badge = trim((string) ($p['badge_text'] ?? 'Special Offer'));
    $salePrice = trim((string) ($p['sale_price'] ?? ''));
    $regPrice = trim((string) ($p['regular_price'] ?? ''));
    $category = trim((string) ($p['category_name'] ?? 'Ready Project'));
    $tagline = trim((string) ($p['tagline'] ?? ''));
    $shortDesc = trim((string) ($p['short_description'] ?? ''));
    $demoUrl = trim((string) ($p['demo_url'] ?? ''));
    $customNote = trim((string) ($p['customization_note'] ?? ''));
    $cover = trim((string) ($p['cover_image'] ?? ''));

    // Split features
    $features = service_lines($p['features_list'] ?? '');

    // WhatsApp Direct Link
    $phone = preg_replace('/[^0-9+]/', '', (string)$whatsappNum);
    if (empty($phone)) {
        $phone = '9779840333333'; // fallback
    }
    $msgText = "Hello! I am interested in buying the ready project: \"" . $title . "\" on offer price (" . ($salePrice ?: 'Fixed Price') . "). Please provide purchase details.";
    $waUrl = 'https://wa.me/' . ltrim($phone, '+') . '?text=' . rawurlencode($msgText);

    ?>
    <article class="prod-card<?= !empty($p['is_featured']) ? ' is-featured' : '' ?>" data-aos="fade-up" data-aos-delay="<?= (int) min($index * 80, 320) ?>">
      <div class="prod-card-head">
        <?php if (!empty($cover) && is_file(UPLOAD_DIR . $cover)): ?>
          <div class="prod-card-cover">
            <img src="<?= e(UPLOAD_URL . $cover) ?>" alt="<?= e($title) ?>" loading="lazy">
          </div>
        <?php else: ?>
          <div class="prod-card-art" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="prod-card-art-icon"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
          </div>
        <?php endif; ?>

        <?php if ($badge !== ''): ?>
          <span class="prod-badge-sale"><?= e($badge) ?></span>
        <?php endif; ?>

        <?php if ($category !== ''): ?>
          <span class="prod-badge-cat"><?= e($category) ?></span>
        <?php endif; ?>
      </div>

      <div class="prod-card-body">
        <h3 class="prod-title"><?= e($title) ?></h3>
        <?php if ($tagline !== ''): ?>
          <p class="prod-tagline"><?= e($tagline) ?></p>
        <?php endif; ?>

        <!-- Pricing Comparison -->
        <div class="prod-pricing">
          <div class="prod-price-wrap">
            <?php if ($regPrice !== ''): ?>
              <span class="prod-price-original"><?= e($regPrice) ?></span>
            <?php endif; ?>
            <span class="prod-price-sale"><?= e($salePrice) ?></span>
          </div>
          <?php if ($regPrice !== '' && $salePrice !== ''): ?>
            <span class="prod-deal-tag">Offer price</span>
          <?php endif; ?>
        </div>

        <?php if ($shortDesc !== ''): ?>
          <p class="prod-desc"><?= e($shortDesc) ?></p>
        <?php endif; ?>

        <!-- Key Features Checklist -->
        <?php if (!empty($features)): ?>
          <ul class="prod-features">
            <?php foreach (array_slice($features, 0, 3) as $f): ?>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="prod-check-icon" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                <span><?= e($f) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <!-- Customization Clarification Note -->
        <?php if ($customNote !== ''): ?>
          <div class="prod-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="prod-note-icon" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span><?= e($customNote) ?></span>
          </div>
        <?php endif; ?>

        <!-- Action CTA Buttons -->
        <div class="prod-actions">
          <?php if (!empty($demoUrl) && $demoUrl !== '#'): ?>
            <a href="<?= e($demoUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary prod-btn-demo">
              <span>Live demo</span>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>
          <?php endif; ?>
          <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary prod-btn-buy">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            <span>Buy now &bull; Claim offer</span>
          </a>
        </div>
      </div>
    </article>
    <?php
}
}
