<?php

namespace App\Logging;

use App\Contracts\IAuditLogger;
use Illuminate\Database\Eloquent\Model;

class NullAuditLogger implements IAuditLogger
{

    /**
     * Does not log anything! Use freely :P
     */
    public function log(string $event, Model $auditable, array $previousPayload = [], array $newPayload = []): void
    {
        // we leave this empty so that when NullLogger instance logs, it logs NOTHING! Useful if we want to silence
        // logging
    }
}
