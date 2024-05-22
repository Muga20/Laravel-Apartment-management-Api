<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Controller;
use App\Mail\ActivateAcc;
use App\Services\CompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class DeactivateUserController extends Controller
{
    public function deactivateMyAccount(Request $request)
    {
        DB::beginTransaction();

        try {
            // Assuming you have a method to load common data including user
            $data = $this->loadCommonData($request);
            $userInstance = $data['user'];
            $userInstance->update([
                'authType' => 'token',
                'status' => 'deactivated',
                'updated_at' => now(),
            ]);

            DB::commit();

            // Assuming you have a method to handle logout logic
            $loginController = app(LoginController::class);
            $response = $loginController->logout($request);

            return response()->json([
                'success' => true,
                'message' => 'Account deactivated successfully.',
                'data' => $response
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendToken(Request $request)
    {
        try {
            $compressionService = new CompressionService();
            $compressedEmail = $compressionService->compressAttribute($request->input('email'));

            $user = User::where('email', $compressedEmail)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            Mail::to($user->email)->send(new ActivateAcc($user, $user->company));

            return response()->json([
                'success' => true,
                'message' => 'Account backup email sent successfully. Please check your inbox.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send backup email.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
