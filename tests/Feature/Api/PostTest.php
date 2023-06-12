<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        Storage::fake('local');
        $this->withHeaders([
            'accept' => 'application/json'
        ]);
    }

    /** @test */
    public function a_post_can_be_stored(): void
    {
        $this->withoutExceptionHandling();
        $file = File::create('myimage.jpg');
        $data = [
            'title' => 'Post Title',
            'description' => 'Post Description',
            'image' => $file
        ];
        $response = $this->post('/api/posts', $data);
        $this->assertDatabaseCount('posts', 1);
        $post = Post::first();
        $this->assertEquals($data['title'], $post->title);
        $this->assertEquals($data['description'], $post->description);
        $this->assertEquals('images/' . $file->hashName(), $post->image);
        Storage::disk('local')->assertExists($post->image);
        $response->assertJson(value: [
            'id' => $post->id,
            'title' => $post->title,
            'description' => $post->description,
            'image' => $post->image,
        ]);
    }


    /** @test */
    public function attribute_title_required_validation()
    {
        $data = [
            'title' => '',
            'description' => 'Post Description',
            'image' => ''
        ];
        $response = $this->post('/api/posts', $data);

        //dd($response->getContent());
        $response->assertStatus(422);
        $response->assertInvalid('title');
    }

    /** @test */
    public function attribute_image_file_validation()
    {
        $file = File::create('myimage.jpg');
        $data = [
            'title' => 'Title',
            'description' => 'Post Description',
            'image' => 'invalidparam' // $file
        ];
        $response = $this->post('/posts', $data);

        $response->assertStatus(422);
        $response->assertInvalid('image');
    }


    /** @test */
    public function post_can_be_updated()
    {
        $file = File::create('myimage.jpg');
        $this->withoutExceptionHandling();
        $post = Post::factory()->create();
        $data = [
            'title' => 'Title Updated',
            'description' => 'Post Updated',
            'image' => $file
        ];
        $response = $this->patch('/api/posts/' . $post->id, $data);
        $response->assertJson(value: [
            'id' => $post->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'image' => 'images/' . $file->hashName(),
        ]);
    }

    /** @test */
    public function get_all_posts()
    {
        $this->withoutExceptionHandling();
        $posts = Post::factory(10)->create();
        $response = $this->get('/api/posts');
        $response->assertOk();
        $json = $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'description' => $post->description,
                'image' => $post->image,
            ];
        })->toArray();
        $response->assertJson($json);
    }

    /** @test */
    public function get_single_post()
    {
        $this->withoutExceptionHandling();
        $post = Post::factory()->create();
        $response = $this->get('/api/posts/' . $post->id);
        $response->assertJson(value: [
            'id' => $post->id,
            'title' => $post->title,
            'description' => $post->description,
            'image' => $post->image,
        ]);
    }

    /** @test */
    public function post_can_be_deleted_by_auth_user_only()
    {
        $this->withoutExceptionHandling();
        $user = \App\Models\User::factory()->create();
        $post = Post::factory()->create();
        $response = $this->actingAs($user)->delete('/api/posts/' . $post->id);
        $response->assertOk();
        $this->assertDatabaseCount('posts', 0);
        $response->assertJson(value: [
            'message' => 'deleted'
        ]);
    }

    /** @test */
    public function post_can_not_be_deleted_by_not_auth_user()
    {
        $post = Post::factory()->create();
        $response = $this->delete('/api/posts/' . $post->id);
        $response->assertUnauthorized();
        $this->assertDatabaseCount('posts', 1);

    }

}
