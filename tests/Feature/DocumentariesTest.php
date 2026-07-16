<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentariesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\MsunliHierarchySeeder::class);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->admin = User::factory()->create([
            'name' => 'ICT Admin',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        $this->admin->assignRole('ict_administrator');
    }

    /** @test */
    public function admin_can_store_documentary()
    {
        Storage::fake('public');

        $thumbnail = UploadedFile::fake()->image('thumb.png');
        $video = UploadedFile::fake()->create('video.mp4', 500);

        $response = $this->actingAs($this->admin)->post(route('admin.settings.documentaries.store'), [
            'title' => 'Test Documentary',
            'description' => 'This is a test description.',
            'duration' => '10:00',
            'thumbnail' => $thumbnail,
            'video' => $video,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $docs = SystemSetting::get('short_courses_documentaries', []);
        $this->assertCount(1, $docs);
        $this->assertEquals('Test Documentary', $docs[0]['title']);
        $this->assertEquals('10:00', $docs[0]['duration']);
        $this->assertNotNull($docs[0]['thumbnail_path']);
        $this->assertNotNull($docs[0]['video_path']);

        // Verify file stored in fake storage
        $thumbPath = str_replace('/storage/', '', $docs[0]['thumbnail_path']);
        $videoPath = str_replace('/storage/', '', $docs[0]['video_path']);
        Storage::disk('public')->assertExists($thumbPath);
        Storage::disk('public')->assertExists($videoPath);
    }

    /** @test */
    public function admin_can_store_documentary_with_mov_format()
    {
        Storage::fake('public');

        $thumbnail = UploadedFile::fake()->image('thumb.png');
        $video = UploadedFile::fake()->create('video.mov', 500, 'video/quicktime');

        $response = $this->actingAs($this->admin)->post(route('admin.settings.documentaries.store'), [
            'title' => 'QuickTime Test',
            'description' => 'This is a QuickTime test.',
            'duration' => '05:40',
            'thumbnail' => $thumbnail,
            'video' => $video,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $docs = SystemSetting::get('short_courses_documentaries', []);
        $this->assertCount(1, $docs);
        $this->assertEquals('QuickTime Test', $docs[0]['title']);

        $videoPath = str_replace('/storage/', '', $docs[0]['video_path']);
        Storage::disk('public')->assertExists($videoPath);
        $this->assertStringEndsWith('.mov', $videoPath);
    }

    /** @test */
    public function admin_can_update_documentary()
    {
        Storage::fake('public');

        // Setup a documentary in the settings first
        $docId = uniqid();
        $docs = [
            [
                'id' => $docId,
                'title' => 'Old Title',
                'description' => 'Old Description',
                'duration' => '05:00',
                'thumbnail_path' => '/storage/documentaries/old_thumb.png',
                'video_path' => '/storage/documentaries/old_video.mp4',
                'is_published' => true,
                'created_at' => now()->toDateTimeString(),
            ]
        ];
        SystemSetting::set('short_courses_documentaries', $docs);

        // Upload new thumbnail
        $newThumbnail = UploadedFile::fake()->image('new_thumb.png');

        $response = $this->actingAs($this->admin)->post(route('admin.settings.documentaries.update', $docId), [
            'title' => 'New Title',
            'description' => 'New Description',
            'duration' => '06:00',
            'thumbnail' => $newThumbnail,
            'is_published' => false,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $docs = SystemSetting::get('short_courses_documentaries', []);
        $this->assertCount(1, $docs);
        $this->assertEquals('New Title', $docs[0]['title']);
        $this->assertEquals('New Description', $docs[0]['description']);
        $this->assertEquals('06:00', $docs[0]['duration']);
        $this->assertFalse($docs[0]['is_published']);

        $newThumbPath = str_replace('/storage/', '', $docs[0]['thumbnail_path']);
        Storage::disk('public')->assertExists($newThumbPath);
    }

    /** @test */
    public function admin_can_delete_documentary()
    {
        Storage::fake('public');

        // Setup documentary
        $docId = uniqid();
        $docs = [
            [
                'id' => $docId,
                'title' => 'Doc to Delete',
                'description' => 'A test description',
                'duration' => '05:00',
                'thumbnail_path' => '/storage/documentaries/thumb.png',
                'video_path' => '/storage/documentaries/video.mp4',
                'is_published' => true,
                'created_at' => now()->toDateTimeString(),
            ]
        ];
        SystemSetting::set('short_courses_documentaries', $docs);

        $response = $this->actingAs($this->admin)->delete(route('admin.settings.documentaries.destroy', $docId));

        $response->assertStatus(302);

        $docs = SystemSetting::get('short_courses_documentaries', []);
        $this->assertCount(0, $docs);
    }

    /** @test */
    public function public_user_can_view_documentaries()
    {
        // Clear setting first to trigger seeding
        SystemSetting::set('short_courses_documentaries', []);

        $response = $this->get('/');
        $response->assertStatus(200);

        // Seeding on demand should have populated default documentaries
        $docs = SystemSetting::get('short_courses_documentaries', []);
        $this->assertNotEmpty($docs);
        $this->assertCount(4, $docs);
    }

    /** @test */
    public function admin_can_store_documentary_without_duration()
    {
        Storage::fake('public');

        $thumbnail = UploadedFile::fake()->image('thumb.png');
        $video = UploadedFile::fake()->create('video.mp4', 500);

        $response = $this->actingAs($this->admin)->post(route('admin.settings.documentaries.store'), [
            'title' => 'No Duration Test',
            'description' => 'Test description.',
            'thumbnail' => $thumbnail,
            'video' => $video,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $docs = SystemSetting::get('short_courses_documentaries', []);
        $found = collect($docs)->firstWhere('title', 'No Duration Test');
        $this->assertNotNull($found);
        $this->assertNull($found['duration']);
    }
}
