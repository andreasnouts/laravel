<?php

namespace App\Listeners;

use App\Contracts\IAuditLogger;
use App\Events\BookingRequiresManualApproval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleBookingRequiresManualApproval
{
    private readonly IAuditLogger $logger;

    /**
     * Create the event listener.
     */
    public function __construct(IAuditLogger $auditLogger)
    {
        $this->logger = $auditLogger;
    }

    /**
     * Handle the event.
     */
    public function handle(BookingRequiresManualApproval $event): void
    {
        $this->logEvent($event);
        // here could possibly add a message to another system that an Admin watches
        // or even make an API call to Add to it...
        // inform through slack, etc...
    }


    private function logEvent(BookingRequiresManualApproval $event): void
    {
        $this->logger->log(
            'manual_approval_required',
            $event->booking,
            [],
            [
                'booking_hash_id' => $event->booking->hash_id,
                'guide_hash_id'   => $event->booking->guide->hash_id,
                'total'           => $event->booking->total,
                'status'          => $event->booking->status->value,
            ]
        );
    }
}
