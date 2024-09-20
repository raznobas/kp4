<?php

namespace App\Http\Controllers;

use App\Models\LeadAppointment;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;

class TaskController extends Controller
{
    use AuthorizesRequests;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }
    public function index()
    {
        $this->authorize('manage-tasks');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $tasks = Task::with(['client:id,surname,name,birthdate,phone,email'])
            ->orderBy('task_date')
            ->paginate(15);

        $noShowLeads = LeadAppointment::with(['client:id,surname,name,birthdate,phone,email'])
            ->where('status', 'no_show')
            ->orderBy('training_date')
            ->paginate(15);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'noShowLeads' => $noShowLeads,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-tasks');

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'user_sender_id' => 'required|exists:users,id',
            'task_date' => 'required|date',
            'task_description' => 'required|string',
        ]);

        Task::create($validated);

        return redirect()->back();
    }

    public function show($client_id)
    {
        $this->authorize('manage-tasks');

        $tasks = Task::where('client_id', $client_id)
            ->orderBy('task_date', 'asc')
            ->get();

        return response()->json($tasks);
    }
}
