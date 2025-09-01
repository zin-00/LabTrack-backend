<?php

namespace App\Http\Controllers\RequestAccess;

use App\Http\Controllers\Controller;
use App\Models\RequestAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RequestAccessController extends Controller
{
    public function index (Request $request){
        $requestAccess = RequestAccess::orderBy('created_at', 'desc')->paginate(7);
        return response()->json([
            'requestAccess' => $requestAccess,
            'message' => 'RequestAccess retrieved successfully'
        ]);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'id_number' => 'required|string|max:255',
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:request_accesses',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,faculty,staff,student',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            unset($data['password_confirmation']); // remove it from the data before sending to database
            $data['password'] = bcrypt($data['password']);
            $data['status'] = 'pending';

            $requestAccess = RequestAccess::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Request submitted successfully!',
                'data' => $requestAccess
            ], 201);
        } catch (\Exception $e) {
            Log::error('Request Access Error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, $id){
        $requestAccess = RequestAccess::findOrFail($id);

        $user = User::create([
            'name' => $requestAccess->fullname,
            'email' => $requestAccess->email,
            'password' => bcrypt($requestAccess->password),
            'status' => $requestAccess->status,
            'roles' => $requestAccess->role,
        ]);

        $requestAccess->update([
            'status' => 'approved'
        ]);
        return response()->json([
            'message' => 'RequestAccess updated successfully',
            'user' => $user,
        ]);

    }

    public function reject(Request $request, $id){
        $requestAccess = RequestAccess::findOrFail($id);

        $requestAccess->update([
            'status' => 'rejected'
        ]);
        return response()->json([
            'message' => 'RequestAccess rejected successfully',
        ]);

    }

}
