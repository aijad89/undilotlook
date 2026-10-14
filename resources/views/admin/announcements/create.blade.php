@extends('layouts.admin')

@section('title', 'New Announcement')

@section('content')
@include('partials.mac-styles')

<div class="mac-page">
    @include('partials.admin-alerts')

    <div class="mac-breadcrumb mb-2">
        <a href="{{ route('admin.announcements.index') }}">Announcements</a> / New
    </div>

    <div class="mac-header mb-4">
        <h2>New Announcement</h2>
        <p class="mac-muted mb-0">It appears in each recipient's inbox once the queue worker has run.</p>
    </div>

    <div class="mac-card mac-card-padded">
        <form action="{{ route('admin.announcements.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="mac-label" for="type">Type</label>
                    <select id="type" name="type" class="mac-select" required>
                        @foreach(\App\Models\Announcement::TYPES as $key => $label)
                            <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="mac-label" for="recipient_type">Send to</label>
                    <select id="recipient_type" name="recipient_type" class="mac-select" required>
                        @foreach($recipients as $key => $label)
                            <option value="{{ $key }}" @selected(old('recipient_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="mac-label" for="title">Title</label>
                    <input id="title" type="text" name="title" class="mac-form-control" value="{{ old('title') }}" maxlength="255" required>
                    @error('title')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-12">
                    <label class="mac-label" for="message">Message</label>
                    <textarea id="message" name="message" class="mac-form-control" rows="5" maxlength="5000" required>{{ old('message') }}</textarea>
                    @error('message')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-md-6">
                    <label class="mac-label" for="banner">Banner image <span class="mac-muted">(optional)</span></label>
                    <input id="banner" type="file" name="banner" class="mac-form-control" accept=".jpg,.jpeg,.png">
                    <small class="mac-muted">JPG or PNG, up to 2 MB.</small>
                    @error('banner')<small class="text-danger d-block">{{ $message }}</small>@enderror
                </div>
                <div class="col-md-6">
                    <label class="mac-label" for="attachment">Attachment <span class="mac-muted">(optional)</span></label>
                    <input id="attachment" type="file" name="attachment" class="mac-form-control" accept=".pdf,.doc,.docx,.zip">
                    <small class="mac-muted">PDF, Word or ZIP, up to 2 MB.</small>
                    @error('attachment')<small class="text-danger d-block">{{ $message }}</small>@enderror
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.announcements.index') }}" class="mac-btn-secondary">Cancel</a>
                <button type="submit" class="mac-btn-primary">Send</button>
            </div>
        </form>
    </div>
</div>
@endsection
