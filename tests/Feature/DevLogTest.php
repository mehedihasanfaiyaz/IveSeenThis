<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
