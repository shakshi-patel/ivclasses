@extends('website.layouts.app')

@section('content')
    <main class="demo-page demo-page-simple">
        <div class="container">
            <div class="demo-shell">
                <div class="demo-form-wrap">
                    <div class="form-success">
                        <div class="success-check">
                            <svg viewBox="0 0 64 64" aria-hidden="true">
                                <circle cx="32" cy="32" r="28"></circle>
                                @if ($ok)
                                    <path d="M20 33l8 8 16-18"></path>
                                @else
                                    <path d="M22 22l20 20M42 22L22 42"></path>
                                @endif
                            </svg>
                        </div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $message }}</p>
                        @if ($inquiry)
                            <p class="demo-form-note"><strong>{{ $inquiry->inquiry_code }}</strong></p>
                            <p>{{ $inquiry->institute_name }}</p>
                            <p class="demo-form-note">Status: {{ $inquiry->demo_status }}</p>
                        @endif
                        <p class="demo-form-note">
                            @if ($inquiry)
                                <a href="{{ route('demo.admin.open', $inquiry) }}">Open in admin</a>
                                ·
                            @endif
                            <a href="{{ route('home') }}">Home</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
