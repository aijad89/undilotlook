<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\DB;

class SocialLinkController extends Controller
{
    public function index()
    {
        $links = SocialLink::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.social-links.index', compact('links'));
    }

    public function create()
    {
        $presets = SocialLink::PRESETS;

        return view('admin.social-links.create', compact('presets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'           => 'required|string|max:60',
            'url'             => 'required|url|max:500',
            'icon_type'       => 'required|in:bootstrap,image',
            'icon_class'      => 'nullable|string|max:60',
            'icon_image'      => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'icon_image_data' => 'nullable|string',
            'is_active'       => 'nullable|boolean',
        ]);

        if ($data['icon_type'] === 'bootstrap') {
            $data['icon_class'] = $request->input('icon_class') ?: 'bi-globe';
            $data['icon_image'] = null;
        } else {
            $data['icon_class'] = null;
            $data['icon_image'] = $this->resolveIconUpload($request);
        }

        $data['is_active']  = $request->boolean('is_active');

        // Not a column. `icon_image_data` is the hidden field the Cropper.js editor
        // posts its base64 in (social-links/_form.blade.php:120, always submitted,
        // empty or not), and resolveIconUpload() has already taken it off the
        // REQUEST — it was never wanted here. Left in $data it reaches the model as
        // an unfillable attribute, which Laravel silently discards in production and
        // throws on everywhere else: AppServiceProvider gates
        // preventSilentlyDiscardingAttributes() to non-production on purpose. So
        // creating or editing a social link 500s on every dev machine and in the e2e
        // suite, and quietly works in production — which is why nothing caught it.
        unset($data['icon_image_data']);

        SocialLink::query()->increment('sort_order');
        $data['sort_order'] = 0;

        SocialLink::create($data);

        return redirect()->route('admin.social-links.index')
            ->with('success', 'Social link created.');
    }

    public function edit(SocialLink $socialLink)
    {
        $presets = SocialLink::PRESETS;

        return view('admin.social-links.edit', compact('socialLink', 'presets'));
    }

    public function update(Request $request, SocialLink $socialLink)
    {
        $data = $request->validate([
            'label'           => 'required|string|max:60',
            'url'             => 'required|url|max:500',
            'icon_type'       => 'required|in:bootstrap,image',
            'icon_class'      => 'nullable|string|max:60',
            'icon_image'      => 'nullable|file|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'icon_image_data' => 'nullable|string',
            'is_active'       => 'nullable|boolean',
        ]);

        if ($data['icon_type'] === 'bootstrap') {
            $data['icon_class'] = $request->input('icon_class') ?: 'bi-globe';
            if ($socialLink->icon_image) {
                Storage::disk('public')->delete($socialLink->icon_image);
            }
            $data['icon_image'] = null;
        } else {
            $data['icon_class'] = null;
            $newImage = $this->resolveIconUpload($request);
            if ($newImage !== null) {
                if ($socialLink->icon_image) {
                    Storage::disk('public')->delete($socialLink->icon_image);
                }
                $data['icon_image'] = $newImage;
            } else {
                // No new upload — keep existing
                unset($data['icon_image']);
            }
        }

        $data['is_active'] = $request->boolean('is_active');

        // Same non-column as store() — see the note there.
        unset($data['icon_image_data']);

        $socialLink->update($data);

        return redirect()->route('admin.social-links.index')
            ->with('success', 'Social link updated.');
    }

    public function destroy(SocialLink $socialLink)
    {
        if ($socialLink->icon_image) {
            Storage::disk('public')->delete($socialLink->icon_image);
        }

        $socialLink->delete();

        return redirect()->route('admin.social-links.index')
            ->with('success', 'Social link deleted.');
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

        SocialLink::whereIn('id', $ids)
            ->update(['sort_order' => DB::raw("case id {$cases} end")]);

        return response()->json(['ok' => true]);
    }

    // ── Private ───────────────────────────────────────────

    /**
     * Resolve icon from either base64 editor output or raw file upload.
     * Returns storage path or null if nothing was submitted.
     */
    private function resolveIconUpload($request): ?string
    {
        // 1. Base64 from editor (cropped raster)
        $base64 = $request->input('icon_image_data');
        // Raster formats only — the same set the file rule allows. An SVG data
        // URL would reach the decoder and fail as a 500, not a refusal.
        if ($base64 && preg_match('/^data:image\/(png|jpe?g|webp|gif);base64,/', $base64)) {
            return $this->processBase64($base64);
        }

        // 2. Raw file upload (SVG bypasses editor)
        if ($request->hasFile('icon_image')) {
            return $this->processFile($request->file('icon_image'));
        }

        return null;
    }

    private function processBase64(string $base64): string
    {
        // Strip data URI prefix: data:image/webp;base64,...
        $raw      = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));
        $manager  = new ImageManager(new Driver());
        $image    = $manager->read($raw);
        $image->resize(16, 24);

        $encoded  = $image->toWebp(90);
        $filename = 'social-icons/' . uniqid('icon_', true) . '.webp';

        Storage::disk('public')->put($filename, $encoded);

        return $filename;
    }

    private function processFile($file): string
    {

        // Raster without editor — resize to 16×24 WebP
        $manager = new ImageManager(new Driver());
        $image   = $manager->read($file->getRealPath());
        $image->resize(16, 24);

        $encoded  = $image->toWebp(90);
        $filename = 'social-icons/' . uniqid('icon_', true) . '.webp';

        Storage::disk('public')->put($filename, $encoded);

        return $filename;
    }
}
