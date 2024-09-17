<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with(['client:id,surname,name,birthdate,phone,email'])
            ->orderBy('task_date')
            ->paginate(15);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
        ]);
    }

    public function store(Request $request)
    {
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
        $tasks = Task::where('client_id', $client_id)
            ->orderBy('task_date', 'asc')
            ->get();

        return response()->json($tasks);
    }
}
