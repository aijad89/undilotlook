@extends('layouts.admin')
@section('title', 'New Footer Menu Item')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div class="mac-header">
            <h2>New Footer Menu Item</h2>
            <div class="mac-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="sep" aria-hidden="true">›</span>
                <a href="{{ route('admin.footer-menus.index') }}">Footer Menus</a>
                <span class="sep" aria-hidden="true">›</span>
                New
            </div>
        </div>
        <a href="{{ route('admin.footer-menus.index') }}" class="mac-btn-secondary">← Back</a>
    </div>

    @include('partials.admin-alerts')

    <form method="POST" action="{{ route('admin.footer-menus.store') }}" id="footerMenuForm">
        @csrf
        <div class="mac-card mac-card-padded">

            <div class="row">
                <div class="col-md-10 mb-3">
                    <label class="mac-label">Menu Title <span class="text-danger">*</span></label>
                    <input type="text" name="title"
                           class="mac-form-control @error('title') is-invalid @enderror"
                           value="{{ old('title') }}"
                           placeholder="e.g. About Us, Privacy Policy, Contact">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2 mb-3">
                    <label class="mac-label">Status</label>
                    <div class="d-flex align-items-center" style="height:43px;">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   value="1" id="isActive"
                                   {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label mac-label mb-0" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="mac-label">Content <span class="text-danger">*</span></label>

                {{-- Toolbar --}}
                <div style="display:flex;gap:6px;margin-bottom:6px;flex-wrap:wrap;">
                    <button type="button" class="mac-action-btn" onclick="fmt('bold')"><b>B</b></button>
                    <button type="button" class="mac-action-btn" onclick="fmt('italic')"><i>I</i></button>
                    <button type="button" class="mac-action-btn" onclick="fmt('insertUnorderedList')">• List</button>
                    <button type="button" class="mac-action-btn" onclick="insertLink()">Link</button>
                    <button type="button" class="mac-action-btn" onclick="togglePreview()">Preview</button>
                </div>

                <div id="editor"
                     contenteditable="true"
                     oninput="document.getElementById('contentInput').value = this.innerHTML"
                     style="min-height:200px;padding:12px 14px;border:1px solid rgba(0,0,0,0.12);
                            border-radius:10px;background:#fff;font-size:0.85rem;line-height:1.6;
                            outline:none;"
                     class="@error('content') border-danger @enderror"
                     >{!! old('content') !!}</div>
                <div id="preview"
                     style="display:none;min-height:200px;padding:12px 14px;border:1px solid rgba(0,0,0,0.12);
                            border-radius:10px;background:rgba(0,0,0,0.02);font-size:0.85rem;line-height:1.6;"></div>
                <input type="hidden" name="content" id="contentInput" value="{{ old('content') }}">
                @error('content')<div class="text-danger" style="font-size:0.82rem;margin-top:4px;">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2 mt-2">
                <button type="submit" class="mac-btn-primary">Save Menu Item</button>
                <a href="{{ route('admin.footer-menus.index') }}" class="mac-btn-secondary">Cancel</a>
            </div>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    function fmt(cmd) {
        document.getElementById('editor').focus();
        document.execCommand(cmd, false, null);
    }

    function insertLink() {
        const url = prompt('Enter URL:');
        if (url) {
            document.getElementById('editor').focus();
            document.execCommand('createLink', false, url);
        }
    }

    function togglePreview() {
        const editor  = document.getElementById('editor');
        const preview = document.getElementById('preview');
        if (preview.style.display === 'none') {
            preview.innerHTML = editor.innerHTML;
            editor.style.display  = 'none';
            preview.style.display = 'block';
        } else {
            editor.style.display  = 'block';
            preview.style.display = 'none';
        }
    }

    // Sync on submit as a safety net (primary sync is oninput on the editor). By id:
    // the first <form> on the page is the sidebar's Sign Out.
    document.getElementById('footerMenuForm').addEventListener('submit', function () {
        document.getElementById('contentInput').value = document.getElementById('editor').innerHTML;
    });
</script>
@endpush
