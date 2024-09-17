<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\LeadAppointment;
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
            ->paginate(15);

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

        $clients = Client::select('id', 'name', 'surname', 'patronymic', 'phone', 'ad_source', 'is_lead')
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

    public function renewals()
    {
        $currentDate = now();

        // Получаем клиентов с абонементами от 1 месяца и больше
        $longTermSubscriptions = Client::where('director_id', auth()->user()->id)
            ->where('is_lead', false)
            ->whereHas('sales', function ($query) use ($currentDate) {
                $query->whereIn('service_type', ['group', 'minigroup'])
                    ->where('subscription_duration', '>=', 1)
                    ->where('subscription_end_date', '>', $currentDate);
            })
            ->with(['sales' => function ($query) use ($currentDate) {
                $query->select('client_id', 'subscription_end_date', 'service_type')
                    ->where('subscription_duration', '>=', 1)
                    ->where('subscription_end_date', '>', $currentDate);
            }])
            ->select('id', 'surname', 'name', 'birthdate', 'phone', 'email')
            ->get();

        // Получаем клиентов, у которых заканчивается абонемент в течение недели
        $expiringSubscriptions = Client::where('director_id', auth()->user()->id)
            ->where('is_lead', false)
            ->whereHas('sales', function ($query) use ($currentDate) {
                $query->whereIn('service_type', ['group', 'minigroup'])
                    ->where('subscription_end_date', '<=', (clone $currentDate)->addDays(7))
                    ->where('subscription_end_date', '>=', $currentDate);
            })
            ->with(['sales' => function ($query) use ($currentDate) {
                $query->select('client_id', 'subscription_end_date', 'service_type')
                    ->where('subscription_end_date', '<=', (clone $currentDate)->addDays(7))
                    ->where('subscription_end_date', '>=', $currentDate);
            }])
            ->select('id', 'surname', 'name', 'birthdate', 'phone', 'email')
            ->get();

        // Получаем клиентов, у которых абонемент закончился в течение последнего месяца
        $recentlyExpiredSubscriptions = Client::where('director_id', auth()->user()->id)
            ->where('is_lead', false)
            ->whereHas('sales', function ($query) use ($currentDate) {
                $query->whereIn('service_type', ['group', 'minigroup'])
                    ->where('subscription_end_date', '>=', (clone $currentDate)->subMonth()->startOfDay())
                    ->where('subscription_end_date', '<', $currentDate->startOfDay());
            })
            ->with(['sales' => function ($query) use ($currentDate) {
                $query->select('client_id', 'subscription_end_date', 'service_type')
                    ->where('subscription_end_date', '>=', (clone $currentDate)->subMonth()->startOfDay())
                    ->where('subscription_end_date', '<', $currentDate->startOfDay());
            }])
            ->select('id', 'surname', 'name', 'birthdate', 'phone', 'email')
            ->get();

        // Объединяем результаты и удаляем дубликаты
        $clientsToRenewal = $longTermSubscriptions->merge($expiringSubscriptions)->merge($recentlyExpiredSubscriptions)->unique();

        // Добавляем поля из sales к каждому клиенту
        $clientsToRenewal->each(function ($client) {
            $client->subscription_end_date = $client->sales->first()->subscription_end_date ?? null;
            $client->service_type = $client->sales->first()->service_type ?? null;
        });

        // Пагинация на стороне сервера
        $perPage = 15;
        $currentPage = request()->input('page', 1);
        $paginatedClients = new \Illuminate\Pagination\LengthAwarePaginator(
            $clientsToRenewal->forPage($currentPage, $perPage),
            $clientsToRenewal->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return Inertia::render('Clients/Renewals', [
            'clientsToRenewal' => $paginatedClients,
        ]);
    }

    public function old()
    {
        $currentDate = now();

        // Получаем клиентов, у которых абонемент закончился более месяца назад (старые клиенты)
        $oldClients = Client::where('director_id', auth()->user()->id)
            ->where('is_lead', false)
            ->whereHas('sales', function ($query) use ($currentDate) {
                $query->where('subscription_end_date', '<', (clone $currentDate)->subMonth()->startOfDay());
            })
            ->with(['sales' => function ($query) use ($currentDate) {
                $query->select('client_id', 'subscription_end_date', 'service_type')
                    ->where('subscription_end_date', '<', (clone $currentDate)->subMonth()->startOfDay());
            }])
            ->select('id', 'surname', 'name', 'birthdate', 'phone', 'email')
            ->get();

        // Добавляем поля из sales к каждому клиенту
        $oldClients->each(function ($client) {
            $client->subscription_end_date = $client->sales->first()->subscription_end_date ?? null;
        });

        // Пагинация на стороне сервера
        $perPage = 15;
        $currentPage = request()->input('page', 1);
        $paginatedClients = new \Illuminate\Pagination\LengthAwarePaginator(
            $oldClients->forPage($currentPage, $perPage),
            $oldClients->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return Inertia::render('Clients/Old', [
            'oldClients' => $paginatedClients,
        ]);
    }

    public function trials()
    {
        $currentDate = now();
        $oneMonthAgo = $currentDate->subMonth();

        // Получаем все пробные тренировки, которые были более месяца назад
        $trials = LeadAppointment::where('training_date', '<', $oneMonthAgo)
//            ->where('status', '!=', 'completed') а надо ли проверять статус?
            ->get();

        // Получаем уникальные client_id из этих пробных тренировок
        $clientIds = $trials->pluck('client_id')->unique();

        // Получаем клиентов, у которых нет активного абонемента
        $trialClients = Client::whereIn('id', $clientIds)
            ->whereDoesntHave('sales', function ($query) use ($currentDate) {
                $query->where('subscription_end_date', '>', $currentDate);
            })
            ->select('id', 'surname', 'name', 'birthdate', 'phone', 'email')
            ->paginate(15);

        // Получаем training_date для каждого клиента
        $trialClients->each(function ($client) use ($trials) {
            $client->training_date = $trials->where('client_id', $client->id)->first()->training_date ?? null;
        });

        return Inertia::render('Clients/Trials', [
            'trialClients' => $trialClients,
        ]);
    }

    public function getSourceOptions()
    {
        $source_options = Category::where('director_id', auth()->user()->id)
            ->where('type', 'ad_source')
            ->get();

        return response()->json($source_options);
    }
}
