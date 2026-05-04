<?php

use App\Domain\News\Http\Controllers\NewsArticleAuditController;
use App\Domain\News\Http\Controllers\NewsArticleController;
use App\Domain\News\Http\Controllers\NewsCommentAuditController;
use App\Domain\News\Http\Controllers\NewsCommentController;
use App\Domain\News\Http\Controllers\PublicNewsController;
use Illuminate\Support\Facades\Route;

// Public news article view
Route::get('news/{slug}', [PublicNewsController::class, 'show'])->name('news.show');

// Authenticated comment actions
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('news/{newsArticle}/comments', [NewsCommentController::class, 'store'])->name('news.comments.store');
    Route::post('news/comments/{newsComment}/vote', [NewsCommentController::class, 'vote'])->name('news.comments.vote');
});

// Admin routes
Route::middleware(['auth', 'verified'])->prefix('backstage/news')->group(function () {
    Route::get('/', [NewsArticleController::class, 'index'])->name('news.index');
    Route::get('create', [NewsArticleController::class, 'create'])->name('news.create');
    Route::get('comments', [NewsCommentController::class, 'index'])->name('news.comments.index');
    Route::post('/', [NewsArticleController::class, 'store'])->name('news.store');
    Route::get('{newsArticle}', [NewsArticleController::class, 'edit'])->name('news.edit');
    Route::get('{newsArticle}/audit', NewsArticleAuditController::class)->name('news.audit');
    Route::post('{newsArticle}', [NewsArticleController::class, 'update'])->name('news.update');
    Route::delete('{newsArticle}', [NewsArticleController::class, 'destroy'])->name('news.destroy');

    // Admin comment management
    Route::get('comments/{newsComment}/audit', NewsCommentAuditController::class)->name('news.comments.audit');
    Route::patch('comments/{newsComment}', [NewsCommentController::class, 'update'])->name('news.comments.update');
    Route::delete('comments/{newsComment}', [NewsCommentController::class, 'destroy'])->name('news.comments.destroy');
    Route::post('comments/{newsComment}/approve', [NewsCommentController::class, 'approve'])->name('news.comments.approve');
});
