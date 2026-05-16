<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Officer;

class OfficerController extends Controller
{
    // GET /api/officers
    public function index()
    {
        $officers = Officer::orderBy('officer_code')->get();
        return response()->json(['success' => true, 'officers' => $officers]);
    }

    // POST /api/officers
    public function store(Request $request)
    {
        $request->validate([
            'officer_code' => 'required|unique:officers,officer_code',
            'name'         => 'required|string|max:100',
        ]);

        $officer = Officer::create($request->only(['officer_code','name','badge','department']));
        return response()->json(['success' => true, 'officer' => $officer]);
    }

    // POST /api/officers/toggle
    public function toggle(Request $request)
    {
        $officer = Officer::where('officer_code', $request->officer_code)->first();
        if (!$officer) {
            return response()->json(['success' => false, 'message' => 'Officer not found'], 404);
        }
        $officer->update(['is_active' => !$officer->is_active]);
        return response()->json(['success' => true, 'is_active' => $officer->is_active]);
    }
}
