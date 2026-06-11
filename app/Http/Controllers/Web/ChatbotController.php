<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Model\ChatLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Backend for the on-site scripted chatbot. Creates a lead row on chat-open and enriches it as
 * the visitor answers; once a phone number is captured the lead is alerted to the sales inbox.
 * Endpoints are deliberately lightweight (no auth — public widget) and never throw on mail issues.
 */
class ChatbotController extends Controller
{
    /** Visitor opened the chat — create the engaged lead, return its id + the scripted flow. */
    public function start(Request $request)
    {
        $lead = ChatLead::create([
            'session_id' => (string) Str::uuid(),
            'page_url'   => $request->input('page_url'),
            'status'     => 'engaged',
            'transcript' => [],
        ]);

        if (config('chatbot.notify_on_engagement')) {
            $this->sendAlert($lead, true);
        }

        return response()->json([
            'id'    => $lead->id,
            'token' => $lead->session_id,
            'flow'  => config('chatbot.flow'),
            'ui'    => [
                'title'    => config('chatbot.title'),
                'subtitle' => config('chatbot.subtitle'),
                'primary'  => config('chatbot.primary'),
            ],
        ]);
    }

    /** One answer from the visitor — persist it, append to transcript, alert when phone arrives. */
    public function step(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'key'   => 'nullable|string',
            'value' => 'nullable|string|max:2000',
        ]);

        $lead = ChatLead::where('session_id', $request->token)->first();
        if (!$lead) {
            return response()->json(['ok' => false], 404);
        }

        $key = $request->input('key');
        $value = trim((string) $request->input('value'));

        // Append to the transcript regardless.
        $transcript = $lead->transcript ?? [];
        $transcript[] = ['key' => $key, 'value' => $value, 'at' => now()->toDateTimeString()];
        $lead->transcript = $transcript;

        // Persist into the matching column when this step maps to one.
        $fillable = ['name', 'company_name', 'location', 'phone', 'email', 'intent', 'product', 'quantity', 'message'];
        if ($key && in_array($key, $fillable, true) && $value !== '') {
            $lead->{$key} = $value;
        }

        // Promote to a callable lead + alert once we have a phone (only once).
        if (!empty($lead->phone) && !$lead->notified) {
            $lead->status = 'lead';
            $lead->notified = true;
            $lead->save();
            $this->sendAlert($lead, false);
        } else {
            $lead->save();
        }

        return response()->json(['ok' => true]);
    }

    /** Email the sales inbox. Best-effort — a mail failure never breaks the visitor's chat. */
    private function sendAlert(ChatLead $lead, bool $engagementOnly): void
    {
        $to = config('chatbot.notify_email');
        if (!$to) {
            return;
        }
        try {
            Mail::to($to)->send(new \App\Mail\ChatLeadAlert($lead, $engagementOnly));
        } catch (\Throwable $e) {
            // Mail not configured / delivery failed — swallow so the widget stays responsive.
        }
    }
}
