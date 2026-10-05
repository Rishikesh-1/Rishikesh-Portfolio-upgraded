<?php
/**
 * media.php — Media Library management page and API endpoint.
 * Browse, reuse, rename, and manage SEO metadata (alt text, title, captions) across images.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

ensure_media_schema($pdo);

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// --- JSON API Endpoints ---
if ($action === 'list') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'items' => get_all_media_items($pdo)]);
    exit;
}

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security token check failed. Please refresh.']);
        exit;
    }

    $uploadFile = $_FILES['media_file'] ?? ($_FILES['image'] ?? ($_FILES['standalone_upload'] ?? null));
    if (!$uploadFile || !is_array($uploadFile)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No image was selected for upload.']);
        exit;
    }

    try {
        $filename = handle_image_upload($uploadFile, 'Media file');
        if (!$filename) {
            throw new RuntimeException('Failed to process image.');
        }

        $fullPath = UPLOAD_DIR . $filename;
        $sizeBytes = (int) filesize($fullPath);
        $mtime = (int) filemtime($fullPath);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $dimensions = '';
        if ($ext !== 'svg') {
            $info = @getimagesize($fullPath);
            if ($info && !empty($info[0]) && !empty($info[1])) {
                $dimensions = $info[0] . ' × ' . $info[1] . ' px';
            }
        }

        $sizeFormatted = $sizeBytes < 1024
            ? $sizeBytes . ' B'
            : ($sizeBytes < 1024 * 1024
                ? round($sizeBytes / 1024, 1) . ' KB'
                : round($sizeBytes / (1024 * 1024), 2) . ' MB');

        $cleanBaseName = ucwords(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
        update_media_metadata($pdo, $filename, [
            'title' => $cleanBaseName,
            'alt_text' => $cleanBaseName,
            'caption' => '',
            'description' => '',
        ]);

        $item = [
            'filename' => $filename,
            'title' => $cleanBaseName,
            'alt_text' => $cleanBaseName,
            'caption' => '',
            'description' => '',
            'url' => 'uploads/' . $filename,
            'full_url' => UPLOAD_URL . $filename,
            'admin_preview_url' => '../uploads/' . $filename,
            'size' => $sizeBytes,
            'size_formatted' => $sizeFormatted,
            'mtime' => $mtime,
            'date' => date('M j, Y', $mtime),
            'dimensions' => $dimensions,
            'ext' => $ext,
        ];

        echo json_encode(['success' => true, 'item' => $item]);
    } catch (RuntimeException $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } catch (Throwable $e) {
        app_log_error('ERROR', 'Media upload error: ' . $e->getMessage(), $e->getFile(), $e->getLine());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'An unexpected server error occurred while uploading.']);
    }
    exit;
}

if ($action === 'update_meta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security token check failed.']);
        exit;
    }

    $filename = basename(trim($_POST['filename'] ?? ''));
    if ($filename === '' || !is_file(UPLOAD_DIR . $filename)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Image file not found on server.']);
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $altText = trim($_POST['alt_text'] ?? '');
    $caption = trim($_POST['caption'] ?? '');
    $description = trim($_POST['description'] ?? '');

    update_media_metadata($pdo, $filename, [
        'title' => $title,
        'alt_text' => $altText,
        'caption' => $caption,
        'description' => $description,
    ]);

    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'title' => $title,
        'alt_text' => $altText,
        'caption' => $caption,
        'description' => $description,
    ]);
    exit;
}

if ($action === 'rename' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security token check failed.']);
        exit;
    }

    $oldFilename = basename(trim($_POST['old_filename'] ?? ''));
    $newName = trim($_POST['new_name'] ?? '');

    if ($oldFilename === '' || !is_file(UPLOAD_DIR . $oldFilename)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Original file not found.']);
        exit;
    }

    if ($newName === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Please provide a valid new file name.']);
        exit;
    }

    try {
        $result = rename_media_file($pdo, $oldFilename, $newName);
        echo json_encode($result);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security token check failed.']);
        exit;
    }

    $rawFilename = trim($_POST['filename'] ?? ($_POST['delete_file'] ?? ''));
    $filename = basename($rawFilename);
    if ($filename === '' || !is_file(UPLOAD_DIR . $filename)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'File not found.']);
        exit;
    }

    delete_uploaded_file($filename);
    try {
        $stmt = $pdo->prepare('DELETE FROM media_items WHERE filename = :f');
        $stmt->execute([':f' => $filename]);
    } catch (Throwable $e) {}

    echo json_encode(['success' => true, 'filename' => $filename]);
    exit;
}

// --- Standalone Media Library Dashboard Page ---
$pageNotice = '';
$pageError = '';

// Handle standard HTML form upload fallback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['standalone_upload']) && $action !== 'upload') {
    verify_csrf();
    try {
        $uploaded = handle_image_upload($_FILES['standalone_upload'], 'Image');
        if ($uploaded) {
            $cleanBaseName = ucwords(str_replace(['-', '_'], ' ', pathinfo($uploaded, PATHINFO_FILENAME)));
            update_media_metadata($pdo, $uploaded, [
                'title' => $cleanBaseName,
                'alt_text' => $cleanBaseName,
                'caption' => '',
                'description' => '',
            ]);
            $pageNotice = 'Image successfully uploaded to library.';
        }
    } catch (RuntimeException $e) {
        $pageError = $e->getMessage();
    }
}

// Handle standard HTML form delete fallback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file']) && $action !== 'delete') {
    verify_csrf();
    $targetFile = basename(trim($_POST['delete_file']));
    if ($targetFile !== '' && is_file(UPLOAD_DIR . $targetFile)) {
        delete_uploaded_file($targetFile);
        try {
            $stmt = $pdo->prepare('DELETE FROM media_items WHERE filename = :f');
            $stmt->execute([':f' => $targetFile]);
        } catch (Throwable $e) {}
        $pageNotice = 'Image deleted from library.';
    }
}

$mediaItems = get_all_media_items($pdo);
$totalSize = array_sum(array_column($mediaItems, 'size'));
$totalSizeFormatted = $totalSize < 1024 * 1024
    ? round($totalSize / 1024, 1) . ' KB'
    : round($totalSize / (1024 * 1024), 2) . ' MB';

$active = 'media';
require __DIR__ . '/includes/admin-header.php';
?>

<style>
  .media-stats-bar { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 10px; }
  .media-stat-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--hairline, rgba(150, 180, 255, .18)); font-size: 0.82rem; color: var(--text-muted, #aab5d1); }
  .media-upload-zone { border: 2px dashed var(--hairline, rgba(150, 180, 255, .18)); border-radius: 14px; padding: 32px 20px; text-align: center; cursor: pointer; background: rgba(255, 255, 255, 0.02); margin-bottom: 24px; transition: all 0.2s ease; }
  .media-upload-zone:hover, .media-upload-zone.is-dragover { border-color: var(--accent, #5ce1ff); background: rgba(92, 225, 255, 0.05); }
  .media-upload-zone-icon { width: 48px; height: 48px; margin: 0 auto 10px; border-radius: 50%; background: rgba(92, 225, 255, 0.1); color: var(--accent, #5ce1ff); display: flex; align-items: center; justify-content: center; }
  .media-upload-zone-title { font-weight: 600; font-size: 0.98rem; color: var(--text, #EDEEF0); margin-bottom: 4px; }
  .media-upload-zone-subtitle { font-size: 0.82rem; color: var(--text-muted, #aab5d1); }
  .media-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
  .media-search-box { position: relative; flex: 1; min-width: 240px; max-width: 380px; }
  .media-search-box input { width: 100%; padding: 10px 14px 10px 38px; border-radius: 8px; border: 1px solid var(--hairline, rgba(150, 180, 255, .18)); background: rgba(0, 0, 0, 0.25); color: var(--text, #EDEEF0); font-size: 0.88rem; }
  .media-search-box svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--text-muted, #aab5d1); pointer-events: none; }
  .media-filter-chips { display: flex; gap: 8px; flex-wrap: wrap; }
  .media-chip { padding: 6px 14px; border-radius: 999px; border: 1px solid var(--hairline, rgba(150, 180, 255, .18)); background: rgba(255, 255, 255, 0.03); color: var(--text-muted, #aab5d1); font-size: 0.82rem; font-weight: 600; cursor: pointer; }
  .media-chip.active { background: var(--accent, #5ce1ff); color: #070b16; border-color: var(--accent, #5ce1ff); }
  .media-library-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 18px; }
  .media-library-card { background: var(--surface, rgba(15, 22, 42, .66)); border: 1px solid var(--hairline, rgba(150, 180, 255, .18)); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease; cursor: pointer; position: relative; }
  .media-library-card:hover { transform: translateY(-3px); border-color: var(--accent, #5ce1ff); box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35); }
  .media-library-card-thumb { height: 160px; max-height: 160px; background: rgba(0, 0, 0, 0.35); display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 12px; border-bottom: 1px solid var(--hairline, rgba(150, 180, 255, .18)); position: relative; }
  .media-library-card-thumb img { max-width: 100% !important; max-height: 100% !important; height: auto; width: auto; object-fit: contain !important; border-radius: 4px; display: block; }
  .media-format-badge { position: absolute; top: 8px; left: 8px; font-size: 0.68rem; font-weight: 700; padding: 2px 7px; border-radius: 4px; background: rgba(0,0,0,0.8); color: var(--accent, #5ce1ff); border: 1px solid rgba(92, 225, 255, 0.35); text-transform: uppercase; }
  .media-library-card-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 4px; }
  .media-library-card-title { font-size: 0.88rem; font-weight: 600; color: var(--text, #EDEEF0); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .media-library-card-filename { font-size: 0.74rem; color: var(--accent, #5ce1ff); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: monospace; }
  .media-library-card-meta { font-size: 0.76rem; color: var(--text-muted, #aab5d1); margin: 0; display: flex; align-items: center; gap: 6px; }
  .media-alt-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 0.72rem; padding: 2px 6px; border-radius: 4px; background: rgba(46, 213, 115, 0.12); color: #2ed573; border: 1px solid rgba(46, 213, 115, 0.25); max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .media-library-card-actions { display: flex; align-items: center; gap: 8px; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--hairline, rgba(150, 180, 255, .18)); }
  .btn-media-details { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--hairline, rgba(150, 180, 255, .18)); background: rgba(255, 255, 255, 0.04); color: var(--text, #EDEEF0); font-size: 0.78rem; font-weight: 600; cursor: pointer; }
  .btn-media-details:hover { border-color: var(--accent, #5ce1ff); color: var(--accent, #5ce1ff); background: rgba(92, 225, 255, 0.08); }

  /* Slide-over Drawer for WordPress-like Attachment Details */
  .media-drawer-backdrop { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 9998; display: none; }
  .media-drawer { position: fixed; top: 0; right: 0; bottom: 0; width: 100%; max-width: 520px; background: rgba(15, 23, 42, 0.98); border-left: 1px solid var(--hairline, rgba(150, 180, 255, .25)); box-shadow: -10px 0 40px rgba(0, 0, 0, 0.7); z-index: 9999; display: flex; flex-direction: column; overflow: hidden; transform: translateX(100%); transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
  .media-drawer.open { transform: translateX(0); }
  .media-drawer-header { padding: 18px 22px; border-bottom: 1px solid var(--hairline, rgba(150, 180, 255, .18)); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
  .media-drawer-body { flex: 1; min-height: 0; overflow-y: auto; padding: 22px; display: flex; flex-direction: column; gap: 18px; }
  .media-drawer-preview { width: 100%; height: 200px; background: rgba(0, 0, 0, 0.4); border-radius: 10px; border: 1px solid var(--hairline, rgba(150, 180, 255, .18)); display: flex; align-items: center; justify-content: center; padding: 12px; overflow: hidden; position: relative; }
  .media-drawer-preview img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 4px; }
  .media-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: rgba(0, 0, 0, 0.25); border: 1px solid var(--hairline, rgba(150, 180, 255, .15)); border-radius: 8px; padding: 12px 14px; font-size: 0.8rem; }
  .media-info-row { display: flex; flex-direction: column; gap: 2px; }
  .media-info-label { color: var(--text-muted, #aab5d1); font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.04em; }
  .media-info-val { color: var(--text, #EDEEF0); font-weight: 600; word-break: break-all; }
  .media-drawer-footer { padding: 16px 22px; border-top: 1px solid var(--hairline, rgba(150, 180, 255, .18)); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; background: rgba(0, 0, 0, 0.2); }
</style>

<div style="margin-bottom:24px;">
  <h1 class="display" style="font-size:1.75rem;margin-bottom:6px;">Media Library</h1>
  <p style="color:var(--text-muted);font-size:0.9rem;margin:0;">
    Manage, rename, and edit SEO metadata (Alt Text, Title, Captions) across all images.
  </p>
  <div class="media-stats-bar">
    <span class="media-stat-pill">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--accent);"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <strong><?= count($mediaItems) ?></strong> images
    </span>
    <span class="media-stat-pill">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--accent);"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      <strong><?= $totalSizeFormatted ?></strong> used
    </span>
    <span class="media-stat-pill">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--accent);"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Max 10MB per file
    </span>
  </div>
</div>

<?php if ($pageNotice): ?>
  <div class="alert alert-success" style="margin-bottom:20px;"><?= e($pageNotice) ?></div>
<?php endif; ?>
<?php if ($pageError): ?>
  <div class="alert alert-error" style="margin-bottom:20px;"><?= e($pageError) ?></div>
<?php endif; ?>

<!-- Drag & Drop Upload Zone -->
<form method="POST" enctype="multipart/form-data" id="media-standalone-form">
  <?= csrf_field() ?>
  <div class="media-upload-zone" id="media-drop-zone" onclick="document.getElementById('media-file-input').click()">
    <input type="file" name="standalone_upload" id="media-file-input" accept=".jpg,.jpeg,.png,.webp,.svg,.gif" style="display:none;" onchange="handleDirectUpload(this)">
    <div class="media-upload-zone-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
    </div>
    <div class="media-upload-zone-title">Click to upload or drag &amp; drop your image here</div>
    <div class="media-upload-zone-subtitle">Supports JPG, PNG, WEBP, SVG, and GIF &bull; Max 10MB</div>
    <div id="media-upload-indicator" style="display:none;margin-top:12px;font-size:0.88rem;color:var(--accent);font-weight:600;">
      <span class="inline-media-spinner" style="margin-right:6px;"></span> Uploading your image...
    </div>
  </div>
</form>

<!-- Filter & Search Toolbar -->
<div class="media-toolbar">
  <div class="media-search-box">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
    <input type="search" id="media-search-input" placeholder="Search by name, title or alt text..." oninput="filterMediaLibrary(this.value)">
  </div>
  <div class="media-filter-chips">
    <button type="button" class="media-chip active" onclick="filterByFormat('all', this)">All (<?= count($mediaItems) ?>)</button>
    <button type="button" class="media-chip" onclick="filterByFormat('png', this)">PNG</button>
    <button type="button" class="media-chip" onclick="filterByFormat('jpg', this)">JPG</button>
    <button type="button" class="media-chip" onclick="filterByFormat('webp', this)">WEBP</button>
    <button type="button" class="media-chip" onclick="filterByFormat('svg', this)">SVG</button>
  </div>
  <span id="media-count-badge" style="font-size:0.84rem;color:var(--text-muted);font-weight:600;"><?= count($mediaItems) ?> images shown</span>
</div>

<!-- Media Library Grid -->
<div class="media-library-grid" id="media-library-container">
  <?php if (!$mediaItems): ?>
    <div style="grid-column:1/-1;text-align:center;padding:54px 20px;color:var(--text-muted);border:1px dashed var(--hairline);border-radius:var(--radius);">
      No images uploaded yet. Upload your first picture above!
    </div>
  <?php else: ?>
    <?php foreach ($mediaItems as $item): ?>
      <div class="media-library-card" onclick="openMediaDrawer(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>)" data-filename="<?= e(strtolower($item['filename'])) ?>" data-title="<?= e(strtolower($item['title'])) ?>" data-alt="<?= e(strtolower($item['alt_text'])) ?>" data-ext="<?= e(strtolower($item['ext'])) ?>">
        <div class="media-library-card-thumb">
          <span class="media-format-badge"><?= e(strtoupper($item['ext'])) ?></span>
          <img src="<?= e($item['admin_preview_url']) ?>" alt="<?= e($item['alt_text'] ?: $item['title']) ?>" loading="lazy">
        </div>
        <div class="media-library-card-body">
          <p class="media-library-card-title" title="<?= e($item['title']) ?>"><?= e($item['title']) ?></p>
          <p class="media-library-card-filename" title="<?= e($item['filename']) ?>"><?= e($item['filename']) ?></p>
          <?php if (!empty($item['alt_text'])): ?>
            <span class="media-alt-badge" title="Alt: <?= e($item['alt_text']) ?>">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:11px;height:11px;" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
              Alt: <?= e($item['alt_text']) ?>
            </span>
          <?php endif; ?>
          <p class="media-library-card-meta">
            <span><?= e($item['size_formatted']) ?></span>
            <?php if ($item['dimensions']): ?>
              <span>&bull;</span>
              <span><?= e($item['dimensions']) ?></span>
            <?php endif; ?>
          </p>
          <div class="media-library-card-actions" onclick="event.stopPropagation();">
            <button type="button" class="btn-media-details" onclick="openMediaDrawer(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              <span>Edit Details &amp; SEO</span>
            </button>
            <button type="button" class="btn-media-delete" title="Delete image" onclick="deleteMediaFromGrid('<?= e($item['filename']) ?>')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- WordPress-style Attachment Details Drawer -->
<div id="media-drawer-backdrop" class="media-drawer-backdrop" onclick="closeMediaDrawer()"></div>
<div id="media-drawer" class="media-drawer" aria-hidden="true">
  <div class="media-drawer-header">
    <div>
      <h3 style="margin:0;font-size:1.15rem;color:var(--text,#EDEEF0);">Attachment Details</h3>
      <span id="drawer-header-filename" style="font-size:0.75rem;color:var(--accent);font-family:monospace;"></span>
    </div>
    <button type="button" class="btn btn-ghost btn-sm" onclick="closeMediaDrawer()" style="font-size:1.4rem;line-height:1;padding:2px 8px;">&times;</button>
  </div>

  <div class="media-drawer-body">
    <div class="media-drawer-preview">
      <img id="drawer-preview-img" src="" alt="">
    </div>

    <!-- Technical metadata -->
    <div class="media-info-grid">
      <div class="media-info-row">
        <span class="media-info-label">File Name</span>
        <span class="media-info-val" id="drawer-info-filename"></span>
      </div>
      <div class="media-info-row">
        <span class="media-info-label">File Type</span>
        <span class="media-info-val" id="drawer-info-ext"></span>
      </div>
      <div class="media-info-row">
        <span class="media-info-label">Uploaded On</span>
        <span class="media-info-val" id="drawer-info-date"></span>
      </div>
      <div class="media-info-row">
        <span class="media-info-label">File Size</span>
        <span class="media-info-val" id="drawer-info-size"></span>
      </div>
      <div class="media-info-row" style="grid-column: span 2;">
        <span class="media-info-label">Dimensions</span>
        <span class="media-info-val" id="drawer-info-dimensions"></span>
      </div>
    </div>

    <!-- Quick Copy URL -->
    <div class="form-group" style="margin:0;">
      <label style="font-size:0.8rem;margin-bottom:4px;">File URL</label>
      <div style="display:flex;gap:8px;">
        <input type="text" id="drawer-file-url" readonly style="font-size:0.82rem;font-family:monospace;background:rgba(0,0,0,0.3);">
        <button type="button" class="btn btn-secondary btn-sm" id="drawer-copy-btn" onclick="copyDrawerUrl()">Copy</button>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid var(--hairline);margin:0;">

    <!-- SEO & Metadata Edit Form -->
    <div class="form-group" style="margin:0;">
      <label style="font-size:0.82rem;font-weight:700;margin-bottom:4px;">Image Title</label>
      <input type="text" id="drawer-meta-title" placeholder="e.g. Modern UI Dashboard Concept" style="font-size:0.88rem;">
      <small style="color:var(--text-muted);font-size:0.75rem;">Human-readable title used for organizing and library search.</small>
    </div>

    <div class="form-group" style="margin:0;">
      <label style="font-size:0.82rem;font-weight:700;margin-bottom:4px;display:flex;justify-content:space-between;align-items:center;">
        <span>Alternative Text (Alt Text for SEO)</span>
        <span style="font-size:0.72rem;color:var(--accent);font-weight:normal;">Highly recommended for SEO</span>
      </label>
      <input type="text" id="drawer-meta-alt" placeholder="e.g. Screenshot of sleek dark mode analytics dashboard" style="font-size:0.88rem;">
      <small style="color:var(--text-muted);font-size:0.75rem;">Describe the image purpose for search engines and visually impaired visitors.</small>
    </div>

    <div class="form-group" style="margin:0;">
      <label style="font-size:0.82rem;font-weight:700;margin-bottom:4px;">Caption</label>
      <textarea id="drawer-meta-caption" rows="2" placeholder="Visible caption rendered underneath the image..." style="font-size:0.85rem;"></textarea>
    </div>

    <div class="form-group" style="margin:0;">
      <label style="font-size:0.82rem;font-weight:700;margin-bottom:4px;">Description / Notes</label>
      <textarea id="drawer-meta-desc" rows="2" placeholder="Detailed notes, licensing or background info..." style="font-size:0.85rem;"></textarea>
    </div>

    <hr style="border:0;border-top:1px solid var(--hairline);margin:0;">

    <!-- Safe Rename File on Disk Section -->
    <div style="background:rgba(92,225,255,0.04);border:1px solid rgba(92,225,255,0.2);border-radius:8px;padding:14px;">
      <label style="font-size:0.82rem;font-weight:700;color:var(--accent);margin-bottom:6px;display:block;">
        Rename Image File (Safe Rename)
      </label>
      <p style="font-size:0.76rem;color:var(--text-muted);margin-bottom:10px;line-height:1.4;">
        Renaming changes the physical file name on disk. All existing blog posts, portfolio projects, services, and site settings referencing this image will be <strong>automatically updated</strong> without breaking any links.
      </p>
      <div style="display:flex;gap:8px;">
        <input type="text" id="drawer-rename-input" placeholder="new-seo-friendly-name" style="font-size:0.84rem;font-family:monospace;">
        <button type="button" class="btn btn-secondary btn-sm" id="drawer-rename-btn" onclick="renameDrawerFile()" style="white-space:nowrap;">Rename File</button>
      </div>
      <div id="drawer-rename-status" style="display:none;font-size:0.78rem;margin-top:6px;"></div>
    </div>
  </div>

  <div class="media-drawer-footer">
    <button type="button" class="btn btn-ghost btn-sm" style="color:#ff6b6b;" onclick="deleteDrawerFile()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;display:inline-block;vertical-align:-1px;margin-right:4px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
      Delete Permanently
    </button>
    <div style="display:flex;align-items:center;gap:10px;">
      <span id="drawer-save-status" style="font-size:0.8rem;color:#2ed573;display:none;">Saved!</span>
      <button type="button" class="btn btn-primary btn-sm" id="drawer-save-btn" onclick="saveDrawerMetadata()">Save Details</button>
    </div>
  </div>
</div>

<script>
let currentDrawerItem = null;
let activeFormatFilter = 'all';

function filterMediaLibrary(query) {
  const term = (query || '').trim().toLowerCase();
  const cards = document.querySelectorAll('.media-library-card');
  let visibleCount = 0;
  cards.forEach(card => {
    const filename = card.getAttribute('data-filename') || '';
    const title = card.getAttribute('data-title') || '';
    const alt = card.getAttribute('data-alt') || '';
    const ext = card.getAttribute('data-ext') || '';

    const matchSearch = !term || filename.includes(term) || title.includes(term) || alt.includes(term);
    let matchFormat = true;
    if (activeFormatFilter !== 'all') {
      if (activeFormatFilter === 'jpg') {
        matchFormat = (ext === 'jpg' || ext === 'jpeg');
      } else {
        matchFormat = (ext === activeFormatFilter);
      }
    }

    if (matchSearch && matchFormat) {
      card.style.display = '';
      visibleCount++;
    } else {
      card.style.display = 'none';
    }
  });

  const badge = document.getElementById('media-count-badge');
  if (badge) {
    badge.textContent = visibleCount + ' image' + (visibleCount === 1 ? '' : 's') + ' shown';
  }
}

function filterByFormat(format, btn) {
  activeFormatFilter = format;
  document.querySelectorAll('.media-chip').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  const searchInput = document.getElementById('media-search-input');
  filterMediaLibrary(searchInput ? searchInput.value : '');
}

function handleDirectUpload(input) {
  if (input.files && input.files[0]) {
    const indicator = document.getElementById('media-upload-indicator');
    if (indicator) indicator.style.display = 'block';
    document.getElementById('media-standalone-form').submit();
  }
}

// Drawer Controller
function openMediaDrawer(item) {
  currentDrawerItem = item;
  const drawer = document.getElementById('media-drawer');
  const backdrop = document.getElementById('media-drawer-backdrop');

  document.getElementById('drawer-header-filename').textContent = item.filename;
  document.getElementById('drawer-preview-img').src = item.admin_preview_url;
  document.getElementById('drawer-info-filename').textContent = item.filename;
  document.getElementById('drawer-info-ext').textContent = (item.ext || '').toUpperCase();
  document.getElementById('drawer-info-date').textContent = item.date || '-';
  document.getElementById('drawer-info-size').textContent = item.size_formatted || '-';
  document.getElementById('drawer-info-dimensions').textContent = item.dimensions || 'Vector graphic / Scalable';
  document.getElementById('drawer-file-url').value = item.url;

  document.getElementById('drawer-meta-title').value = item.title || '';
  document.getElementById('drawer-meta-alt').value = item.alt_text || '';
  document.getElementById('drawer-meta-caption').value = item.caption || '';
  document.getElementById('drawer-meta-desc').value = item.description || '';

  const baseNameNoExt = item.filename.substring(0, item.filename.lastIndexOf('.')) || item.filename;
  document.getElementById('drawer-rename-input').value = baseNameNoExt;
  document.getElementById('drawer-rename-status').style.display = 'none';
  document.getElementById('drawer-save-status').style.display = 'none';

  backdrop.style.display = 'block';
  drawer.classList.add('open');
  drawer.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

function closeMediaDrawer() {
  const drawer = document.getElementById('media-drawer');
  const backdrop = document.getElementById('media-drawer-backdrop');
  drawer.classList.remove('open');
  drawer.setAttribute('aria-hidden', 'true');
  backdrop.style.display = 'none';
  document.body.style.overflow = '';
  currentDrawerItem = null;
}

window.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && document.getElementById('media-drawer').classList.contains('open')) {
    closeMediaDrawer();
  }
});

function copyDrawerUrl() {
  const urlInput = document.getElementById('drawer-file-url');
  const btn = document.getElementById('drawer-copy-btn');
  navigator.clipboard.writeText(urlInput.value).then(() => {
    btn.textContent = 'Copied!';
    setTimeout(() => { btn.textContent = 'Copy'; }, 2000);
  });
}

function escapeHtml(str) {
  return (str || '').replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  })[m]);
}

function showMediaToast(msg) {
  let toast = document.getElementById('media-floating-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'media-floating-toast';
    toast.style = 'position:fixed;bottom:28px;right:28px;z-index:99999;background:rgba(15,23,42,0.95);border:1px solid #2ed573;color:#2ed573;padding:12px 20px;border-radius:10px;font-size:0.9rem;font-weight:600;box-shadow:0 10px 30px rgba(0,0,0,0.6);display:flex;align-items:center;gap:8px;transform:translateY(20px);opacity:0;transition:all 0.25s cubic-bezier(0.16,1,0.3,1);backdrop-filter:blur(10px);';
    document.body.appendChild(toast);
  }
  toast.innerHTML = msg;
  toast.style.transform = 'translateY(0)';
  toast.style.opacity = '1';
  setTimeout(() => {
    toast.style.transform = 'translateY(20px)';
    toast.style.opacity = '0';
  }, 2800);
}

function saveDrawerMetadata() {
  if (!currentDrawerItem) return;
  const btn = document.getElementById('drawer-save-btn');
  btn.disabled = true;
  btn.textContent = 'Saving...';

  const csrfToken = document.querySelector('input[name="csrf_token"]').value;
  const payload = new FormData();
  payload.append('action', 'update_meta');
  payload.append('csrf_token', csrfToken);
  payload.append('filename', currentDrawerItem.filename);
  payload.append('title', document.getElementById('drawer-meta-title').value);
  payload.append('alt_text', document.getElementById('drawer-meta-alt').value);
  payload.append('caption', document.getElementById('drawer-meta-caption').value);
  payload.append('description', document.getElementById('drawer-meta-desc').value);

  fetch('media.php', { method: 'POST', body: payload })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.textContent = 'Save Details';
      if (data && data.success) {
        const savedFilename = currentDrawerItem.filename;

        // Update card attributes and DOM in the grid
        const card = document.querySelector(`.media-library-card[data-filename="${savedFilename.toLowerCase()}"]`);
        if (card) {
          card.setAttribute('data-title', (data.title || '').toLowerCase());
          card.setAttribute('data-alt', (data.alt_text || '').toLowerCase());
          const titleEl = card.querySelector('.media-library-card-title');
          if (titleEl) titleEl.textContent = data.title;

          let altBadge = card.querySelector('.media-alt-badge');
          if (data.alt_text) {
            if (!altBadge) {
              altBadge = document.createElement('span');
              altBadge.className = 'media-alt-badge';
              const bodyEl = card.querySelector('.media-library-card-body');
              const metaEl = card.querySelector('.media-library-card-meta');
              bodyEl.insertBefore(altBadge, metaEl);
            }
            altBadge.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:11px;height:11px;" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg> Alt: ${escapeHtml(data.alt_text)}`;
            altBadge.title = 'Alt: ' + data.alt_text;
          } else if (altBadge) {
            altBadge.remove();
          }
        }

        // Close drawer and display success toast notification
        closeMediaDrawer();
        showMediaToast('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:16px;height:16px;" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg> Saved successfully!');
      } else {
        alert((data && data.error) ? data.error : 'Failed to save changes.');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.textContent = 'Save Details';
      alert('Network error while saving: ' + err.message);
    });
}

function renameDrawerFile() {
  if (!currentDrawerItem) return;
  const newName = (document.getElementById('drawer-rename-input').value || '').trim();
  if (!newName) {
    alert('Please enter a new name.');
    return;
  }

  const btn = document.getElementById('drawer-rename-btn');
  const statusEl = document.getElementById('drawer-rename-status');
  btn.disabled = true;
  btn.textContent = 'Renaming...';
  statusEl.style.display = 'none';

  const csrfToken = document.querySelector('input[name="csrf_token"]').value;
  const payload = new FormData();
  payload.append('action', 'rename');
  payload.append('csrf_token', csrfToken);
  payload.append('old_filename', currentDrawerItem.filename);
  payload.append('new_name', newName);

  fetch('media.php', { method: 'POST', body: payload })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.textContent = 'Rename File';
      if (data && data.success) {
        statusEl.style.display = 'block';
        statusEl.style.color = '#2ed573';
        statusEl.textContent = '✓ Renamed to ' + data.new_filename + ' & all website links updated!';

        // Reload page shortly to refresh all grid cards smoothly
        setTimeout(() => {
          window.location.reload();
        }, 800);
      } else {
        statusEl.style.display = 'block';
        statusEl.style.color = '#ff6b6b';
        statusEl.textContent = 'Error: ' + ((data && data.error) ? data.error : 'Rename failed.');
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.textContent = 'Rename File';
      statusEl.style.display = 'block';
      statusEl.style.color = '#ff6b6b';
      statusEl.textContent = 'Error: ' + err.message;
    });
}

function deleteDrawerFile() {
  if (!currentDrawerItem) return;
  if (!confirm(`Are you sure you want to permanently delete "${currentDrawerItem.filename}"?`)) return;

  const csrfToken = document.querySelector('input[name="csrf_token"]').value;
  const payload = new FormData();
  payload.append('action', 'delete');
  payload.append('csrf_token', csrfToken);
  payload.append('filename', currentDrawerItem.filename);

  fetch('media.php', { method: 'POST', body: payload })
    .then(r => r.json())
    .then(data => {
      if (data && data.success) {
        closeMediaDrawer();
        window.location.reload();
      } else {
        alert((data && data.error) ? data.error : 'Could not delete file.');
      }
    })
    .catch(err => {
      alert('Error deleting: ' + err.message);
    });
}

function deleteMediaFromGrid(filename) {
  if (!confirm(`Permanently delete "${filename}"?`)) return;
  const csrfToken = document.querySelector('input[name="csrf_token"]').value;
  const payload = new FormData();
  payload.append('action', 'delete');
  payload.append('csrf_token', csrfToken);
  payload.append('filename', filename);

  fetch('media.php', { method: 'POST', body: payload })
    .then(r => r.json())
    .then(data => {
      if (data && data.success) {
        window.location.reload();
      } else {
        alert((data && data.error) ? data.error : 'Could not delete file.');
      }
    })
    .catch(err => {
      alert('Error: ' + err.message);
    });
}

// Drag & drop on the upload zone
(function() {
  const dropZone = document.getElementById('media-drop-zone');
  const fileInput = document.getElementById('media-file-input');
  if (!dropZone || !fileInput) return;

  ['dragenter', 'dragover'].forEach(evt => {
    dropZone.addEventListener(evt, e => {
      e.preventDefault();
      e.stopPropagation();
      dropZone.classList.add('is-dragover');
    });
  });

  ['dragleave', 'drop'].forEach(evt => {
    dropZone.addEventListener(evt, e => {
      e.preventDefault();
      e.stopPropagation();
      dropZone.classList.remove('is-dragover');
    });
  });

  dropZone.addEventListener('drop', e => {
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
      fileInput.files = e.dataTransfer.files;
      handleDirectUpload(fileInput);
    }
  });
})();
</script>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
