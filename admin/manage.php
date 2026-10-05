<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$section = $_GET['section'] ?? '';
if ($section === 'clients') {
    ensure_clients_schema($pdo);
} elseif ($section === 'services') {
    ensure_services_schema($pdo);
} elseif ($section === 'products') {
    ensure_products_schema($pdo);
}
$definitions = [
    'experience' => [
        'title' => 'Experience',
        'table' => 'experience',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'role_title' => ['label' => 'Role title', 'required' => true, 'maxlength' => 150],
            'organization' => ['label' => 'Organization', 'required' => true, 'maxlength' => 150],
            'location' => ['label' => 'Location', 'maxlength' => 120],
            'start_date' => ['label' => 'Start date', 'required' => true, 'maxlength' => 30],
            'end_date' => ['label' => 'End date', 'default' => 'Present', 'maxlength' => 30],
            'description' => ['label' => 'Description', 'type' => 'textarea'],
            'sort_order' => ['label' => 'Display order (1 = first, 2 = second...)', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['sort_order', 'role_title', 'organization', 'start_date', 'is_visible'],
    ],
    'skills' => [
        'title' => 'Skills',
        'table' => 'skills',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'skill_name' => ['label' => 'Skill name', 'required' => true, 'maxlength' => 100],
            'category' => ['label' => 'Category', 'default' => 'General', 'maxlength' => 80],
            'proficiency' => ['label' => 'Proficiency (%)', 'type' => 'number', 'default' => 80, 'min' => 0, 'max' => 100],
            'icon_class' => ['label' => 'Icon class', 'maxlength' => 80],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
        ],
        'columns' => ['skill_name', 'category', 'proficiency'],
    ],
    'services' => [
        'title' => 'Services',
        'table' => 'services',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'title' => ['label' => 'Service Title', 'required' => true, 'maxlength' => 150],
            'slug' => ['label' => 'URL Slug (e.g. social-media-management, leave empty to auto-generate)', 'maxlength' => 180],
            'tagline' => ['label' => 'Tagline / Value Proposition', 'maxlength' => 255],
            'description' => ['label' => 'Short Summary (Shown on homepage cards)', 'type' => 'textarea'],
            'cover_image' => ['label' => 'Cover Image / Showcase Graphic', 'type' => 'image'],
            'icon_class' => ['label' => 'Service Category Icon', 'type' => 'select', 'options' => array_keys(service_icon_options())],
            'price_label' => ['label' => 'Starting Price (e.g. From $650 / mo, NPR 50,000)', 'maxlength' => 80],
            'turnaround' => ['label' => 'Delivery Timeline (e.g. 2–3 weeks, Monthly retainer)', 'maxlength' => 80],
            'deliverables' => ['label' => 'Key Deliverables (One per line with "-" or "•")', 'type' => 'textarea'],
            'tools' => ['label' => 'Tools & Platforms (Comma-separated: Premiere Pro, Canva, Meta Suite)', 'maxlength' => 255],
            'overview' => ['label' => 'In-Depth Overview & Scope (Shown on service detail page)', 'type' => 'textarea'],
            'meta_title' => ['label' => 'SEO Meta Title (Optional)', 'maxlength' => 180],
            'meta_description' => ['label' => 'SEO Meta Description (Optional)', 'maxlength' => 255],
            'is_featured' => ['label' => 'Featured service (Highlighted card with badge)', 'type' => 'checkbox', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
        ],
        'columns' => ['cover_image', 'title', 'price_label', 'turnaround', 'is_featured', 'is_visible'],
    ],
    'social' => [
        'title' => 'Social links',
        'table' => 'social_links',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'platform' => ['label' => 'Platform', 'type' => 'select', 'options' => ['YouTube', 'Instagram', 'TikTok', 'LinkedIn', 'Facebook', 'Vimeo'], 'required' => true],
            'handle' => ['label' => 'Handle', 'maxlength' => 100],
            'url' => ['label' => 'URL', 'type' => 'url', 'required' => true, 'maxlength' => 255],
            'icon_class' => ['label' => 'Icon class', 'maxlength' => 80],
            'embed_code' => ['label' => 'Embed code', 'type' => 'textarea'],
            'followers_label' => ['label' => 'Followers label', 'maxlength' => 50],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['platform', 'handle', 'url', 'is_visible'],
    ],
    'social_posts' => [
        'title' => 'Social content',
        'table' => 'social_posts',
        'primary' => 'id',
        'order' => 'is_featured DESC, sort_order ASC, id DESC',
        'fields' => [
            'platform' => ['label' => 'Platform', 'type' => 'select', 'options' => ['YouTube', 'Instagram', 'TikTok', 'LinkedIn', 'Vimeo'], 'required' => true],
            'title' => ['label' => 'Post or video title', 'required' => true, 'maxlength' => 180],
            'url' => ['label' => 'Post URL', 'type' => 'url', 'required' => true, 'maxlength' => 500],
            'embed_url' => ['label' => 'Video URL for inline player (YouTube or Vimeo)', 'type' => 'url', 'maxlength' => 500],
            'thumbnail_image' => ['label' => 'Upload thumbnail (JPG, PNG, or WEBP)', 'type' => 'image'],
            'thumbnail_url' => ['label' => 'Thumbnail URL', 'type' => 'url', 'maxlength' => 500],
            'description' => ['label' => 'Short description', 'type' => 'textarea'],
            'is_featured' => ['label' => 'Feature this post', 'type' => 'checkbox', 'default' => 0],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['platform', 'title', 'is_featured', 'is_visible'],
    ],
    'testimonials' => [
        'title' => 'Testimonials',
        'table' => 'testimonials',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'client_name' => ['label' => 'Client name', 'required' => true, 'maxlength' => 120],
            'client_role' => ['label' => 'Client role', 'maxlength' => 150],
            'client_photo' => ['label' => 'Client photo', 'type' => 'image'],
            'quote' => ['label' => 'Quote', 'type' => 'textarea', 'required' => true],
            'rating' => ['label' => 'Rating (1-5)', 'type' => 'number', 'default' => 5, 'min' => 1, 'max' => 5],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['client_name', 'client_role', 'rating', 'is_visible'],
    ],
    'clients' => [
        'title' => 'Clients & Brands',
        'table' => 'clients',
        'primary' => 'id',
        'order' => 'sort_order ASC, id DESC',
        'fields' => [
            'name' => ['label' => 'Company / Brand name', 'required' => true, 'maxlength' => 150],
            'logo_image' => ['label' => 'Logo image (PNG, SVG, WEBP, JPEG, JPG)', 'type' => 'image', 'required' => true],
            'website_url' => ['label' => 'Website URL (optional)', 'type' => 'url', 'maxlength' => 255],
            'sort_order' => ['label' => 'Display order (1 = first, 2 = second...)', 'type' => 'number', 'default' => 0],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
        ],
        'columns' => ['logo_image', 'name', 'website_url', 'sort_order', 'is_visible'],
    ],
    'products' => [
        'title' => 'Projects for Sale & Deals',
        'table' => 'products',
        'primary' => 'id',
        'order' => 'is_featured DESC, sort_order ASC, id DESC',
        'fields' => [
            'title' => ['label' => 'Project / Item Title', 'required' => true, 'maxlength' => 200],
            'slug' => ['label' => 'URL Slug (leave blank to auto-generate)', 'maxlength' => 220],
            'badge_text' => ['label' => 'Sale Badge (e.g. 66% OFF, FLASH SALE, HOT DEAL, LIMITED OFFER)', 'maxlength' => 100, 'default' => '66% OFF • LIMITED DEAL'],
            'category_name' => ['label' => 'Category (e.g. Full Website & CMS, Mobile App, Web App, Template)', 'maxlength' => 100, 'default' => 'Full Website & CMS'],
            'sale_price' => ['label' => 'Sale / Offer Price (e.g. Rs. 10,000 or $99)', 'required' => true, 'maxlength' => 100],
            'regular_price' => ['label' => 'Original / Regular Price (e.g. Rs. 30,000 or $299 — displayed with strikethrough)', 'maxlength' => 100],
            'tagline' => ['label' => 'Catchy Tagline / One-liner', 'maxlength' => 255],
            'demo_url' => ['label' => 'Live Demo URL (Link where customers can preview/test the project)', 'type' => 'url', 'maxlength' => 500],
            'cover_image' => ['label' => 'Cover / Showcase Image (JPG, PNG, or WEBP)', 'type' => 'image'],
            'short_description' => ['label' => 'Short Summary (Shown on product cards)', 'type' => 'textarea'],
            'features_list' => ['label' => 'Features & Deliverables Included (One item per line)', 'type' => 'textarea'],
            'customization_note' => ['label' => 'Customization Terms (e.g. Buy exact project as demo. Extra changes charged as per requirement)', 'maxlength' => 255, 'default' => 'Buy exact same project as demo for fixed offer price. Extra custom modifications or additions charged separately based on requirements.'],
            'is_featured' => ['label' => 'Highlight as Featured Deal (Top placement with glow)', 'type' => 'checkbox', 'default' => 1],
            'is_visible' => ['label' => 'Visible on live site', 'type' => 'checkbox', 'default' => 1],
            'sort_order' => ['label' => 'Display order', 'type' => 'number', 'default' => 0],
        ],
        'columns' => ['cover_image', 'title', 'sale_price', 'regular_price', 'badge_text', 'is_featured', 'is_visible'],
    ],
];

if (!isset($definitions[$section])) {
    http_response_code(404);
    exit('Section not found.');
}

$definition = $definitions[$section];
$table = $definition['table'];
$fields = $definition['fields'];
$primary = $definition['primary'];
$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);
$error = '';
$record = [];

if ($action === 'edit') {
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$primary} = :id");
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch();
    if (!$record) {
        header('Location: manage.php?section=' . urlencode($section));
        exit;
    }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$primary} = :id");
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch();
    if ($record) {
        foreach ($fields as $fieldName => $field) {
            if (($field['type'] ?? '') === 'image' && !empty($record[$fieldName])) {
                delete_uploaded_file($record[$fieldName]);
            }
        }
        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$primary} = :id");
        $stmt->execute([':id' => $id]);
    }
    header('Location: manage.php?section=' . urlencode($section) . '&deleted=1');
    exit;
}

if (($action === 'add' || $action === 'edit') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($fields as $name => $field) {
        if (($field['type'] ?? '') === 'checkbox') {
            $_POST[$name] = isset($_POST[$name]) ? 1 : 0;
        } elseif (($field['type'] ?? '') !== 'image') {
            $_POST[$name] = trim($_POST[$name] ?? '');
        }
    }

    foreach ($fields as $name => $field) {
        if (!empty($field['required'])) {
            if (($field['type'] ?? '') === 'image') {
                $hasUploadedFile = !empty($_FILES[$name]['name']) && ($_FILES[$name]['error'] === UPLOAD_ERR_OK);
                $hasExistingFile = !empty($record[$name]);
                $hasLibraryFile = !empty($_POST[$name . '_existing']) && is_file(UPLOAD_DIR . basename($_POST[$name . '_existing']));
                if (!$hasUploadedFile && !$hasExistingFile && !$hasLibraryFile) {
                    $error = $field['label'] . ' is required.';
                    break;
                }
            } else {
                if (($_POST[$name] ?? '') === '') {
                    $error = $field['label'] . ' is required.';
                    break;
                }
            }
        }
    }
    if (!$error && isset($_POST['proficiency']) && ((int)$_POST['proficiency'] < 0 || (int)$_POST['proficiency'] > 100)) {
        $error = 'Proficiency must be between 0 and 100.';
    }
    if (!$error && isset($_POST['rating']) && ((int)$_POST['rating'] < 1 || (int)$_POST['rating'] > 5)) {
        $error = 'Rating must be between 1 and 5.';
    }
    if (!$error && in_array($section, ['social', 'social_posts'], true) && !filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid URL.';
    }
    if (!$error && $section === 'social_posts' && !empty($_POST['thumbnail_url']) && !filter_var($_POST['thumbnail_url'], FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid thumbnail URL.';
    }
    if (!$error && $section === 'social_posts' && !empty($_POST['embed_url'])) {
        $safeEmbedUrl = social_embed_url($_POST['embed_url'], $_POST['platform']);
        if (!$safeEmbedUrl) {
            $error = 'Inline video supports YouTube URLs for YouTube posts and Vimeo URLs for Vimeo posts.';
        } else {
            $_POST['embed_url'] = $safeEmbedUrl;
        }
    }

    if (!$error && in_array($section, ['services', 'products'], true)) {
        $rawSlug = trim($_POST['slug'] ?? '');
        if ($rawSlug === '' && !empty($_POST['title'])) {
            $rawSlug = make_slug($_POST['title']);
        } elseif ($rawSlug !== '') {
            $rawSlug = make_slug($rawSlug);
        }
        if ($rawSlug !== '') {
            $slugCheck = $pdo->prepare("SELECT id FROM {$table} WHERE slug = :s AND id != :id LIMIT 1");
            $slugCheck->execute([':s' => $rawSlug, ':id' => $id]);
            if ($slugCheck->fetch()) {
                $rawSlug .= '-' . time();
            }
            $_POST['slug'] = $rawSlug;
        }
    }

    $newImages = [];
    if (!$error) {
        try {
            foreach ($fields as $fieldName => $field) {
                if (($field['type'] ?? '') === 'image') {
                    $uploaded = (!empty($_FILES[$fieldName]) && is_array($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['name'])) ? handle_image_upload($_FILES[$fieldName], $field['label']) : null;
                    if ($uploaded) {
                        $newImages[$fieldName] = $uploaded;
                    } elseif (!empty($_POST[$fieldName . '_existing'])) {
                        $chosen = basename(trim($_POST[$fieldName . '_existing']));
                        if (is_file(UPLOAD_DIR . $chosen)) {
                            $newImages[$fieldName] = $chosen;
                        }
                    }
                }
            }

            $values = [];
            foreach ($fields as $name => $field) {
                if (($field['type'] ?? '') === 'image') {
                    $values[$name] = $newImages[$name] ?? ($record[$name] ?? null);
                } elseif (($field['type'] ?? '') === 'number') {
                    $values[$name] = isset($_POST[$name]) && $_POST[$name] !== '' ? (int)$_POST[$name] : ($field['default'] ?? 0);
                } else {
                    $values[$name] = $_POST[$name] ?? null;
                }
            }

            if ($section === 'services') {
                // 1. Packages JSON
                $rawPackages = $_POST['pricing_packages'] ?? [];
                $cleanPackages = [];
                if (is_array($rawPackages)) {
                    foreach ($rawPackages as $pkg) {
                        $pName = trim((string)($pkg['name'] ?? ''));
                        if ($pName === '') continue;
                        $cleanPackages[] = [
                            'name'     => $pName,
                            'price'    => trim((string)($pkg['price'] ?? '')),
                            'period'   => trim((string)($pkg['period'] ?? '')),
                            'summary'  => trim((string)($pkg['summary'] ?? '')),
                            'features' => trim((string)($pkg['features'] ?? '')),
                            'featured' => !empty($pkg['featured']) ? 1 : 0,
                        ];
                    }
                }
                $values['packages_json'] = !empty($cleanPackages) ? json_encode($cleanPackages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

                // 2. Process Steps JSON
                $rawSteps = $_POST['process_steps'] ?? [];
                $cleanSteps = [];
                if (is_array($rawSteps)) {
                    foreach ($rawSteps as $st) {
                        $sTitle = trim((string)($st['title'] ?? ''));
                        if ($sTitle === '') continue;
                        $cleanSteps[] = [
                            'title' => $sTitle,
                            'text'  => trim((string)($st['text'] ?? '')),
                        ];
                    }
                }
                $values['process_json'] = !empty($cleanSteps) ? json_encode($cleanSteps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

                // 3. FAQs JSON
                $rawFaqs = $_POST['faq_items'] ?? [];
                $cleanFaqs = [];
                if (is_array($rawFaqs)) {
                    foreach ($rawFaqs as $fq) {
                        $fQ = trim((string)($fq['q'] ?? ''));
                        if ($fQ === '') continue;
                        $cleanFaqs[] = [
                            'q' => $fQ,
                            'a' => trim((string)($fq['a'] ?? '')),
                        ];
                    }
                }
                $values['faqs_json'] = !empty($cleanFaqs) ? json_encode($cleanFaqs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            }

            if ($action === 'add') {
                $columns = implode(', ', array_keys($values));
                $placeholders = ':' . implode(', :', array_keys($values));
                $stmt = $pdo->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})");
                foreach ($values as $name => $value) $stmt->bindValue(':' . $name, $value);
                $stmt->execute();
                $savedId = (int)$pdo->lastInsertId();
            } else {
                $assignments = implode(', ', array_map(static fn($name) => "{$name} = :{$name}", array_keys($values)));
                $stmt = $pdo->prepare("UPDATE {$table} SET {$assignments} WHERE {$primary} = :record_id");
                foreach ($values as $name => $value) $stmt->bindValue(':' . $name, $value);
                $stmt->bindValue(':record_id', $id, PDO::PARAM_INT);
                $stmt->execute();
                $savedId = $id;
            }

            // Sync metadata to media_items table for any image saved
            $metaItemTitle = trim($_POST['title'] ?? $_POST['name'] ?? $_POST['client_name'] ?? $_POST['role_title'] ?? ($definitions[$section]['title'] ?? ''));
            $metaItemDesc = trim($_POST['meta_description'] ?? $_POST['description'] ?? $_POST['overview'] ?? $_POST['tagline'] ?? $_POST['quote'] ?? '');
            $metaItemAlt = trim($_POST['meta_title'] ?? $metaItemTitle);
            foreach ($fields as $fName => $fDef) {
                if (($fDef['type'] ?? '') === 'image' && !empty($values[$fName])) {
                    update_media_metadata($pdo, $values[$fName], [
                        'title' => $metaItemTitle,
                        'alt_text' => $metaItemAlt,
                        'description' => $metaItemDesc,
                    ]);
                }
            }

            // Sync related projects for services
            if ($section === 'services' && $savedId) {
                $selectedProjects = isset($_POST['linked_projects']) && is_array($_POST['linked_projects'])
                    ? array_map('intval', $_POST['linked_projects'])
                    : [];
                $delStmt = $pdo->prepare("DELETE FROM service_projects WHERE service_id = :sid");
                $delStmt->execute([':sid' => $savedId]);
                if (!empty($selectedProjects)) {
                    $insProj = $pdo->prepare("INSERT IGNORE INTO service_projects (service_id, project_id) VALUES (:sid, :pid)");
                    foreach ($selectedProjects as $pid) {
                        $insProj->execute([':sid' => $savedId, ':pid' => $pid]);
                    }
                }
            }

            header('Location: manage.php?section=' . urlencode($section) . '&saved=1');
            exit;
        } catch (RuntimeException $exception) {
            foreach ($newImages as $img) {
                if ($img) delete_uploaded_file($img);
            }
            $error = $exception->getMessage();
        }
    }
}

if ($action === 'add' || $action === 'edit') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        foreach ($fields as $name => $field) $record[$name] = $record[$name] ?? ($field['default'] ?? '');
    }
    $active = $section;
    require __DIR__ . '/includes/admin-header.php';
    ?>
    <h1 class="display" style="font-size:1.6rem;margin-bottom:var(--space-3);">
      <?= $action === 'add' ? 'Add ' : 'Edit ' ?><?= e($definition['title']) ?>
    </h1>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="admin-card">
      <?= csrf_field() ?>
      <?php foreach ($fields as $name => $field):
          $type = $field['type'] ?? 'text';
          $value = $_POST[$name] ?? ($record[$name] ?? ($field['default'] ?? ''));
          $label = $field['label'];
          $maxlength = !empty($field['maxlength']) ? ' maxlength="' . (int)$field['maxlength'] . '"' : '';
          $min = isset($field['min']) ? ' min="' . (int)$field['min'] . '"' : '';
          $max = isset($field['max']) ? ' max="' . (int)$field['max'] . '"' : '';
      ?>
        <?php if ($type === 'checkbox'): ?>
          <div class="form-group"><label><input type="checkbox" name="<?= e($name) ?>" <?= $value ? 'checked' : '' ?> style="width:auto;display:inline;margin-right:8px;"> <?= e($label) ?></label></div>
                <?php elseif ($type === 'textarea'): ?>
          <div class="form-group"><label><?= e($label) ?></label><textarea name="<?= e($name) ?>" rows="6"<?= $maxlength ?>><?= e((string)$value) ?></textarea></div>
                <?php elseif ($type === 'select'): ?>
                    <div class="form-group"><label><?= e($label) ?></label><select name="<?= e($name) ?>"<?= !empty($field['required']) ? ' required' : '' ?>><?php foreach ($field['options'] as $option): ?><option value="<?= e($option) ?>" <?= (string)$value === (string)$option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
        <?php elseif ($type === 'image'): ?>
          <div class="form-group"><label><?= e($label) ?></label>
            <?php if ($value): ?>
              <div style="margin-bottom:8px;">
                <span style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">Current image:</span>
                <img src="<?= e(UPLOAD_URL . $value) ?>" alt="" style="max-height:80px;max-width:160px;object-fit:contain;border-radius:4px;display:block;background:rgba(255,255,255,0.05);padding:6px;border:1px solid var(--hairline);">
              </div>
            <?php endif; ?>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="chooseFromMediaLibrary('<?= e($name) ?>', '<?= e(addslashes($label)) ?>')" style="display:inline-flex;align-items:center;gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Choose from Media Library
              </button>
              <span class="badge" id="<?= e($name) ?>_selected_badge" style="display:none;background:rgba(92,225,255,0.15);color:var(--accent);border:1px solid rgba(92,225,255,0.3);padding:4px 8px;border-radius:4px;font-size:0.8rem;"></span>
              <button type="button" class="btn btn-ghost btn-sm" id="<?= e($name) ?>_clear_btn" style="display:none;color:#ff6b6b;font-size:0.8rem;padding:3px 8px;" onclick="clearMediaSelection('<?= e($name) ?>')">Clear</button>
            </div>

            <div id="<?= e($name) ?>_preview_wrap" style="display:none;margin-bottom:8px;align-items:center;gap:10px;">
              <img id="<?= e($name) ?>_preview_img" src="" alt="" style="max-height:80px;max-width:160px;object-fit:contain;border-radius:4px;display:block;background:rgba(255,255,255,0.05);padding:6px;border:1px solid var(--accent);">
            </div>

            <input type="hidden" name="<?= e($name) ?>_existing" id="<?= e($name) ?>_existing" value="">
            <small style="color:var(--text-muted);display:block;margin-top:4px;">Click above to select or upload an image via the Media Library.</small>
          </div>
        <?php else: ?>
          <div class="form-group"><label><?= e($label) ?></label><input type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e((string)$value) ?>"<?= $maxlength . $min . $max ?><?= !empty($field['required']) ? ' required' : '' ?>></div>
        <?php endif; ?>
      <?php endforeach; ?>

      <?php if ($section === 'services'): 
          $existingPackages = service_json($record['packages_json'] ?? '');
          $existingProcess = service_json($record['process_json'] ?? '');
          if (empty($existingProcess) && $action === 'add') {
              $existingProcess = service_default_process();
          }
          $existingFaqs = service_json($record['faqs_json'] ?? '');
          if (empty($existingFaqs) && $action === 'add') {
              $existingFaqs = service_default_faqs();
          }

          $allProjects = $pdo->query("SELECT p.id, p.title, c.name AS category FROM projects p LEFT JOIN project_categories c ON c.id = p.category_id ORDER BY p.sort_order ASC, p.id DESC")->fetchAll();
          $linkedProjectIds = [];
          if ($action === 'edit' && $id) {
              $pStmt = $pdo->prepare("SELECT project_id FROM service_projects WHERE service_id = :sid");
              $pStmt->execute([':sid' => $id]);
              $linkedProjectIds = $pStmt->fetchAll(PDO::FETCH_COLUMN);
          }
      ?>
        <!-- 1. PRICING PACKAGES BUILDER -->
        <div class="form-group" style="margin-top:32px;padding-top:24px;border-top:1px solid var(--hairline);">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:10px;">
            <div>
              <label style="font-size:1.1rem;font-weight:700;margin-bottom:4px;display:block;">Pricing Packages &amp; Plans</label>
              <p style="color:var(--text-muted);font-size:0.86rem;margin:0;line-height:1.5;">Define custom pricing tiers (e.g. Starter, Growth Pro, Enterprise). Clients can pick them directly when booking.</p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addPackageRow()" style="display:inline-flex;align-items:center;gap:6px;">+ Add Package</button>
          </div>

          <div id="packages-container" style="display:flex;flex-direction:column;gap:16px;margin-top:14px;">
            <?php foreach ($existingPackages as $pi => $pkg): 
                $pkgFeatures = is_array($pkg['features'] ?? null) ? implode("\n", $pkg['features']) : ($pkg['features'] ?? '');
            ?>
              <div class="builder-card" style="padding:18px 20px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid var(--hairline);position:relative;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                  <span style="font-weight:700;font-size:0.9rem;color:var(--accent);">Package #<span class="pkg-num"><?= $pi + 1 ?></span></span>
                  <button type="button" class="btn btn-ghost btn-sm" style="color:#ff6b6b;padding:2px 8px;font-size:0.8rem;" onclick="this.closest('.builder-card').remove(); renumberPkgs();">Remove</button>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;margin-bottom:12px;">
                  <div>
                    <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Package Name *</label>
                    <input type="text" name="pricing_packages[<?= $pi ?>][name]" value="<?= e($pkg['name'] ?? '') ?>" placeholder="e.g. Starter Growth" style="margin-top:0;" required>
                  </div>
                  <div>
                    <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Price</label>
                    <input type="text" name="pricing_packages[<?= $pi ?>][price]" value="<?= e($pkg['price'] ?? '') ?>" placeholder="e.g. $450 or NPR 35,000" style="margin-top:0;">
                  </div>
                  <div>
                    <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Billing Period</label>
                    <input type="text" name="pricing_packages[<?= $pi ?>][period]" value="<?= e($pkg['period'] ?? '') ?>" placeholder="e.g. mo, project, month" style="margin-top:0;">
                  </div>
                  <div style="display:flex;align-items:center;padding-top:20px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;cursor:pointer;margin:0;">
                      <input type="checkbox" name="pricing_packages[<?= $pi ?>][featured]" value="1" <?= !empty($pkg['featured']) ? 'checked' : '' ?> style="width:auto;margin:0;">
                      <span style="color:var(--accent);font-weight:600;">Highlight as "Most Popular"</span>
                    </label>
                  </div>
                </div>
                <div style="margin-bottom:12px;">
                  <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Short Summary</label>
                  <input type="text" name="pricing_packages[<?= $pi ?>][summary]" value="<?= e($pkg['summary'] ?? '') ?>" placeholder="e.g. Perfect for creators and ambitious personal brands" style="margin-top:0;">
                </div>
                <div>
                  <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Features &amp; Deliverables (One per line)</label>
                  <textarea name="pricing_packages[<?= $pi ?>][features]" rows="3" placeholder="- 12 High Retention Reels per month&#10;- Strategy &amp; Scripting&#10;- 48h Turnaround" style="margin-top:0;font-size:0.88rem;"><?= e($pkgFeatures) ?></textarea>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- 2. PROCESS ROADMAP BUILDER -->
        <div class="form-group" style="margin-top:32px;padding-top:24px;border-top:1px solid var(--hairline);">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:10px;">
            <div>
              <label style="font-size:1.1rem;font-weight:700;margin-bottom:4px;display:block;">Step-by-Step Roadmap / Process</label>
              <p style="color:var(--text-muted);font-size:0.86rem;margin:0;line-height:1.5;">Define how you execute and deliver this service from discovery to kickoff and delivery.</p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addProcessRow()" style="display:inline-flex;align-items:center;gap:6px;">+ Add Step</button>
          </div>

          <div id="process-container" style="display:flex;flex-direction:column;gap:14px;margin-top:14px;">
            <?php foreach ($existingProcess as $si => $st): ?>
              <div class="builder-card" style="padding:16px 18px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid var(--hairline);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                  <span style="font-weight:700;font-size:0.9rem;color:var(--accent);">Step #<span class="step-num"><?= $si + 1 ?></span></span>
                  <button type="button" class="btn btn-ghost btn-sm" style="color:#ff6b6b;padding:2px 8px;font-size:0.8rem;" onclick="this.closest('.builder-card').remove(); renumberSteps();">Remove</button>
                </div>
                <div style="margin-bottom:10px;">
                  <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Step Title *</label>
                  <input type="text" name="process_steps[<?= $si ?>][title]" value="<?= e($st['title'] ?? '') ?>" placeholder="e.g. Discovery &amp; Strategy" style="margin-top:0;" required>
                </div>
                <div>
                  <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Step Description</label>
                  <textarea name="process_steps[<?= $si ?>][text]" rows="2" placeholder="e.g. We align on your goals, target audience and deliverables before work begins." style="margin-top:0;font-size:0.88rem;"><?= e($st['text'] ?? '') ?></textarea>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- 3. FAQS ACCORDION BUILDER -->
        <div class="form-group" style="margin-top:32px;padding-top:24px;border-top:1px solid var(--hairline);">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:10px;">
            <div>
              <label style="font-size:1.1rem;font-weight:700;margin-bottom:4px;display:block;">Frequently Asked Questions (FAQs)</label>
              <p style="color:var(--text-muted);font-size:0.86rem;margin:0;line-height:1.5;">Add answers to common questions. These generate SEO Schema.org FAQ rich snippets automatically.</p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addFaqRow()" style="display:inline-flex;align-items:center;gap:6px;">+ Add Question</button>
          </div>

          <div id="faqs-container" style="display:flex;flex-direction:column;gap:14px;margin-top:14px;">
            <?php foreach ($existingFaqs as $fi => $fq): ?>
              <div class="builder-card" style="padding:16px 18px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid var(--hairline);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                  <span style="font-weight:700;font-size:0.9rem;color:var(--accent);">FAQ #<span class="faq-num"><?= $fi + 1 ?></span></span>
                  <button type="button" class="btn btn-ghost btn-sm" style="color:#ff6b6b;padding:2px 8px;font-size:0.8rem;" onclick="this.closest('.builder-card').remove(); renumberFaqs();">Remove</button>
                </div>
                <div style="margin-bottom:10px;">
                  <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Question *</label>
                  <input type="text" name="faq_items[<?= $fi ?>][q]" value="<?= e($fq['q'] ?? '') ?>" placeholder="e.g. How long does a typical project take?" style="margin-top:0;" required>
                </div>
                <div>
                  <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Answer *</label>
                  <textarea name="faq_items[<?= $fi ?>][a]" rows="2" placeholder="e.g. Most projects are delivered within 2-3 weeks with milestone reviews." style="margin-top:0;font-size:0.88rem;" required><?= e($fq['a'] ?? '') ?></textarea>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- 4. LINK RELATED CASE STUDIES / PROJECTS -->
        <div class="form-group" style="margin-top:32px;padding-top:24px;border-top:1px solid var(--hairline);">
          <label style="font-size:1.1rem;font-weight:700;margin-bottom:6px;display:block;">Link Related Projects / Proof of Work</label>
          <p style="color:var(--text-muted);font-size:0.86rem;margin:0 0 14px;line-height:1.5;">Check the projects completed under this service to showcase them as proof case studies on the service page.</p>
          <?php if (empty($allProjects)): ?>
            <p style="color:var(--text-muted);font-style:italic;">No projects found yet. You can add projects in the <a href="projects.php" style="color:var(--accent);">Projects</a> section.</p>
          <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:12px;">
              <?php foreach ($allProjects as $proj): 
                  $isLinked = in_array((int)$proj['id'], array_map('intval', $linkedProjectIds), true);
              ?>
                <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid <?= $isLinked ? 'var(--accent)' : 'var(--hairline)' ?>;border-radius:10px;background:rgba(255,255,255,0.03);cursor:pointer;transition:border-color .2s ease;">
                  <input type="checkbox" name="linked_projects[]" value="<?= (int)$proj['id'] ?>" <?= $isLinked ? 'checked' : '' ?> style="width:auto;margin:0;">
                  <div style="min-width:0;">
                    <div style="font-weight:600;font-size:0.92rem;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($proj['title']) ?></div>
                    <?php if (!empty($proj['category'])): ?><div style="font-size:0.75rem;color:var(--accent);"><?= e($proj['category']) ?></div><?php endif; ?>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <script>
        function renumberPkgs() {
          document.querySelectorAll('#packages-container .pkg-num').forEach(function(el, i) { el.textContent = i + 1; });
        }
        function renumberSteps() {
          document.querySelectorAll('#process-container .step-num').forEach(function(el, i) { el.textContent = i + 1; });
        }
        function renumberFaqs() {
          document.querySelectorAll('#faqs-container .faq-num').forEach(function(el, i) { el.textContent = i + 1; });
        }

        function addPackageRow() {
          var container = document.getElementById('packages-container');
          var idx = Date.now();
          var card = document.createElement('div');
          card.className = 'builder-card';
          card.style = 'padding:18px 20px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid var(--hairline);position:relative;';
          card.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
              <span style="font-weight:700;font-size:0.9rem;color:var(--accent);">Package #<span class="pkg-num"></span></span>
              <button type="button" class="btn btn-ghost btn-sm" style="color:#ff6b6b;padding:2px 8px;font-size:0.8rem;" onclick="this.closest('.builder-card').remove(); renumberPkgs();">Remove</button>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:12px;margin-bottom:12px;">
              <div>
                <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Package Name *</label>
                <input type="text" name="pricing_packages[${idx}][name]" placeholder="e.g. Starter Plan" style="margin-top:0;" required>
              </div>
              <div>
                <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Price</label>
                <input type="text" name="pricing_packages[${idx}][price]" placeholder="e.g. $450 or NPR 35,000" style="margin-top:0;">
              </div>
              <div>
                <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Billing Period</label>
                <input type="text" name="pricing_packages[${idx}][period]" placeholder="e.g. mo, project" style="margin-top:0;">
              </div>
              <div style="display:flex;align-items:center;padding-top:20px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;cursor:pointer;margin:0;">
                  <input type="checkbox" name="pricing_packages[${idx}][featured]" value="1" style="width:auto;margin:0;">
                  <span style="color:var(--accent);font-weight:600;">Highlight as "Most Popular"</span>
                </label>
              </div>
            </div>
            <div style="margin-bottom:12px;">
              <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Short Summary</label>
              <input type="text" name="pricing_packages[${idx}][summary]" placeholder="e.g. Perfect for new creators getting started" style="margin-top:0;">
            </div>
            <div>
              <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Features &amp; Deliverables (One per line)</label>
              <textarea name="pricing_packages[${idx}][features]" rows="3" placeholder="- Feature 1&#10;- Feature 2&#10;- Feature 3" style="margin-top:0;font-size:0.88rem;"></textarea>
            </div>
          `;
          container.appendChild(card);
          renumberPkgs();
        }

        function addProcessRow() {
          var container = document.getElementById('process-container');
          var idx = Date.now();
          var card = document.createElement('div');
          card.className = 'builder-card';
          card.style = 'padding:16px 18px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid var(--hairline);';
          card.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
              <span style="font-weight:700;font-size:0.9rem;color:var(--accent);">Step #<span class="step-num"></span></span>
              <button type="button" class="btn btn-ghost btn-sm" style="color:#ff6b6b;padding:2px 8px;font-size:0.8rem;" onclick="this.closest('.builder-card').remove(); renumberSteps();">Remove</button>
            </div>
            <div style="margin-bottom:10px;">
              <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Step Title *</label>
              <input type="text" name="process_steps[${idx}][title]" placeholder="e.g. Discovery &amp; Kickoff" style="margin-top:0;" required>
            </div>
            <div>
              <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Step Description</label>
              <textarea name="process_steps[${idx}][text]" rows="2" placeholder="e.g. We review requirements and set expectations." style="margin-top:0;font-size:0.88rem;"></textarea>
            </div>
          `;
          container.appendChild(card);
          renumberSteps();
        }

        function addFaqRow() {
          var container = document.getElementById('faqs-container');
          var idx = Date.now();
          var card = document.createElement('div');
          card.className = 'builder-card';
          card.style = 'padding:16px 18px;border-radius:12px;background:rgba(255,255,255,0.03);border:1px solid var(--hairline);';
          card.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
              <span style="font-weight:700;font-size:0.9rem;color:var(--accent);">FAQ #<span class="faq-num"></span></span>
              <button type="button" class="btn btn-ghost btn-sm" style="color:#ff6b6b;padding:2px 8px;font-size:0.8rem;" onclick="this.closest('.builder-card').remove(); renumberFaqs();">Remove</button>
            </div>
            <div style="margin-bottom:10px;">
              <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Question *</label>
              <input type="text" name="faq_items[${idx}][q]" placeholder="e.g. What is your payment structure?" style="margin-top:0;" required>
            </div>
            <div>
              <label style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px;">Answer *</label>
              <textarea name="faq_items[${idx}][a]" rows="2" placeholder="e.g. 50% deposit upfront and 50% on final delivery." style="margin-top:0;font-size:0.88rem;" required></textarea>
            </div>
          `;
          container.appendChild(card);
          renumberFaqs();
        }
        </script>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary"><?= $action === 'add' ? 'Save' : 'Update' ?></button>
      <a href="manage.php?section=<?= e($section) ?>" class="btn btn-ghost">Cancel</a>
    </form>
    <?php
    require __DIR__ . '/includes/admin-footer.php';
    exit;
}

$records = $pdo->query("SELECT * FROM {$table} ORDER BY {$definition['order']}")->fetchAll();
$active = $section;
require __DIR__ . '/includes/admin-header.php';
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-3);">
  <h1 class="display" style="font-size:1.6rem;"><?= e($definition['title']) ?></h1>
  <a href="manage.php?section=<?= e($section) ?>&action=add" class="btn btn-primary">+ Add <?= e(rtrim($definition['title'], 's')) ?></a>
</div>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Deleted.</div><?php endif; ?>
<div class="admin-card" style="padding:0;overflow:auto;">
  <table class="admin-table">
    <thead><tr><?php foreach ($definition['columns'] as $column): ?><th><?= e($fields[$column]['label']) ?></th><?php endforeach; ?><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($records as $row): ?>
      <tr>
        <?php foreach ($definition['columns'] as $column): ?>
          <td>
            <?php if (($fields[$column]['type'] ?? '') === 'checkbox'): ?><span class="badge <?= $row[$column] ? 'badge-on' : 'badge-off' ?>"><?= $row[$column] ? 'Yes' : 'No' ?></span>
            <?php elseif (($fields[$column]['type'] ?? '') === 'image'): ?>
              <?php if (!empty($row[$column])): ?>
                <img src="<?= e(UPLOAD_URL . $row[$column]) ?>" alt="" style="max-height:36px;max-width:90px;object-fit:contain;vertical-align:middle;background:rgba(255,255,255,0.06);padding:3px;border-radius:4px;">
              <?php else: ?>
                <span style="color:var(--text-muted);">&mdash;</span>
              <?php endif; ?>
            <?php elseif (($fields[$column]['type'] ?? '') === 'textarea'): ?><?= e(mb_strimwidth((string)$row[$column], 0, 100, '...')) ?>
            <?php else: ?><?= e((string)$row[$column]) ?><?php endif; ?>
          </td>
        <?php endforeach; ?>
        <td>
          <a href="manage.php?section=<?= e($section) ?>&action=edit&id=<?= (int)$row[$primary] ?>" style="color:var(--accent);margin-right:12px;">Edit</a>
          <form method="POST" action="manage.php?section=<?= e($section) ?>&action=delete&id=<?= (int)$row[$primary] ?>" style="display:inline;" onsubmit="return confirm('Delete this item? This cannot be undone.');"><?= csrf_field() ?><button type="submit" style="background:none;border:0;padding:0;color:#ff7442;cursor:pointer;font:inherit;">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$records): ?><tr><td colspan="<?= count($definition['columns']) + 1 ?>" style="color:var(--text-muted);">No items yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
