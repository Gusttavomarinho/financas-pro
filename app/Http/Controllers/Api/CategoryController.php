<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Lista todas as categorias do usuário + categorias do sistema
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::where(function ($query) use ($request) {
            $query->where('user_id', $request->user()->id)
                ->orWhere('is_system', true);
        })
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Cria uma nova categoria
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:receita,despesa'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]);

        $category = Category::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        AuditLog::log('create', 'Category', $category->id);

        return response()->json([
            'message' => 'Categoria criada com sucesso!',
            'data' => $category,
        ], 201);
    }

    /**
     * Atualiza uma categoria
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        // Não permite editar categorias do sistema
        if ($category->is_system) {
            return response()->json([
                'message' => 'Categorias do sistema não podem ser editadas.',
            ], 403);
        }

        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'icon' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldData = $category->toArray();
        $category->update($validated);

        AuditLog::log('update', 'Category', $category->id, [
            'old' => $oldData,
            'new' => $validated,
        ]);

        return response()->json([
            'message' => 'Categoria atualizada com sucesso!',
            'data' => $category->fresh(),
        ]);
    }

    /**
     * Remove uma categoria
     */
    public function destroy(Request $request, Category $category): JsonResponse
    {
        // Não permite deletar categorias do sistema
        if ($category->is_system) {
            return response()->json([
                'message' => 'Categorias do sistema não podem ser removidas.',
            ], 403);
        }

        $this->authorize('delete', $category);

        $category->delete();

        AuditLog::log('delete', 'Category', $category->id);

        return response()->json([
            'message' => 'Categoria removida com sucesso!',
        ]);
    }
}
