<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index (Request $request){
        $users = User::orderBy('created_at', 'desc')->paginate(7);
        return response()->json([
            'users' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    }


   public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|same:password',
            'status' => 'required|in:active,inactive,restricted',
            'roles' => 'required|in:admin,faculty,superadmin',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'status' => $data['status'],
            'roles' => $data['roles'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }


public function update(Request $request, $id){
    $data = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
        'password' => 'sometimes|nullable|string|min:8|confirmed',
        'status' => ['sometimes','required', Rule::in(['active','inactive','restricted'])],
        'roles'  => ['sometimes','required', Rule::in(['admin','faculty'])],
    ]);

    if (!empty($data['password'])) {
        $data['password'] = bcrypt($data['password']);
    } else {
        unset($data['password']); // don’t overwrite if empty
    }

    $user = User::findOrFail($id);
    $user->update($data);

    return response()->json([
        'message' => 'User updated successfully',
        'user' => $user,
    ]);
}


    public function edit (Request $request, $id){
        $user = User::findOrFail($id);
        return response()->json([
            'user' => $user,
        ]);
    }

    public function delete (Request $request, $id){
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
