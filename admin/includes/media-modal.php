<?php
/**
 * media-modal.php — Universal Media Library Modal for Admin Dashboard.
 * Allows picking from existing uploaded images or uploading new images, with direct cursor insertion.
 */
?>
<!-- Media Library Universal Modal -->
<div id="media-library-modal" class="media-modal-backdrop" style="display:none;" aria-hidden="true" role="dialog" aria-labelledby="media-modal-title">
  <div class="media-modal-container">
    <div class="media-modal-header">
      <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <h2 id="media-modal-title" style="margin:0;font-size:1.25rem;">Media Library</h2>
        <div class="media-modal-tabs">
          <button type="button" class="media-tab-btn active" data-tab="browse" onclick="mediaModalSwitchTab('browse')">Browse Library</button>
          <button type="button" class="media-tab-btn" data-tab="upload" onclick="mediaModalSwitchTab('upload')">Upload New Image</button>
        </div>
      </div>
      <button type="button" class="media-modal-close" onclick="closeMediaLibrary()" aria-label="Close modal">&times;</button>
    </div>

    <div class="media-modal-body">
      <!-- TAB: BROWSE -->
      <div class="media-tab-content active" id="media-tab-browse">
        <div class="media-modal-toolbar">
          <input type="search" id="media-modal-search" placeholder="Search images by name..." oninput="mediaModalFilter(this.value)">
          <span id="media-modal-count" style="font-size:0.82rem;color:var(--text-muted);"></span>
        </div>
        <div class="media-modal-main-layout">
          <div class="media-modal-grid-wrap">
            <div id="media-modal-grid" class="media-modal-grid">
              <div style="text-align:center;padding:48px 20px;color:var(--text-muted);grid-column:1/-1;">Loading library images...</div>
            </div>
          </div>
          <!-- DETAILS SIDEBAR -->
          <div class="media-modal-sidebar" id="media-modal-sidebar" style="display:none;">
            <div class="media-modal-sidebar-thumb">
              <img id="media-sidebar-preview" src="" alt="">
            </div>
            <div class="media-modal-sidebar-info">
              <p id="media-sidebar-name" style="font-weight:600;font-size:0.86rem;word-break:break-all;margin-bottom:4px;"></p>
              <p id="media-sidebar-meta" style="font-size:0.78rem;color:var(--text-muted);margin-bottom:12px;"></p>

              <div class="form-group" style="margin-bottom:10px;">
                <label style="font-size:0.8rem;margin-bottom:4px;">Alt description</label>
                <input type="text" id="media-sidebar-alt" placeholder="Describe this image" style="font-size:0.85rem;">
              </div>

              <div class="form-group" style="margin-bottom:14px;" id="media-sidebar-caption-group">
                <label style="font-size:0.8rem;margin-bottom:4px;">Caption (optional)</label>
                <input type="text" id="media-sidebar-caption" placeholder="Visible image caption" style="font-size:0.85rem;">
              </div>

              <div style="display:flex;flex-direction:column;gap:8px;margin-top:14px;">
                <button type="button" class="btn btn-primary btn-sm" id="media-sidebar-insert-btn" onclick="mediaModalConfirmInsert(false)">Insert Image into Post</button>
                <button type="button" class="btn btn-secondary btn-sm" id="media-sidebar-insert-caption-btn" onclick="mediaModalConfirmInsert(true)">Insert with Caption</button>
                <button type="button" class="btn btn-ghost btn-sm" id="media-sidebar-copy-btn" onclick="mediaModalCopyPath()">Copy image path</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: UPLOAD -->
      <div class="media-tab-content" id="media-tab-upload" style="display:none;">
        <div class="media-modal-dropzone" id="media-modal-dropzone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;color:var(--accent);margin-bottom:12px;" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <h3 style="margin-bottom:6px;">Upload a New Image</h3>
          <p style="color:var(--text-muted);font-size:0.88rem;margin-bottom:16px;">Drag and drop an image file here, or click Browse</p>
          <div>
            <input type="file" id="media-modal-upload-input" accept=".jpg,.jpeg,.png,.webp,.svg,.gif" style="max-width:320px;margin:0 auto 10px;">
          </div>
          <button type="button" class="btn btn-primary" id="media-modal-do-upload-btn" onclick="mediaModalTriggerUpload()">Upload &amp; Select</button>
          <p style="font-size:0.78rem;color:var(--text-muted);margin-top:14px;">Supported: JPG, PNG, WEBP, SVG, GIF (Up to 10MB)</p>
          <div id="media-modal-upload-status" style="margin-top:14px;display:none;padding:10px 14px;border-radius:var(--radius);font-size:0.85rem;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  let modalOptions = {};
  let mediaLibraryItems = [];
  let selectedMediaItem = null;

  const modal = document.getElementById('media-library-modal');
  const grid = document.getElementById('media-modal-grid');
  const sidebar = document.getElementById('media-modal-sidebar');
  const countBadge = document.getElementById('media-modal-count');
  const searchInput = document.getElementById('media-modal-search');
  const fileInput = document.getElementById('media-modal-upload-input');
  const uploadStatus = document.getElementById('media-modal-upload-status');
  const dropzone = document.getElementById('media-modal-dropzone');

  window.openMediaLibrary = function(options) {
    modalOptions = options || {};
    selectedMediaItem = null;
    sidebar.style.display = 'none';
    if (searchInput) searchInput.value = '';
    mediaModalSwitchTab('browse');

    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    loadMediaLibraryList();
  };

  window.closeMediaLibrary = function() {
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  // Close on Escape or click outside
  window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modal.style.display === 'flex') {
      closeMediaLibrary();
    }
  });

  modal.addEventListener('click', function(e) {
    if (e.target === modal) {
      closeMediaLibrary();
    }
  });

  window.mediaModalSwitchTab = function(tabName) {
    document.querySelectorAll('.media-tab-btn').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
    });
    document.querySelectorAll('.media-tab-content').forEach(content => {
      content.style.display = content.id === ('media-tab-' + tabName) ? '' : 'none';
    });
  };

  function loadMediaLibraryList(selectFilename) {
    grid.innerHTML = '<div style="text-align:center;padding:48px 20px;color:var(--text-muted);grid-column:1/-1;">Loading library images...</div>';
    fetch('media.php?action=list')
      .then(res => res.json())
      .then(data => {
        if (data && data.success && Array.isArray(data.items)) {
          mediaLibraryItems = data.items;
          renderMediaGrid(mediaLibraryItems, selectFilename);
        } else {
          grid.innerHTML = '<div style="text-align:center;padding:40px;color:#ff6b6b;grid-column:1/-1;">Could not load media library.</div>';
        }
      })
      .catch(err => {
        grid.innerHTML = '<div style="text-align:center;padding:40px;color:#ff6b6b;grid-column:1/-1;">Failed to connect to server.</div>';
      });
  }

  function renderMediaGrid(items, selectFilename) {
    if (!items || items.length === 0) {
      grid.innerHTML = '<div style="text-align:center;padding:48px 20px;color:var(--text-muted);grid-column:1/-1;">No images in library. Click "Upload New Image" above!</div>';
      if (countBadge) countBadge.textContent = '0 items';
      return;
    }

    if (countBadge) countBadge.textContent = items.length + ' item' + (items.length === 1 ? '' : 's');

    grid.innerHTML = '';
    let itemToSelect = null;

    items.forEach(item => {
      const card = document.createElement('div');
      card.className = 'media-modal-item';
      card.setAttribute('data-filename', item.filename.toLowerCase());
      card.innerHTML = `
        <div class="media-modal-item-thumb">
          <img src="${item.admin_preview_url}" alt="${item.filename}" loading="lazy">
        </div>
        <p class="media-modal-item-name" title="${item.filename}">${item.filename}</p>
      `;

      card.addEventListener('click', function() {
        selectMediaItem(item, card);
      });

      card.addEventListener('dblclick', function() {
        selectMediaItem(item, card);
        mediaModalConfirmInsert(false);
      });

      grid.appendChild(card);

      if (selectFilename && item.filename === selectFilename) {
        itemToSelect = { item: item, card: card };
      }
    });

    if (itemToSelect) {
      selectMediaItem(itemToSelect.item, itemToSelect.card);
    }
  }

  function selectMediaItem(item, cardElement) {
    selectedMediaItem = item;
    document.querySelectorAll('.media-modal-item').forEach(el => el.classList.remove('is-selected'));
    if (cardElement) cardElement.classList.add('is-selected');

    sidebar.style.display = 'flex';
    document.getElementById('media-sidebar-preview').src = item.admin_preview_url;
    document.getElementById('media-sidebar-name').textContent = item.filename;
    document.getElementById('media-sidebar-meta').textContent = item.size_formatted + (item.dimensions ? ' • ' + item.dimensions : '') + ' • ' + item.date;

    const altInput = document.getElementById('media-sidebar-alt');
    const captionInput = document.getElementById('media-sidebar-caption');
    const captionGroup = document.getElementById('media-sidebar-caption-group');
    const captionBtn = document.getElementById('media-sidebar-insert-caption-btn');
    const insertBtn = document.getElementById('media-sidebar-insert-btn');
    const altGroup = altInput ? altInput.closest('.form-group') : null;

    altInput.value = '';
    captionInput.value = '';

    const isSingleSelect = modalOptions.mode === 'select_single' || modalOptions.mode === 'cover_image' || typeof modalOptions.onSelect === 'function';

    if (isSingleSelect) {
      captionGroup.style.display = 'none';
      captionBtn.style.display = 'none';
      if (altGroup) altGroup.style.display = 'none';
      insertBtn.textContent = modalOptions.selectButtonText || (modalOptions.mode === 'cover_image' ? 'Use as Cover Image' : 'Select This Image');
    } else {
      captionGroup.style.display = '';
      captionBtn.style.display = '';
      if (altGroup) altGroup.style.display = '';
      insertBtn.textContent = 'Insert Image into Post';
    }
  }

  window.mediaModalFilter = function(query) {
    const term = (query || '').trim().toLowerCase();
    const cards = document.querySelectorAll('.media-modal-item');
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
    if (countBadge) countBadge.textContent = visibleCount + ' item' + (visibleCount === 1 ? '' : 's');
  };

  window.mediaModalConfirmInsert = function(withCaption) {
    if (!selectedMediaItem) {
      alert('Please select an image first.');
      return;
    }

    const alt = (document.getElementById('media-sidebar-alt').value || '').trim();
    const caption = (document.getElementById('media-sidebar-caption').value || '').trim();

    // Custom onSelect callback
    if (typeof modalOptions.onSelect === 'function') {
      modalOptions.onSelect({
        item: selectedMediaItem,
        url: selectedMediaItem.url,
        filename: selectedMediaItem.filename,
        alt: alt,
        caption: caption,
        withCaption: withCaption
      });
      closeMediaLibrary();
      return;
    }

    // Default: Insert into target textarea
    let textarea = modalOptions.targetTextarea;
    if (!textarea && modalOptions.targetName) {
      textarea = document.querySelector('[name="' + modalOptions.targetName + '"]');
    }

    if (textarea) {
      textarea.focus();
      let start = textarea.selectionStart;
      let end = textarea.selectionEnd;
      if (typeof start !== 'number' || start < 0) {
        start = textarea.value.length;
        end = textarea.value.length;
      }

      const text = textarea.value;
      const before = text.substring(0, start);
      const after = text.substring(end);

      let prefix = '\n\n';
      let suffix = '\n\n';
      if (before.endsWith('\n\n') || before === '') prefix = '';
      else if (before.endsWith('\n')) prefix = '\n';
      if (after.startsWith('\n\n') || after === '') suffix = '';
      else if (after.startsWith('\n')) suffix = '\n';

      let snippet = '';
      const safeAlt = alt || selectedMediaItem.filename;
      if (withCaption) {
        const safeCaption = caption || 'Image caption';
        snippet = '<figure>\n  <img src="' + selectedMediaItem.url + '" alt="' + safeAlt + '">\n  <figcaption>' + safeCaption + '</figcaption>\n</figure>';
      } else {
        snippet = '<img src="' + selectedMediaItem.url + '" alt="' + safeAlt + '">';
      }

      const chunk = prefix + snippet + suffix;
      textarea.value = before + chunk + after;
      const newPos = before.length + chunk.length;
      textarea.selectionStart = textarea.selectionEnd = newPos;
      textarea.dispatchEvent(new Event('input', { bubbles: true }));

      // Also notify any inline tray helper if present
      if (window.onMediaImageInserted) {
        window.onMediaImageInserted(selectedMediaItem.url, selectedMediaItem.filename, selectedMediaItem.admin_preview_url);
      }
    }

    closeMediaLibrary();
  };

  window.mediaModalCopyPath = function() {
    if (!selectedMediaItem) return;
    const btn = document.getElementById('media-sidebar-copy-btn');
    navigator.clipboard.writeText(selectedMediaItem.url).then(() => {
      const orig = btn.textContent;
      btn.textContent = 'Copied path!';
      setTimeout(() => { btn.textContent = orig; }, 2000);
    }).catch(() => {
      prompt('Image path:', selectedMediaItem.url);
    });
  };

  // Upload handler
  window.mediaModalTriggerUpload = function() {
    if (!fileInput || !fileInput.files || !fileInput.files[0]) {
      alert('Please choose an image file first.');
      return;
    }
    const file = fileInput.files[0];
    uploadMediaFile(file);
  };

  function uploadMediaFile(file) {
    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
      showUploadStatus('Error: Image exceeds 10MB limit.', 'error');
      return;
    }

    showUploadStatus('Uploading "' + file.name + '"...', 'uploading');

    // Get CSRF token from active page
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    const token = csrfInput ? csrfInput.value : '';

    const formData = new FormData();
    formData.append('media_file', file);
    formData.append('csrf_token', token);

    fetch('media.php?action=upload', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: {
        'X-CSRF-Token': token
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data && data.success && data.item) {
        showUploadStatus('✓ Successfully uploaded!', 'success');
        fileInput.value = '';
        setTimeout(() => {
          mediaModalSwitchTab('browse');
          loadMediaLibraryList(data.item.filename);
        }, 600);
      } else {
        throw new Error((data && data.error) ? data.error : 'Upload failed.');
      }
    })
    .catch(err => {
      showUploadStatus('Error: ' + err.message, 'error');
    });
  }

  function showUploadStatus(msg, type) {
    uploadStatus.style.display = 'block';
    uploadStatus.textContent = msg;
    if (type === 'uploading') {
      uploadStatus.style.background = 'rgba(92, 225, 255, 0.12)';
      uploadStatus.style.color = 'var(--accent)';
      uploadStatus.style.border = '1px solid rgba(92, 225, 255, 0.3)';
    } else if (type === 'success') {
      uploadStatus.style.background = 'rgba(46, 213, 115, 0.12)';
      uploadStatus.style.color = '#2ed573';
      uploadStatus.style.border = '1px solid rgba(46, 213, 115, 0.3)';
    } else {
      uploadStatus.style.background = 'rgba(255, 107, 107, 0.12)';
      uploadStatus.style.color = '#ff6b6b';
      uploadStatus.style.border = '1px solid rgba(255, 107, 107, 0.3)';
    }
  }

  // Drag and drop onto dropzone
  if (dropzone) {
    ['dragenter', 'dragover'].forEach(evt => {
      dropzone.addEventListener(evt, e => {
        e.preventDefault();
        dropzone.classList.add('is-dragover');
      });
    });
    ['dragleave', 'drop'].forEach(evt => {
      dropzone.addEventListener(evt, e => {
        e.preventDefault();
        dropzone.classList.remove('is-dragover');
      });
    });
    dropzone.addEventListener('drop', e => {
      if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
        uploadMediaFile(e.dataTransfer.files[0]);
      }
    });
  }

  // Direct change on file input triggers upload automatically for maximum ease
  if (fileInput) {
    fileInput.addEventListener('change', function() {
      if (fileInput.files && fileInput.files[0]) {
        uploadMediaFile(fileInput.files[0]);
      }
    });
  }

  // Universal helper for form image fields to pick from media library
  window.chooseFromMediaLibrary = function(fieldName, fieldLabel) {
    window.openMediaLibrary({
      mode: 'select_single',
      selectButtonText: fieldLabel ? ('Use for ' + fieldLabel) : 'Select This Image',
      onSelect: function(data) {
        const input = document.getElementById(fieldName + '_existing');
        const badge = document.getElementById(fieldName + '_selected_badge');
        const clearBtn = document.getElementById(fieldName + '_clear_btn');
        const previewWrap = document.getElementById(fieldName + '_preview_wrap');
        const previewImg = document.getElementById(fieldName + '_preview_img');
        const fileInput = document.getElementById(fieldName + '_file_input') || document.querySelector('input[name="' + fieldName + '"][type="file"]');

        if (input) input.value = data.filename;
        if (badge) {
          badge.textContent = '✓ Library: ' + data.filename;
          badge.style.display = 'inline-flex';
        }
        if (clearBtn) clearBtn.style.display = 'inline-flex';
        if (previewWrap && previewImg) {
          previewImg.src = data.item ? data.item.admin_preview_url : ('../uploads/' + data.filename);
          previewWrap.style.display = 'flex';
        }
        if (fileInput) {
          fileInput.value = '';
        }
      }
    });
  };

  window.clearMediaSelection = function(fieldName) {
    const input = document.getElementById(fieldName + '_existing');
    const badge = document.getElementById(fieldName + '_selected_badge');
    const clearBtn = document.getElementById(fieldName + '_clear_btn');
    const previewWrap = document.getElementById(fieldName + '_preview_wrap');
    const previewImg = document.getElementById(fieldName + '_preview_img');

    if (input) input.value = '';
    if (badge) {
      badge.textContent = '';
      badge.style.display = 'none';
    }
    if (clearBtn) clearBtn.style.display = 'none';
    if (previewWrap) previewWrap.style.display = 'none';
    if (previewImg) previewImg.src = '';
  };
})();
</script>
