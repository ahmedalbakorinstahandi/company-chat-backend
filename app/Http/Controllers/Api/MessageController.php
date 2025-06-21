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
            ->paginate($request->limit ?? 20);

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
        $pusherResult = $pusher->sendMessage('user.' . $request->receiver_id, 'message.new', [
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

        // if (!$user || $message->receiver_id !== $user->id) {
        //     MessageService::abort(403, 'Unauthorized');
        // }

        $message->update(['read_at' => now()]);

        // Send Pusher notification
        $pusher = new PusherService();
        $pusherResult = $pusher->sendMessage('user.' . $message->sender_id, 'message.read', [
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
    public function getUserChats(Request $request)
    {
        $user = User::auth();

        // استرجاع الرسائل الأخيرة مع كل مستخدم تم التفاعل معه
        $messageQuery = Message::where(function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id);
        })
            ->latest('created_at')
            ->get();

        // إنشاء المحادثات المميزة حسب كل مستخدم (الشخص الآخر فقط)
        $grouped = $messageQuery->groupBy(function ($message) use ($user) {
            return $message->sender_id === $user->id ? $message->receiver_id : $message->sender_id;
        });

        $chats = collect();

        foreach ($grouped as $otherUserId => $messages) {
            $otherUser = User::find($otherUserId);
            if (!$otherUser) continue;

            $lastMessage = $messages->first();

            $unreadCount = $messages->where('receiver_id', $user->id)
                ->where('sender_id', $otherUserId)
                ->whereNull('read_at')
                ->count();

            // تركيبه بنفس التنسيق المتوقع
            $chat = clone $otherUser;

            $chat->unread_messages_count = $unreadCount;
            $chat->last_message = $lastMessage;
            $chat->last_message->load('messageImages');

            unset($chat->sentMessages);
            unset($chat->receivedMessages);

            $chats->push($chat);
        }

        // دعم التصفّح
        $perPage = 20;
        $currentPage = request('page', 1);
        $pagedChats = $chats->forPage($currentPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedChats,
            $chats->count(),
            $perPage,
            $currentPage,
            ['path' => url()->current()]
        );

        return ResponseService::response([
            'status' => 200,
            'data' => $paginator,
            'meta' => true,
        ]);
    }
}
