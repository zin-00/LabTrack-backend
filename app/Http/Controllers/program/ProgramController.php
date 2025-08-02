<?php

namespace App\Http\Controllers\program;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index(Request $request){
          return response()->json([
            'programs' => Program::all()
        ]);
    }
}
