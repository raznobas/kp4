<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function index()
    {
        $leads = Client::where('director_id', auth()->user()->id)
            ->where('is_lead', true)
            ->orderBy('created_at', 'desc')
            ->get();
        $categories = Category::where('director_id', auth()->user()->id)->get();

        return Inertia::render('Leads/Index', [
            'categories' => $categories,
            'leads' => $leads,
        ]);
    }

}
