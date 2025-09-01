<?php

namespace App\Http\Controllers\computers;

use App\Events\ComputerStatusUpdated;
use App\Events\ComputerUnlockRequested;
use App\Events\SetOnlineEvent;
use App\Events\Student\ScanEvent;
use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\ComputerStudent;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class ComputerController extends Controller
{
    public function index(Request $request){
        $computers = Computer::all();
        return response()->json([
            'computers' => $computers,
            'message' => 'Computers retrieved successfully'
        ]);
    }

    public function showAllComputerWithNullLabId(Request $request){
        $computers = Computer::whereNull('laboratory_id')->get();
        return response()->json([
            'computers' => $computers,
            'message' => 'Computers with null laboratory ID retrieved successfully'
        ]);
    }

    public function store(Request $request){
        $data = $request->validate([
            'computer_number' => ['required','string', 'max:255'],
            'ip_address' => 'required|string|max:255',
            'status' => ['required', 'in:active,inactive,maintenance'],
            'laboratory_id' => 'required|integer',
        ]);

        $computer = Computer::create($data);

        ComputerStatusUpdated::dispatch($computer);

        return response()->json([
            'message' => 'Computer registered successfully',
            'computer' => $computer
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'computer_number'   => ['required', 'string', 'max:255', 'unique:computers,computer_number,' . $id],
            'ip_address'        => ['required' , 'string','max:255', 'unique:computers,ip_address,' . $id],
            'mac_address'       => ['required', 'string', 'max:255', 'unique:computers,mac_address,' . $id],
            'status'            => ['required', 'in:active,inactive,maintenance'],
            'laboratory_id'     => ['required', 'integer', 'exists:laboratories,id'],
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

    public function assignLaboratory(Request $request)
    {
        $data = $request->validate([
            'computer_ids' => 'required|array',
            'computer_ids.*' => 'integer|exists:computers,id',
            'laboratory_id' => 'required|integer|exists:laboratories,id',
        ]);

        Computer::whereIn('id', $data['computer_ids'])
            ->update(['laboratory_id' => $data['laboratory_id']]);

        return response()->json([
            'message' => 'Laboratories assigned successfully',
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

    // For Unlocking Computers
    public function unlock(Request $request, $id){
        $request->validate([
            'rfid_uid' => 'required|string|max:255',
        ]);

        $student = Student::where('rfid_uid', $request->input('rfid_uid'))->first();
        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $computer = Computer::findOrFail($id);
        $computer->is_lock = false;
        $computer->save();

        $computer_log =  ComputerLog::create([
                'student_id'   => $student->id,
                'computer_id'  => $computer->id,
                'ip_address'   => $computer->ip_address,
                'mac_address'  => $computer->mac_address,
                'program'      => $student->program?->program_name ?? 'N/A',
                // 'year_level'   => $student->year_level,
                'start_time'   => Carbon::now(),
                'end_time'     => null,
            ]);

        if (!$computer_log) {
            return response()->json(['message' => 'Failed to create computer log'], 500);
        }

        // event(new ComputerUnlockRequested($computer->id, $student->id));

        ComputerStatusUpdated::dispatch($computer->id, $student->id);
        ComputerUnlockRequested::dispatch($computer);

        return response()->json([
            'message' => 'Computer state updated successfully',
            'computer' => $computer,
            'computer_log' => $computer_log,

        ]);

    }
 public function isOffline(Request $request, $ip)
    {
        $computer = Computer::where("ip_address", $ip)->first();

        if (!$computer) {
            return response()->json([
                "message" => "Computer not found"
            ], 404);
        }

        $computer->update([
            "is_online" => false,
            "is_lock" => true,
        ]);

        // Update the latest active log for this computer
        ComputerLog::where("ip_address", $ip)
            ->whereNull("end_time")
            ->update([
                "end_time" => Carbon::now()
            ]);

        try {
            event(new ComputerStatusUpdated($computer));
        } catch (\Exception $e) {
            Log::error("Broadcast event failed: " . $e->getMessage());
        }

        return response()->json([
            "message" => "Computer is now offline",
            "computer" => $computer
        ]);
    }

    public function isOnline(Request $request, $ip)
    {
        $computer = Computer::where("ip_address", $ip)->firstOrFail();

        $computer->update([
            'is_online' => true,
            'is_lock' => true,
        ]);

        try {
            event(new ComputerStatusUpdated($computer));
        } catch (\Exception $e) {
            Log::error("Broadcast event failed: " . $e->getMessage());
        }

        return response()->json([
            "message" => "Computer is online",
            "computer" => $computer
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

    public function register_computer(Request $request)
    {
        $data = $request->validate([
            'computer_number' => ['required', 'string', 'max:255'],
            'ip_address' => ['required', 'string', 'max:255', 'unique:computers,ip_address'],
            'mac_address' => ['required', 'string', 'max:255', 'unique:computers,mac_address'],
            'status' => ['required', 'in:active,inactive,maintenance'],
            'is_lock' => ['required', 'boolean'],
            'is_online' => ['required', 'boolean'],
        ]);

        // Check if computer already exists by IP or MAC
        $existing = Computer::where('ip_address', $data['ip_address'])
            ->orWhere('mac_address', $data['mac_address'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Computer already registered',
                'computer' => $existing,
            ], 200);
        }

        // Create new computer
        $computer = Computer::create($data);

        ComputerStatusUpdated::dispatch($computer);

        return response()->json([
            'message' => 'Computer registered successfully',
            'computer' => $computer,
        ], 201);
    }

public function unlockAssignedComputer(Request $request){
    $request->validate([
        'rfid_uid' => 'required|string|max:255',
    ]);

    $student = Student::where('rfid_uid', $request->input('rfid_uid'))->first();

    if(!$student){
        return response()->json(['message' => 'Student not found'], 404);
    }

    // Use count() instead of empty() check for collections
    $computers = $student->computers()->get();

    if($computers->count() === 0){
        return response()->json(['message' => 'No computers assigned to this student'], 404);
    }

    $unlockedComputers = [];

    foreach ($computers as $computer) {
        $computer->update(['is_lock' => false]);

        // Create computer log - make sure to include year_level
        $computerLog = ComputerLog::create([
            'student_id' => $student->id,
            'computer_id' => $computer->id,
            'ip_address' => $computer->ip_address,
            'mac_address' => $computer->mac_address,
            'program' => $student->program?->program_name ?? 'N/A',
            'year_level' => $student->year_level ?? 'N/A', // Add this required field
            'start_time' => Carbon::now(),
            'end_time' => null,
        ]);

        $unlockedComputers[] = [
            'id' => $computer->id,
            'computer_number' => $computer->computer_number,
            'ip_address' => $computer->ip_address,
            'log_id' => $computerLog->id
        ];
    }

    ScanEvent::dispatch($student);
    ComputerUnlockRequested::dispatch($computer);

    return response()->json([
        'message' => 'Computers unlocked successfully',
        'computers' => $unlockedComputers, // Return meaningful data
        'student' => [
            'id' => $student->id,
            'name' => $student->first_name . ' ' . $student->last_name,
            'student_id' => $student->student_id
        ]
    ]);
}

//  public function assignStudent(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'computer_id' => 'required|exists:computers,id',
//             'student_id' => 'required|exists:students,id'
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'message' => 'Validation failed',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         try {
//             DB::beginTransaction();

//             $computer = Computer::findOrFail($request->computer_id);
//             $student = Student::findOrFail($request->student_id);

//             if ($computer->status !== 'active') {
//                 return response()->json([
//                     'message' => 'Computer is not available for assignment'
//                 ], 422);
//             }

//             $existingAssignment = ComputerStudent::where('computer_id', $computer->id)
//                 ->where('student_id', $student->id)
//                 ->whereNull('unassign_at')
//                 ->first();

//             if ($existingAssignment) {
//                 return response()->json([
//                     'message' => 'Student is already assigned to this computer'
//                 ], 422);
//             }

//             $otherAssignment = ComputerStudent::where('student_id', $student->id)
//                 ->whereNull('unassign_at')
//                 ->first();

//             if ($otherAssignment) {
//                 return response()->json([
//                     'message' => 'Student is already assigned to another computer'
//                 ], 422);
//             }

//             ComputerStudent::create([
//                 'computer_id' => $computer->id,
//                 'student_id' => $student->id,
//                 'assigned_at' => Carbon::now(),
//                 // 'status' => 'active'
//             ]);

//             DB::commit();

//             return response()->json([
//                 'message' => 'Student assigned successfully',
//                 'assignment' => [
//                     'computer' => $computer->load('laboratory'),
//                     'student' => $student->load('program')
//                 ]
//             ]);

//         } catch (\Exception $e) {
//             DB::rollback();
//             return response()->json([
//                 'message' => 'Failed to assign student: ' . $e->getMessage()
//             ], 500);
//         }
//     }
public function bulkAssign(Request $request)
{
    $request->validate([
        'computer_id'   => 'required|exists:computers,id',
        'student_ids'   => 'required|array',
        'student_ids.*' => 'exists:students,id',
    ]);

    $computer = Computer::findOrFail($request->computer_id);
    $labId    = $computer->laboratory_id;

    $conflicts = [];
    $successfulAssignments = [];

    foreach ($request->student_ids as $studentId) {
        // Check if student already assigned in THIS laboratory
        $existsInSameLab = DB::table('computer_students as cs')
            ->join('computers as c', 'c.id', '=', 'cs.computer_id')
            ->where('cs.student_id', $studentId)
            ->where('c.laboratory_id', $labId)
            ->exists();

        if ($existsInSameLab) {
            $student = Student::find($studentId);
            $conflicts[] = $student ? $student->first_name . ' ' . $student->last_name : "Student ID $studentId";
            continue;
        }

        // Otherwise assign student to the selected computer
        ComputerStudent::create([
            'computer_id' => $computer->id,
            'student_id'  => $studentId,
        ]);

        $successfulAssignments[] = $studentId;
    }

    if (!empty($conflicts)) {
        return response()->json([
            'message'                 => 'Some students could not be assigned due to conflicts in this laboratory.',
            'conflicts'               => $conflicts,
            'successful_assignments'  => $successfulAssignments
        ], 422);
    }

    return response()->json([
        'message'        => 'All students assigned successfully',
        'assigned_count' => count($successfulAssignments)
    ]);
}



public function getUnassignedStudents(Request $request)
{
    try {
        $computerId = $request->computer_id;
        $yearLevel  = $request->year_level;
        $program    = $request->program;
        $search     = $request->search;

        if (!$computerId) {
            return response()->json(['error' => 'Computer ID is required'], 400);
        }

        // Get the computer and its lab
        $computer = Computer::find($computerId);
        if (!$computer) {
            return response()->json(['error' => 'Computer not found'], 404);
        }

        $labId = $computer->laboratory_id;

        // 🔹 Get student_ids already assigned in THIS lab only
        $assignedInLab = DB::table('computer_students as cs')
            ->join('computers as c', 'c.id', '=', 'cs.computer_id')
            ->where('c.laboratory_id', $labId)
            ->pluck('cs.student_id')
            ->toArray();

        // 🔹 Only exclude students in THIS lab
        $query = Student::query()
            ->when(!empty($assignedInLab), function ($q) use ($assignedInLab) {
                $q->whereNotIn('id', $assignedInLab);
            });

        // 🔹 Apply filters
        if (!empty($yearLevel) && $yearLevel !== 'all') {
            $query->where('year_level', $yearLevel);
        }

        if (!empty($program) && $program !== 'all') {
            $query->where('program_id', $program);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('student_id', 'like', "%$search%");
            });
        }

        $students = $query->orderBy('last_name')->orderBy('first_name')->get();

        return response()->json([
            'students' => $students,
            'total'    => $students->count(),
        ]);

    } catch (\Exception $e) {
        Log::error('Error in getUnassignedStudents: ' . $e->getMessage());
        return response()->json(['error' => 'Internal server error'], 500);
    }
}



    // Unassign students from a computer
    public function bulkUnassignStudents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|exists:computers,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $computer = Computer::findOrFail($request->computer_id);
        $studentIds = $request->student_ids;

        // Unassign students by setting unassign_at timestamp
        $now = now();
        $unassignedCount = ComputerStudent::where('computer_id', $computer->id)
            ->whereIn('student_id', $studentIds)
            ->whereNull('unassign_at')
            ->update(['unassign_at' => $now]);

        return response()->json([
            'message' => $unassignedCount . ' student(s) unassigned successfully',
            'unassigned_count' => $unassignedCount
        ]);
    }

}
