<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

interface IAuditLogger
{
    /**
     * Logs a new entry
     * @param string $event Created, updated, deleted
     * @param Model $auditable The participating Model
     * @param array $previousPayload Model state before the action performed
     * @param array $newPayload The new Payload (after action is performed)
     * @return void
     */
    public function log(string $event, Model $auditable, array $previousPayload = [], array $newPayload = []): void;
}
