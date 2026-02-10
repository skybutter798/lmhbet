<?php
// /home/lmh/app/app/Http/Controllers/Admin/AdminSupportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSupportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');
        $q = trim((string) $request->query('q', ''));

        $tickets = SupportTicket::query()
            ->with('user')
            ->when($status !== 'all', fn($qq) => $qq->where('status', $status))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->whereHas('user', function ($u) use ($q) {
                    $u->where('username', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
                })->orWhere('subject', 'like', "%{$q}%");
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admins.support.index', compact('tickets', 'status', 'q'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['user', 'messages' => fn($q) => $q->orderBy('id')]);

        SupportMessage::query()
            ->where('ticket_id', $ticket->id)
            ->where('sender_role', SupportMessage::ROLE_USER)
            ->whereNull('read_by_admin_at')
            ->update(['read_by_admin_at' => now()]);

        return view('admins.support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($ticket, $data) {
            SupportMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => null,
                'sender_role' => SupportMessage::ROLE_ADMIN,
                'body' => $data['message'],
                'read_by_admin_at' => now(),
                'read_by_user_at' => null,
            ]);

            $ticket->status = SupportTicket::STATUS_OPEN;
            $ticket->last_message_at = now();
            $ticket->save();
        });

        return back()->with('success', 'Reply sent.');
    }

    public function close(SupportTicket $ticket)
    {
        $ticket->status = SupportTicket::STATUS_CLOSED;
        $ticket->closed_at = now();
        $ticket->save();

        return back()->with('success', 'Ticket closed.');
    }

    public function reopen(SupportTicket $ticket)
    {
        $ticket->status = SupportTicket::STATUS_OPEN;
        $ticket->closed_at = null;
        $ticket->save();

        return back()->with('success', 'Ticket reopened.');
    }
}