<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AiToolsController;
use App\Http\Controllers\ConsumerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProAccountRequestController;
use App\Http\Controllers\ProducerController;
use App\Http\Controllers\ProductAssistantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TransformerController;
use App\Http\Controllers\TwoFactorController;
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

    // Two-factor authentication (TOTP); enabling, disabling and new codes require the current password
    Route::post('/user/two-factor', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/user/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/user/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');
    Route::delete('/user/two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/mon-profil', [ConsumerController::class, 'profile'])->name('consumer.profile');
    Route::get('/mon-profil/modifier', [ConsumerController::class, 'edit'])->name('consumer.edit');
    Route::patch('/mon-profil', [ConsumerController::class, 'update'])->name('consumer.update');
    Route::get('/mes-recommandations', [ConsumerController::class, 'recommendations'])->name('consumer.recommendations');

    // Ask for a professional account (producer, transformer, distributor)
    Route::get('/compte-pro', [ProAccountRequestController::class, 'create'])->name('pro-account.create');
    Route::post('/compte-pro', [ProAccountRequestController::class, 'store'])->middleware('throttle:5,1')->name('pro-account.store');
    Route::delete('/compte-pro/{proAccountRequest}', [ProAccountRequestController::class, 'destroy'])->name('pro-account.destroy');

    Route::post('/produits/{product}/avis', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/produits/{product}/assistant', ProductAssistantController::class)
        ->middleware('throttle:10,1')
        ->name('products.assistant');
    Route::post('/outils-ia/avis', [AiToolsController::class, 'reviewCheck'])->middleware('throttle:30,1')->name('ai-tools.review-check');
    Route::put('/avis/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/avis/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});

Route::get('/produits', [ProductController::class, 'index'])->name('products.index');
Route::get('/produits/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/produits/{product}/trace', [ProductController::class, 'trace'])->name('products.trace');
Route::get('/produits/{product}/qr-code', [ProductController::class, 'qrCode'])->name('products.qr');
// Consumer AI tools (local ML models)
Route::get('/outils-ia', [AiToolsController::class, 'index'])->name('ai-tools.index');
Route::middleware('throttle:20,1')->group(function () {
    Route::post('/outils-ia/greenwashing', [AiToolsController::class, 'greenwashing'])->name('ai-tools.greenwashing');
    Route::post('/outils-ia/empreinte', [AiToolsController::class, 'footprint'])->name('ai-tools.footprint');
});
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
        Route::get('ml-models', [Admin\MlModelsController::class, 'index'])->name('ml-models.index');
        Route::get('pro-requests', [Admin\ProAccountRequestController::class, 'index'])->name('pro-requests.index');
        Route::get('pro-requests/{proRequest}', [Admin\ProAccountRequestController::class, 'show'])->name('pro-requests.show');
        Route::patch('pro-requests/{proRequest}/approve', [Admin\ProAccountRequestController::class, 'approve'])->name('pro-requests.approve');
        Route::patch('pro-requests/{proRequest}/reject', [Admin\ProAccountRequestController::class, 'reject'])->name('pro-requests.reject');
        Route::get('pro-requests/{proRequest}/document', [Admin\ProAccountRequestController::class, 'document'])->name('pro-requests.document');
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
