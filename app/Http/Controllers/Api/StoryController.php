<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use App\Services\ImageService;
use App\Services\MessageService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Pusher\Pusher;

class StoryController extends Controller
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
        $stories = Story::with(['user', 'views'])
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return ResponseService::response([
            'status' => 200,
            'data' => $stories,
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

        $story = Story::create([
            'user_id' => $request->user()->id,
            'content' => $request->content ?? null,
            'image' => $image ?? null,
        ]);

        $story->load(['user', 'views']);

        // // Broadcast to Pusher
        // $this->pusher->trigger(
        //     'presence-chat.' . $request->user()->company_id,
        //     'story.new',
        //     $story
        // );

        return ResponseService::response([
            'status' => 201,
            'message' => 'Story created successfully',
            'data' => $story,
        ]);
    }

    public function show(Request $request, Story $story)
    {
        $story->load(['user', 'views']);

        return ResponseService::response([
            'status' => 200,
            'data' => $story,
        ]);
    }

    public function view(Request $request, Story $story)
    {
        $view = StoryView::updateOrCreate(
            [
                'story_id' => $story->id,
                'user_id' => $request->user()->id,
            ],
            [
                'is_favorite' => $request->is_favorite ?? false,
            ]
        );

        // Broadcast to Pusher
        $this->pusher->trigger(
            'private-user.' . $story->user_id,
            'story.view',
            [
                'story' => $story,
                'view' => $view,
            ]
        );

        return ResponseService::response([
            'status' => 200,
            'message' => 'Story viewed successfully',
            'data' => $view,
        ]);
    }

    public function destroy(Request $request, Story $story)
    {
        if ($story->user_id !== $request->user()->id) {
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
