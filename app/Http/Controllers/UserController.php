<?php

namespace App\Http\Controllers;

use Silber\Bouncer\Bouncer;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->isAn('admin')) {
            return redirect()->back()->withErrors(['error' => 'Вы не администратор']);
        }

        $users = User::where('id', '!=', $user->id)->with('roles')->paginate(50);

        // Преобразуем данные для передачи
        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'roles' => $user->roles->pluck('name')->toArray(),
            ];
        });

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user();

        if (!$currentUser->isAn('admin') || $currentUser->director_id != null) {
            return redirect()->back()->withErrors(['error' => 'Вы не администратор']);
        }

        // Проверка, что пользователь, которого пытаются удалить, не является администратором
        if ($user->isAn('admin')) {
            return redirect()->back()->withErrors(['error' => 'Нельзя удалить администратора']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Пользователь успешно удален');
    }
}
