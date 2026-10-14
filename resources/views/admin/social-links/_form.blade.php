<div class="mac-card mac-card-padded">

    <div class="row">
        {{-- Label --}}
        <div class="col-md-6 mb-3">
            <label class="mac-label">Platform Label <span class="text-danger">*</span></label>
            <input type="text" name="label"
                   class="mac-form-control @error('label') is-invalid @enderror"
                   value="{{ old('label', $link?->label) }}"
                   placeholder="e.g. Facebook, Instagram">
            @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        {{-- URL --}}
        <div class="col-md-6 mb-3">
            <label class="mac-label">URL <span class="text-danger">*</span></label>
            <input type="url" name="url"
                   class="mac-form-control @error('url') is-invalid @enderror"
                   value="{{ old('url', $link?->url) }}"
                   placeholder="https://facebook.com/yourpage">
            @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    {{-- Status --}}
    <div class="mb-4">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                   id="isActive" {{ old('is_active', $link ? $link->is_active : true) ? 'checked' : '' }}>
            <label class="form-check-label mac-label mb-0" for="isActive">Active (visible on site)</label>
        </div>
    </div>

    {{-- Icon type toggle --}}
    <div class="mb-3">
        <label class="mac-label">Icon Type</label>
        <div class="d-flex gap-3 mt-1">
            <label style="cursor:pointer;display:flex;align-items:center;gap:6px;font-size:0.85rem;">
                <input type="radio" name="icon_type" value="bootstrap" id="typeBootstrap"
                       {{ old('icon_type', $link?->icon_type ?? 'bootstrap') === 'bootstrap' ? 'checked' : '' }}>
                Bootstrap Icon (built-in)
            </label>
            <label style="cursor:pointer;display:flex;align-items:center;gap:6px;font-size:0.85rem;">
                <input type="radio" name="icon_type" value="image" id="typeImage"
                       {{ old('icon_type', $link?->icon_type) === 'image' ? 'checked' : '' }}>
                Custom Logo (upload)
            </label>
        </div>
    </div>

    {{-- Bootstrap icon section --}}
    <div id="sectionBootstrap" class="mb-3">
        <label class="mac-label">Bootstrap Icon Class</label>
        <div class="d-flex gap-3 align-items-center flex-wrap mb-2">
            {{-- Quick-pick presets --}}
            @foreach($presets as $cls => $name)
                <button type="button" class="preset-btn mac-action-btn d-flex align-items-center gap-1"
                        data-class="{{ $cls }}"
                        style="font-size:0.78rem;">
                    <i class="bi {{ $cls }}"></i> {{ $name }}
                </button>
            @endforeach
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="text" name="icon_class" id="iconClassInput"
                   class="mac-form-control @error('icon_class') is-invalid @enderror"
                   style="max-width:220px;"
                   value="{{ old('icon_class', $link?->icon_class ?? 'bi-globe') }}"
                   placeholder="bi-facebook">
            <div id="iconPreview" style="font-size:1.6rem;width:36px;text-align:center;"></div>
        </div>
        @error('icon_class')<div class="text-danger mt-1" style="font-size:0.82rem;">{{ $message }}</div>@enderror
    </div>

    {{-- Custom image section --}}
    <div id="sectionImage" class="mb-3" style="display:none;">
        <label class="mac-label">Logo Image <small class="mac-muted">(JPG/PNG/WebP/GIF, resized to 16×24 and converted to WebP)</small></label>

        {{-- Existing image --}}
        @if($link?->icon_image)
            <div class="mb-2" id="existingImg">
                <div class="mac-muted" style="font-size:0.78rem;margin-bottom:4px;">Current logo:</div>
                <img src="{{ $link->icon_image_url }}" width="16" height="24" loading="lazy"
                     alt="Current icon for {{ $link->label }}"
                     style="object-fit:contain;border:1px solid var(--ui-border-strong);border-radius:3px;padding:2px;">
            </div>
        @endif

        {{-- Upload zone --}}
        <div id="uploadZone"
             style="border:2px dashed var(--ui-border-strong);border-radius:10px;padding:24px;
                    text-align:center;cursor:pointer;transition:border-color 0.15s;background:var(--ui-surface-2);"
             onclick="document.getElementById('iconFile').click()"
             ondragover="event.preventDefault();this.style.borderColor='var(--ui-blue)'"
             ondragleave="this.style.borderColor='var(--ui-border-strong)'"
             ondrop="handleDrop(event)">
            <div id="uploadPrompt">
                <i class="bi bi-cloud-upload" style="font-size:1.6rem;color:var(--ui-muted);"></i>
                <div class="mac-muted" style="font-size:0.82rem;margin-top:6px;">Click or drag an image here</div>
                <div class="mac-muted" style="font-size:0.72rem;">JPG · PNG · WebP · GIF · Max 2MB</div>
            </div>
            <div id="uploadPreviewWrap" style="display:none;align-items:center;gap:16px;justify-content:center;">
                <img id="uploadPreview" width="32" height="48" loading="lazy"
                     alt="Preview of the icon you selected"
                     style="object-fit:contain;border-radius:4px;border:1px solid var(--ui-border-strong);">
                <div>
                    <div class="mac-muted" id="uploadNote" style="font-size:0.75rem;">Will be saved as 16×24 WebP</div>
                    <button type="button" class="mac-action-btn mt-1" id="editAgainBtn"
                            style="display:none;" onclick="reopenEditor()">
                        <i class="bi bi-crop"></i> Edit again
                    </button>
                </div>
            </div>
        </div>

        <input type="file" name="icon_image" id="iconFile"
               accept="image/jpeg,image/png,image/webp,image/gif"
               style="display:none;" onchange="onFileSelected(this)">
        {{-- Base64 result from editor (replaces file upload for raster) --}}
        <input type="hidden" name="icon_image_data" id="iconImageData">
        @error('icon_image')<div class="text-danger mt-1" style="font-size:0.82rem;">{{ $message }}</div>@enderror
        @error('icon_image_data')<div class="text-danger mt-1" style="font-size:0.82rem;">{{ $message }}</div>@enderror

        {{-- ── Editor Modal ─────────────────────────────── --}}
        <div id="iconEditorModal"
             style="display:none;position:fixed;inset:0;z-index:9999;
                    background:rgba(0,0,0,0.72);backdrop-filter:blur(4px);
                    align-items:center;justify-content:center;">
            <div style="background:var(--ui-surface);border-radius:var(--ui-r-lg);padding:24px;width:340px;max-width:95vw;">
                <div style="font-weight:600;font-size:0.95rem;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
                    <span><i class="bi bi-crop"></i> Edit Icon</span>
                    <button type="button" onclick="closeEditor()" style="background:none;border:none;font-size:1.1rem;cursor:pointer;color:var(--ui-muted);" aria-label="Close" title="Close">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                {{-- Cropper container --}}
                <div style="width:100%;height:260px;background:var(--ui-neutral-tint);border-radius:10px;overflow:hidden;margin-bottom:14px;">
                    <img id="cropperImg" alt="Icon being cropped" loading="lazy" style="max-width:100%;display:block;">
                </div>

                {{-- Controls --}}
                <div class="d-flex gap-2 justify-content-center mb-3 flex-wrap">
                    <button type="button" class="mac-action-btn" onclick="cropperRotate(-90)" title="Rotate left">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    <button type="button" class="mac-action-btn" onclick="cropperRotate(90)" title="Rotate right">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button type="button" class="mac-action-btn" onclick="cropperFlip('h')" title="Flip horizontal">
                        <i class="bi bi-symmetry-vertical"></i>
                    </button>
                    <button type="button" class="mac-action-btn" onclick="cropperFlip('v')" title="Flip vertical">
                        <i class="bi bi-symmetry-horizontal"></i>
                    </button>
                    <button type="button" class="mac-action-btn" onclick="cropperReset()" title="Reset">
                        <i class="bi bi-arrow-repeat"></i> Reset
                    </button>
                </div>

                <div class="mac-muted text-center mb-3" style="font-size:0.72rem;">
                    Crop area locked to 2:3 ratio (16×24 px output)
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="mac-btn-primary w-100" onclick="applyEditor()">
                        <i class="bi bi-check-lg"></i> Apply
                    </button>
                    <button type="button" class="mac-btn-secondary" onclick="closeEditor()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="mac-btn-primary">{{ $link ? 'Update' : 'Save' }} Social Link</button>
        <a href="{{ route('admin.social-links.index') }}" class="mac-btn-secondary">Cancel</a>
    </div>

</div>
