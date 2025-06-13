<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Pusher\Pusher;

class CompanyController extends Controller
{
    protected $pusher;

    public function __construct()
    {
        $this->pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            config('broadcasting.connections.pusher.options')
        );
    }

    public function index(Request $request)
    {
        $companies = Company::with(['manager', 'employees'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'companies' => $companies,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|string|email',
            'description' => 'required|string',
            'logo' => 'nullable|image|max:5120', // 5MB max
        ]);

        $company = Company::create([
            'manager_id' => $request->user()->id,
            'name' => $request->name,
            'email' => $request->email,
            'description' => $request->description,
        ]);

        if ($request->hasFile('logo')) {
            $company->addMedia($request->file('logo'))
                ->toMediaCollection('logo');
        }

        $company->load(['manager', 'employees']);

        return response()->json([
            'message' => 'Company created successfully',
            'data' => $company,
        ], 201);
    }

    public function show(Request $request, Company $company)
    {
        $company->load(['manager', 'employees']);

        return response()->json([
            'company' => $company,
        ]);
    }

    public function update(Request $request, Company $company)
    {
        if ($company->manager_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|string|email',
            'description' => 'required|string',
            'logo' => 'nullable|image|max:5120', // 5MB max
        ]);

        $company->update([
            'name' => $request->name,
            'email' => $request->email,
            'description' => $request->description,
        ]);

        if ($request->hasFile('logo')) {
            $company->clearMediaCollection('logo');
            $company->addMedia($request->file('logo'))
                ->toMediaCollection('logo');
        }

        $company->load(['manager', 'employees']);

        return response()->json([
            'message' => 'Company updated successfully',
            'data' => $company,
        ]);
    }

    public function destroy(Request $request, Company $company)
    {
        if ($company->manager_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $company->delete();

        return response()->json([
            'message' => 'Company deleted successfully',
        ]);
    }

    public function addEmployee(Request $request, Company $company)
    {
        if ($company->manager_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        $user->update(['company_id' => $company->id]);

        // Broadcast to Pusher
        $this->pusher->trigger(
            'private-user.' . $user->id,
            'company.employee.added',
            [
                'company' => $company,
                'user' => $user,
            ]
        );

        return response()->json([
            'message' => 'Employee added successfully',
            'data' => $user,
        ]);
    }

    public function removeEmployee(Request $request, Company $company)
    {
        if ($company->manager_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        $user->update(['company_id' => null]);

        // Broadcast to Pusher
        $this->pusher->trigger(
            'private-user.' . $user->id,
            'company.employee.removed',
            [
                'company' => $company,
                'user' => $user,
            ]
        );

        return response()->json([
            'message' => 'Employee removed successfully',
            'data' => $user,
        ]);
    }
} 