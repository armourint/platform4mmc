<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class AdminUserSeeder extends Seeder {
  public function run(): void {
    User::firstOrCreate(['email'=>'admin@example.com'],['name'=>'Admin','password'=>Hash::make('password'),'role'=>'admin']);
  }
}
