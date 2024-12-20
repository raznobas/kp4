<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryCost;
use App\Models\CategoryCostAdditional;
use App\Models\Client;
use App\Models\LeadAppointment;
use App\Models\Sale;
use DateTime;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;
use App\Traits\TranslatableAttributes;

class SaleController extends Controller
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
        $sales = Sale::where('director_id', auth()->user()->director_id)
            ->with(['client:id,name,surname'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $categories = Category::where('director_id', auth()->user()->director_id)->get();

        // Получаем все настройки стоимости
        $categoryCosts = CategoryCost::with('additionalCosts')->get();

        return Inertia::render('Sales', [
            'categories' => $categories,
            'categoryCosts' => $categoryCosts,
            'sales' => $sales
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-sales');

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
            'service_or_product' => 'required|in:service,product',
            'sport_type' => 'nullable|exists:categories,name',
            'service_type' => 'nullable|in:trial,group,minigroup,individual,split',
            'product_type' => 'nullable|exists:categories,name',
            'subscription_duration' => 'nullable|exists:categories,name',
            'visits_per_week' => 'nullable|exists:categories,name',
            'training_count' => 'nullable|exists:categories,name',
            'trainer_category' => 'nullable|exists:categories,name',
            'trainer' => 'nullable|exists:categories,name',
            'subscription_start_date' => [
                'nullable',
                'date',
                'after_or_equal:' . $today,
            ],
            'subscription_end_date' => [
                'nullable',
                'date',
                'after_or_equal:' . $today,
            ],
            'cost' => 'required|numeric|min:1',
            'paid_amount' => 'nullable|numeric|min:1',
            'pay_method' => 'nullable|exists:categories,name',
        ], [], $attributes);

        $client = Client::find($validated['client_id']);

        // DateTime для корректного сравнения
        $startDate = new DateTime($validated['subscription_start_date']);
        $endDate = new DateTime($validated['subscription_end_date']);

        // Дата окончания не должна быть раньше даты начала
        if ($endDate < $startDate) {
            return redirect()->back()->withErrors(['error' => 'Дата окончания не должна быть раньше даты начала']);
        }

        // Если покупку сделал лид, тогда меняем статус аккаунта на клиента.
        if ($client->is_lead) {
            $client->is_lead = false;
            $client->save();
        }

        // Если тренировка пробная, то ищем запись в таблице lead_appointments и меняем статус на completed.
        // Это случай, когда Лид пришел на записанную тренировку
        if ($validated['service_type'] === 'trial') {
            $leadAppointment = LeadAppointment::where('client_id', $validated['client_id'])->first();

            if ($leadAppointment) {
                $leadAppointment->status = 'completed';
                $leadAppointment->save();
            }
        }

        Sale::create($validated);

        return redirect()->back();
    }
    public function update(Request $request, $id)
    {
        $this->authorize('manage-sales');

        $validated = $request->validate([
            'sale_date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'director_id' => 'required|exists:users,id',
            'service_or_product' => 'required|in:service,product',
            'sport_type' => 'nullable|exists:categories,name',
            'service_type' => 'nullable|in:trial,group,minigroup,individual,split',
            'product_type' => 'nullable|exists:categories,name',
            'subscription_duration' => 'nullable|exists:categories,name',
            'visits_per_week' => 'nullable|exists:categories,name',
            'training_count' => 'nullable|exists:categories,name',
            'trainer_category' => 'nullable|exists:categories,name',
            'trainer' => 'nullable|exists:categories,name',
            'subscription_start_date' => 'nullable|date',
            'subscription_end_date' => 'nullable|date',
            'cost' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'pay_method' => 'nullable|exists:categories,name',
        ]);

        if ($validated['director_id'] != auth()->user()->director_id) {
            return redirect()->back()->withErrors(['director_id' => 'У вас нет прав на редактирование этой продажи.']);
        }

        $sale = Sale::where('id', $id)
            ->where('director_id', auth()->user()->director_id)
            ->firstOrFail();

        $client = Client::where('id', $validated['client_id'])
            ->where('director_id', auth()->user()->director_id)
            ->firstOrFail();

        // Если покупку сделал лид, тогда меняем статус аккаунта на клиента.
        if ($client->is_lead) {
            $client->is_lead = false;
            $client->save();
        }

        // Если тренировка пробная, то ищем запись в таблице lead_appointments и меняем статус на completed.
        // Это случай, когда Лид пришел на записанную тренировку
        if ($validated['service_type'] === 'trial') {
            $leadAppointment = LeadAppointment::where('client_id', $validated['client_id'])
                ->where('director_id', auth()->user()->director_id)
                ->first();

            if ($leadAppointment) {
                $leadAppointment->status = 'completed';
                $leadAppointment->save();
            }
        }

        // Обновляем данные продажи
        $sale->update($validated);

        return redirect()->back()->with('success', 'Продажа успешно обновлена!');
    }

    public function show($client_id)
    {
        $this->authorize('manage-sales');

        $clientSales = Sale::where('client_id', $client_id)
            ->orderBy('sale_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($clientSales);
    }

    public function destroy(Sale $sale)
    {
        $this->authorize('manage-sales');

        if ($sale->director_id != auth()->user()->director_id) {
            return redirect()->back()->withErrors(['error' => 'У вас нет прав на удаление этой задачи.']);
        }

        $sale->delete();

        return redirect()->back();
    }
}
