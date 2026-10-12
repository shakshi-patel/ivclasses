@extends('website.layouts.app')
@section('content')
    <main class="demo-page demo-page-simple">
        <div class="container">
            <div class="demo-shell">
                <div class="demo-form-wrap">
                    <div id="formSuccess" class="form-success">
                        <div class="success-check">
                            <svg viewBox="0 0 64 64" aria-hidden="true">
                                <circle cx="32" cy="32" r="28"></circle>
                                <path d="M20 33l8 8 16-18"></path>
                            </svg>
                        </div>
                        <h3>Demo Request Received</h3>
                        <p>
                            Thanks — we have your preferred slot.
                            Our team will confirm shortly, and you will get another email with the Google Meet link.
                        </p>
                        <p class="demo-form-note"><strong>{{ $booking['inquiry_code'] }}</strong></p>
                        <p>{{ $booking['institute_name'] }}</p>
                        <p>{{ $booking['demo_date_label'] }} · {{ $booking['demo_time_label'] }}</p>
                        <p class="demo-form-note">{{ $booking['timezone'] }}</p>
                        <p class="demo-form-note">
                            <a href="{{ route('home') }}">Back to home</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
