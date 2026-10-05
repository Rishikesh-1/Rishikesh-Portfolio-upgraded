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

    $allowedTags = ['h1', 'h2', 'h3', 'p', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'blockquote', 'br', 'hr', 'a', 'img', 'figure', 'figcaption'];
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
            $imgSrc = '';
            $imgAlt = '';
            $imgTitle = '';
            $imgClass = '';

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
            } elseif ($tag === 'img') {
                $rawSrc = trim($child->getAttribute('src'));
                $imgAlt = trim($child->getAttribute('alt'));
                $imgTitle = trim($child->getAttribute('title'));
                $rawClass = trim($child->getAttribute('class'));
                if (preg_match('/^[a-zA-Z0-9_\-\s]+$/', $rawClass)) {
                    $imgClass = $rawClass;
                }

                $parts = parse_url($rawSrc);
                $scheme = is_array($parts) ? strtolower($parts['scheme'] ?? '') : '';
                $isHttp = in_array($scheme, ['http', 'https'], true)
                    && filter_var($rawSrc, FILTER_VALIDATE_URL) !== false;
                $isRelative = $scheme === ''
                    && $rawSrc !== ''
                    && !str_starts_with($rawSrc, '//')
                    && !str_contains($rawSrc, '\\')
                    && !str_contains($rawSrc, '..')
                    && !preg_match('/[\x00-\x20]/', $rawSrc);

                if ($isRelative) {
                    $cleanRelative = ltrim($rawSrc, '/');
                    if (str_starts_with($cleanRelative, 'uploads/') || str_starts_with($cleanRelative, 'assets/')) {
                        $imgSrc = defined('SITE_ROOT_URL') ? (rtrim(SITE_ROOT_URL, '/') . '/' . $cleanRelative) : $rawSrc;
                    }
                } elseif ($isHttp) {
                    $imgSrc = $rawSrc;
                }

                if ($imgSrc === '') {
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
            } elseif ($tag === 'img') {
                $child->setAttribute('src', $imgSrc);
                if ($imgAlt !== '') {
                    $child->setAttribute('alt', $imgAlt);
                }
                if ($imgTitle !== '') {
                    $child->setAttribute('title', $imgTitle);
                }
                if ($imgClass !== '') {
                    $child->setAttribute('class', $imgClass);
                }
                $child->setAttribute('loading', 'lazy');
                $child->setAttribute('decoding', 'async');
            }

            if ($tag === 'img') {
                while ($child->firstChild) {
                    $child->removeChild($child->firstChild);
                }
            } else {
                $sanitizeChildren($child);
            }
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

            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['img', 'figure', 'figcaption'], true)) {
                $parent->removeChild($child);
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
        'whatsapp' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
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
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
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

    $allowedExts = defined('ALLOWED_IMAGE_EXTS') && is_array(ALLOWED_IMAGE_EXTS)
        ? array_unique(array_merge(ALLOWED_IMAGE_EXTS, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif']))
        : ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
    $allowedTypes = defined('ALLOWED_IMAGE_TYPES') && is_array(ALLOWED_IMAGE_TYPES)
        ? array_unique(array_merge(ALLOWED_IMAGE_TYPES, ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/svg', 'image/gif', 'image/pjpeg', 'image/x-png', 'image/jpg']))
        : ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/svg', 'image/gif'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        throw new RuntimeException('Invalid file extension. Allowed formats: JPG, PNG, WEBP, SVG, GIF.');
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
        if (!in_array($mime, $allowedTypes, true)) {
            throw new RuntimeException('Invalid file type. Only JPG, PNG, WEBP, SVG, and GIF are allowed.');
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
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
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

// ------------------------------------------------------------
// Clients ticker table — idempotent migration for databases created before the feature
// ------------------------------------------------------------
function ensure_clients_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    if (get_setting($pdo, 'clients_schema_version') === '1') {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS clients (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            logo_image VARCHAR(255) NOT NULL,
            website_url VARCHAR(255) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_visible TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $stmt = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = :v2'
    );
    $stmt->execute([':k' => 'clients_schema_version', ':v' => '1', ':v2' => '1']);
}

// ------------------------------------------------------------
// Media library metadata & rename management
// ------------------------------------------------------------
function ensure_media_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS media_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL UNIQUE,
                title VARCHAR(255) DEFAULT NULL,
                alt_text VARCHAR(255) DEFAULT NULL,
                caption TEXT DEFAULT NULL,
                description TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    } catch (Throwable $e) {
        // Table or index might already exist or permission restricted
    }
}

function get_all_media_items(?PDO $pdo = null): array
{
    if ($pdo === null) {
        global $pdo;
    }

    $metaMap = [];
    if ($pdo instanceof PDO) {
        try {
            ensure_media_schema($pdo);
            $stmt = $pdo->query('SELECT filename, title, alt_text, caption, description FROM media_items');
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $metaMap[$row['filename']] = $row;
            }
        } catch (Throwable $e) {
            // Proceed if database table is unavailable
        }
    }

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

        $meta = $metaMap[$file] ?? null;
        $cleanBaseName = ucwords(str_replace(['-', '_'], ' ', pathinfo($file, PATHINFO_FILENAME)));
        $title = ($meta && !empty($meta['title'])) ? $meta['title'] : $cleanBaseName;
        $altText = ($meta && isset($meta['alt_text'])) ? $meta['alt_text'] : '';
        $caption = ($meta && isset($meta['caption'])) ? $meta['caption'] : '';
        $description = ($meta && isset($meta['description'])) ? $meta['description'] : '';

        $items[] = [
            'filename' => $file,
            'title' => $title,
            'alt_text' => $altText,
            'caption' => $caption,
            'description' => $description,
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

function update_media_metadata(PDO $pdo, string $filename, array $data): bool
{
    ensure_media_schema($pdo);
    $filename = basename(trim($filename));
    if ($filename === '') {
        return false;
    }

    $title = isset($data['title']) ? trim((string)$data['title']) : null;
    $altText = isset($data['alt_text']) ? trim((string)$data['alt_text']) : null;
    $caption = isset($data['caption']) ? trim((string)$data['caption']) : null;
    $description = isset($data['description']) ? trim((string)$data['description']) : null;

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO media_items (filename, title, alt_text, caption, description)
             VALUES (:filename, :title, :alt_text, :caption, :description)
             ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                alt_text = VALUES(alt_text),
                caption = VALUES(caption),
                description = VALUES(description),
                updated_at = NOW()'
        );

        return $stmt->execute([
            ':filename' => $filename,
            ':title' => $title,
            ':alt_text' => $altText,
            ':caption' => $caption,
            ':description' => $description,
        ]);
    } catch (Throwable $e) {
        app_log_error('ERROR', 'update_media_metadata failed: ' . $e->getMessage(), $e->getFile(), $e->getLine());
        return false;
    }
}

function rename_media_file(PDO $pdo, string $oldFilename, string $newDesiredName): array
{
    ensure_media_schema($pdo);
    $oldFilename = basename(trim($oldFilename));
    if ($oldFilename === '' || !is_file(UPLOAD_DIR . $oldFilename)) {
        throw new RuntimeException('Original image file not found on server.');
    }

    $oldExt = strtolower(pathinfo($oldFilename, PATHINFO_EXTENSION));
    $rawBase = pathinfo($newDesiredName, PATHINFO_FILENAME);
    $slugBase = strtolower(trim(preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $rawBase), '-'));
    if ($slugBase === '') {
        $slugBase = 'image-' . time();
    }

    $finalNewFilename = $slugBase . '.' . $oldExt;

    if ($finalNewFilename === $oldFilename) {
        return [
            'success' => true,
            'old_filename' => $oldFilename,
            'new_filename' => $oldFilename,
            'url' => 'uploads/' . $oldFilename,
            'full_url' => UPLOAD_URL . $oldFilename,
            'admin_preview_url' => '../uploads/' . $oldFilename,
        ];
    }

    // Ensure unique target filename if needed
    $counter = 1;
    while (is_file(UPLOAD_DIR . $finalNewFilename) && $finalNewFilename !== $oldFilename) {
        $finalNewFilename = $slugBase . '-' . $counter . '.' . $oldExt;
        $counter++;
    }

    // 1. Rename on disk
    if (!@rename(UPLOAD_DIR . $oldFilename, UPLOAD_DIR . $finalNewFilename)) {
        throw new RuntimeException('Failed to rename file on disk.');
    }

    // 2. Update media_items table
    $stmt = $pdo->prepare('UPDATE media_items SET filename = :new, updated_at = NOW() WHERE filename = :old');
    $stmt->execute([':new' => $finalNewFilename, ':old' => $oldFilename]);

    // 3. Cascade updates across all foreign image columns
    $tablesAndCols = [
        'projects' => ['cover_image'],
        'blog_posts' => ['cover_image'],
        'services' => ['cover_image'],
        'products' => ['cover_image'],
        'clients' => ['logo_image'],
        'testimonials' => ['client_photo'],
        'about_content' => ['profile_image', 'resume_file'],
    ];

    foreach ($tablesAndCols as $table => $cols) {
        try {
            foreach ($cols as $col) {
                $stmt = $pdo->prepare("UPDATE {$table} SET {$col} = :new WHERE {$col} = :old");
                $stmt->execute([':new' => $finalNewFilename, ':old' => $oldFilename]);
            }
        } catch (Throwable $e) {
            // Skip non-existent tables or columns
        }
    }

    // 4. Cascade updates in rich text HTML content
    $richTextFields = [
        'blog_posts' => 'body',
        'projects' => 'description',
        'services' => 'description',
        'products' => 'description',
    ];

    foreach ($richTextFields as $table => $col) {
        try {
            $stmt = $pdo->prepare("UPDATE {$table} SET {$col} = REPLACE({$col}, :old, :new) WHERE {$col} LIKE :likeOld");
            $stmt->execute([
                ':old' => $oldFilename,
                ':new' => $finalNewFilename,
                ':likeOld' => '%' . $oldFilename . '%',
            ]);
        } catch (Throwable $e) {
            // Ignore
        }
    }

    // 5. Cascade site_settings table
    try {
        $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = :new WHERE setting_value = :old");
        $stmt->execute([':new' => $finalNewFilename, ':old' => $oldFilename]);
    } catch (Throwable $e) {
        // Ignore
    }

    return [
        'success' => true,
        'old_filename' => $oldFilename,
        'new_filename' => $finalNewFilename,
        'url' => 'uploads/' . $finalNewFilename,
        'full_url' => UPLOAD_URL . $finalNewFilename,
        'admin_preview_url' => '../uploads/' . $finalNewFilename,
    ];
}

// Services feature helpers (schema migration, icons, card renderer)
require_once __DIR__ . '/services-lib.php';

// Ready projects & digital products for sale
require_once __DIR__ . '/products-lib.php';

