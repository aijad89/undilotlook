<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Back to the page, scrolled to the card the form was in.
     *
     * The flashed `anchor` tells that card to show the success/error message
     * itself — the page's own alerts sit at the top, out of sight once the
     * browser has jumped down.
     */
    protected function backTo(string $anchor): \Illuminate\Http\RedirectResponse
    {
        return back()->withFragment($anchor)->with('anchor', $anchor);
    }
}
