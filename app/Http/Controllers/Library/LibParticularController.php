<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Models\LibParticular;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibParticularController extends Controller
{
    // Get all particulars for current barangay
    public function index()
    {
        return LibParticular::where('barangay_id', Auth::user()->barangay_id)
            ->get();
    }

    // Create new particular
    public function store(Request $request)
    {
        $request->validate([
            'particular_name' => 'required|string'
        ]);

        return LibParticular::create([
            'barangay_id' => Auth::user()->barangay_id,
            'particular_name' => $request->particular_name,
            'created_by' => Auth::id()
        ]);
    }

    // Get single particular
    public function show($id)
    {
        return LibParticular::where('barangay_id', Auth::user()->barangay_id)
            ->findOrFail($id);
    }

    // Update particular
    public function update(Request $request, $id)
    {
        $particular = LibParticular::where('barangay_id', Auth::user()->barangay_id)
            ->findOrFail($id);

        $request->validate([
            'particular_name' => 'required|string'
        ]);

        $particular->update([
            'particular_name' => $request->particular_name
        ]);

        return $particular;
    }

    // Delete particular
    public function destroy($id)
    {
        $particular = LibParticular::where('barangay_id', Auth::user()->barangay_id)
            ->findOrFail($id);

        $particular->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
