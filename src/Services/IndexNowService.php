<?php

namespace MyAds\Plugins\InstantIndexer\Services;

use App\Models\Option;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IndexNowService
{
    public const ENDPOINT_INDEXNOW = 'https://api.indexnow.org/indexnow';
    public const ENDPOINT_BING = 'https://www.bing.com/indexnow';
    public const ENDPOINT_YANDEX = 'https://yandex.com/indexnow';

    /**
     * Get the configured IndexNow API Key.
     */
    public function getKey(): string
    {
        $key = Option::where('name', 'indexer_indexnow_key')
            ->where('o_type', 'instant_indexer')
            ->value('o_valuer');

        if (empty($key)) {
            $key = $this->generateAndSaveKey();
        }

        return $key;
    }

    /**
     * Get the configured host.
     */
    public function getHost(): string
    {
        $customHost = Option::where('name', 'indexer_indexnow_host')
            ->where('o_type', 'instant_indexer')
            ->value('o_valuer');

        if (!empty($customHost)) {
            return trim($customHost);
        }

        try {
            $host = request()->getHost();
            if (!empty($host)) {
                return $host;
            }
        } catch (\Throwable $e) {
            // Ignore
        }

        $appUrl = config('app.url');
        $parsed = parse_url($appUrl, PHP_URL_HOST);

        return $parsed ?: 'localhost';
    }

    /**
     * Get the Key Location URL.
     */
    public function getKeyLocation(): string
    {
        $key = $this->getKey();
        $host = $this->getHost();
        $scheme = request()->isSecure() ? 'https://' : 'http://';

        return $scheme . $host . '/' . $key . '.txt';
    }

    /**
     * Check if IndexNow is enabled.
     */
    public function isConfigured(): bool
    {
        $enabled = (int) (Option::where('name', 'indexer_indexnow_enabled')->where('o_type', 'instant_indexer')->value('o_valuer') ?? 0);
        $key = $this->getKey();

        return $enabled === 1 && !empty($key);
    }

    /**
     * Generate a new IndexNow key and save it to options & public file.
     */
    public function generateAndSaveKey(): string
    {
        $newKey = bin2hex(random_bytes(16)); // 32 chars hex

        Option::updateOrCreate(
            ['name' => 'indexer_indexnow_key', 'o_type' => 'instant_indexer'],
            ['o_valuer' => $newKey]
        );

        $this->syncPublicFile($newKey);

        return $newKey;
    }

    /**
     * Sync key to public/{key}.txt file.
     */
    public function syncPublicFile(?string $key = null): bool
    {
        $key = $key ?: $this->getKey();
        if (empty($key)) {
            return false;
        }

        try {
            $filePath = public_path($key . '.txt');
            File::put($filePath, $key);
            return true;
        } catch (\Throwable $e) {
            Log::warning('IndexNow: Could not write key file to public folder: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test connection and submission with IndexNow.
     */
    public function testConnection(?string $key = null, ?string $host = null): array
    {
        $startTime = microtime(true);
        $key = $key ?: $this->getKey();
        $host = $host ?: $this->getHost();

        $testUrl = url('/');

        $result = $this->publishUrl($testUrl, $key, $host);
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        if ($result['success']) {
            return [
                'success' => true,
                'http_code' => $result['http_code'],
                'message' => 'تم اختبار بروتوكول IndexNow بنجاح! تم استلام الرابط من محركات البحث.',
                'key' => $key,
                'key_location' => $this->getKeyLocation(),
                'time_ms' => $durationMs,
            ];
        }

        return [
            'success' => false,
            'http_code' => $result['http_code'],
            'message' => 'فشل اختبار IndexNow: ' . $result['message'],
            'key' => $key,
            'key_location' => $this->getKeyLocation(),
            'time_ms' => $durationMs,
        ];
    }

    /**
     * Submit a single URL to IndexNow.
     */
    public function publishUrl(string $url, ?string $key = null, ?string $host = null): array
    {
        return $this->publishBatch([$url], $key, $host);
    }

    /**
     * Submit a batch of URLs to IndexNow.
     */
    public function publishBatch(array $urls, ?string $key = null, ?string $host = null): array
    {
        $startTime = microtime(true);
        $key = $key ?: $this->getKey();
        $host = $host ?: $this->getHost();
        $keyLocation = $this->getKeyLocation();

        $urls = array_values(array_unique(array_filter($urls)));

        if (empty($urls)) {
            return [
                'success' => false,
                'http_code' => 400,
                'message' => 'قائمة الروابط فارغة.',
                'response' => null,
                'time_ms' => 0,
            ];
        }

        $payload = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => $keyLocation,
            'urlList' => $urls,
        ];

        try {
            // Send to official IndexNow hub
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8',
                ])
                ->connectTimeout(5)
                ->timeout(10)
                ->post(self::ENDPOINT_INDEXNOW, $payload);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();
            $body = $response->json() ?? $response->body();

            // IndexNow returns 200 OK or 202 Accepted for success
            if (in_array($statusCode, [200, 202], true)) {
                return [
                    'success' => true,
                    'http_code' => $statusCode,
                    'message' => $statusCode === 202 
                        ? 'تم قبول الروابط من IndexNow ومفتاح التحقق قيد المراجعة.' 
                        : 'تم إرسال وقبول الروابط بنجاح في IndexNow.',
                    'response' => $body ?: ['status' => 'accepted', 'code' => $statusCode],
                    'time_ms' => $durationMs,
                ];
            }

            $errorMsg = match ($statusCode) {
                400 => 'طلب غير صالح (400 Bad Request): تحقق من صياغة الروابط أو صيغة JSON.',
                403 => 'المفتاح غير صالح (403 Forbidden): لم يتمكن محرك البحث من العثور على ملف المفتاح في ' . $keyLocation,
                422 => 'النطاق غير مطابق (422 Unprocessable Entity): الروابط لا تنتمي للمضيف ' . $host,
                429 => 'تجاوز حد الطلبات (429 Too Many Requests): يرجى الانتظار قليلاً قبل المحاولة مجدداً.',
                default => 'استجابة برمز خطأ من IndexNow (HTTP ' . $statusCode . ')',
            };

            return [
                'success' => false,
                'http_code' => $statusCode,
                'message' => $errorMsg,
                'response' => $body,
                'time_ms' => $durationMs,
            ];
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('IndexNow Exception: ' . $e->getMessage(), ['urls' => $urls]);

            return [
                'success' => false,
                'http_code' => 500,
                'message' => 'استثناء في الاتصال: ' . $e->getMessage(),
                'response' => null,
                'time_ms' => $durationMs,
            ];
        }
    }
}
