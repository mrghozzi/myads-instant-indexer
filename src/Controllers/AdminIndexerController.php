<?php

namespace MyAds\Plugins\InstantIndexer\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use MyAds\Plugins\InstantIndexer\Models\IndexerLog;
use MyAds\Plugins\InstantIndexer\Services\InstantIndexerService;

class AdminIndexerController extends Controller
{
    protected InstantIndexerService $indexerService;

    public function __construct(?InstantIndexerService $indexerService = null)
    {
        $this->indexerService = $indexerService ?? instant_indexer_service();
    }

    /**
     * Main Dashboard view.
     */
    public function index(Request $request)
    {
        $stats = $this->indexerService->getStatistics();
        $config = $this->indexerService->getConfig();
        $candidates = $this->indexerService->getCandidateContent(20);

        // Logs query with filters
        $logsQuery = IndexerLog::query()->orderBy('id', 'desc');

        if ($request->filled('engine')) {
            $logsQuery->where('engine', $request->input('engine'));
        }

        if ($request->filled('status')) {
            $logsQuery->where('status', $request->input('status'));
        }

        if ($request->filled('source_type')) {
            $logsQuery->where('source_type', $request->input('source_type'));
        }

        if ($request->filled('q')) {
            $logsQuery->search($request->input('q'));
        }

        $logs = $logsQuery->paginate(15)->withQueryString();

        $googleConfigured = $this->indexerService->google()->isConfigured();
        $indexNowConfigured = $this->indexerService->indexNow()->isConfigured();

        return view('instant_indexer::index', [
            'stats' => $stats,
            'config' => $config,
            'logs' => $logs,
            'candidates' => $candidates,
            'googleConfigured' => $googleConfigured,
            'indexNowConfigured' => $indexNowConfigured,
            'keyLocation' => $this->indexerService->indexNow()->getKeyLocation(),
        ]);
    }

    /**
     * Save configuration.
     */
    public function saveSettings(Request $request)
    {
        $data = $request->all();

        // Handle uploaded JSON file if provided
        if ($request->hasFile('google_json_file')) {
            $file = $request->file('google_json_file');
            if ($file->isValid()) {
                $content = file_get_contents($file->getRealPath());
                if (!empty($content) && json_decode($content, true)) {
                    $data['google_json'] = $content;
                }
            }
        }

        $this->indexerService->saveConfig($data);

        return redirect()->back()->with('success', __('instant_indexer::messages.settings_saved_success'));
    }

    /**
     * Test Google Indexing API connection (AJAX).
     */
    public function testGoogle(Request $request): JsonResponse
    {
        $rawJson = $request->input('google_json');
        $result = $this->indexerService->google()->testConnection($rawJson);

        return response()->json($result);
    }

    /**
     * Test IndexNow API connection (AJAX).
     */
    public function testIndexNow(Request $request): JsonResponse
    {
        $key = $request->input('indexnow_key');
        $host = $request->input('indexnow_host');
        $result = $this->indexerService->indexNow()->testConnection($key, $host);

        return response()->json($result);
    }

    /**
     * Generate new IndexNow key (AJAX).
     */
    public function generateKey(): JsonResponse
    {
        $key = $this->indexerService->indexNow()->generateAndSaveKey();
        $keyLocation = $this->indexerService->indexNow()->getKeyLocation();

        return response()->json([
            'success' => true,
            'key' => $key,
            'key_location' => $keyLocation,
            'message' => __('instant_indexer::messages.key_generated_success'),
        ]);
    }

    /**
     * Submit single or multi-line URLs manually.
     */
    public function submitManual(Request $request)
    {
        $request->validate([
            'urls' => 'required|string',
            'action' => 'required|in:URL_UPDATED,URL_DELETED',
            'engines' => 'nullable|array',
        ]);

        $rawUrls = $request->input('urls');
        $action = $request->input('action', 'URL_UPDATED');
        $engines = $request->input('engines', ['google', 'indexnow']);

        if (empty($engines)) {
            $engines = ['google', 'indexnow'];
        }

        // Split by lines or commas
        $lines = preg_split('/[\r\n,]+/', $rawUrls);
        $cleanUrls = array_values(array_unique(array_filter(array_map('trim', $lines))));

        if (empty($cleanUrls)) {
            return redirect()->back()->with('error', __('instant_indexer::messages.no_valid_urls_found'));
        }

        if (count($cleanUrls) === 1) {
            $result = $this->indexerService->submitUrl(
                $cleanUrls[0],
                $action,
                'manual',
                null,
                $engines
            );

            if ($result['success']) {
                return redirect()->back()->with('success', __('instant_indexer::messages.url_submitted_success'));
            }

            return redirect()->back()->with('error', __('instant_indexer::messages.url_submitted_with_errors', ['msg' => $result['message']]));
        }

        $batchResult = $this->indexerService->submitBatch(
            $cleanUrls,
            $action,
            'batch',
            $engines
        );

        return redirect()->back()->with('success', __('instant_indexer::messages.batch_submitted_success', ['count' => count($cleanUrls)]));
    }

    /**
     * Submit Candidate items selected from system.
     */
    public function submitCandidate(Request $request)
    {
        $urls = (array) $request->input('selected_urls', []);
        $engines = (array) $request->input('engines', ['google', 'indexnow']);

        if (empty($urls)) {
            return redirect()->back()->with('error', __('instant_indexer::messages.no_candidate_selected'));
        }

        $this->indexerService->submitBatch($urls, 'URL_UPDATED', 'candidate_system', $engines);

        return redirect()->back()->with('success', __('instant_indexer::messages.batch_submitted_success', ['count' => count($urls)]));
    }

    /**
     * Retry a failed log.
     */
    public function retryLog(int $id)
    {
        $log = IndexerLog::findOrFail($id);

        $result = $this->indexerService->submitUrl(
            $log->url,
            $log->action,
            $log->source_type,
            $log->source_id,
            [$log->engine . '_forced']
        );

        if ($result['success']) {
            return redirect()->back()->with('success', __('instant_indexer::messages.retry_success'));
        }

        return redirect()->back()->with('error', __('instant_indexer::messages.retry_failed'));
    }

    /**
     * Clear indexing logs.
     */
    public function clearLogs(Request $request)
    {
        $days = (int) $request->input('days', 0);

        if ($days > 0) {
            IndexerLog::where('created_at', '<', now()->subDays($days))->delete();
        } else {
            IndexerLog::truncate();
        }

        return redirect()->back()->with('success', __('instant_indexer::messages.logs_cleared_success'));
    }

    /**
     * Export indexing logs to CSV.
     */
    public function exportLogs()
    {
        $logs = IndexerLog::orderBy('id', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="instant_indexer_logs_' . date('Y-m-d_H-i') . '.csv"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel Arabic support
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['ID', 'URL', 'Engine', 'Action', 'Status', 'HTTP Code', 'Source Type', 'Execution Time (ms)', 'Created At']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->url,
                    $log->engine,
                    $log->action,
                    $log->status,
                    $log->http_code,
                    $log->source_type,
                    $log->execution_time_ms,
                    $log->created_at ? $log->created_at->toDateTimeString() : '',
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
