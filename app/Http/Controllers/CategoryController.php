<?php

namespace App\Http\Controllers;

use App\Models\Category;
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
        return Inertia::render('Director/Categories/Index', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-categories');

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ]);

        // Логирование для отладки
        \Log::info('Received type:', ['type' => $request->type]);

        Category::create([
            'director_id' => auth()->user()->id,
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect()->route('categories.index');
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
}
