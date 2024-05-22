<?php

namespace App\Http\Controllers\Help;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\CompressionService;
use Illuminate\Http\Request;


class ContactController extends Controller
{

    public function showMyMessages(Request $request)
    {
        $data = $this->loadCommonData($request);
        return view('pages.Contact.showMessage' ,$data );
    }

    public function conversation(Request $request, $company, $with)
    {
        $data = $this->loadCommonData($request);
        $user = $data['user'];

        $otherUser = User::where('uuid', $with)->firstOrFail();

        // Fetch messages between the current user and the other user
        $messages = Message::where(function ($query) use ($user, $otherUser) {
            $query->where('recipients_id', $user->id)
                ->where('senders_id', $otherUser->id);
        })
            ->orWhere(function ($query) use ($user, $otherUser) {
                $query->where('recipients_id', $otherUser->id)
                    ->where('senders_id', $user->id);
            })
            ->latest()
            ->get();

        $sender = User::where('uuid', $with)->first();

        $senderMessages = Message::where('senders_id', $otherUser->id)
            ->where('recipients_id', $user->id)
            ->where('status', '!=', 'viewed')
            ->get();

        foreach ($senderMessages as $message) {
            $message->status = 'viewed';
            $message->save();
        }
        return view('pages.Contact.conversation', compact('messages', 'sender')+$data);
    }


    public function showMessages(Request $request)
    {
        $data = $this->loadCommonData($request);
        $user = $data['user'];

        // Retrieve the latest message from each sender along with their counts
        $latestMessages = Message::selectRaw('MAX(created_at) as latest_created_at, senders_id, COUNT(*) as message_count')
            ->where('recipients_id', $user->id)
            ->groupBy('senders_id');

        // Join the latest messages back to the main messages table to get the complete message data
        $messages = Message::joinSub($latestMessages, 'latest_messages', function ($join) {
            $join->on('messages.senders_id', '=', 'latest_messages.senders_id')
                ->on('messages.created_at', '=', 'latest_messages.latest_created_at');
        })->get();

        // Decrement count for messages with status 'notViewed'
        foreach ($messages as $message) {
            $message->message_count -= Message::where('senders_id', $message->senders_id)
                ->where('recipients_id', $user->id)
                ->where('status', 'viewed')
                ->count();
        }

        return view('pages.Contact.showMessage', compact('messages') + $data);
    }

    public function replyMessage(Request $request)
    {
        try {
            // Load common data
            $data = $this->loadCommonData($request);
            $user = $data['user'];
            $company = $data['company'];

            // Validate the incoming request data
            $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
            ]);

            // Show "Sending" button and hide "Send" button
            $request->session()->flash('sending', true);

            // Create a new message instance
            $message = new Message();
            $message->senders_id = $user->id;
            $message->company_id = $company->id;
            $message->recipients_id = $request->input('recipients_id');
            $message->message = $request->input('message');
            $message->subject = $request->input('subject');
            $message->status = "notViewed";

            // Save the message
            $message->save();

            return redirect()->back()->with('success', 'Message sent');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send message: ' . $e->getMessage());
        }
    }


    public function createMessage(Request $request)
    {
        $data = $this->loadCommonData($request);
        return view('pages.Contact.contact' ,$data );
    }

    public function storeMessage(Request $request)
    {
        $data = $this->loadCommonData($request);
        $validatedData = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $validatedData['company_id'] = $data['company']->id;
        $validatedData['senders_id'] = $data['user']->id;
        $validatedData['status'] = "notViewed";

        try {
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            if ($admins->isEmpty()) {
                throw new \Exception('No admin users found.');
            }

            foreach ($admins as $admin) {
                $validatedData['recipients_id'] = $admin->id;
                Message::create($validatedData);
            }

            return redirect()->back()->with('success', 'Message sent successfully to all admin users.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send message: ' . $e->getMessage());
        }
    }

}