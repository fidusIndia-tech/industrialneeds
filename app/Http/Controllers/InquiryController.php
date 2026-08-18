<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Inquiry;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewInquiryAlert;

class InquiryController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validate the data coming from the form
        $validatedData = $request->validate([
            'customer_name' => 'required|string|max:255',
            'phone_number'  => 'required|string|max:20',
            'email'         => 'nullable|email',
            'message'       => 'required|string',
            'product_id'    => 'nullable|integer'
        ]);

        // 2. Save to the database
        $newInquiry = Inquiry::create($validatedData);

        // 3. Alert the sales inbox (best-effort — the lead is already saved, so a
        //    mail failure must not throw an error onto the customer's screen).
        $notify = config('leads.notify_email');
        if ($notify) {
            try {
                Mail::to($notify)->send(new NewInquiryAlert($newInquiry));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // 4. Send the customer back with a success message
        return redirect()->back()->with('success', 'Thank you! Our team will call you shortly.');
    }
}