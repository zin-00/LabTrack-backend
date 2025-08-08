<?php

namespace App\Http\Controllers\computers;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComputerStatusDistribution extends Controller
{
    public function index(Request $request)
    {
        $lockedCount = Computer::where('is_lock', true)->count();
        $onlineCount = Computer::where('is_online', true)->count();
        $offlineCount = Computer::where('is_online', false)->count();
        $maintenanceCount = Computer::where('status', 'maintenance')->count();
        $activeCount = Computer::where('status', 'active')->count();
        $inactiveCount = Computer::where('status', 'inactive')->count();

        $computers = Computer::count();

        return response()->json([
            'locked_count' => $lockedCount,
            'online_count' => $onlineCount,
            'offline_count' => $offlineCount,
            'maintenance_count' => $maintenanceCount,
            'active_count' => $activeCount,
            'inactive_count' => $inactiveCount,
            'computers' => $computers,
        ]);
    }

    public function getDataDistribution(Request $request)
    {
        $type = $request->query('type', 'today');

        if($type === 'today'){
            $data = Computer::select(DB::raw('status, COUNT(*) as count'))
                ->whereDate('created_at', now())
                ->groupBy('status')
                ->get();
        }

        return response()->json($data);
    }
}
