<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class HomeController extends Controller
{
    public function index(Request $request, string $locale = 'en')
    {
        // Validate and set locale
        if (! in_array($locale, ['en', 'nl'])) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return view('home');
    }
}
