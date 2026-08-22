<?php
require_once 'auth.php';
require_once 'i18n.php';
$current_page = 'api_guide';
include 'includes/header.php';
?>

<div class="px-4 py-6 sm:px-0">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo __('nav_api_guide'); ?></h1>
        <p class="text-gray-600"><?php echo __('app_subtitle'); ?> - REST API & Server-Sent Events</p>
    </div>

    <!-- Environment & Credentials -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 space-y-3">
            <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                <span>🌐</span> <?php echo __('settings_title'); ?>
            </h2>
            <div class="text-xs text-gray-700 space-y-1.5 font-mono">
                <div>Base URL: <span class="bg-gray-100 px-2 py-0.5 rounded text-blue-600 font-bold" id="cur-base">(detecting...)</span></div>
                <div>Header: <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-800">X-API-Key: &lt;API_KEY&gt;</span></div>
                <div>Query: <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-800">?api_key=&lt;API_KEY&gt;</span></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 space-y-3">
            <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                <span>🔑</span> <?php echo __('api_keys_title'); ?>
            </h2>
            <div class="text-xs text-gray-700 space-y-1.5 font-mono">
                <div class="flex items-center justify-between bg-gray-50 p-2 rounded border">
                    <span>temporal_grid_api_key_2024</span>
                    <span class="text-green-600 font-bold uppercase text-[10px]"><?php echo __('active'); ?></span>
                </div>
                <div class="flex items-center justify-between bg-gray-50 p-2 rounded border">
                    <span>demo_key_12345</span>
                    <span class="text-green-600 font-bold uppercase text-[10px]"><?php echo __('active'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Documentation & Postman Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <a href="/docs/aggregate_api_guide.md" target="_blank" class="block bg-white rounded-xl shadow-md p-5 hover:shadow-lg border border-gray-100 transition duration-200">
            <div class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
                <span>📖</span> API Markdown Docs
            </div>
            <div class="text-xs text-gray-500">Query parameters, JSON schemas, error codes & rate limiting details.</div>
        </a>
        <a href="/postman/temporal_memory_grid.postman_collection.json" download class="block bg-white rounded-xl shadow-md p-5 hover:shadow-lg border border-gray-100 transition duration-200">
            <div class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
                <span>🚀</span> Postman Collection
            </div>
            <div class="text-xs text-gray-500">Download ready-to-import Postman JSON collection for instant testing.</div>
        </a>
    </div>

    <!-- Quick Start API Links -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6 border border-gray-100 space-y-4">
        <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2">
            <span>⚡</span> Quick Test Links (Live Responses)
        </h2>
        <p class="text-xs text-gray-500">Click any link below to test live JSON/CSV endpoints for the last 5 minutes:</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3" id="quick-links"></div>
    </div>
</div>

<script>
function iso(dt){return new Date(dt).toISOString()}
function buildQuickLinks(){
    const now = new Date();
    const end = iso(now);
    const start = iso(new Date(now.getTime() - 15 * 60 * 1000));
    const apiKey = 'temporal_grid_api_key_2024';
    const base = window.location.origin;

    const items = [
        { label: '📊 Timeseries Aggregates (JSON, 1m)', url: `${base}/api/v1/timeseries.php?api_key=${apiKey}&metric_type=total_events&bucket_size=1m&start_time=${start}&end_time=${end}` },
        { label: '📥 Timeseries Export (CSV, 1m)', url: `${base}/api/v1/timeseries.php?api_key=${apiKey}&metric_type=total_events&bucket_size=1m&start_time=${start}&end_time=${end}&format=csv` },
        { label: '📈 Two-Period Trend Comparison', url: `${base}/api/v1/trend.php?api_key=${apiKey}&metric_type=total_events&primary_start_time=${start}&primary_end_time=${end}&compare_start_time=${iso(new Date(now.getTime()-30*60*1000))}&compare_end_time=${start}` },
        { label: '🚨 Anomaly Detector (Historical)', url: `${base}/api/v1/anomalies.php?api_key=${apiKey}&metric_type=total_events&bucket_size=1m&start_time=${start}&end_time=${end}&baseline=historical&deviation_threshold=50` },
        { label: '🔴 Real-time Live Stream (SSE)', url: `${base}/api/v1/stream.php?api_key=${apiKey}&bucket_size=1m` }
    ];

    const wrap = document.getElementById('quick-links');
    wrap.innerHTML = '';
    items.forEach(i => {
        const a = document.createElement('a');
        a.href = i.url;
        a.target = '_blank';
        a.className = 'block bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg p-3 text-xs font-semibold text-gray-800 hover:text-blue-700 transition duration-150 shadow-sm';
        a.textContent = i.label;
        wrap.appendChild(a);
    });

    document.getElementById('cur-base').textContent = window.location.origin;
}
document.addEventListener('DOMContentLoaded', buildQuickLinks);
</script>

<?php include 'includes/footer.php'; ?>
