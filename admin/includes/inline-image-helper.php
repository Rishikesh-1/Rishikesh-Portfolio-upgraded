<?php
/**
 * inline-image-helper.php — Reusable UI helper for uploading & inserting inline images into blog/project textareas.
 */
function render_inline_image_helper(string $targetName = 'body'): void
{
    $token = csrf_token();
    ?>
    <div class="inline-media-helper" data-target="<?= e($targetName) ?>">
      <div class="inline-media-helper-header">
        <div class="inline-media-helper-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          <div>
            <strong>Inline Images</strong>
            <span class="inline-media-helper-subtitle">— Add pictures between paragraphs</span>
          </div>
        </div>
        <div class="inline-media-helper-action">
          <label class="btn btn-secondary btn-sm inline-media-upload-btn" tabindex="0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;vertical-align:-2px;margin-right:4px;" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <span>Upload &amp; Insert Image</span>
            <input type="file" class="inline-media-file-input" accept=".jpg,.jpeg,.png,.webp,.svg" style="display:none;">
          </label>
        </div>
      </div>

      <div class="inline-media-status" style="display:none;"></div>

      <div class="inline-media-tray" style="display:none;">
        <span class="inline-media-tray-label">Images in this post / description:</span>
        <div class="inline-media-tray-items"></div>
      </div>
    </div>

    <script>
    (function() {
      const helper = document.querySelector('.inline-media-helper[data-target="<?= e($targetName) ?>"]');
      if (!helper) return;

      const form = helper.closest('form');
      if (!form) return;
      const textarea = form.querySelector('[name="<?= e($targetName) ?>"]');
      if (!textarea) return;

      const fileInput = helper.querySelector('.inline-media-file-input');
      const uploadBtn = helper.querySelector('.inline-media-upload-btn');
      const statusDiv = helper.querySelector('.inline-media-status');
      const trayDiv = helper.querySelector('.inline-media-tray');
      const trayItems = helper.querySelector('.inline-media-tray-items');

      let lastCursorPos = textarea.value.length;

      function updateCursorPos() {
        if (document.activeElement === textarea) {
          lastCursorPos = textarea.selectionStart;
        }
      }

      textarea.addEventListener('keyup', updateCursorPos);
      textarea.addEventListener('mouseup', updateCursorPos);
      textarea.addEventListener('select', updateCursorPos);
      textarea.addEventListener('blur', updateCursorPos);

      // Support Enter/Space key on label for keyboard accessibility
      uploadBtn.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          fileInput.click();
        }
      });

      function showStatus(message, type) {
        statusDiv.className = 'inline-media-status ' + (type || '');
        statusDiv.innerHTML = message;
        statusDiv.style.display = 'block';
        if (type === 'success') {
          setTimeout(function() {
            if (statusDiv.classList.contains('success')) {
              statusDiv.style.display = 'none';
            }
          }, 6000);
        }
      }

      function insertAtCursor(markup) {
        textarea.focus();
        let start = textarea.selectionStart;
        let end = textarea.selectionEnd;

        // If not focused, fallback to last remembered position
        if (typeof start !== 'number' || start < 0) {
          start = lastCursorPos;
          end = lastCursorPos;
        }

        const text = textarea.value;
        const before = text.substring(0, start);
        const after = text.substring(end);

        // Ensure clean separation between surrounding lines
        let prefix = '\n\n';
        let suffix = '\n\n';
        if (before.endsWith('\n\n') || before === '') prefix = '';
        else if (before.endsWith('\n')) prefix = '\n';

        if (after.startsWith('\n\n') || after === '') suffix = '';
        else if (after.startsWith('\n')) suffix = '\n';

        const chunk = prefix + markup + suffix;
        textarea.value = before + chunk + after;
        const newPos = before.length + chunk.length;
        textarea.selectionStart = textarea.selectionEnd = newPos;
        lastCursorPos = newPos;
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
      }

      function addImageToTray(url, filename) {
        trayDiv.style.display = 'block';

        // Avoid duplicate entries in tray
        const existing = trayItems.querySelector('[data-url="' + CSS.escape(url) + '"]');
        if (existing) return;

        const card = document.createElement('div');
        card.className = 'inline-media-card';
        card.setAttribute('data-url', url);

        const displayName = filename || url.split('/').pop();
        card.innerHTML = `
          <img src="${url}" alt="" loading="lazy">
          <div class="inline-media-card-info">
            <span class="inline-media-card-name" title="${displayName}">${displayName}</span>
            <div class="inline-media-card-actions">
              <button type="button" class="inline-media-btn-action btn-insert-img" title="Insert image tag at cursor">+ &lt;img&gt;</button>
              <button type="button" class="inline-media-btn-action btn-insert-fig" title="Insert figure with caption at cursor">+ With caption</button>
              <button type="button" class="inline-media-btn-action btn-copy-url" title="Copy image URL">Copy path</button>
            </div>
          </div>
        `;

        card.querySelector('.btn-insert-img').addEventListener('click', function() {
          insertAtCursor('<img src="' + url + '" alt="' + displayName + '">');
          showStatus('✓ Image tag inserted at cursor position!', 'success');
        });

        card.querySelector('.btn-insert-fig').addEventListener('click', function() {
          insertAtCursor('<figure>\n  <img src="' + url + '" alt="' + displayName + '">\n  <figcaption>Enter caption here</figcaption>\n</figure>');
          showStatus('✓ Figure with caption inserted at cursor position!', 'success');
        });

        card.querySelector('.btn-copy-url').addEventListener('click', function() {
          const btn = this;
          navigator.clipboard.writeText(url).then(function() {
            const original = btn.textContent;
            btn.textContent = 'Copied!';
            setTimeout(function() { btn.textContent = original; }, 2000);
          }).catch(function() {
            prompt('Copy image URL:', url);
          });
        });

        trayItems.appendChild(card);
      }

      // Populate existing images found in textarea
      function scanExistingImages() {
        const regex = /<img[^>]+src=["']([^"']+)["']/gi;
        let match;
        while ((match = regex.exec(textarea.value)) !== null) {
          const src = match[1];
          addImageToTray(src);
        }
      }
      scanExistingImages();

      // Handle file selection and asynchronous upload
      fileInput.addEventListener('change', function() {
        const file = fileInput.files[0];
        if (!file) return;

        if (file.size > 3 * 1024 * 1024) {
          showStatus('Error: Image exceeds 3MB limit. Please choose a smaller image.', 'error');
          fileInput.value = '';
          return;
        }

        const validExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        const ext = file.name.split('.').pop().toLowerCase();
        if (!validExts.includes(ext)) {
          showStatus('Error: Invalid file format. Only JPG, PNG, WEBP, and SVG are supported.', 'error');
          fileInput.value = '';
          return;
        }

        showStatus('<span class="inline-media-spinner"></span> Uploading "' + file.name + '"...', 'uploading');

        const formData = new FormData();
        formData.append('image', file);
        formData.append('csrf_token', '<?= $token ?>');

        fetch('upload-image.php', {
          method: 'POST',
          body: formData
        })
        .then(function(res) {
          return res.json().then(function(data) {
            return { ok: res.ok, data: data };
          });
        })
        .then(function(result) {
          if (!result.ok || !result.data.success) {
            throw new Error(result.data.error || 'Failed to upload image.');
          }

          const relativeUrl = result.data.url;
          const filename = result.data.filename;

          // Default auto-insert at current cursor: Figure with caption (easy to keep or edit)
          const insertSnippet = '<figure>\n  <img src="' + relativeUrl + '" alt="' + filename + '">\n  <figcaption>Enter caption here</figcaption>\n</figure>';
          insertAtCursor(insertSnippet);

          addImageToTray(relativeUrl, filename);
          showStatus('✓ Image uploaded and inserted between your text! You can edit the caption or alt text anytime.', 'success');
          fileInput.value = '';
        })
        .catch(function(err) {
          showStatus('Upload failed: ' + (err.message || 'Please check your connection and try again.'), 'error');
          fileInput.value = '';
        });
      });
    })();
    </script>
    <?php
}
