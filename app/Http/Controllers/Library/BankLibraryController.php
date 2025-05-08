<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\LibBank;
use App\Models\LibCheque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BankLibraryController extends Controller
{
    /**
     * Get all banks for current barangay
     */
    public function getBanks(Request $request)
    {
        $banks = LibBank::where('barangay_id', Auth::user()->barangay_id)
            ->withCount('cheques')
            ->get()
            ->map(function ($bank) {
                return [
                    'id' => $bank->id,
                    'name' => $bank->bank_name,
                    'status' => ucfirst($bank->status),
                    'cheques_count' => $bank->cheques_count,
                ];
            });

        return response()->json($banks);
    }

    /**
     * Create a new bank
     */
public function createBank(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|min:3|max:255',
    ]);

    $bank = LibBank::create([
        'bank_name' => $validated['name'],
        'status' => 'available',
        'barangay_id' => Auth::user()->barangay_id, // Add this line
    ]);

    return response()->json([
        'id' => $bank->id,
        'name' => $bank->bank_name,
        'status' => ucfirst($bank->status),
        'cheques_count' => 0,
    ], 201);
}

    /**
     * Update a bank
     */
    public function updateBank(Request $request, LibBank $bank)
    {
        // Verify bank belongs to user's barangay
        if ($bank->barangay_id !== Auth::user()->barangay_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'status' => ['required', Rule::in(['available', 'consumed'])],
        ]);

        $bank->update([
            'bank_name' => $validated['name'],
            'status' => $validated['status'],
        ]);

        return response()->json([
            'id' => $bank->id,
            'name' => $bank->bank_name,
            'status' => ucfirst($bank->status),
            'cheques_count' => $bank->cheques()->count(),
        ]);
    }

    /**
     * Delete a bank
     */
   /* public function deleteBank(LibBank $bank)
    {
        // Verify bank belongs to user's barangay
        if ($bank->barangay_id !== Auth::user()->barangay_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if bank has cheques before deleting
        if ($bank->cheques()->exists()) {
            return response()->json([
                'message' => 'Cannot delete bank with existing cheques'
            ], 422);
        }

        $bank->delete();

        return response()->json(['message' => 'Bank deleted successfully']);
    }*/

    /**
     * Get all cheques for a bank
     */
    public function getBankCheques(LibBank $bank)
    {
        // Verify bank belongs to user's barangay
        if ($bank->barangay_id !== Auth::user()->barangay_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cheques = $bank->cheques()
            ->get()
            ->map(function ($cheque) {
                return [
                    'id' => $cheque->id,
                    'chequeNo' => $cheque->cheque_number,
                    'date' => $cheque->created_at->format('Y-m-d'),
                    'status' => $cheque->cheque_status,
                ];
            });

        return response()->json([
            'bank' => [
                'id' => $bank->id,
                'name' => $bank->bank_name,
            ],
            'cheques' => $cheques,
        ]);
    }

    /**
     * Create a new cheque for a bank
     */
    public function createCheque(Request $request, LibBank $bank)
    {
        // Verify bank belongs to user's barangay
        if ($bank->barangay_id !== Auth::user()->barangay_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'chequeNo' => 'required|string|max:255|unique:lib_cheque,cheque_number',
            'date' => 'nullable|date',
        ]);

        $cheque = $bank->cheques()->create([
            'cheque_number' => $validated['chequeNo'],
            'cheque_status' => 'unused',
            'created_at' => $validated['date'] ?? now(),
        ]);

        return response()->json([
            'id' => $cheque->id,
            'chequeNo' => $cheque->cheque_number,
            'date' => $cheque->created_at->format('Y-m-d'),
            'status' => $cheque->cheque_status,
            'dvs' => [], // Empty array as expected by frontend
        ], 201);
    }
}
