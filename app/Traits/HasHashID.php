<?php

namespace App\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

trait HasHashID
{
    /**
     * Returns a new hash based on the parameters passed in
     * @param string $validate the validation string to be performed on the generated hash: Asking for hash to be unique : 'unique:table_name_here'
     * @param string $parameters The string value to be hashed (can always add Carbon::now()->toDateTimeString() for randomness)
     * @param int $hash_length The desired length of the produced hash, default value is 9.
     */
    public function generateHashID(string $validate, $parameters, int $hash_length = 9): string
    {
        do {
            $hash = Hash::make($parameters);
        } while (validator(['hash_id' => $hash], ['hash_id' => $validate])->fails());

        return (Str::substr(Str::slug($hash), 0, $hash_length));
    }
}
