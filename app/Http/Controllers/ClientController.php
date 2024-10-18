<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Client;
use App\Models\LeadAppointment;
use App\Models\Sale;
use App\Traits\TranslatableAttributes;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;

class ClientController extends Controller
{
    use AuthorizesRequests;
    use TranslatableAttributes;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    public function index()
    {
        $this->authorize('manage-sales');
        if (auth()->user()->director_id === null) {
            return false;
        }

        $clients = Client::where('director_id', auth()->user()->director_id)
            ->where('is_lead', false)
            ->orderBy('created_at', 'desc')
            ->select('id', 'surname', 'name', 'patronymic', 'birthdate', 'phone', 'email')
            ->paginate(15);

        $source_options = Category::where('director_id', auth()->user()->director_id)
            ->where('type', 'ad_source')
            ->get();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'source_options' => $source_options
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-sales');

        $attributes = $this->getTranslatableAttributes();
        $validated = $request->validate([
            'surname' => 'nullable|string|max:255',
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
            'director_id' => 'required|exists:users,id',
        ], [], $attributes);

        Client::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $this->authorize('manage-sales');
        $validatedData = $request->validate([
            'surname' => 'nullable|string|max:255',
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

    public function destroy(Request $request, $id)
    {
        // Проверка роли пользователя
        $user = $request->user();
        if (!$user->isAn('admin') && !$user->isA('director')) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на удаление клиентов или лидов.']);
        }

        $client = Client::where('director_id', auth()->user()->director_id)->where('id', $id)->first();

        if (!$client) {
            return response()->json(['message' => 'Клиент не найден'], 404);
        }

        $client->delete();

        return redirect()->back()->with('success', 'Клиент/лид успешно удален.');
    }

    public function search(Request $request)
    {
        $this->authorize('manage-sales');
        $query = $request->input('query');
        $isLead = $request->input('is_lead'); // Параметр может быть передан или нет

        if (empty($query)) {
            return response()->json([]);
        }

        $clients = Client::select('id', 'name', 'surname', 'patronymic', 'phone', 'ad_source', 'is_lead')
            ->where('director_id', auth()->user()->director_id) // Ограничиваем поиск по director_id
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
        $this->authorize('manage-sales');

        $client = Client::findOrFail($id);

        return response()->json($client);
    }

    public function renewals(Request $request)
    {
        if (auth()->user()->director_id === null) {
            return false;
        }

        $currentDate = now();
        $filter = $request->input('filter', 'expired'); // По умолчанию фильтр
        $date = $request->input('date', 'asc'); // По умолчанию сортировка

        // Получаем все продажи с групповыми абонементами от 1 месяца и больше
        $salesQuery = Sale::whereHas('client', function ($query) {
            $query->where('director_id', auth()->user()->director_id)
                ->where('is_lead', false);
        })
            ->whereIn('service_type', ['group', 'minigroup'])
            ->where('subscription_duration', '>=', 1)
            ->where(function ($query) use ($currentDate, $filter) {
                if ($filter === 'upcoming') {
                    $query->where('subscription_end_date', '<=', $currentDate->copy()->addDays(7))
                        ->where('subscription_end_date', '>', $currentDate);
                } elseif ($filter === 'expired') {
                    $query->whereBetween('subscription_end_date', [
                        $currentDate->copy()->subMonth()->startOfDay(),
                        $currentDate->copy()->startOfDay()
                    ]);
                }
            })
            ->orderBy('subscription_end_date', $date) // Применяем сортировку
            ->with('client:id,surname,name,birthdate,phone,email');

        $sales = $salesQuery->get()
            ->groupBy('client.id')
            ->map(function ($sales) {
                return $sales->first();
            })
            ->values();

        // Фильтруем продажи по условиям
        $salesToRenewal = $sales->filter(function ($sale) use ($currentDate, $filter) {
            $subscriptionEndDate = $sale->subscription_end_date ? Carbon::parse($sale->subscription_end_date) : null;

            if ($subscriptionEndDate === null) {
                return false;
            }

            // Проверяем, есть ли у клиента хотя бы один действующий абонемент только для фильтра 'expired'
            if ($filter === 'expired') {
                $hasActiveSubscription = Sale::where('client_id', $sale->client->id)
                    ->where('subscription_end_date', '>', $currentDate)
                    ->exists();
                if ($hasActiveSubscription) {
                    return false;
                }
            }

            // Продажи, у которых заканчивается абонемент в течение недели
            if ($filter === 'upcoming' && $subscriptionEndDate->lte($currentDate->copy()->addDays(7)) && $subscriptionEndDate->gte($currentDate)) {
                return true;
            }

            // Продажи, у которых абонемент закончился в течение последнего месяца
            if ($filter === 'expired' && $subscriptionEndDate->gte($currentDate->copy()->subMonth()->startOfDay()) && $subscriptionEndDate->lt($currentDate->copy()->startOfDay())) {
                return true;
            }

            return false;
        });

        // Преобразуем данные для удобства использования в представлении
        $clientsToRenewal = $salesToRenewal->map(function ($sale) {
            return [
                'id' => $sale->client->id,
                'surname' => $sale->client->surname,
                'name' => $sale->client->name,
                'birthdate' => $sale->client->birthdate,
                'phone' => $sale->client->phone,
                'email' => $sale->client->email,
                'subscription_end_date' => $sale->subscription_end_date,
                'service_type' => $sale->service_type,
                'subscription_duration' => $sale->subscription_duration,
            ];
        });

        // Пагинация на стороне сервера
        $paginatedRenewals = $this->serverPaginate($clientsToRenewal);

        return Inertia::render('Clients/Renewals', [
            'clientsToRenewal' => $paginatedRenewals,
            'filter' => $filter,
            'date' => $date,
        ]);
    }

    public function old()
    {
        $this->authorize('manage-sales');
        if (auth()->user()->director_id === null) {
            return false;
        }

        $currentDate = now();

        // Получаем всех клиентов с абонементами, отсортированными по дате окончания
        $clients = Client::where('director_id', auth()->user()->director_id)
            ->where('is_lead', false)
            ->whereHas('sales', function ($query) use ($currentDate) {
                $query->whereIn('service_type', ['group', 'minigroup']);
            })
            ->with(['sales' => function ($query) use ($currentDate) {
                $query->select('client_id', 'subscription_end_date', 'service_type')
                    ->whereIn('service_type', ['group', 'minigroup'])
                    ->orderBy('subscription_end_date', 'desc')
                    ->limit(1);
            }])
            ->select('id', 'surname', 'name', 'birthdate', 'phone', 'email')
            ->get();

        // Фильтруем клиентов по условиям
        $oldClients = $clients->filter(function ($client) use ($currentDate) {
            $subscriptionEndDate = $client->sales->first()->subscription_end_date ?? null;

            if ($subscriptionEndDate === null) {
                return false;
            }
            // Проверяем, есть ли у клиента хотя бы один действующий абонемент
            $hasActiveSubscription = Sale::where('client_id', $client->id)
                ->where('subscription_end_date', '>', $currentDate)
                ->exists();
            if ($hasActiveSubscription) {
                return false;
            }

            // Клиенты, у которых абонемент закончился более месяца назад
            if ($subscriptionEndDate < (clone $currentDate)->subMonth()->startOfDay()) {
                return true;
            }

            return false;
        });

        // Добавляем поля из sales к каждому клиенту
        $oldClients->each(function ($client) {
            $client->subscription_end_date = $client->sales->first()->subscription_end_date ?? null;
        });

        // Пагинация на стороне сервера
        $paginatedClients = $this->serverPaginate($oldClients);

        return Inertia::render('Clients/Old', [
            'oldClients' => $paginatedClients,
        ]);
    }

    public function getSourceOptions()
    {
        $this->authorize('manage-sales');
        if (auth()->user()->director_id === null) {
            return false;
        }

        $source_options = Category::where('director_id', auth()->user()->director_id)
            ->where('type', 'ad_source')
            ->get();

        return response()->json($source_options);
    }

    private function serverPaginate($items)
    {
        $perPage = 15;
        $currentPage = request()->input('page', 1);
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($currentPage, $perPage),
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
