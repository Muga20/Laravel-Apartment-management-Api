<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\HomePaymentTypes;
use App\Models\PaymentType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentSettingController extends Controller
{

    public function paymentModeTypes(Request $request)
    {
        try {
            $data = $this->loadCommonData($request);
            $payments = PaymentType::all();
            return response()->json([
                'payments' => $payments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get payments',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deactivatePayment($deactivate = null)
    {
        try {
            $payment = PaymentType::find($deactivate);

            if (!$payment) {
                return response()->json([
                    'error' => 'Payment type not found.',
                ]);
            }

            $newStatus = $payment->status === 'active' ? 'inactive' : 'active';

            $payment->update([
                'status' => $newStatus,
            ]);

            $successMessage = $newStatus === 'inactive' ? 'Payment type deactivated successfully.' : 'Payment type activated successfully.';

            return response()->json([
                'message' => $successMessage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to Deactivate / Activate payments',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function storePayment(Request $request)
    {
        try {

            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $paymentId = PaymentType::count() + 1;
            $slug = Str::slug($request->input('name'), '-') . '-' . $paymentId;

            $paymentType = new PaymentType();
            $paymentType->name = $request->name;
            $paymentType->status = 'active';
            $paymentType->slug = $slug;

            $paymentType->save();

            return response()->json([
                'message' => 'Payment Created Successfully ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to Create payments',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updatePayment(Request $request, $dummy, $payment)
    {
        try {
            $paymentType = PaymentType::where('slug', $payment)->firstOrFail();

            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $paymentType->name = $request->name;
            $paymentType->save();

            return redirect()->back()->with('success', 'Payment type updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to update Payment type. Please try again.');
        }
    }

    public function createCompanyPayment(Request $request, $dummy, $paymentId)
    {
        try {
            $data = $this->loadCommonData($request);

            $requiredRoles = $this->rolesThatMustHave(2);

            if (!$this->hasRequiredRoles($data, $requiredRoles)) {
                return $this->unauthorizedResponse();
            }

            $existingPayment = HomePaymentTypes::where('home_id', $paymentId)
                ->where('payment_type_id', $request->input('payment_type_id'))
                ->first();

            if ($existingPayment) {
                return redirect()->back()->with('error', 'Company already has this Payment type.');
            }

            $paymentType = new HomePaymentTypes();
            $paymentType->home_id = $paymentId;
            $paymentType->payment_type_id = $request->input('payment_type_id');
            $paymentType->save();

            return redirect()->back()->with('success', 'Payment type created successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to create Payment type. Please try again.');
        }
    }

    public function deletePaymentAction($dummy, $paymentId)
    {

        try {
            $payments = HomePaymentTypes::where('payment_type_id', $paymentId)
                ->firstOrFail();
            $payments->delete();

            return redirect()->back()->with('success', 'Payment  removed successfully from user.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to remove Payment from user: ' . $e->getMessage());
        }
    }

    public function deleteThisPayment($payment)
    {
        try {
            $paymentType = PaymentType::findOrFail($payment);

            if ($paymentType->status === 'active') {
                return response()->json([
                    'error' => 'Payment type is active and cannot be deleted.',
                ], 400);
            }

            $paymentType->delete();

            return response()->json([
                'message' => 'Payment deleted Successfully ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete payments',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
