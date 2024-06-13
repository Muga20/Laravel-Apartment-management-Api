<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    use ImageTrait;

    public function settingIndex(Request $request)
    {
        $data = $this->loadCommonData($request);

        return view('pages.Setting.Company.index', $data);
    }

    public function editCompanyProfile(Request $request)
    {
        $data = $this->loadCommonData($request);
        return view('pages.Company.update', $data);
    }

    public function storeEditedProfile(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);

            $rules = [
                'phone' => 'required|digits:10',
            ];

            $request->validate($rules);

            $companyData = $request->only([
                'name', 'email', 'status', 'address',
                'phone', 'description', 'location', 'companyUrl',
            ]);

            $this->updateImage($request, $companyData, 'logoImage');

            $companyData['slug'] = Str::slug($companyData['name']);

            $company = $data['company'];

            $company->update($companyData);

            return response()->json([
                'success' => true,
                'message', 'Company details updated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message', 'Failed to update company: ' . $e->getMessage()], 500);
        }
    }

}
