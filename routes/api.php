<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\Library\LibParticularController;
use App\Http\Controllers\Library\AccountsLibController;
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
        Route::get('fiscal-years', [AccountsLibController::class, 'getFiscalYears']);
        Route::post('fiscal-years', [AccountsLibController::class, 'createFiscalYear']);

     // Expense Classes
    Route::get('expense-classes', [AccountsLibController::class, 'getExpenseClasses']);
    Route::post('expense-classes', [AccountsLibController::class, 'createExpenseClass']);
    Route::put('expense-classes/{classId}', [AccountsLibController::class, 'updateClass']); // Added {classId}
    Route::delete('expense-classes/{classId}', [AccountsLibController::class, 'deleteClass']); // Added {classId}

     // Expense Types
    Route::get('expense-classes/{class}/types', [AccountsLibController::class, 'getExpenseTypes']);
    Route::post('expense-classes/{class}/types', [AccountsLibController::class, 'createExpenseType']);
    Route::put('expense-types/{classId}/{typeId}', [AccountsLibController::class, 'updateType']);
    Route::delete('expense-types/{classId}/{typeId}', [AccountsLibController::class, 'deleteType']);

    // Expense Items
    Route::get('expense-classes/{class}/types/{type}/items', [AccountsLibController::class, 'getExpenseItems']);
    Route::post('expense-classes/{class}/types/{type}/items', [AccountsLibController::class, 'createExpenseItem']);
    Route::put('expense-items/{classId}/{typeId}/{itemId}', [AccountsLibController::class, 'updateItem']);
    Route::delete('expense-items/{classId}/{typeId}/{itemId}', [AccountsLibController::class, 'deleteItem']);

});
});

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
    });
});
