<?php

namespace MyAds\Plugins\InstantIndexer\Services;

use App\Models\Directory;
use App\Models\ForumTopic;
use App\Models\News;
use App\Models\Option;
use App\Models\Page;
use App\Models\Product;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MyAds\Plugins\InstantIndexer\Models\IndexerLog;

class InstantIndexerService
{
    protected GoogleIndexingService $googleService;
    protected IndexNowService $indexNowService;

    public function __construct(
        ?GoogleIndexingService $googleService = null,
        ?IndexNowService $indexNowService = null
    ) {
        $this->googleService = $googleService ?? new GoogleIndexingService();
        $this->indexNowService = $indexNowService ?? new IndexNowService();
    }

    public function google(): GoogleIndexingService
    {
        return $this->googleService;
    }

    public function indexNow(): IndexNowService
    {
        return $this->indexNowService;
    }

    /**
     * Submit a single URL to selected engines.
     *
     * @param string $url The absolute URL
     * @param string $action 'URL_UPDATED' or 'URL_DELETED'
     * @param string|null $sourceType Content type (forum_topic, status_video, product, etc.)
     * @param int|null $sourceId ID of the content
     * @param array $engines ['google', 'indexnow']
     * @return array
     */
    public function submitUrl(
        string $url,
        string $action = 'URL_UPDATED',
        ?string $sourceType = 'manual',
        ?int $sourceId = null,
        array $engines = ['google', 'indexnow']
    ): array {
        $results = [];
        $url = trim($url);

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'الرابط غير صالح: ' . $url,
                'results' => [],
            ];
        }

        $config = $this->getConfig();

        // 1. Google Indexing
        if (in_array('google', $engines, true) && ($config['google_enabled'] || in_array('google_forced', $engines, true))) {
            $googleResult = $this->googleService->publishUrl($url, $action);
            
            $log = IndexerLog::create([
                'url' => $url,
                'engine' => 'google',
                'action' => $action,
                'status' => $googleResult['success'] ? 'success' : 'error',
                'http_code' => $googleResult['http_code'],
                'response_body' => is_array($googleResult['response']) ? json_encode($googleResult['response'], JSON_UNESCAPED_UNICODE) : (string) $googleResult['response'],
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'error_message' => $googleResult['success'] ? null : $googleResult['message'],
                'execution_time_ms' => $googleResult['time_ms'] ?? null,
            ]);

            $results['google'] = array_merge($googleResult, ['log_id' => $log->id]);
        }

        // 2. IndexNow
        if (in_array('indexnow', $engines, true) && ($config['indexnow_enabled'] || in_array('indexnow_forced', $engines, true))) {
            $indexNowResult = $this->indexNowService->publishUrl($url);

            $log = IndexerLog::create([
                'url' => $url,
                'engine' => 'indexnow',
                'action' => $action,
                'status' => $indexNowResult['success'] ? 'success' : 'error',
                'http_code' => $indexNowResult['http_code'],
                'response_body' => is_array($indexNowResult['response']) ? json_encode($indexNowResult['response'], JSON_UNESCAPED_UNICODE) : (string) $indexNowResult['response'],
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'error_message' => $indexNowResult['success'] ? null : $indexNowResult['message'],
                'execution_time_ms' => $indexNowResult['time_ms'] ?? null,
            ]);

            $results['indexnow'] = array_merge($indexNowResult, ['log_id' => $log->id]);
        }

        $overallSuccess = count($results) > 0 && collect($results)->every(fn ($r) => $r['success'] ?? false);

        return [
            'success' => $overallSuccess,
            'message' => $overallSuccess ? 'تم إرسال الرابط لمحركات البحث بنجاح.' : 'تمت معالجة الإرسال مع بعض الملاحظات.',
            'results' => $results,
        ];
    }

    /**
     * Submit batch of URLs to engines.
     */
    public function submitBatch(
        array $urls,
        string $action = 'URL_UPDATED',
        ?string $sourceType = 'batch',
        array $engines = ['google', 'indexnow']
    ): array {
        $urls = array_values(array_unique(array_filter($urls, fn ($u) => filter_var(trim($u), FILTER_VALIDATE_URL))));

        if (empty($urls)) {
            return [
                'success' => false,
                'message' => 'لم يتم العثور على روابط صالحة للإرسال.',
                'count' => 0,
                'results' => [],
            ];
        }

        $config = $this->getConfig();
        $results = [
            'google' => ['success' => 0, 'error' => 0],
            'indexnow' => ['success' => 0, 'error' => 0],
        ];

        // 1. Google (Iterate URLs)
        if (in_array('google', $engines, true) && ($config['google_enabled'] || in_array('google_forced', $engines, true))) {
            foreach ($urls as $url) {
                $res = $this->googleService->publishUrl($url, $action);
                IndexerLog::create([
                    'url' => $url,
                    'engine' => 'google',
                    'action' => $action,
                    'status' => $res['success'] ? 'success' : 'error',
                    'http_code' => $res['http_code'],
                    'response_body' => is_array($res['response']) ? json_encode($res['response'], JSON_UNESCAPED_UNICODE) : (string) $res['response'],
                    'source_type' => $sourceType,
                    'error_message' => $res['success'] ? null : $res['message'],
                    'execution_time_ms' => $res['time_ms'] ?? null,
                ]);

                if ($res['success']) {
                    $results['google']['success']++;
                } else {
                    $results['google']['error']++;
                }
            }
        }

        // 2. IndexNow (Single batch payload)
        if (in_array('indexnow', $engines, true) && ($config['indexnow_enabled'] || in_array('indexnow_forced', $engines, true))) {
            $indexNowRes = $this->indexNowService->publishBatch($urls);

            foreach ($urls as $url) {
                IndexerLog::create([
                    'url' => $url,
                    'engine' => 'indexnow',
                    'action' => $action,
                    'status' => $indexNowRes['success'] ? 'success' : 'error',
                    'http_code' => $indexNowRes['http_code'],
                    'response_body' => is_array($indexNowRes['response']) ? json_encode($indexNowRes['response'], JSON_UNESCAPED_UNICODE) : (string) $indexNowRes['response'],
                    'source_type' => $sourceType,
                    'error_message' => $indexNowRes['success'] ? null : $indexNowRes['message'],
                    'execution_time_ms' => $indexNowRes['time_ms'] ?? null,
                ]);
            }

            if ($indexNowRes['success']) {
                $results['indexnow']['success'] = count($urls);
            } else {
                $results['indexnow']['error'] = count($urls);
            }
        }

        return [
            'success' => true,
            'message' => "تمت معالجة إرسال " . count($urls) . " رابط دفعة واحدة.",
            'count' => count($urls),
            'results' => $results,
        ];
    }

    /**
     * Get aggregate statistics.
     */
    public function getStatistics(): array
    {
        $today = Carbon::today();

        $totalSubmissions = IndexerLog::count();
        $todaySubmissions = IndexerLog::where('created_at', '>=', $today)->count();

        $googleTotal = IndexerLog::where('engine', 'google')->count();
        $googleSuccess = IndexerLog::where('engine', 'google')->where('status', 'success')->count();
        $googleError = IndexerLog::where('engine', 'google')->where('status', 'error')->count();
        $googleRate = $googleTotal > 0 ? round(($googleSuccess / $googleTotal) * 100, 1) : 100;

        $indexnowTotal = IndexerLog::where('engine', 'indexnow')->count();
        $indexnowSuccess = IndexerLog::where('engine', 'indexnow')->where('status', 'success')->count();
        $indexnowError = IndexerLog::where('engine', 'indexnow')->where('status', 'error')->count();
        $indexnowRate = $indexnowTotal > 0 ? round(($indexnowSuccess / $indexnowTotal) * 100, 1) : 100;

        $totalErrors = IndexerLog::where('status', 'error')->count();

        return [
            'total_all' => $totalSubmissions,
            'total_today' => $todaySubmissions,
            'google' => [
                'total' => $googleTotal,
                'success' => $googleSuccess,
                'error' => $googleError,
                'rate' => $googleRate,
            ],
            'indexnow' => [
                'total' => $indexnowTotal,
                'success' => $indexnowSuccess,
                'error' => $indexnowError,
                'rate' => $indexnowRate,
            ],
            'total_errors' => $totalErrors,
        ];
    }

    /**
     * Get candidates for batch indexing directly from database.
     */
    public function getCandidateContent(int $limit = 30): array
    {
        $items = [];

        // 1. Forum Topics
        try {
            $topics = ForumTopic::where('statu', 1)
                ->whereNull('group_id')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            foreach ($topics as $topic) {
                $url = route('forum.topic', $topic->id);
                $lastLog = IndexerLog::where('url', $url)->latest()->first();

                $items[] = [
                    'id' => $topic->id,
                    'title' => $topic->name ?? ('موضوع رقم #' . $topic->id),
                    'type' => 'forum_topic',
                    'type_label' => 'موضوع منتدى',
                    'url' => $url,
                    'date' => $topic->date ? Carbon::createFromTimestamp($topic->date)->diffForHumans() : '',
                    'is_indexed' => $lastLog && $lastLog->status === 'success',
                    'last_status' => $lastLog ? $lastLog->status : 'none',
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        // 2. Videos / Shorts
        try {
            $videos = Status::where('statu', 1)
                ->whereIn('s_type', [10, 14, 2, 4])
                ->whereNull('group_id')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            foreach ($videos as $video) {
                $url = route('forum.topic', $video->id);
                $lastLog = IndexerLog::where('url', $url)->latest()->first();

                $items[] = [
                    'id' => $video->id,
                    'title' => Str::limit(strip_tags($video->txt ?? ('فيديو رقم #' . $video->id)), 50),
                    'type' => 'status_video',
                    'type_label' => $video->s_type == 14 ? 'شورتس' : 'فيديو',
                    'url' => $url,
                    'date' => $video->date ? Carbon::createFromTimestamp($video->date)->diffForHumans() : '',
                    'is_indexed' => $lastLog && $lastLog->status === 'success',
                    'last_status' => $lastLog ? $lastLog->status : 'none',
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        // 3. Products
        try {
            $products = Product::where('o_type', 'store')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            foreach ($products as $prod) {
                $url = route('store.show', $prod->name);
                $lastLog = IndexerLog::where('url', $url)->latest()->first();

                $items[] = [
                    'id' => $prod->id,
                    'title' => $prod->name,
                    'type' => 'product',
                    'type_label' => 'منتج متجر',
                    'url' => $url,
                    'date' => '',
                    'is_indexed' => $lastLog && $lastLog->status === 'success',
                    'last_status' => $lastLog ? $lastLog->status : 'none',
                ];
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        return $items;
    }

    /**
     * Get all configuration settings.
     */
    public function getConfig(): array
    {
        $options = Option::where('o_type', 'instant_indexer')->pluck('o_valuer', 'name')->toArray();

        return [
            'google_enabled' => (bool) ($options['indexer_google_enabled'] ?? false),
            'google_json' => $options['indexer_google_json'] ?? '',
            'indexnow_enabled' => (bool) ($options['indexer_indexnow_enabled'] ?? true),
            'indexnow_key' => $options['indexer_indexnow_key'] ?? $this->indexNowService->getKey(),
            'indexnow_host' => $options['indexer_indexnow_host'] ?? $this->indexNowService->getHost(),
            'auto_topic' => (bool) ($options['indexer_auto_topic'] ?? true),
            'auto_video' => (bool) ($options['indexer_auto_video'] ?? true),
            'auto_product' => (bool) ($options['indexer_auto_product'] ?? true),
            'auto_news' => (bool) ($options['indexer_auto_news'] ?? true),
            'auto_directory' => (bool) ($options['indexer_auto_directory'] ?? true),
            'auto_page' => (bool) ($options['indexer_auto_page'] ?? true),
            'auto_delete' => (bool) ($options['indexer_auto_delete'] ?? true),
        ];
    }

    /**
     * Save configuration options.
     */
    public function saveConfig(array $data): bool
    {
        $fields = [
            'indexer_google_enabled' => isset($data['google_enabled']) && $data['google_enabled'] ? '1' : '0',
            'indexer_google_json' => $data['google_json'] ?? '',
            'indexer_indexnow_enabled' => isset($data['indexnow_enabled']) && $data['indexnow_enabled'] ? '1' : '0',
            'indexer_indexnow_key' => trim($data['indexnow_key'] ?? ''),
            'indexer_indexnow_host' => trim($data['indexnow_host'] ?? ''),
            'indexer_auto_topic' => isset($data['auto_topic']) && $data['auto_topic'] ? '1' : '0',
            'indexer_auto_video' => isset($data['auto_video']) && $data['auto_video'] ? '1' : '0',
            'indexer_auto_product' => isset($data['auto_product']) && $data['auto_product'] ? '1' : '0',
            'indexer_auto_news' => isset($data['auto_news']) && $data['auto_news'] ? '1' : '0',
            'indexer_auto_directory' => isset($data['auto_directory']) && $data['auto_directory'] ? '1' : '0',
            'indexer_auto_page' => isset($data['auto_page']) && $data['auto_page'] ? '1' : '0',
            'indexer_auto_delete' => isset($data['auto_delete']) && $data['auto_delete'] ? '1' : '0',
        ];

        foreach ($fields as $name => $valuer) {
            Option::updateOrCreate(
                ['name' => $name, 'o_type' => 'instant_indexer'],
                ['o_valuer' => $valuer]
            );
        }

        // Sync public key file if indexnow key changed
        if (!empty($fields['indexer_indexnow_key'])) {
            $this->indexNowService->syncPublicFile($fields['indexer_indexnow_key']);
        }

        return true;
    }
}
