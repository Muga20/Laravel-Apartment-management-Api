<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Exception;
use App\Models\Company;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


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

        return view('pages.Company.profile' ,compact('companyUsers' ), $data);
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
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
            ]);

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

            $company->save();


            return redirect()->back()->with('success', 'Company Created Successfully');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to create company. Please try again.');
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
            compact( 'companies'));
    }

    public function companyOwnerRegistration(Request $request)
    {
        $response = $this->userService->createUser($request);

        if ($response instanceof User) {
            $user = $response;

            try {
//                $company = Company::findOrFail($validatedData['company_id']);
//                Mail::to($validatedData['email'])->send(new NewAccount($user, $company));
                return redirect()->back()->with('success', 'User Created Successfully');
            } catch (ModelNotFoundException $e) {
                return redirect()->back()->with('error', 'Company not found.');
            }
        } else {
            return $response;
        }
    }


    public function showAvailableCompanies(Request $request)
    {
        $data = $this->loadCommonData($request);
        $keyword = $request->input('keyword');
        $companies = Company::query()
            ->where('name', 'like', "%{$keyword}%")
            ->withCount('users')
            ->latest()
            ->paginate(10);

        return view('pages.Company.show', compact('companies') + $data);
    }


}

