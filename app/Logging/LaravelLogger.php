<?php
declare(strict_types=1);

namespace App\Logging;

use App\Contracts\IAuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class LaravelLogger implements IAuditLogger
{
    /**
     * @inheritDoc
     */
    public function log(string $event, Model $auditable, array $previousPayload = [], array $newPayload = []): void
    {
        Log::info("[Audit] " . $event, [
            'auditable_type' => get_class($auditable),
            'auditable_id'   => $auditable->getKey(),
            'old'            => $previousPayload,
            'new'            => $newPayload,
        ]);
    }
}
