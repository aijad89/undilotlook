<?php

namespace App\Http\Controllers;

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

        // How they paid and the receipt for it; supporting documents if they like.
        $data = $request->validate(
            [
                'payment_method' => ['required', Rule::in(array_keys(EventJoinRequest::PAYMENT_METHODS))],
                'receipt' => self::FILE_RULE,
                'documents' => ['nullable', 'array', 'max:'.self::MAX_FILES],
                'documents.*' => self::FILE_RULE,
            ],
            [
                'payment_method.required' => 'Choose how you paid.',
                'receipt.required' => 'Attach your payment receipt.',
            ]
        );

        $keep = fn ($file, $name) => [
            'name' => $name,
            'path' => $file->store("join-requests/{$event->id}", 'local'),
            'original' => $file->getClientOriginalName(),
        ];

        $documents = collect([$keep($request->file('receipt'), 'Payment receipt')])
            ->concat(collect($request->file('documents', []))->map(fn ($file) => $keep($file, $file->getClientOriginalName())))
            ->all();

        // A rejected request is asked again in place: its old files go.
        if ($existing) {
            Storage::disk('local')->delete(array_column($existing->documents ?? [], 'path'));
        }

        EventJoinRequest::updateOrCreate(
            ['event_id' => $event->id, 'company_id' => $company->id],
            [
                'user_id' => $request->user()->id,
                'payment_method' => $data['payment_method'],
                'documents' => $documents,
                'status' => 'pending',
                'reject_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        return back()->with('success', 'Sent. The office will review your documents.');
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
