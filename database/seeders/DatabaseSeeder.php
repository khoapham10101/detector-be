<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Brand\Database\Seeders\BrandDatabaseSeeder;
use Modules\Gender\Database\Seeders\GenderDatabaseSeeder;
use Modules\GlobalStatus\Database\Seeders\GlobalStatusDatabaseSeeder;
use Modules\Permission\Database\Seeders\PermissionDatabaseSeeder;
use Modules\Role\Database\Seeders\RoleDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;
use Modules\UserStatus\Database\Seeders\UserStatusDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(GlobalStatusDatabaseSeeder::class);
        $this->call(GenderDatabaseSeeder::class);
        $this->call(BrandDatabaseSeeder::class);
        $this->call(UserStatusDatabaseSeeder::class);
        $this->call(PermissionDatabaseSeeder::class);
        $this->call(RoleDatabaseSeeder::class);
        $this->call(UserDatabaseSeeder::class);
    }
}
