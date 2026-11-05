<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyKraf;
use App\Models\KrafBidang;
use App\Models\KrafPengelasan;
use App\Models\KrafProduk;
use App\Models\KrafSubBidang;
use App\Models\User;
use App\Support\AdminZones;
use App\Support\EntrepreneurCategories;
use App\Support\KrafPicker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Companies, each with the one account that signs in for it.
 *
 * ONE COMPANY, ONE USER, AND THE FORM MAKES BOTH. `companies.user_id` is NOT
 * NULL and carries a unique index, so there is no company without an account and
 * no account with two companies. The admin fills one form and two rows are
 * written inside one transaction — the owner's name, email, phone and IC are
 * columns on the USER, not on the company.
 *
 * NOTHING POSTS `user_id`, SO NOTHING VALIDATES IT. The account is always created
 * here rather than picked, which is what makes the unique index unreachable from
 * this screen; a `Rule::unique('companies', 'user_id')` would be a rule that can
 * never fire. What the admin can collide with is an EMAIL that already has an
 * account, and that is validated against `users` with a message they can read.
 * If a "link an existing account" path is ever added, the rule comes back with it.
 *
 * THE SCOPE IS THE ZONE. A state admin sees and edits companies whose
 * `companies.state` equals their own `admins.state`, and reaches no other by
 * typing a URL. There is no middleware that looks like this guard, deliberately:
 * one would read as enforcement on every route and enforce nothing.
 */
class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user('admin');

        $companies = Company::query()
            ->with('user')
            ->withCount('eventRegistrations')
            ->when($request->input('search'), fn ($q, $search) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
            ))
            ->when($request->input('state'), fn ($q, $state) => $q->where('state', $state))
            ->unless($admin->hasGlobalAccess(), fn ($q) => $q->where('state', $admin->state))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.companies.index', [
            'companies' => $companies,
            'canFilterZone' => $admin->hasGlobalAccess(),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.companies.create', [
            'company' => new Company,
            'zones' => $this->zonesFor($request->user('admin')),
        ]);
    }

    private const IC_MESSAGES = [
        'ic_number.regex' => 'An IC number has twelve digits, written 990101-01-1234.',
        'ic_number.unique' => 'Another account already holds that IC number.',
    ];

    /**
     * Twelve bare digits are the commonest way an IC is typed, and the stored
     * form is dashed — icHash() and the unique index both compare the string,
     * so one IC in two spellings would be two people. Dashes go in here; anything
     * that is not twelve digits is left for the rule to refuse.
     */
    private function normaliseIc(Request $request): void
    {
        $digits = preg_replace('/\D/', '', (string) $request->input('ic_number'));

        if (strlen($digits) === 12) {
            $request->merge(['ic_number' => substr($digits, 0, 6).'-'.substr($digits, 6, 2).'-'.substr($digits, 8)]);
        }
    }

    public function store(Request $request)
    {
        $admin = $request->user('admin');

        $this->normaliseIc($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'state' => ['required', Rule::in($this->zonesFor($admin))],
            'entrepreneur_category' => ['nullable', EntrepreneurCategories::rule()],
            'is_active' => ['nullable', 'boolean'],

            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'ic_number' => ['nullable', 'string', 'regex:/^\d{6}-\d{2}-\d{4}$/', 'unique:users,ic_number'],
            // Optional. Blank means the office is not handing a password over in
            // person and the owner will set one through the reset flow.
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'account_active' => ['nullable', 'boolean'],
        ], self::IC_MESSAGES);

        // One act, or neither. Without the transaction a failing company insert
        // leaves a user behind with no company and no screen that lists them.
        $company = DB::transaction(function () use ($request, $data) {
            $user = User::create([
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'ic_number' => $data['ic_number'] ?? null,
                'is_active' => $request->boolean('account_active', true),
                // A random password when none was typed, NOT a default one: a
                // fallback password in tracked code is a password in git history
                // from the day it was written. The owner sets a real one through
                // the reset flow, which applies the same floor as the field above.
                'password' => bcrypt($data['password'] ?? Str::random(32)),
            ]);

            return $user->company()->create([
                'name' => $data['name'],
                'state' => $data['state'],
                'entrepreneur_category' => $data['entrepreneur_category'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('admin.companies.edit', $company)
            ->with('success', "{$company->name} created, with an account for {$company->user->email}.");
    }

    public function edit(Request $request, Company $company)
    {
        $this->authorizeScope($request, $company);

        $company->load('user');

        return view('admin.companies.edit', [
            'company' => $company,
            'zones' => $this->zonesFor($request->user('admin')),
            'picker' => KrafPicker::data($company),
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $this->authorizeScope($request, $company);

        $user = $company->user;

        $this->normaliseIc($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'state' => ['required', Rule::in($this->zonesFor($request->user('admin')))],
            'entrepreneur_category' => ['nullable', EntrepreneurCategories::rule()],

            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'ic_number' => [
                'nullable', 'string', 'regex:/^\d{6}-\d{2}-\d{4}$/',
                Rule::unique('users', 'ic_number')->ignore($user->id),
            ],
            // Left blank on every ordinary save. Filled only when the office is
            // resetting the password for someone who cannot do it themselves.
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], self::IC_MESSAGES);

        DB::transaction(function () use ($request, $data, $company, $user) {
            $user->update([
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'ic_number' => $data['ic_number'] ?? null,
                // Two switches, two facts. A company switched off is off the
                // books; an account switched off cannot sign in, and
                // EnforceUserIsActive ends the session of anyone already in.
                'is_active' => $request->boolean('account_active'),
            ]);

            if (! empty($data['password'])) {
                $user->update(['password' => bcrypt($data['password'])]);
            }

            $company->update([
                'name' => $data['name'],
                'state' => $data['state'],
                'entrepreneur_category' => $data['entrepreneur_category'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);
        });

        return redirect()->route('admin.companies.edit', $company)
            ->with('success', 'Saved.');
    }

    /**
     * The company goes, and its account with it.
     *
     * `companies.user_id` is RESTRICT, so the order is forced: the company first,
     * then the user that now owns nothing. The model refuses outright if this
     * company holds a lot in a draw that has already run — that refusal is a
     * ValidationException, and it is caught here so the admin reads a sentence
     * rather than a stack trace.
     */
    public function destroy(Request $request, Company $company)
    {
        $this->authorizeScope($request, $company);

        $name = $company->name;

        try {
            DB::transaction(function () use ($company) {
                $user = $company->user;

                $company->delete();
                $user?->delete();
            });
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return redirect()->route('admin.companies.index')
            ->with('success', "{$name} deleted, with its account.");
    }

    /**
     * Add a craft selection.
     *
     * HASIL LOGAM is picked several sub-categories at a time and stored as one
     * row each, which is why the input is always an array. Every sub-category is
     * checked to belong to the category named beside it: the two columns describe
     * one path down the hierarchy, and a pair that does not is a row no screen
     * can draw.
     */
    public function storeKraf(Request $request, Company $company)
    {
        $this->authorizeScope($request, $company);

        $data = $request->validate([
            'bidang_id' => ['required', Rule::exists('kraf_bidangs', 'id')->whereNull('code')],
            'sub_bidang_id' => ['required', 'array', 'min:1'],
            'sub_bidang_id.*' => ['required', 'integer'],
            'is_primary' => ['nullable', 'boolean'],
            'pengelasan_id' => ['nullable', 'array'],
            'pengelasan_id.*' => ['integer'],
            'produk_id' => ['nullable', 'array'],
            'produk_id.*' => ['integer'],
        ]);

        $category = KrafBidang::findOrFail($data['bidang_id']);

        $subIds = KrafSubBidang::whereIn('id', $data['sub_bidang_id'])
            ->where('bidang_id', $category->id)
            ->pluck('id');

        if ($subIds->isEmpty()) {
            return $this->backTo('craft-categories')->with('error', 'Pick a sub-category that belongs to that craft category.');
        }

        // One at a time unless the category says otherwise, so a stray second
        // value cannot quietly become a second selection.
        if ($category->name !== KrafPicker::MULTI_SUB_CATEGORY) {
            $subIds = $subIds->take(1);
        }

        // Classifications and products ticked, both optional, grouped under the
        // sub-category each belongs to — so one ticked under another sub-category,
        // or a product not placed in any, matches none and is simply not taken.
        $ticked = KrafPengelasan::whereIn('id', $data['pengelasan_id'] ?? [])->get()->groupBy('sub_bidang_id');

        $tickedProduks = KrafProduk::whereIn('id', $data['produk_id'] ?? [])->get()->groupBy('sub_bidang_id');

        $makePrimary = $request->boolean('is_primary') || $company->krafSelections()->doesntExist();

        DB::transaction(function () use ($company, $category, $subIds, $makePrimary, $ticked, $tickedProduks) {
            if ($makePrimary) {
                // Through the model, not a mass update: a query-builder write
                // fires no events and would leave the audit trail silent.
                $company->krafSelections()->where('is_primary', true)->get()
                    ->each->update(['is_primary' => false]);
            }

            foreach ($subIds as $index => $subId) {
                $selection = CompanyKraf::create([
                    'company_id' => $company->id,
                    'bidang_id' => $category->id,
                    'sub_bidang_id' => $subId,
                    'is_primary' => $makePrimary && $index === 0,
                ]);
                $selection->pengelasan()->attach($ticked->get($subId, collect())->pluck('id'));
                $selection->produks()->attach($tickedProduks->get($subId, collect())->pluck('id'));
            }
        });

        return $this->backTo('craft-categories')->with('success', 'Craft selection added.');
    }

    public function destroyKraf(Request $request, Company $company, CompanyKraf $selection)
    {
        $this->authorizeScope($request, $company);

        abort_unless($selection->company_id === $company->id, 404);

        // Its ticked classifications and products go with it, by cascade.
        $selection->delete();

        return $this->backTo('craft-categories')->with('success', 'Craft selection removed.');
    }

    /**
     * A state admin reaches their own zone and nothing else, whatever URL they
     * type. Super and HQ reach everything.
     */
    private function authorizeScope(Request $request, Company $company): void
    {
        $admin = $request->user('admin');

        abort_if(
            ! $admin->hasGlobalAccess() && $company->state !== $admin->state,
            403,
            'That company is administered by another zone.'
        );
    }

    /**
     * The zones this admin may file a company under.
     *
     * A state admin gets exactly one — their own — so the form offers no way to
     * post a company into a zone they cannot then see, and the validation rule is
     * built from the same list the form renders.
     *
     * @return array<int, string>
     */
    private function zonesFor($admin): array
    {
        return $admin->hasGlobalAccess()
            ? AdminZones::all()
            : array_values(array_filter([$admin->state]));
    }
}
