<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {



        $users = User::query();

        if ($request->has('search')) {
            $users->where(function ($query) use ($request) {
                $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $request->search . '%'])
                    ->orWhere('username', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $users->where('is_verified', true);

        $users = $users->paginate(100);


        return ResponseService::response([
            'status' => 200,
            'data' => $users,
        ]);
    }
}
