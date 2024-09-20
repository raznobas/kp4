<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\LeadAppointment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;

class LeadController extends Controller
{
    use AuthorizesRequests;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }
    public function index()
    {
        $this->authorize('manage-leads');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $leads = Client::where('director_id', auth()->user()->director_id)
            ->where('is_lead', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        $categories = Category::where('director_id', auth()->user()->director_id)->get();

        return Inertia::render('Leads/Index', [
            'categories' => $categories,
            'leads' => $leads,
        ]);
    }

    // сохранение записи лида на тренировку
    public function store(Request $request)
    {
        $this->authorize('manage-leads');

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
