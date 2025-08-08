<?php

namespace App\Http\Controllers\laboratories;

use App\Http\Controllers\Controller;
use App\Models\Laboratory;
use Illuminate\Http\Request;

class LabController extends Controller
{
    public function index(Request $request){
        $laboratories = Laboratory::all();
        return response()->json([
            'laboratories' => $laboratories,
            'message' => 'Laboratories retrieved successfully'
        ]);
    }
    public function store(Request $request){
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:laboratories,code'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $laboratory = Laboratory::create($data);
        return response()->json([
            'message' => 'Laboratory created successfully',
            'laboratory' => $laboratory
        ], 201);
    }
    public function update(Request $request, $id){
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:laboratories,code,' . $id],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $laboratory = Laboratory::findOrFail($id);
        if (!$laboratory) {
            return response()->json(['message' => 'Laboratory not found'], 404);
        }

        $laboratory->update($data);

        return response()->json([
            'message' => 'Laboratory updated successfully',
            'laboratory' => $laboratory
        ]);
    }
    public function destroy(Request $request, $id){
        $laboratory = Laboratory::findOrFail($id);
        if (!$laboratory) {
            return response()->json(['message' => 'Laboratory not found'], 404);
        }

        $laboratory->delete();

        return response()->json([
            'message' => 'Laboratory deleted successfully'
        ]);
    }
}
