<?php
// /home/lmh/app/app/Http/Controllers/Support/SupportTicketController.php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $tickets = SupportTicket::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('support.index', [
            'title' => 'Message',
            'tickets' => $tickets,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = null;

        DB::transaction(function () use ($user, $data, &$ticket) {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'subject' => $data['subject'],
                'status' => SupportTicket::STATUS_OPEN,
                'last_message_at' => now(),
            ]);

            SupportMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'sender_role' => SupportMessage::ROLE_USER,
                'body' => $data['message'],
                'read_by_admin_at' => null,
                'read_by_user_at' => now(),
            ]);
        });

        return redirect()->route('support.show', $ticket)->with('success', 'Message sent.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $user = $request->user();

        abort_unless((int)$ticket->user_id === (int)$user->id, 403);

        $messages = $ticket->messages()
            ->orderBy('id')
            ->get();

        SupportMessage::query()
            ->where('ticket_id', $ticket->id)
            ->where('sender_role', SupportMessage::ROLE_ADMIN)
            ->whereNull('read_by_user_at')
            ->update(['read_by_user_at' => now()]);

        return view('support.show', [
            'title' => 'Message',
            'ticket' => $ticket,
            'messages' => $messages,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $user = $request->user();

        abort_unless((int)$ticket->user_id === (int)$user->id, 403);
        abort_if($ticket->status === SupportTicket::STATUS_CLOSED, 422);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($user, $ticket, $data) {
            SupportMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'sender_role' => SupportMessage::ROLE_USER,
                'body' => $data['message'],
                'read_by_user_at' => now(),
                'read_by_admin_at' => null,
            ]);

            $ticket->status = SupportTicket::STATUS_OPEN;
            $ticket->last_message_at = now();
            $ticket->save();
        });

        return back()->with('success', 'Message sent.');
    }
}