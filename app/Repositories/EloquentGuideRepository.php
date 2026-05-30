<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Guide;
use Illuminate\Database\Eloquent\Model;

class EloquentGuideRepository implements \App\Contracts\IGuideRepository
{

    /**
     * @inheritDoc
     */
    public function getByHashId(string $hashId): Model|Guide|null
    {
        return Guide::query()->where('hash_id', '=', $hashId)->first();
    }

    /**
     * @inheritDoc
     */
    public function getByHashIdWithoutGlobalScopes(string $hashId): ?Guide
    {
        return Guide::withoutGlobalScopes()->where('hash_id', '=', $hashId)->first();
    }
}
