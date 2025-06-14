<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use App\Services\ResponseService;
use Google\Rpc\Context\AttributeContext\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Pusher\Pusher;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\CloudMessage;

class MessageController extends Controller
{
    protected $pusher;

    public function __construct()
    {
        $this->pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            config('broadcasting.connections.pusher.options')
        );
    }

    public function index(Request $request)
    {
        $messages = Message::where(function ($query) use ($request) {
            $query->where('sender_id', $request->user()->id)
                ->where('receiver_id', $request->receiver_id);
        })->orWhere(function ($query) use ($request) {
            $query->where('sender_id', $request->receiver_id)
                ->where('receiver_id', $request->user()->id);
        })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return ResponseService::response([
            'status' => 200,
            'data' => $messages,
            'meta' => true,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required_without:images|string',
            'images.*' => 'required_without:content|image|max:10240', // 10MB max
        ]);

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->content ?? null,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $message->addMedia($image)
                    ->toMediaCollection('images');
            }
        }

        $message->load(['sender', 'receiver']);

        // Broadcast to Pusher
        $this->pusher->trigger(
            'private-user.' . $request->receiver_id,
            'message.new',
            $message
        );

        // Send Firebase notification to all receiver's devices
        $receiverDeviceTokens = DB::table('personal_access_tokens')
            ->where('tokenable_id', $request->receiver_id)
            ->whereNotNull('device_token')
            ->pluck('device_token')
            ->unique()
            ->toArray();

        if (!empty($receiverDeviceTokens)) {
            $notification = Notification::create(
                'New Message',
                $request->user()->full_name . ' sent you a message'
            );

            $messageData = CloudMessage::new()
                ->withNotification($notification)
                ->withData(['message_id' => $message->id]);

            app('firebase.messaging')->sendMulticast($messageData, $receiverDeviceTokens);
        }

        return ResponseService::response([
            'status' => 201,
            'message' => 'Message sent successfully',
            'data' => $message,
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $message = Message::find($id);

        if (!$message) {
            MessageService::abort(404, 'Message not found');
        }

        $user = User::auth();

        if (!$user || $message->receiver_id !== $user->id) {
            MessageService::abort(403, 'Unauthorized');
        }

        $message->update(['read_at' => now()]);

        // Broadcast to Pusher
        $this->pusher->trigger(
            'private-user.' . $message->sender_id,
            'message.read',
            $message
        );

        return ResponseService::response([
            'status' => 200,
            'message' => 'Message marked as read',
            'data' => $message,
        ]);
    }

    public function destroy($id)
    {
        $message = Message::find($id);

        if (!$message) {
            MessageService::abort(404, 'Message not found');
        }

        $user = User::auth();

        if (!$user || $message->sender_id !== $user->id) {
            MessageService::abort(403, 'Unauthorized');
        }

        $message->delete();

        return ResponseService::response([
            'status' => 200,
            'message' => 'Message deleted successfully',
        ]);
    }
}
