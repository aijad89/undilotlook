<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Lot Draw Result Slip – {{ $event->title }}</title>
<style>
    /* The PDF renderer reads no CSS variables, so the colours are the token values
       from partials/ui-tokens.blade.php written out: navy #0b2240 (--ui-navy-900),
       blue #1a56db / #1546b3 (--ui-blue / --ui-blue-text), text #101828 / #344054 /
       #586477 (--ui-text / -2 / --ui-muted), borders #dce1e8 / #c3ccd8, tints
       #f8fafc / #ebf1fd / #e6f4ec, ok text #0b6239. Change a token, change it here. */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #101828; background: #fff; }

    /* ── Header ── */
    .header {
        background: #0b2240;
        color: #fff;
        padding: 20px 24px;
        margin-bottom: 0;
    }
    .header h1   { font-size: 17px; font-weight: bold; margin-bottom: 4px; }
    .header .sub { font-size: 10px; opacity: .7; }
    .header .ref { font-size: 10px; opacity: .55; }

    /* ── Green banner ── */
    .success-banner {
        background: #0b6239;
        color: #fff;
        text-align: center;
        padding: 8px 24px;
        font-size: 10.5px;
        font-weight: bold;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-bottom: 24px;
    }

    /* ── Lot box ── */
    .lot-box {
        margin: 0 24px 24px;
        border: 2.5px solid #1a56db;
        border-radius: 10px;
        background: #ebf1fd;
        text-align: center;
        padding: 22px 20px;
    }
    .lot-box .lot-eyebrow {
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: #1546b3;
        margin-bottom: 6px;
    }
    .lot-box .lot-code {
        font-size: 44px;
        font-weight: bold;
        color: #1a56db;
        letter-spacing: .06em;
        line-height: 1;
        margin-bottom: 6px;
    }
    .lot-box .lot-size {
        font-size: 11.5px;
        color: #344054;
        margin-bottom: 4px;
    }
    .lot-box .lot-location {
        font-size: 10px;
        color: #586477;
    }

    /* ── Section ── */
    .section { margin-bottom: 18px; padding: 0 24px; }
    .section-title {
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: #1a56db;
        border-bottom: 1.5px solid #ebf1fd;
        padding-bottom: 4px;
        margin-bottom: 10px;
    }

    /* ── Info table ── */
    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 5px 8px; vertical-align: top; }
    table.info td:first-child { width: 42%; font-weight: bold; color: #344054; }
    table.info tr:nth-child(even) td { background: #f8fafc; }

    /* ── Badge ── */
    .badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .badge-success { background: #e6f4ec; color: #0b6239; }

    /* ── Stamp ── */
    .stamp-area {
        margin: 0 24px 20px;
        border: 1.5px dashed #c3ccd8;
        border-radius: 8px;
        padding: 14px 18px;
        display: table;
        width: calc(100% - 48px);
    }
    .stamp-cell { display: table-cell; width: 50%; vertical-align: middle; }
    .stamp-cell.right { text-align: right; }
    .stamp-label { font-size: 9px; color: #586477; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 32px; }
    .stamp-line  { border-bottom: 1px solid #586477; width: 140px; margin-left: auto; }
    .stamp-name  { font-size: 9px; color: #586477; text-align: center; margin-top: 4px; }

    /* ── Footer ── */
    .footer {
        margin-top: 24px;
        padding: 12px 24px;
        border-top: 1px solid #dce1e8;
        font-size: 9px;
        color: #586477;
    }
    .footer .right { float: right; }

    .no-break { page-break-inside: avoid; }

    .qr-box { text-align: center; margin: 0 0 14px; }
    .qr-note { font-size: 8px; color: #586477; letter-spacing: 0.4px; text-transform: uppercase; margin-top: 3px; }
    .qr-code-text { font-family: monospace; font-size: 10px; color: #101828; letter-spacing: 1.5px; }
    /* Same footprint as the code it stands in for, so the slip does not reflow. */
    /* dompdf ignores line-height centring on a block, so the text is padded
       down to the middle instead: 42 + 12 + 42 = 96. */
    .qr-empty {
        width: 96px; height: 12px; padding: 42px 0; margin: 0 auto;
        border: 1px dashed #c3ccd8; color: #586477;
        font-size: 9px; text-align: center;
    }
</style>
</head>
<body>

{{-- ── Header ── --}}
{{-- A table, not a float: dompdf placed the floated reference above the page edge. --}}
<table class="header" style="width:100%;border-collapse:collapse;">
    <tr>
        <td>
            <div class="sub">Lot Draw Result Slip</div>
            <h1>{{ $event->title }}</h1>
        </td>
        <td class="ref" style="text-align:right;vertical-align:top;white-space:nowrap;">
            Ref: LOT-{{ str_pad($registration->id, 6, '0', STR_PAD_LEFT) }}
        </td>
    </tr>
</table>

<div class="success-banner">
    ✓ &nbsp;Lot Successfully Assigned
</div>

{{-- ── Lot Number ── --}}
<div class="lot-box no-break">
    <div class="lot-eyebrow">Your Assigned Lot Number</div>
    <div class="lot-code">{{ $assignedPlot->plot_code }}</div>
    @if($assignedPlot->plotSize)
        <div class="lot-size">
            Plot Size: {{ $assignedPlot->plotSize->size }}
            @if($assignedPlot->plotSize->location)
                &nbsp;·&nbsp; Location: {{ $assignedPlot->plotSize->location }}
            @endif
        </div>
    @endif
    @if($assignedPlot->bidang)
        <div class="lot-location">Craft code: {{ $assignedPlot->bidang->name }}</div>
    @endif
</div>

{{-- ── Verification QR ──
     Encodes an md5 of the participant's IC, so a scanner at the door resolves a
     slip to a person in one beep. The short code beneath is the head of the same
     hash, for when no scanner is to hand.

     What this is NOT is a way of keeping the IC off the slip: the Participant
     Information table below prints it in full, a few centimetres down. Whoever
     holds this sheet already has the number. The hash is a lookup key here, not
     a mask — see LotDrawController::slip(). --}}
<div class="qr-box no-break">
    @if($qrDataUri)
        <img src="{{ $qrDataUri }}" width="96" height="96" alt="">
        <div class="qr-note">Scan to verify holder</div>
        <div class="qr-code-text">{{ strtoupper(substr($icHash, 0, 8)) }}</div>
    @else
        {{-- ic_number is nullable, and the counter needs to know why the code is
             missing rather than wonder whether the slip printed wrong. --}}
        <div class="qr-empty">No scan code</div>
        <div class="qr-note">IC number not on file &mdash; verify this holder by hand</div>
    @endif
</div>

{{-- ── Participant Info ── --}}
<div class="section no-break">
    <div class="section-title">Participant Information</div>
    <table class="info">
        <tr>
            <td>Full Name</td>
            <td>{{ $user->name }}</td>
        </tr>
        <tr>
            <td>IC Number</td>
            <td>{{ $user->ic_number ?? '—' }}</td>
        </tr>
        @if($registration->company)
        <tr>
            <td>Company Name</td>
            <td>{{ $registration->company->name }}</td>
        </tr>
        @if($registration->company->state)
        <tr>
            <td>Zone</td>
            <td>{{ $registration->company->state }}</td>
        </tr>
        @endif
        @endif
        @if($registration->code)
        <tr>
            <td>Craft Category Code</td>
            <td>{{ $registration->code }}</td>
        </tr>
        @endif
    </table>
</div>

{{-- ── Event & Lot Info ── --}}
<div class="section no-break">
    <div class="section-title">Event & Lot Details</div>
    <table class="info">
        <tr>
            <td>Event</td>
            <td>{{ $event->title }}</td>
        </tr>
        <tr>
            <td>Event Date</td>
            <td>{{ $event->start_date?->format('d F Y') }}@if($event->end_date && $event->end_date->ne($event->start_date)) – {{ $event->end_date->format('d F Y') }}@endif</td>
        </tr>
        <tr>
            <td>Location</td>
            <td>{{ $event->location }}</td>
        </tr>
        <tr>
            <td>Lot Confirmed</td>
            <td>{{ $poll->created_at->format('d F Y, h:i A') }}</td>
        </tr>
        <tr>
            <td>Participation Confirmed</td>
            <td>
                @if($registration->confirmed_at)
                    {{ $registration->confirmed_at->format('d F Y, h:i A') }}
                    &nbsp;<span class="badge badge-success">Confirmed</span>
                @else
                    —
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- ── Signature area ── --}}
<div class="stamp-area no-break">
    <div class="stamp-cell">
        <div class="stamp-label">Participant Signature</div>
        <div class="stamp-line" style="margin-left:0; width:130px;"></div>
        <div class="stamp-name">{{ $user->name }}</div>
    </div>
    <div class="stamp-cell right">
        <div class="stamp-label">Official Stamp / Signature</div>
        <div class="stamp-line"></div>
        <div class="stamp-name">Authorised Officer</div>
    </div>
</div>

{{-- ── Footer ── --}}
<div class="footer">
    <span class="right">Generated: {{ now()->format('d F Y, h:i A') }}</span>
    This slip is an official record of your lot draw result. Please retain it for reference during the event.
</div>

</body>
</html>
