<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DemoStatus;
use App\Enums\ProjectInquiryStatus;
use App\Http\Requests\Website\StoreBookDemoRequest;
use App\Models\Plan;
use App\Models\ProjectInquiry;
use App\Services\DemoBookingService;
use App\Services\InstituteRegistrationService;
use App\Services\MailTemplateService;
use App\Support\EmailTemplateRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ProjectInquiryController extends AdminController
{
    public function index(Request $request): View
    {
        $inquiries = ProjectInquiry::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();

                $query->where(function ($inner) use ($search) {
                    $inner->where('inquiry_code', 'like', "%{$search}%")
                        ->orWhere('institute_name', 'like', "%{$search}%")
                        ->orWhere('contact_person_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('demo_status'), function ($query) use ($request) {
                $query->where('demo_status', $request->string('demo_status'));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.project-inquiries.index', [
            'inquiries' => $inquiries,
            'statusOptions' => ProjectInquiryStatus::cases(),
            'demoStatusOptions' => DemoStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.project-inquiries.create');
    }

    public function store(StoreBookDemoRequest $request, DemoBookingService $demoBookingService): RedirectResponse
    {
        try {
            $inquiry = $demoBookingService->book(
                $request->validated(),
                'admin'
            );

            return redirect()
                ->route('admin.project-inquiries.show', $inquiry)
                ->with('success', 'Demo booked successfully.');
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'demo_start_time' => $e->getMessage(),
                ]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'demo' => 'Something went wrong while scheduling this demo. Please try again.',
                ]);
        }
    }

    public function show(
        ProjectInquiry $projectInquiry,
        DemoBookingService $demoBookingService,
        InstituteRegistrationService $registrationService
    ): View {
        $projectInquiry->loadMissing('convertedInstitute:id,name,slug');

        $plans = Plan::query()
            ->where('is_public', true)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.project-inquiries.show', [
            'inquiry' => $projectInquiry,
            'confirmUrl' => $projectInquiry->demo_status === DemoStatus::Requested
                ? $demoBookingService->adminConfirmUrl($projectInquiry)
                : null,
            'declineUrl' => $projectInquiry->demo_status === DemoStatus::Requested
                ? $demoBookingService->adminDeclineUrl($projectInquiry)
                : null,
            'plans' => $plans,
            'branchFormCap' => InstituteRegistrationService::BRANCH_FORM_CAP,
            'suggestedManagerEmail' => $registrationService->suggestManagerEmail((string) $projectInquiry->email),
        ]);
    }

    public function update(Request $request, ProjectInquiry $projectInquiry): RedirectResponse
    {
        $validated = $request->validate([
            'demo_status' => ['required', Rule::enum(DemoStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $demoStatus = DemoStatus::from($validated['demo_status']);

        $data = [
            'demo_status' => $demoStatus,
            'admin_notes' => $validated['admin_notes'] ?? null,
        ];

        if ($demoStatus === DemoStatus::Completed) {
            $data['demo_completed_at'] = now();
            if (! $projectInquiry->isConverted()) {
                $data['status'] = ProjectInquiryStatus::DemoCompleted;
            }
        } elseif ($demoStatus === DemoStatus::Cancelled) {
            $data['status'] = ProjectInquiryStatus::Cancelled;
        } elseif ($demoStatus === DemoStatus::Requested) {
            if (! $projectInquiry->isConverted()) {
                $data['status'] = ProjectInquiryStatus::New;
            }
            $data['demo_completed_at'] = null;
        } else {
            if (! $projectInquiry->isConverted()) {
                $data['status'] = ProjectInquiryStatus::New;
            }
            $data['demo_completed_at'] = null;
        }

        $projectInquiry->update($data);

        return back()->with('success', 'Project inquiry updated.');
    }

    public function convert(
        Request $request,
        ProjectInquiry $projectInquiry,
        InstituteRegistrationService $registrationService,
        MailTemplateService $mailTemplates
    ): RedirectResponse {
        if ($projectInquiry->isConverted()) {
            return back()->withErrors(['convert' => 'This inquiry is already converted.']);
        }

        $hasBranches = $request->input('has_branches') === 'yes';

        $validated = $request->validate([
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(fn ($q) => $q
                    ->where('is_public', true)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')),
            ],
            'has_branches' => ['required', Rule::in(['yes', 'no'])],
            'branch_count' => ['nullable', 'integer', 'min:1', 'max:'.InstituteRegistrationService::BRANCH_FORM_CAP],
            'name' => ['required', 'string', 'max:150'],
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'branches' => [$hasBranches ? 'required' : 'nullable', 'array', 'min:1'],
            'branches.*.name' => [$hasBranches ? 'required' : 'nullable', 'string', 'max:150'],
            'branches.*.phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'branches.*.address' => ['nullable', 'string', 'max:500'],
            'branches.*.manager_name' => [$hasBranches ? 'required' : 'nullable', 'string', 'max:150'],
            'branches.*.manager_email' => [
                $hasBranches ? 'required' : 'nullable',
                'email',
                'max:150',
                'distinct',
                'different:admin_email',
                'unique:users,email',
            ],
        ], [
            'branches.*.manager_email.different' => 'Branch manager email must be different from institute admin email.',
            'branches.*.manager_email.distinct' => 'Each branch manager must use a different email.',
        ]);

        if ($hasBranches) {
            $plan = Plan::query()->findOrFail((int) $validated['plan_id']);
            $count = max(1, (int) ($validated['branch_count'] ?? count($validated['branches'] ?? [])));
            $allowed = $plan->max_branches === null
                ? InstituteRegistrationService::BRANCH_FORM_CAP
                : min(InstituteRegistrationService::BRANCH_FORM_CAP, (int) $plan->max_branches);

            if ($count > $allowed) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'branch_count' => 'The '.$plan->name.' plan allows '.$allowed.' branch'.($allowed === 1 ? '' : 'es').'.',
                    ]);
            }

            if (count($validated['branches'] ?? []) !== $count) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'branches' => 'Fill details for all '.$count.' branch'.($count === 1 ? '' : 'es').'.',
                    ]);
            }
        }

        try {
            $result = $registrationService->convertFromProjectInquiry($projectInquiry, [
                'plan_id' => $validated['plan_id'],
                'has_branches' => $validated['has_branches'],
                'name' => $validated['name'],
                'admin_name' => $validated['admin_name'],
                'admin_email' => $validated['admin_email'],
                'phone' => $validated['phone'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'branches' => $hasBranches ? ($validated['branches'] ?? []) : [],
            ]);

            $mailTemplates->send(
                EmailTemplateRegistry::REGISTRATION_CREDENTIALS,
                $result['user']->email,
                $mailTemplates->variablesForCredentials($result['credentials'])
            );

            return redirect()
                ->route('admin.project-inquiries.show', $projectInquiry->fresh())
                ->with('success', 'Institute created. Temporary login details emailed to '.$result['user']->email.'.');
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['convert' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['convert' => 'Convert failed: '.$e->getMessage()]);
        }
    }
}
