<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManageUserSupportController extends Controller
{
    public function index()
    {
        return Inertia::render('User/Support/Index');
    }

    public function privacy()
    {
        return Inertia::render('User/Support/Privacy');
    }

    public function terms()
    {
        return Inertia::render('User/Support/Terms');
    }

    public function aml()
    {
        return Inertia::render('User/Support/Aml');
    }
}
