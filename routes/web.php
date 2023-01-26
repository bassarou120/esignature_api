<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ModelsController;
use App\Http\Controllers\PHPMailerController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ReportIssuesController;
use App\Http\Controllers\SendingController;
use App\Http\Controllers\SendingParameterController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SubscribeToController;
use App\Http\Controllers\TeamsController;
use App\Http\Controllers\TypeSignatureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ContactController;
use App\Models\Teams;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('migrate', function() {
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh -—seed');
});



Route::get('/', function () {
    return view('auth.login');
})->name('/')
    ->middleware('guest');

// Authentification
Route::get('pdf_test', [SendingController::class, 'test_for_doc'])->name('pdf');
Route::get('pdf_test_fpdf', [SendingController::class, 'test_with_fpdf'])->name('pdf_1');
Route::get('pdf_with_img', [SendingController::class, 'test_with_img'])->name('img_1');

Route::group(['prefix' => 'admins', 'middleware' => ['guest:admin'] ], function () {
    Route::post('password/forgot-password', [AdminController::class, 'sendResetLinkResponseCustomer'])->name('passwords.sent.admin');
    Route::post('password/reset', [AdminController::class, 'sendResetResponseCustomer'])->name('passwords.reset.admin');
    Route::post('login', [AdminController::class, 'login']);
    Route::get('activateacount/{token}', [AdminController::class, 'activate_account'])->name('admin.activateaccount');
});

Route::group(['middleware' => ['guest'] ], function () {
    // Route::get('home', [HomeController::class, 'home'])->name('home');
});

Route::group(['prefix' => 'admins', 'middleware' => ['auth:admin']], function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::resource('admins',AdminController::Class);
    Route::resource('users',UserController::Class);
    Route::resource('pricings',PricingController::Class);
    Route::resource('sendparameters',SendingParameterController::Class);
    Route::resource('typesignatures',TypeSignatureController::Class);
    Route::resource('teams',Teams::Class);
    Route::resource('documents',DocumentController::Class);
    Route::resource('status',StatusController::Class);
    Route::resource('sendings',SendingController::Class);
    Route::resource('status',StatusController::Class);
    Route::resource('report',ReportIssuesController::Class);
    Route::resource('subscribeto',SubscribeToController::Class);
});


Auth::routes();

Route::fallback(function () {
    return view('errors.404'); //custom view
});
