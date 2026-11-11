@extends('layouts.frontend')

@section('title', 'Product photos')
@section('page-title', 'Product photos')

@section('content')
@include('partials.apple-styles')

<div class="ap-page ap-dash">
    <div class="ap-page-header">
        <div>
            <h2 class="ap-page-title">Product photos</h2>
            <p class="ap-page-subtitle">
                The photos you sent with your request to join
                <strong>{{ $joinRequest->event->title }}</strong>, {{ $joinRequest->event->start_date->format('d M Y') }}.
            </p>
        </div>
    </div>

    @if($photos)
        <ul class="ap-photo-grid">
            @foreach($photos as $i => $photo)
                <li>
                    {{-- Full size in its own tab. --}}
                    <a href="{{ route('join-requests.photo', [$joinRequest, $i]) }}" target="_blank" rel="noopener" class="ap-photo">
                        <img src="{{ route('join-requests.photo', [$joinRequest, $i]) }}" alt="Product photo {{ $i + 1 }}" loading="lazy">
                        <span class="ap-photo-name">Photo {{ $i + 1 }} <span class="visually-hidden">(opens full size in a new tab)</span></span>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <div class="ap-card ap-empty">
            <i class="bi bi-images ap-empty-icon" aria-hidden="true"></i>
            <div class="ap-empty-title">No photos with this request</div>
        </div>
    @endif
</div>
@endsection
