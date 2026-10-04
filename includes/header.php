<?php
/**
 * header.php
 * Expects optional variables set by the calling page BEFORE include:
 *   $page_title, $page_description, $page_image, $page_url
 */
$page_title       = $page_title       ?? get_setting($pdo, 'site_title', 'Rishikesh Rana');
$page_description = $page_description ?? get_setting($pdo, 'site_description', '');
$page_image       = $page_image       ?? get_setting($pdo, 'og_image', '');
$page_url         = $page_url         ?? (SITE_ROOT_URL . $_SERVER['REQUEST_URI']);
$favicon          = get_setting($pdo, 'site_favicon', '');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_description) ?>">
<link rel="canonical" href="<?= e($page_url) ?>">

<!-- OpenGraph / social sharing -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($page_description) ?>">
<meta property="og:url" content="<?= e($page_url) ?>">
<?php if ($page_image): ?><meta property="og:image" content="<?= e(UPLOAD_URL . $page_image) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($page_title) ?>">
<meta name="twitter:description" content="<?= e($page_description) ?>">

<?php if ($favicon): ?><link rel="icon" href="<?= e(UPLOAD_URL . $favicon) ?>"><?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(SITE_ROOT_URL) ?>/assets/css/style.css?v=20261004-1">
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
</head>
<body class="<?= e($body_class ?? '') ?>" data-theme="dark">
<div class="preloader" id="preloader" aria-label="Loading portfolio" role="status">
	<img src="<?= e(SITE_ROOT_URL) ?>/assets/img/loading.svg" alt="Loading" class="preloader-graphic">
</div>
<script>
	window.addEventListener('load', function () {
		var loader = document.getElementById('preloader');
		if (loader) {
			window.setTimeout(function () {
				loader.classList.add('is-done');
				window.setTimeout(function () { loader.style.display = 'none'; }, 600);
			}, 400);
		}
	});
	window.setTimeout(function () {
		var loader = document.getElementById('preloader');
		if (loader && !loader.classList.contains('is-done')) {
			loader.classList.add('is-done');
			window.setTimeout(function () { loader.style.display = 'none'; }, 600);
		}
	}, 1800);
</script>
<canvas class="particle-field" id="particleField" aria-hidden="true"></canvas>
<div class="ambient-shape ambient-shape-one" aria-hidden="true"></div>
<div class="ambient-shape ambient-shape-two" aria-hidden="true"></div>
<?php include __DIR__ . '/nav.php'; ?>
<main>
