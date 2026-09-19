<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DevLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_the_devlog(): void
    {
        $this->get(route('issues.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_an_issue_with_attempts_and_solution(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::issues.create')
            ->set('title', 'Laravel Docker MySQL connection refused')
            ->set('occurred_on', '2026-09-19')
            ->set('environment', 'Laravel 13 / Docker / MySQL 8.4')
            ->set('problem', 'The app could not connect to MySQL.')
            ->set('error_logs', 'SQLSTATE[HY000] [1045] Access denied')
            ->set('root_cause', 'The password was incorrect.')
            ->set('dont_do_again', 'Do not use localhost inside the app container.')
            ->set('tags', 'laravel, docker, mysql')
            ->set('attempts.0.description', 'Changed DB_HOST to localhost')
            ->set('attempts.0.result', 'failed')
            ->set('solution_title', 'Use the service hostname')
            ->set('solution', 'Set DB_HOST to the Docker service name and correct the password.')
            ->call('save')
            ->assertRedirect();

        $issue = Issue::firstOrFail();
        $this->assertSame('Laravel Docker MySQL connection refused', $issue->title);
        $this->assertSame(['laravel', 'docker', 'mysql'], $issue->tags);
        $this->assertDatabaseHas('issue_attempts', ['issue_id' => $issue->id, 'result' => 'failed']);
        $this->assertDatabaseHas('solutions', ['issue_id' => $issue->id, 'title' => 'Use the service hostname']);
    }

    public function test_user_cannot_view_another_users_issue(): void
    {
        $owner = User::factory()->create();
        $issue = Issue::factory()->create(['user_id' => $owner->id]);
        $this->actingAs(User::factory()->create());

        $this->get(route('issues.show', $issue))->assertNotFound();
    }

    public function test_user_can_edit_an_issue(): void
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create(['user_id' => $user->id, 'title' => 'Original title']);
        $this->actingAs($user);

        Livewire::test('pages::issues.edit', ['issue' => $issue])
            ->set('title', 'Updated title')
            ->set('problem', 'The updated problem description.')
            ->set('solution_title', 'The working fix')
            ->set('solution', 'This is the updated solution.')
            ->call('save')
            ->assertRedirect(route('issues.show', $issue));

        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'title' => 'Updated title']);
        $this->assertDatabaseHas('solutions', ['issue_id' => $issue->id, 'title' => 'The working fix']);
    }

    public function test_user_can_delete_an_issue_from_the_detail_page(): void
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test('pages::issues.show', ['issue' => $issue])
            ->call('deleteIssue')
            ->assertRedirect(route('issues.index'));

        $this->assertDatabaseMissing('issues', ['id' => $issue->id]);
    }

    public function test_user_can_add_an_attempt_from_an_open_issue(): void
    {
        $user = User::factory()->create();
        $issue = Issue::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        Livewire::test('pages::issues.show', ['issue' => $issue])
            ->set('newAttemptDescription', 'Changed DB_HOST to the service hostname.')
            ->set('newAttemptResult', 'worked')
            ->call('addAttempt');

        $this->assertDatabaseHas('issue_attempts', [
            'issue_id' => $issue->id,
            'position' => 1,
            'description' => 'Changed DB_HOST to the service hostname.',
            'result' => 'worked',
        ]);
    }

    public function test_user_can_upload_images_for_an_issue_attempt_and_solution(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::issues.create')
            ->set('title', 'Image-backed issue')
            ->set('problem', 'The screenshot explains the problem.')
            ->set('problemImage', UploadedFile::fake()->image('problem.png'))
            ->set('attempts.0.description', 'Tried the first fix.')
            ->set('attempts.0.image', UploadedFile::fake()->image('attempt.png'))
            ->set('solution_title', 'Use the correct setting')
            ->set('solution', 'The screenshot shows the working setting.')
            ->set('solutionImage', UploadedFile::fake()->image('solution.png'))
            ->call('save');

        $issue = Issue::firstOrFail();
        $this->assertDatabaseCount('attachments', 3);
        $this->assertSame(1, $issue->attachments()->count());
        $this->assertSame(1, $issue->attempts()->first()->attachments()->count());
        $this->assertSame(1, $issue->solutions()->first()->attachments()->count());
        Storage::disk('public')->assertExists($issue->attachments()->first()->path);
    }

    public function test_edit_page_shows_existing_problem_image_preview(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $issue = Issue::factory()->create(['user_id' => $user->id]);
        Storage::disk('public')->put('issues/'.$issue->id.'/problem.png', 'image');
        $issue->attachments()->create([
            'disk' => 'public',
            'path' => 'issues/'.$issue->id.'/problem.png',
            'original_name' => 'problem.png',
            'mime_type' => 'image/png',
            'size' => 5,
        ]);
        $this->actingAs($user);

        Livewire::test('pages::issues.edit', ['issue' => $issue])
            ->assertSee('problem.png');
    }
}
