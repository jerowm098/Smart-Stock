<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the public homepage.
     * Accessible to both guests and authenticated users.
     * Passes auth state so the view can show Login vs user dropdown.
     */
    public function index(Request $request): View
    {
        return view('home', [
            'authUser' => Auth::user(), // null for guests
        ]);
    }
}
