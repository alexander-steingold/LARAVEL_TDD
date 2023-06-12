<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StoreRequest;
use App\Http\Requests\Post\UpdateRequest;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::all();
        return view('posts.index', compact('posts'));
    }

    public function show(Post $post)
    {
        return view('posts.show', compact('post'));
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        if(!empty($data['image'])) {
            $path = Storage::disk('local')->put('/images', $data['image']);
            $data['image'] = $path;
        }

        Post::create($data);
    }

    public function update(UpdateRequest $request, Post $post)
    {
        $data = $request->validated();
        if(!empty($data['image'])) {
            $path = Storage::disk('local')->put('/images', $data['image']);
            $data['image'] = $path;
        }

        $post->update($data);
    }

}
