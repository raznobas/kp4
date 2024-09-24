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
    public function index(Request $request)
    {
        $this->authorize('manage-tasks');

        if (auth()->user()->director_id === null) {
            return false;
        }

        $tasksPage = $request->input('page', 1);
        $noShowLeadsPage = $request->input('page_no_show_leads', 1);

        $tasks = Task::with(['client:id,surname,name,birthdate,phone,email', 'userSender:id,name'])
            ->where('director_id', auth()->user()->director_id)
            ->orderBy('task_date')
            ->paginate(15, ['*'], 'page', $tasksPage);

        $noShowLeads = LeadAppointment::with(['client:id,surname,name,birthdate,phone,email'])
            ->where('director_id', auth()->user()->director_id)
            ->where('status', 'no_show')
            ->orderBy('training_date')
            ->paginate(15, ['*'], 'page_no_show_leads', $noShowLeadsPage);

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
            'director_id' => 'required|exists:users,id',
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

        $tasks = Task::with('userSender:id,name')
            ->where('client_id', $client_id)
            ->where('director_id', auth()->user()->director_id)
            ->orderBy('task_date', 'asc')
            ->get();

        return response()->json($tasks);
    }

    public function destroy(Task $task)
    {
        $this->authorize('manage-tasks');

        if ($task->director_id !== auth()->user()->director_id) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на удаление этой задачи.']);
        }
        $task->delete();

        return redirect()->back();
    }
}
