<?php
require_once 'auth.php';
require_once 'database_pdo.php';
require_once 'aggregation_engine.php';
require_once 'system_logs.php';

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
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 pb-4 border-b border-gray-200">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Zaman Serisi Dashboard</h1>
            <p class="text-gray-600">Event'leri zaman boyutunda analiz edin ve görselleştirin.</p>
        </div>
        <!-- Live SSE Stream Toggle & Worker Badge -->
        <div class="mt-4 md:mt-0 flex flex-wrap items-center gap-3">
            <div id="worker-badge" class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-300 shadow-sm">
                <span class="w-2 h-2 mr-2 rounded-full bg-gray-400"></span>
                Worker: Yükleniyor...
            </div>
            <button type="button" id="sse-toggle" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-all duration-200 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                <span id="sse-indicator" class="w-2.5 h-2.5 mr-2 rounded-full bg-gray-400"></span>
                <span id="sse-text">Canlı Akış (SSE): Kapalı</span>
            </button>
        </div>
    </div>

    <!-- Aggregation Status Card -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Agregasyon & Akış Durumu</h2>
            <span id="live-update-time" class="text-xs text-gray-500">Son Güncelleme: Sayfa Yüklendi</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-sm font-medium text-blue-600">Toplam Kovalar</div>
                <div id="kpi-total-buckets" class="text-2xl font-bold text-blue-900"><?php echo number_format($aggregation_status['total_buckets']); ?></div>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm font-medium text-green-600">Agregasyon Events</div>
                <div id="kpi-total-events" class="text-2xl font-bold text-green-900"><?php echo number_format($aggregation_status['total_events_aggregated']); ?></div>
            </div>
            <div class="bg-purple-50 rounded-lg p-4">
                <div class="text-sm font-medium text-purple-600">Son Kova Zamanı</div>
                <div id="kpi-latest-bucket" class="text-sm font-semibold text-purple-900"><?php echo $aggregation_status['latest_bucket_end'] ? date('Y-m-d H:i', strtotime($aggregation_status['latest_bucket_end'])) : 'Yok'; ?></div>
            </div>
            <div class="bg-amber-50 rounded-lg p-4">
                <div class="text-sm font-medium text-amber-600">Canlı Olaylar (5dk)</div>
                <div id="kpi-recent-events" class="text-2xl font-bold text-amber-900">-</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Filtreler</h2>
        <form id="filter-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="time-range" class="block text-sm font-medium text-gray-700 mb-2">Zaman Aralığı</label>
                <select id="time-range" name="time_range" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="today">Bugün</option>
                    <option value="24h" selected>Son 24 Saat</option>
                    <option value="7d">Son 7 Gün</option>
                    <option value="custom">Özel Aralık</option>
                </select>
            </div>
            
            <div>
                <label for="bucket-size" class="block text-sm font-medium text-gray-700 mb-2">Bucket Boyutu</label>
                <select id="bucket-size" name="bucket_size" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1m" selected>1 Dakika</option>
                    <option value="5m">5 Dakika</option>
                    <option value="15m">15 Dakika</option>
                    <option value="1h">1 Saat</option>
                    <option value="1d">1 Gün</option>
                </select>
            </div>
            
            <div>
                <label for="event-type" class="block text-sm font-medium text-gray-700 mb-2">Event Türü</label>
                <select id="event-type" name="event_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tümü</option>
                    <?php foreach ($event_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['event_type']); ?>"><?php echo htmlspecialchars($type['event_type']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="source-id" class="block text-sm font-medium text-gray-700 mb-2">Kaynak</label>
                <select id="source-id" name="source_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tümü</option>
                    <?php foreach ($event_sources as $source): ?>
                        <option value="<?php echo htmlspecialchars($source['source_id']); ?>"><?php echo htmlspecialchars($source['source_id']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        
        <!-- Custom Date Range -->
        <div id="custom-date-range" class="hidden mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="start-date" class="block text-sm font-medium text-gray-700 mb-2">Başlangıç Tarihi</label>
                <input type="datetime-local" id="start-date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="end-date" class="block text-sm font-medium text-gray-700 mb-2">Bitiş Tarihi</label>
                <input type="datetime-local" id="end-date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        
        <div class="mt-4 flex space-x-4">
            <button type="button" id="apply-filters" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md font-medium transition duration-200">
                Filtreleri Uygula
            </button>
            <button type="button" id="refresh-data" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md font-medium transition duration-200">
                Yenile
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-500">Toplam Event</div>
                    <div id="total-events" class="text-2xl font-semibold text-gray-900">-</div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-500">En Yoğun Bucket</div>
                    <div id="peak-bucket" class="text-lg font-semibold text-gray-900">-</div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-sm font-medium text-gray-500">Ortalama / Bucket</div>
                    <div id="avg-per-bucket" class="text-2xl font-semibold text-gray-900">-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Zaman Serisi Grafiği</h2>
            <div class="flex space-x-2">
                <button id="export-csv" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm font-medium transition duration-200">
                    CSV Export
                </button>
                <button id="export-json" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium transition duration-200">
                    JSON Export
                </button>
            </div>
        </div>
        <div class="h-96">
            <canvas id="timeseries-chart"></canvas>
        </div>
    </div>
</div>

<script>
let chart = null;
let eventSource = null;
let isSseActive = false;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    updateChart();
    checkWorkerStatus();
    setInterval(checkWorkerStatus, 10000);
});

window.addEventListener('beforeunload', function() {
    if (eventSource) {
        eventSource.close();
    }
});

function setupEventListeners() {
    // Time range change
    document.getElementById('time-range').addEventListener('change', function() {
        const customRange = document.getElementById('custom-date-range');
        if (this.value === 'custom') {
            customRange.classList.remove('hidden');
        } else {
            customRange.classList.add('hidden');
        }
    });
    
    // Apply filters
    document.getElementById('apply-filters').addEventListener('click', updateChart);
    
    // Refresh data
    document.getElementById('refresh-data').addEventListener('click', updateChart);
    
    // Export buttons
    document.getElementById('export-csv').addEventListener('click', function() {
        exportData('csv');
    });
    
    document.getElementById('export-json').addEventListener('click', function() {
        exportData('json');
    });

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

    sseText.textContent = 'Canlı Akış: Bağlanıyor...';
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
                sseText.textContent = `🔴 Canlı Akış Aktif (${payload.bucket_size})`;

                document.getElementById('live-update-time').textContent = 'Son Canlı Veri: ' + new Date().toLocaleTimeString('tr-TR');

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
                    updateSummaryCards({ buckets: payload.buckets });
                    renderChart({ buckets: payload.buckets });
                }
            } catch (err) {
                console.error('SSE JSON parse error:', err);
            }
        });

        eventSource.onerror = function() {
            sseIndicator.className = 'w-2.5 h-2.5 mr-2 rounded-full bg-amber-500 animate-pulse';
            sseText.textContent = 'Canlı Akış: Yeniden Bağlanıyor...';
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
    sseText.textContent = 'Canlı Akış (SSE): Kapalı';
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
                badge.innerHTML = `<span class="w-2 h-2 mr-2 rounded-full bg-green-500 animate-pulse"></span>Worker: Aktif (${d.seconds_ago !== null ? d.seconds_ago + 's önce' : 'canlı'})`;
            } else {
                badge.className = 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-300 shadow-sm';
                badge.innerHTML = `<span class="w-2 h-2 mr-2 rounded-full bg-gray-400"></span>Worker: Çevrimdışı`;
            }
        }
    } catch (e) {
        // Silently ignore network hiccups for status poller
    }
}

async function updateChart() {
    try {
        const params = getFilterParams();
        params.metric_type = 'total_events';
        
        const data = await apiCall('timeseries/aggregate', params);
        
        updateSummaryCards(data);
        renderChart(data);
        document.getElementById('live-update-time').textContent = 'Son Güncelleme: ' + new Date().toLocaleTimeString('tr-TR');
        
    } catch (error) {
        console.error('Error updating chart:', error);
        alert('Veri yüklenirken hata oluştu: ' + error.message);
    }
}

function getFilterParams() {
    const timeRange = document.getElementById('time-range').value;
    const bucketSize = document.getElementById('bucket-size').value;
    const eventType = document.getElementById('event-type').value;
    const sourceId = document.getElementById('source-id').value;
    
    let startTime, endTime;
    
    switch (timeRange) {
        case 'today':
            startTime = new Date().toISOString().split('T')[0] + 'T00:00:00Z';
            endTime = new Date().toISOString();
            break;
        case '24h':
            startTime = new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString();
            endTime = new Date().toISOString();
            break;
        case '7d':
            startTime = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString();
            endTime = new Date().toISOString();
            break;
        case 'custom':
            startTime = document.getElementById('start-date').value;
            endTime = document.getElementById('end-date').value;
            if (!startTime || !endTime) {
                throw new Error('Lütfen özel tarih aralığı seçin');
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

    // Total events
    const totalEvents = data.buckets.reduce((sum, bucket) => sum + (parseInt(bucket.count) || 0), 0);
    document.getElementById('total-events').textContent = formatNumber(totalEvents);
    
    // Peak bucket
    const peakBucket = data.buckets.reduce((max, bucket) => 
        (parseInt(bucket.count) || 0) > (parseInt(max.count) || 0) ? bucket : max, {count: 0}
    );
    document.getElementById('peak-bucket').textContent = 
        peakBucket.count > 0 ? `${formatNumber(peakBucket.count)} (${formatDate(peakBucket.bucket_start)})` : '-';
    
    // Average per bucket
    const avgPerBucket = data.buckets.length > 0 ? totalEvents / data.buckets.length : 0;
    document.getElementById('avg-per-bucket').textContent = formatNumber(Math.round(avgPerBucket));
}

function renderChart(data) {
    const ctx = document.getElementById('timeseries-chart').getContext('2d');
    
    if (chart) {
        chart.destroy();
    }
    
    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: (data.buckets || []).map(bucket => new Date(bucket.bucket_start).toLocaleString('tr-TR')),
            datasets: [{
                label: 'Toplam Event Sayısı',
                data: (data.buckets || []).map(bucket => bucket.count),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.12)',
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 6,
                tension: 0.25,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: isSseActive ? false : { duration: 400 },
            plugins: {
                title: {
                    display: true,
                    text: isSseActive ? '⚡ Gerçek Zamanlı Canlı Kova Akışı' : 'Zaman Serisi Grafiği'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return 'Event Sayısı: ' + formatNumber(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Zaman'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Event Sayısı'
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
        alert('Export sırasında hata oluştu: ' + error.message);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
