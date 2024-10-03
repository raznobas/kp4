<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\LeadAppointment;
use App\Traits\TranslatableAttributes;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;

class LeadController extends Controller
{
    use AuthorizesRequests;
    use TranslatableAttributes;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }
    public function index(Request $request)
    {
        $this->authorize('manage-leads');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $leadsPage = $request->input('page', 1);
        $leadAppointmentsPage = $request->input('page_appointments', 1);

        $leads = Client::where('director_id', auth()->user()->director_id)
            ->where('is_lead', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page', $leadsPage);

        $leadAppointments = LeadAppointment::where('director_id', auth()->user()->director_id)
            ->where('status', 'scheduled')
            ->orderBy('training_date', 'desc')
            ->paginate(15, ['*'], 'page_appointments', $leadAppointmentsPage);

        $categories = Category::where('director_id', auth()->user()->director_id)->get();

        return Inertia::render('Leads/Index', [
            'categories' => $categories,
            'leads' => $leads,
            'leadAppointments' => $leadAppointments,
        ]);
    }

    // сохранение записи лида на пробную тренировку
    public function store(Request $request)
    {
        $this->authorize('manage-leads');

        $today = now()->toDateString();
        $attributes = $this->getTranslatableAttributes();

        $validated = $request->validate([
            'sale_date' => [
                'required',
                'date',
                'after_or_equal:' . $today,
            ],
            'client_id' => 'required|exists:clients,id',
            'director_id' => 'required|exists:users,id',
            'sport_type' => 'nullable|exists:categories,name',
            'service_type' => 'nullable|in:trial,group,minigroup,individual,split',
            'trainer' => 'nullable',
            'training_date' => [
                'required',
                'date',
                'after_or_equal:' . $today,
            ],
            'training_time' => 'nullable',
            'status' => 'nullable|in:scheduled,cancelled,completed,no_show',
        ], [], $attributes);

       LeadAppointment::create($validated);

        return redirect()->back();
    }

}
