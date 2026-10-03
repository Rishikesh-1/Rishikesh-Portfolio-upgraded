<?php
require_once __DIR__ . '/../config/config.php';
require_admin_login();

if (isset($_GET['read'])) {
    $stmt = $pdo->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id');
    $stmt->execute([':id' => (int)$_GET['read']]);
    header('Location: messages.php');
    exit;
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();

$active = 'messages';
require __DIR__ . '/includes/admin-header.php';
?>
<h1 class="display" style="font-size:1.6rem;margin-bottom:var(--space-3);">Messages</h1>
<div class="admin-card" style="padding:0;">
  <table class="admin-table">
    <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Received</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($messages as $m): ?>
      <tr>
        <td><?= e($m['name']) ?><br><span style="color:var(--text-muted);font-size:0.82rem;"><?= e($m['email']) ?></span></td>
        <td><?= e($m['subject'] ?: '—') ?></td>
        <td style="max-width:320px;"><?= e(mb_strimwidth($m['message'], 0, 140, '…')) ?></td>
        <td style="color:var(--text-muted);font-size:0.85rem;"><?= e(date('M j, g:i A', strtotime($m['created_at']))) ?></td>
        <td>
          <?php if ($m['is_read']): ?>
            <span class="badge badge-off">Read</span>
          <?php else: ?>
            <a href="?read=<?= (int)$m['id'] ?>" class="badge badge-on" style="text-decoration:none;">Mark read</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$messages): ?><tr><td colspan="5" style="color:var(--text-muted);">No messages yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/includes/admin-footer.php'; ?>
