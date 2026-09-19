<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@iveseenthis.test'],
            [
                'name' => 'Demo Developer',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $project = $user->projects()->updateOrCreate(
            ['slug' => Project::makeSlug('IveSeenThis')],
            ['name' => 'IveSeenThis', 'description' => 'The engineering knowledge base.'],
        );

        $user->issues()->update(['project_id' => $project->id]);
        $user->projects()->whereKeyNot($project->id)->delete();

        $this->seedIssue($user, $project, [
            'title' => 'Laravel Docker MySQL connection refused',
            'occurred_on' => '2026-09-18',
            'environment' => 'Laravel 13 / Docker / MySQL 8.4',
            'problem' => 'The Laravel container could not connect to MySQL during startup.',
            'error_logs' => 'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo for localhost failed',
            'root_cause' => 'The app was using localhost instead of the Docker Compose service name.',
            'dont_do_again' => 'Do not use localhost to connect to another container.',
            'tags' => ['laravel', 'docker', 'mysql'],
            'attempts' => [
                ['description' => 'Changed DB_HOST to localhost.', 'result' => 'failed'],
                ['description' => 'Changed DB_HOST to db, the Compose service name.', 'result' => 'worked'],
            ],
            'solution' => ['title' => 'Use the database service hostname', 'description' => 'Set DB_HOST=db inside the app container and let Compose provide the network connection.'],
        ]);

        $this->seedIssue($user, $project, [
            'title' => 'Uploaded files returned 404 in production',
            'occurred_on' => '2026-09-12',
            'environment' => 'Laravel 13 / Apache / Docker',
            'problem' => 'Uploaded images existed on disk but their public URLs returned 404.',
            'error_logs' => 'GET /storage/uploads/avatar.jpg 404 Not Found',
            'root_cause' => 'The public storage symlink had not been created in the deployment container.',
            'dont_do_again' => 'Always run storage:link as part of container startup or deployment.',
            'tags' => ['laravel', 'storage', 'docker'],
            'attempts' => [
                ['description' => 'Changed the filesystem disk to public.', 'result' => 'failed'],
                ['description' => 'Ran php artisan storage:link.', 'result' => 'worked'],
            ],
            'solution' => ['title' => 'Create the storage link during deployment', 'description' => 'Run php artisan storage:link after the container starts and before serving requests.'],
        ]);

        $this->seedIssue($user, $project, [
            'title' => 'Livewire page reported multiple root elements',
            'occurred_on' => '2026-09-10',
            'environment' => 'Laravel 13 / Livewire 4 / Flux',
            'problem' => 'The issue creation page failed when Livewire tried to render the component.',
            'error_logs' => 'Livewire only supports one HTML element per component.',
            'root_cause' => 'The full-page component used a layout component as its root instead of one section element.',
            'dont_do_again' => 'Keep full-page Livewire views wrapped in one root element.',
            'tags' => ['livewire', 'laravel', 'blade'],
            'attempts' => [
                ['description' => 'Nested another layout component around the page.', 'result' => 'failed'],
                ['description' => 'Matched the starter kit pattern with one section root.', 'result' => 'worked'],
            ],
            'solution' => ['title' => 'Use a single page root', 'description' => 'Render the full-page Livewire view inside one section element and let the route provide the app layout.'],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function seedIssue(User $user, Project $project, array $data): void
    {
        $issue = $user->issues()->updateOrCreate(
            ['title' => $data['title']],
            [
                'project_id' => $project->id,
                'occurred_on' => $data['occurred_on'],
                'environment' => $data['environment'],
                'problem' => $data['problem'],
                'error_logs' => $data['error_logs'],
                'root_cause' => $data['root_cause'],
                'dont_do_again' => $data['dont_do_again'],
                'tags' => $data['tags'],
            ],
        );

        $issue->attempts()->delete();
        foreach ($data['attempts'] as $position => $attempt) {
            $issue->attempts()->create([...$attempt, 'position' => $position + 1]);
        }

        $issue->solutions()->updateOrCreate(
            ['title' => $data['solution']['title']],
            ['user_id' => $user->id, 'description' => $data['solution']['description']],
        );
    }
}
