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
    }
    public function update(Request $request, $id){
    }
    public function destroy(Request $request, $id){
    }
}
