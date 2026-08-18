<?php

namespace MyAds\Plugins\InstantIndexer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class IndexerLog extends Model
{
    protected $table = 'instant_indexer_logs';

    protected $fillable = [
        'url',
        'engine',
        'action',
        'status',
        'http_code',
        'response_body',
        'source_type',
        'source_id',
        'error_message',
        'execution_time_ms',
    ];

    protected $casts = [
        'http_code' => 'integer',
        'source_id' => 'integer',
        'execution_time_ms' => 'integer',
    ];

    public function scopeGoogle(Builder $query): Builder
    {
        return $query->where('engine', 'google');
    }

    public function scopeIndexNow(Builder $query): Builder
    {
        return $query->whereIn('engine', ['indexnow', 'bing', 'yandex']);
    }

    public function scopeSuccess(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'error');
    }

    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->orderBy('id', 'desc')->limit($limit);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('url', 'like', "%{$term}%")
              ->orWhere('engine', 'like', "%{$term}%")
              ->orWhere('source_type', 'like', "%{$term}%")
              ->orWhere('error_message', 'like', "%{$term}%")
              ->orWhere('http_code', 'like', "%{$term}%");
        });
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'success' => '<span class="badge bg-soft-success text-success"><i class="feather-check-circle me-1"></i> ناجح</span>',
            'error' => '<span class="badge bg-soft-danger text-danger"><i class="feather-alert-circle me-1"></i> خطأ</span>',
            default => '<span class="badge bg-soft-warning text-warning"><i class="feather-clock me-1"></i> قيد المعالجة</span>',
        };
    }

    public function getEngineBadgeAttribute(): string
    {
        return match (strtolower($this->engine)) {
            'google' => '<span class="badge bg-soft-primary text-primary"><i class="feather-globe me-1"></i> Google Indexing</span>',
            'indexnow' => '<span class="badge bg-soft-info text-info"><i class="feather-zap me-1"></i> IndexNow</span>',
            'bing' => '<span class="badge bg-soft-info text-info"><i class="feather-search me-1"></i> Bing</span>',
            'yandex' => '<span class="badge bg-soft-danger text-danger"><i class="feather-compass me-1"></i> Yandex</span>',
            default => '<span class="badge bg-soft-secondary text-secondary">' . e($this->engine) . '</span>',
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_type) {
            'forum_topic' => 'موضوع منتدى',
            'status_video' => 'فيديو / شورتس',
            'product' => 'منتج متجر',
            'news' => 'مقال / خبر',
            'directory' => 'دليل مواقع',
            'page' => 'صفحة مخصصة',
            'manual' => 'إرسال يدوي',
            'batch' => 'إرسال دفعي',
            default => $this->source_type ?? 'عام',
        };
    }

    public function getPrettyResponseAttribute(): string
    {
        if (empty($this->response_body)) {
            return $this->error_message ?? 'لا توجد استجابة مسجلة';
        }

        $decoded = json_decode($this->response_body, true);
        if ($decoded !== null) {
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->response_body;
    }
}
