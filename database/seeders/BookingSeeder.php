<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enumerations\BookingStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Traits\HasHashID;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    use HasHashID;

    public function run(): void
    {
        DB::transaction(function () {
            $this->createBooking(
                guideId: 1,
                notes: 'Morning tour, meet at Syntagma Square.',
                items: [
                    ['tour_name' => 'Acropolis Tour',     'participants' => 3, 'price_per_person' => 120.00],
                    ['tour_name' => 'Parthenon Deep Dive', 'participants' => 3, 'price_per_person' => 80.00],
                ]
            ); // total: 600 — stays draft

            sleep(1);

            $this->createBooking(
                guideId: 1,
                notes: 'VIP group, requires special arrangements.',
                items: [
                    ['tour_name' => 'Private Athens Tour', 'participants' => 10, 'price_per_person' => 350.00],
                    ['tour_name' => 'Delphi Day Trip',     'participants' => 10, 'price_per_person' => 200.00],
                ]
            ); // total: 5500 — triggers pending_approval

            sleep(1);

            $this->createBooking(
                guideId: 2,
                notes: 'Small group, flexible schedule.',
                items: [
                    ['tour_name' => 'Cape Sounion Sunset', 'participants' => 4, 'price_per_person' => 95.00],
                ]
            ); // total: 380 — stays draft

            sleep(1);

            $this->createBooking(
                guideId: 2,
                notes: 'Corporate group, invoice required.',
                items: [
                    ['tour_name' => 'Full Day Athens',     'participants' => 20, 'price_per_person' => 280.00],
                    ['tour_name' => 'Museum Private Tour', 'participants' => 20, 'price_per_person' => 150.00],
                    ['tour_name' => 'Dinner Experience',   'participants' => 20, 'price_per_person' => 120.00],
                ]
            ); // total: 11000 — triggers pending_approval + would fire event
        });
    }


    /*******      P R I V A T E      ******/


    private function createBooking(int $guideId, string $notes, array $items): Booking
    {
        $total = collect($items)->sum(
            fn($item) => $item['participants'] * $item['price_per_person']
        );

        $status = $total > 5000
            ? BookingStatus::PENDING_APPROVAL
            : BookingStatus::DRAFT;

        $booking = Booking::create([
            'hash_id'  => $this->generateHashID('unique:bookings', Carbon::now()->toDateTimeString(), 9),
            'guide_id' => $guideId,
            'status'   => $status,
            'total'    => $total,
            'notes'    => $notes,
        ]);

        $bookingItems = collect($items)->map(fn($item) => new BookingItem([
            'tour_name'        => $item['tour_name'],
            'participants'     => $item['participants'],
            'price_per_person' => $item['price_per_person'],
        ]))->all();

        $booking->bookingItems()->saveMany($bookingItems);

        return $booking->load('bookingItems');
    }
}
