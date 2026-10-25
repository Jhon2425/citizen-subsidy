<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use Illuminate\Http\Request;

class LoginHistoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $histories = LoginHistory::with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
            })
            ->latest('logged_in_at')
            ->paginate(15)
            ->withQueryString();

        return view('login-history.index', compact('histories', 'search'));
    }
}