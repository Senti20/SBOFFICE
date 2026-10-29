<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_id', 'session_name', 'type', 'session_id',
        'session_date', 'sponsors', 'tags', 'file_path', 'filename',
        'status', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'is_public' => 'boolean',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(Request::class, 'document_id', 'document_id');
    }

    public function getTagsArrayAttribute(): array
    {
        return collect(preg_split('/[,;]+/', (string) $this->tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
    }
    
    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id'); // Adjust the foreign key if your schema differs
    }
}