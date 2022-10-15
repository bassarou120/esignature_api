<?php

use App\Http\Controllers\API\Authentification;
use App\Http\Controllers\API\GroupMemberController;
use App\Http\Controllers\API\MailerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DocumentController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\ModelsController;
use App\Http\Controllers\API\PHPMailerController;
use App\Http\Controllers\API\PricingController;
use App\Http\Controllers\API\ReportIssuesController;
use App\Http\Controllers\API\SendingController;
use App\Http\Controllers\API\SendingParameterController;
use App\Http\Controllers\API\StatusController;
use App\Http\Controllers\API\SubscribeToController;
use App\Http\Controllers\API\TeamsController;
use App\Http\Controllers\API\TypeSignatureController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ContactController;
use App\Models\Teams;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post("send-email", [MailerController::class, "composeEmail"])->name("send-email");

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

// Authentification

Route::post('login', [Authentification::class, 'login']);
Route::post('register', [Authentification::class, 'register']);
Route::post('password/forgot-password', [Authentification::class, 'sendResetLinkResponse'])->name('passwords.sent.user');
Route::post('password/reset', [Authentification::class, 'sendResetResponse'])->name('passwords.reset.user');
Route::get('activateacount/{token}', [Authentification::class, 'activate_account'])->name('user.activateaccount');

//Route::get('activateacount/{token?}', function ($token) {
//   // echo $token;
//    $a = base64_encode('a/');
//  echo  $a;
//  echo  base64_decode($a);
//});

Route::group(['middleware' => ['cors', 'json.response'] ],function(){
    Route::get('sendings/mail/opened/{id_sending}/{id_signataire}', [SendingController::class, 'mail_opened'])->name('user.sendings.mailopened');
    Route::get('sendings/doc/opened/{id_sending}/{id_signataire}', [SendingController::class, 'doc_opened'])->name('user.sendings.docopened');
    Route::put('sendings/doc/signed', [SendingController::class, 'doc_signed'])->name('user.sendings.docsigned');
});

Route::group( ['middleware' => ['auth:user-api','scopes:user', 'json.response'] ],function() {
    Route::get('sendings/download/original/document/{id}', [SendingController::class, 'downloadTheOriginalFile'])->name('user.sendings.download.original');
    Route::get('sendings/download/proof/document/{id}', [SendingController::class, 'downloadTheProofFile'])->name('user.sendings.download.proof');
    Route::get('sendings/download/signed/document/{id}', [SendingController::class, 'downloadTheSignedFile'])->name('user.sendings.download.signed');

});

Route::group( ['middleware' => ['auth:user-api','scopes:user','cors', 'json.response'] ],function(){
    Route::apiResource('users', UserController::Class, [
        'names' => [
            'index' => 'user.users.index',
            'show' => 'user.users.show',
            'update' => 'user.users.update',
            'store' => 'user.users.store',
            'destroy' => 'user.users.delete',
        ]
    ]);
    Route::get('pricings', [PricingController::class, 'index'])->name('user.pricings');
    Route::get('sendparameters', [SendingParameterController::class, 'index'])->name('user.sendparameters');
    Route::get('activatedsendparameters', [SendingParameterController::class, 'getActivatedParameter'])->name('user.activatedsendparameters');
    Route::get('typesignatures', [TypeSignatureController::class, 'index'])->name('user.typesignatures');

    Route::apiResource('groups', GroupController::Class, [
        'names' => [
            'index' => 'user.groups.index',
            'show' => 'user.groups.show',
            'update' => 'user.groups.update',
            'store' => 'user.groups.store',
            'destroy' => 'user.groups.delete',
        ]
    ]);

    Route::get('groups/{id_user}/user', [GroupController::class, 'getGroupByUser'])->name('user.groups.byuser');
    Route::get('groups/members/{id_group}', [GroupController::class, 'getMembersOfGroup'])->name('user.groups.members');

    Route::apiResource('groupsmembers', GroupMemberController::Class, [
        'names' => [
            'index' => 'user.groups.members.index',
            'show' => 'user.groups.members.show',
            'update' => 'user.groups.members.update',
            'store' => 'user.groups.members.store',
            'destroy' => 'user.groups.members.delete',
        ]
    ]);

    Route::apiResource('contacts', ContactController::Class, [
        'names' => [
            'index' => 'user.contacts.index',
            'show' => 'user.contacts.show',
            'update' => 'user.contacts.update',
            'store' => 'user.contacts.store',
            'destroy' => 'user.contacts.delete',
        ]
    ]);
    Route::get('contacts/{id_user}/user', [ContactController::class, 'getContactByUser'])->name('user.contact.byuser');

    Route::apiResource('members', MemberController::Class, [
        'names' => [
            'index' => 'user.members.index',
            'show' => 'user.members.show',
            'update' => 'user.members.update',
            'store' => 'user.members.store',
            'destroy' => 'user.members.delete',
        ]
    ])->except(['destroy']);
    Route::get('members/{id_user}/user', [MemberController::class, 'getMemberByUser'])->name('user.members.byuser');
    Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('user.members.delete');
    Route::get('members/get/statistique', [MemberController::class, 'getStat'])->name('user.members.stat');


    Route::apiResource('documents', DocumentController::Class, [
        'names' => [
            'index' => 'user.documents.index',
            'show' => 'user.documents.show',
            'update' => 'user.documents.update',
            'store' => 'user.documents.store',
            'destroy' => 'user.documents.delete',
        ]
    ]);

    Route::apiResource('status', StatusController::Class, [
        'names' => [
            'index' => 'user.status.index',
            'show' => 'user.status.show',
            'update' => 'user.status.update',
            'store' => 'user.status.store',
            'destroy' => 'user.status.delete',
        ]
    ]);

    Route::apiResource('models', ModelsController::Class, [
        'names' => [
            'index' => 'user.models.index',
            'show' => 'user.models.show',
            'update' => 'user.models.update',
            'store' => 'user.models.store',
            'destroy' => 'user.models.delete',
        ]
    ]);
    Route::get('models/{id_user}/user', [ModelsController::class, 'getModelByUser'])->name('user.models.byuser');
    Route::get('models/bysignaturetype/{id_user}/{id_type_signature}/user', [ModelsController::class, 'getModelByUserAndSignatureType'])->name('user.models.byuser.bysignaturetype');

    Route::apiResource('sendings', SendingController::Class, [
        'names' => [
            'index' => 'user.sendings.index',
            'show' => 'user.sendings.show',
            'update' => 'user.sendings.update',
            'store' => 'user.sendings.store',
            'destroy' => 'user.sendings.delete',
        ]
    ]);
    Route::get('sendings/bysignataire/{id_sending}/{id_signataire}', [SendingController::class, 'getSendingWidgetBySignataire'])->name('user.sendings.widget.signataire');
    Route::post('sendings/finalise/configuration/{id}', [SendingController::class, 'addFinaliseConfiguration'])->name('user.sendings.add.last.configuration');
    Route::get('sendings/{id_user}/user/{typesignature?}', [SendingController::class, 'getSendingByUser'])->name('user.sendings.byuser');
    Route::get('sendings/get/top/{id_user}/user', [SendingController::class, 'getTopPendingSending'])->name('user.sendings.byuser.top.docsigned');
    Route::post('sendings/confirm/model/registration/{sending}', [SendingController::class, 'saveModelRegistration'])->name('user.sendings.confirm.register.model');
    Route::post('sendings/cancel/model/registration/{sending}', [SendingController::class, 'cancelModelRegistration'])->name('user.sendings.cancel.register.model');
    Route::post('sendings/copy/{sending}', [SendingController::class, 'copySending'])->name('user.sendings.copie');
    Route::post('sendings/archived', [SendingController::class, 'archiveSending'])->name('user.sendings.archived');

    Route::get('sendings/statut/{statut}', [SendingController::class, 'ended_sending'])->name('user.sendings.statut');
    Route::get('sendings/get/all/signataire/laststatut/{id}', [SendingController::class, 'sending_signataire_statut'])->name('user.sendings.signataire.statut');
    Route::get('sendings/get/all/signataire/{id}', [SendingController::class, 'get_signataire_by_sending'])->name('user.sendings.get.signataire');
    Route::get('sendings/get/all/cc/{id}', [SendingController::class, 'sending_cc'])->name('user.sendings.cc');
    Route::get('sendings/get/all/validataire/laststatut/{id}', [SendingController::class, 'sending_validataire_statut'])->name('user.sendings.validataire');
    Route::put('sendings/add/widget/{id_sending}', [SendingController::class, 'addSendingWidget'])->name('user.sendings.addwidget');
    Route::put('sendings/add/signataires/{id_sending}', [SendingController::class, 'addSendingSignataire'])->name('user.sendings.addsignataire');
    Route::put('sendings/add/signataires/answer', [SendingController::class, 'addSignataireAnswer'])->name('user.sendings.addsignataireanswer');


    Route::apiResource('report', ReportIssuesController::Class, [
        'names' => [
            'index' => 'user.report.index',
            'show' => 'user.report.show',
            'update' => 'user.report.update',
            'store' => 'user.report.store',
            'destroy' => 'user.report.delete',
        ]
    ]);

    Route::apiResource('subscribeto', SubscribeToController::Class, [
        'names' => [
            'index' => 'user.subscribeto.index',
            'create' => 'user.subscribeto.create',
            'show' => 'user.subscribeto.show',
            'edit' => 'user.subscribeto.edit',
            'update' => 'user.subscribeto.update',
            'store' => 'user.subscribeto.store',
            'destroy' => 'user.subscribeto.delete',
        ]
    ]);

    Route::post('/logout', [Authentification::class, 'logout'])->name('api.logout.user');

});

//Auth::routes();

Route::any('{path}', function () {
    return response()->json([
        'message' => 'Route not found'
    ], 404);
})->where('path', '.*');

