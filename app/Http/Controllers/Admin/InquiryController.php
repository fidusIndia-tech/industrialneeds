<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Inquiry; // Grabbing your legacy Model structure!

class InquiryController extends Controller
{
    // This loads the main list of all inquiries
    public function index()
    {
        // Fetch all leads, newest first
        $leads = Inquiry::orderBy('created_at', 'desc')->get();

        // Send them to the Blade HTML file we are about to build
        return view('admin-views.inquiries.list', compact('leads'));
    }

    // This loads the specific details when the founder clicks "View" on one lead
    public function view($id)
    {
        $lead = Inquiry::findOrFail($id);
        
        return view('admin-views.inquiries.view', compact('lead'));
    }
}