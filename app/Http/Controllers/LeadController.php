<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\LeadAppointment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function index()
    {
        $leads = Client::where('director_id', auth()->user()->id)
            ->where('is_lead', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        $categories = Category::where('director_id', auth()->user()->id)->get();

        return Inertia::render('Leads/Index', [
            'categories' => $categories,
            'leads' => $leads,
        ]);
    }

    // сохранение записи лида на тренировку
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'sport_type' => 'nullable|exists:categories,name',
            'service_type' => 'nullable|in:trial,group,minigroup,individual,split',
            'trainer' => 'nullable',
            'training_date' => 'nullable|date',
            'training_time' => 'nullable|time',
            'status' => 'nullable|in:scheduled,cancelled,completed,no_show',
        ]);

        LeadAppointment::create($validated);

        return redirect()->back();
    }

}
