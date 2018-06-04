<?php
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call(AdminRoleSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(AdminAuthSeeder::class);
        $this->call(OpCouponSeeder::class);
        $this->call(OpCouponDetailSeeder::class);
        $this->call(OpActiveSeeder::class);

        Model::reguard();
    }
}
