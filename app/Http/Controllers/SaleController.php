<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryCost;
use App\Models\CategoryCostAdditional;
use App\Models\Client;
use App\Models\Sale;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Silber\Bouncer\Bouncer;

class SaleController extends Controller
{
    use AuthorizesRequests;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    public function index()
    {
        $this->authorize('manage-sales');

        $categories = Category::where('director_id', auth()->user()->id)->get();

        // Получаем все настройки стоимости
        $categoryCosts = CategoryCost::with('additionalCosts')->get();

        return Inertia::render('Sales', [
            'categories' => $categories,
            'categoryCosts' => $categoryCosts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
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

        $client = Client::find($validated['client_id']);

        // Если покупку сделал лид, тогда меняем статус аккаунта на клиента.
        if ($client->is_lead) {
            $client->is_lead = false;
            $client->save();
        }

        Sale::create($validated);

        return redirect()->back();
    }
    public function show($client_id)
    {
        $clientSales = Sale::where('client_id', $client_id)->get();

        return response()->json($clientSales);
    }
}
