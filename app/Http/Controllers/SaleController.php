<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryCost;
use App\Models\CategoryCostAdditional;
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
        $this->authorize('manage-sales');

        $request->validate([
            'name' => 'required|string|max:255',
            'service_or_product' => 'required|string|max:255',
        ]);

        Category::create([
            'director_id' => auth()->user()->id,
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect()->route('categories.index');
    }
}
