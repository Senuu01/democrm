<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Proposal;
use App\Models\Invoice;
use App\Models\Activity;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }
} 