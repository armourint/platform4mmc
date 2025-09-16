<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModelRunController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});


Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');


Route::middleware(['auth'])->group(function(){
  Route::get('/dashboard', DashboardController::class)->name('dashboard');
  Route::get('/runs', fn()=>view('admin.runs'))->name('runs.index');
  Route::get('/upload', fn()=>view('admin.upload'))->name('runs.upload');
  Route::post('/runs', [ModelRunController::class,'store'])->name('runs.store');
  Route::get('/tides', fn()=>view('admin.tides'))->name('tides.index');
  Route::get('/scenarios', fn()=>view('admin.scenarios'))->name('scenarios.index');
  Route::get('/hindcasting', fn()=>view('admin.hindcasting'))->name('hindcasting.index');
});



/*Route::view('/login-test', 'login-test');
Route::post('/login-test', function (Request $r) {
    $cred = $r->validate(['email'=>'required|email','password'=>'required']);
    if (Auth::attempt($cred, true)) {
        $r->session()->regenerate();
        return 'Logged in as '.Auth::user()->email;
    }
    return back()->withErrors(['email'=>'Invalid credentials']);
})->name('login.test');*/