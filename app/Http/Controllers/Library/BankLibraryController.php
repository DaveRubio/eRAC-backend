<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\LibBank;
use App\Models\LibCheque;
use App\Models\LibBooklet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class BankLibraryController extends Controller
{
    /**
     * Get all banks for current barangay
     */
    public function getBanks(Request $request)
{
    $banks = LibBank::where('barangay_id', Auth::user()->barangay_id)
        ->withCount('booklets') // Count booklets instead of cheques
        ->get()
        ->map(function ($bank) {
            return [
                'id' => $bank->id,
                'name' => $bank->bank_name,
                'status' => ucfirst($bank->status),
                'booklets_count' => $bank->booklets_count, // Changed from cheques_count
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
            //'status' => ['required', Rule::in(['available', 'consumed'])],
        ]);

        $bank->update([
            'bank_name' => $validated['name'],
            //'status' => $validated['status'],
        ]);

        return response()->json([
            'id' => $bank->id,
            'name' => $bank->bank_name,
            //'status' => ucfirst($bank->status),
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
    public function getBookletCheques($bookletId)
    {
        try {
            // Convert to integer and validate
            $bookletId = (int)$bookletId;
            if ($bookletId <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid booklet ID'
                ], 400);
            }

            $booklet = LibBooklet::with('bank')->findOrFail($bookletId);

            // Verify barangay access
            if ($booklet->bank->barangay_id != Auth::user()->barangay_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $cheques = $booklet->cheques()
                ->get()
                ->map(function ($cheque) {
                    return [
                        'id' => $cheque->id,
                        'cheque_number' => $cheque->cheque_number,
                        'cheque_status' => $cheque->cheque_status,
                        'created_at' => $cheque->created_at->format('Y-m-d'),
                        'dvs' => []
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' => $cheques,
                'cheques' => $cheques
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Booklet not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }


  /* public function getBankCheques(LibBank $bank)
{
    // Verify bank belongs to user's barangay
    if ($bank->barangay_id !== Auth::user()->barangay_id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    return $bank->cheques()
    ->get()
    ->map(function ($cheque) {
        return [
            'id' => $cheque->id,
            'chequeNo' => $cheque->cheque_number, // ✅ required by frontend
            'date' => $cheque->created_at->format('Y-m-d'),
            'status' => $cheque->cheque_status,
            'dvs' => [], // if needed
        ];
    });


    return response()->json([
        'status' => true,
        'message' => 'Request successful',
        'data' => $cheques,
        'cheques' => $cheques,
    ]);
}*/


    /**
     * Create a new cheque for a bank
     */
    /**
 * Create a new cheque for a bank
 */
/*public function createCheque(Request $request, LibBank $bank)
{
    // Verify bank belongs to user's barangay
    if ($bank->barangay_id !== Auth::user()->barangay_id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $validated = $request->validate([
        'chequeNo' => [
            'required',
            'string',
            'max:255',
            Rule::unique('lib_cheque', 'cheque_number')->where(function ($query) use ($bank) {
                return $query->where('bank_id', $bank->id);
            })
        ],
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
}*/

public function getBankBooklets(LibBank $bank)
{
    // Verify bank belongs to user's barangay
    if ($bank->barangay_id !== Auth::user()->barangay_id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $booklets = $bank->booklets()
        ->get()
        ->map(function ($booklet) {
            $start = (int)$booklet->starting_cheque_numb;
            $end = (int)$booklet->ending_cheque_numb;
            $quantity = $end - $start + 1;
            return [
                'id' => $booklet->id,
                'date' => $booklet->created_at->format('Y-m-d'), // Map to 'date'
                'booklet_numb' => $booklet->booklet_numb,
                'starting_cheque_numb' => $booklet->starting_cheque_numb,
                'ending_cheque_numb' => $booklet->ending_cheque_numb,
                'quantity' => $quantity,
                'status' => $booklet->status,

            ];
        });

    return response()->json([
        'booklets' => $booklets
    ]);
}

public function createBooklet(Request $request, LibBank $bank)
{
    // Verify bank belongs to user's barangay
    if ($bank->barangay_id !== Auth::user()->barangay_id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $validated = $request->validate([
        'starting_cheque_numb' => 'required|string|size:8|regex:/^[0-9]+$/',
        'ending_cheque_numb' => 'required|string|size:8|regex:/^[0-9]+$/',
    ]);

    // Convert to integers for validation
    $start = (int)$validated['starting_cheque_numb'];
    $end = (int)$validated['ending_cheque_numb'];

    // Validate range
    if ($start > $end) {
        return response()->json(['message' => 'Starting number must be less than ending number'], 422);
    }
    if (($end - $start + 1) > 50) {
        return response()->json(['message' => 'Maximum 50 cheques per booklet'], 422);
    }

    // Calculate quantity
    $quantity = $end - $start + 1;

    // Start database transaction
    DB::beginTransaction();
    try {
        // Create the booklet
        $booklet = $bank->booklets()->create([
            'booklet_numb' => $validated['starting_cheque_numb'] . '-' . $validated['ending_cheque_numb'],
            'starting_cheque_numb' => $validated['starting_cheque_numb'],
            'ending_cheque_numb' => $validated['ending_cheque_numb'],
            'quantity' => $quantity,
            'status' => 'unused',
        ]);

        // Generate cheque numbers
        $cheques = [];
        for ($i = $start; $i <= $end; $i++) {
            $cheques[] = [
                'booklet_id' => $booklet->id,
                'cheque_number' => str_pad($i, 8, '0', STR_PAD_LEFT),
                'status' => 'unused',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert cheques
        LibCheque::insert($cheques);

        DB::commit();

        return response()->json([
            'id' => $booklet->id,
            'booklet_numb' => $booklet->booklet_numb,
            'starting_cheque_numb' => $booklet->starting_cheque_numb,
            'ending_cheque_numb' => $booklet->ending_cheque_numb,
            'quantity' => $quantity,
            'status' => $booklet->status,
            'created_at' => $booklet->created_at->format('Y-m-d'),
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to create booklet: ' . $e->getMessage()
        ], 500);
    }
}

}
