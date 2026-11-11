<?php

declare(strict_types=1);

use App\Helpers\Hooks;
use App\Models\Directory;
use App\Models\ForumTopic;
use App\Models\News;
use App\Models\Option;
use App\Models\Page;
use App\Models\Product;
use App\Models\Status;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use MyAds\Plugins\InstantIndexer\Controllers\AdminIndexerController;
use MyAds\Plugins\InstantIndexer\Services\InstantIndexerService;

// ─── 1. Autoloader ─────────────────────────────────────────────────────────────
$indexerBasePath = __DIR__;
$indexerNamespace = 'MyAds\\Plugins\\InstantIndexer\\';

if (!function_exists('myads_instant_indexer_autoload_registered')) {
    function myads_instant_indexer_autoload_registered(string $namespace, string $basePath): bool
    {
        static $registered = false;
        if ($registered) {
            return true;
        }

        spl_autoload_register(static function (string $class) use (&$registered, $namespace, $basePath): void {
            if (!str_starts_with($class, $namespace)) {
                return;
            }

            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($namespace)));
            $filePath = $basePath . '/src/' . $relativePath . '.php';

            if (is_file($filePath)) {
                require_once $filePath;
            }
        });

        $registered = true;
        return true;
    }
}

myads_instant_indexer_autoload_registered($indexerNamespace, $indexerBasePath);

// ─── 2. Service Singleton Helper ───────────────────────────────────────────────
if (!function_exists('instant_indexer_service')) {
    function instant_indexer_service(): InstantIndexerService
    {
        static $instance = null;
        if (!$instance instanceof InstantIndexerService) {
            $instance = new InstantIndexerService();
        }
        return $instance;
    }
}

// ─── 3. Register Views & Translations ──────────────────────────────────────────
View::addNamespace('instant_indexer', __DIR__ . '/views');
app('translator')->addNamespace('instant_indexer', __DIR__ . '/lang');

// ─── 4. Admin Sidebar Hook ─────────────────────────────────────────────────────
Hooks::add_action('admin_sidebar_menu', function (): void {
    $url = route('admin.instant_indexer.index');
    $isActive = request()->routeIs('admin.instant_indexer.*');
    $linkClass = $isActive ? 'nxl-link active' : 'nxl-link';

    echo '<li class="nxl-item">'
        . '<a href="' . e($url) . '" class="' . e($linkClass) . '">'
        . '<span class="nxl-micon"><i class="feather-zap"></i></span>'
        . '<span class="nxl-mtext">' . __('instant_indexer::messages.dashboard_title') . '</span>'
        . '</a>'
        . '</li>';
});

// ─── 5. Automated Content Observers / Eloquent Event Listeners ─────────────────
if (!app()->runningInConsole() || app()->environment('testing')) {
    // A. Forum Topics
    ForumTopic::saved(function ($topic) {
        try {
            $config = instant_indexer_service()->getConfig();
            if (!$config['auto_topic']) {
                return;
            }

            if ((int) ($topic->statu ?? 1) === 1 && empty($topic->group_id)) {
                $url = route('forum.topic', $topic->id);
                instant_indexer_service()->submitUrl($url, 'URL_UPDATED', 'forum_topic', $topic->id);
            }
        } catch (\Throwable $e) {
            // Suppress errors to prevent user action disruption
        }
    });

    ForumTopic::deleted(function ($topic) {
        try {
            $config = instant_indexer_service()->getConfig();
            if (!$config['auto_delete']) {
                return;
            }

            $url = route('forum.topic', $topic->id);
            instant_indexer_service()->submitUrl($url, 'URL_DELETED', 'forum_topic', $topic->id);
        } catch (\Throwable $e) {
            // Suppress errors
        }
    });

    // B. Videos / Clips (Status model)
    Status::saved(function ($status) {
        try {
            $config = instant_indexer_service()->getConfig();
            if (!$config['auto_video']) {
                return;
            }

            if (in_array((int) ($status->s_type ?? 0), [10, 14, 2, 4, 100], true) && (int) ($status->statu ?? 1) === 1 && empty($status->group_id)) {
                $url = route('forum.topic', $status->id);
                instant_indexer_service()->submitUrl($url, 'URL_UPDATED', 'status_video', $status->id);
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    });

    // C. Store Products
    Product::saved(function ($product) {
        try {
            $config = instant_indexer_service()->getConfig();
            if (!$config['auto_product']) {
                return;
            }

            if (($product->o_type ?? '') === 'store' && !empty($product->name)) {
                $url = route('store.show', $product->name);
                instant_indexer_service()->submitUrl($url, 'URL_UPDATED', 'product', $product->id);
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    });

    Product::deleted(function ($product) {
        try {
            $config = instant_indexer_service()->getConfig();
            if (!$config['auto_delete']) {
                return;
            }

            if (($product->o_type ?? '') === 'store' && !empty($product->name)) {
                $url = route('store.show', $product->name);
                instant_indexer_service()->submitUrl($url, 'URL_DELETED', 'product', $product->id);
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    });

    // D. News
    News::saved(function ($news) {
        try {
            $config = instant_indexer_service()->getConfig();
            if (!$config['auto_news']) {
                return;
            }

            if ((int) ($news->statu ?? 1) === 1) {
                $url = route('news.show', $news->id);
                instant_indexer_service()->submitUrl($url, 'URL_UPDATED', 'news', $news->id);
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    });

    // E. Directory Sites
    Directory::saved(function ($dir) {
        try {
            $config = instant_indexer_service()->getConfig();
            if (!$config['auto_directory']) {
                return;
            }

            if ((int) ($dir->statu ?? 1) === 1) {
                $url = route('directory.show', $dir->id);
                instant_indexer_service()->submitUrl($url, 'URL_UPDATED', 'directory', $dir->id);
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    });

    // F. Pages
    Page::saved(function ($page) {
        try {
            $config = instant_indexer_service()->getConfig();
            if (!$config['auto_page']) {
                return;
            }

            if (!empty($page->slug)) {
                $url = route('page.show', $page->slug);
                instant_indexer_service()->submitUrl($url, 'URL_UPDATED', 'page', $page->id);
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    });

    // G. Public Community Groups
    if (class_exists(\App\Models\Group::class)) {
        \App\Models\Group::saved(function ($group) {
            try {
                $config = instant_indexer_service()->getConfig();
                if (empty($config['auto_group'])) {
                    return;
                }

                if ($group->status === \App\Models\Group::STATUS_ACTIVE && $group->privacy === \App\Models\Group::PRIVACY_PUBLIC && !empty($group->slug)) {
                    $url = route('groups.show', $group->slug);
                    instant_indexer_service()->submitUrl($url, 'URL_UPDATED', 'group', $group->id);
                }
            } catch (\Throwable $e) {
                // Suppress
            }
        });

        \App\Models\Group::deleted(function ($group) {
            try {
                $config = instant_indexer_service()->getConfig();
                if (!$config['auto_delete']) {
                    return;
                }

                if (!empty($group->slug)) {
                    $url = route('groups.show', $group->slug);
                    instant_indexer_service()->submitUrl($url, 'URL_DELETED', 'group', $group->id);
                }
            } catch (\Throwable $e) {
                // Suppress
            }
        });
    }
}

// ─── 6. Dynamic IndexNow Verification Key Route ───────────────────────────────
Route::get('/{key}.txt', function ($key) {
    $currentKey = instant_indexer_service()->indexNow()->getKey();
    if ($key === $currentKey) {
        return response($currentKey, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
    abort(404);
})->where('key', '[a-zA-Z0-9_\-]{8,64}');

// ─── 7. Admin Routes ───────────────────────────────────────────────────────────
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/instant-indexer', [AdminIndexerController::class, 'index'])->name('admin.instant_indexer.index');
    Route::post('/admin/instant-indexer/settings', [AdminIndexerController::class, 'saveSettings'])->name('admin.instant_indexer.settings');
    Route::post('/admin/instant-indexer/test-google', [AdminIndexerController::class, 'testGoogle'])->name('admin.instant_indexer.test_google');
    Route::post('/admin/instant-indexer/test-indexnow', [AdminIndexerController::class, 'testIndexNow'])->name('admin.instant_indexer.test_indexnow');
    Route::post('/admin/instant-indexer/generate-key', [AdminIndexerController::class, 'generateKey'])->name('admin.instant_indexer.generate_key');
    Route::post('/admin/instant-indexer/submit-manual', [AdminIndexerController::class, 'submitManual'])->name('admin.instant_indexer.submit_manual');
    Route::post('/admin/instant-indexer/submit-candidate', [AdminIndexerController::class, 'submitCandidate'])->name('admin.instant_indexer.submit_candidate');
    Route::post('/admin/instant-indexer/retry/{id}', [AdminIndexerController::class, 'retryLog'])->name('admin.instant_indexer.retry');
    Route::post('/admin/instant-indexer/clear-logs', [AdminIndexerController::class, 'clearLogs'])->name('admin.instant_indexer.clear_logs');
    Route::get('/admin/instant-indexer/export', [AdminIndexerController::class, 'exportLogs'])->name('admin.instant_indexer.export');
});
