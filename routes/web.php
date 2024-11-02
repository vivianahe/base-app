<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EventsNController;
use App\Http\Controllers\AssistanceController;
use App\Http\Controllers\ExcelEventController;
use App\Http\Controllers\ParticipantsController;
use App\Http\Controllers\EmailModController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes(['register' => false]);
Route::get('/', [App\Http\Controllers\HomeController::class, 'index']);
Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);

//LectorQR
Route::view('/qrRead/{id}', 'home');
Route::view('/assistance', 'home')->name('assistance');

//VIEW ROUTES
Route::group(['middleware' => ['auth']], function () {
    Route::view('/events', 'home')->name('events');
    Route::view('/participants', 'home')->name('participants');
    Route::view('/users', 'home')->name('users');
    Route::view('/emailMod', 'home')->name('emailMod');
});

//EVENTOS
Route::group(['middleware' => ['web', 'permission:event_management', 'auth']], function () {
    Route::get('getEventData', [EventsNController::class, 'index'])->name('getEventData');
    Route::post('addEventNoved', [EventsNController::class, 'store'])->name('addEventNoved');
    Route::get('deleteEvent/{roleId}', [EventsNController::class, 'destroy'])->name('deleteEvent');
    Route::get('getEventId/{id}', [EventsNController::class, 'edit'])->name('getEventId');
    Route::post('updateEventNoved', [EventsNController::class, 'update'])->name('updateEventNoved');
});
//PARTICIPANTES
Route::group(['middleware' => ['web', 'permission:manage_participants', 'auth']], function () {
    Route::get('getParticipantData', [ParticipantsController::class, 'index'])->name('getParticipantData');
    Route::post('addParticipant', [ParticipantsController::class, 'store'])->name('addParticipant');
    Route::get('getEventData', [EventsNController::class, 'index'])->name('getEventData');
    Route::get('getParticipantId/{id}/{event_id}', [ParticipantsController::class, 'edit'])->name('getParticipantId');
    Route::get('deleteParticipant/{id}/{event_id}', [ParticipantsController::class, 'destroy'])->name('deleteParticipant');
    Route::get('/getEventExport/{id}', [ExcelEventController::class, 'getEventExport'])->name('getEventExport');
    Route::get('/getParticipantQR/{data}/{id}', [ParticipantsController::class, 'getParticipantQR'])->name('getParticipantQR');
    Route::get('/stateParticipant/{id}/{event_id}', [ParticipantsController::class, 'stateParticipant'])->name('stateParticipant');
    Route::post('stateParticipantManual', [ParticipantsController::class, 'stateParticipantManual'])->name('stateParticipantManual');
    Route::get('certificateParticipant/{data}', [ParticipantsController::class, 'certificateParticipant'])->name('certificateParticipant');
    Route::get('tagParticipant/{data}', [ParticipantsController::class, 'tagParticipant'])->name('tagParticipant');
    Route::get('getParticipantAirtable', [ParticipantsController::class, 'getParticipantAirtable'])->name('getParticipantAirtable');
    Route::get('sendAccreditation/{data}', [ParticipantsController::class, 'sendAccreditation'])->name('sendAccreditation');
});

Route::get('getParticipantInscrit/{id}', [AssistanceController::class, 'getParticipantInscrit'])->name('getParticipantInscrit');
Route::get('getEventDate', [AssistanceController::class, 'getEventDate'])->name('getEventDate');
Route::get('getEventData', [EventsNController::class, 'index'])->name('getEventData');
Route::get('getParticipantData', [ParticipantsController::class, 'index'])->name('getParticipantData');
Route::get('getEventId/{id}', [EventsNController::class, 'edit'])->name('getEventId');
Route::get('/getParticipantQR/{data}/{id}', [ParticipantsController::class, 'getParticipantQR'])->name('getParticipantQR');
Route::get('getInitialRedirectPath', [UserController::class, 'getInitialRedirectPath'])->name('getInitialRedirectPath');

//USER
Route::group(['middleware' => ['web', 'permission:user_management', 'auth']], function () {
    Route::get('getUserData', [UserController::class, 'index'])->name('getUserData');
    Route::post('addUser', [UserController::class, 'store'])->name('addUser');
    Route::delete('deleteUser/{id}', [UserController::class, 'destroy'])->name('deleteUser');
    Route::get('getUserId/{id}', [UserController::class, 'edit'])->name('getUserId');
    Route::post('updateUser', [UserController::class, 'update'])->name('updateUser');
    Route::get('getAccessHistory/{id}', [UserController::class, 'getAccessHistory'])->name('getAccessHistory');
    Route::post('updateState', [UserController::class, 'updateState'])->name('updateState');
    Route::get('getRol', [UserController::class, 'getRol'])->name('getRol');
});

//EMAIL CONFIG
Route::group(['middleware' => ['web', 'permission:email_management', 'auth']], function () {
    Route::get('getEmailSend', [EmailModController::class, 'index'])->name('EmailSend');
    Route::post('setEmailSend', [EmailModController::class, 'store'])->name('setEmailSend');
});
