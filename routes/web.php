<?php

use App\Events\TestEvent;
use App\Mail\TestMail;
use App\Models\Admin;
use App\Models\Dynasty\DynastyMessage;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\Level\Level;
use App\Models\Level\Levelrecievedprize;
use App\Models\Level\Prize;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\TicketRecieved;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Models\Level\UserLevel;
use Illuminate\Database\Eloquent\Model;
use PhpParser\JsonDecoder;
use Illuminate\Support\Facades\DB;
use App\Models\Feature;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/send', function() {
    $user = User::find(3);
    $user->email = 'sa204@yahoo.com';
    Mail::to($user)->send(new TestMail($user));
    return 'mail sent';
});


