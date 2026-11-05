<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Faq extends Model
{
    protected $fillable = [
        'question_id',
        'question_en',
        'answer_id',
        'answer_en',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $clearCache = static function (): void {
            Cache::forget('faqs_help_id');
            Cache::forget('faqs_help_en');
        };

        static::created($clearCache);
        static::updated($clearCache);
        static::deleted($clearCache);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getQuestionAttribute(): string
    {
        $locale = app()->getLocale();

        return (string) ($this->attributes['question_'.$locale] ?? $this->attributes['question_id']);
    }

    public function getAnswerAttribute(): string
    {
        $locale = app()->getLocale();

        return (string) ($this->attributes['answer_'.$locale] ?? $this->attributes['answer_id']);
    }
}
