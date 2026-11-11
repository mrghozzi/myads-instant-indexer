@extends('admin::layouts.admin')

@section('title', __('instant_indexer::messages.dashboard_title'))
@section('admin_shell_header_mode', 'hidden')

@php
    $stats = $stats ?? [
        'total_all' => 0,
        'total_today' => 0,
        'google' => ['total' => 0, 'success' => 0, 'error' => 0, 'rate' => 100],
        'indexnow' => ['total' => 0, 'success' => 0, 'error' => 0, 'rate' => 100],
        'total_errors' => 0,
    ];
    $config = $config ?? [];
    $logs = $logs ?? collect();
    $candidates = $candidates ?? [];
@endphp

@push('styles')
<style>
    .indexer-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 600;
    }
    .indexer-kpi-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        padding: 1.25rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .indexer-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    }
    .indexer-kpi-title {
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 0.4rem;
    }
    .indexer-kpi-val {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
    }
    .indexer-kpi-meta {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 0.35rem;
    }
    .indexer-engine-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 1.25rem;
        height: 100%;
    }
    .indexer-nav-tabs .nav-link {
        font-weight: 600;
        color: #64748b;
        padding: 0.75rem 1.25rem;
        border: none;
        border-bottom: 2px solid transparent;
    }
    .indexer-nav-tabs .nav-link.active {
        color: #0d6efd;
        background: transparent;
        border-bottom: 2px solid #0d6efd;
    }
    .indexer-code-box {
        background: #0f172a;
        color: #38bdf8;
        font-family: monospace;
        padding: 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        white-space: pre-wrap;
        word-break: break-all;
        max-height: 350px;
        overflow-y: auto;
    }
    .indexer-status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    .indexer-status-dot.active { background-color: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.6); }
    .indexer-status-dot.inactive { background-color: #94a3b8; }
</style>
@endpush

@section('content')
<div class="admin-page indexer-page pb-5">

    {{-- HERO SECTION --}}
    <section class="admin-hero mb-4">
        <div class="admin-hero__content">
            <ul class="admin-breadcrumb">
                <li><a href="{{ route('admin.index') }}">{{ __('messages.board') }}</a></li>
                <li>{{ __('messages.plugins') }}</li>
                <li>{{ __('instant_indexer::messages.dashboard_title') }}</li>
            </ul>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="indexer-hero-badge">
                    <i class="feather-zap text-warning"></i> {{ __('instant_indexer::messages.dashboard_eyebrow') }}
                </span>
                @if($googleConfigured)
                    <span class="badge bg-soft-success text-success"><i class="feather-check me-1"></i> Google Ready</span>
                @else
                    <span class="badge bg-soft-secondary text-muted">Google Not Configured</span>
                @endif
                @if($indexNowConfigured)
                    <span class="badge bg-soft-info text-info"><i class="feather-check me-1"></i> IndexNow Ready</span>
                @endif
            </div>
            <h1 class="admin-hero__title">{{ __('instant_indexer::messages.dashboard_title') }}</h1>
            <p class="admin-hero__copy">
                {{ __('instant_indexer::messages.dashboard_desc') }}
            </p>
        </div>
        <div class="admin-hero__actions">
            <div class="row g-3 w-100">
                <div class="col-6 col-md-3">
                    <div class="indexer-kpi-card">
                        <div class="indexer-kpi-title">{{ __('instant_indexer::messages.stat_total_submissions') }}</div>
                        <div class="indexer-kpi-val text-primary">{{ number_format($stats['total_all']) }}</div>
                        <div class="indexer-kpi-meta">{{ __('instant_indexer::messages.stat_today_submissions') }}: {{ number_format($stats['total_today']) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="indexer-kpi-card">
                        <div class="indexer-kpi-title">{{ __('instant_indexer::messages.stat_google_rate') }}</div>
                        <div class="indexer-kpi-val text-success">{{ $stats['google']['rate'] }}%</div>
                        <div class="indexer-kpi-meta">{{ $stats['google']['success'] }} / {{ $stats['google']['total'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="indexer-kpi-card">
                        <div class="indexer-kpi-title">{{ __('instant_indexer::messages.stat_indexnow_rate') }}</div>
                        <div class="indexer-kpi-val text-info">{{ $stats['indexnow']['rate'] }}%</div>
                        <div class="indexer-kpi-meta">{{ $stats['indexnow']['success'] }} / {{ $stats['indexnow']['total'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="indexer-kpi-card">
                        <div class="indexer-kpi-title">{{ __('instant_indexer::messages.stat_total_errors') }}</div>
                        <div class="indexer-kpi-val {{ $stats['total_errors'] > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($stats['total_errors']) }}</div>
                        <div class="indexer-kpi-meta">HTTP Errors / Quotas</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="feather-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="feather-alert-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- NAVIGATION TABS --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom-0 pb-0">
            <ul class="nav nav-tabs indexer-nav-tabs" id="indexerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-logs-btn" data-bs-toggle="tab" data-bs-target="#tab-logs" type="button" role="tab">
                        <i class="feather-activity me-1"></i> {{ __('instant_indexer::messages.tab_overview') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-submitter-btn" data-bs-toggle="tab" data-bs-target="#tab-submitter" type="button" role="tab">
                        <i class="feather-send me-1"></i> {{ __('instant_indexer::messages.tab_submitter') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-candidates-btn" data-bs-toggle="tab" data-bs-target="#tab-candidates" type="button" role="tab">
                        <i class="feather-layers me-1"></i> {{ __('instant_indexer::messages.tab_candidates') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-settings-btn" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button" role="tab">
                        <i class="feather-settings me-1"></i> {{ __('instant_indexer::messages.tab_settings') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-docs-btn" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab">
                        <i class="feather-book-open me-1"></i> {{ __('instant_indexer::messages.tab_docs') }}
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="indexerTabsContent">

                {{-- TAB 1: LOGS & OVERVIEW --}}
                <div class="tab-pane fade show active" id="tab-logs" role="tabpanel">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">{{ __('instant_indexer::messages.logs_title') }}</h5>
                            <p class="text-muted small mb-0">عرض الاستجابات اللحظية من Google و IndexNow وحالات الأرشفة بالتفصيل.</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.instant_indexer.export') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="feather-download me-1"></i> {{ __('instant_indexer::messages.btn_export_csv') }}
                            </a>
                            <form action="{{ route('admin.instant_indexer.clear_logs') }}" method="POST" onsubmit="return confirm('هل أنت متأكد من رغبتك في مسح كافة السجلات؟');" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="feather-trash-2 me-1"></i> {{ __('instant_indexer::messages.btn_clear_logs') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Search & Filters --}}
                    <form method="GET" action="{{ route('admin.instant_indexer.index') }}" class="row g-2 mb-4">
                        <div class="col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="feather-search"></i></span>
                                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('instant_indexer::messages.logs_search_placeholder') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="engine" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">{{ __('instant_indexer::messages.logs_filter_all') }}</option>
                                <option value="google" {{ request('engine') === 'google' ? 'selected' : '' }}>Google Indexing</option>
                                <option value="indexnow" {{ request('engine') === 'indexnow' ? 'selected' : '' }}>IndexNow</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">{{ __('instant_indexer::messages.logs_filter_status_all') }}</option>
                                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>ناجح (Success)</option>
                                <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>خطأ (Error)</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill">تطبيق الفلاتر</button>
                            @if(request()->anyFilled(['q', 'engine', 'status', 'source_type']))
                                <a href="{{ route('admin.instant_indexer.index') }}" class="btn btn-light btn-sm">إعادة ضبط</a>
                            @endif
                        </div>
                    </form>

                    {{-- Logs Table --}}
                    <div class="table-responsive border rounded-3">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>{{ __('instant_indexer::messages.col_url') }}</th>
                                    <th style="width: 140px;">{{ __('instant_indexer::messages.col_engine') }}</th>
                                    <th style="width: 110px;">{{ __('instant_indexer::messages.col_source') }}</th>
                                    <th style="width: 110px;">{{ __('instant_indexer::messages.col_status') }}</th>
                                    <th style="width: 90px;">{{ __('instant_indexer::messages.col_code') }}</th>
                                    <th style="width: 90px;">{{ __('instant_indexer::messages.col_duration') }}</th>
                                    <th style="width: 140px;">{{ __('instant_indexer::messages.col_date') }}</th>
                                    <th style="width: 120px;" class="text-center">{{ __('instant_indexer::messages.col_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td class="text-muted small">{{ $log->id }}</td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 380px;">
                                                <a href="{{ $log->url }}" target="_blank" class="text-decoration-none fw-medium">
                                                    {{ $log->url }}
                                                </a>
                                            </div>
                                            @if($log->action === 'URL_DELETED')
                                                <span class="badge bg-soft-danger text-danger small py-0">حذف URL_DELETED</span>
                                            @endif
                                        </td>
                                        <td>{!! $log->engine_badge !!}</td>
                                        <td><span class="badge bg-light text-dark">{{ $log->source_label }}</span></td>
                                        <td>{!! $log->status_badge !!}</td>
                                        <td>
                                            @if($log->http_code >= 200 && $log->http_code < 300)
                                                <span class="text-success fw-bold">{{ $log->http_code }}</span>
                                            @elseif($log->http_code)
                                                <span class="text-danger fw-bold">{{ $log->http_code }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted">{{ $log->execution_time_ms ? $log->execution_time_ms . ' ms' : '-' }}</td>
                                        <td class="small text-muted">{{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}</td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-info view-response-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#responseModal"
                                                        data-url="{{ $log->url }}"
                                                        data-engine="{{ $log->engine }}"
                                                        data-code="{{ $log->http_code }}"
                                                        data-status="{{ $log->status }}"
                                                        data-response="{{ $log->pretty_response }}">
                                                    <i class="feather-eye"></i>
                                                </button>
                                                @if($log->status === 'error')
                                                    <form action="{{ route('admin.instant_indexer.retry', $log->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-warning" title="{{ __('instant_indexer::messages.btn_retry') }}">
                                                            <i class="feather-refresh-cw"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="feather-inbox fs-1 d-block mb-2 text-muted"></i>
                                            لم يتم تسجيل أي عمليات أرشفة حتى الآن.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>

                {{-- TAB 2: INSTANT SUBMITTER --}}
                <div class="tab-pane fade" id="tab-submitter" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8">
                            <h5 class="fw-bold mb-1">{{ __('instant_indexer::messages.submit_urls_title') }}</h5>
                            <p class="text-muted small mb-4">{{ __('instant_indexer::messages.submit_urls_desc') }}</p>

                            <form action="{{ route('admin.instant_indexer.submit_manual') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-bold">{{ __('instant_indexer::messages.urls_input_label') }} <span class="text-danger">*</span></label>
                                    <textarea name="urls" rows="6" class="form-control font-monospace" placeholder="{{ __('instant_indexer::messages.urls_input_placeholder') }}" required></textarea>
                                    <div class="form-text">يمكنك إدخال رابط واحد أو حتى 100 رابط، كل رابط في سطر منفصل.</div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <label class="form-label fw-bold">{{ __('instant_indexer::messages.action_label') }}</label>
                                        <select name="action" class="form-select">
                                            <option value="URL_UPDATED">{{ __('instant_indexer::messages.action_updated') }}</option>
                                            <option value="URL_DELETED">{{ __('instant_indexer::messages.action_deleted') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">{{ __('instant_indexer::messages.engines_select_label') }}</label>
                                        <div class="d-flex flex-column gap-2 pt-1">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="engines[]" value="google" id="chkGoogleManual" checked>
                                                <label class="form-check-label" for="chkGoogleManual">{{ __('instant_indexer::messages.engine_google') }}</label>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="engines[]" value="indexnow" id="chkIndexNowManual" checked>
                                                <label class="form-check-label" for="chkIndexNowManual">{{ __('instant_indexer::messages.engine_indexnow') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary px-4 py-2">
                                    <i class="feather-zap me-1"></i> {{ __('instant_indexer::messages.btn_submit_now') }}
                                </button>
                            </form>
                        </div>

                        <div class="col-lg-4 mt-4 mt-lg-0">
                            <div class="card bg-light border-0 rounded-3 p-3">
                                <h6 class="fw-bold mb-3"><i class="feather-info text-primary me-1"></i> نصائح الأرشفة الفعالة:</h6>
                                <ul class="small text-muted ps-3 mb-0" style="line-height: 1.8;">
                                    <li>تأكد من أن الرابط يبدأ بـ <code>https://</code> ومتاح للزوار دون تسجيل دخول (Publicly Accessible).</li>
                                    <li>تمنحك <strong>Google Indexing API</strong> حصة تصل إلى 200 طلب يومياً لكل مشروع في Google Cloud.</li>
                                    <li>يقوم بروتوكول <strong>IndexNow</strong> بإشعار Bing و Yandex و Seznam فورياً دون حدود تذكر.</li>
                                    <li>عند حذف موضوع أو منتج، اختر <code>URL_DELETED</code> لحذف الرابط سريعاً من نتائج البحث.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TAB 3: CANDIDATE CONTENT PICKER --}}
                <div class="tab-pane fade" id="tab-candidates" role="tabpanel">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">{{ __('instant_indexer::messages.candidates_title') }}</h5>
                            <p class="text-muted small mb-0">{{ __('instant_indexer::messages.candidates_desc') }}</p>
                        </div>
                    </div>

                    <form action="{{ route('admin.instant_indexer.submit_candidate') }}" method="POST">
                        @csrf
                        <div class="d-flex align-items-center justify-content-between bg-light p-3 rounded-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllCandidates">
                                <label class="form-check-label fw-bold" for="selectAllCandidates">{{ __('instant_indexer::messages.select_all') }}</label>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex gap-3 small">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="engines[]" value="google" id="chkCandGoogle" checked>
                                        <label class="form-check-label" for="chkCandGoogle">Google</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="engines[]" value="indexnow" id="chkCandIndexNow" checked>
                                        <label class="form-check-label" for="chkCandIndexNow">IndexNow</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="feather-send me-1"></i> {{ __('instant_indexer::messages.btn_index_selected') }}
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;"></th>
                                        <th>عنوان المحتوى</th>
                                        <th style="width: 140px;">النوع</th>
                                        <th style="width: 120px;">حالة الأرشفة</th>
                                        <th style="width: 140px;">التاريخ</th>
                                        <th style="width: 80px;" class="text-center">معاينة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($candidates as $cand)
                                        <tr>
                                            <td>
                                                <input class="form-check-input candidate-chk" type="checkbox" name="selected_urls[]" value="{{ $cand['url'] }}">
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $cand['title'] }}</div>
                                                <div class="small text-muted font-monospace">{{ $cand['url'] }}</div>
                                            </td>
                                            <td><span class="badge bg-light text-dark">{{ $cand['type_label'] }}</span></td>
                                            <td>
                                                @if($cand['is_indexed'])
                                                    <span class="badge bg-soft-success text-success"><i class="feather-check me-1"></i> {{ __('instant_indexer::messages.status_indexed') }}</span>
                                                @else
                                                    <span class="badge bg-soft-warning text-warning"><i class="feather-clock me-1"></i> {{ __('instant_indexer::messages.status_not_indexed') }}</span>
                                                @endif
                                            </td>
                                            <td class="small text-muted">{{ $cand['date'] }}</td>
                                            <td class="text-center">
                                                <a href="{{ $cand['url'] }}" target="_blank" class="btn btn-light btn-sm text-muted">
                                                    <i class="feather-external-link"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">لا يوجد محتوى حديث متاح حالياً.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>

                {{-- TAB 4: SETTINGS & CONFIGURATION --}}
                <div class="tab-pane fade" id="tab-settings" role="tabpanel">
                    <form action="{{ route('admin.instant_indexer.settings') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-4">
                            {{-- Google Settings --}}
                            <div class="col-lg-6">
                                <div class="indexer-engine-card">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="feather-globe fs-4 text-primary"></i>
                                            <h6 class="fw-bold mb-0">{{ __('instant_indexer::messages.settings_google_title') }}</h6>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="google_enabled" value="1" id="googleEnabled" {{ $config['google_enabled'] ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3">{{ __('instant_indexer::messages.settings_google_desc') }}</p>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">{{ __('instant_indexer::messages.google_json_label') }}</label>
                                        <textarea name="google_json" id="googleJsonArea" rows="6" class="form-control font-monospace small" placeholder="{{ __('instant_indexer::messages.google_json_placeholder') }}">{{ $config['google_json'] }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">{{ __('instant_indexer::messages.google_json_file_label') }}</label>
                                        <input type="file" name="google_json_file" class="form-control form-control-sm" accept=".json">
                                    </div>

                                    <button type="button" id="btnTestGoogle" class="btn btn-outline-primary btn-sm w-100">
                                        <i class="feather-check-circle me-1"></i> {{ __('instant_indexer::messages.btn_test_google') }}
                                    </button>
                                    <div id="googleTestResult" class="mt-2 small" style="display: none;"></div>
                                </div>
                            </div>

                            {{-- IndexNow Settings --}}
                            <div class="col-lg-6">
                                <div class="indexer-engine-card">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="feather-zap fs-4 text-warning"></i>
                                            <h6 class="fw-bold mb-0">{{ __('instant_indexer::messages.settings_indexnow_title') }}</h6>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="indexnow_enabled" value="1" id="indexNowEnabled" {{ $config['indexnow_enabled'] ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3">{{ __('instant_indexer::messages.settings_indexnow_desc') }}</p>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">{{ __('instant_indexer::messages.indexnow_key_label') }}</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="indexnow_key" id="indexNowKey" value="{{ $config['indexnow_key'] }}" class="form-control font-monospace" required>
                                            <button type="button" id="btnGenerateKey" class="btn btn-secondary">
                                                <i class="feather-refresh-cw me-1"></i> {{ __('instant_indexer::messages.btn_generate_key') }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">{{ __('instant_indexer::messages.indexnow_host_label') }}</label>
                                        <input type="text" name="indexnow_host" id="indexNowHost" value="{{ $config['indexnow_host'] }}" class="form-control form-control-sm font-monospace" placeholder="e.g. yourdomain.com">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">{{ __('instant_indexer::messages.key_location_label') }}</label>
                                        <div class="p-2 bg-white border rounded font-monospace small text-muted text-break" id="keyLocationDisplay">
                                            {{ $keyLocation }}
                                        </div>
                                    </div>

                                    <button type="button" id="btnTestIndexNow" class="btn btn-outline-warning btn-sm w-100">
                                        <i class="feather-check-circle me-1"></i> {{ __('instant_indexer::messages.btn_test_indexnow') }}
                                    </button>
                                    <div id="indexNowTestResult" class="mt-2 small" style="display: none;"></div>
                                </div>
                            </div>

                            {{-- Automated Triggers --}}
                            <div class="col-12">
                                <div class="card border rounded-3 p-4">
                                    <h6 class="fw-bold mb-1">{{ __('instant_indexer::messages.settings_triggers_title') }}</h6>
                                    <p class="text-muted small mb-3">{{ __('instant_indexer::messages.settings_triggers_desc') }}</p>

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_topic" value="1" id="autoTopic" {{ $config['auto_topic'] ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="autoTopic">{{ __('instant_indexer::messages.trigger_topic') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_video" value="1" id="autoVideo" {{ $config['auto_video'] ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="autoVideo">{{ __('instant_indexer::messages.trigger_video') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_product" value="1" id="autoProduct" {{ $config['auto_product'] ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="autoProduct">{{ __('instant_indexer::messages.trigger_product') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_news" value="1" id="autoNews" {{ $config['auto_news'] ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="autoNews">{{ __('instant_indexer::messages.trigger_news') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_directory" value="1" id="autoDirectory" {{ $config['auto_directory'] ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="autoDirectory">{{ __('instant_indexer::messages.trigger_directory') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_page" value="1" id="autoPage" {{ $config['auto_page'] ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="autoPage">{{ __('instant_indexer::messages.trigger_page') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_group" value="1" id="autoGroup" {{ !empty($config['auto_group']) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-medium" for="autoGroup">{{ __('instant_indexer::messages.trigger_group') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <hr class="my-2">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="auto_delete" value="1" id="autoDelete" {{ $config['auto_delete'] ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-danger" for="autoDelete">{{ __('instant_indexer::messages.trigger_delete') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="feather-save me-1"></i> {{ __('instant_indexer::messages.btn_save_settings') }}
                            </button>
                        </div>
                    </form>
                </div>

                {{-- TAB 5: DOCS & SETUP GUIDE --}}
                <div class="tab-pane fade" id="tab-docs" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 border rounded-3 p-4">
                                <h5 class="fw-bold text-primary mb-3">
                                    <i class="feather-help-circle me-1"></i> دليل إعداد Google Indexing API
                                </h5>
                                <ol class="ps-3 small text-muted" style="line-height: 2;">
                                    <li>توجه إلى <strong>Google Cloud Console</strong> (<a href="https://console.cloud.google.com/" target="_blank">console.cloud.google.com</a>) وأنشئ مشروعاً جديداً.</li>
                                    <li>من قائمة <strong>APIs & Services</strong>، ابحث عن <strong>Web Search Indexing API</strong> وقم بتفعيلها (Enable).</li>
                                    <li>توجه إلى <strong>Service Accounts</strong> وأنشئ حساب خدمة جديد (Service Account) بدور <code>Owner</code>.</li>
                                    <li>من تبويب <strong>Keys</strong> في حساب الخدمة، اختر <code>Add Key &gt; Create new key &gt; JSON</code> وقم بتنزيل الملف.</li>
                                    <li>توجه إلى <strong>Google Search Console</strong> (<a href="https://search.google.com/search-console" target="_blank">search.google.com</a>) الخاص بموقعك، ومن قسم <strong>Settings &gt; Users and permissions</strong>، أضف البريد الإلكتروني لحساب الخدمة (مثل: <code>xxxx@xxx.iam.gserviceaccount.com</code>) وامنحه صلاحية <strong>Owner</strong>.</li>
                                    <li>انسخ محتوى ملف الـ JSON والصقه في تبويب <strong>الإعدادات</strong> في هذا الملحق ثم اضغط <strong>فحص اتصال Google</strong>.</li>
                                </ol>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card h-100 border rounded-3 p-4">
                                <h5 class="fw-bold text-warning mb-3">
                                    <i class="feather-zap me-1"></i> دليل إعداد بروتوكول IndexNow
                                </h5>
                                <ol class="ps-3 small text-muted" style="line-height: 2;">
                                    <li>يقوم النظام تلقائياً بتوليد مفتاح IndexNow وإنشاء ملف التحقق <code>/{key}.txt</code> داخل المجلد العام.</li>
                                    <li>يدعم البروتوكول محركات <strong>Microsoft Bing</strong> و <strong>Yandex</strong> و <strong>Seznam</strong> و <strong>Naver</strong> دفعة واحدة.</li>
                                    <li>عند إرسال أول دفعة روابط، سيقوم محرك البحث بزيارة رابط التحقق <code>{{ $keyLocation }}</code> للتحقق من ملكية النطاق.</li>
                                    <li>يمكنك التحقق من وصول الروابط مباشرة عبر لوحة <strong>Bing Webmaster Tools</strong> في قسم <strong>IndexNow</strong>.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

{{-- MODAL: VIEW RAW API RESPONSE --}}
<div class="modal fade" id="responseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">
                    <i class="feather-code text-primary me-2"></i> تفاصيل استجابة محرك البحث
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="text-muted small fw-bold">الرابط المرسل:</label>
                    <div class="font-monospace small p-2 bg-light rounded text-break" id="modalUrl">-</div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="text-muted small fw-bold">المحرك:</label>
                        <div id="modalEngine" class="fw-bold text-dark">-</div>
                    </div>
                    <div class="col-4">
                        <label class="text-muted small fw-bold">رمز الحالة HTTP:</label>
                        <div id="modalCode" class="fw-bold">-</div>
                    </div>
                    <div class="col-4">
                        <label class="text-muted small fw-bold">الحالة الإجمالية:</label>
                        <div id="modalStatus">-</div>
                    </div>
                </div>
                <div>
                    <label class="text-muted small fw-bold">نص الاستجابة الخام (JSON Payload):</label>
                    <div class="indexer-code-box" id="modalResponse"></div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Response Modal Handler
    const responseModal = document.getElementById('responseModal');
    if (responseModal) {
        responseModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('modalUrl').textContent = button.getAttribute('data-url');
            document.getElementById('modalEngine').textContent = button.getAttribute('data-engine');
            document.getElementById('modalCode').textContent = button.getAttribute('data-code') || 'N/A';
            document.getElementById('modalStatus').innerHTML = button.getAttribute('data-status') === 'success' 
                ? '<span class="badge bg-success">ناجح (Success)</span>' 
                : '<span class="badge bg-danger">خطأ (Error)</span>';
            document.getElementById('modalResponse').textContent = button.getAttribute('data-response') || 'لا توجد بيانات استجابة.';
        });
    }

    // 2. Select All Candidates
    const selectAllCandidates = document.getElementById('selectAllCandidates');
    if (selectAllCandidates) {
        selectAllCandidates.addEventListener('change', function () {
            document.querySelectorAll('.candidate-chk').forEach(chk => {
                chk.checked = selectAllCandidates.checked;
            });
        });
    }

    // 3. Test Google Connection AJAX
    const btnTestGoogle = document.getElementById('btnTestGoogle');
    const googleTestResult = document.getElementById('googleTestResult');
    if (btnTestGoogle) {
        btnTestGoogle.addEventListener('click', function () {
            const jsonVal = document.getElementById('googleJsonArea').value;
            btnTestGoogle.disabled = true;
            btnTestGoogle.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري الفحص...';
            googleTestResult.style.display = 'none';

            fetch("{{ route('admin.instant_indexer.test_google') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ google_json: jsonVal })
            })
            .then(res => res.json())
            .then(data => {
                btnTestGoogle.disabled = false;
                btnTestGoogle.innerHTML = '<i class="feather-check-circle me-1"></i> {{ __("instant_indexer::messages.btn_test_google") }}';
                googleTestResult.style.display = 'block';
                if (data.success) {
                    googleTestResult.innerHTML = `<div class="alert alert-success py-2 mb-0"><i class="feather-check me-1"></i> ${data.message} (${data.time_ms} ms)</div>`;
                } else {
                    googleTestResult.innerHTML = `<div class="alert alert-danger py-2 mb-0"><i class="feather-alert-triangle me-1"></i> ${data.message}</div>`;
                }
            })
            .catch(err => {
                btnTestGoogle.disabled = false;
                btnTestGoogle.innerHTML = '<i class="feather-check-circle me-1"></i> {{ __("instant_indexer::messages.btn_test_google") }}';
                googleTestResult.style.display = 'block';
                googleTestResult.innerHTML = `<div class="alert alert-danger py-2 mb-0">خطأ في الاتصال بالخادم.</div>`;
            });
        });
    }

    // 4. Test IndexNow Connection AJAX
    const btnTestIndexNow = document.getElementById('btnTestIndexNow');
    const indexNowTestResult = document.getElementById('indexNowTestResult');
    if (btnTestIndexNow) {
        btnTestIndexNow.addEventListener('click', function () {
            const keyVal = document.getElementById('indexNowKey').value;
            const hostVal = document.getElementById('indexNowHost').value;
            btnTestIndexNow.disabled = true;
            btnTestIndexNow.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري الفحص...';
            indexNowTestResult.style.display = 'none';

            fetch("{{ route('admin.instant_indexer.test_indexnow') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ indexnow_key: keyVal, indexnow_host: hostVal })
            })
            .then(res => res.json())
            .then(data => {
                btnTestIndexNow.disabled = false;
                btnTestIndexNow.innerHTML = '<i class="feather-check-circle me-1"></i> {{ __("instant_indexer::messages.btn_test_indexnow") }}';
                indexNowTestResult.style.display = 'block';
                if (data.success) {
                    indexNowTestResult.innerHTML = `<div class="alert alert-success py-2 mb-0"><i class="feather-check me-1"></i> ${data.message} (${data.time_ms} ms)</div>`;
                } else {
                    indexNowTestResult.innerHTML = `<div class="alert alert-danger py-2 mb-0"><i class="feather-alert-triangle me-1"></i> ${data.message}</div>`;
                }
            })
            .catch(err => {
                btnTestIndexNow.disabled = false;
                btnTestIndexNow.innerHTML = '<i class="feather-check-circle me-1"></i> {{ __("instant_indexer::messages.btn_test_indexnow") }}';
                indexNowTestResult.style.display = 'block';
                indexNowTestResult.innerHTML = `<div class="alert alert-danger py-2 mb-0">خطأ في الاتصال بالخادم.</div>`;
            });
        });
    }

    // 5. Generate IndexNow Key AJAX
    const btnGenerateKey = document.getElementById('btnGenerateKey');
    if (btnGenerateKey) {
        btnGenerateKey.addEventListener('click', function () {
            btnGenerateKey.disabled = true;
            fetch("{{ route('admin.instant_indexer.generate_key') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                btnGenerateKey.disabled = false;
                if (data.success) {
                    document.getElementById('indexNowKey').value = data.key;
                    document.getElementById('keyLocationDisplay').textContent = data.key_location;
                }
            })
            .catch(() => { btnGenerateKey.disabled = false; });
        });
    }
});
</script>
@endpush
