<?php

namespace App\Services\Cms;

use App\Models\ExerciseDocument;
use App\Models\ExerciseGenerationLog;
use App\Models\Formula;
use App\Models\MathLevel;
use App\Models\MathLevelQuestion;
use App\Models\MathPath;
use App\Models\User;

class GlobalSearchService
{
    public function search(User $administrator, string $search, string $locale, int $limit): array
    {
        $groups = [];
        if ($administrator->hasCmsPermission('users.view')) {
            $groups['users'] = User::query()
                ->where(fn ($query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                ->limit($limit)
                ->get(['id', 'name', 'email', 'role'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'title' => $user->name,
                    'subtitle' => "{$user->email} · {$user->role}",
                    'section' => 'students',
                ]);
        }
        if ($administrator->hasCmsPermission('paths.view')) {
            $groups['paths'] = MathPath::query()
                ->with(['translations' => fn ($query) => $query->where('locale', $locale)])
                ->where(fn ($query) => $query->where('slug', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn ($translations) => $translations
                        ->where('title', 'like', "%{$search}%")))
                ->limit($limit)->get()
                ->map(fn (MathPath $path) => [
                    'id' => $path->id,
                    'title' => $path->translations->first()?->title ?? $path->slug,
                    'subtitle' => $path->slug,
                    'section' => 'math-paths',
                ]);
        }
        if ($administrator->hasCmsPermission('levels.view')) {
            $groups['levels'] = MathLevel::query()
                ->with(['translations' => fn ($query) => $query->where('locale', $locale)])
                ->where(fn ($query) => $query->where('slug', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn ($translations) => $translations
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('topic', 'like', "%{$search}%")))
                ->limit($limit)->get()
                ->map(fn (MathLevel $level) => [
                    'id' => $level->id,
                    'title' => $level->translations->first()?->title ?? $level->slug,
                    'subtitle' => $level->difficulty,
                    'section' => 'levels',
                ]);
        }
        if ($administrator->hasCmsPermission('questions.view')) {
            $groups['questions'] = MathLevelQuestion::query()
                ->where(fn ($query) => $query->where('question', 'like', "%{$search}%")
                    ->orWhere('topic', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn ($translations) => $translations
                        ->where('question', 'like', "%{$search}%")))
                ->limit($limit)->get(['id', 'question', 'type', 'topic'])
                ->map(fn (MathLevelQuestion $question) => [
                    'id' => $question->id,
                    'title' => str($question->question)->limit(80)->toString(),
                    'subtitle' => $question->topic ?: $question->type,
                    'section' => 'questions',
                ]);
        }
        if ($administrator->hasCmsPermission('formulas.view')) {
            $groups['formulas'] = Formula::query()
                ->with(['translations' => fn ($query) => $query->where('locale', $locale)])
                ->where(fn ($query) => $query->where('formula', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn ($translations) => $translations
                        ->where('name', 'like', "%{$search}%")))
                ->limit($limit)->get()
                ->map(fn (Formula $formula) => [
                    'id' => $formula->id,
                    'title' => $formula->translations->first()?->name ?? $formula->category,
                    'subtitle' => str($formula->formula)->limit(80)->toString(),
                    'section' => 'formulas',
                ]);
        }
        if ($administrator->hasCmsPermission('exercise_documents.view')) {
            $groups['documents'] = ExerciseDocument::query()
                ->with('user:id,name')
                ->where('title', 'like', "%{$search}%")
                ->limit($limit)->get()
                ->map(fn (ExerciseDocument $document) => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'subtitle' => ($document->user?->name ?? 'Unknown').' · '.$document->publication_status,
                    'section' => 'documents',
                ]);
        }
        if ($administrator->hasCmsPermission('ai.view')) {
            $groups['generation_logs'] = ExerciseGenerationLog::query()
                ->with('user:id,name')
                ->where(function ($query) use ($search) {
                    if (ctype_digit($search)) {
                        $query->where('id', (int) $search);
                    }
                    $method = ctype_digit($search) ? 'orWhere' : 'where';
                    $query->{$method}('error', 'like', "%{$search}%")
                        ->orWhere('feature', 'like', "%{$search}%");
                })
                ->limit($limit)->get()
                ->map(fn (ExerciseGenerationLog $log) => [
                    'id' => $log->id,
                    'title' => "Generation #{$log->id}",
                    'subtitle' => ($log->user?->name ?? 'Unknown').' · '.$log->status,
                    'section' => 'generation-logs',
                ]);
        }

        return collect($groups)->map(fn ($items) => $items->values())->all();
    }
}
