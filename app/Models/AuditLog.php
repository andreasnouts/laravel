<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'auditable',
        'user_id',
        'previous_payload',
        'new_payload',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'previous_payload' => 'array',
        'new_payload' => 'array',
    ];



    /*******      R E L A T I O N S      ******/

    /**
     * Returns the User this AuditLog belongs to or null if it's a System Log
     * @return BelongsTo|null
     */
    public function User(): BelongsTo|null
    {
        return $this->belongsTo(User::class);
    }

}
