<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryCost;
use App\Models\CategoryCostAdditional;
use App\Models\Chirp;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Silber\Bouncer\Bouncer;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    protected $bouncer;

    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    public function index()
    {
        $this->authorize('manage-categories');

        $categories = Category::where('director_id', auth()->user()->id)->get();
        $categoryCosts = CategoryCost::with('additionalCosts')->get();

        return Inertia::render('Director/Categories/Index', [
            'categories' => $categories,
            'categoryCosts' => $categoryCosts,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-categories');

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ]);

        Category::create([
            'director_id' => auth()->user()->id,
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect()->route('categories.index');
    }
    public function storeCost(Request $request)
    {
        $this->authorize('manage-categories');

        $request->validate([
            'mainCategory.type' => 'required|string',
            'mainCategory.option' => 'required|string',
            'additionalCategories' => 'array',
            'additionalCategories.*.type' => 'required|string',
            'additionalCategories.*.option' => 'required|string',
            'cost' => 'required|numeric',
        ]);

        // Найти основную категорию
        $mainCategory = Category::where('name', $request->input('mainCategory.option'))
            ->where('type', $request->input('mainCategory.type'))
            ->firstOrFail();

        // Создать новую запись в таблице category_costs
        $categoryCost = CategoryCost::create([
            'main_category_id' => $mainCategory->id,
            'cost' => $request->input('cost'),
        ]);

        // Добавить дополнительные категории
        foreach ($request->input('additionalCategories') as $additionalCategory) {
            $additionalCategoryModel = Category::where('name', $additionalCategory['option'])
                ->where('type', $additionalCategory['type'])
                ->firstOrFail();

            CategoryCostAdditional::create([
                'category_cost_id' => $categoryCost->id,
                'additional_category_id' => $additionalCategoryModel->id,
            ]);
        }

        return redirect()->route('categories.index')->with('success', 'Стоимость категорий успешно сохранена.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('manage-categories');

        if ($category->director_id !== auth()->user()->id) {
            return redirect()->route('categories.index')->withErrors(['error' => 'У вас нет прав на удаление этой категории.']);
        }

        $category->delete();

        return redirect()->route('categories.index');
    }
    public function destroyCost($id)
    {
        $this->authorize('manage-categories');

        $categoryCost = CategoryCost::findOrFail($id);
        $categoryCost->delete();

        return redirect()->route('categories.index')->with('success', 'Сборка стоимости успешно удалена.');
    }
}
