<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PsgcController;
use App\Http\Controllers\SeniorCitizenController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\SubsidyController;
use App\Http\Controllers\SubsidyReleaseController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | SENIOR CITIZENS
    |--------------------------------------------------------------------------
    */

    Route::get('/senior-citizens', [SeniorCitizenController::class, 'index'])
        ->name('senior-citizens.index');

    // Import (Excel / CSV). Keep these above any route with a {seniorCitizen} parameter.
    Route::get('/senior-citizens/import-template', [SeniorCitizenController::class, 'importTemplate'])
        ->name('senior-citizens.import.template');

    Route::post('/senior-citizens/import', [SeniorCitizenController::class, 'import'])
        ->name('senior-citizens.import');

    Route::post('/senior-citizens', [SeniorCitizenController::class, 'store'])
        ->name('senior-citizens.store');

    Route::put('/senior-citizens/{seniorCitizen}', [SeniorCitizenController::class, 'update'])
        ->name('senior-citizens.update');

    Route::patch('/senior-citizens/{seniorCitizen}/deceased', [SeniorCitizenController::class, 'deceased'])
        ->name('senior-citizens.deceased');


    /*
    |--------------------------------------------------------------------------
    | APPLICATIONS
    |--------------------------------------------------------------------------
    */

    Route::prefix('applications')->name('applications.')->group(function () {

        Route::get('/', [ApplicationController::class, 'index'])
            ->name('index');

        Route::get('/create', [ApplicationController::class, 'create'])
            ->name('create');

        Route::post('/', [ApplicationController::class, 'store'])
            ->name('store');

        Route::get('/{application}/edit', [ApplicationController::class, 'edit'])
            ->name('edit');

        Route::put('/{application}', [ApplicationController::class, 'update'])
            ->name('update');

        Route::patch('/{application}/status', [ApplicationController::class, 'updateStatus'])
            ->name('updateStatus');

        Route::delete('/{application}', [ApplicationController::class, 'destroy'])
            ->name('destroy');

    });


    /*
    |--------------------------------------------------------------------------
    | SUBSIDIES
    |--------------------------------------------------------------------------
    */

    Route::prefix('subsidies')->name('subsidies.')->group(function () {

        Route::get('/', [SubsidyController::class, 'index'])->name('index');
        Route::post('/', [SubsidyController::class, 'store'])->name('store');
        Route::put('/{subsidy}', [SubsidyController::class, 'update'])->name('update');
        Route::delete('/{subsidy}', [SubsidyController::class, 'destroy'])->name('destroy');
        Route::post('/{seniorCitizen}/release', [SubsidyController::class, 'release'])->name('release');

    });


    /*
    |--------------------------------------------------------------------------
    | SUBSIDY RELEASES
    |--------------------------------------------------------------------------
    */

    Route::prefix('subsidy-releases')->name('subsidy-releases.')->group(function () {

        Route::get('/', [SubsidyReleaseController::class, 'index'])
            ->name('index');

        Route::get('/create', [SubsidyReleaseController::class, 'create'])
            ->name('create');

        Route::post('/', [SubsidyReleaseController::class, 'store'])
            ->name('store');

        Route::get('/{subsidyRelease}/edit', [SubsidyReleaseController::class, 'edit'])
            ->name('edit');

        Route::put('/{subsidyRelease}', [SubsidyReleaseController::class, 'update'])
            ->name('update');

        Route::delete('/{subsidyRelease}', [SubsidyReleaseController::class, 'destroy'])
            ->name('destroy');

    });


    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */

    Route::prefix('reports')->name('reports.')->group(function () {

        Route::get('/', [ReportController::class, 'index'])
            ->name('index');

        Route::get('/export', [ReportController::class, 'export'])
            ->name('export');

    });


    /*
    |--------------------------------------------------------------------------
    | ANNOUNCEMENTS
    |--------------------------------------------------------------------------
    */

    Route::prefix('announcements')->name('announcements.')->group(function () {

        Route::get('/', [AnnouncementController::class, 'index'])
            ->name('index');

        Route::get('/create', [AnnouncementController::class, 'create'])
            ->name('create');

        Route::get('/recipients-count', [AnnouncementController::class, 'recipientsCount'])
            ->name('recipients-count');

        Route::post('/', [AnnouncementController::class, 'store'])
            ->name('store');

        Route::post('/{announcement}/send', [AnnouncementController::class, 'send'])
            ->name('send');

        Route::put('/{announcement}', [AnnouncementController::class, 'update'])
            ->name('update');

        Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])
            ->name('destroy');

    });


    /*
    |--------------------------------------------------------------------------
    | PSGC ADDRESS LOOKUP
    |--------------------------------------------------------------------------
    */

    Route::prefix('psgc')->name('psgc.')->group(function () {

        Route::get('/regions', [PsgcController::class, 'regions'])
            ->name('regions');

        Route::get('/regions/{regionCode}/provinces', [PsgcController::class, 'provinces'])
            ->name('provinces');

        Route::get('/regions/{regionCode}/cities', [PsgcController::class, 'citiesInRegion'])
            ->name('cities-in-region');

        Route::get('/provinces/{provinceCode}/cities', [PsgcController::class, 'cities'])
            ->name('cities');

        Route::get('/cities/{cityCode}/barangays', [PsgcController::class, 'barangays'])
            ->name('barangays');

    });

});