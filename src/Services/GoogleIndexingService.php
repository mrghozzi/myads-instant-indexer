<?php

namespace MyAds\Plugins\InstantIndexer\Services;

use App\Models\Option;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleIndexingService
{
    protected const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    protected const INDEXING_ENDPOINT = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
    protected const METADATA_ENDPOINT = 'https://indexing.googleapis.com/v3/urlNotifications/metadata';
    protected const CACHE_KEY_TOKEN = 'myads_indexer_google_oauth_token';

    /**
     * Get the stored Google Service Account JSON.
     */
    public function getCredentials(): ?array
    {
        $raw = Option::where('name', 'indexer_google_json')
            ->where('o_type', 'instant_indexer')
            ->value('o_valuer');

        if (empty($raw)) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Check if Google Indexing API is configured and enabled.
     */
    public function isConfigured(): bool
    {
        $enabled = (int) (Option::where('name', 'indexer_google_enabled')->where('o_type', 'instant_indexer')->value('o_valuer') ?? 0);
        $credentials = $this->getCredentials();

        return $enabled === 1 && !empty($credentials['client_email']) && !empty($credentials['private_key']);
    }

    /**
     * Test connection and authentication with Google Indexing API.
     */
    public function testConnection(?string $rawJson = null): array
    {
        $startTime = microtime(true);

        try {
            $credentials = null;
            if (!empty($rawJson)) {
                $credentials = json_decode($rawJson, true);
            } else {
                $credentials = $this->getCredentials();
            }

            if (!$credentials || empty($credentials['client_email']) || empty($credentials['private_key'])) {
                return [
                    'success' => false,
                    'http_code' => 400,
                    'message' => 'ملف بيانات حساب الخدمة (Service Account JSON) غير صالح أو ينقصه البريد الإلكتروني والمفتاح الخاص.',
                    'response' => null,
                    'time_ms' => 0,
                ];
            }

            // Generate Token
            $token = $this->generateAccessToken($credentials, true); // Force fresh token for test
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$token) {
                return [
                    'success' => false,
                    'http_code' => 401,
                    'message' => 'فشل استخراج رمز الوصول (OAuth2 Token). تأكد من صحة المفتاح الخاص وتفعيل Google Indexing API في Google Cloud Console.',
                    'response' => null,
                    'time_ms' => $durationMs,
                ];
            }

            return [
                'success' => true,
                'http_code' => 200,
                'message' => 'تم الاتصال وتوليد رمز المصادقة بنجاح مع Google Indexing API!',
                'email' => $credentials['client_email'] ?? '',
                'project_id' => $credentials['project_id'] ?? '',
                'time_ms' => $durationMs,
            ];
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            return [
                'success' => false,
                'http_code' => 500,
                'message' => 'خطأ أثناء اختبار الاتصال: ' . $e->getMessage(),
                'response' => null,
                'time_ms' => $durationMs,
            ];
        }
    }

    /**
     * Submit a URL to Google Indexing API.
     * 
     * @param string $url The fully qualified URL to index
     * @param string $action 'URL_UPDATED' or 'URL_DELETED'
     * @return array
     */
    public function publishUrl(string $url, string $action = 'URL_UPDATED'): array
    {
        $startTime = microtime(true);
        $action = in_array(strtoupper($action), ['URL_UPDATED', 'URL_DELETED'], true) ? strtoupper($action) : 'URL_UPDATED';

        $credentials = $this->getCredentials();
        if (!$credentials || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            return [
                'success' => false,
                'http_code' => 400,
                'message' => 'لم يتم إعداد بيانات Google Service Account بشكل صحيح.',
                'response' => null,
                'time_ms' => 0,
            ];
        }

        try {
            $token = $this->getAccessToken($credentials);
            if (!$token) {
                return [
                    'success' => false,
                    'http_code' => 401,
                    'message' => 'فشل الحصول على رمز OAuth2 من Google.',
                    'response' => null,
                    'time_ms' => (int) round((microtime(true) - $startTime) * 1000),
                ];
            }

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->connectTimeout(5)
                ->timeout(10)
                ->post(self::INDEXING_ENDPOINT, [
                    'url' => $url,
                    'type' => $action,
                ]);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();
            $body = $response->json() ?? $response->body();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'http_code' => $statusCode,
                    'message' => 'تم إرسال الرابط إلى Google Indexing API بنجاح.',
                    'response' => $body,
                    'time_ms' => $durationMs,
                ];
            }

            $errorMsg = is_array($body) && isset($body['error']['message']) 
                ? $body['error']['message'] 
                : 'استجابة غير متوقعة من Google (HTTP ' . $statusCode . ')';

            return [
                'success' => false,
                'http_code' => $statusCode,
                'message' => $errorMsg,
                'response' => $body,
                'time_ms' => $durationMs,
            ];
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('Google Indexing API Exception: ' . $e->getMessage(), ['url' => $url]);

            return [
                'success' => false,
                'http_code' => 500,
                'message' => 'استثناء في الاتصال: ' . $e->getMessage(),
                'response' => null,
                'time_ms' => $durationMs,
            ];
        }
    }

    /**
     * Get URL notification metadata from Google.
     */
    public function getUrlMetadata(string $url): array
    {
        $startTime = microtime(true);
        $credentials = $this->getCredentials();

        if (!$credentials) {
            return ['success' => false, 'message' => 'بيانات الاعتماد غير متوفرة.'];
        }

        try {
            $token = $this->getAccessToken($credentials);
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->connectTimeout(5)
                ->timeout(10)
                ->get(self::METADATA_ENDPOINT, ['url' => $url]);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            return [
                'success' => $response->successful(),
                'http_code' => $response->status(),
                'response' => $response->json() ?? $response->body(),
                'time_ms' => $durationMs,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'http_code' => 500,
                'message' => $e->getMessage(),
                'time_ms' => (int) round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Get OAuth2 Access Token (cached for 55 minutes).
     */
    protected function getAccessToken(array $credentials): ?string
    {
        $cacheKey = self::CACHE_KEY_TOKEN . '_' . md5($credentials['client_email'] ?? 'google');

        return Cache::remember($cacheKey, 3300, function () use ($credentials) {
            return $this->generateAccessToken($credentials);
        });
    }

    /**
     * Generate and exchange JWT assertion for Google OAuth2 Bearer Token.
     */
    protected function generateAccessToken(array $credentials, bool $ignoreCache = false): ?string
    {
        $clientEmail = $credentials['client_email'] ?? '';
        $privateKey = $credentials['private_key'] ?? '';

        if (empty($clientEmail) || empty($privateKey)) {
            return null;
        }

        // Format Private Key correctly if needed
        $privateKey = str_replace(['\n', "\r\n"], "\n", $privateKey);

        $now = time();
        $jwtHeader = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $jwtClaim = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud' => self::TOKEN_ENDPOINT,
            'exp' => $now + 3600,
            'iat' => $now,
        ]));

        $signatureInput = $jwtHeader . '.' . $jwtClaim;
        $signature = '';

        $keyResource = openssl_pkey_get_private($privateKey);
        if (!$keyResource) {
            Log::error('Google Indexing: Failed to parse OpenSSL private key.');
            return null;
        }

        $signed = openssl_sign($signatureInput, $signature, $keyResource, OPENSSL_ALGO_SHA256);
        if (!$signed) {
            Log::error('Google Indexing: OpenSSL sign failed.');
            return null;
        }

        $jwt = $signatureInput . '.' . $this->base64UrlEncode($signature);

        $response = Http::withoutVerifying()
            ->asForm()
            ->connectTimeout(5)
            ->timeout(10)
            ->post(self::TOKEN_ENDPOINT, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['access_token'] ?? null;
        }

        Log::error('Google OAuth token request failed: ' . $response->body());
        return null;
    }

    /**
     * Helper for Base64 URL Encoding.
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
