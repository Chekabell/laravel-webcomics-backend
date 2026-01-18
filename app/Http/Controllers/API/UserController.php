<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Обновление пароля пользователя.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password:sanctum',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $validated, $request) {
            $user->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            // Отзываем все другие токены для безопасности
            $user->tokens()->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    /**
     * Загрузка аватара пользователя.
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $request) {
            // Удаляем старое изображение если оно есть
            if ($user->image && Storage::disk('s3')->exists($user->image)) {
                Storage::disk('s3')->delete($user->image);
            }

            // Генерируем уникальное имя файла
            $fileName = 'user_' . $user->id . '_' . time() . '.' .
                       $request->file('avatar')->extension();

            // Сохраняем файл
            $path = $request->file('avatar');
            $success = Storage::disk('s3')->put('avatars', $fileName, 'public');

            if(!$success){
                throw new Exception('Не удалось сохранить изображение на сервере');
            }
            // Обновляем поле image в базе данных
            $user->update(['image' => $path]);
        });

        return response()->json([
            'user' => new UserResource($user->fresh())
        ]);
    }

    /**
     * Удаление аватара пользователя.
     */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            if ($user->image && Storage::disk('s3')->exists($user->image)) {
                Storage::disk('s3')->delete($user->image);
            }

            $user->update(['image' => null]);
        });

        return response()->json([
            'user' => new UserResource($user->fresh())
        ]);
    }

    // ============ АДМИНСКИЕ МЕТОДЫ ============

    /**
     * Получение списка пользователей.
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $users = User::orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ]
        ]);
    }

    /**
     * Обновление роли пользователя.
     */
    public function updateRole(Request $request, User $user)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,writer,reader'
        ]);

        $user->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully',
            'user' => new UserResource($user->fresh())
        ]);
    }

    /**
     * Удаление пользователя.
     */
    public function destroy(User $user)
    {
        if (!request()->user()->isAdmin()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        DB::transaction(function () use ($user) {
            // Удаляем аватар если есть
            if ($user->image && Storage::disk('s3')->exists($user->image)) {
                Storage::disk('s3')->delete($user->image);
            }

            // Удаляем все токены
            $user->tokens()->delete();

            // Удаляем пользователя
            $user->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
