<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'requester_name',
        'contact_number',
        'email',
        'document_id',
        'copies',
        'fee',
        'reason',
        'pickup_date',
        'valid_id_path',
        'selfie_path',
        'status',
        'access_token_hash',
    ];

    protected $hidden = [
        'access_token_hash',
        'valid_id_path',
        'selfie_path',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'copies' => 'integer',
    ];

    public function document()
    {
        return $this->belongsTo(
            Document::class,
            'document_id',
            'document_id'
        );
    }
}