<?php

namespace App\Models;

use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['project_id', 'title', 'occurred_on', 'environment', 'problem', 'error_logs', 'root_cause', 'dont_do_again', 'tags'])]
class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Issue $issue): void {
            $issue->relatedIssues()->detach();
        });
    }

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'tags' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<IssueAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(IssueAttempt::class)->orderBy('position');
    }

    /** @return HasMany<Solution, $this> */
    public function solutions(): HasMany
    {
        return $this->hasMany(Solution::class);
    }

    /** @return MorphMany<Attachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /** @return BelongsToMany<Issue, $this, Pivot, 'pivot'> */
    public function relatedIssues(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'issue_relations', 'issue_id', 'related_issue_id');
    }
}
