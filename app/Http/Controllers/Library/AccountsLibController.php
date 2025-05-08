<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Models\LibExpense;
use App\Models\LibExpenseClass;
use App\Models\LibExpenseItem;
use App\Models\LibExpenseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountsLibController extends Controller
{
   protected function verifyBarangayAccess()
{
    // No parameter needed since we'll get it from Auth
    $barangayId = Auth::user()->barangay_id;

    if (!$barangayId) {
        abort(403, 'User is not associated with any barangay');
    }
}

// Get all fiscal years for current barangay
public function getFiscalYears()
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    return response()->json(
        FiscalYear::where('barangay_id', $barangayId)
            ->orderBy('year', 'desc')
            ->get()
    );
}


public function createFiscalYear(Request $request)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    $validated = $request->validate([  
        'year' => [
            'required',
            'digits:4',
            Rule::unique('lib_fiscal_years')->where(function ($query) use ($barangayId) {
                return $query->where('barangay_id', $barangayId);
            })
        ]
    ]);

    $year = FiscalYear::create([
        'barangay_id' => $barangayId,
        'year' => $validated['year'],
        'is_active' => false
    ]);

    return response()->json($year, 201);
}

// Copy fiscal year data
  // Add this to your ExpenseClassController.php
public function copyToYear(Request $request, $sourceYearId)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    $request->validate([
        'target_year_id' => 'required|exists:lib_fiscal_years,id',
        'class_ids' => 'required|array',
        'class_ids.*' => 'exists:lib_expense_classes,id,fiscal_year_id,'.$sourceYearId
    ]);

    DB::beginTransaction();
    try {
        \Log::info('Starting year copy', [
            'source_year_id' => $sourceYearId,
            'target_year_id' => $request->target_year_id,
            'class_ids' => $request->class_ids
        ]);

        $stats = [
            'copied_classes' => 0,
            'copied_types' => 0,
            'skipped_classes' => 0,
            'skipped_types' => 0
        ];

        foreach ($request->class_ids as $classId) {
            $sourceClass = LibExpenseClass::with('types')
                ->where('fiscal_year_id', $sourceYearId)
                ->findOrFail($classId);

            // Check for duplicate class name in target year
            if (LibExpenseClass::where('fiscal_year_id', $request->target_year_id)
                ->where('name', $sourceClass->name)
                ->exists()) {
                $stats['skipped_classes']++;
                continue;
            }

            // Copy class
            $newClass = $sourceClass->replicate();
            $newClass->fiscal_year_id = $request->target_year_id;
            $newClass->save();
            $stats['copied_classes']++;

            // Copy types
            foreach ($sourceClass->types as $type) {
                if (LibExpenseType::where('expense_class_id', $newClass->id)
                    ->where('name', $type->name)
                    ->exists()) {
                    $stats['skipped_types']++;
                    continue;
                }

                $newType = $type->replicate();
                $newType->expense_class_id = $newClass->id;
                $newType->save();
                $stats['copied_types']++;
            }
        }
        
        DB::commit();

         \Log::info('Copy completed successfully', [
            'stats' => $stats,
            'response_data' => [
                'success' => true,
                'message' => 'Copy completed successfully',
                'stats' => $stats
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Copy completed successfully',
            'stats' => $stats
        ]);

     } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Copy failed', [
            'error' => $e->getMessage(),
            'stack' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to copy data',
            'error' => $e->getMessage()
        ], 500);
    }
}
    // =============================================
    // Expense Class Methods
    // =============================================


     public function getExpenseClasses()
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;
    $fiscalYearId = request('fiscal_year_id');

    $classes = LibExpenseClass::where('barangay_id', $barangayId)
        ->when($fiscalYearId, function($query) use ($fiscalYearId) {
            $query->where('fiscal_year_id', $fiscalYearId);
        })
        ->with('types')
        ->orderBy('order')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $classes
    ]);
}
   public function createExpenseClass(Request $request)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    $validated = $request->validate([
        'fiscal_year_id' => [
            'required',
            Rule::exists('lib_fiscal_years', 'id')->where(function ($query) use ($barangayId) {
                $query->where('barangay_id', $barangayId);
            })
        ],
        'name' => [
            'required',
            'max:255',
            Rule::unique('lib_expense_classes')->where(function ($query) use ($barangayId, $request) {
                return $query->where('barangay_id', $barangayId)
                            ->where('fiscal_year_id', $request->fiscal_year_id);
            })
        ],

        'order' => 'sometimes|integer'
    ]);

    $class = LibExpenseClass::create([
        'barangay_id' => $barangayId,
        'fiscal_year_id' => $validated['fiscal_year_id'],
        'name' => $validated['name'],
        'order' => LibExpenseClass::where('fiscal_year_id', $validated['fiscal_year_id'])
            ->count()
    ]);

    return response()->json($class, 201);
}


 public function updateClass(Request $request, $classId)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    $validated = $request->validate([
        'fiscal_year_id' => 'required|exists:lib_fiscal_years,id',
        'name' => [
            'required',
            'max:255',
            Rule::unique('lib_expense_classes')
                ->ignore($classId)
                ->where(function ($query) use ($barangayId, $request) {
                    return $query->where('barangay_id', $barangayId)
                                ->where('fiscal_year_id', $request->fiscal_year_id);
                })
        ],
        'order' => 'sometimes|integer'
    ]);

    $class = LibExpenseClass::forBarangay($barangayId)->findOrFail($classId);
    $class->update($validated);

    return response()->json($class->fresh()->load('types'));
}


  public function deleteClass($classId)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    DB::transaction(function () use ($barangayId, $classId) {
        $class = LibExpenseClass::forBarangay($barangayId)
            ->with('types.items')
            ->findOrFail($classId);

        // Manually delete to avoid cascade issues
        $class->types->each(function ($type) {
            $type->items()->delete();
            $type->delete();
        });

        $class->delete();
    });

    return response()->json(['message' => 'Class deleted successfully']);
}

public function updateTypeOrder(Request $request)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    $request->validate([
        'classes' => 'required|array',
        'classes.*.id' => 'required|exists:lib_expense_classes,id',
        'classes.*.order' => 'required|integer'
    ]);

    DB::transaction(function () use ($request, $barangayId) {
        foreach ($request->classes as $classData) {
            LibExpenseClass::forBarangay($barangayId)
                ->where('id', $classData['id'])
                ->update(['order' => $classData['order']]);
        }
    });

    return response()->json(['message' => 'Order updated successfully']);
}

    //Exepnse Type Methods

 public function getExpenseTypes($classId)

 // Get all expense types for a specific class
    {
        $this->verifyBarangayAccess();
        $barangayId = Auth::user()->barangay_id;

        return response()->json(
            LibExpenseType::where('expense_class_id', $classId)
                ->whereHas('expenseClass', function($query) use ($barangayId) {
                    $query->where('barangay_id', $barangayId);
                })
                ->with('items') // Eager load items
                ->orderBy('order')
                ->get()
        );
    }

// Create a new expense type
public function createExpenseType(Request $request, $classId)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    $validated = $request->validate([
        'name' => [
            'required',
            'max:255',
            Rule::unique('lib_expense_types')->where(function ($query) use ($classId) {
                return $query->where('expense_class_id', $classId);
            })
        ],
        'order' => 'sometimes|integer',
    ]);

    $type = LibExpenseType::create([
        'expense_class_id' => $classId,
        'name' => $validated['name'],
        'order' => LibExpenseType::where('expense_class_id', $classId)
            ->count()
    ]);

    return response()->json($type->load('items'), 201);
}



  public function updateExpenseType(Request $request, $classId, $typeId)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    $validated = $request->validate([
        'name' => [
            'required',
            'max:255',
            Rule::unique('lib_expense_types')
                ->ignore($typeId)
                ->where(function ($query) use ($classId) {
                    return $query->where('expense_class_id', $classId);
                })
        ],
        'order' => 'sometimes|integer',
    ]);

    $type = LibExpenseType::where('expense_class_id', $classId)
        ->whereHas('expenseClass', fn($q) => $q->where('barangay_id', $barangayId))
        ->findOrFail($typeId);

    $type->update($validated);

    return response()->json($type->fresh()->load('items'));
}


    // Delete an expense type
    public function deleteType($classId, $typeId)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    DB::transaction(function () use ($barangayId, $classId, $typeId) {
        $type = LibExpenseType::where('expense_class_id', $classId)
            ->whereHas('expenseClass', function ($q) use ($barangayId) {
                $q->where('barangay_id', $barangayId);
            })
            ->with('items')
            ->findOrFail($typeId);

        $type->items()->delete();
        $type->delete();
    });

    return response()->json(['message' => 'Type deleted successfully']);
}
//Sotrtable
public function updateOrder(Request $request, $classId)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    $request->validate([
        'types' => 'required|array',
        'types.*.id' => 'required|exists:lib_expense_types,id',
        'types.*.order' => 'required|integer'
    ]);

    DB::transaction(function () use ($request, $barangayId, $classId) {
        foreach ($request->types as $typeData) {
            LibExpenseType::forBarangay($barangayId)
                ->where('id', $typeData['id'])
                ->where('expense_class_id', $classId)
                ->update(['order' => $typeData['order']]);
        }
    });

    return response()->json(['message' => 'Type order updated successfully']);
}

    // =============================================
    // Expense Item Methods
    // =============================================

    public function getExpenseItems($classId, $typeId)

    // Get all expense items for a specific type
    {
        $this->verifyBarangayAccess();
        $barangayId = Auth::user()->barangay_id;

        return response()->json(
            LibExpenseItem::where('expense_type_id', $typeId)
                ->whereHas('expenseType.expenseClass', function($query) use ($barangayId) {
                    $query->where('barangay_id', $barangayId);
                })
                ->orderBy('order')
                ->get()
        );
    }

    // Create a new expense item
    public function createExpenseItem(Request $request, $classId, $typeId)
    {
        $this->verifyBarangayAccess();
        $barangayId = Auth::user()->barangay_id;

        // Verify the type belongs to this class which belongs to this barangay
        $type = LibExpenseType::where('expense_class_id', $classId)
            ->whereHas('expenseClass', function($query) use ($barangayId) {
                $query->where('barangay_id', $barangayId);
            })
            ->findOrFail($typeId);

        $validated = $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('lib_expense_items')->where(function ($query) use ($typeId) {
                    return $query->where('expense_type_id', $typeId);
                })
            ],
            'order' => 'sometimes|integer'
        ]);

        $item = $type->items()->create([
            'name' => $validated['name'],
            'order' => $validated['order'] ?? 0
        ]);

        return response()->json($item, 201);
    }

   public function updateItem(Request $request, $classId, $typeId, $itemId)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    $validated = $request->validate([
        'name' => [
            'required',
            'max:255',
            Rule::unique('lib_expense_items')
                ->ignore($itemId)
                ->where(function ($query) use ($typeId) {
                    return $query->where('expense_type_id', $typeId);
                })
        ],
        'order' => 'sometimes|integer'
    ]);

    $item = LibExpenseItem::where('expense_type_id', $typeId)
        ->whereHas('expenseType.expenseClass', function ($q) use ($barangayId) {
            $q->where('barangay_id', $barangayId);
        })
        ->findOrFail($itemId);

    $item->update($validated);

    return response()->json($item);
}

   public function deleteItem($classId, $typeId, $itemId)
{
    $this->verifyBarangayAccess();
    $barangayId = Auth::user()->barangay_id;

    $item = LibExpenseItem::where('expense_type_id', $typeId)
        ->whereHas('expenseType.expenseClass', function ($q) use ($barangayId) {
            $q->where('barangay_id', $barangayId);
        })
        ->findOrFail($itemId);

    $item->delete();

    return response()->json(['message' => 'Item deleted successfully']);
}




}
