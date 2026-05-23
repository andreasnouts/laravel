<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingNotEditableException;
use App\Http\Requests\CreateBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Guide;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private readonly BookingService $bookingService;


    public function __construct(BookingService $service)
    {
        $this->bookingService = $service;
    }

    /**
     * @param Guide $guide
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(Guide $guide, Request $request): JsonResponse
    {
        // could also make this request something like listBookingRequest and make the per_page required...
        // as well as having default order by and directions in a settings table or config file
        $page_size = $request->per_page ?? 10;
        $order_by = $request->order_by ?? 'created_at';
        $order = $request->order_direction ?? 'desc';

        $bookings = $this->bookingService->getBookingsForGuide($guide->hash_id, $page_size, $order_by, $order);
        return response()->json($bookings);
    }


    /**
     * @param CreateBookingRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        /*
         * I imagine request body like this:
         * {
            "guide_hash_id": "hash_id here",
            "notes": "notes here",
            "items": [
                        ['tour_name' => 'Acropolis', 'participants' => 3, 'price_per_person' => 100.00],
                        ['tour_name' => 'Mystra',    'participants' => 4, 'price_per_person' => 125.00],
                    ] // each booking
            }
         *
         * This is the point where you always have to work with frontend and agree on...
         * */

        $booking = $this->bookingService->createBooking(
            $request->safe()->except('items'),
            $request->validated('items')
        );

        return response()->json($booking, 201);
    }


    /**
     * @param Booking $booking
     * @param UpdateBookingRequest $request
     * @return JsonResponse
     */
    public function updateNotes(Booking $booking, UpdateBookingRequest $request): JsonResponse
    {
        try
        {
            $booking = $this->bookingService->updateNotes($booking, $request->validated('notes'));
            return response()->json($booking, 200);
        }
        catch (BookingNotEditableException $ex)
        {
            // Log Error, not in audit log though! Need generic logger
            return response()->json([
                'message' => 'Only Bookings in DRAFT state can have their notes updated!'
            ], $ex->getCode());
        }
        catch (\Throwable $t)
        {
            return response()->json([
                'message' => $t->getMessage()
            ], 500); // we can define custom errors rather than throwing this scary 500!
        }
    }
}
