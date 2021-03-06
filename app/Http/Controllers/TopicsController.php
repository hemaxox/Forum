<?php

namespace App\Http\Controllers;

use Auth;
use App\User;
use App\Post;
use App\Topic;
use App\Subscription;
use App\Events\TopicDeleted;
use Illuminate\Http\Request;
use App\Events\UsersMentioned;
use App\Http\Requests\CreateTopicFormRequest;

class TopicsController extends Controller
{

    protected function getMentionedUsers (Request $request)
    {
        // @mention functionality
        $matches = [];
        $mentioned_users = collect([]);
        // get usernames mentioned and put into $matches
        preg_match_all('/\@\w+/', $request->post, $matches);
        for ($i = 0; $i < count($matches[0]); $i++) {
            // get User objects from mentioned @usernames
            $mentioned_users->push(User::where('name', str_replace('@', '', $matches[0][$i]))->first());
        }
        // remove current user from list of mentioned users, we don't want to email them about mentioning themselves, if they happen to..
        $mentioned_users = $mentioned_users->reject(function ($value, $key) {
            return $value->id === Auth::user()->id;
        });

        return $mentioned_users;
    }

    public function index()
    {
        $topics = Topic::all();

        return view('forum.topics.index', [
            'topics' => $topics,
        ]);
    }

    public function show(Request $request, Topic $topic)
    {
        $posts = $topic->posts()->get();

        return view('forum.topics.topic.index', [
            'topic' => $topic,
            'posts' => $posts,
        ]);
    }

    public function showCreateForm()
    {
        return view('forum.topics.topic.create.form');
    }

    public function create(CreateTopicFormRequest $request)
    {
        //$request->title ==== topic title
        $topic = new topic();
        $topic->user_id = $request->user()->id;
        // str_slug will basically strip all special chars and replace with hyphens.
        // be careful, as slug is to be unique, but hello&1 is evaluated as hello1 and hello.1 is also evaluated as hello1
        $topic->slug = str_slug(mb_strimwidth($request->title, 0, 255), '-');
        $topic->title = $request->title;
        $topic->save();

        $post = new Post();
        $post->topic_id = $topic->id;
        $post->user_id = $request->user()->id;
        $post->body = $request->post;

        // change @username to markdown links
        $url = env('APP_URL');
        $post->body = preg_replace('/\@\w+/', "[\\0]($url/user/profile/\\0)", $request->post);

        $post->save();

        $mentioned_users = $this->getMentionedUsers($request);
        if (count($mentioned_users)) {
            event(new UsersMentioned($mentioned_users, $topic, $post));
        }

        $subscription = new Subscription();
        $subscription->topic_id = $topic->id;
        $subscription->user_id = $request->user()->id;
        $subscription->subscribed = ($request->subscribe === null ? 0 : 1);
        $subscription->save();

        return redirect()->route('forum.topics.topic.show', [
            'topic' => $topic,
        ]);
    }

    public function destroy(Request $request, Topic $topic)
    {
        // don't need to use policy here, as auth.elevated middleware is being use for the route associated with this controller method invocation
        $topic->delete();

        if ($topic->user->id !== $request->user()->id) {
            // don't want to send email to the owner of the topic, if they deleted it
            event(new TopicDeleted($topic));
        }

        return redirect()->route('forum.topics.index');
    }
}
