<?php

namespace App\Http\Controllers\computers;

use App\Events\ComputerUnlockRequested;
use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Container\Attributes\DB;
use Illuminate\Http\Request;

class ComputerController extends Controller
{
    public function index(Request $request){
        $computers = Computer::all();
        return response()->json([
            'computers' => $computers,
            'message' => 'Computers retrieved successfully'
        ]);
    }

    public function store(Request $request){
        $data = $request->validate([
            'computer_number' => ['required','string', 'max:255'],
            'ip_address' => 'required|string|max:255',
            'status' => ['required', 'in:active,inactive,maintenance'],
            'laboratory_id' => 'required|integer'
        ]);

        $computer = Computer::create($data);

        return response()->json([
            'message' => 'Computer registered successfully',
            'computer' => $computer
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'computer_number' => 'required|string|max:255',
            'ip_address' => 'required|string|max:255',
            'status' => 'required|in:active,inactive,maintenance',
            'laboratory_id' => 'required|integer'
        ]);

        $computer = Computer::find($id);
        if (!$computer) {
            return response()->json(['message' => 'Computer not found'], 404);
        }

        $computer->update($data);

        return response()->json([
            'message' => 'Computer updated successfully',
            'computer' => $computer
        ]);
    }

    public function destroy(Request $request, $id){
        $computer = Computer::find($id);
        if (!$computer) {
            return response()->json(['message' => 'Computer not found'], 404);
        }

        $computer->delete();

        return response()->json([
            'message' => 'Computer deleted successfully'
        ]);
    }

    public function computerState(Request $request, $id){
        $request->validate([
            'rfid_uid' => 'required|string|max:255',
        ]);

        $student = Student::where('rfid_uid', $request->input('rfid_uid'))->first();
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $computer = Computer::findOrFail($id);
        $computer->state = true;
        $computer->save();

        ComputerLog::create([
            'student_id'   => $student->id,
            'computer_id'  => $computer->id,
            'ip_address'   => $computer->ip_address,
            'program'      => $student->program?->program_name ?? 'N/A',
            'year_level'   => $student->year_level,
            'start_time'   => Carbon::now(),
            'end_time'     => null,
        ]);

        event(new ComputerUnlockRequested($computer->id, $student->id));

        return response()->json([
            'message' => 'Computer state updated successfully',
            'computer' => $computer
        ]);

    }

    public function isOffline(Request $request, $ip){

        Computer::where("ip_address", $ip)->update([
            "is_online" => false,
            "is_lock" => true,
        ]);

        ComputerLog::where("ip_address", $ip)->update([
            "end_time" => Carbon::now()
        ]);

        return response()->json([
            "message" => "Computer is offline"
        ]);
    }

    public function isOnline(Request $request){
         Computer::where("ip_address", $request->ip_address)->update([
            "is_online" => true,
        ]);

        ComputerLog::where("ip_address", $request->ip_address)->update([
            "start_time" => Carbon::now()
        ]);


        return response()->json([
            "message" => "Computer is offline"
        ]);
    }

   public function getStatus($ip)
    {
        $computer = Computer::with('laboratory')->where('ip_address', $ip)->first();

        if (!$computer) {
            return response()->json([
                'message' => 'Computer not found'
            ], 404);
        }

        return response()->json([
            'is_online' => $computer->is_online,
            'is_lock' => $computer->is_lock,
            'name' => $computer->laboratory?->name,
            'pc_number' => $computer->computer_number,
        ]);
    }


}
