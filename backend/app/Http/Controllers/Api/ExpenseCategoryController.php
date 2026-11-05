<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ExpenseCategory::query()->ordered()->withCount('expenses');
        if ($request->boolean('active')) {
            $query->active();
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(StoreExpenseCategoryRequest $request): JsonResponse
    {
        return response()->json(['data' => ExpenseCategory::create($request->validated())], 201);
    }

    public function update(StoreExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $expenseCategory->update($request->validated());

        return response()->json(['data' => $expenseCategory->refresh()]);
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        if ($expenseCategory->expenses()->exists()) {
            throw new BusinessRuleException('category_in_use', 'Catégorie utilisée, désactivez-la plutôt.');
        }
        $expenseCategory->delete();

        return response()->json(null, 204);
    }
}
