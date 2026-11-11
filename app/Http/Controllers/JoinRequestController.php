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

    private const PRODUCT_IMAGES = 5;

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
        if ($existing && ! in_array($existing->status, ['draft', 'rejected'], true)) {
            return back()->with('error', 'Your request is already with the office.');
        }

        // Save draft: whatever is filled in so far, checked for form but not for
        // completeness, and kept as a draft the office does not see.
        $isDraft = $request->boolean('draft');

        // A draft's payment files count as sent until replaced; a rejected
        // request's do not.
        $draftFiles = $existing?->status === 'draft'
            ? array_values(array_filter($existing->documents ?? [], fn ($doc) => str_starts_with($doc['path'], 'join-requests/')))
            : [];
        $draftWaived = $existing?->payment_method === 'waiver';

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

        // Contact person and product photos. The photos on record stand until a
        // new set is sent, and a set is always five.
        $credentialRules += [
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'regex:/^\+?6?01\d[- ]?\d{3,4}[- ]?\d{4}$/'],
            'product_images' => [empty($held['product_images']) ? 'required' : 'nullable', 'array', 'size:'.self::PRODUCT_IMAGES],
            'product_images.*' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];

        $rules = [
                'declaration' => ['accepted'],
                'payment_method' => ['required', Rule::in(array_keys(EventJoinRequest::PAYMENT_METHODS))],
                'receipt' => $waiver ? ['exclude'] : ($draftFiles && ! $draftWaived ? $this->optional(self::FILE_RULE) : self::FILE_RULE),
                'waiver_category' => $waiver
                    ? ['required', Rule::in(array_keys(EventJoinRequest::WAIVER_CATEGORIES))]
                    : ['exclude'],
                'documents' => $waiver ? [$draftFiles && $draftWaived ? 'nullable' : 'required', 'array', 'max:'.self::MAX_FILES] : ['exclude'],
                'documents.*' => $waiver ? self::FILE_RULE : ['exclude'],
            ] + $credentialRules;

        // A draft: the same rules, nothing required, and no expiry check yet.
        if ($isDraft) {
            $rules = array_map(fn ($rule) => array_values(array_filter(
                $this->optional($rule), fn ($part) => ! in_array($part, ['accepted', 'after_or_equal:today'], true)
            )), $rules);
        }

        $data = $request->validate(
            $rules,
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
                'contact_phone.regex' => 'Enter a Malaysian mobile number, e.g. 012-345 6789.',
                'product_images.required' => 'Upload '.self::PRODUCT_IMAGES.' photos of your products.',
                'product_images.size' => 'Upload exactly '.self::PRODUCT_IMAGES.' photos of your products.',
                'product_images.*.mimes' => 'Product photos must be JPG or PNG.',
            ],
            [
                'classification_number' => 'certificate number', 'quality_number' => 'certificate number',
                'classification_start' => 'valid from', 'quality_start' => 'valid from',
                'classification_end' => 'valid until', 'quality_end' => 'valid until',
                'contact_name' => 'contact person', 'contact_phone' => 'mobile number',
            ]
        );

        // A certificate keeps the file on record unless a new one was sent, and
        // only while it is the same certificate (same number, same end date): a
        // renewal saved in a draft must not carry the old file to Join.
        // ponytail: a replaced file is not deleted; an older request may still
        // link to it.
        // (A draft may leave any of these empty.)
        $certificate = function ($field) use ($data, $request, $held, $company) {
            $cert = [
                'number' => $data["{$field}_number"] ?? null,
                'start' => $data["{$field}_start"] ?? null,
                'end' => $data["{$field}_end"] ?? null,
            ];
            $onFile = $held["{$field}_cert"] ?? [];

            return $cert + match (true) {
                $request->hasFile("{$field}_file") => ['path' => $request->file("{$field}_file")->store("certificates/{$company->id}", 'local'), 'original' => $request->file("{$field}_file")->getClientOriginalName()],
                ($onFile['number'] ?? null) === $cert['number'] && ($onFile['end'] ?? null) === $cert['end'] => array_intersect_key($onFile, array_flip(['path', 'original'])),
                default => [],
            };
        };

        $credentials = [
            'recognition' => $data['recognition'] ?? null,
            'classification' => $data['classification'] ?? null,
            'classification_cert' => ($data['classification'] ?? null) === 'yes' ? $certificate('classification') : null,
            'quality' => $data['quality'] ?? null,
            'quality_cert' => in_array($data['quality'] ?? null, [null, 'none'], true) ? null : $certificate('quality'),
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'product_images' => $request->hasFile('product_images')
                ? collect($request->file('product_images'))->map(fn ($file) => [
                    'path' => $file->store("products/{$company->id}", 'local'),
                    'original' => $file->getClientOriginalName(),
                ])->values()->all()
                : ($held['product_images'] ?? []),
        ];
        $company->update(['credentials' => $credentials]);

        $keep = fn ($file, $name) => [
            'name' => $name,
            'path' => $file->store("join-requests/{$event->id}", 'local'),
            'original' => $file->getClientOriginalName(),
        ];

        // New files replace the draft's; none sent keeps the draft's own kind.
        $documents = match (true) {
            ! $request->filled('payment_method') => [],
            $waiver && $request->hasFile('documents') => collect($request->file('documents'))->map(fn ($file) => $keep($file, $file->getClientOriginalName()))->values()->all(),
            ! $waiver && $request->hasFile('receipt') => [$keep($request->file('receipt'), 'Payment receipt')],
            default => $waiver === $draftWaived ? $draftFiles : [],
        };

        // The certificates and photos sit with the other files, so the office
        // opens them from the same list. A draft holds its payment files only.
        foreach (['classification_cert' => 'Classification certificate', 'quality_cert' => 'Quality compliance certificate'] as $key => $name) {
            if (! $isDraft && $credentials[$key]) {
                $documents[] = ['name' => $name, 'path' => $credentials[$key]['path'], 'original' => $credentials[$key]['original']];
            }
        }
        foreach ($isDraft ? [] : $credentials['product_images'] as $i => $image) {
            $documents[] = ['name' => 'Product photo '.($i + 1)] + $image;
        }

        // Asked again in place: its own old files go unless kept. The
        // certificates and photos are the company's, and stay.
        if ($existing) {
            Storage::disk('local')->delete(array_diff(
                array_filter(array_column($existing->documents ?? [], 'path'), fn ($path) => str_starts_with($path, 'join-requests/')),
                array_column($documents, 'path')
            ));
        }

        EventJoinRequest::updateOrCreate(
            ['event_id' => $event->id, 'company_id' => $company->id],
            [
                'user_id' => $request->user()->id,
                'payment_method' => $data['payment_method'] ?? null,
                'waiver_category' => $data['waiver_category'] ?? null,
                'documents' => $documents,
                'answers' => $credentials,
                'status' => $isDraft ? 'draft' : 'pending',
            ] + ($isDraft ? [] : [
                // A draft keeps the last rejection, so its reason stays in
                // view until the request is sent again.
                'reject_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ])
        );

        return $isDraft
            ? back()->with('success', 'Draft saved. Come back to finish it any time before registration closes.')->with('step', $request->input('at'))
            : back()->with('success', 'Sent. The office will review your documents.');
    }

    /** A rule with `required` made `nullable`. */
    private function optional(array $rule): array
    {
        return array_map(fn ($part) => $part === 'required' ? 'nullable' : $part, $rule);
    }

    /**
     * The rules for one certificate: number, dates, and the file. Expired is
     * refused. The file on record still counts while it is the same certificate
     * (same number, same end date); a renewed one needs its new file.
     */
    private function certificateRules(Request $request, string $field, ?array $onFile): array
    {
        $same = ! empty($onFile['path'])
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
