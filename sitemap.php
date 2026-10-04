<?php
require_once __DIR__ . '/config/config.php';
header('Content-Type: application/xml; charset=utf-8');

$urls = [
    ['loc' => SITE_ROOT_URL . '/', 'priority' => '1.0'],
    ['loc' => SITE_ROOT_URL . '/page.php?view=about', 'priority' => '0.8'],
    ['loc' => SITE_ROOT_URL . '/page.php?view=work', 'priority' => '0.9'],
    ['loc' => SITE_ROOT_URL . '/page.php?view=services', 'priority' => '0.8'],
    ['loc' => SITE_ROOT_URL . '/page.php?view=experience', 'priority' => '0.7'],
    ['loc' => SITE_ROOT_URL . '/page.php?view=skills', 'priority' => '0.7'],
    ['loc' => SITE_ROOT_URL . '/page.php?view=testimonials', 'priority' => '0.7'],
    ['loc' => SITE_ROOT_URL . '/page.php?view=contact', 'priority' => '0.8'],
    ['loc' => SITE_ROOT_URL . '/blog.php', 'priority' => '0.8'],
];

$posts = $pdo->query('SELECT slug, updated_at FROM blog_posts WHERE is_published = 1')->fetchAll();
foreach ($posts as $p) {
    $urls[] = ['loc' => SITE_ROOT_URL . '/post.php?slug=' . urlencode($p['slug']), 'lastmod' => date('Y-m-d', strtotime($p['updated_at'])), 'priority' => '0.6'];
}

$servicesSitemap = $pdo->query("SELECT slug FROM services WHERE is_visible = 1 AND slug IS NOT NULL AND slug != ''")->fetchAll();
foreach ($servicesSitemap as $s) {
    $urls[] = ['loc' => SITE_ROOT_URL . '/service.php?slug=' . urlencode($s['slug']), 'priority' => '0.85'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
    if (!empty($u['lastmod'])) echo '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
    echo '    <priority>' . $u['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>';
