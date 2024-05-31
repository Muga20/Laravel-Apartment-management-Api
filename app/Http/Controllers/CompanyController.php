<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\UserService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\NewUserSession;
use App\Mail\NewAccount;
use App\Services\CompressionService;

class CompanyController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $data = $this->loadCommonData($request);

        $companyUsers = User::where('company_id', $data['company']->id)->get();

        return view('pages.Company.profile', compact('companyUsers'), $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $data = $this->loadCommonData($request);
        $data['companies'] = Company::all();

        return view('pages.Company.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->errors()->first(),
                ], 422);
            }

            $validatedData = $validator->validated();
            $validatedData['status'] = 'inactive';

            $slug = Str::slug($validatedData['name']);
            $suffix = 1;
            while (Company::where('slug', $slug)->exists()) {
                $slug = Str::slug($validatedData['name']) . '-' . $suffix++;
            }

            $company = new Company();
            $company->fill($validatedData);

            $randomNumber = 'cm' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

            $company->companyId = $randomNumber;
            $company->slug = $slug;

            $image = $request->file('logoImage');
            $uploadedImageUrl = Cloudinary::upload($image->getRealPath())->getSecurePath();

            $company->logoImage = $uploadedImageUrl;

            $company->save();

            return response()->json(['success' => 'Company Created Successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create company. Please try again.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function companyOwner(Request $request)
    {
        $data = $this->loadCommonData($request);

        $companies = Company::pluck('name', 'id')->toArray();

        return view('pages.Company.companyOwnerRegistration', $data +
            compact('companies'));
    }


    public function companyOwnerRegistration(Request $request)
    {
        $data = $this->loadCommonData($request);

        $response = $this->userService->createUser($request, $data);

        if ($response instanceof User) {
            try {
                $request->validate([
                    'company_id' => 'required|exists:companies,id',
                ]);

                $compressionService = new CompressionService();
                $compressedEmail = $compressionService->compressAttribute($request->input('email'));

                $user = User::where('email', $compressedEmail)->first();

                $user->update(['' => null]);

                $company = Company::findOrFail($request->input('company_id'));

                $this->sendNewAccountEmail($user, $company);

                return response()->json(['success' => 'User Created Successfully'], 200);
            } catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'Company not found.'], 404);
            }
        } else {
            return $response;
        }
    }

    private function sendNewAccountEmail(User $user, Company $company)
    {
        //$authToken = sha1($user->id);

        $authLink = str_replace('.', '_', base64_encode($user->id . '|' . now()->timestamp . '|' . Str::random(40)));

        NewUserSession::create([
            'email' => $user->email,
            'token' => $authLink,
            //'otp_code' => $authToken,
        ]);

        $resetLink = 'http://localhost:5173/auth/new-account/' . $authLink;

        Mail::to($user->email)->queue(new NewAccount($user, $company, $resetLink));

    }

    public function showAvailableCompanies(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);
            $keyword = $request->input('keyword');
            $page = $request->input('page', 1);
            $perPage = 15;
            $status = $request->input('status');
            $location = $request->input('location');

            $companiesQuery = Company::query()
                ->where('name', 'like', "%{$keyword}%")
                ->where('status', 'like', "%{$status}%");

            if ($location !== null) {
                $companiesQuery->where('location', 'like', "%{$location}%");
            }

            $companies = $companiesQuery->withCount('users')
                ->latest()
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'companies' => $companies,
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching the companies data.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // Existing methods...

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);
            $company->delete();

            return response()->json([
                'message' => 'Company deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete company',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate the specified resource in storage.
     */
    public function deactivate(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            if ($company->status === 'active') {
                $company->status = 'inactive';
                $message = 'Company deactivated successfully';
            } else {
                $company->status = 'active';
                $message = 'Company activated successfully';
            }

            $company->save();

            return response()->json([
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to toggle company status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
