<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the public homepage.
     * Accessible to both guests and authenticated users.
     * Passes auth state so the view can show Login vs user dropdown.
     *
     * The hero counters mirror the same aggregates rendered by the admin
     * dashboard (DashboardController::stats) so the landing page never
     * advertises hard-coded marketing numbers.
     */
    public function index(Request $request): View
    {
        return view('home', [
            'authUser' => Auth::user(), // null for guests
            'heroStats' => [
                'products'  => Product::count(),
                'suppliers' => Supplier::count(),
                'sales'     => Sale::count(),
            ],
        ]);
    }
}
