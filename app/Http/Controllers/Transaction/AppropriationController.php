<?php
namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\TranAppropriation;
use App\Models\LibFiscalYear;
use App\Models\LibExpenseClass;
use App\Models\LibExpenseType;
use App\Models\LibExpenseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

    class AppropriationController extends Controller
    {
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer',
            'status' => 'nullable|in:draft,committed,reverted',
            'search' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        // Get base query
        $query = Budget::with(['tranAppropriations.expenseType', 'fiscalYear'])
            ->where('barangay_id', $request->user()->barangay_id);

        // Apply year filter
        if ($request->year) {
            $query->whereHas('fiscalYear', function($q) use ($request) {
                $q->where('year', $request->year);
            });
        }

        // Apply other filters
        if ($request->search) {
            $query->where('description', 'like', '%'.$request->search.'%');
        }

        if ($request->date_from) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('end_date', '<=', $request->date_to);
        }

        // Get budgets with their total appropriations
        $budgets = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function($budget) {
                return [
                    'id' => $budget->id,
                    'date' => $budget->created_at->format('Y-m-d'),
                    'description' => $budget->description,
                    'amount' => (float)$budget->original_amount,
                    'unappropriated' => (float)$budget->current_amount,
                    'fiscal_year' => $budget->fiscalYear->year,
                    'allocations' => $budget->tranAppropriations->map(function($tranAppropriations) {
                        return [
                            'id' => $tranAppropriations->id,
                            'amount' => (float)$tranAppropriations->amount,
                            'expense_type' => $tranAppropriations->expenseType->name ?? null
                        ];
                    })
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $budgets,
            'total_available' => (float)Budget::where('barangay_id', $request->user()->barangay_id)
                                        ->sum('current_amount')
        ]);
    }
    /**
     * Create a new budget without initial appropriations
     */
        public function storeBudget(Request $request)
    {
        $validated = $request->validate([
            'fiscal_year_id' => 'required|exists:lib_fiscal_years,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'required|string|max:255',
            'original_amount' => 'required|numeric|min:0'
        ]);

        $budget = Budget::create([
            'barangay_id' => $request->user()->barangay_id,
            'fiscal_year_id' => $validated['fiscal_year_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'description' => $validated['description'],
            'original_amount' => $validated['original_amount'],
            'current_amount' => $validated['original_amount'], // Initialize with full amount
            'user_id' => $request->user()->id
        ]);

        return response()->json($budget, 201);
    }

        // Get expense hierarchy for allocation
        public function getExpenseHierarchy(Request $request)
        {
            $request->validate([
            'fiscal_year_id' => 'required|exists:lib_fiscal_years,id'
        ]);

            $classes = LibExpenseClass::with(['types.items'])
                ->where('fiscal_year_id', $request->fiscal_year_id)
                ->get()
                ->map(function($class) {
                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'isMainCategory' => true,
                        'children' => $class->types->map(function($type) {
                            return [
                                'id' => $type->id,
                                'name' => $type->name,
                                'isMainCategory' => false,
                                'children' => $type->items->map(function($item) {
                                    return [
                                        'id' => $item->id,
                                        'name' => $item->name,
                                        'isMainCategory' => false,
                                        'amount' => null
                                    ];
                                })
                            ];
                        })
                    ];
                });

            return response()->json($classes);
        }



        // Save allocation from modal
public function saveAllocation(Request $request, Budget $budget)
{
    $validated = $request->validate([
        'allocations' => 'required|array',
        'allocations.*.id' => 'required',
        'allocations.*.type' => 'required|in:class,type,item',
        'allocations.*.amount' => 'required|numeric|min:0'
    ]);

    return DB::transaction(function () use ($validated, $budget, $request) {
        $totalAllocated = 0;
        $totalAdjustment = 0; // Tracks net changes to existing allocations
        $appropriations = [];

        // First get all existing appropriations for this budget
        $existingAppropriations = TranAppropriation::where('budget_id', $budget->id)
            ->where('barangay_id', $request->user()->barangay_id)
            ->get();

        foreach ($validated['allocations'] as $allocation) {
            $query = clone $existingAppropriations;

            // Filter by type and id
            switch ($allocation['type']) {
                case 'class':
                    $query = $query->where('expense_class_id', $allocation['id']);
                    break;
                case 'type':
                    $query = $query->where('expense_type_id', $allocation['id']);
                    break;
                case 'item':
                    $query = $query->where('expense_item_id', $allocation['id']);
                    break;
            }

            $existing = $query->first();

            $appropriationData = [
                'barangay_id' => $request->user()->barangay_id,
                'budget_id' => $budget->id,
                'amount' => $allocation['amount'],
                'transaction_date' => now(),
                'status' => 'committed',
                'user_id' => $request->user()->id
            ];

            // Set the appropriate expense field
            $field = match($allocation['type']) {
                'class' => 'expense_class_id',
                'type' => 'expense_type_id',
                'item' => 'expense_item_id',
            };
            $appropriationData[$field] = $allocation['id'];

            if ($existing) {
                // Calculate the difference from previous amount
                $amountDifference = $allocation['amount'] - $existing->amount;
                $totalAdjustment += $amountDifference;

                // Update existing record
                $existing->update($appropriationData);
                $appropriations[] = $existing;
            } else {
                // New allocation - add to total
                $totalAllocated += $allocation['amount'];
                $appropriations[] = TranAppropriation::create($appropriationData);
            }
        }

        // Calculate net change (new allocations + adjustments to existing ones)
        $netChange = $totalAllocated + $totalAdjustment;

        // Update the budget's current amount
        if ($netChange != 0) {
            $budget->decrement('current_amount', $netChange);
        }

        // If all allocations are removed, restore original amount
        if (empty($validated['allocations'])) {
            $budget->update(['current_amount' => $budget->original_amount]);
        }

        $budget->refresh();

        return response()->json([
            'budget' => $budget,
            'appropriations' => $appropriations,
            'total_allocated' => $totalAllocated + $totalAdjustment
        ]);
    });
}

// In your AppropriationController.php
    public function getBudgetAllocations($budgetId)
    {
        $allocations = TranAppropriation::where('budget_id', $budgetId)
            ->get()
            ->map(function($alloc) {
                return [
                    'expense_item_id' => $alloc->expense_item_id,
                    'expense_type_id' => $alloc->expense_type_id,
                    'amount' => $alloc->amount
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $allocations
        ]);
    }

        // Adjust budget (augmentation/return)
        public function adjustBudget(Request $request, Budget $budget)
        {
            $validated = $request->validate([
                'type' => 'required|in:augmentation,return',
                'amount' => 'required|numeric|min:0',
                'reason' => 'required|string|max:255'
            ]);

            return DB::transaction(function () use ($validated, $budget) {
                if ($validated['type'] === 'augmentation') {
                    $budget->increment('augmentation', $validated['amount']);
                    $budget->increment('current_amount', $validated['amount']);
                } else {
                    $budget->increment('return_amount', $validated['amount']);
                    $budget->decrement('current_amount', $validated['amount']);
                }

                return response()->json($budget);
            });
        }

    }
