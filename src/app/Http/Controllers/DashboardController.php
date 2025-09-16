<?php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // render resources/views/dashboard/index.blade.php
        return view('dashboard.index');
    }
}
