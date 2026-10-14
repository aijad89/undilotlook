<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use Illuminate\Http\Request;

/**
 * The audit trail, read-only, for super and hq (gated in routes/web.php).
 *
 * Opened in full to hq, Admin rows included: hq runs the day-to-day work, this
 * is how anyone finds out what happened to a company or a draw, and a record
 * only deters if the people doing the work can see it.
 */
class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $audits = AuditTrail::with('performer')
            ->latest('created_at')
            ->when($request->input('action'), fn ($q, $action) => $q->where('action', $action))
            ->when($request->input('model'), fn ($q, $model) => $q->where('auditable_type', 'App\\Models\\'.$model))
            ->when($request->input('performer'), fn ($q, $performer) => $q->where('performer_type', 'App\\Models\\'.$performer))
            ->when($request->input('search'), fn ($q, $search) => $q->where(fn ($w) => $w
                ->where('ip_address', 'like', "%{$search}%")
                ->orWhere('url', 'like', "%{$search}%")))
            ->when($request->input('from'), fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->input('to'), fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->paginate(25)
            ->withQueryString();

        return view('admin.audit-trails.index', [
            'audits' => $audits,
            'actions' => AuditTrail::distinct()->orderBy('action')->pluck('action'),
            'models' => AuditTrail::distinct()->pluck('auditable_type')->filter()->map(fn ($type) => class_basename($type)),
        ]);
    }

    public function show(AuditTrail $auditTrail)
    {
        $auditTrail->load(['performer', 'auditable']);

        return view('admin.audit-trails.show', compact('auditTrail'));
    }
}
