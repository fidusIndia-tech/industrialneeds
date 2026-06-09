<?php

namespace App\Http\Controllers\Web;

use App\CPU\CartManager;
use App\Http\Controllers\Controller;
use App\Model\Quote;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * RFQ MVP (PR 3) — customer-facing quote acceptance via the tokenised link from the
 * quote email. Guest-friendly: accept adds a price-locked line to the cart and sends
 * the customer into the normal checkout (which handles login + guest-cart merge).
 */
class QuoteController extends Controller
{
    public function show($token)
    {
        $quote = Quote::with('product')->where('accept_token', $token)->first();
        if (!$quote) {
            abort(404);
        }
        $this->expireIfNeeded($quote);

        return view('web-views.quote-view', compact('quote'));
    }

    public function accept($token, Request $request)
    {
        $quote = Quote::where('accept_token', $token)->first();
        if (!$quote) {
            abort(404);
        }
        $this->expireIfNeeded($quote);

        if ($quote->status !== 'quoted') {
            Toastr::warning(\App\CPU\translate('This quote can no longer be accepted.'));
            return redirect()->route('quote.show', $token);
        }

        // Add the quoted line to the cart at the locked price.
        $result = CartManager::add_quoted_to_cart($quote, $request);
        if (($result['status'] ?? 0) != 1) {
            Toastr::error($result['message'] ?? \App\CPU\translate('Could not add the quote to your cart.'));
            return redirect()->route('quote.show', $token);
        }

        $quote->status = 'accepted';
        if (auth('customer')->check()) {
            $quote->customer_id = auth('customer')->id();
        }
        $quote->save();

        // Let checkout_complete link the resulting order back to this quote.
        session(['accepted_quote_id' => $quote->id]);

        Toastr::success(\App\CPU\translate('Quote accepted — please complete your order.'));
        return redirect()->route('shop-cart');
    }

    public function reject($token)
    {
        $quote = Quote::where('accept_token', $token)->first();
        if (!$quote) {
            abort(404);
        }
        $this->expireIfNeeded($quote);

        if (in_array($quote->status, ['quoted', 'requested'])) {
            $quote->status = 'rejected';
            $quote->save();
            Toastr::success(\App\CPU\translate('You have declined this quote.'));
        }

        return redirect()->route('quote.show', $token);
    }

    /** Move a past-validity quote to 'expired' so it can't be accepted. */
    private function expireIfNeeded(Quote $quote)
    {
        if ($quote->status === 'quoted'
            && $quote->quote_valid_until
            && Carbon::parse($quote->quote_valid_until)->endOfDay()->isPast()) {
            $quote->status = 'expired';
            $quote->save();
        }
    }
}
