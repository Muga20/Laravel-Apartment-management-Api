<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\HomePaymentTypes;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Str;

class PaymentSettingController extends Controller
{

    public function paymentSetting(Request $request)
    {
        $data = $this->loadCommonData($request);
        $payments = PaymentType::all();

        return view('pages.Setting.Payment.payments', compact('payments') + $data);
    }

    public function deactivatePayment($dummy, $deactivate = null)
    {
        try {
            $payment = PaymentType::find($deactivate);

            if (!$payment) {
                return redirect()->back()->with('error', 'Payment type not found.');
            }

            $newStatus = $payment->status === 'active' ? 'inactive' : 'active';

            $payment->update([
                'status' => $newStatus,
            ]);

            $successMessage = $newStatus === 'inactive' ? 'Payment type deactivated successfully.' : 'Payment type activated successfully.';

            return redirect()->back()->with('success', $successMessage);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to deactivate/activate Payment type. Please try again.');
        }
    }

    public function createPayment(Request $request)
    {
        $data = $this->loadCommonData($request);

        return view('pages.Setting.Payment.create',  $data);
    }

    public function storePayment(Request $request)
    {
        try {

            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $paymentId = PaymentType::count() + 1;
            $slug = Str::slug($request->input('name'), '-') . '-' .  $paymentId;

            $paymentType = new PaymentType();
            $paymentType->name = $request->name;
            $paymentType->status = 'active';
            $paymentType->slug = $slug;


            $paymentType->save();

             return redirect()->back()->with('success', 'Payment type created successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to create Payment type. Please try again.');
        }
    }

    public function editPayment(Request $request, $dummy, $payment)
    {
        $data = $this->loadCommonData($request);

        $paymentType = PaymentType::where('slug', $payment)->firstOrFail();

        return view('pages.Setting.Payment.edit', compact('paymentType') + $data);
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

    public function deletePaymentAction( $dummy, $paymentId )
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


    public function deleteThisPayment( $dummy, $payment )
    {
        try {
            $payments = PaymentType::where('id', $payment)->firstOrFail();

            $payments->delete();
            return redirect()->back()->with('success', 'Payment deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete Payment: ' . $e->getMessage());
        }
    }




}
