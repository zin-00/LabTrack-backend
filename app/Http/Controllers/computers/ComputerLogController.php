<?php

namespace App\Http\Controllers\computers;

use App\Http\Controllers\Controller;
use App\Models\ComputerLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ComputerLogController extends Controller
{
    public function index(Request $request){

        $today = Carbon::now();
         $startOfDay = $today->copy()->startOfDay();
        $computer_logs = ComputerLog::with('student', 'computer.laboratory')
        ->whereBetween('created_at', [$startOfDay, $today->copy()->endOfDay()])
        ->orderBy('created_at', 'desc')->paginate(7);
        return response()->json([
            'computer_logs' => $computer_logs,
            'message' => 'Computer logs retrieved successfully'
        ]);
    }
}
