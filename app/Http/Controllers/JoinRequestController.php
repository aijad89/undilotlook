<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Event;
use App\Models\EventJoinRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * A participant asking to join an event's draw, and the documents they sent.
 *
 * Asking is all a participant can do: the office reviews the request and, on
 * approval, puts the company on the draw list. Being on that list is still the
 * only thing that puts a company in the draw.
 */
class JoinRequestController extends Controller
{
    /** Allowed per file: PDF or an image, 5 MB. */
    private const FILE_RULE = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

    private const MAX_FILES = 10;

    public function store(Request $request, Event $event)
    {
        $company = $request->user()->company;

        abort_unless($company && $company->is_active, 403, 'Your account has no active company.');

        if (! $event->acceptsJoinRequests()) {
            return back()->with('error', 'Registration for this event is not open.');
        }

        if ($event->registrations()->where('company_id', $company->id)->exists()) {
            return back()->with('error', 'Your company is already in this draw.');
        }

        $existing = $event->joinRequests()->where('company_id', $company->id)->first();
        if ($existing && $existing->status !== 'rejected') {
            return back()->with('error', 'Your request is already with the office.');
        }

        // Paid: how, and the receipt — nothing else. Waiver: which category, and
        // supporting documents that prove it (the OKU certificate, the award
        // letter…), at least one.
        $waiver = $request->input('payment_method') === 'waiver';

        // Recognition and certificates: what the company said last time is
        // already filled in. The quality question is asked only of a craft that
        // has a standard (Batik, Songket, Tenun).
        $held = $company->credentials ?? [];
        $standard = $company->qualityStandard();
        $credentialRules = [
            'recognition' => ['required', Rule::in(array_keys(Company::RECOGNITIONS))],
            'classification' => ['required', Rule::in(['yes', 'no'])],
        ];
        if ($request->input('classification') === 'yes') {
            $credentialRules += $this->certificateRules($request, 'classification', $held['classification_cert'] ?? null);
        }
        if ($standard) {
            $credentialRules['quality'] = ['required', Rule::in([$standard, 'none'])];
            if ($request->input('quality') === $standard) {
                $credentialRules += $this->certificateRules($request, 'quality', $held['quality_cert'] ?? null);
            }
        }

        $data = $request->validate(
            [
                'declaration' => ['accepted'],
                'payment_method' => ['required', Rule::in(array_keys(EventJoinRequest::PAYMENT_METHODS))],
                'receipt' => $waiver ? ['exclude'] : self::FILE_RULE,
                'waiver_category' => $waiver
                    ? ['required', Rule::in(array_keys(EventJoinRequest::WAIVER_CATEGORIES))]
                    : ['exclude'],
                'documents' => $waiver ? ['required', 'array', 'max:'.self::MAX_FILES] : ['exclude'],
                'documents.*' => $waiver ? self::FILE_RULE : ['exclude'],
            ] + $credentialRules,
            [
                'declaration.accepted' => 'Tick the declaration to join.',
                'payment_method.required' => 'Choose how you paid.',
                'receipt.required' => 'Attach your payment receipt.',
                'waiver_category.required' => 'Choose which waiver applies to you.',
                'documents.required' => 'Attach a document that shows you qualify for the waiver.',
                'recognition.required' => 'Choose the recognition you have received, or Not applicable.',
                'classification.required' => 'Say whether you hold a classification certificate.',
                'quality.required' => 'Choose your quality compliance standard, or Not applicable.',
                'classification_end.after_or_equal' => 'This certificate has expired. Renew it before joining.',
                'quality_end.after_or_equal' => 'This certificate has expired. Renew it before joining.',
                'classification_file.required' => 'Upload the certificate.',
                'quality_file.required' => 'Upload the certificate.',
            ],
            [
                'classification_number' => 'certificate number', 'quality_number' => 'certificate number',
                'classification_start' => 'valid from', 'quality_start' => 'valid from',
                'classification_end' => 'valid until', 'quality_end' => 'valid until',
            ]
        );

        // A certificate keeps the file on record unless a new one was sent.
        // ponytail: a replaced file is not deleted; an older request may still
        // link to it.
        $certificate = fn ($field) => [
            'number' => $data["{$field}_number"],
            'start' => $data["{$field}_start"],
            'end' => $data["{$field}_end"],
        ] + ($request->hasFile("{$field}_file")
            ? ['path' => $request->file("{$field}_file")->store("certificates/{$company->id}", 'local'), 'original' => $request->file("{$field}_file")->getClientOriginalName()]
            : array_intersect_key($held["{$field}_cert"], array_flip(['path', 'original'])));

        $credentials = [
            'recognition' => $data['recognition'],
            'classification' => $data['classification'],
            'classification_cert' => $data['classification'] === 'yes' ? $certificate('classification') : null,
            'quality' => $data['quality'] ?? null,
            'quality_cert' => ($data['quality'] ?? 'none') !== 'none' ? $certificate('quality') : null,
        ];
        $company->update(['credentials' => $credentials]);

        $keep = fn ($file, $name) => [
            'name' => $name,
            'path' => $file->store("join-requests/{$event->id}", 'local'),
            'original' => $file->getClientOriginalName(),
        ];

        $documents = $waiver
            ? collect($request->file('documents', []))->map(fn ($file) => $keep($file, $file->getClientOriginalName()))->values()->all()
            : [$keep($request->file('receipt'), 'Payment receipt')];

        // The certificates sit with the other files, so the office opens them
        // from the same list.
        foreach (['classification_cert' => 'Classification certificate', 'quality_cert' => 'Quality compliance certificate'] as $key => $name) {
            if ($credentials[$key]) {
                $documents[] = ['name' => $name, 'path' => $credentials[$key]['path'], 'original' => $credentials[$key]['original']];
            }
        }

        // A rejected request is asked again in place: its own old files go. The
        // certificates are the company's, and stay.
        if ($existing) {
            Storage::disk('local')->delete(array_filter(
                array_column($existing->documents ?? [], 'path'),
                fn ($path) => str_starts_with($path, 'join-requests/')
            ));
        }

        EventJoinRequest::updateOrCreate(
            ['event_id' => $event->id, 'company_id' => $company->id],
            [
                'user_id' => $request->user()->id,
                'payment_method' => $data['payment_method'],
                'waiver_category' => $data['waiver_category'] ?? null,
                'documents' => $documents,
                'answers' => $credentials,
                'status' => 'pending',
                'reject_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        return back()->with('success', 'Sent. The office will review your documents.');
    }

    /**
     * The rules for one certificate: number, dates, and the file. Expired is
     * refused. The file on record still counts while it is the same certificate
     * (same number, same end date); a renewed one needs its new file.
     */
    private function certificateRules(Request $request, string $field, ?array $onFile): array
    {
        $same = $onFile
            && $request->input("{$field}_number") === $onFile['number']
            && $request->input("{$field}_end") === $onFile['end'];

        return [
            "{$field}_number" => ['required', 'string', 'max:100'],
            "{$field}_start" => ['required', 'date'],
            "{$field}_end" => ['required', 'date', "after:{$field}_start", 'after_or_equal:today'],
            "{$field}_file" => $same ? ['nullable', ...array_slice(self::FILE_RULE, 1)] : self::FILE_RULE,
        ];
    }

    /**
     * One uploaded document, for its own company or an admin of that company's
     * zone. Either guard may ask, so the route sits outside both groups.
     */
    public function document(Request $request, EventJoinRequest $joinRequest, int $index)
    {
        $path = $joinRequest->documents[$index]['path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $admin = $request->user('admin');
        $user = $request->user('web');

        abort_unless(
            ($admin && ($admin->hasGlobalAccess() || $admin->state === $joinRequest->company->state))
                || ($user && $user->company?->id === $joinRequest->company_id),
            403
        );

        return Storage::disk('local')->response($path, $joinRequest->documents[$index]['original']);
    }
}
