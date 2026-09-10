<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeCategoryRequest;
use App\Models\IncomeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeCategoryController extends Controller
{
    public function index()
    {
        $categories = IncomeCategory::withCount('transactions')->orderBy('name')->get();

        return view('accounting.income-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('accounting.income-categories.create');
    }

    public function store(StoreIncomeCategoryRequest $request)
    {
        $category = IncomeCategory::create($request->validated());

        return redirect()->route('accounting.income-categories.index')
            ->with('success', "\"{$category->name}\" was added successfully.");
    }

    public function edit(IncomeCategory $incomeCategory)
    {
        return view('accounting.income-categories.edit', ['category' => $incomeCategory]);
    }

    public function update(StoreIncomeCategoryRequest $request, IncomeCategory $incomeCategory)
    {
        $incomeCategory->update($request->validated());

        return redirect()->route('accounting.income-categories.index')
            ->with('success', "\"{$incomeCategory->name}\" was updated successfully.");
    }

    public function destroy(IncomeCategory $incomeCategory): JsonResponse
    {
        if ($incomeCategory->transactions()->exists()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete a category that has transactions.'], 422);
        }

        $incomeCategory->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
    }
}
