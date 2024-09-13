<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::where('director_id', auth()->user()->id)
            ->where('is_lead', false)
            ->orderBy('created_at', 'desc')
            ->select('id', 'surname', 'name', 'patronymic', 'birthdate', 'phone', 'email')
            ->get();

        $source_options = Category::where('director_id', auth()->user()->id)
            ->where('type', 'ad_source')
            ->get();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'source_options' => $source_options
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'surname' => 'string|max:255',
            'name' => 'required|string|max:255',
            'patronymic' => 'nullable|string|max:255',
            'birthdate' => 'nullable|date',
            'workplace' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telegram' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'ad_source' => 'nullable|string|max:255',
            'is_lead' => 'boolean',
            'director_id' => 'nullable|integer',
        ]);

        Client::create($validated);

        return redirect()->back();
    }
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'surname' => 'string|max:255',
            'name' => 'required|string|max:255',
            'patronymic' => 'nullable|string|max:255',
            'birthdate' => 'nullable|date',
            'workplace' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telegram' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'ad_source' => 'nullable|string|max:255',
        ]);

        $client = Client::findOrFail($id);
        $client->update($validatedData);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $isLead = $request->input('is_lead'); // Параметр может быть передан или нет

        if (empty($query)) {
            return response()->json([]);
        }

        $clients = Client::select('id', 'name', 'surname', 'patronymic', 'is_lead')
            ->when($isLead !== null, function ($q) use ($isLead) {
                return $q->where('is_lead', $isLead);
            })
            ->where(function ($q) use ($query) {
                $q->where('surname', 'like', "%$query%")
                    ->orWhere('name', 'like', "%$query%")
                    ->orWhere('patronymic', 'like', "%$query%");
            })
            ->get();

        return response()->json($clients);
    }

    public function show($id)
    {
        $client = Client::findOrFail($id);

        return response()->json($client);
    }
}
