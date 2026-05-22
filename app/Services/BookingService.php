<?php

namespace App\Services;

use App\Contracts\IBookingRepository;

class BookingService
{
    private readonly IBookingRepository $bookingRepo;

    public function __construct(IBookingRepository $bookingRepository)
    {
        $this->bookingRepo = $bookingRepository;
    }


}
