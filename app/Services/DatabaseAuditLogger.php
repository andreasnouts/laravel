<?php

namespace App\Services;

use App\Contracts\IAuditLogger;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class DatabaseAuditLogger implements IAuditLogger
{
    private readonly IAuditLogger $innerLogger;

    public function __construct(IAuditLogger $innerLogger)
    {
        $this->innerLogger = $innerLogger;
    }

    /**
     * Logs to both the audit_logs table and the inner logger (Laravel Log)
     * @param string $event
     * @param Model $auditable
     * @param array $previousPayload
     * @param array $newPayload
     * @return void
     */
    public function log(string $event, Model $auditable, array $previousPayload = [], array $newPayload = []): void
    {
        // we first write to our Audit Log DB Table
        AuditLog::create([
            'event'            => $event,
            'auditable_type'   => get_class($auditable),
            'auditable_id'     => $auditable->getKey(),
            'user_id'          => auth()->id(),
            'previous_payload' => $previousPayload,
            'new_payload'      => $newPayload,
            'ip_address'       => request()->ip(),
        ]);

        // ... and then also log to standard Laravel Log... (comes from our bindings in ServiceProvider)
        $this->innerLogger->log($event, $auditable, $previousPayload, $newPayload);
    }
}
