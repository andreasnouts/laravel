<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'tour_name',
        'participants',
        'price_per_person',
    ];


    protected $appends = [
        'total_price'
    ];



    /*******      C A L C U L A T E D    A T T R I B U T E S      ******/


    /**
     * Returns the Total Price of this Booking instance
     * @return float
     */
    public function getTotalPriceAttribute(): float
    {
        return $this->price_per_person * $this->participants;
    }




    /*******      R E L A T I O N S      ******/


    /**
     * Returns the Booking Model instance this BookingItem belongs to
     * @return BelongsTo
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

}
