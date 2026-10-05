<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$about = $pdo->query('SELECT * FROM about_content WHERE id = 1')->fetch();
$settingsRows = $pdo->query('SELECT * FROM site_settings')->fetchAll();
$settings = [];
foreach ($settingsRows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $fullName  = trim($_POST['full_name'] ?? '');
        $roleTitle = trim($_POST['role_title'] ?? '');
        $tagline   = trim($_POST['tagline'] ?? '');
        $bio       = trim($_POST['bio_text'] ?? '');
        $currentFavicon = $settings['site_favicon'] ?? '';
        $newFavicon = handle_image_upload($_FILES['site_favicon'] ?? [], 'site favicon');
        if (!$newFavicon && !empty($_POST['site_favicon_existing'])) {
            $cand = basename(trim($_POST['site_favicon_existing']));
            if (is_file(UPLOAD_DIR . $cand)) $newFavicon = $cand;
        }

        $currentLogo = $settings['site_logo_image'] ?? '';
        $newLogo = handle_image_upload($_FILES['site_logo_image'] ?? [], 'site logo');
        if (!$newLogo && !empty($_POST['site_logo_image_existing'])) {
            $cand = basename(trim($_POST['site_logo_image_existing']));
            if (is_file(UPLOAD_DIR . $cand)) $newLogo = $cand;
        }

        $currentLoader = $settings['site_loader_file'] ?? '';
        $newLoader = handle_loader_upload($_FILES['site_loader_file'] ?? []);
        if (!$newLoader && !empty($_POST['site_loader_file_existing'])) {
            $cand = basename(trim($_POST['site_loader_file_existing']));
            if (is_file(UPLOAD_DIR . $cand)) $newLoader = $cand;
        }

        $profileImage = $about['profile_image'];
        $newProfile = handle_image_upload($_FILES['profile_image'] ?? [], 'profile photo');
        if (!$newProfile && !empty($_POST['profile_image_existing'])) {
            $cand = basename(trim($_POST['profile_image_existing']));
            if (is_file(UPLOAD_DIR . $cand)) $newProfile = $cand;
        }
        if ($newProfile) { $profileImage = $newProfile; }

        $resumeFile = $about['resume_file'];
        if (!empty($_FILES['resume_file']['name'])) {
            // Résumé is a PDF, handled separately from the image validator.
            $f = $_FILES['resume_file'];
            if ($f['error'] === UPLOAD_ERR_OK) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($f['tmp_name']);
                if ($mime === 'application/pdf' && $f['size'] <= MAX_UPLOAD_BYTES * 3) {
                    $newName = bin2hex(random_bytes(16)) . '.pdf';
                    move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $newName);
                    delete_uploaded_file($resumeFile);
                    $resumeFile = $newName;
                } else {
                    throw new RuntimeException('Résumé must be a PDF under ' . (MAX_UPLOAD_BYTES * 3 / 1024 / 1024) . 'MB.');
                }
            }
        }

        $stmt = $pdo->prepare(
            'UPDATE about_content SET full_name=:n, role_title=:r, tagline=:t, bio_text=:b, profile_image=:p, resume_file=:rf WHERE id = 1'
        );
        $stmt->execute([':n' => $fullName, ':r' => $roleTitle, ':t' => $tagline, ':b' => $bio, ':p' => $profileImage, ':rf' => $resumeFile]);

        $settingsToSave = ['hero_focus_1', 'hero_focus_2', 'hero_focus_3', 'site_logo_text', 'resume_button_text', 'site_title', 'site_description', 'contact_email', 'contact_notification_email', 'contact_phone', 'location', 'google_analytics_id', 'clients_heading', 'clients_subheading'];
        $upd = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2');
        foreach ($settingsToSave as $key) {
            $val = trim($_POST[$key] ?? '');
            $upd->execute([':k' => $key, ':v' => $val, ':v2' => $val]);
        }
        $showSlider = !empty($_POST['show_clients_slider']) ? '1' : '0';
        $upd->execute([':k' => 'show_clients_slider', ':v' => $showSlider, ':v2' => $showSlider]);
        if (!empty($_POST['remove_site_favicon'])) {
          $upd->execute([':k' => 'site_favicon', ':v' => '', ':v2' => '']);
        } elseif ($newFavicon) {
          $upd->execute([':k' => 'site_favicon', ':v' => $newFavicon, ':v2' => $newFavicon]);
        }

        if (!empty($_POST['remove_site_logo_image'])) {
          $upd->execute([':k' => 'site_logo_image', ':v' => '', ':v2' => '']);
        } elseif ($newLogo) {
          $upd->execute([':k' => 'site_logo_image', ':v' => $newLogo, ':v2' => $newLogo]);
        }

        if (!empty($_POST['remove_site_loader_file'])) {
          $upd->execute([':k' => 'site_loader_file', ':v' => '', ':v2' => '']);
        } elseif ($newLoader) {
          $upd->execute([':k' => 'site_loader_file', ':v' => $newLoader, ':v2' => $newLoader]);
        }

        if ($profileImage) {
            update_media_metadata($pdo, $profileImage, [
                'title' => ($fullName ?: 'Profile') . ' Photo',
                'alt_text' => ($fullName ?: 'Profile') . ($roleTitle ? ' - ' . $roleTitle : ' Photo'),
                'description' => $tagline ?: $bio,
            ]);
        }
        $savedLogo = $newLogo ?: ($settings['site_logo_image'] ?? '');
        if ($savedLogo && empty($_POST['remove_site_logo_image'])) {
            update_media_metadata($pdo, $savedLogo, [
                'title' => ($fullName ?: 'Site') . ' Logo',
                'alt_text' => ($fullName ?: 'Site') . ' Logo',
            ]);
        }

        $success = true;
        $about = $pdo->query('SELECT * FROM about_content WHERE id = 1')->fetch();
        $settingsRows = $pdo->query('SELECT * FROM site_settings')->fetchAll();
        $settings = [];
        foreach ($settingsRows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$active = 'settings';
require __DIR__ . '/includes/admin-header.php';
?>
<h1 class="display" style="font-size:1.6rem;margin-bottom:var(--space-3);">Site settings</h1>
<?php if ($success): ?><div class="alert alert-success">Saved.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="admin-card">
  <?= csrf_field() ?>
  <h3 style="margin-bottom:16px;">Hero / About</h3>
  <div class="form-row">
    <div class="form-group"><label>Full name</label><input type="text" name="full_name" value="<?= e($about['full_name']) ?>" required></div>
    <div class="form-group"><label>Role / title</label><input type="text" name="role_title" value="<?= e($about['role_title']) ?>" required></div>
  </div>
  <div class="form-group"><label>Tagline (one confident sentence)</label><input type="text" name="tagline" value="<?= e($about['tagline']) ?>"></div>
  <h4 style="margin:var(--space-3) 0 12px;">Hero focus rotation</h4>
  <div class="form-row">
    <div class="form-group"><label>Focus phrase 1</label><input type="text" name="hero_focus_1" value="<?= e($settings['hero_focus_1'] ?? ($settings['hero_focus'] ?? 'creative direction')) ?>" maxlength="120"></div>
    <div class="form-group"><label>Focus phrase 2</label><input type="text" name="hero_focus_2" value="<?= e($settings['hero_focus_2'] ?? 'digital marketing') ?>" maxlength="120"></div>
    <div class="form-group"><label>Focus phrase 3</label><input type="text" name="hero_focus_3" value="<?= e($settings['hero_focus_3'] ?? 'web experiences') ?>" maxlength="120"></div>
  </div>
  <h4 style="margin:var(--space-3) 0 12px;">Navigation logo</h4>
  <div class="form-group"><label>Brand text</label><input type="text" name="site_logo_text" value="<?= e($settings['site_logo_text'] ?? 'Rishikesh Rana') ?>" maxlength="100" required></div>
  <div class="form-group"><label>Bio</label><textarea name="bio_text" rows="5"><?= e($about['bio_text']) ?></textarea></div>
  <div class="form-row">
    <div class="form-group">
      <label>Profile photo</label>
      <?php if ($about['profile_image']): ?>
        <div style="margin-bottom:8px;">
          <span style="font-size:0.78rem;color:var(--text-muted);display:block;margin-bottom:4px;">Current photo:</span>
          <img src="<?= e(UPLOAD_URL . $about['profile_image']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:4px;border:1px solid var(--hairline);">
        </div>
      <?php endif; ?>

      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="chooseFromMediaLibrary('profile_image', 'Profile photo')" style="display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          Choose from Media Library
        </button>
        <span class="badge" id="profile_image_selected_badge" style="display:none;background:rgba(92,225,255,0.15);color:var(--accent);border:1px solid rgba(92,225,255,0.3);padding:4px 8px;border-radius:4px;font-size:0.8rem;"></span>
        <button type="button" class="btn btn-ghost btn-sm" id="profile_image_clear_btn" style="display:none;color:#ff6b6b;font-size:0.8rem;padding:3px 8px;" onclick="clearMediaSelection('profile_image')">Clear</button>
      </div>

      <div id="profile_image_preview_wrap" style="display:none;margin-bottom:8px;align-items:center;gap:10px;">
        <img id="profile_image_preview_img" src="" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:4px;border:1px solid var(--accent);">
      </div>

      <input type="hidden" name="profile_image_existing" id="profile_image_existing" value="">
      <small style="color:var(--text-muted);display:block;margin-top:4px;">Click above to select or upload a profile photo via the Media Library.</small>
    </div>
    <div class="form-group">
      <label>Résumé (PDF)</label>
      <?php if ($about['resume_file']): ?><p style="font-size:0.85rem;margin-bottom:8px;"><a href="<?= e(UPLOAD_URL . $about['resume_file']) ?>" style="color:var(--accent);">Current résumé &rarr;</a></p><?php endif; ?>
      <input type="file" name="resume_file" accept=".pdf">
      <div style="margin-top:8px;">
        <label style="font-size:0.82rem;color:var(--text-muted);">Button label on homepage</label>
        <input type="text" name="resume_button_text" value="<?= e($settings['resume_button_text'] ?? 'View CV') ?>" placeholder="View CV" maxlength="60" style="margin-top:4px;">
        <small style="color:var(--text-muted);font-size:0.75rem;display:block;margin-top:4px;">Opens interactive CV viewer modal with direct download and fullscreen buttons.</small>
      </div>
    </div>
    <div class="form-group">
      <label>Browser tab icon</label>
      <?php if (!empty($settings['site_favicon'])): ?>
        <p style="display:flex;align-items:center;gap:8px;font-size:0.85rem;margin-bottom:8px;"><img src="<?= e(UPLOAD_URL . $settings['site_favicon']) ?>" alt="Current browser tab icon" style="width:32px;height:32px;object-fit:contain;"><span>Current icon</span></p>
        <label style="font-size:0.85rem;margin-bottom:8px;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="remove_site_favicon" value="1" style="width:auto;display:inline;"> Remove current icon</label>
      <?php endif; ?>

      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="chooseFromMediaLibrary('site_favicon', 'Browser Tab Icon')" style="display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          Choose from Media Library
        </button>
        <span class="badge" id="site_favicon_selected_badge" style="display:none;background:rgba(92,225,255,0.15);color:var(--accent);border:1px solid rgba(92,225,255,0.3);padding:4px 8px;border-radius:4px;font-size:0.8rem;"></span>
        <button type="button" class="btn btn-ghost btn-sm" id="site_favicon_clear_btn" style="display:none;color:#ff6b6b;font-size:0.8rem;padding:3px 8px;" onclick="clearMediaSelection('site_favicon')">Clear</button>
      </div>

      <div id="site_favicon_preview_wrap" style="display:none;margin-bottom:8px;align-items:center;gap:10px;">
        <img id="site_favicon_preview_img" src="" alt="" style="width:32px;height:32px;object-fit:contain;border:1px solid var(--accent);border-radius:4px;padding:2px;">
      </div>

      <input type="hidden" name="site_favicon_existing" id="site_favicon_existing" value="">
      <small style="color:var(--text-muted);display:block;margin-top:4px;">Select from library or upload. A square PNG or ICO is recommended.</small>
    </div>
    <div class="form-group">
      <label>Logo image (optional)</label>
      <?php if (!empty($settings['site_logo_image'])): ?>
        <p style="margin-bottom:8px;"><img src="<?= e(UPLOAD_URL . $settings['site_logo_image']) ?>" alt="Current navigation logo" style="width:120px;height:40px;object-fit:contain;"></p>
        <label style="font-size:0.85rem;margin-bottom:8px;display:flex;align-items:center;gap:6px;"><input type="checkbox" name="remove_site_logo_image" value="1" style="width:auto;display:inline;"> Remove current logo image (use brand text instead)</label>
      <?php endif; ?>

      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="chooseFromMediaLibrary('site_logo_image', 'Navigation Logo')" style="display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          Choose from Media Library
        </button>
        <span class="badge" id="site_logo_image_selected_badge" style="display:none;background:rgba(92,225,255,0.15);color:var(--accent);border:1px solid rgba(92,225,255,0.3);padding:4px 8px;border-radius:4px;font-size:0.8rem;"></span>
        <button type="button" class="btn btn-ghost btn-sm" id="site_logo_image_clear_btn" style="display:none;color:#ff6b6b;font-size:0.8rem;padding:3px 8px;" onclick="clearMediaSelection('site_logo_image')">Clear</button>
      </div>

      <div id="site_logo_image_preview_wrap" style="display:none;margin-bottom:8px;align-items:center;gap:10px;">
        <img id="site_logo_image_preview_img" src="" alt="" style="max-height:50px;max-width:160px;object-fit:contain;border:1px solid var(--accent);border-radius:4px;padding:4px;background:rgba(255,255,255,0.05);">
      </div>

      <input type="hidden" name="site_logo_image_existing" id="site_logo_image_existing" value="">
      <small style="color:var(--text-muted);display:block;margin-top:4px;">Transparent PNG, SVG, or WebP recommended.</small>
    </div>
  </div>

  <h3 style="margin:var(--space-4) 0 16px;">Page loading animation</h3>
  <div class="form-row">
    <div class="form-group" style="grid-column: span 2;">
      <label>Preloader graphic (SVG, GIF, PNG, WebP)</label>
      <?php 
      $customLoader = $settings['site_loader_file'] ?? '';
      $hasCustomLoader = !empty($customLoader) && file_exists(UPLOAD_DIR . $customLoader);
      $currentLoaderUrl = $hasCustomLoader 
          ? (UPLOAD_URL . $customLoader) 
          : (SITE_ROOT_URL . '/assets/img/loading.svg');
      ?>
      <div style="display:flex;align-items:center;gap:18px;margin-bottom:12px;padding:14px 18px;background:var(--ink);border:1px solid var(--hairline);border-radius:var(--radius);max-width:540px;">
        <div style="width:72px;height:72px;display:grid;place-items:center;background:rgba(255,255,255,0.04);border:1px solid var(--hairline);border-radius:8px;padding:8px;overflow:hidden;flex-shrink:0;">
          <img src="<?= e($currentLoaderUrl) ?>" alt="Current loading animation" style="max-width:100%;max-height:100%;object-fit:contain;">
        </div>
        <div>
          <span style="font-weight:600;font-size:0.92rem;color:var(--text);display:block;">
            <?= $hasCustomLoader ? 'Custom loader active' : 'Default animated loader active' ?>
          </span>
          <span style="font-size:0.78rem;color:var(--text-muted);display:block;margin-top:2px;">
            <?= $hasCustomLoader ? e($customLoader) : 'assets/img/loading.svg' ?>
          </span>
          <?php if ($hasCustomLoader): ?>
            <label style="font-size:0.82rem;margin-top:8px;display:inline-flex;align-items:center;gap:6px;color:#ff8d8d;cursor:pointer;">
              <input type="checkbox" name="remove_site_loader_file" value="1" style="width:auto;display:inline;"> Revert to default loading animation
            </label>
          <?php endif; ?>
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="chooseFromMediaLibrary('site_loader_file', 'Preloader Graphic')" style="display:inline-flex;align-items:center;gap:6px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          Choose from Media Library
        </button>
        <span class="badge" id="site_loader_file_selected_badge" style="display:none;background:rgba(92,225,255,0.15);color:var(--accent);border:1px solid rgba(92,225,255,0.3);padding:4px 8px;border-radius:4px;font-size:0.8rem;"></span>
        <button type="button" class="btn btn-ghost btn-sm" id="site_loader_file_clear_btn" style="display:none;color:#ff6b6b;font-size:0.8rem;padding:3px 8px;" onclick="clearMediaSelection('site_loader_file')">Clear</button>
      </div>

      <div id="site_loader_file_preview_wrap" style="display:none;margin-bottom:8px;align-items:center;gap:10px;">
        <img id="site_loader_file_preview_img" src="" alt="" style="max-height:60px;max-width:60px;object-fit:contain;border:1px solid var(--accent);border-radius:4px;padding:4px;background:rgba(255,255,255,0.05);">
      </div>

      <input type="hidden" name="site_loader_file_existing" id="site_loader_file_existing" value="">
      <small style="color:var(--text-muted);display:block;margin-top:4px;">Select or upload an SVG or GIF loader from the Media Library to replace the website's loading screen.</small>
    </div>
  </div>

  <h3 style="margin:var(--space-4) 0 16px;">Clients &amp; Brands ticker</h3>
  <div class="form-group">
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
      <input type="checkbox" name="show_clients_slider" value="1" <?= ($settings['show_clients_slider'] ?? '1') === '1' ? 'checked' : '' ?> style="width:auto;display:inline;">
      <span>Enable client &amp; partner logo slider on website</span>
    </label>
    <small>Unchecking this will temporarily hide the logo slider across the entire site without deleting your uploaded logos.</small>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label>Slider headline (displayed directly above the logo slider)</label>
      <input type="text" name="clients_heading" value="<?= e($settings['clients_heading'] ?? 'Companies & Brands I\'ve Worked With') ?>" maxlength="150" placeholder="e.g. Companies & Brands I've Worked With">
    </div>
    <div class="form-group">
      <label>Slider subtitle (optional)</label>
      <input type="text" name="clients_subheading" value="<?= e($settings['clients_subheading'] ?? '') ?>" maxlength="200" placeholder="e.g. Selected collaborations, sponsor campaigns, and client work">
    </div>
  </div>
  <p style="font-size:0.85rem;color:var(--text-muted);margin-top:-8px;margin-bottom:20px;">
    To upload or manage client logos, go to <a href="manage.php?section=clients" style="color:var(--accent);">Clients &amp; Brands</a> in the sidebar.
  </p>

  <h3 style="margin:var(--space-4) 0 16px;">Global SEO &amp; contact</h3>
  <div class="form-group"><label>Default site title</label><input type="text" name="site_title" value="<?= e($settings['site_title'] ?? '') ?>"></div>
  <div class="form-group"><label>Default site description</label><input type="text" name="site_description" value="<?= e($settings['site_description'] ?? '') ?>"></div>
  <div class="form-row">
    <div class="form-group"><label>Contact email</label><input type="email" name="contact_email" value="<?= e($settings['contact_email'] ?? '') ?>"></div>
    <div class="form-group"><label>Contact notification email</label><input type="email" name="contact_notification_email" value="<?= e($settings['contact_notification_email'] ?? ($settings['contact_email'] ?? '')) ?>"><small>Form submissions are emailed here.</small></div>
  </div>
  <div class="form-group"><label>Contact phone</label><input type="text" name="contact_phone" value="<?= e($settings['contact_phone'] ?? '') ?>"></div>
  <div class="form-group"><label>Location</label><input type="text" name="location" value="<?= e($settings['location'] ?? '') ?>"></div>
  <div class="form-group"><label>Google Analytics ID (optional)</label><input type="text" name="google_analytics_id" value="<?= e($settings['google_analytics_id'] ?? '') ?>"></div>

  <button type="submit" class="btn btn-primary">Save settings</button>
</form>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
