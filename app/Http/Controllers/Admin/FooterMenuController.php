<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FooterMenu;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\DB;

class FooterMenuController extends Controller
{
    public function index()
    {
        $menus = FooterMenu::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.footer-menus.index', compact('menus'));
    }

    public function create()
    {
        return view('admin.footer-menus.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'     => 'required|string|max:100',
            'content'   => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['content']   = Purifier::clean($data['content'], 'footer_menu');

        // Push all existing items down so new item lands at top
        FooterMenu::query()->increment('sort_order');
        $data['sort_order'] = 0;

        FooterMenu::create($data);

        return redirect()->route('admin.footer-menus.index')
            ->with('success', 'Footer menu created.');
    }

    public function edit(FooterMenu $footerMenu)
    {
        return view('admin.footer-menus.edit', compact('footerMenu'));
    }

    public function update(Request $request, FooterMenu $footerMenu)
    {
        $data = $request->validate([
            'title'     => 'required|string|max:100',
            'content'   => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['content']   = Purifier::clean($data['content'], 'footer_menu');

        $footerMenu->update($data);

        return redirect()->route('admin.footer-menus.index')
            ->with('success', 'Footer menu updated.');
    }

    public function destroy(FooterMenu $footerMenu)
    {
        $footerMenu->delete();

        return redirect()->route('admin.footer-menus.index')
            ->with('success', 'Footer menu deleted.');
    }

    public function reorder(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        // One UPDATE for the whole order, not one per row. No `exists:` rule: the id
        // is a SELECTOR, never a written value, so an unknown id simply matches
        // nothing and the rest are still reordered — one stale id in a drag-and-drop
        // does not abort the lot. The CASE is built from ints cast by intval, never
        // from request strings.
        $ids = array_values(array_filter(array_map('intval', (array) $request->ids)));

        if (! $ids) {
            return response()->json(['ok' => true]);
        }

        $cases = collect($ids)
            ->map(fn ($id, $order) => "when {$id} then {$order}")
            ->implode(' ');

        FooterMenu::whereIn('id', $ids)
            ->update(['sort_order' => DB::raw("case id {$cases} end")]);

        return response()->json(['ok' => true]);
    }
}
