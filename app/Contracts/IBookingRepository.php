<?php

namespace App\Contracts;

use App\Models\Booking;
use Illuminate\Pagination\LengthAwarePaginator;

interface IBookingRepository
{
    /**
     * Returns paginated booking Collection for the Guide matching the given $guideId
     * @param int $guideId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBookings(int $guideId, int $perPage): LengthAwarePaginator;

    /**
     * Creates a new booking and returns it
     * @param array $data {name, email, guide_id, status, total, notes}
     * @return Booking
     */
    public function createBooking(array $data): Booking;


    /**
     * Updates the notes for the Booking identified by the given $bookingId
     * WARNING: Notes are not appended, the whole notes must be given, otherwise old notes will be lost!
     * @param int $bookingId
     * @param string $notes The whole new notes text
     * @return Booking
     */
    public function updateNotes(int $bookingId, string $notes): Booking;
}
