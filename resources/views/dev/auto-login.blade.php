<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quick Login — local only</title>
    <style>
        body { margin: 0; padding: 28px 20px 60px; background: #F2F2F7; color: #1C1C1E;
               font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; }
        .wrap { max-width: 940px; margin: 0 auto; }
        .banner { background: #FEF3C7; border: 1px solid #FDE68A; color: #92400E; border-radius: 12px;
                  padding: 12px 16px; font-size: 0.82rem; margin-bottom: 22px; }
        h1 { font-size: 1.5rem; margin: 0 0 18px; }
        h2 { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; color: #6E6E73; margin: 26px 0 10px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 10px; }
        form { margin: 0; }
        /* The whole card is the submit button: one click, nothing to type. */
        .card { width: 100%; text-align: left; font: inherit; cursor: pointer; background: #fff;
                border: 1px solid rgba(60,60,67,0.12); border-radius: 12px; padding: 12px 14px; }
        .card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
        .card:focus-visible { outline: 2px solid #0066DD; outline-offset: 2px; }
        .email { display: block; font-size: 0.83rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .meta { display: block; font-size: 0.73rem; color: #6E6E73; margin-top: 2px; }
        .bar { display: flex; gap: 10px; align-items: center; font-size: 0.83rem; }
        .plain { font: inherit; font-size: 0.79rem; padding: 6px 14px; border-radius: 980px;
                 border: 1px solid rgba(60,60,67,0.12); background: #fff; cursor: pointer; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="banner">
        <strong>Local only.</strong> Every card signs in without a password. These routes do not exist outside
        <code>local</code> and <code>testing</code>.
    </div>

    <div class="bar">
        <h1 style="margin:0;flex:1;">Quick Login</h1>
        <form method="POST" action="{{ route('dev.auto.logout') }}">
            @csrf
            <button type="submit" class="plain">Sign everyone out</button>
        </form>
    </div>

    <h2>Admins</h2>
    <div class="grid">
        @forelse($admins as $admin)
            <form method="POST" action="{{ route('dev.auto.admin', $admin) }}">
                @csrf
                <button type="submit" class="card">
                    <span class="email">{{ $admin->email }}</span>
                    <span class="meta">{{ \App\Models\Admin::ROLES[$admin->role] ?? $admin->role }}@if($admin->state) · {{ $admin->state }}@endif</span>
                </button>
            </form>
        @empty
            <p>No admins. Run <code>php artisan db:seed</code>.</p>
        @endforelse
    </div>

    <h2>Participants</h2>
    <div class="grid">
        @forelse($users as $user)
            <form method="POST" action="{{ route('dev.auto.user', $user) }}">
                @csrf
                <button type="submit" class="card">
                    <span class="email">{{ $user->email }}</span>
                    <span class="meta">{{ $user->company?->name ?? 'No company' }}@unless($user->is_active) · inactive @endunless</span>
                </button>
            </form>
        @empty
            <p>No participants yet. Create a company from the admin Companies screen.</p>
        @endforelse
    </div>
</div>
</body>
</html>
