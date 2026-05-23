<?php

namespace App\Jobs;

use App\Contracts\IAuditLogger;
use App\Contracts\IBookingRepository;
use App\Enumerations\BookingStatus;
use App\Events\BookingRequiresManualApproval;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessBookingApproval implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const AUTO_APPROVE_AMOUNT_THRESHOLD = 10000;

    public int $tries = 3; // common retry attempts for Laravel Jobs, not always good!
    public int $backoff = 90; // seconds between retries

    private readonly Booking $booking;
    private readonly IBookingRepository $bookingRepo;
    private readonly IAuditLogger $logger;


    /**
     * Create a new job instance.
     */
    public function __construct(Booking $booking, IBookingRepository $bookingRepository, IAuditLogger $auditLogger)
    {
        $this->booking = $booking;
        $this->bookingRepo = $bookingRepository;
        $this->logger = $auditLogger;
    }


    /*******      U N I Q U E N E S S      ******/


    /**
     * The unique Id prevents the same booking from being queued twice as per requirements (Idempotence).
     * Laravel offer functionality out of the box which is handy!
     */
    public function uniqueId(): string
    {
        return (string) $this->booking->id;
    }



    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // GOTCHA!
        // refresh booking data since by the time the job picks it up, it may have been cancelled!
        $this->bookingRepo->refreshBooking($this->booking);

        // first if booking is below the given threshold then simply update its status to APPROVED
        if ($this->booking->total < self::AUTO_APPROVE_AMOUNT_THRESHOLD)
        {
            $this->bookingRepo->updateStatus($this->booking, BookingStatus::APPROVED);
            // nothing more to do in this case, return execution
            return;
        }

        // since we're here booking total is higher or equal to THRESHOLD and requires manual approval...
        // update its status to what it should be and fire event!
        $this->bookingRepo->updateStatus($this->booking, BookingStatus::PENDING_APPROVAL);
        // fire event!
        event(new BookingRequiresManualApproval($this->booking));
    }



    /*******      F A I L U R E      ******/


    /**
     * On final failure (after all retries exhausted), reset booking to draft
     * and audit log the exception.
     */
    public function failed(Throwable $exception): void
    {
        $previous_booking = $this->booking->toArray();

        $this->bookingRepo->updateStatus($this->booking, BookingStatus::DRAFT);


        // resolve logger from the AppServiceProvider, now we could have a different provider for Jobs so that
        // a different IAuditLogger implementation is returned that logs stuff the queued Jobs need...
        $this->logger->log(
            'job_failed',
            $this->booking,
            $previous_booking,
            [
                'exception' => get_class($exception),
                'message'   => $exception->getMessage(),
                'job'       => self::class,
            ]
        );
    }
}
