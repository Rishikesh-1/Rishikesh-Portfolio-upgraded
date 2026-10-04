<?php
/**
 * services-lib.php — Shared helpers for the Services feature.
 *
 * - ensure_services_schema(): idempotent migration that upgrades the `services`
 *   table with detail-page columns and creates the `service_projects` pivot.
 * - service_icon_svg(): curated inline SVG icon set (no external icon fonts).
 * - service_* decoders for the JSON / line-based columns.
 * - render_service_card(): one card markup used on the homepage + services page.
 */

const SERVICES_SCHEMA_VERSION = '2';

function ensure_services_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (get_setting($pdo, 'services_schema_version') === SERVICES_SCHEMA_VERSION) {
        return;
    }

    $existing = $pdo->query(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services'"
    )->fetchAll(PDO::FETCH_COLUMN);
    $existing = array_map('strtolower', $existing);

    $columns = [
        'slug'             => 'VARCHAR(180) DEFAULT NULL',
        'tagline'          => 'VARCHAR(255) DEFAULT NULL',
        'cover_image'      => 'VARCHAR(255) DEFAULT NULL',
        'overview'         => 'MEDIUMTEXT',
        'deliverables'     => 'TEXT',
        'process_json'     => 'TEXT',
        'packages_json'    => 'TEXT',
        'faqs_json'        => 'TEXT',
        'tools'            => 'TEXT',
        'turnaround'       => 'VARCHAR(80) DEFAULT NULL',
        'is_featured'      => 'TINYINT(1) NOT NULL DEFAULT 0',
        'meta_title'       => 'VARCHAR(180) DEFAULT NULL',
        'meta_description' => 'VARCHAR(300) DEFAULT NULL',
    ];
    foreach ($columns as $name => $definition) {
        if (!in_array($name, $existing, true)) {
            $pdo->exec("ALTER TABLE services ADD COLUMN `{$name}` {$definition}");
        }
    }

    // Unique slug index (only add once).
    $hasIndex = $pdo->query(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'services' AND INDEX_NAME = 'uniq_services_slug'"
    )->fetchColumn();

    // Backfill slugs for older rows before enforcing uniqueness.
    $rows = $pdo->query("SELECT id, title FROM services WHERE slug IS NULL OR slug = ''")->fetchAll();
    $update = $pdo->prepare('UPDATE services SET slug = :slug WHERE id = :id');
    foreach ($rows as $row) {
        $update->execute([':slug' => unique_service_slug($pdo, (string) $row['title'], (int) $row['id']), ':id' => $row['id']]);
    }

    if (!(int) $hasIndex) {
        $pdo->exec('ALTER TABLE services ADD UNIQUE INDEX uniq_services_slug (slug)');
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS service_projects (
            service_id INT UNSIGNED NOT NULL,
            project_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (service_id, project_id),
            INDEX idx_service_projects_project (project_id),
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = :v2'
    );
    $stmt->execute([':k' => 'services_schema_version', ':v' => SERVICES_SCHEMA_VERSION, ':v2' => SERVICES_SCHEMA_VERSION]);
}

/** Build a slug that is unique in the services table (optionally ignoring one id). */
function unique_service_slug(PDO $pdo, string $source, int $ignoreId = 0): string
{
    $base = make_slug($source);
    if ($base === '') {
        $base = 'service';
    }
    $base = substr($base, 0, 170);
    $slug = $base;
    $i = 1;
    $check = $pdo->prepare('SELECT COUNT(*) FROM services WHERE slug = :slug AND id <> :id');
    while (true) {
        $check->execute([':slug' => $slug, ':id' => $ignoreId]);
        if ((int) $check->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . (++$i);
    }
}

function service_url(array $service): string
{
    return SITE_ROOT_URL . '/service.php?slug=' . rawurlencode((string) ($service['slug'] ?? ''));
}

/** Curated icon set: key => [label, svg inner markup]. */
function service_icon_options(): array
{
    return [
        'social'    => ['Social media', '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none"/>'],
        'video'     => ['Video production', '<rect x="2" y="6" width="14" height="12" rx="2"/><path d="m16 10 6-3v10l-6-3"/>'],
        'camera'    => ['Photography', '<path d="M4 8h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/><circle cx="12" cy="13.5" r="3.5"/>'],
        'design'    => ['Graphic design', '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 2-.9 2-2 0-.5-.2-1-.5-1.3-.3-.4-.5-.8-.5-1.3 0-1.1.9-2 2-2h2.3A4.7 4.7 0 0 0 22 9.7C22 5.9 17.5 3 12 3Z"/><circle cx="7.5" cy="11" r="1.2" fill="currentColor" stroke="none"/><circle cx="10" cy="7" r="1.2" fill="currentColor" stroke="none"/><circle cx="15" cy="7" r="1.2" fill="currentColor" stroke="none"/>'],
        'brand'     => ['Branding', '<path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z"/><path d="m3 7 9 5 9-5M12 12v10"/>'],
        'web'       => ['Web development', '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 9h20M6 6.5h.01M9 6.5h.01"/><path d="m10 13-2 2 2 2M14 13l2 2-2 2"/>'],
        'marketing' => ['Digital marketing', '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M15 8.5a5 5 0 0 1 0 7M18 6a8.5 8.5 0 0 1 0 12"/>'],
        'strategy'  => ['Strategy', '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none"/>'],
        'content'   => ['Content writing', '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>'],
        'ads'       => ['Paid ads', '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 6-6"/><path d="M15 8h5v5"/>'],
        'seo'       => ['SEO', '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/><path d="M8 11h6M11 8v6"/>'],
        'mic'       => ['Podcast / audio', '<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5M8 22h8"/>'],
        'ai'        => ['AI & automation', '<rect x="5" y="7" width="14" height="12" rx="3"/><path d="M12 3v4M9 12h.01M15 12h.01M9.5 16h5M2 12h3M19 12h3"/>'],
        'consult'   => ['Consulting', '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12Z"/><path d="M8.5 10.5h7M8.5 13.5h4"/>'],
        'spark'     => ['Creative / general', '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/>'],
    ];
}

function service_icon_svg(?string $key, string $class = 'svc-icon'): string
{
    $options = service_icon_options();
    $key = strtolower(trim((string) $key));
    $inner = $options[$key][1] ?? $options['spark'][1];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

/** Decode a JSON array column safely. */
function service_json(?string $json): array
{
    if ($json === null || trim($json) === '') {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
}

/** Split newline-separated text into clean, non-empty lines. */
function service_lines(?string $text): array
{
    $lines = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
    $lines = array_map(static fn($l) => trim(ltrim(trim($l), "-•*✓ \t")), $lines);
    return array_values(array_filter($lines, static fn($l) => $l !== ''));
}

/** Split comma-separated tools into chips. */
function service_tools(?string $text): array
{
    $parts = preg_split('/[,\n]+/', (string) $text) ?: [];
    $parts = array_map('trim', $parts);
    return array_values(array_unique(array_filter($parts, static fn($p) => $p !== '')));
}

/** Sensible default process used when a service has none configured. */
function service_default_process(): array
{
    return [
        ['title' => 'Discovery call', 'text' => 'We talk through your goals, audience, timeline and budget so I understand exactly what success looks like for you.'],
        ['title' => 'Strategy & proposal', 'text' => 'You receive a clear plan with scope, deliverables, milestones and a fixed quote — no surprises later.'],
        ['title' => 'Create & refine', 'text' => 'I produce the work in focused sprints and share progress early, so your feedback shapes the result.'],
        ['title' => 'Launch & handover', 'text' => 'Final files, launch support and a short report on results, plus recommendations for what to do next.'],
    ];
}

/** Sensible default FAQs used when a service has none configured. */
function service_default_faqs(): array
{
    return [
        ['q' => 'How do we get started?', 'a' => 'Click “Book this service” and share a few details about your project. I reply within 24 hours to schedule a free discovery call.'],
        ['q' => 'How long does a typical project take?', 'a' => 'It depends on scope, but every proposal includes a clear timeline with milestones before any work begins.'],
        ['q' => 'How many revisions are included?', 'a' => 'Each package includes revision rounds so the final result matches your vision. Extra rounds can be added anytime.'],
        ['q' => 'How does payment work?', 'a' => 'Usually a deposit to reserve your slot and the balance on delivery. Monthly retainers are billed at the start of each month.'],
        ['q' => 'Can you tailor a package to my needs?', 'a' => 'Absolutely. Packages are a starting point — pick “Custom” in the booking form and I will build a plan around your goals.'],
    ];
}

/** Count related projects per service id in one query. */
function service_project_counts(PDO $pdo): array
{
    try {
        $rows = $pdo->query(
            'SELECT sp.service_id, COUNT(*) AS c FROM service_projects sp
             JOIN projects p ON p.id = sp.project_id AND p.is_visible = 1
             GROUP BY sp.service_id'
        )->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
    $counts = [];
    foreach ($rows as $row) {
        $counts[(int) $row['service_id']] = (int) $row['c'];
    }
    return $counts;
}

/** Render one premium service card. */
function render_service_card(array $s, int $projectCount = 0, int $index = 0): void
{
    $url = service_url($s);
    $hasCover = !empty($s['cover_image']);
    ?>
    <article class="svc-card<?= !empty($s['is_featured']) ? ' is-featured' : '' ?><?= $hasCover ? ' has-cover' : '' ?>" data-aos="fade-up" data-aos-delay="<?= (int) min($index * 80, 320) ?>">
      <a href="<?= e($url) ?>" class="svc-card-media-link" tabindex="-1" aria-hidden="true">
        <div class="svc-card-media">
          <?php if ($hasCover): ?>
            <img src="<?= e(UPLOAD_URL . $s['cover_image']) ?>" alt="" loading="lazy" width="640" height="400">
          <?php else: ?>
            <div class="svc-card-art" aria-hidden="true"><?= service_icon_svg($s['icon_class'] ?? '', 'svc-card-art-icon') ?></div>
          <?php endif; ?>
          <span class="svc-card-badge"><?= service_icon_svg($s['icon_class'] ?? '') ?></span>
          <?php if (!empty($s['is_featured'])): ?><span class="svc-card-flag">Most requested</span><?php endif; ?>
        </div>
      </a>
      <div class="svc-card-body">
        <h3 class="svc-card-title"><a href="<?= e($url) ?>" class="svc-card-link"><?= e($s['title']) ?></a></h3>
        <?php if (!empty($s['description'])): ?><p class="svc-card-desc"><?= e($s['description']) ?></p><?php endif; ?>
        <a href="<?= e($url) ?>" class="svc-card-cta">Explore service <span aria-hidden="true">&rarr;</span></a>
      </div>
    </article>
    <?php
}
