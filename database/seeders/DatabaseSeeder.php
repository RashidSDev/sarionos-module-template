<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed module-owned development data.
     *
     * SarionOS users are owned exclusively by Core. New capability modules
     * must never create local user identities from their module seeders.
     */
    public function run(): void
    {
        //
    }
}
