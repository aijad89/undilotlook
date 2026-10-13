<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The admin landing screen.
     *
     * NOT OPTIONAL, even while it is almost empty: bootstrap/app.php's
     * `redirectUsersTo` sends every signed-in admin here, so this route existing
     * is what keeps an already-signed-in admin from being bounced to the
     * PARTICIPANT login form when they open /admin/login.
     *
     * PHASE 1: the shell. The counts arrive with the models they count — events,
     * companies, draw lists and polls — in phases 2 and 3.
     */
    public function index(Request $request)
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        return view('admin.dashboard', [
            'admin' => $admin,
        ]);
    }
}
