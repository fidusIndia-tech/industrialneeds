<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Quote;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * RFQ MVP (PR 2) — admin-only quote management. Admin lists requests, opens one,
 * and responds with a price; the customer is emailed a tokenised accept link.
 */
class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status'); // optional filter
        $query = Quote::with('product')->orderBy('created_at', 'desc');
        if ($status) {
            $query->where('status', $status);
        }
        $quotes = $query->paginate(20)->appends(['status' => $status]);

        return view('admin-views.quotes.list', compact('quotes', 'status'));
    }

    public function show($id)
    {
        $quote = Quote::with('product')->findOrFail($id);

        return view('admin-views.quotes.show', compact('quote'));
    }

    public function respond(Request $request, $id)
    {
        $request->validate([
            'quoted_unit_price' => 'required|numeric|min:0',
            'quoted_qty' => 'nullable|integer|min:1',
            'quote_valid_until' => 'nullable|date',
            'admin_note' => 'nullable|string',
        ]);

        $quote = Quote::findOrFail($id);

        // Only respond to live requests (allow re-quoting one already quoted).
        if (!in_array($quote->status, ['requested', 'quoted'])) {
            Toastr::warning(\App\CPU\translate('This quote can no longer be responded to.'));
            return back();
        }

        // Admin enters the price in the display currency; store in USD base like unit_price.
        $quote->quoted_unit_price = \App\CPU\BackEndHelper::currency_to_usd($request->quoted_unit_price);
        $quote->quoted_qty = $request->quoted_qty ?: $quote->requested_qty;
        $quote->quote_valid_until = $request->quote_valid_until;
        $quote->admin_note = $request->admin_note;
        $quote->status = 'quoted';
        $quote->save();

        // Email the customer their quote with the tokenised accept link (best-effort).
        if ($quote->email) {
            try {
                Mail::to($quote->email)->send(new \App\Mail\QuoteOffered($quote));
            } catch (\Exception $e) {
                // Mail not configured — don't block the admin action.
            }
        }

        Toastr::success(\App\CPU\translate('Quote sent to the customer.'));
        return redirect()->route('admin.quotes.show', $quote->id);
    }
}
