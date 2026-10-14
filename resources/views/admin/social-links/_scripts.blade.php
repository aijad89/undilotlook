<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css"
      integrity="sha384-6LFfkTKLRlzFtgx8xsWyBdKGpcMMQTkv+dB7rAbugeJAu1Ym2q1Aji1cjHBG12Xh" crossorigin="anonymous">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"
        integrity="sha384-jrOgQzBlDeUNdmQn3rUt/PZD+pdcRBdWd/HWRqRo+n2OR2QtGyjSaJC0GiCeH+ir" crossorigin="anonymous"></script>
<script>
    // ── Icon type toggle ───────────────────────────────────
    function updateSections() {
        const isBootstrap = document.getElementById('typeBootstrap').checked;
        document.getElementById('sectionBootstrap').style.display = isBootstrap ? 'block' : 'none';
        document.getElementById('sectionImage').style.display     = isBootstrap ? 'none'  : 'block';
    }

    document.getElementById('typeBootstrap').addEventListener('change', updateSections);
    document.getElementById('typeImage').addEventListener('change', updateSections);
    updateSections();

    // ── Bootstrap icon preview ─────────────────────────────
    const iconInput   = document.getElementById('iconClassInput');
    const iconPreview = document.getElementById('iconPreview');

    function updateIconPreview() {
        iconPreview.innerHTML = `<i class="bi ${iconInput.value.trim()}"></i>`;
    }

    iconInput.addEventListener('input', updateIconPreview);
    updateIconPreview();

    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            iconInput.value = btn.dataset.class;
            updateIconPreview();
        });
    });

    // ── File selection ─────────────────────────────────────
    let cropperInstance = null;
    let currentFile     = null;

    function onFileSelected(input) {
        if (!input.files || !input.files[0]) return;
        currentFile = input.files[0];

        // Raster only: an SVG served from this origin can carry script.
        const reader = new FileReader();
        reader.onload = e => openEditor(e.target.result);
        reader.readAsDataURL(currentFile);
    }

    function handleDrop(e) {
        e.preventDefault();
        document.getElementById('uploadZone').style.borderColor = 'rgba(0,0,0,0.15)';
        const file = e.dataTransfer.files[0];
        if (!file) return;
        const dt   = new DataTransfer();
        dt.items.add(file);
        const input = document.getElementById('iconFile');
        input.files = dt.files;
        onFileSelected(input);
    }

    // ── Editor ─────────────────────────────────────────────
    function openEditor(src) {
        const modal = document.getElementById('iconEditorModal');
        const img   = document.getElementById('cropperImg');

        // Destroy previous instance
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }

        img.src              = src;
        modal.style.display  = 'flex';

        // Init Cropper after image loads
        img.onload = () => {
            cropperInstance = new Cropper(img, {
                aspectRatio:    16 / 24,   // 2:3 — exactly 16×24 output
                viewMode:       1,
                autoCropArea:   1,
                movable:        true,
                zoomable:       true,
                rotatable:      true,
                scalable:       true,
                responsive:     true,
            });
        };
    }

    function reopenEditor() {
        if (!currentFile) return;
        const reader = new FileReader();
        reader.onload = e => openEditor(e.target.result);
        reader.readAsDataURL(currentFile);
    }

    function closeEditor() {
        document.getElementById('iconEditorModal').style.display = 'none';
        if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
    }

    function cropperRotate(deg)  { cropperInstance?.rotate(deg); }
    function cropperReset()      { cropperInstance?.reset(); }
    function cropperFlip(axis)   {
        if (!cropperInstance) return;
        const d = cropperInstance.getData();
        if (axis === 'h') cropperInstance.scaleX(d.scaleX === -1 ? 1 : -1);
        else              cropperInstance.scaleY(d.scaleY === -1 ? 1 : -1);
    }

    function applyEditor() {
        if (!cropperInstance) return;

        // Get cropped canvas at exactly 16×24
        const canvas = cropperInstance.getCroppedCanvas({ width: 16, height: 24 });

        // The fifth getCroppedCanvas() site, and the one the audit's own measurement
        // missed: it counted `toBlob` calls, and this one reaches the canvas through
        // toDataURL. No button is disabled here, so a null canvas was not a stuck
        // control — it was a TypeError that left the editor open with no preview and
        // no explanation, which reads as the Apply button being broken.
        if (!canvas) {
            alert('This image is too large to process on this device. Try a smaller photo.');
            return;
        }

        const base64 = canvas.toDataURL('image/webp', 0.9);

        document.getElementById('iconImageData').value = base64;

        // Show preview
        showPreview(base64, 'Cropped · 16×24 · WebP', true);
        closeEditor();
    }

    function showPreview(src, note, showEditBtn) {
        document.getElementById('uploadPreview').src       = src;
        document.getElementById('uploadNote').textContent  = note;
        document.getElementById('uploadPrompt').style.display       = 'none';
        document.getElementById('uploadPreviewWrap').style.display  = 'flex';
        document.getElementById('editAgainBtn').style.display       = showEditBtn ? 'inline-flex' : 'none';
    }
</script>
