<?php

namespace App\Models;

use App\Enumerations\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'guide_id',
        'status',
        'total',
        'notes',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
    ];

    protected $appends = [
        'is_draft',
        'is_pending_approval',
        'is_approved',
        'is_rejected',
    ];



    /*******      C A L C U L A T E D    A T T R I B U T E S      ******/


    /**
     * Indicates current Booking instance is in Draft status
     * @return bool
     */
    public function getIsDraftAttribute()
    {
        return $this->status === BookingStatus::DRAFT;
    }

    /**
     * Indicates current Booking instance is in PendingApproval Status
     * @return bool
     */
    public function getIsPendingApprovalAttribute()
    {
        return $this->status === BookingStatus::PENDING_APPROVAL;
    }

    /**
     * Indicates current Booking instance is in Approved status
     * @return bool
     */
    public function getIsApprovedAttribute()
    {
        return $this->status === BookingStatus::APPROVED;
    }

    /**
     * Indicates current Booking instance is in Rejected status
     * @return bool
     */
    public function getIsRejectedAttribute()
    {
        return $this->status === BookingStatus::REJECTED;
    }



    /*******      R E L A T I O N S      ******/


    /**
     * Returns the Guide Model instance this Booking belongs to
     * @return BelongsTo
     */
    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }
}
