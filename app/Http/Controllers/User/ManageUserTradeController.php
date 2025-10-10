<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ManageUserTradeController extends Controller
{
    public function index()
    {
        return Inertia::render('User/Trade');
    }
}
