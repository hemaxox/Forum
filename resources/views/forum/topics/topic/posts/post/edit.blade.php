@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <p><a href="{{ route('forum.topics.topic.show', $topic) }}">&laquo; Back to the topic</a></p>
            <div class="panel panel-default">
                <div class="panel-heading">Edit the post</div>

                <div class="panel-body">
                    <form action="{{ route('forum.topics.topic.posts.post.update', [$topic, $post]) }}" method="post">
                        <div class="form-group{{ $errors->has('post') ? ' has-error' : '' }}">
                            <label for="post" class="control-label">Post</label>
                            <input type="text" name="post" id="post" class="form-control" value="{{ $post->body }}">
                            @if ($errors->has('post'))
                                <div class="help-block danger">
                                    {{ $errors->first('post') }}
                                </div>
                            @endif
                        </div>
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-default pull-right">Update</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection