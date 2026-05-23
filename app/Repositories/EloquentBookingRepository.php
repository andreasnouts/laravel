<?php

namespace App\Repositories;

use App\Contracts\IBookingRepository;
use App\Enumerations\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Guide;
use App\Traits\HasHashID;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentBookingRepository implements IBookingRepository
{
    use HasHashID;


    /**
     * @inheritDoc
     */
    public function refreshBooking(Booking $booking): ?Booking
    {
        return $booking->refresh();
    }


    /**
     * @inheritDoc
     */
    public function getBookings(int $guideId, int $perPage = 10, string $orderBy = 'created_at', string $direction = 'desc'): LengthAwarePaginator
    {
        return Booking::query()
            ->where('guide_id', '=', $guideId)
            ->orderBy($orderBy, $direction)
            ->paginate($perPage);
    }


    /**
     * @inheritDoc
     */
    public function createBooking(Guide $guide, array $data, array $items): Booking
    {
        return DB::transaction(function () use ($guide, $data, $items)
        {
            $booking = $guide->bookings()->create([
                'hash_id' => $this->generateHashID(
                    'unique:bookings',
                    Carbon::now()->toDateTimeString(),
                    9
                ),
                'status'  => $data['status'],
                'total'   => $data['total'],
                'notes'   => $data['notes'] ?? null,
            ]);

            $bookingItems = collect($items)->map(fn($item) => new BookingItem([
                'tour_name'        => $item['tour_name'],
                'participants'     => $item['participants'],
                'price_per_person' => $item['price_per_person'],
            ]))->all();

            // or foreach ... and ... BookingItem::insert()
            $booking->bookingItems()->saveMany($bookingItems);

            // eager load items...
            return $booking->load('bookingItems');
        });
    }


    /**
     * @inheritDoc
     */
    public function updateNotes(Booking $booking, string $notes): Booking
    {
        $booking->update(['notes' => $notes]);
        // re-fetch from DB...
        return $booking->fresh();
    }


    /**
     * @inheritDoc
     */
    public function updateStatus(Booking $booking, BookingStatus $bookingStatus): bool
    {
        return $booking->update([
            'status' => $bookingStatus,
        ]);
    }
}
