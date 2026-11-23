<?php

declare(strict_types=1);

use App\Modules\Content\Presentation\Http\Controllers\Storefront\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
