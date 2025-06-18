<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageImage;
use App\Models\User;
use App\Services\ImageService;
use App\Services\MessageService;
use App\Services\PusherService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\CloudMessage;

class MessageController extends Controller
{

    public function index(Request $request)
    {

        $user = User::auth();

        $messages = Message::where(function ($query) use ($request, $user) {
            $query->where('sender_id', $user->id)
                ->where('receiver_id', $request->receiver_id);
        })->orWhere(function ($query) use ($request, $user) {
            $query->where('sender_id', $request->receiver_id)
                ->where('receiver_id', $user->id);
        })
            ->with(['sender', 'receiver', 'messageImages'])
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
                $messageImage = MessageImage::create([
                    'message_id' => $message->id,
                    'path' => ImageService::storeImage($image, 'messages'),
                ]);
            }
        }

        $message->load(['sender', 'receiver', 'messageImages']);

        // Send Pusher notification
        $pusher = new PusherService();
        $pusherResult = $pusher->sendMessage('private-user.' . $request->receiver_id, 'message.new', [
            'message' => $message,
            'sender' => $message->sender,
            'timestamp' => now()->toISOString()
        ]);

        if ($pusherResult === false) {
            Log::warning('Failed to send Pusher notification for message', [
                'message_id' => $message->id,
                'receiver_id' => $request->receiver_id
            ]);
        } else {
            Log::info('Pusher notification sent successfully', [
                'message_id' => $message->id,
                'receiver_id' => $request->receiver_id
            ]);
        }

        // Send Firebase notification to all receiver's devices
        $receiverDeviceTokens = DB::table('personal_access_tokens')
            ->where('tokenable_id', $request->receiver_id)
            ->whereNotNull('device_token')
            ->pluck('device_token')
            ->unique()
            ->toArray();

        if (!empty($receiverDeviceTokens)) {
            try {
                $notification = Notification::create(
                    'New Message',
                    $request->user()->full_name . ' sent you a message'
                );

                $messageData = CloudMessage::new()
                    ->withNotification($notification)
                    ->withData(['message_id' => $message->id]);

                app('firebase.messaging')->sendMulticast($messageData, $receiverDeviceTokens);
                
                Log::info('Firebase notification sent successfully', [
                    'message_id' => $message->id,
                    'device_tokens_count' => count($receiverDeviceTokens)
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send Firebase notification', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage()
                ]);
            }
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

        // Send Pusher notification
        $pusher = new PusherService();
        $pusherResult = $pusher->sendMessage('private-user.' . $message->sender_id, 'message.read', [
            'message' => $message,
            'read_by' => $user,
            'timestamp' => now()->toISOString()
        ]);

        if ($pusherResult === false) {
            Log::warning('Failed to send Pusher notification for message read', [
                'message_id' => $message->id,
                'sender_id' => $message->sender_id
            ]);
        }

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


    // get chats
    public function getUserChats(Request $request){
        $user = User::auth();

        // Get all users that have exchanged messages with the authenticated user
        $chats = User::whereHas('sentMessages', function ($query) use ($user) {
            $query->where('receiver_id', $user->id); // Users who sent messages to me
        })
        ->orWhereHas('receivedMessages', function ($query) use ($user) {
            $query->where('sender_id', $user->id); // Users who received messages from me
        })
        ->withCount(['receivedMessages as unread_messages_count' => function($query) use ($user) {
            $query->whereNull('read_at')
                 ->where('receiver_id', $user->id);
        }])
        ->with(['receivedMessages' => function($query) use ($user) {
            $query->where('sender_id', $user->id)
                 ->latest()
                 ->take(1);
        }, 'sentMessages' => function($query) use ($user) {
            $query->where('receiver_id', $user->id)
                 ->latest()
                 ->take(1);
        }])
        ->paginate(20);

        // Add last message to each chat
        $chats->getCollection()->transform(function($chat) {
            $lastReceivedMessage = $chat->receivedMessages->first();
            $lastSentMessage = $chat->sentMessages->first();
            
            $chat->last_message = $lastReceivedMessage && $lastSentMessage 
                ? ($lastReceivedMessage->created_at > $lastSentMessage->created_at ? $lastReceivedMessage : $lastSentMessage)
                : ($lastReceivedMessage ?? $lastSentMessage);

            unset($chat->receivedMessages, $chat->sentMessages);
            return $chat;
        });

        return ResponseService::response([
            'status' => 200,
            'data' => $chats,
            'meta' => true,
        ]);
    }
}
