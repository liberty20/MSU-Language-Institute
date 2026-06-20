<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    private function isStudent($user)
    {
        return $user->hasRole('student');
    }

    private function canChat($userA, $userB)
    {
        if ($userA->id === $userB->id) return false;
        // Students cannot use messaging at all
        if ($this->isStudent($userA) || $this->isStudent($userB)) return false;
        // All other authenticated users can message each other freely
        return true;
    }

    public function index()
    {
        $user = Auth::user();

        // Students are not allowed to use messaging
        if ($this->isStudent($user)) {
            abort(403, 'Students do not have access to the messaging system.');
        }

        // All non-student users see all other non-student users as contacts
        $contacts = User::where('id', '!=', $user->id)
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'student');
            })
            ->orderBy('name')
            ->with('roles')
            ->get();

        $contactsData = $contacts->map(function($contact) use ($user) {
            $lastMsg = Message::where(function($q) use ($user, $contact) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $contact->id);
                })
                ->orWhere(function($q) use ($user, $contact) {
                    $q->where('sender_id', $contact->id)->where('receiver_id', $user->id);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            return [
                'id'           => $contact->id,
                'name'         => $contact->name,
                'email'        => $contact->email,
                'role'         => $contact->roles->first() ? $contact->roles->first()->name : 'User',
                'unread_count' => Message::where('sender_id', $contact->id)
                    ->where('receiver_id', $user->id)
                    ->whereNull('read_at')
                    ->count(),
                'last_message' => $lastMsg ? [
                    'message'    => $lastMsg->message,
                    'created_at' => $lastMsg->created_at->toIso8601String(),
                ] : null,
            ];
        });

        return \Inertia\Inertia::render('Chat/Index', [
            'contacts' => $contactsData,
        ]);
    }


    public function getMessages($contactId)
    {
        $user = Auth::user();
        $contact = User::findOrFail($contactId);

        if (!$this->canChat($user, $contact)) {
            abort(403, 'Unauthorized communication.');
        }

        $messages = Message::where(function($q) use ($user, $contact) {
                $q->where('sender_id', $user->id)->where('receiver_id', $contact->id);
            })
            ->orWhere(function($q) use ($user, $contact) {
                $q->where('sender_id', $contact->id)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($msg) {
                return [
                    'id' => $msg->id,
                    'sender_id' => $msg->sender_id,
                    'receiver_id' => $msg->receiver_id,
                    'message' => $msg->message,
                    'read_at' => $msg->read_at ? $msg->read_at->toIso8601String() : null,
                    'created_at' => $msg->created_at->toIso8601String(),
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    public function sendMessage(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
        ]);

        $receiver = User::findOrFail($request->receiver_id);
        if (!$this->canChat($user, $receiver)) {
            abort(403, 'Unauthorized communication.');
        }

        $message = Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'message' => $request->message,
        ]);

        $receiver->notify(new \App\Notifications\SystemNotification(
            'Messages',
            'New Message',
            'New chat message from ' . $user->name . ': "' . \Str::limit($request->message, 30) . '"',
            route('chat.index'),
            ['message_id' => $message->id, 'sender_id' => $user->id]
        ));

        ActivityLog::log(
            'send_chat_message',
            'Sent chat message to ' . $receiver->name . ' (' . $receiver->email . ').',
            $message
        );

        return response()->json([
            'status' => 'success',
            'message' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'message' => $message->message,
                'created_at' => $message->created_at->toIso8601String(),
            ]
        ]);
    }

    public function markAsRead($contactId)
    {
        $user = Auth::user();
        Message::where('sender_id', $contactId)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['status' => 'success']);
    }

    public function getUnreadCount()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['unread_messages_count' => 0]);
        }

        $count = Message::where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json(['unread_messages_count' => $count]);
    }
}
