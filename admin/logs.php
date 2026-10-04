<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

$logFile = APP_ERROR_LOG;
$logDir = APP_LOG_DIR;

// 1. Download action
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    if (file_exists($logFile) && filesize($logFile) > 0) {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="error-log-' . date('Y-m-d') . '.txt"');
        header('Content-Length: ' . filesize($logFile));
        readfile($logFile);
        exit;
    }
    header('Location: logs.php');
    exit;
}

// 2. Handle POST actions (Clear, Test)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'clear') {
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        @file_put_contents($logFile, '');
        header('Location: logs.php?cleared=1');
        exit;
    }

    if ($postAction === 'test') {
        app_log_error('TEST', 'Test error log triggered by administrator to verify error recording.', __FILE__, __LINE__);
        header('Location: logs.php?tested=1');
        exit;
    }
}

// 3. Read and parse logs
$logExists = file_exists($logFile);
$logSize = $logExists ? filesize($logFile) : 0;
$logContent = '';
$parsedEntries = [];

if ($logExists && $logSize > 0) {
    // Avoid reading more than 3MB at once
    $maxBytes = 3 * 1024 * 1024;
    if ($logSize > $maxBytes) {
        $fp = fopen($logFile, 'r');
        fseek($fp, -$maxBytes, SEEK_END);
        $logContent = fread($fp, $maxBytes);
        fclose($fp);
    } else {
        $logContent = file_get_contents($logFile);
    }

    // Split into distinct entries (handles both custom structured entries and native PHP error logs)
    $chunks = preg_split('/(?=(?:=== \[\d{4}-\d{2}-\d{2}|\[\d{2}-[A-Za-z]{3}-\d{4}))/', $logContent);
    foreach ($chunks as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '') {
            continue;
        }

        if (preg_match('/^=== \[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\] ===\s*\n?(.*)$/s', $chunk, $matches)) {
            $timestamp = $matches[1];
            $level = strtoupper(trim($matches[2]));
            $ip = $matches[3];
            $request = $matches[4];
            $body = $matches[5];

            $message = '';
            $location = '';
            $trace = '';

            if (preg_match('/Message:\s*(.*?)(?=\nLocation:|\nTrace:|\z)/s', $body, $mMsg)) {
                $message = trim($mMsg[1]);
            }
            if (preg_match('/Location:\s*(.*?)(?=\nTrace:|\z)/s', $body, $mLoc)) {
                $location = trim($mLoc[1]);
            }
            if (preg_match('/Trace:\s*(.*)/s', $body, $mTrace)) {
                $trace = trim($mTrace[1]);
            }

            $parsedEntries[] = [
                'timestamp' => $timestamp,
                'level'     => $level,
                'ip'        => $ip,
                'request'   => $request,
                'message'   => $message ?: $body,
                'location'  => $location,
                'trace'     => $trace,
                'raw'       => $chunk,
            ];
        } elseif (preg_match('/^\[([^\]]+)\]\s+PHP\s+([A-Za-z\s]+):\s*(.*)$/s', $chunk, $matches)) {
            $timestamp = $matches[1];
            $level = strtoupper(trim($matches[2]));
            $body = trim($matches[3]);
            $location = '';
            if (preg_match('/in (.*) on line (\d+)/', $body, $locMatch)) {
                $location = $locMatch[1] . ':' . $locMatch[2];
            }
            $parsedEntries[] = [
                'timestamp' => $timestamp,
                'level'     => $level,
                'ip'        => '-',
                'request'   => 'PHP Internal',
                'message'   => $body,
                'location'  => $location,
                'trace'     => '',
                'raw'       => $chunk,
            ];
        } else {
            $parsedEntries[] = [
                'timestamp' => '',
                'level'     => 'NOTICE',
                'ip'        => '-',
                'request'   => '-',
                'message'   => $chunk,
                'location'  => '',
                'trace'     => '',
                'raw'       => $chunk,
            ];
        }
    }

    // Newest entries at the top
    $parsedEntries = array_reverse($parsedEntries);
}

// Human readable size helper
function format_bytes(int $bytes): string {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

$active = 'logs';
require __DIR__ . '/includes/admin-header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:var(--space-3);">
  <div>
    <h1 class="display" style="font-size:1.6rem;margin:0;">Error Logs</h1>
    <p style="color:var(--text-muted);font-size:0.88rem;margin-top:4px;">
      Monitor runtime exceptions, PHP fatal errors, and warnings automatically captured from visitors and the website.
    </p>
  </div>
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
    <a href="logs.php" class="btn" style="background:var(--surface);color:var(--text);border:1px solid var(--hairline);padding:8px 14px;font-size:0.85rem;display:inline-flex;align-items:center;gap:6px;">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
      <span>Refresh</span>
    </a>

    <?php if ($logSize > 0): ?>
      <a href="logs.php?action=download" class="btn" style="background:var(--surface);color:var(--text);border:1px solid var(--hairline);padding:8px 14px;font-size:0.85rem;display:inline-flex;align-items:center;gap:6px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        <span>Download log</span>
      </a>
      
      <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to clear all error logs?');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="btn" style="background:#dc2626;color:#ffffff;border:1px solid #dc2626;padding:8px 14px;font-size:0.85rem;font-weight:600;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          <span>Clear logs</span>
        </button>
      </form>
    <?php endif; ?>

    <form method="POST" style="display:inline;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="test">
      <button type="submit" class="btn" style="background:var(--accent);color:var(--ink);border:1px solid var(--accent);padding:8px 16px;font-size:0.85rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;cursor:pointer;box-shadow:0 0 16px rgba(92,225,255,0.25);" title="Simulates a test warning to ensure error logging is working">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        <span>Test error</span>
      </button>
    </form>
  </div>
</div>

<?php if (isset($_GET['cleared'])): ?>
  <div class="alert alert-success">Error log file has been cleared successfully.</div>
<?php endif; ?>

<?php if (isset($_GET['tested'])): ?>
  <div class="alert alert-success">A test entry was logged and recorded below. Logger is active and working properly!</div>
<?php endif; ?>

<!-- System status card -->
<div class="admin-card" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:16px;padding:16px 20px;">
  <div>
    <span style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.04em;">System Status</span>
    <div style="font-weight:600;font-size:1.05rem;margin-top:4px;">
      <?php if ($logSize === 0): ?>
        <span style="color:var(--success);">All Systems Normal</span>
      <?php else: ?>
        <span style="color:#ffb08a;"><?= count($parsedEntries) ?> Event<?= count($parsedEntries) === 1 ? '' : 's' ?> Recorded</span>
      <?php endif; ?>
    </div>
  </div>
  <div>
    <span style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.04em;">Log File Size</span>
    <div style="font-weight:600;font-size:1.05rem;margin-top:4px;"><?= format_bytes($logSize) ?></div>
  </div>
  <div>
    <span style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.04em;">Storage Path</span>
    <div style="font-family:monospace;font-size:0.85rem;margin-top:4px;color:var(--text-muted);" title="<?= e(APP_ERROR_LOG) ?>">
      logs/error.log
    </div>
  </div>
  <div>
    <span style="color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.04em;">Security Protection</span>
    <div style="font-weight:600;font-size:1.05rem;margin-top:4px;color:var(--success);">
      Protected (.htaccess)
    </div>
  </div>
</div>

<?php if (empty($parsedEntries)): ?>
  <div class="admin-card" style="text-align:center;padding:48px 24px;">
    <div style="width:48px;height:48px;border-radius:50%;background:rgba(92,225,255,0.1);color:var(--accent);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h3 style="margin-bottom:8px;font-size:1.2rem;">No errors recorded</h3>
    <p style="color:var(--text-muted);font-size:0.92rem;max-width:480px;margin:0 auto;">
      The application has not encountered any unhandled exceptions or PHP errors. If a visitor or script hits an error in the future, full diagnostic details will show up here.
    </p>
  </div>
<?php else: ?>

  <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:var(--space-4);">
    <?php foreach ($parsedEntries as $idx => $entry): 
      $lvl = $entry['level'];
      $isCrit = in_array($lvl, ['ERROR', 'FATAL', 'EXCEPTION', 'CORE_ERROR', 'COMPILE_ERROR'], true);
      $isWarn = in_array($lvl, ['WARNING', 'USER_WARNING', 'CORE_WARNING'], true);
      $isTest = ($lvl === 'TEST');

      $badgeBg = $isCrit ? 'rgba(239, 68, 68, 0.18)' : ($isWarn ? 'rgba(245, 158, 11, 0.18)' : ($isTest ? 'rgba(92, 225, 255, 0.18)' : 'rgba(255,255,255,0.08)'));
      $badgeColor = $isCrit ? '#f87171' : ($isWarn ? '#fbbf24' : ($isTest ? 'var(--accent)' : 'var(--text-muted)'));
      $borderColor = $isCrit ? 'rgba(239, 68, 68, 0.35)' : 'var(--hairline)';
    ?>
      <div class="admin-card" style="margin-bottom:0;border-left:4px solid <?= $badgeColor ?>;border-color:<?= $borderColor ?>;">
        <div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="display:inline-block;padding:3px 9px;border-radius:4px;font-weight:700;font-size:0.75rem;letter-spacing:0.04em;background:<?= $badgeBg ?>;color:<?= $badgeColor ?>;">
              <?= e($lvl) ?>
            </span>
            <span style="font-size:0.86rem;font-weight:600;color:var(--text);">
              <?= e($entry['timestamp']) ?>
            </span>
            <?php if ($entry['ip'] && $entry['ip'] !== '-'): ?>
              <span style="font-size:0.8rem;color:var(--text-muted);">
                IP: <code style="color:var(--text);font-size:0.78rem;"><?= e($entry['ip']) ?></code>
              </span>
            <?php endif; ?>
          </div>
          <?php if ($entry['request'] && $entry['request'] !== '-'): ?>
            <span style="font-family:monospace;font-size:0.82rem;color:var(--text-muted);background:var(--ink);padding:3px 8px;border-radius:4px;border:1px solid var(--hairline);">
              <?= e($entry['request']) ?>
            </span>
          <?php endif; ?>
        </div>

        <div style="font-size:0.95rem;font-weight:500;color:var(--text);margin-bottom:8px;line-height:1.5;overflow-wrap:break-word;">
          <?= nl2br(e($entry['message'])) ?>
        </div>

        <?php if (!empty($entry['location'])): ?>
          <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:8px;font-family:monospace;word-break:break-all;display:flex;align-items:center;gap:6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;flex-shrink:0;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?= e($entry['location']) ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($entry['trace'])): ?>
          <details style="margin-top:10px;">
            <summary style="font-size:0.82rem;color:var(--accent);cursor:pointer;font-weight:600;">
              View Stack Trace
            </summary>
            <pre style="margin-top:8px;padding:12px;background:var(--ink);border:1px solid var(--hairline);border-radius:6px;font-size:0.78rem;color:#cbd5e1;overflow-x:auto;line-height:1.5;white-space:pre-wrap;"><?= e($entry['trace']) ?></pre>
          </details>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <details class="admin-card" style="margin-top:20px;">
    <summary style="cursor:pointer;font-weight:600;color:var(--text);font-size:0.92rem;">
      View Raw Log File
    </summary>
    <div style="margin-top:14px;">
      <textarea readonly style="width:100%;min-height:220px;background:var(--ink);border:1px solid var(--hairline);border-radius:6px;color:#cbd5e1;font-family:monospace;font-size:0.8rem;padding:12px;line-height:1.5;"><?= e($logContent) ?></textarea>
    </div>
  </details>

<?php endif; ?>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
