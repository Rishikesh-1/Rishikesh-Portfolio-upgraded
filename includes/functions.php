<?php
/**
 * functions.php — shared helpers used across the whole site.
 */

/** Escape any dynamic value before it touches HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Allow a small set of formatting tags in admin-authored project descriptions. */
function sanitize_rich_text(string $content): string
{
    $content = trim($content);
    if ($content === '') {
        return '';
    }
    if (!preg_match('/<\/?[a-z][^>]*>/i', $content) || !class_exists(DOMDocument::class)) {
        return nl2br(e($content));
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $previousErrors = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8"><div id="rich-content">' . $content . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);

    $root = null;
    foreach ($document->getElementsByTagName('div') as $element) {
        if ($element->getAttribute('id') === 'rich-content') {
            $root = $element;
            break;
        }
    }
    if (!$loaded || !$root) {
        return nl2br(e($content));
    }

    $allowedTags = ['h1', 'h2', 'h3', 'p', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'blockquote', 'br', 'hr', 'a'];
    $discardContents = ['script', 'style', 'iframe', 'object', 'svg', 'math'];
    $sanitizeChildren = static function (DOMNode $parent) use (&$sanitizeChildren, $allowedTags, $discardContents): void {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMText) {
                if ((!str_contains($child->data, "\n") && !str_contains($child->data, "\r")) || trim($child->data) === '') {
                    continue;
                }

                $lines = preg_split('/\r\n|\r|\n/', $child->data) ?: [];
                foreach ($lines as $index => $line) {
                    if ($index > 0) {
                        $parent->insertBefore($parent->ownerDocument->createElement('br'), $child);
                    }
                    if ($line !== '') {
                        $parent->insertBefore($parent->ownerDocument->createTextNode($line), $child);
                    }
                }
                $parent->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (!in_array($tag, $allowedTags, true)) {
                if (in_array($tag, $discardContents, true)) {
                    $parent->removeChild($child);
                    continue;
                }
                $sanitizeChildren($child);
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);
                continue;
            }

            $linkHref = '';
            $externalLink = false;
            if ($tag === 'a') {
                $linkHref = trim($child->getAttribute('href'));
                $parts = parse_url($linkHref);
                $scheme = is_array($parts) ? strtolower($parts['scheme'] ?? '') : '';
                $externalLink = in_array($scheme, ['http', 'https'], true)
                    && filter_var($linkHref, FILTER_VALIDATE_URL) !== false;
                $relativeLink = $scheme === ''
                    && $linkHref !== ''
                    && !str_starts_with($linkHref, '//')
                    && !str_contains($linkHref, '\\')
                    && !preg_match('/[\x00-\x20]/', $linkHref);

                if (!$externalLink && !$relativeLink) {
                    $sanitizeChildren($child);
                    while ($child->firstChild) {
                        $parent->insertBefore($child->firstChild, $child);
                    }
                    $parent->removeChild($child);
                    continue;
                }
            }

            while ($child->attributes->length > 0) {
                $child->removeAttributeNode($child->attributes->item(0));
            }
            if ($tag === 'a') {
                $child->setAttribute('href', $linkHref);
                if ($externalLink) {
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            }
            $sanitizeChildren($child);
        }
    };
    $sanitizeChildren($root);

    $safeHtml = '';
    foreach ($root->childNodes as $child) {
        $safeHtml .= $document->saveHTML($child);
    }
    return $safeHtml;
}

/** Limit a sanitized rich-text teaser without breaking its allowed markup. */
function limit_rich_text_words(string $content, int $wordLimit = 20): string
{
    if ($wordLimit < 1) {
        return '';
    }

    $safeHtml = sanitize_rich_text($content);
    if (!class_exists(DOMDocument::class)) {
        $words = preg_split('/\s+/u', trim(strip_tags($content)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $truncated = count($words) > $wordLimit;
        return e(implode(' ', array_slice($words, 0, $wordLimit))) . ($truncated ? '...' : '');
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $previousErrors = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8"><div id="rich-teaser">' . $safeHtml . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);

    $root = null;
    foreach ($document->getElementsByTagName('div') as $element) {
        if ($element->getAttribute('id') === 'rich-teaser') {
            $root = $element;
            break;
        }
    }
    if (!$loaded || !$root) {
        $words = preg_split('/\s+/u', trim(strip_tags($content)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $truncated = count($words) > $wordLimit;
        return e(implode(' ', array_slice($words, 0, $wordLimit))) . ($truncated ? '...' : '');
    }

    $remaining = $wordLimit;
    $truncated = false;
    $limitChildren = static function (DOMNode $parent) use (&$limitChildren, &$remaining, &$truncated, $document): void {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($truncated) {
                $parent->removeChild($child);
                continue;
            }

            if ($child instanceof DOMText) {
                preg_match_all('/\S+/u', $child->data, $matches, PREG_OFFSET_CAPTURE);
                $words = $matches[0] ?? [];
                if (count($words) <= $remaining) {
                    $remaining -= count($words);
                    continue;
                }

                if ($remaining > 0) {
                    $cutAt = $words[$remaining][1];
                    $child->data = rtrim(substr($child->data, 0, $cutAt)) . '...';
                } else {
                    $parent->insertBefore($document->createTextNode('...'), $child);
                    $parent->removeChild($child);
                }
                $remaining = 0;
                $truncated = true;
                continue;
            }

            if ($child instanceof DOMElement) {
                $limitChildren($child);
            }
        }
    };
    $limitChildren($root);

    $limitedHtml = '';
    foreach ($root->childNodes as $child) {
        $limitedHtml .= $document->saveHTML($child);
    }
    return $limitedHtml;
}

/** Count visible words in rich text using the same text-node rules as the teaser limiter. */
function rich_text_word_count(string $content): int
{
    $safeHtml = sanitize_rich_text($content);
    if (!class_exists(DOMDocument::class)) {
        $plainText = html_entity_decode(strip_tags($safeHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        preg_match_all('/\S+/u', trim($plainText), $matches);
        return count($matches[0] ?? []);
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    $previousErrors = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8"><div id="rich-count">' . $safeHtml . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);

    $root = null;
    foreach ($document->getElementsByTagName('div') as $element) {
        if ($element->getAttribute('id') === 'rich-count') {
            $root = $element;
            break;
        }
    }
    if (!$loaded || !$root) {
        $plainText = html_entity_decode(strip_tags($safeHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        preg_match_all('/\S+/u', trim($plainText), $matches);
        return count($matches[0] ?? []);
    }

    $wordCount = 0;
    $countChildren = static function (DOMNode $parent) use (&$countChildren, &$wordCount): void {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMText) {
                preg_match_all('/\S+/u', $child->data, $matches);
                $wordCount += count($matches[0] ?? []);
            } elseif ($child instanceof DOMElement) {
                $countChildren($child);
            }
        }
    };
    $countChildren($root);
    return $wordCount;
}

/** Turn a string into a URL-safe slug. */
function make_slug(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/** Return a safe embeddable URL for supported video hosts, or null. */
function social_embed_url(string $url, string $platform): ?string
{
    $parts = parse_url(trim($url));
    $host = strtolower($parts['host'] ?? '');
    $path = trim($parts['path'] ?? '', '/');
    $query = [];
    parse_str($parts['query'] ?? '', $query);

    if ($platform === 'YouTube' && in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'], true)) {
        $videoId = '';
        if ($host === 'youtu.be') $videoId = explode('/', $path)[0] ?? '';
        if (!$videoId && str_starts_with($path, 'watch')) $videoId = $query['v'] ?? '';
        if (!$videoId && str_starts_with($path, 'shorts/')) $videoId = explode('/', substr($path, 7))[0] ?? '';
        if (!$videoId && str_starts_with($path, 'embed/')) $videoId = explode('/', substr($path, 6))[0] ?? '';
        return preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) ? 'https://www.youtube-nocookie.com/embed/' . $videoId : null;
    }

    if ($platform === 'Vimeo' && in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
        $videoId = preg_match('/(?:video\/)?(\d+)/', $path, $match) ? $match[1] : '';
        return $videoId !== '' ? 'https://player.vimeo.com/video/' . $videoId : null;
    }

    return null;
}

/** Derive a public thumbnail for providers that expose one without an API. */
function social_thumbnail_url(string $url, string $platform): ?string
{
    $embed = social_embed_url($url, $platform);
    if ($platform === 'YouTube' && $embed && preg_match('~/embed/([A-Za-z0-9_-]{11})$~', $embed, $match)) {
        return 'https://img.youtube.com/vi/' . $match[1] . '/hqdefault.jpg';
    }
    return null;
}

/** Render a small safe platform icon without trusting stored HTML. */
function social_icon(string $platform): string
{
    $key = strtolower(trim($platform));
    $paths = [
        'youtube' => '<path d="M21.6 7.2a2.7 2.7 0 0 0-1.9-1.9C18 4.8 12 4.8 12 4.8s-6 0-7.7.5a2.7 2.7 0 0 0-1.9 1.9A28 28 0 0 0 1.9 12a28 28 0 0 0 .5 4.8 2.7 2.7 0 0 0 1.9 1.9c1.7.5 7.7.5 7.7.5s6 0 7.7-.5a2.7 2.7 0 0 0 1.9-1.9 28 28 0 0 0 .5-4.8 28 28 0 0 0-.5-4.8Z"/><path d="m10 15.5 5-3.5-5-3.5v7Z" fill="currentColor" stroke="none"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none"/>',
        'tiktok' => '<path d="M14 4v10.2a3.8 3.8 0 1 1-3-3.7"/><path d="M14 4c.7 2.5 2.1 4 4.5 4"/>',
        'linkedin' => '<path d="M5 8v11M5 5.2v.1M10 19v-6a3 3 0 0 1 6 0v6M10 10v9"/>',
        'facebook' => '<path d="M14 21v-8h2.7l.4-3H14V8.1c0-.9.3-1.6 1.7-1.6h1.8V3.8c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V10H8v3h2.8v8"/>',
        'vimeo' => '<path d="M4 8.5c1.5-1.8 3-2.6 4.4-2.4 1.8.2 1.6 2.4 2.2 4.5.6 2.1 1 3.2 1.4 3.2.3 0 1.2-1 2.5-3 1.3-2 1.9-3.1 1.8-3.4-.1-.5-.8-.5-2 .1l.8-1.5c1.8-.8 3.2-1.1 4.1-.5 1 .6.9 1.9-.1 4-2.8 5.7-5.1 8.6-7 8.6-1.3 0-2.4-1.2-3.2-3.7l-1.7-6.2C6.5 7 6 6.8 4.9 7.6L4 8.5Z"/>',
    ];
    $path = $paths[$key] ?? '<circle cx="12" cy="12" r="8"/><path d="M4 12h16M12 4c2 2.2 3 4.9 3 8s-1 5.8-3 8c-2-2.2-3-4.9-3-8s1-5.8 3-8Z"/>';
    return '<svg class="social-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

// ------------------------------------------------------------
// CSRF protection
// ------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Security check failed. Please go back and try the form again.');
    }
}

// ------------------------------------------------------------
// Admin auth guards
// ------------------------------------------------------------
function is_admin_logged_in(): bool
{
    if (empty($_SESSION['admin_id'])) {
        return false;
    }
    // Idle timeout
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > ADMIN_SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function require_admin_login(): void
{
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// ------------------------------------------------------------
// Secure image upload
// Returns the new random filename on success, or throws Exception.
// ------------------------------------------------------------
function handle_image_upload(array $file, string $fieldNameForError = 'image'): ?string
{
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // optional field, nothing uploaded
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'The file exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'The file exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server temporary upload directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'A server extension stopped the upload.',
        ];
        $reason = $uploadErrors[$file['error']] ?? 'Unknown upload error.';
        throw new RuntimeException("{$fieldNameForError}: {$reason}");
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('File is too large. Max size is ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . 'MB.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTS, true)) {
        throw new RuntimeException('Invalid file extension.');
    }

    if ($ext === 'svg') {
        $content = file_get_contents($file['tmp_name']);
        if ($content === false || stripos($content, '<svg') === false) {
            throw new RuntimeException('Uploaded file is not a valid SVG file.');
        }
        $disallowed = [
            '<\s*script',
            'javascript\s*:',
            'data\s*:\s*text\/html',
            'onload\s*=',
            'onerror\s*=',
            'onclick\s*=',
            'onmouseover\s*=',
            '<\s*foreignobject',
            '<\s*iframe',
            '<\s*embed',
            '<!ENTITY'
        ];
        foreach ($disallowed as $pattern) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                throw new RuntimeException('Security check failed: SVG contains unauthorized scripts or tags.');
            }
        }
    } else {
        // Verify MIME type for raster images
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($file['tmp_name']);
        } else {
            $imageInfo = @getimagesize($file['tmp_name']);
            $mime = $imageInfo['mime'] ?? '';
        }
        if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
            throw new RuntimeException('Invalid file type. Only JPG, PNG, WEBP, and SVG are allowed.');
        }

        // Extra safety: verify it decodes as a real image (blocks polyglot files).
        if (@getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('File is not a valid image.');
        }
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Random filename — never trust or reuse the original name.
    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = UPLOAD_DIR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save uploaded file.');
    }
    chmod($destination, 0644);

    return $newName;
}

/**
 * Handle secure upload of site preloader file (SVG, GIF, PNG, WEBP).
 * Specifically sanitizes SVG files to ensure no malicious scripts or event handlers exist.
 */
function handle_loader_upload(array $file): ?string
{
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'The file exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'The file exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server temporary upload directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'A server extension stopped the upload.',
        ];
        throw new RuntimeException('Loader: ' . ($uploadErrors[$file['error']] ?? 'Upload error.'));
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Loader file is too large. Max size is ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . 'MB.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['svg', 'gif', 'png', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        throw new RuntimeException('Invalid loader format. Allowed formats: SVG, GIF, PNG, WEBP.');
    }

    if ($ext === 'svg') {
        $content = file_get_contents($file['tmp_name']);
        if ($content === false || stripos($content, '<svg') === false) {
            throw new RuntimeException('Uploaded file is not a valid SVG file.');
        }
        // Disallow dangerous script tags, handlers, and external entities
        $disallowed = [
            '<\s*script',
            'javascript\s*:',
            'data\s*:\s*text\/html',
            'onload\s*=',
            'onerror\s*=',
            'onclick\s*=',
            'onmouseover\s*=',
            '<\s*foreignobject',
            '<\s*iframe',
            '<\s*embed',
            '<!ENTITY'
        ];
        foreach ($disallowed as $pattern) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                throw new RuntimeException('Security check failed: SVG contains unauthorized scripts or tags.');
            }
        }
    } else {
        if (@getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('The uploaded file is not a valid image.');
        }
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $newName = 'loader_' . bin2hex(random_bytes(12)) . '.' . $ext;
    $destination = UPLOAD_DIR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save uploaded loader file.');
    }
    chmod($destination, 0644);

    return $newName;
}

/** Delete a previously uploaded file safely (used when replacing/removing images). */
function delete_uploaded_file(?string $filename): void
{
    if (!$filename) {
        return;
    }
    $path = UPLOAD_DIR . basename($filename); // basename() blocks path traversal
    if (is_file($path)) {
        @unlink($path);
    }
}

// ------------------------------------------------------------
// Settings helper (reads the key/value site_settings table)
// ------------------------------------------------------------
function get_setting(PDO $pdo, string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM site_settings');
        foreach ($stmt->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}
