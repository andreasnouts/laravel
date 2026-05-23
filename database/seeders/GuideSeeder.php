<?php

namespace Database\Seeders;

use App\Enumerations\GuideStatus;
use App\Models\Guide;
use App\Traits\HasHashID;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GuideSeeder extends Seeder
{
    use HasHashID;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Guide::create([
            'hash_id' => $this->generateHashID('unique:guides', Carbon::now()->toDateTimeString(), 9),
            'name'    => 'Maria Papadopoulou',
            'email'   => 'maria@cliomusetours.com',
            'status'  => GuideStatus::ACTIVE,
        ]);

        // small delay so Carbon timestamp differs — ensures unique hash generation
        sleep(1);

        Guide::create([
            'hash_id' => $this->generateHashID('unique:guides', Carbon::now()->toDateTimeString(), 9),
            'name'    => 'Nikos Alexandrou',
            'email'   => 'nikos@cliomusetours.com',
            'status'  => GuideStatus::SUSPENDED,
        ]);
    }
}
