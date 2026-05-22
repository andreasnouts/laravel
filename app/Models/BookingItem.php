<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    use HasFactory;



    /**
     * Returns the Total Price of this Booking instance
     * @return float
     */
    public function getTotalPriceAttribute(): float
    {
        return $this->price_per_person * $this->participants;
    }
}
