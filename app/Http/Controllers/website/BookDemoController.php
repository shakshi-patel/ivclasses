<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\StoreBookDemoRequest;
use App\Models\ProjectInquiry;
use App\Services\DemoBookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BookDemoController extends Controller
{
    public function __construct(
        protected DemoBookingService $demoBookingService
    ) {}

    public function create()
    {
        return view('website.book_demo');
    }

    public function success(Request $request)
    {
        $booking = $request->session()->get('demo_booking');

        if (!$booking && $request->filled('code')) {
            $inquiry = ProjectInquiry::query()
                ->where('inquiry_code', $request->string('code')->toString())
                ->first();

            if ($inquiry) {
                $booking = [
                    'inquiry_code' => $inquiry->inquiry_code,
                    'institute_name' => $inquiry->institute_name,
                    'demo_date_label' => optional($inquiry->demo_date)->format('d M Y'),
                    'demo_time_label' => $inquiry->demo_start_time && $inquiry->demo_end_time
                        ? date('h:i A', strtotime($inquiry->demo_start_time)) . ' – ' . date('h:i A', strtotime($inquiry->demo_end_time))
                        : '',
                    'timezone' => $inquiry->timezone,
                    'meet_link' => $inquiry->google_meet_link,
                ];
            }
        }

        if (!$booking) {
            return redirect()->route('book-demo');
        }

        return view('website.book_demo_success', [
            'booking' => $booking,
        ]);
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        try {
            $date = Carbon::parse(
                $request->date,
                $this->demoBookingService->timezone()
            );

            return response()->json([
                'success' => true,
                'date' => $date->format('Y-m-d'),
                'timezone' => $this->demoBookingService->timezone(),
                'slots' => $this->demoBookingService->getSlots($date, false),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve available demo slots.',
            ], 500);
        }
    }

    public function store(StoreBookDemoRequest $request)
    {
        try {
            $inquiry = $this->demoBookingService->book(
                $request->validated(),
                'website'
            );

            $booking = [
                'inquiry_code' => $inquiry->inquiry_code,
                'institute_name' => $inquiry->institute_name,
                'demo_date_label' => optional($inquiry->demo_date)->format('d M Y'),
                'demo_time_label' => $inquiry->demo_start_time && $inquiry->demo_end_time
                    ? date('h:i A', strtotime($inquiry->demo_start_time)) . ' – ' . date('h:i A', strtotime($inquiry->demo_end_time))
                    : '',
                'timezone' => $inquiry->timezone,
                'meet_link' => $inquiry->google_meet_link,
            ];

            $request->session()->put('demo_booking', $booking);
            $request->session()->save();

            $successUrl = route('book-demo.success', [
                'code' => $inquiry->inquiry_code,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Demo scheduled successfully.',
                    'redirect' => $successUrl,
                    'booking' => $booking,
                ]);
            }

            return redirect()->to($successUrl);
        } catch (InvalidArgumentException $e) {
            return $this->bookingError($request, $e->getMessage(), 'demo_start_time');
        } catch (\Throwable $e) {
            report($e);

            return $this->bookingError(
                $request,
                        'Something went wrong while scheduling your demo. Please try again.',
                'demo',
                500
            );
        }
    }

    protected function bookingError(
        Request $request,
        string $message,
        string $field = 'demo',
        int $status = 422
    ) {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [
                    $field => [$message],
                ],
            ], $status);
        }

        return back()
            ->withInput()
            ->withErrors([
                $field => $message,
            ]);
    }
}
