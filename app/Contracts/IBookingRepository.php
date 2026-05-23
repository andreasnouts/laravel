<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Enumerations\BookingStatus;
use App\Models\Booking;
use App\Models\Guide;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IBookingRepository
{
    /*******      D A T A   R E T R I E V A L     M E T H O D S      ******/


    /**
     * Returns paginated booking Collection for the Guide matching the given $guideId
     * @param int $guideId
     * @param int $perPage Items per page
     * @param string $orderBy The column name
     * @param string $direction asc|desc
     * @return LengthAwarePaginator
     */
    public function getBookings(int $guideId, int $perPage = 10, string $orderBy = 'created_at', string $direction = 'desc'): LengthAwarePaginator;

    /**
     * Returns a freshly loaded Booking from the DB
     * @param Booking $booking
     * @return ?Booking
     */
    public function refreshBooking(Booking $booking): ?Booking;




    /*******      D A T A   C R E A T I O N     M E T H O D S      ******/


    /**
     * Creates a new booking and returns it. It is done in transactional fashion, since bookingItems must be created also!
     * @param Guide $guide
     * @param array $data {status:string, total:float (decimal 12,2 in DB), notes: string}
     * @param array $items {tour_name:string, participants:int, price_per_person:float (note: decimal 12,2 in DB)}
     * @return Booking
     */
    public function createBooking(Guide $guide, array $data, array $items): Booking;



    /*******      D A T A   U P D A T E     M E T H O D S      ******/

    /**
     * Updates the notes for the given Booking
     * WARNING: Notes are not appended, the whole notes must be given, otherwise old notes will be lost!
     * @param Booking $booking
     * @param string $notes The whole new notes text
     * @return Booking The updated Booking instance
     */
    public function updateNotes(Booking $booking, string $notes): Booking;

    /**
     * Sets the status of the given Booking
     * @param Booking $booking The Booking to be updated
     * @param BookingStatus $bookingStatus The new Booking Status
     * @return bool
     */
    public function updateStatus(Booking $booking, BookingStatus $bookingStatus): bool;
}
