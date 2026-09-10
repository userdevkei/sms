<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::withCount('transactions')->orderBy('name')->get();

        return view('accounting.expense-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('accounting.expense-categories.create');
    }

    public function store(StoreIncomeCategoryRequest $request)
    {
        $category = ExpenseCategory::create($request->validated());

        return redirect()->route('accounting.expense-categories.index')
            ->with('success', "\"{$category->name}\" was added successfully.");
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        return view('accounting.expense-categories.edit', ['category' => $expenseCategory]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $expenseCategory->update($validated);

        return redirect()
            ->route('accounting.expense-categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();

        return response()->json(['success' => true]);
    }
}
