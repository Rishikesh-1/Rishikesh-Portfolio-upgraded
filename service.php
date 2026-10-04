<?php
require_once __DIR__ . '/config/config.php';
ensure_services_schema($pdo);

$slug = trim((string) ($_GET['slug'] ?? ''));
$stmt = $pdo->prepare('SELECT * FROM services WHERE slug = :slug AND is_visible = 1 LIMIT 1');
$stmt->execute([':slug' => $slug]);
$service = $stmt->fetch();

if (!$service) {
    http_response_code(404);
    $page_title = 'Service not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><h1>Service not found</h1><p style="margin-top:12px;"><a class="detail-backlink" href="' . e(SITE_ROOT_URL . '/page.php?view=services') . '"><span aria-hidden="true">&larr;</span> All services</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$serviceId = (int) $service['id'];
$serviceUrl = service_url($service);

// ---- Structured content (with graceful defaults) ----
$deliverables = service_lines($service['deliverables'] ?? '');
$process = service_json($service['process_json'] ?? '');
$processIsDefault = !$process;
if (!$process) $process = service_default_process();
$packages = service_json($service['packages_json'] ?? '');
$faqs = service_json($service['faqs_json'] ?? '');
if (!$faqs) $faqs = service_default_faqs();
$tools = service_tools($service['tools'] ?? '');

// ---- Related projects ----
$related = $pdo->prepare(
    'SELECT p.*, c.name AS category_name FROM service_projects sp
     JOIN projects p ON p.id = sp.project_id AND p.is_visible = 1
     LEFT JOIN project_categories c ON c.id = p.category_id
     WHERE sp.service_id = :id
     ORDER BY p.is_featured DESC, p.sort_order ASC, p.created_at DESC'
);
$related->execute([':id' => $serviceId]);
$relatedProjects = $related->fetchAll();
$relatedIsFallback = false;
if (!$relatedProjects) {
    $relatedIsFallback = true;
    $relatedProjects = $pdo->query(
        'SELECT p.*, c.name AS category_name FROM projects p
         LEFT JOIN project_categories c ON c.id = p.category_id
         WHERE p.is_visible = 1 ORDER BY p.is_featured DESC, p.sort_order ASC, p.created_at DESC LIMIT 3'
    )->fetchAll();
}

// ---- Social proof ----
$testimonials = $pdo->query('SELECT * FROM testimonials WHERE is_visible = 1 ORDER BY sort_order ASC, id DESC LIMIT 3')->fetchAll();
$ratingRow = $pdo->query('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM testimonials WHERE is_visible = 1')->fetch();
$avgRating = $ratingRow && (int) $ratingRow['total'] > 0 ? round((float) $ratingRow['avg_rating'], 1) : 0;
$totalProjects = (int) $pdo->query('SELECT COUNT(*) FROM projects WHERE is_visible = 1')->fetchColumn();
$clientCount = 0;
try { $clientCount = (int) $pdo->query('SELECT COUNT(*) FROM clients WHERE is_visible = 1')->fetchColumn(); } catch (PDOException $e) {}

// ---- Other services ----
$othersStmt = $pdo->prepare('SELECT * FROM services WHERE is_visible = 1 AND id <> :id ORDER BY is_featured DESC, sort_order ASC LIMIT 3');
$othersStmt->execute([':id' => $serviceId]);
$otherServices = $othersStmt->fetchAll();
$projectCounts = service_project_counts($pdo);

// ---- Booking form state (flash) ----
$bookingSuccess = !empty($_SESSION['service_booking_success']) && (int) $_SESSION['service_booking_success'] === $serviceId;
$bookingErrors = $_SESSION['service_booking_errors'] ?? [];
$old = $_SESSION['service_booking_old'] ?? [];
unset($_SESSION['service_booking_success'], $_SESSION['service_booking_errors'], $_SESSION['service_booking_old']);
$selectedPackage = (string) ($old['package'] ?? ($_GET['package'] ?? ''));

$contactEmail = get_setting($pdo, 'contact_email');
$contactPhone = get_setting($pdo, 'contact_phone');
$whatsappDigits = preg_replace('/\D+/', '', $contactPhone);

$page_title = ($service['meta_title'] ?: $service['title']) . ' — ' . get_setting($pdo, 'site_title');
$page_description = $service['meta_description'] ?: ($service['tagline'] ?: (string) $service['description']);
$page_image = $service['cover_image'] ?: null;
$page_url = $serviceUrl;
$body_class = 'service-page';

require __DIR__ . '/includes/header.php';
?>
<article class="svc-detail">
  <nav class="svc-breadcrumb" aria-label="Breadcrumb">
    <a href="<?= e(SITE_ROOT_URL) ?>/">Home</a><span aria-hidden="true">/</span>
    <a href="<?= e(SITE_ROOT_URL) ?>/page.php?view=services">Services</a><span aria-hidden="true">/</span>
    <span aria-current="page"><?= e($service['title']) ?></span>
  </nav>

  <!-- HERO -->
  <header class="svc-hero<?= empty($service['cover_image']) ? ' no-cover' : '' ?>">
    <div class="svc-hero-inner">
      <div class="svc-hero-copy">
        <span class="svc-hero-icon"><?= service_icon_svg($service['icon_class'] ?? '') ?></span>
        <p class="eyebrow">Service<?= !empty($service['is_featured']) ? ' · Most requested' : '' ?></p>
        <h1 class="display svc-hero-title"><?= e($service['title']) ?></h1>
        <?php if (!empty($service['tagline'])): ?><p class="svc-hero-tagline"><?= e($service['tagline']) ?></p><?php endif; ?>
        <?php if (!empty($service['description'])): ?><p class="svc-hero-lede"><?= e($service['description']) ?></p><?php endif; ?>

        <ul class="svc-hero-facts">
          <?php if (!empty($service['price_label'])): ?><li><span>Starting at</span><strong><?= e($service['price_label']) ?></strong></li><?php endif; ?>
          <?php if (!empty($service['turnaround'])): ?><li><span>Typical timeline</span><strong><?= e($service['turnaround']) ?></strong></li><?php endif; ?>
          <?php if (!$relatedIsFallback && $relatedProjects): ?><li><span>Delivered</span><strong><?= count($relatedProjects) ?> project<?= count($relatedProjects) === 1 ? '' : 's' ?></strong></li><?php endif; ?>
          <?php if ($avgRating > 0): ?><li><span>Client rating</span><strong><span class="svc-star" aria-hidden="true">&#9733;</span> <?= e(number_format($avgRating, 1)) ?>/5</strong></li><?php endif; ?>
        </ul>

        <div class="svc-hero-actions">
          <a href="#book" class="btn btn-primary svc-btn-lg">Book this service <span aria-hidden="true">&rarr;</span></a>
          <a href="#work" class="btn btn-ghost svc-btn-lg">See related work</a>
        </div>
        <p class="svc-hero-assure"><span class="svc-assure-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Free discovery call</span> &bull; <span class="svc-assure-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Reply within 24 hours</span> &bull; <span class="svc-assure-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> No obligation</span></p>
      </div>
      <?php if (!empty($service['cover_image'])): ?>
        <figure class="svc-hero-media">
          <img src="<?= e(UPLOAD_URL . $service['cover_image']) ?>" alt="<?= e($service['title']) ?>" loading="eager">
        </figure>
      <?php else: ?>
        <div class="svc-hero-art" aria-hidden="true"><?= service_icon_svg($service['icon_class'] ?? '', 'svc-hero-art-icon') ?></div>
      <?php endif; ?>
    </div>
  </header>

  <!-- SECTION NAV -->
  <div class="svc-subnav-wrapper">
    <nav class="svc-subnav" aria-label="On this page">
      <a href="#overview">Overview</a>
      <?php if ($deliverables): ?><a href="#included">What's included</a><?php endif; ?>
      <a href="#process">Process</a>
      <?php if ($packages): ?><a href="#packages">Packages</a><?php endif; ?>
      <?php if ($relatedProjects): ?><a href="#work">Work</a><?php endif; ?>
      <a href="#faq">FAQ</a>
      <a href="#book" class="svc-subnav-cta">Book now</a>
    </nav>
  </div>

  <div class="svc-layout">
    <div class="svc-main">
      <!-- OVERVIEW -->
      <section class="svc-block" id="overview">
        <p class="detail-section-label">Overview</p>
        <h2 class="svc-block-title">What this service delivers</h2>
        <?php if (!empty(trim(strip_tags((string) $service['overview'], '<img>')))): ?>
          <div class="project-detail-copy svc-copy"><?= sanitize_rich_text((string) $service['overview']) ?></div>
        <?php else: ?>
          <p class="svc-copy"><?= e($service['description'] ?: 'Details for this service are coming soon. Book a free discovery call to talk through your project.') ?></p>
        <?php endif; ?>
      </section>

      <!-- WHAT'S INCLUDED -->
      <?php if ($deliverables): ?>
      <section class="svc-block" id="included">
        <p class="detail-section-label">What's included</p>
        <h2 class="svc-block-title">Everything you get</h2>
        <ul class="svc-included">
          <?php foreach ($deliverables as $item): ?>
            <li><span class="svc-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span><?= e($item) ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>

      <!-- PROCESS -->
      <section class="svc-block" id="process">
        <p class="detail-section-label">How it works</p>
        <h2 class="svc-block-title"><?= $processIsDefault ? 'A simple, transparent process' : 'My process, step by step' ?></h2>
        <ol class="svc-process">
          <?php foreach ($process as $i => $step): ?>
            <li class="svc-step">
              <span class="svc-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
              <div>
                <h3><?= e($step['title'] ?? '') ?></h3>
                <?php if (!empty($step['text'])): ?><p><?= e($step['text']) ?></p><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>

      <!-- TOOLS -->
      <?php if ($tools): ?>
      <section class="svc-block" id="tools">
        <p class="detail-section-label">Tools &amp; platforms</p>
        <ul class="svc-tools">
          <?php foreach ($tools as $tool): ?><li><?= e($tool) ?></li><?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>
    </div>

    <!-- STICKY BOOKING CARD -->
    <aside class="svc-aside">
      <div class="svc-sticky-card">
        <p class="svc-sticky-label">Ready to start?</p>
        <?php if (!empty($service['price_label'])): ?>
          <p class="svc-sticky-price"><span>Starting at</span><?= e($service['price_label']) ?></p>
        <?php else: ?>
          <p class="svc-sticky-price"><span>Pricing</span>Custom quote</p>
        <?php endif; ?>
        <ul class="svc-sticky-list">
          <?php if (!empty($service['turnaround'])): ?>
            <li>
              <svg class="svc-list-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <span><?= e($service['turnaround']) ?> delivery</span>
            </li>
          <?php endif; ?>
          <li>
            <svg class="svc-list-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span>Free 20-min discovery call</span>
          </li>
          <li>
            <svg class="svc-list-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span>Clear scope &amp; fixed quote</span>
          </li>
          <li>
            <svg class="svc-list-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
            <span>Revisions included</span>
          </li>
        </ul>
        <a href="#book" class="btn btn-primary svc-btn-block">Book this service</a>
        <?php if ($whatsappDigits !== '' && strlen($whatsappDigits) >= 8): ?>
          <a href="https://wa.me/<?= e($whatsappDigits) ?>?text=<?= rawurlencode('Hi! I am interested in your "' . $service['title'] . '" service.') ?>" class="btn btn-ghost svc-btn-block" target="_blank" rel="noopener noreferrer">Chat on WhatsApp</a>
        <?php elseif ($contactEmail !== ''): ?>
          <a href="mailto:<?= e($contactEmail) ?>?subject=<?= rawurlencode('Question about ' . $service['title']) ?>" class="btn btn-ghost svc-btn-block">Ask a question</a>
        <?php endif; ?>
        <div class="svc-sticky-proof">
          <?php if ($totalProjects > 0): ?><div><strong><?= $totalProjects ?>+</strong><span>Projects</span></div><?php endif; ?>
          <?php if ($clientCount > 0): ?><div><strong><?= $clientCount ?>+</strong><span>Brands</span></div><?php endif; ?>
          <?php if ($avgRating > 0): ?><div><strong><?= e(number_format($avgRating, 1)) ?>&#9733;</strong><span>Rating</span></div><?php endif; ?>
        </div>
      </div>
    </aside>
  </div>

  <!-- PACKAGES -->
  <?php if ($packages): ?>
  <section class="svc-section" id="packages">
    <div class="svc-section-head">
      <p class="detail-section-label">Packages</p>
      <h2 class="svc-block-title">Choose the plan that fits</h2>
      <p class="svc-section-lede">Transparent pricing. Every package can be tailored to your goals.</p>
    </div>
    <div class="svc-packages svc-packages-<?= min(count($packages), 4) ?>">
      <?php foreach ($packages as $pkg):
        $features = service_lines($pkg['features'] ?? '');
        $isPopular = !empty($pkg['featured']);
      ?>
        <div class="svc-package<?= $isPopular ? ' is-popular' : '' ?>">
          <?php if ($isPopular): ?><span class="svc-package-flag">Most popular</span><?php endif; ?>
          <h3 class="svc-package-name"><?= e($pkg['name'] ?? 'Package') ?></h3>
          <?php if (!empty($pkg['price'])): ?>
            <p class="svc-package-price"><?= e($pkg['price']) ?><?php if (!empty($pkg['period'])): ?><span>/ <?= e($pkg['period']) ?></span><?php endif; ?></p>
          <?php endif; ?>
          <?php if (!empty($pkg['summary'])): ?><p class="svc-package-summary"><?= e($pkg['summary']) ?></p><?php endif; ?>
          <?php if ($features): ?>
            <ul class="svc-package-features">
              <?php foreach ($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <a href="#book" class="btn <?= $isPopular ? 'btn-primary' : 'btn-ghost' ?> svc-btn-block" data-choose-package="<?= e($pkg['name'] ?? '') ?>">Choose <?= e($pkg['name'] ?? 'package') ?></a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- RELATED WORK -->
  <?php if ($relatedProjects): ?>
  <section class="svc-section" id="work">
    <div class="svc-section-head">
      <p class="detail-section-label"><?= $relatedIsFallback ? 'Recent work' : 'Proof of work' ?></p>
      <h2 class="svc-block-title"><?= $relatedIsFallback ? 'A look at my recent projects' : 'Projects delivered with this service' ?></h2>
    </div>
    <div class="work-grid">
      <?php foreach ($relatedProjects as $p): $pUrl = SITE_ROOT_URL . '/project.php?slug=' . urlencode($p['slug']); ?>
        <article class="project-card">
          <div class="project-media">
            <?php if ($p['cover_image']): ?>
              <a class="project-image-link" href="<?= e($pUrl) ?>" aria-label="View project: <?= e($p['title']) ?>"><img src="<?= e(UPLOAD_URL . $p['cover_image']) ?>" alt="<?= e($p['title']) ?>" loading="lazy" width="640" height="400"></a>
            <?php endif; ?>
          </div>
          <div class="project-body">
            <?php if ($p['category_name']): ?><p class="project-cat"><?= e($p['category_name']) ?></p><?php endif; ?>
            <h3 class="project-title"><a href="<?= e($pUrl) ?>"><?= e($p['title']) ?></a></h3>
            <?php if ($p['short_description']): ?><div class="project-desc"><?= limit_rich_text_words((string) $p['short_description'], 20) ?></div><?php endif; ?>
            <a class="project-open" href="<?= e($pUrl) ?>">View case study <span aria-hidden="true">&#8599;</span></a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- TESTIMONIALS -->
  <?php if ($testimonials): ?>
  <section class="svc-section" id="reviews">
    <div class="svc-section-head">
      <p class="detail-section-label">Client words</p>
      <h2 class="svc-block-title">Trusted by people I've worked with</h2>
    </div>
    <div class="svc-reviews">
      <?php foreach ($testimonials as $t): ?>
        <figure class="svc-review">
          <div class="svc-review-stars" aria-label="<?= (int) $t['rating'] ?> out of 5 stars"><?= str_repeat('&#9733;', max(1, min(5, (int) $t['rating']))) ?></div>
          <blockquote>&ldquo;<?= e($t['quote']) ?>&rdquo;</blockquote>
          <figcaption>
            <?php if (!empty($t['client_photo'])): ?><img src="<?= e(UPLOAD_URL . $t['client_photo']) ?>" alt="" loading="lazy" width="44" height="44"><?php else: ?><span class="svc-review-initial"><?= e(mb_strtoupper(mb_substr($t['client_name'], 0, 1))) ?></span><?php endif; ?>
            <span><strong><?= e($t['client_name']) ?></strong><?php if (!empty($t['client_role'])): ?><small><?= e($t['client_role']) ?></small><?php endif; ?></span>
          </figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- FAQ -->
  <section class="svc-section svc-faq-section" id="faq">
    <div class="svc-section-head">
      <p class="detail-section-label">FAQ</p>
      <h2 class="svc-block-title">Questions, answered</h2>
    </div>
    <div class="svc-faq">
      <?php foreach ($faqs as $i => $faq): ?>
        <details class="svc-faq-item"<?= $i === 0 ? ' open' : '' ?>>
          <summary><?= e($faq['q'] ?? '') ?><span class="svc-faq-toggle" aria-hidden="true"></span></summary>
          <p><?= nl2br(e($faq['a'] ?? '')) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- BOOKING -->
  <section class="svc-section svc-book" id="book">
    <div class="svc-book-intro">
      <p class="detail-section-label">Book this service</p>
      <h2 class="svc-block-title">Let's make it happen.</h2>
      <p class="svc-section-lede">Tell me a little about your project. I'll review it personally and get back to you within 24 hours.</p>
      <ol class="svc-next-steps">
        <li><strong>Send your brief</strong><span>Takes less than 2 minutes.</span></li>
        <li><strong>Discovery call</strong><span>We align on goals, scope and timeline.</span></li>
        <li><strong>Proposal &amp; kickoff</strong><span>Clear quote, then we start creating.</span></li>
      </ol>
    </div>

    <div class="svc-book-card">
      <?php if ($bookingSuccess): ?>
        <div class="svc-book-success" role="status">
          <span class="svc-book-success-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><polyline points="20 6 9 17 4 12"/></svg></span>
          <h3>Request received!</h3>
          <p>Thank you — your booking request for <strong><?= e($service['title']) ?></strong> is in. I'll reply to your email within 24 hours.</p>
          <a href="<?= e(SITE_ROOT_URL) ?>/page.php?view=work" class="btn btn-ghost">Browse more work meanwhile</a>
        </div>
      <?php else: ?>
        <?php if ($bookingErrors): ?>
          <div class="contact-status contact-status-error" role="alert"><?php foreach ($bookingErrors as $err): ?><?= e($err) ?><br><?php endforeach; ?></div>
        <?php endif; ?>
        <form class="svc-book-form" action="<?= e(SITE_ROOT_URL) ?>/service-book.php" method="POST" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="service_id" value="<?= $serviceId ?>">
          <div class="svc-hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="svc-form-row">
            <label>Your name *<input type="text" name="name" required maxlength="120" value="<?= e($old['name'] ?? '') ?>" autocomplete="name"></label>
            <label>Email *<input type="email" name="email" required maxlength="150" value="<?= e($old['email'] ?? '') ?>" autocomplete="email"></label>
          </div>
          <div class="svc-form-row">
            <label>Phone / WhatsApp<input type="tel" name="phone" maxlength="40" value="<?= e($old['phone'] ?? '') ?>" autocomplete="tel"></label>
            <label>Company / brand<input type="text" name="company" maxlength="120" value="<?= e($old['company'] ?? '') ?>" autocomplete="organization"></label>
          </div>
          <div class="svc-form-row">
            <label>Package
              <select name="package" id="svcPackageSelect">
                <option value="">Not sure yet — help me choose</option>
                <?php foreach ($packages as $pkg): $pn = (string) ($pkg['name'] ?? ''); if ($pn === '') continue; ?>
                  <option value="<?= e($pn) ?>"<?= $selectedPackage === $pn ? ' selected' : '' ?>><?= e($pn) ?><?= !empty($pkg['price']) ? ' — ' . e($pkg['price']) : '' ?></option>
                <?php endforeach; ?>
                <option value="Custom"<?= $selectedPackage === 'Custom' ? ' selected' : '' ?>>Custom plan</option>
              </select>
            </label>
            <label>Preferred start
              <select name="timeline">
                <?php foreach (['As soon as possible', 'Within 2 weeks', 'Within a month', 'In 1–3 months', 'Flexible'] as $opt): ?>
                  <option value="<?= e($opt) ?>"<?= ($old['timeline'] ?? '') === $opt ? ' selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
          <label>Estimated budget<input type="text" name="budget" maxlength="80" placeholder="e.g. NPR 50,000 or a range" value="<?= e($old['budget'] ?? '') ?>"></label>
          <label>Project details *<textarea name="message" required maxlength="4000" rows="5" placeholder="Your goals, audience, deadlines, links to references…"><?= e($old['message'] ?? '') ?></textarea></label>
          <button type="submit" class="btn btn-primary svc-btn-block svc-btn-lg">Send booking request <span aria-hidden="true">&rarr;</span></button>
          <p class="svc-form-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;display:inline-block;vertical-align:-1px;margin-right:5px;" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Your details stay private and are only used to reply to you.</p>
        </form>
      <?php endif; ?>
    </div>
  </section>

  <!-- OTHER SERVICES -->
  <?php if ($otherServices): ?>
  <section class="svc-section" id="more-services">
    <div class="svc-section-head svc-section-head-row">
      <div>
        <p class="detail-section-label">Explore more</p>
        <h2 class="svc-block-title">Other services</h2>
      </div>
      <a href="<?= e(SITE_ROOT_URL) ?>/page.php?view=services" class="btn btn-ghost section-cta">All services <span aria-hidden="true">&rarr;</span></a>
    </div>
    <div class="svc-grid">
      <?php foreach ($otherServices as $i => $os) render_service_card($os, $projectCounts[(int) $os['id']] ?? 0, $i); ?>
    </div>
  </section>
  <?php endif; ?>
</article>

<?php require __DIR__ . '/includes/clients-slider.php'; ?>

<!-- Mobile sticky CTA -->
<div class="svc-mobile-cta" id="svcMobileCta">
  <div>
    <strong><?= e($service['title']) ?></strong>
    <?php if (!empty($service['price_label'])): ?><span>From <?= e($service['price_label']) ?></span><?php endif; ?>
  </div>
  <a href="#book" class="btn btn-primary btn-sm">Book now</a>
</div>

<?php
// ---- Structured data (Service + FAQ) for rich search results ----
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => $service['title'],
    'description' => strip_tags((string) ($service['tagline'] ?: $service['description'])),
    'url' => $serviceUrl,
    'provider' => ['@type' => 'Person', 'name' => get_setting($pdo, 'site_logo_text', get_setting($pdo, 'site_title'))],
];
if (!empty($service['cover_image'])) $jsonLd['image'] = UPLOAD_URL . $service['cover_image'];
if ($avgRating > 0) $jsonLd['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $avgRating, 'reviewCount' => (int) $ratingRow['total']];
$faqLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(static fn($f) => ['@type' => 'Question', 'name' => (string) ($f['q'] ?? ''), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string) ($f['a'] ?? '')]], $faqs),
];
$ldFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP;
?>
<script type="application/ld+json"><?= json_encode($jsonLd, $ldFlags) ?></script>
<script type="application/ld+json"><?= json_encode($faqLd, $ldFlags) ?></script>
<script>
(function () {
  // "Choose package" buttons pre-select the package in the booking form.
  var select = document.getElementById('svcPackageSelect');
  document.querySelectorAll('[data-choose-package]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!select) return;
      var name = btn.getAttribute('data-choose-package');
      for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value === name) { select.selectedIndex = i; break; }
      }
      select.classList.add('svc-flash');
      setTimeout(function () { select.classList.remove('svc-flash'); }, 1200);
    });
  });

  // Show the mobile CTA bar only after the hero, hide it when the booking form is visible.
  var bar = document.getElementById('svcMobileCta');
  var hero = document.querySelector('.svc-hero');
  var book = document.getElementById('book');
  if (bar && hero && book && 'IntersectionObserver' in window) {
    var heroVisible = true, bookVisible = false;
    var update = function () { bar.classList.toggle('is-visible', !heroVisible && !bookVisible); };
    new IntersectionObserver(function (e) { heroVisible = e[0].isIntersecting; update(); }).observe(hero);
    new IntersectionObserver(function (e) { bookVisible = e[0].isIntersecting; update(); }).observe(book);
  }

  // Highlight the current section in the sub navigation.
  var links = document.querySelectorAll('.svc-subnav a[href^="#"]');
  if ('IntersectionObserver' in window && links.length) {
    var map = {};
    links.forEach(function (a) { var t = document.querySelector(a.getAttribute('href')); if (t) map[t.id] = a; });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting && map[en.target.id]) {
          links.forEach(function (l) { l.classList.remove('is-active'); });
          map[en.target.id].classList.add('is-active');
        }
      });
    }, { rootMargin: '-40% 0px -55% 0px' });
    Object.keys(map).forEach(function (id) { io.observe(document.getElementById(id)); });
  }

  <?php if ($bookingSuccess || $bookingErrors): ?>
  // Bring the visitor back to the form result after the redirect.
  window.addEventListener('load', function () { if (book) book.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
  <?php endif; ?>
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
