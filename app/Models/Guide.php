<?php

namespace App\Models;

use App\Enumerations\GuideStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guide extends Model
{
    use HasFactory;

    protected $fillable = [
        'hash_id',
        'name',
        'email',
        'status'
    ];

    protected $casts = [
        'status' => GuideStatus::class,
    ];



    /**
     * Work with hash_id as model key...
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'hash_id';
    }



    /*******      C A L C U L A T E D    A T T R I B U T E S      ******/


    /**
     * Indicates current Guide instance isActive
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === GuideStatus::ACTIVE;
    }

    /**
     * Indicates current Guide instance is Suspended
     * @return bool
     */
    public function getIsSuspendedAttribute(): bool
    {
        return $this->status === GuideStatus::SUSPENDED;
    }




    /*******      R E L A T I O N S      ******/


    /**
     * Returns a collection of Bookings made for this Guide
     * @return HasMany
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }




}
