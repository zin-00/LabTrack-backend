<?php

namespace App\Http\Controllers\students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request){
    $students = Student::with('program')->
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
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255'],
            'program_id'    => ['required', 'exists:programs,id'],
        ]);

        $student = Student::create($data);

        return response()->json([
            'message' => 'Student registered successfully',
            'student' => $student
        ], 201);
    }

    public function update(Request $request, $id){
    $data = $request->validate([
            'student_id'    => ['required', 'string', 'max:255'],
            'rfid_uid'      => ['required', 'string', 'max:255'],
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255'],
            'program_id'    => ['required', 'exists:programs,id'],
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

}
