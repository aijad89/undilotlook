<?php

namespace App\Http\Controllers;

use App\Exceptions\LotUnavailable;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Poll;
use App\Services\LotAllocator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The participant's side of the draw: the Confirm button and the lot it reveals.
 *
 * TWO KEYS, ON PURPOSE. Whether the company is in the draw is asked by
 * company_id, through User::registrations(). Whether this account has drawn is
 * asked of `polls` by user_id. They are different questions.
 */
class LotDrawController extends Controller
{
    public function show(Request $request, Event $event)
    {
        $registration = $this->entryFor($request, $event);

        $poll = Poll::where('user_id', $request->user()->id)->where('event_id', $event->id)->first();

        if ($poll) {
            return view('zvote.vote', [
                'event' => $event,
                'registration' => $registration,
                'poll' => $poll,
                'hasVoted' => true,
                'assignedPlot' => $registration->plot()->with('plotSize')->first(),
                'code' => $registration->code,
            ]);
        }

        abort_unless($this->isOpen($event), 403, 'The lot draw is not open.');

        // Before confirming, nothing about the lot — not even whether one was
        // set aside. The reveal is the point.
        return view('zvote.vote', [
            'event' => $event,
            'registration' => $registration,
            'hasVoted' => false,
            'code' => filled($registration->code) ? $registration->code : 'NOT SET',
        ]);
    }

    public function submit(Request $request, Event $event)
    {
        $registration = $this->entryFor($request, $event);

        $request->validate(['agreement' => 'accepted']);

        if (! $this->isOpen($event)) {
            return back()->with('error', 'The lot draw is not open.');
        }

        if (blank($registration->code)) {
            return back()->with('error', 'Your craft code has not been set yet. Please contact the office.');
        }

        try {
            // Every rule about which lot this may be lives in LotAllocator, so the
            // admin's bulk run reaches the same decision from the same inputs.
            //
            // A second Confirm is refused there too: after a draw the row carries
            // plot_id, which sends assign() down the preset path, and that path
            // asks Poll. The lock is what makes it hold for a double-click — the
            // second request waits here, then reads the plot_id the first wrote.
            $plot = DB::transaction(function () use ($registration) {
                $fresh = EventRegistration::lockForUpdate()->findOrFail($registration->id);

                return (new LotAllocator)->assign($fresh);
            });
        } catch (LotUnavailable $e) {
            // Known and explicable; the message is already written for the
            // participant.
            Log::info('Lot could not be drawn', [
                'user_id' => $request->user()->id,
                'event_id' => $event->id,
                'reason' => $e->reason,
            ]);

            return redirect()->route('zvote.index', $event)->with('error', $e->getMessage());
        }

        Log::info('Lot drawn', [
            'user_id' => $request->user()->id,
            'event_id' => $event->id,
            'plot_code' => $plot->plot_code,
        ]);

        // Back to the page that performs the reveal. A flash carrying the lot
        // code would spoil it; the code is on that page either way.
        return redirect()->route('zvote.index', $event)->with('just_drawn', true);
    }

    /**
     * The printed slip: one page with the lot, the check-in code, and room for a
     * stamp. Only once a lot is held — before that there is nothing to print.
     */
    public function slip(Request $request, Event $event)
    {
        $registration = $this->entryFor($request, $event);
        $user = $request->user();

        $poll = Poll::where('user_id', $user->id)->where('event_id', $event->id)->first();
        $assignedPlot = $registration->plot()->with(['plotSize', 'bidang'])->first();

        abort_unless($poll && $assignedPlot, 404);

        return Pdf::loadView('pdf.lot-result', [
            'event' => $event,
            'registration' => $registration->load('company'),
            'assignedPlot' => $assignedPlot,
            'user' => $user,
            'poll' => $poll,
            // The same identity code the participant sees on screen.
            'icHash' => $user->icHash(),
            'qrDataUri' => $user->icQrDataUri(),
        ])
            ->setPaper('a4', 'portrait')
            // Embed only the glyphs used. Without it the whole of DejaVu Sans
            // rides along and one page weighs ~880 KB — a lot to share from a phone.
            ->setOption('isFontSubsettingEnabled', true)
            ->stream("lot-{$assignedPlot->plot_code}.pdf");
    }

    /**
     * This account's company's place in the draw, or 404. Not 403: an event the
     * company is not in is, from here, an event that does not exist.
     */
    private function entryFor(Request $request, Event $event): EventRegistration
    {
        return $request->user()->registrations()
            ->where('event_registrations.event_id', $event->id)
            ->firstOrFail();
    }

    private function isOpen(Event $event): bool
    {
        return $event->polling_status === 'polling'
            && ! $event->polling_deadline?->isPast();
    }
}
