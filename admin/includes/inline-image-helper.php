<?php
/**
 * inline-image-helper.php — Reusable UI helper for uploading & inserting inline images into blog/project textareas.
 */
function render_inline_image_helper(string $targetName = 'body'): void
{
    ?>
    <div class="inline-media-helper" data-target="<?= e($targetName) ?>">
      <div class="inline-media-helper-header">
        <div class="inline-media-helper-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          <div>
            <strong>Inline Images</strong>
            <span class="inline-media-helper-subtitle">— Add pictures between paragraphs (browse library or upload new)</span>
          </div>
        </div>
        <div class="inline-media-helper-action" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <button type="button" class="btn btn-primary btn-sm" onclick="mediaModalSwitchTab('browse'); openMediaLibrary({ targetName: '<?= e($targetName) ?>' });">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;vertical-align:-2px;margin-right:4px;" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <span>📁 Open Media Library</span>
          </button>
          <button type="button" class="btn btn-secondary btn-sm" onclick="mediaModalSwitchTab('upload'); openMediaLibrary({ targetName: '<?= e($targetName) ?>' });">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;display:inline-block;vertical-align:-2px;margin-right:4px;" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <span>Upload New Image</span>
          </button>
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

      function showStatus(message, type) {
        statusDiv.className = 'inline-media-status ' + (type || '');
        statusDiv.innerHTML = message;
        statusDiv.style.display = 'block';
        if (type === 'success') {
          setTimeout(function() {
            if (statusDiv.classList.contains('success')) {
              statusDiv.style.display = 'none';
            }
          }, 8000);
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

      function addImageToTray(url, filename, previewUrl) {
        trayDiv.style.display = 'block';

        // Avoid duplicate entries in tray
        const existing = trayItems.querySelector('[data-url="' + CSS.escape(url) + '"]');
        if (existing) return;

        const card = document.createElement('div');
        card.className = 'inline-media-card';
        card.setAttribute('data-url', url);

        const displayName = filename || url.split('/').pop();
        let effectivePreview = previewUrl;
        if (!effectivePreview) {
          effectivePreview = url.startsWith('uploads/') ? ('../' + url) : url;
        }

        card.innerHTML = `
          <img src="${effectivePreview}" alt="" loading="lazy">
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

      // Hook for media modal insertion to update local tray
      window.onMediaImageInserted = function(url, filename, previewUrl) {
        addImageToTray(url, filename, previewUrl);
        showStatus('✓ Image inserted into your post content at cursor position!', 'success');
      };

      // Support dropping an image directly onto textarea to trigger upload via modal
      textarea.addEventListener('dragover', function(e) {
        if (e.dataTransfer && Array.from(e.dataTransfer.types).includes('Files')) {
          e.preventDefault();
        }
      });

      textarea.addEventListener('drop', function(e) {
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
          const file = e.dataTransfer.files[0];
          if (file.type && file.type.startsWith('image/')) {
            e.preventDefault();
            openMediaLibrary({ targetName: '<?= e($targetName) ?>' });
            mediaModalSwitchTab('upload');
            // Auto trigger upload
            const input = document.getElementById('media-modal-upload-input');
            if (input) {
              const dt = new DataTransfer();
              dt.items.add(file);
              input.files = dt.files;
              mediaModalTriggerUpload();
            }
          }
        }
      });
    })();
    </script>
    <?php
}
