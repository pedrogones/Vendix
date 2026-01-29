<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
//        dd(auth()->user()->name ?? null);
        return view('portal.home');
    }
}
