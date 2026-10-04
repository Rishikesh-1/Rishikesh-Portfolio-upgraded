<?php
/**
 * media.php — Media Library management page and API endpoint.
 * Browse, upload, and manage images stored in uploads/ directory.
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

    $uploadFile = $_FILES['media_file'] ?? ($_FILES['image'] ?? null);
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

    $rawFilename = trim($_POST['filename'] ?? '');
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['standalone_upload'])) {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
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

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:var(--space-3);">
  <div>
    <h1 class="display" style="font-size:1.6rem;margin-bottom:4px;">Media Library</h1>
    <p style="color:var(--text-muted);font-size:0.88rem;margin:0;">
      Browse, reuse, and manage uploaded images across your blog posts and projects (<?= count($mediaItems) ?> images &bull; <?= $totalSizeFormatted ?>).
    </p>
  </div>
</div>

<?php if ($pageNotice): ?>
  <div class="alert alert-success" style="margin-bottom:18px;"><?= e($pageNotice) ?></div>
<?php endif; ?>
<?php if ($pageError): ?>
  <div class="alert alert-error" style="margin-bottom:18px;"><?= e($pageError) ?></div>
<?php endif; ?>

<!-- Standalone Upload Card -->
<div class="admin-card" style="margin-bottom:24px;">
  <form method="POST" enctype="multipart/form-data" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
    <?= csrf_field() ?>
    <label style="font-weight:600;font-size:0.9rem;margin:0;">Upload new image:</label>
    <input type="file" name="standalone_upload" accept=".jpg,.jpeg,.png,.webp,.svg,.gif" required style="max-width:320px;">
    <button type="submit" class="btn btn-primary btn-sm">Upload to Library</button>
    <span style="font-size:0.8rem;color:var(--text-muted);">(Max 10MB &bull; JPG, PNG, WEBP, SVG, GIF)</span>
  </form>
</div>

<!-- Search & Filter Bar -->
<div style="margin-bottom:18px;display:flex;align-items:center;gap:12px;">
  <input type="search" id="media-search-input" placeholder="Search images by filename..." style="max-width:360px;" oninput="filterMediaLibrary(this.value)">
  <span id="media-count-badge" style="font-size:0.84rem;color:var(--text-muted);"><?= count($mediaItems) ?> items</span>
</div>

<!-- Media Library Grid -->
<div class="media-library-grid" id="media-library-container">
  <?php if (!$mediaItems): ?>
    <div style="grid-column:1/-1;text-align:center;padding:48px 20px;color:var(--text-muted);border:1px dashed var(--hairline);border-radius:var(--radius);">
      No images uploaded yet. Upload your first picture above!
    </div>
  <?php else: ?>
    <?php foreach ($mediaItems as $item): ?>
      <div class="media-library-card" data-filename="<?= e(strtolower($item['filename'])) ?>">
        <div class="media-library-card-thumb">
          <img src="<?= e($item['admin_preview_url']) ?>" alt="<?= e($item['filename']) ?>" loading="lazy">
        </div>
        <div class="media-library-card-body">
          <p class="media-library-card-title" title="<?= e($item['filename']) ?>"><?= e($item['filename']) ?></p>
          <p class="media-library-card-meta">
            <?= e($item['size_formatted']) ?><?= $item['dimensions'] ? ' &bull; ' . e($item['dimensions']) : '' ?>
          </p>
          <div class="media-library-card-actions">
            <button type="button" class="btn btn-ghost btn-sm" onclick="copyMediaUrl('<?= e($item['url']) ?>', this)">Copy path</button>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this image?');">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_file" value="<?= e($item['filename']) ?>">
              <button type="submit" class="btn btn-ghost btn-sm" style="color:#ff7373;">Delete</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
function filterMediaLibrary(query) {
  const term = query.trim().toLowerCase();
  const cards = document.querySelectorAll('.media-library-card');
  let visibleCount = 0;
  cards.forEach(card => {
    const filename = card.getAttribute('data-filename') || '';
    if (!term || filename.includes(term)) {
      card.style.display = '';
      visibleCount++;
    } else {
      card.style.display = 'none';
    }
  });
  const badge = document.getElementById('media-count-badge');
  if (badge) {
    badge.textContent = visibleCount + ' item' + (visibleCount === 1 ? '' : 's');
  }
}

function copyMediaUrl(url, btn) {
  navigator.clipboard.writeText(url).then(() => {
    const orig = btn.textContent;
    btn.textContent = 'Copied!';
    setTimeout(() => { btn.textContent = orig; }, 2000);
  }).catch(() => {
    prompt('Copy image URL:', url);
  });
}
</script>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
