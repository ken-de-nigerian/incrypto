<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminWalletConnectController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $userQuery = User::query()
            ->with(['profile', 'wallets'])
            ->whereHas('wallets');

        if ($search) {
            $userQuery->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        switch ($sortBy) {
            case 'name':
                $userQuery->orderBy('first_name', $sortOrder);
                break;
            case 'date':
            default:
                $userQuery->orderBy('created_at', $sortOrder === 'asc' ? 'asc' : 'desc');
                break;
        }

        $users = $userQuery->paginate(8)->withQueryString();

        return Inertia::render('Admin/Wallet', [
            'users' => $users,
        ]);
    }
}
