<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DocsController extends Controller
{
    public function api(): View
    {
        return view('docs.api');
    }
}
