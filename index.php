<?php
require_once 'auth.php';
require_once 'database_pdo.php';
require_once 'aggregation_engine.php';
require_once 'system_logs.php';
require_once 'i18n.php';

// Require login
\Temporal\Auth::getInstance()->requireLogin();

$current_page = 'dashboard';

// Get aggregation status
$aggregation_engine = \Temporal\AggregationEngine::getInstance();
$aggregation_status = $aggregation_engine->getAggregationStatus();

// Get available event types and sources for filters
$db = \Temporal\Database::getInstance();
$event_types = $db->query("SELECT DISTINCT event_type FROM events ORDER BY event_type");
$event_sources = $db->query("SELECT DISTINCT source_id FROM events ORDER BY source_id");

include 'includes/header.php';
?>

<div class="px-4 py-6 sm:px-0">
    <!-- Top Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo __('dashboard_title'); ?></h1>
            <p class="text-gray-600"><?php echo __('dashboard_subtitle'); ?></p>
        </div>
        <!-- Live SSE Stream Toggle & Worker Badge -->
        <div class="mt-4 md:mt-0 flex flex-wrap items-center gap-3">
            <div id="worker-badge" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-300 shadow-sm">
                <span class="w-2 h-2 mr-2 rounded-full bg-gray-400"></span>
                <?php echo __('worker_badge_loading'); ?>
            </div>
            <button type="button" id="sse-toggle" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all duration-200 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                <span id="sse-indicator" class="w-2.5 h-2.5 mr-2 rounded-full bg-gray-400"></span>
                <span id="sse-text"><?php echo __('live_stream_toggle'); ?></span>
            </button>
        </div>
    </div>

    <!-- Aggregation Status Card -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6 border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span>⚡</span> <?php echo __('agg_status_title'); ?>
            </h2>
            <span id="live-update-time" class="text-xs text-gray-500 font-mono"><?php echo __('last_update'); ?>: -</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-4">
                <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider"><?php echo __('total_buckets'); ?></div>
                <div id="kpi-total-buckets" class="text-2xl font-bold text-blue-900 mt-1"><?php echo number_format($aggregation_status['total_buckets']); ?></div>
            </div>
            <div class="bg-green-50/70 border border-green-100 rounded-xl p-4">
                <div class="text-xs font-semibold text-green-600 uppercase tracking-wider"><?php echo __('total_events_agg'); ?></div>
                <div id="kpi-total-events" class="text-2xl font-bold text-green-900 mt-1"><?php echo number_format($aggregation_status['total_events_aggregated']); ?></div>
            </div>
            <div class="bg-purple-50/70 border border-purple-100 rounded-xl p-4">
                <div class="text-xs font-semibold text-purple-600 uppercase tracking-wider"><?php echo __('latest_bucket_time'); ?></div>
                <div id="kpi-latest-bucket" class="text-sm font-semibold text-purple-900 mt-2 font-mono"><?php echo $aggregation_status['latest_bucket_end'] ? date('Y-m-d H:i', strtotime($aggregation_status['latest_bucket_end'])) : __('none'); ?></div>
            </div>
            <div class="bg-amber-50/70 border border-amber-100 rounded-xl p-4">
                <div class="text-xs font-semibold text-amber-600 uppercase tracking-wider"><?php echo __('live_5m_events'); ?></div>
                <div id="kpi-recent-events" class="text-2xl font-bold text-amber-900 mt-1">-</div>
            </div>
        </div>
    </div>

    <!-- Filters & Quick Pills Card -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6 border border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4 pb-3 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <span>🎯</span> <?php echo __('filters_title'); ?>
            </h2>

            <!-- Quick Time Range Pills -->
            <div class="flex flex-wrap items-center gap-1.5" id="quick-time-pills">
                <button type="button" data-range="1h" class="quick-pill px-3 py-1 text-xs font-semibold rounded-full border transition border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
                    <?php echo __('last_1h'); ?>
                </button>
                <button type="button" data-range="6h" class="quick-pill px-3 py-1 text-xs font-semibold rounded-full border transition border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
                    <?php echo __('last_6h'); ?>
                </button>
                <button type="button" data-range="24h" class="quick-pill active px-3 py-1 text-xs font-semibold rounded-full border transition border-blue-600 text-white bg-blue-600 shadow-sm">
                    <?php echo __('last_24h'); ?>
                </button>
                <button type="button" data-range="today" class="quick-pill px-3 py-1 text-xs font-semibold rounded-full border transition border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
                    <?php echo __('today'); ?>
                </button>
                <button type="button" data-range="7d" class="quick-pill px-3 py-1 text-xs font-semibold rounded-full border transition border-gray-300 text-gray-700 bg-white hover:bg-gray-50">
                    <?php echo __('last_7d'); ?>
                </button>
            </div>
        </div>

        <form id="filter-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="time-range" class="block text-xs font-medium text-gray-700 mb-1"><?php echo __('time_range'); ?></label>
                <select id="time-range" name="time_range" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1h"><?php echo __('last_1h'); ?></option>
                    <option value="6h"><?php echo __('last_6h'); ?></option>
                    <option value="24h" selected><?php echo __('last_24h'); ?></option>
                    <option value="today"><?php echo __('today'); ?></option>
                    <option value="7d"><?php echo __('last_7d'); ?></option>
                    <option value="custom"><?php echo __('custom_range'); ?></option>
                </select>
            </div>
            
            <div>
                <label for="bucket-size" class="block text-xs font-medium text-gray-700 mb-1"><?php echo __('bucket_size'); ?></label>
                <select id="bucket-size" name="bucket_size" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1m" selected>1 min (1m)</option>
                    <option value="5m">5 min (5m)</option>
                    <option value="15m">15 min (15m)</option>
                    <option value="1h">1 hour (1h)</option>
                    <option value="1d">1 day (1d)</option>
                </select>
            </div>
            
            <div>
                <label for="event-type" class="block text-xs font-medium text-gray-700 mb-1"><?php echo __('event_type'); ?></label>
                <select id="event-type" name="event_type" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value=""><?php echo __('all'); ?></option>
                    <?php foreach ($event_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['event_type']); ?>"><?php echo htmlspecialchars($type['event_type']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="source-id" class="block text-xs font-medium text-gray-700 mb-1"><?php echo __('source_id'); ?></label>
                <select id="source-id" name="source_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value=""><?php echo __('all'); ?></option>
                    <?php foreach ($event_sources as $source): ?>
                        <option value="<?php echo htmlspecialchars($source['source_id']); ?>"><?php echo htmlspecialchars($source['source_id']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        
        <!-- Custom Date Range -->
        <div id="custom-date-range" class="hidden mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="start-date" class="block text-xs font-medium text-gray-700 mb-1"><?php echo __('start_date'); ?></label>
                <input type="datetime-local" id="start-date" name="start_date" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="end-date" class="block text-xs font-medium text-gray-700 mb-1"><?php echo __('end_date'); ?></label>
                <input type="datetime-local" id="end-date" name="end_date" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button type="button" id="apply-filters" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition duration-200">
                <?php echo __('apply_filters'); ?>
            </button>
            <button type="button" id="refresh-data" class="bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300 px-4 py-2 rounded-lg text-sm font-semibold transition duration-200">
                🔄 <?php echo __('refresh'); ?>
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-md p-5 border border-gray-100">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center font-bold text-lg">
                    📊
                </div>
                <div class="ml-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase"><?php echo __('total_events'); ?></div>
                    <div id="total-events" class="text-2xl font-bold text-gray-900">-</div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-5 border border-gray-100">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-100 text-green-600 rounded-xl flex items-center justify-center font-bold text-lg">
                    ⚡
                </div>
                <div class="ml-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase"><?php echo __('peak_bucket'); ?></div>
                    <div id="peak-bucket" class="text-base font-bold text-gray-900">-</div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-5 border border-gray-100">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center font-bold text-lg">
                    📈
                </div>
                <div class="ml-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase"><?php echo __('avg_per_bucket'); ?></div>
                    <div id="avg-per-bucket" class="text-2xl font-bold text-gray-900">-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Chart Card with Type Switcher -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6 border border-gray-100">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 pb-3 border-b border-gray-100">
            <div>
                <h2 id="chart-main-title" class="text-lg font-semibold text-gray-900"><?php echo __('timeseries_chart'); ?></h2>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                <!-- Chart Type Switcher Buttons -->
                <div class="inline-flex rounded-lg border border-gray-300 p-0.5 bg-gray-50 shadow-sm" role="group" id="chart-type-group">
                    <button type="button" data-type="line" class="chart-type-btn px-2.5 py-1 text-xs font-semibold rounded-md transition bg-white text-blue-600 shadow-sm">
                        📈 <?php echo __('chart_type_line'); ?>
                    </button>
                    <button type="button" data-type="bar" class="chart-type-btn px-2.5 py-1 text-xs font-semibold rounded-md transition text-gray-600 hover:text-gray-900">
                        📊 <?php echo __('chart_type_bar'); ?>
                    </button>
                    <button type="button" data-type="area" class="chart-type-btn px-2.5 py-1 text-xs font-semibold rounded-md transition text-gray-600 hover:text-gray-900">
                        🌊 <?php echo __('chart_type_area'); ?>
                    </button>
                </div>

                <!-- Export Buttons -->
                <div class="flex items-center space-x-1.5 pl-2 border-l border-gray-200">
                    <button id="export-csv" class="bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 px-3 py-1 rounded-lg text-xs font-semibold transition">
                        📥 CSV
                    </button>
                    <button id="export-json" class="bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 px-3 py-1 rounded-lg text-xs font-semibold transition">
                        📄 JSON
                    </button>
                </div>
            </div>
        </div>
        <div class="h-96">
            <canvas id="timeseries-chart"></canvas>
        </div>
    </div>

    <!-- Geographic / Regional Grid Summary Card -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span>🗺️</span> <?php echo __('geo_summary_title'); ?>
                </h2>
                <p class="text-xs text-gray-500"><?php echo __('geo_summary_subtitle'); ?></p>
            </div>
            <div id="geo-grand-total" class="text-xs font-mono font-semibold text-gray-600 bg-gray-50 px-2.5 py-1 rounded-md border">
                Total: -
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-500 uppercase tracking-wider"><?php echo __('cell_coords'); ?></th>
                        <th class="px-4 py-2.5 text-left font-semibold text-gray-500 uppercase tracking-wider"><?php echo __('event_share'); ?></th>
                        <th class="px-4 py-2.5 text-right font-semibold text-gray-500 uppercase tracking-wider"><?php echo __('key_table_status'); ?></th>
                    </tr>
                </thead>
                <tbody id="geo-summary-tbody" class="bg-white divide-y divide-gray-100">
                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Loading geographic summary...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let chart = null;
let eventSource = null;
let isSseActive = false;
let currentChartType = 'line'; // 'line', 'bar', 'area'
let cachedBucketsData = null;

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    updateChart();
    loadGeoSummary();
    checkWorkerStatus();
    setInterval(checkWorkerStatus, 10000);
});

window.addEventListener('beforeunload', function() {
    if (eventSource) {
        eventSource.close();
    }
});

function setupEventListeners() {
    // Quick Range Pills
    document.querySelectorAll('.quick-pill').forEach(btn => {
        btn.addEventListener('click', function() {
            const range = this.dataset.range;
            document.getElementById('time-range').value = range;
            
            document.querySelectorAll('.quick-pill').forEach(p => {
                p.classList.remove('active', 'bg-blue-600', 'text-white', 'border-blue-600', 'shadow-sm');
                p.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
            });
            this.classList.add('active', 'bg-blue-600', 'text-white', 'border-blue-600', 'shadow-sm');
            this.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');

            const customRange = document.getElementById('custom-date-range');
            customRange.classList.add('hidden');

            updateChart();
            loadGeoSummary();
        });
    });

    // Time range dropdown change
    document.getElementById('time-range').addEventListener('change', function() {
        const customRange = document.getElementById('custom-date-range');
        if (this.value === 'custom') {
            customRange.classList.remove('hidden');
        } else {
            customRange.classList.add('hidden');
        }
        // Sync active pill
        document.querySelectorAll('.quick-pill').forEach(p => {
            if (p.dataset.range === this.value) {
                p.classList.add('active', 'bg-blue-600', 'text-white', 'border-blue-600', 'shadow-sm');
                p.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
            } else {
                p.classList.remove('active', 'bg-blue-600', 'text-white', 'border-blue-600', 'shadow-sm');
                p.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
            }
        });
    });

    // Chart Type Switcher Buttons
    document.querySelectorAll('.chart-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.dataset.type;
            if (type === currentChartType) return;
            currentChartType = type;

            document.querySelectorAll('.chart-type-btn').forEach(b => {
                b.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
                b.classList.add('text-gray-600');
            });
            this.classList.add('bg-white', 'text-blue-600', 'shadow-sm');
            this.classList.remove('text-gray-600');

            if (cachedBucketsData) {
                renderChart(cachedBucketsData);
            }
        });
    });
    
    // Apply filters
    document.getElementById('apply-filters').addEventListener('click', () => {
        updateChart();
        loadGeoSummary();
    });
    
    // Refresh data
    document.getElementById('refresh-data').addEventListener('click', () => {
        updateChart();
        loadGeoSummary();
    });
    
    // Export buttons
    document.getElementById('export-csv').addEventListener('click', () => exportData('csv'));
    document.getElementById('export-json').addEventListener('click', () => exportData('json'));

    // SSE Stream Toggle
    document.getElementById('sse-toggle').addEventListener('click', toggleSseStream);
}

function toggleSseStream() {
    if (isSseActive) {
        stopSseStream();
    } else {
        startSseStream();
    }
}

function startSseStream() {
    const bucketSize = document.getElementById('bucket-size').value || '1m';
    const sseToggle = document.getElementById('sse-toggle');
    const sseIndicator = document.getElementById('sse-indicator');
    const sseText = document.getElementById('sse-text');

    sseText.textContent = window.t('live_stream_connecting', 'Connecting...');
    sseIndicator.className = 'w-2.5 h-2.5 mr-2 rounded-full bg-amber-400 animate-pulse';

    const url = `/api/v1/stream.php?api_key=temporal_grid_api_key_2024&bucket_size=${encodeURIComponent(bucketSize)}`;
    
    try {
        eventSource = new EventSource(url);

        eventSource.addEventListener('update', function(e) {
            try {
                const payload = JSON.parse(e.data);
                isSseActive = true;
                sseToggle.className = 'inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all duration-200 bg-red-50 border border-red-300 text-red-700 hover:bg-red-100';
                sseIndicator.className = 'w-2.5 h-2.5 mr-2 rounded-full bg-red-500 animate-ping';
                sseText.textContent = `🔴 ${window.t('live_stream_active', 'Live Active (%s)').replace('%s', payload.bucket_size)}`;

                document.getElementById('live-update-time').textContent = window.t('last_live_data', 'Last Live Data') + ': ' + new Date().toLocaleTimeString(window.TMG_LOCALE || 'en-US');

                // Update KPIs
                if (payload.kpis) {
                    if (payload.kpis.total_buckets !== undefined) {
                        document.getElementById('kpi-total-buckets').textContent = formatNumber(payload.kpis.total_buckets);
                    }
                    if (payload.kpis.total_events_aggregated !== undefined) {
                        document.getElementById('kpi-total-events').textContent = formatNumber(payload.kpis.total_events_aggregated);
                    }
                    if (payload.kpis.latest_bucket_end) {
                        document.getElementById('kpi-latest-bucket').textContent = formatDate(payload.kpis.latest_bucket_end);
                    }
                    if (payload.kpis.recent_5m_events !== undefined) {
                        document.getElementById('kpi-recent-events').textContent = formatNumber(payload.kpis.recent_5m_events);
                    }
                }

                // Update Live Buckets on Chart
                if (payload.buckets && payload.buckets.length > 0) {
                    cachedBucketsData = { buckets: payload.buckets };
                    updateSummaryCards(cachedBucketsData);
                    renderChart(cachedBucketsData);
                }
            } catch (err) {
                console.error('SSE JSON parse error:', err);
            }
        });

        eventSource.onerror = function() {
            sseIndicator.className = 'w-2.5 h-2.5 mr-2 rounded-full bg-amber-500 animate-pulse';
            sseText.textContent = window.t('live_stream_connecting', 'Connecting...');
        };

    } catch (err) {
        console.error('SSE initialization error:', err);
        stopSseStream();
    }
}

function stopSseStream() {
    if (eventSource) {
        eventSource.close();
        eventSource = null;
    }
    isSseActive = false;
    const sseToggle = document.getElementById('sse-toggle');
    const sseIndicator = document.getElementById('sse-indicator');
    const sseText = document.getElementById('sse-text');

    sseToggle.className = 'inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all duration-200 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50';
    sseIndicator.className = 'w-2.5 h-2.5 mr-2 rounded-full bg-gray-400';
    sseText.textContent = window.t('live_stream_toggle', 'Live Stream (SSE): Off');
}

async function checkWorkerStatus() {
    try {
        const res = await fetch('/actions/worker_status.php');
        const json = await res.json();
        const badge = document.getElementById('worker-badge');
        if (json.success && json.data) {
            const d = json.data;
            if (d.is_live) {
                badge.className = 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-300 shadow-sm';
                badge.innerHTML = `<span class="w-2 h-2 mr-2 rounded-full bg-green-500 animate-pulse"></span>Worker: ${window.t('active', 'Active')} (${d.seconds_ago !== null ? d.seconds_ago + 's' : ''})`;
            } else {
                badge.className = 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-300 shadow-sm';
                badge.innerHTML = `<span class="w-2 h-2 mr-2 rounded-full bg-gray-400"></span>${window.t('worker_badge_offline', 'Worker: Offline')}`;
            }
        }
    } catch (e) {}
}

async function updateChart() {
    try {
        const params = getFilterParams();
        params.metric_type = 'total_events';
        
        const data = await apiCall('timeseries/aggregate', params);
        cachedBucketsData = data;
        
        updateSummaryCards(data);
        renderChart(data);
        document.getElementById('live-update-time').textContent = window.t('last_update', 'Last Update') + ': ' + new Date().toLocaleTimeString(window.TMG_LOCALE || 'en-US');
        
    } catch (error) {
        console.error('Error updating chart:', error);
    }
}

async function loadGeoSummary() {
    const tbody = document.getElementById('geo-summary-tbody');
    try {
        const params = getFilterParams();
        const url = new URL('/actions/get_geo_summary.php', window.location.origin);
        url.searchParams.append('start_time', params.start_time);
        url.searchParams.append('end_time', params.end_time);

        const res = await fetch(url);
        const json = await res.json();

        if (!json.success || !json.data || !json.data.regions || json.data.regions.length === 0) {
            tbody.innerHTML = `<tr><td colspan="3" class="px-4 py-6 text-center text-gray-400 font-sans">${window.t('no_geo_data', 'No geographical data found')}</td></tr>`;
            document.getElementById('geo-grand-total').textContent = 'Total: 0';
            return;
        }

        const d = json.data;
        document.getElementById('geo-grand-total').textContent = 'Total: ' + formatNumber(d.grand_total);

        tbody.innerHTML = d.regions.map(r => {
            const intensityLabel = r.intensity === 'high' ? window.t('intensity_high', 'High') : (r.intensity === 'medium' ? window.t('intensity_med', 'Medium') : window.t('intensity_low', 'Low'));
            const badgeClass = r.intensity === 'high' ? 'bg-red-100 text-red-800 border-red-200' : (r.intensity === 'medium' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-blue-100 text-blue-800 border-blue-200');
            const barColor = r.intensity === 'high' ? 'bg-red-500' : (r.intensity === 'medium' ? 'bg-amber-500' : 'bg-blue-500');

            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2.5 font-mono font-medium text-gray-900 flex items-center gap-1.5">
                        <span class="text-blue-500">📍</span>
                        <span>${escapeHtml(r.region)}</span>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-800 w-12">${formatNumber(r.total_events)}</span>
                            <div class="w-32 bg-gray-200 rounded-full h-2 overflow-hidden">
                                <div class="${barColor} h-2 rounded-full" style="width: ${r.percentage}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 font-semibold">${r.percentage}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-2.5 text-right">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border ${badgeClass}">
                            ${intensityLabel}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="3" class="px-4 py-4 text-center text-red-400">Error loading geo summary</td></tr>`;
    }
}

function getFilterParams() {
    const timeRange = document.getElementById('time-range').value;
    const bucketSize = document.getElementById('bucket-size').value;
    const eventType = document.getElementById('event-type').value;
    const sourceId = document.getElementById('source-id').value;
    
    let startTime, endTime;
    const now = Date.now();
    
    switch (timeRange) {
        case '1h':
            startTime = new Date(now - 1 * 60 * 60 * 1000).toISOString();
            endTime = new Date(now).toISOString();
            break;
        case '6h':
            startTime = new Date(now - 6 * 60 * 60 * 1000).toISOString();
            endTime = new Date(now).toISOString();
            break;
        case 'today':
            startTime = new Date().toISOString().split('T')[0] + 'T00:00:00Z';
            endTime = new Date().toISOString();
            break;
        case '24h':
            startTime = new Date(now - 24 * 60 * 60 * 1000).toISOString();
            endTime = new Date(now).toISOString();
            break;
        case '7d':
            startTime = new Date(now - 7 * 24 * 60 * 60 * 1000).toISOString();
            endTime = new Date(now).toISOString();
            break;
        case 'custom':
            startTime = document.getElementById('start-date').value;
            endTime = document.getElementById('end-date').value;
            if (!startTime || !endTime) {
                throw new Error('Please select date range');
            }
            break;
    }
    
    return {
        start_time: startTime,
        end_time: endTime,
        bucket_size: bucketSize,
        type: eventType || null,
        source_id: sourceId || null
    };
}

function updateSummaryCards(data) {
    if (!data.buckets || data.buckets.length === 0) {
        document.getElementById('total-events').textContent = '0';
        document.getElementById('peak-bucket').textContent = '-';
        document.getElementById('avg-per-bucket').textContent = '0';
        return;
    }

    const totalEvents = data.buckets.reduce((sum, bucket) => sum + (parseInt(bucket.count) || 0), 0);
    document.getElementById('total-events').textContent = formatNumber(totalEvents);
    
    const peakBucket = data.buckets.reduce((max, bucket) => 
        (parseInt(bucket.count) || 0) > (parseInt(max.count) || 0) ? bucket : max, {count: 0}
    );
    document.getElementById('peak-bucket').textContent = 
        peakBucket.count > 0 ? `${formatNumber(peakBucket.count)} (${formatDate(peakBucket.bucket_start)})` : '-';
    
    const avgPerBucket = data.buckets.length > 0 ? totalEvents / data.buckets.length : 0;
    document.getElementById('avg-per-bucket').textContent = formatNumber(Math.round(avgPerBucket));
}

function renderChart(data) {
    const ctx = document.getElementById('timeseries-chart').getContext('2d');
    
    if (chart) {
        chart.destroy();
    }

    const chartType = currentChartType === 'area' ? 'line' : currentChartType;
    const isArea = currentChartType === 'area';
    
    chart = new Chart(ctx, {
        type: chartType,
        data: {
            labels: (data.buckets || []).map(bucket => new Date(bucket.bucket_start).toLocaleString(window.TMG_LOCALE || 'en-US')),
            datasets: [{
                label: window.t('total_events', 'Total Events'),
                data: (data.buckets || []).map(bucket => bucket.count),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: isArea 
                    ? 'rgba(59, 130, 246, 0.35)' 
                    : (chartType === 'bar' ? 'rgba(59, 130, 246, 0.75)' : 'rgba(59, 130, 246, 0.12)'),
                borderWidth: chartType === 'bar' ? 1 : 2,
                borderRadius: chartType === 'bar' ? 4 : 0,
                pointRadius: chartType === 'bar' ? 0 : 3,
                pointHoverRadius: chartType === 'bar' ? 0 : 6,
                tension: 0.25,
                fill: isArea || chartType === 'line'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: isSseActive ? false : { duration: 350 },
            plugins: {
                title: {
                    display: true,
                    text: isSseActive ? window.t('live_chart_title', '⚡ Real-time Live Bucket Stream') : window.t('timeseries_chart', 'Time-Series Chart')
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return window.t('total_events', 'Events') + ': ' + formatNumber(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: window.t('time_range', 'Time')
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: window.t('total_events', 'Event Count')
                    },
                    beginAtZero: true
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function exportData(format) {
    try {
        const params = getFilterParams();
        params.metric_type = 'total_events';
        params.format = format;
        
        const url = new URL('/api/v1/timeseries.php', window.location.origin);
        url.searchParams.append('api_key', 'temporal_grid_api_key_2024');
        
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });
        
        window.open(url.toString(), '_blank');
        
    } catch (error) {
        console.error('Export error:', error);
        alert('Export error: ' + error.message);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
