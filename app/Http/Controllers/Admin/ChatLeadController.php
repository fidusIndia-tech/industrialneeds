<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\ChatLead;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

/**
 * Admin view of chatbot leads. Mirrors the Quote Requests screen: list (status filter),
 * detail with transcript, and a status update so the team can track who's been called.
 */
class ChatLeadController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $query = ChatLead::orderByDesc('id');
        if ($status) {
            $query->where('status', $status);
        } else {
            // Default: hide bare "engaged" (opened but no details) unless explicitly filtered.
            $query->where('status', '!=', 'engaged');
        }
        $leads = $query->paginate(20)->appends(['status' => $status]);
        $engagedCount = ChatLead::where('status', 'engaged')->count();

        return view('admin-views.chat-leads.index', compact('leads', 'status', 'engagedCount'));
    }

    public function show($id)
    {
        $lead = ChatLead::findOrFail($id);
        return view('admin-views.chat-leads.show', compact('lead'));
    }

    public function status(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:engaged,lead,contacted,closed']);
        $lead = ChatLead::findOrFail($id);
        $lead->status = $request->status;
        $lead->save();
        Toastr::success(\App\CPU\translate('Lead status updated.'));
        return back();
    }
}
