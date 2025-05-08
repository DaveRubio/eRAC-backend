<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\Library\LibParticularController;
use App\Http\Controllers\Library\AccountsLibController;
use App\Http\Controllers\Library\BankLibraryController;
use App\Http\Controllers\Transaction\AppropriationController;
use App\Http\Middleware\AuthTokenValid;
use App\Models\Barangay;
use App\Models\Admin;

Route::prefix('barangay')->group(function () {
    // Barangays list endpoint
    Route::get('/barangays', function(Request $request) {
        $query = Barangay::query();

        if ($request->name) {
            $query->where('name', $request->name);
        }

        return response()->json($query->get(['id', 'name']));
    });

    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/upload-photo', [AuthController::class, 'uploadPhoto']);

    Route::middleware(['auth:sanctum', 'auth.barangay'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    //Accounts Library

   // Particulars CRUD (simplified)
        Route::apiResource('particulars', LibParticularController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);

    //Fiscal Years
    Route::get('fiscal-years', [AccountsLibController::class, 'getFiscalYears']);
    Route::post('fiscal-years', [AccountsLibController::class, 'createFiscalYear']);

     // Expense Classes
    Route::get('expense-classes', [AccountsLibController::class, 'getExpenseClasses']);
    Route::post('expense-classes', [AccountsLibController::class, 'createExpenseClass']);
    Route::put('expense-classes/{classId}', [AccountsLibController::class, 'updateClass']);
    Route::delete('expense-classes/{classId}', [AccountsLibController::class, 'deleteClass']);
    Route::patch('expense-classes/update-order', [AccountsLibController::class, 'updateOrder']);
    //
    Route::post('expense-classes/copy-to-year/{sourceYearId}',
    [AccountsLibController::class, 'copyToYear']);

     // Expense Types
    Route::get('expense-classes/{class}/types', [AccountsLibController::class, 'getExpenseTypes']);
    Route::post('expense-classes/{class}/types', [AccountsLibController::class, 'createExpenseType']);
    Route::put('expense-classes/{classId}/types/{typeId}', [AccountsLibController::class, 'updateExpenseType']);
    Route::delete('expense-classes/{classId}/types/{typeId}', [AccountsLibController::class, 'deleteType']);
    Route::patch('expense-classes/{classId}/types/update-order', [AccountsLibController::class, 'updateTypeOrder']);

    // Expense Items
    Route::get('expense-classes/{class}/types/{type}/items', [AccountsLibController::class, 'getExpenseItems']);
    Route::post('expense-classes/{class}/types/{type}/items', [AccountsLibController::class, 'createExpenseItem']);
    Route::put('expense-classes/{classId}/types/{typeId}/items/{itemId}', [AccountsLibController::class, 'updateItem']);
    Route::delete('expense-classes/{classId}/types/{typeId}/items/{itemId}', [AccountsLibController::class, 'deleteItem']);

    //Banks Library
    Route::get('banks', [BankLibraryController::class, 'getBanks']);
    Route::post('banks', [BankLibraryController::class, 'createBank']);
    Route::put('banks/{bank}', [BankLibraryController::class, 'updateBank']);
    //Route::delete('banks/{bank}', [AccountsLibController::class, 'deleteBank']);
    Route::get('banks/{bank}/cheques', [BankLibraryController::class, 'getBankCheques']);
    Route::post('banks/{bank}/cheques', [BankLibraryController::class, 'createCheque']);



    //Transaction Appropriation
    // Budget endpoints
     Route::get('budgets', [AppropriationController::class, 'index']);
    // Add this above your existing budget routes
    Route::post('budgets/create', [AppropriationController::class, 'storeBudget']);
    // Expense hierarchy
    Route::get('expense-hierarchy', [AppropriationController::class, 'getExpenseHierarchy']);
    // Allocation endpoints
    Route::get('budgets/{budget}/allocations', [AppropriationController::class, 'getBudgetAllocations']);
    Route::post('budgets/{budget}/allocate', [AppropriationController::class, 'saveAllocation']);
});
});


Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);

    // Just use Sanctum's default auth
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
    });
});
