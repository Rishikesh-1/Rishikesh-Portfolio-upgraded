<?php
/**
 * media.php — Media Library management page and API endpoint.
 * Browse, reuse, and manage uploaded images across blog posts and projects.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin_login();

// Helper to list all uploaded media items
function get_all_media_items(): array
{
    if (!is_dir(UPLOAD_DIR)) {
        return [];
    }
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
    $files = scandir(UPLOAD_DIR);
    $items = [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || str_starts_with($file, '.')) {
            continue;
        }
        $fullPath = UPLOAD_DIR . $file;
        if (!is_file($fullPath)) {
            continue;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            continue;
        }

        $sizeBytes = (int) filesize($fullPath);
        $mtime = (int) filemtime($fullPath);

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

        $items[] = [
            'filename' => $file,
            'url' => 'uploads/' . $file,
            'full_url' => UPLOAD_URL . $file,
            'admin_preview_url' => '../uploads/' . $file,
            'size' => $sizeBytes,
            'size_formatted' => $sizeFormatted,
            'mtime' => $mtime,
            'date' => date('M j, Y', $mtime),
            'dimensions' => $dimensions,
            'ext' => $ext,
        ];
    }

    usort($items, static fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    return $items;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// --- JSON API Endpoints ---
if ($action === 'list') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'items' => get_all_media_items()]);
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

        $item = [
            'filename' => $filename,
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
        $pageNotice = 'Image deleted from library.';
    }
}

$mediaItems = get_all_media_items();
$totalSize = array_sum(array_column($mediaItems, 'size'));
$totalSizeFormatted = $totalSize < 1024 * 1024
    ? round($totalSize / 1024, 1) . ' KB'
    : round($totalSize / (1024 * 1024), 2) . ' MB';

$active = 'media';
require __DIR__ . '/includes/admin-header.php';
?>

<div style="margin-bottom:24px;">
  <h1 class="display" style="font-size:1.75rem;margin-bottom:6px;">Media Library</h1>
  <p style="color:var(--text-muted);font-size:0.9rem;margin:0;">
    Manage, reuse, and insert uploaded photos into your blog posts and projects.
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
    <input type="search" id="media-search-input" placeholder="Search by image filename..." oninput="filterMediaLibrary(this.value)">
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
      <div class="media-library-card" data-filename="<?= e(strtolower($item['filename'])) ?>" data-ext="<?= e(strtolower($item['ext'])) ?>">
        <div class="media-library-card-thumb">
          <span class="media-format-badge"><?= e(strtoupper($item['ext'])) ?></span>
          <img src="<?= e($item['admin_preview_url']) ?>" alt="<?= e($item['filename']) ?>" loading="lazy">
        </div>
        <div class="media-library-card-body">
          <p class="media-library-card-title" title="<?= e($item['filename']) ?>"><?= e($item['filename']) ?></p>
          <p class="media-library-card-meta">
            <span><?= e($item['size_formatted']) ?></span>
            <?php if ($item['dimensions']): ?>
              <span>&bull;</span>
              <span><?= e($item['dimensions']) ?></span>
            <?php endif; ?>
          </p>
          <div class="media-library-card-actions">
            <button type="button" class="btn-media-copy" onclick="copyMediaUrl('<?= e($item['url']) ?>', this)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
              <span>Copy path</span>
            </button>
            <form method="POST" style="display:inline;margin:0;" onsubmit="return confirm('Permanently delete <?= e($item['filename']) ?>?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_file" value="<?= e($item['filename']) ?>">
              <button type="submit" class="btn-media-delete" title="Delete image">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                <span>Delete</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
let activeFormatFilter = 'all';

function filterMediaLibrary(query) {
  const term = (query || '').trim().toLowerCase();
  const cards = document.querySelectorAll('.media-library-card');
  let visibleCount = 0;
  cards.forEach(card => {
    const filename = card.getAttribute('data-filename') || '';
    const ext = card.getAttribute('data-ext') || '';
    const matchSearch = !term || filename.includes(term);
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

function copyMediaUrl(url, btn) {
  navigator.clipboard.writeText(url).then(() => {
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span style="color:#2ed573;">✓ Copied!</span>';
    setTimeout(() => { btn.innerHTML = origHtml; }, 2000);
  }).catch(() => {
    prompt('Copy image URL:', url);
  });
}

function handleDirectUpload(input) {
  if (input.files && input.files[0]) {
    const indicator = document.getElementById('media-upload-indicator');
    if (indicator) indicator.style.display = 'block';
    document.getElementById('media-standalone-form').submit();
  }
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
