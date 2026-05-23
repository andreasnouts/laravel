<?php

namespace App\Exceptions;
use App\Models\Booking;
use RuntimeException;

class BookingNotEditableException extends RuntimeException
{
    public function __construct(Booking $booking)
    {
        parent::__construct(
            "Booking [{$booking->hash_id}] cannot be edited because its status is [{$booking->status->value}]."
        );
    }
}
