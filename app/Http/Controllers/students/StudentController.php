<?php

namespace App\Http\Controllers\students;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\ComputerStudent;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function index(Request $request){
    $students = Student::with(['program', 'year_level','section'])->
    orderBy('created_at', 'desc')->paginate(7);
        return response()->json([
            'students' => $students,
            'message' => 'Students retrieved successfully']);

    }
    public function store(Request $request){
        $data = $request->validate([
            'student_id'    => ['required', 'string', 'max:255'],
            'rfid_uid'      => ['required', 'string', 'max:255'],
            'first_name'    => ['required', 'string', 'max:255'],
            'middle_name'   => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255'],
            'program_id'    => ['required', 'exists:programs,id'],
            'status'        => ['required', 'in:active,inactive,restricted'],
        ]);

        $student = Student::create($data);

        return response()->json([
            'message' => 'Student registered successfully',
            'student' => $student
        ], 201);
    }
public function importStudents(Request $request)
{
    $validator = Validator::make($request->all(), [
        'students' => ['required', 'array', 'min:1'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    $importedCount = 0;
    $skippedCount = 0;
    $errors = [];

    DB::beginTransaction();
    try {
        foreach ($request->students as $studentData) {
            // ❌ Skip row if key fields are missing
            if (
                empty($studentData['student_id']) ||
                empty($studentData['rfid_uid']) ||
                empty($studentData['first_name']) ||
                empty($studentData['last_name']) ||
                empty($studentData['email']) ||
                empty($studentData['year_level_id'])
            ) {
                $skippedCount++;
                $errors[] = [
                    'student' => $studentData,
                    'errors' => ['Row skipped because of missing required fields']
                ];
                continue;
            }

            try {
                // ✅ Validate only this student
                $studentValidator = Validator::make($studentData, [
                    'student_id' => ['required', 'string', 'max:255', 'unique:students,student_id'],
                    'rfid_uid'   => ['required', 'string', 'max:255', 'unique:students,rfid_uid'],
                    'first_name' => ['required', 'string', 'max:255'],
                    'middle_name'=> ['nullable', 'string', 'max:255'],
                    'last_name'  => ['required', 'string', 'max:255'],
                    'email'      => ['required', 'email', 'max:255', 'unique:students,email'],
                    'year_level_id' => ['required', 'exists:year_levels,id'],
                ]);

                if ($studentValidator->fails()) {
                    $skippedCount++;
                    $errors[] = [
                        'student' => $studentData,
                        'errors' => $studentValidator->errors()->toArray()
                    ];
                    continue;
                }

                Student::create($studentData);
                $importedCount++;

            } catch (\Exception $e) {
                $skippedCount++;
                $errors[] = [
                    'student' => $studentData,
                    'error' => $e->getMessage()
                ];
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Bulk import completed',
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Error during bulk import',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // public function importStudents(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'students' => ['required', 'array', 'min:1'],
    //         'students.*.student_id'    => ['required', 'string', 'max:255'],
    //         'students.*.rfid_uid'      => ['required', 'string', 'max:255'],
    //         'students.*.first_name'    => ['required', 'string', 'max:255'],
    //         'students.*.middle_name'   => ['required', 'string', 'max:255'],
    //         'students.*.last_name'     => ['required', 'string', 'max:255'],
    //         'students.*.email'         => ['required', 'email', 'max:255'],
    //     ]);

    //     if($validator->fails()){
    //          return response()->json([
    //             'message' => 'Validation error',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     try{
    //         $importedCount = 0;
    //         $skippedCount = 0;
    //         $errors = [];

    //         foreach($request->students as $studentsData){
    //             try{
    //                 Student::create($studentsData);
    //                 $importedCount++;
    //             }catch(\Exception $e){
    //                 $skippedCount++;
    //                 $errors[] = [
    //                     'student_id' => $studentsData['student_id'] ?? 'N/A',
    //                     'error' => $e->getMessage()
    //                 ];
    //             }
    //         }

    //         return response()->json([
    //             'message' => 'Bulk import completed',
    //             'imported_count' => $importedCount,
    //             'skipped_count' => $skippedCount,
    //             'errors' => $errors
    //         ], 201);
    //     }catch(\Exception $e){
    //          return response()->json([
    //             'message' => 'Error creating student',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function update(Request $request, $id){
    $data = $request->validate([
            'student_id'    => ['required', 'string', 'max:255'],
            'rfid_uid'      => ['required', 'string', 'max:255'],
            'first_name'    => ['required', 'string', 'max:255'],
            'middle_name'   => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255'],
            'program_id'    => ['required', 'exists:programs,id'],
            'status'        => ['required', 'in:active,inactive,restricted']
        ]);
        $student = Student::findOrFail($id);

        if(!$student){
            return response()->json(['message' => 'Student not found'], 404);
        }

        $student->update($data);
        return response()->json([
            'message' => 'Student updated successfully',
            'student' => $student
        ]);

    }

    public function destroy(Request $request, $id){
        $student = Student::findOrFail($id);

        if(!$student){
            return response()->json(['message' => 'Student not found'], 404);
        }
        $student->delete();
        return response()->json([
            'message' => 'Student deleted successfully',
            'student' => $student
        ]);
    }

   public function getUnassignedStudents(Request $request)
{
    try {
        $computerId = $request->computer_id;
        $yearLevel  = $request->year_level;
        $program    = $request->program;
        $search     = $request->search;

        Log::debug('getUnassignedStudents called with:', [
            'computer_id' => $computerId,
            'year_level' => $yearLevel,
            'program' => $program,
            'search' => $search
        ]);

        if (!$computerId) {
            return response()->json(['error' => 'Computer ID is required'], 400);
        }

        // Get the computer and its lab
        $computer = Computer::find($computerId);
        if (!$computer) {
            return response()->json(['error' => 'Computer not found'], 404);
        }

        $labId = $computer->laboratory_id;
        Log::debug('Computer found:', ['computer' => $computer->toArray(), 'lab_id' => $labId]);

        // Get student_ids already assigned in THIS lab only
        $assignedInLab = ComputerStudent::where('laboratory_id', $labId)
            ->pluck('student_id')
            ->toArray();

        Log::debug('Students already assigned in lab ' . $labId . ':', $assignedInLab);

        // Only exclude students in THIS lab
        $query = Student::query()
            ->when(!empty($assignedInLab), function ($q) use ($assignedInLab) {
                $q->whereNotIn('id', $assignedInLab);
            });

        // Apply filters
        if (!empty($yearLevel) && $yearLevel !== 'all') {
            $query->where('year_level_id', $yearLevel);
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

        Log::debug('Unassigned students found:', [
            'count' => $students->count(),
            'students' => $students->pluck('id')->toArray()
        ]);

        return response()->json([
            'students' => $students,
            'total'    => $students->count(),
        ]);

    } catch (\Exception $e) {
        Log::error('Error in getUnassignedStudents: ' . $e->getMessage());
        return response()->json(['error' => 'Internal server error'], 500);
    }
}
}
