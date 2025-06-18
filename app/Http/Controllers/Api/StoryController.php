<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use App\Services\PusherService;
use App\Services\ImageService;
use App\Services\MessageService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Pusher\Pusher;
use Illuminate\Support\Facades\Log;

class StoryController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('is_active', true)
            ->whereHas('stories')
            ->with(['stories' => function($query) {
                $query->with('views')
                     ->orderBy('created_at', 'desc');
            }])
            ->paginate(20);

        // Transform each user to include their stories array starting from 1
        $users->getCollection()->transform(function($user) {
            $user->stories_array = $user->stories->values();
            unset($user->stories);
            return $user;
        });

        return ResponseService::response([
            'status' => 200,
            'data' => $users,
            'meta' => true,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required_without:image|string',
            'image' => 'required_without:content|image|max:10240', // 10MB max
        ]);

        if ($request->hasFile('image')) {
            $image = ImageService::storeImage($request->file('image'), 'stories');
        }

        $user = User::auth();

        $story = Story::create([
            'user_id' => $user->id,
            'content' => $request->content ?? null,
            'image' => $image ?? null,
        ]);

        $story->load(['user', 'views']);

        // Send Pusher notification
        $pusher = new PusherService();
        $pusherResult = $pusher->sendMessage('stories', 'story.new', [
            'story' => $story,
            'user' => $story->user,
            'timestamp' => now()->toISOString()
        ]);

        if ($pusherResult === false) {
            Log::warning('Failed to send Pusher notification for new story', [
                'story_id' => $story->id,
                'user_id' => $user->id
            ]);
        } else {
            Log::info('Pusher notification sent successfully for new story', [
                'story_id' => $story->id,
                'user_id' => $user->id
            ]);
        }

        return ResponseService::response([
            'status' => 201,
            'message' => 'Story created successfully',
            'data' => $story,
        ]);
    }

    public function show($id)
    {

        $story = Story::find($id);

        if (!$story) {
            MessageService::abort(404, 'Story not found');
        }

        $story->load(['user', 'views']);

        return ResponseService::response([
            'status' => 200,
            'data' => $story,
        ]);
    }

    public function view(Request $request, $id)
    {
        $story = Story::find($id);

        if (!$story) {
            MessageService::abort(404, 'Story not found');
        }

        $view = StoryView::updateOrCreate(
            [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
            ],
            [
                'is_favorite' => $request->is_favorite ?? false,
            ]
        );

        // Send Pusher notification
        $pusher = new PusherService();
        $pusherResult = $pusher->sendMessage('private-user.' . $story->user_id, 'story.view', [
            'story' => $story,
            'view' => $view,
            'viewer' => $request->user(),
            'timestamp' => now()->toISOString()
        ]);

        if ($pusherResult === false) {
            Log::warning('Failed to send Pusher notification for story view', [
                'story_id' => $story->id,
                'viewer_id' => $request->user()->id
            ]);
        }

        return ResponseService::response([
            'status' => 200,
            'message' => 'Story viewed successfully',
            'data' => $view,
        ]);
    }

    public function destroy($id)
    {
        $story = Story::find($id);

        if (!$story) {
            MessageService::abort(404, 'Story not found');
        }

        $user = User::auth();

        if (!$user || $story->user_id !== $user->id) {
            MessageService::abort(403, 'Unauthorized');
        }

        $story->delete();

        return ResponseService::response([
            'status' => 200,
            'message' => 'Story deleted successfully',
            'data' => $story,
        ]);
    }
}
