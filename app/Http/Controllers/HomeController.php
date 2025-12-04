<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactUsRequest;
use App\Mail\ContactUsEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index(Request $request)
    {
        return Inertia::render('Home');
    }

    public function contact(ContactUsRequest $request)
    {
        Mail::mailer(config('settings.email_provider'))
            ->to(config('settings.site.site_email'))
            ->send(new ContactUsEmail($request->validated()));
    }
}
