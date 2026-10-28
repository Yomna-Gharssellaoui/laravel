<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ConsumerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProducerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TransformerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Role-based landing page after login / registration
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->homeRoute());
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/mon-profil', [ConsumerController::class, 'profile'])->name('consumer.profile');
    Route::get('/mon-profil/modifier', [ConsumerController::class, 'edit'])->name('consumer.edit');
    Route::patch('/mon-profil', [ConsumerController::class, 'update'])->name('consumer.update');
    Route::get('/mes-recommandations', [ConsumerController::class, 'recommendations'])->name('consumer.recommendations');

    Route::post('/produits/{product}/avis', [ReviewController::class, 'store'])->name('reviews.store');
    Route::put('/avis/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/avis/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});

Route::get('/produits', [ProductController::class, 'index'])->name('products.index');
Route::get('/produits/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/produits/{product}/trace', [ProductController::class, 'trace'])->name('products.trace');
Route::get('/producteurs', [ProducerController::class, 'index'])->name('producers.index');
Route::get('/producteurs/{producer}', [ProducerController::class, 'show'])->name('producers.show');
Route::get('/transformateurs', [TransformerController::class, 'index'])->name('transformers.index');
Route::get('/transformateurs/{transformer}', [TransformerController::class, 'show'])->name('transformers.show');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'active'])->group(function () {
    // Shared back-office: admin + supply-chain actors (data is scoped by policies)
    Route::middleware('role:admin,producer,transformer,distributor')->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('products', Admin\ProductAdminController::class);
        Route::resource('supply-chain-traces', Admin\SupplyChainTraceController::class);
        Route::resource('certificates', Admin\CertificateController::class);
    });

    // Administration only
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', Admin\UserController::class);
        Route::patch('users/{user}/block', [Admin\UserController::class, 'toggleBlock'])->name('users.block');
        Route::post('users/{user}/verification', [Admin\UserController::class, 'resendVerification'])->name('users.verification');

        Route::resource('environmental-footprints', Admin\EnvironmentalFootprintController::class);
        Route::resource('environmental-claims', Admin\EnvironmentalClaimController::class);
        Route::resource('compliance-checks', Admin\ComplianceCheckController::class);
        Route::resource('producers', Admin\ProducerController::class);
        Route::resource('transformers', Admin\TransformerController::class);
        Route::resource('distributors', Admin\DistributorController::class);
        Route::resource('alerts', Admin\AlertController::class);
        Route::resource('consumers', Admin\ConsumerController::class);

        // Reviews: moderation only (consumers write reviews from the public site)
        Route::resource('reviews', Admin\ReviewController::class)->only(['index', 'show', 'destroy']);
        Route::patch('reviews/{review}/moderate', [Admin\ReviewController::class, 'moderate'])->name('reviews.moderate');

        // AI analyses are produced by the analyzer, not typed in
        Route::resource('ai-analyses', Admin\AIAnalysisController::class)->only(['index', 'show', 'destroy']);
        Route::post('environmental-claims/{environmental_claim}/analyze', [Admin\AIAnalysisController::class, 'analyzeClaim'])->name('environmental-claims.analyze');
        Route::post('products/{product}/analyze', [Admin\AIAnalysisController::class, 'analyzeProduct'])->name('products.analyze');

        // Personal ratings are computed by the recommendation engine
        Route::resource('personal-ratings', Admin\PersonalRatingController::class)->only(['index', 'show', 'destroy']);
        Route::post('personal-ratings/recompute', [Admin\PersonalRatingController::class, 'recompute'])->name('personal-ratings.recompute');
    });
});

require __DIR__.'/auth.php';
