<?php

namespace App\Services;

use App\Contracts\IAuditLogger;
use App\Contracts\IBookingRepository;
use App\Contracts\IGuideRepository;
use App\Enumerations\BookingStatus;
use App\Exceptions\BookingNotEditableException;
use App\Exceptions\GuideNotFoundException;
use App\Jobs\ProcessBookingApproval;
use App\Models\Booking;
use App\Models\Guide;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookingService
{
    private readonly IBookingRepository $bookingRepo;
    private readonly IGuideRepository $guideRepo;
    private readonly IAuditLogger $logger;

    // could be read from DB as Global Booking System Settings which are cached in redis or in any cache medium
    // or even from a config file, for simplicity added here as a constant
    private const APPROVAL_AMOUNT_THRESHOLD = 5000;


    public function __construct(IBookingRepository $bookingRepository, IGuideRepository $guideRepository, IAuditLogger $auditLogger)
    {
        $this->bookingRepo = $bookingRepository;
        $this->guideRepo = $guideRepository;
        $this->logger = $auditLogger;
    }


    /**
     * Returns a paginated list of Bookings for the given Guide ($guideId)
     * @param string $guideHashId The hash_id of the Guide for which Bookings will be fetched
     * @param int $perPage Items per page
     * @param string $orderBy
     * @param string $direction
     * @return LengthAwarePaginator
     * @throws \Exception Thrown if Guide could not be resolved by the given $guideHashId!
     */
    public function getBookingsForGuide(string $guideHashId, int $perPage = 10, string $orderBy = 'created_at', string $direction = 'desc'): LengthAwarePaginator
    {
        // resolve Guide (find it by its hash_id since we've used hash_id on the UI
        // for hiding records ids and avoiding user guessing the id of a Guide!)
        $guide = $this->resolveGuide($guideHashId);

        // return the Bookings for this Guide, internally we can use the $guide->id for faster searching (int vs string)
        return $this->bookingRepo->getBookings($guide->id, $perPage, $orderBy, $direction);
    }


    /**
     * Updates the notes field for the given Booking.
     * WARNING: The whole notes text must be given otherwise old notes will be overwritten!
     * @param Booking $booking The actual Booking to be updated
     * @param string $notes The full notes text (should contain previous notes too if you don't want to lose them!)
     * @return Booking The updated Booking model instance
     * @throws \Exception Thrown if Booking is not in DRAFT status as only DRAFT Bookings can have their notes updated!
     */
    public function updateNotes(Booking $booking, string $notes): Booking
    {
        if (!$booking->is_draft)
        {
            $error_msg = 'Only DRAFT Bookings can have their notes updated!';
            // would be better off writing custom Exceptions layered as http, db, core, etc...
            // so we never write http codes (and other codes) explicitly every time we decide to throw an Exception
            //throw new \Exception($error_msg, 409, $booking);
            throw new BookingNotEditableException($booking);
        }

        // get the current booking before updating...
        $previous_booking = $booking->toArray();
        // perform actual update
        $updated_booking = $this->bookingRepo->updateNotes($booking, $notes);
        // log event
        $this->logger->log('updated', $updated_booking, $previous_booking, $updated_booking->toArray());

        return $updated_booking;
    }


    /**
     * Creates a new Booking
     * @param array $data Contains the Guide Data
     * @param array $items Contains Booking details (
     * @return Booking The newly created Booking
     * @throws \Exception
     */
    public function createBooking(array $data, array $items): Booking
    {
        // make sure Guide exists. This could be done in Controller Validation,
        // BUT adding 'exists' in the request validation Rules would break the Repository pattern as it
        // would directly access the DB assuming guide exists in my DB, what if it comes from another publisher
        // and is accessed by an API call? So I'd rather not break the Repository pattern!
        $guide = $this->guideRepo->getByHashId($data['guide_hash_id']);

        if (!$guide)
        {
            $error_msg = 'Guide not found!';
            throw new \Exception($error_msg . ' (Given hash_id: ' . $data['guide_hash_id'] . ')'); // again we could have custom exceptions
        }

        // Calculate the Total price
        // items look like this:
        /*
         * "items": [
                        ['tour_name' => 'Acropolis', 'participants' => 3, 'price_per_person' => 100.00],
                        ['tour_name' => 'Mystra',    'participants' => 4, 'price_per_person' => 125.00],
                    ] // each booking
            }
         * */
        $total_price = $this->calculateTotal($items);

        // set the total price for the Booking
        $data['total'] = $total_price;

        // set status of Booking based on the $total price
        $status = ($total_price > self::APPROVAL_AMOUNT_THRESHOLD)
            ? BookingStatus::PENDING_APPROVAL
            : BookingStatus::DRAFT;

        // set the calculated status for the Booking
        $data['status'] = $status;

        $booking = $this->bookingRepo->createBooking($guide, $data, $items);
        // log record-created event
        $this->logger->log('created', $booking, [], $booking->toArray());

        if ($status === BookingStatus::PENDING_APPROVAL)
        {
            // no need to explicitly pass logger and repo, but since I have them why not save some execution time :)
            dispatch(new ProcessBookingApproval($booking, $this->bookingRepo, $this->logger));
        }

        return $booking;
    }



    /*******      P R I V A T E      ******/


    /**
     *
     * @param string $hashId
     * @return Guide
     * @throws \Exception
     */
    private function resolveGuide(string $hashId): Guide
    {
        $guide = $this->guideRepo->getByHashId($hashId);

        if (! $guide)
        {
//            $error_msg = 'Guide not found!';
//            throw new \Exception($error_msg . ' (Given hash_id: ' . $hashId . ')'); // again we could have custom exceptions
            throw new GuideNotFoundException($hashId);
        }

        return $guide;
    }


    /**
     * Calculates the total price and returns it
     * @param array $items {participants:int, price_per_person: float}
     * @return float The total price
     */
    private function calculateTotal(array $items): float
    {
        // COMMENTS on collect($items)->sum(fn() => ...)
        // I do not always prefer these -otherwise elegant constructs- as sometimes they might add obscurity...
        // I am much more in favour of a simpler readable approaches as at the end of the day, the same loops are executed
        // without the overhead of the interpreter
        // creating a collection, fn() creates a closure object, dispatches a method, callback invocation per iteration.
        // here this are simple, so I'd probably do this elegant coding style, but in something like that:
        // collect($items)->map(...)->filter(...)->sum() ...
        // A simple foreach is usually faster.
        // This is where you have to judge based on pros/cons (i.e. huge data, heavy calculations, etc...)
        return collect($items)->sum(
            fn($item) => $item['participants'] * $item['price_per_person']
        );
    }
}
