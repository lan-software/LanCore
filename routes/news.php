<?php

use App\Domain\News\Http\Controllers\NewsArticleController;
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
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('news-admin', [NewsArticleController::class, 'index'])->name('news.index');
    Route::get('news-admin/create', [NewsArticleController::class, 'create'])->name('news.create');
    Route::get('news-admin/comments', [NewsCommentController::class, 'index'])->name('news.comments.index');
    Route::post('news-admin', [NewsArticleController::class, 'store'])->name('news.store');
    Route::get('news-admin/{newsArticle}', [NewsArticleController::class, 'edit'])->name('news.edit');
    Route::post('news-admin/{newsArticle}', [NewsArticleController::class, 'update'])->name('news.update');
    Route::delete('news-admin/{newsArticle}', [NewsArticleController::class, 'destroy'])->name('news.destroy');

    // Admin comment management
    Route::patch('news/comments/{newsComment}', [NewsCommentController::class, 'update'])->name('news.comments.update');
    Route::delete('news/comments/{newsComment}', [NewsCommentController::class, 'destroy'])->name('news.comments.destroy');
    Route::post('news/comments/{newsComment}/approve', [NewsCommentController::class, 'approve'])->name('news.comments.approve');
});
