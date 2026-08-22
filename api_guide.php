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

    <!-- Environment & Credentials Cards -->
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

    <!-- Interactive API Test Console (Swagger / Postman Style) -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden mb-8">
        <div class="bg-gray-900 text-white p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 bg-blue-500 text-white text-xs font-bold rounded">TRY-IT-OUT</span>
                    <h2 class="text-lg font-bold"><?php echo __('api_console_title'); ?></h2>
                </div>
                <p class="text-xs text-gray-400 mt-1"><?php echo __('api_console_subtitle'); ?></p>
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-400 font-medium">Auth Mode:</label>
                <select id="console-auth-mode" class="bg-gray-800 border border-gray-700 text-xs text-white rounded px-2.5 py-1 focus:outline-none">
                    <option value="query">Query Param (?api_key=...)</option>
                    <option value="header" selected>HTTP Header (X-API-Key)</option>
                </select>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <!-- Endpoint Selector Tabs -->
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wider">Select Endpoint</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2" id="endpoint-tabs">
                    <button type="button" data-ep="timeseries" class="ep-tab active px-3 py-2 text-xs font-bold rounded-lg border text-left transition bg-blue-50 border-blue-500 text-blue-700 shadow-sm">
                        <span class="text-[10px] font-bold text-green-600 block">GET</span>
                        Timeseries
                    </button>
                    <button type="button" data-ep="trend" class="ep-tab px-3 py-2 text-xs font-bold rounded-lg border text-left transition bg-white border-gray-200 text-gray-700 hover:bg-gray-50">
                        <span class="text-[10px] font-bold text-green-600 block">GET</span>
                        Trend Compare
                    </button>
                    <button type="button" data-ep="anomalies" class="ep-tab px-3 py-2 text-xs font-bold rounded-lg border text-left transition bg-white border-gray-200 text-gray-700 hover:bg-gray-50">
                        <span class="text-[10px] font-bold text-green-600 block">GET</span>
                        Anomalies
                    </button>
                    <button type="button" data-ep="stream" class="ep-tab px-3 py-2 text-xs font-bold rounded-lg border text-left transition bg-white border-gray-200 text-gray-700 hover:bg-gray-50">
                        <span class="text-[10px] font-bold text-orange-600 block">SSE</span>
                        Live Stream
                    </button>
                    <button type="button" data-ep="export" class="ep-tab px-3 py-2 text-xs font-bold rounded-lg border text-left transition bg-white border-gray-200 text-gray-700 hover:bg-gray-50">
                        <span class="text-[10px] font-bold text-green-600 block">GET</span>
                        CSV / JSON Export
                    </button>
                    <button type="button" data-ep="geo" class="ep-tab px-3 py-2 text-xs font-bold rounded-lg border text-left transition bg-white border-gray-200 text-gray-700 hover:bg-gray-50">
                        <span class="text-[10px] font-bold text-green-600 block">GET</span>
                        Geo Grid
                    </button>
                </div>
            </div>

            <!-- Parameters Grid -->
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Request Parameters</h3>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-gray-500">Quick Ranges:</span>
                        <button type="button" onclick="setConsoleRange(15)" class="px-2 py-0.5 text-[11px] bg-white border rounded hover:bg-gray-100 font-medium">15m</button>
                        <button type="button" onclick="setConsoleRange(60)" class="px-2 py-0.5 text-[11px] bg-white border rounded hover:bg-gray-100 font-medium">1h</button>
                        <button type="button" onclick="setConsoleRange(1440)" class="px-2 py-0.5 text-[11px] bg-white border rounded hover:bg-gray-100 font-medium">24h</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">API Key</label>
                        <input id="c-key" type="text" value="temporal_grid_api_key_2024" class="w-full px-3 py-1.5 border rounded-lg font-mono">
                    </div>
                    <div id="c-wrap-metric">
                        <label class="block font-medium text-gray-700 mb-1">Metric Type</label>
                        <select id="c-metric" class="w-full px-3 py-1.5 border rounded-lg bg-white">
                            <option value="total_events" selected>total_events</option>
                            <option value="events_by_type">events_by_type</option>
                            <option value="events_by_source">events_by_source</option>
                        </select>
                    </div>
                    <div id="c-wrap-bucket">
                        <label class="block font-medium text-gray-700 mb-1">Bucket Size</label>
                        <select id="c-bucket" class="w-full px-3 py-1.5 border rounded-lg bg-white">
                            <option value="1m" selected>1m</option>
                            <option value="5m">5m</option>
                            <option value="15m">15m</option>
                            <option value="1h">1h</option>
                            <option value="1d">1d</option>
                        </select>
                    </div>
                    <div id="c-wrap-format">
                        <label class="block font-medium text-gray-700 mb-1">Format</label>
                        <select id="c-format" class="w-full px-3 py-1.5 border rounded-lg bg-white">
                            <option value="json" selected>JSON</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>

                    <div id="c-wrap-start">
                        <label class="block font-medium text-gray-700 mb-1">Start Time (UTC)</label>
                        <input id="c-start" type="datetime-local" class="w-full px-3 py-1.5 border rounded-lg font-mono">
                    </div>
                    <div id="c-wrap-end">
                        <label class="block font-medium text-gray-700 mb-1">End Time (UTC)</label>
                        <input id="c-end" type="datetime-local" class="w-full px-3 py-1.5 border rounded-lg font-mono">
                    </div>

                    <!-- Anomalies specific -->
                    <div id="c-wrap-baseline" class="hidden">
                        <label class="block font-medium text-gray-700 mb-1">Baseline</label>
                        <select id="c-baseline" class="w-full px-3 py-1.5 border rounded-lg bg-white">
                            <option value="historical" selected>Historical Average</option>
                            <option value="moving_average">Moving Average (MA)</option>
                        </select>
                    </div>
                    <div id="c-wrap-threshold" class="hidden">
                        <label class="block font-medium text-gray-700 mb-1">Deviation Threshold (%)</label>
                        <input id="c-threshold" type="number" value="50" class="w-full px-3 py-1.5 border rounded-lg font-mono">
                    </div>
                </div>
            </div>

            <!-- Live URL Preview & cURL Box -->
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span id="c-method-badge" class="px-2.5 py-1 bg-green-600 text-white font-bold rounded text-xs">GET</span>
                    <input id="c-live-url" type="text" readonly class="flex-1 px-3 py-1.5 bg-gray-100 border rounded-lg text-xs font-mono text-gray-800">
                    <button type="button" id="btn-execute" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-1.5 rounded-lg text-xs font-bold shadow-md hover:shadow-lg transition">
                        <?php echo __('send_request'); ?>
                    </button>
                </div>

                <div class="relative">
                    <input id="c-curl-box" type="text" readonly class="w-full px-3 py-1.5 bg-gray-900 text-gray-300 font-mono text-xs rounded-lg pr-20" value="curl -X GET ...">
                    <button type="button" onclick="copyCurl()" class="absolute right-2 top-1 bg-gray-800 hover:bg-gray-700 text-white px-2 py-0.5 rounded text-[11px] font-mono">
                        Copy cURL
                    </button>
                </div>
            </div>

            <!-- Response Viewer Card -->
            <div id="c-response-card" class="hidden border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-gray-100 px-4 py-2.5 border-b border-gray-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-gray-700"><?php echo __('response'); ?>:</span>
                        <span id="c-res-status" class="px-2 py-0.5 rounded font-bold text-white bg-green-600">200 OK</span>
                        <span id="c-res-time" class="text-gray-600 font-mono">⏱️ 0 ms</span>
                        <span id="c-res-size" class="text-gray-600 font-mono">📦 0 KB</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="copyConsoleResponse()" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50 text-xs font-semibold text-gray-700">
                            <?php echo __('copy_response'); ?>
                        </button>
                    </div>
                </div>

                <!-- SSE Terminal Mode or JSON Code Viewer -->
                <div id="c-sse-terminal" class="hidden bg-gray-950 p-4 text-green-400 font-mono text-xs max-h-80 overflow-y-auto space-y-1">
                    <div class="text-gray-500">// Connected to Server-Sent Events stream. Listening for live updates...</div>
                </div>

                <pre id="c-res-body" class="bg-gray-900 text-gray-100 p-4 font-mono text-xs max-h-96 overflow-auto leading-relaxed"></pre>
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
</div>

<script>
let activeEp = 'timeseries';
let sseConsoleSource = null;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('cur-base').textContent = window.location.origin;
    setConsoleRange(15);
    setupConsoleEvents();
    buildLiveUrl();
});

function toLocalInput(dt) {
    const pad = n => String(n).padStart(2,'0');
    return `${dt.getFullYear()}-${pad(dt.getMonth()+1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
}

function setConsoleRange(minutes) {
    const now = new Date();
    const start = new Date(now.getTime() - minutes * 60 * 1000);
    document.getElementById('c-start').value = toLocalInput(start);
    document.getElementById('c-end').value = toLocalInput(now);
    buildLiveUrl();
}

function setupConsoleEvents() {
    // Tabs
    document.querySelectorAll('.ep-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            activeEp = this.dataset.ep;
            document.querySelectorAll('.ep-tab').forEach(t => {
                t.classList.remove('active', 'bg-blue-50', 'border-blue-500', 'text-blue-700', 'shadow-sm');
                t.classList.add('bg-white', 'border-gray-200', 'text-gray-700');
            });
            this.classList.add('active', 'bg-blue-50', 'border-blue-500', 'text-blue-700', 'shadow-sm');
            this.classList.remove('bg-white', 'border-gray-200', 'text-gray-700');

            updateFieldVisibility();
            buildLiveUrl();
        });
    });

    // Form input listeners
    ['c-key', 'c-metric', 'c-bucket', 'c-format', 'c-start', 'c-end', 'c-baseline', 'c-threshold', 'console-auth-mode'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', buildLiveUrl);
            el.addEventListener('change', buildLiveUrl);
        }
    });

    // Execute button
    document.getElementById('btn-execute').addEventListener('click', executeConsoleRequest);
}

function updateFieldVisibility() {
    const isAnomalies = activeEp === 'anomalies';
    const isStream = activeEp === 'stream';
    const isGeo = activeEp === 'geo';

    document.getElementById('c-wrap-baseline').style.display = isAnomalies ? 'block' : 'none';
    document.getElementById('c-wrap-threshold').style.display = isAnomalies ? 'block' : 'none';
    document.getElementById('c-wrap-metric').style.display = (isStream || isGeo) ? 'none' : 'block';
    document.getElementById('c-wrap-bucket').style.display = isGeo ? 'none' : 'block';
    document.getElementById('c-wrap-format').style.display = (isStream || isGeo) ? 'none' : 'block';

    const methodBadge = document.getElementById('c-method-badge');
    if (isStream) {
        methodBadge.textContent = 'SSE';
        methodBadge.className = 'px-2.5 py-1 bg-orange-500 text-white font-bold rounded text-xs';
    } else {
        methodBadge.textContent = 'GET';
        methodBadge.className = 'px-2.5 py-1 bg-green-600 text-white font-bold rounded text-xs';
    }
}

function buildLiveUrl() {
    const base = window.location.origin;
    const authMode = document.getElementById('console-auth-mode').value;
    const key = document.getElementById('c-key').value.trim();
    const metric = document.getElementById('c-metric').value;
    const bucket = document.getElementById('c-bucket').value;
    const format = document.getElementById('c-format').value;
    const startVal = document.getElementById('c-start').value;
    const endVal = document.getElementById('c-end').value;
    const start = startVal ? new Date(startVal).toISOString() : '';
    const end = endVal ? new Date(endVal).toISOString() : '';

    let endpointPath = '/api/v1/timeseries.php';
    const params = new URLSearchParams();

    if (activeEp === 'timeseries') {
        endpointPath = '/api/v1/timeseries.php';
        params.set('metric_type', metric);
        params.set('bucket_size', bucket);
        if (start) params.set('start_time', start);
        if (end) params.set('end_time', end);
        if (format === 'csv') params.set('format', 'csv');
    } else if (activeEp === 'trend') {
        endpointPath = '/api/v1/trend.php';
        params.set('metric_type', metric);
        if (start) params.set('primary_start_time', start);
        if (end) params.set('primary_end_time', end);
        const compStart = startVal ? new Date(new Date(startVal).getTime() - 24*3600*1000).toISOString() : '';
        const compEnd = endVal ? new Date(new Date(endVal).getTime() - 24*3600*1000).toISOString() : '';
        params.set('compare_start_time', compStart);
        params.set('compare_end_time', compEnd);
    } else if (activeEp === 'anomalies') {
        endpointPath = '/api/v1/anomalies.php';
        params.set('metric_type', metric);
        params.set('bucket_size', bucket);
        params.set('baseline', document.getElementById('c-baseline').value);
        params.set('deviation_threshold', document.getElementById('c-threshold').value);
        if (start) params.set('start_time', start);
        if (end) params.set('end_time', end);
    } else if (activeEp === 'stream') {
        endpointPath = '/api/v1/stream.php';
        params.set('bucket_size', bucket);
    } else if (activeEp === 'export') {
        endpointPath = '/api/v1/export.php';
        params.set('metric_type', metric);
        params.set('bucket_size', bucket);
        params.set('format', format);
        if (start) params.set('start_time', start);
        if (end) params.set('end_time', end);
    } else if (activeEp === 'geo') {
        endpointPath = '/actions/get_geo_summary.php';
        if (start) params.set('start_time', start);
        if (end) params.set('end_time', end);
        params.set('limit', '8');
    }

    if (authMode === 'query' && key) {
        params.set('api_key', key);
    }

    const qs = params.toString();
    const fullUrl = `${base}${endpointPath}${qs ? '?' + qs : ''}`;
    document.getElementById('c-live-url').value = fullUrl;

    // cURL command
    let curl = `curl -X GET "${fullUrl}"`;
    if (authMode === 'header' && key) {
        curl = `curl -X GET "${fullUrl}" \\\n  -H "X-API-Key: ${key}"`;
    }
    document.getElementById('c-curl-box').value = curl;
}

async function executeConsoleRequest() {
    const url = document.getElementById('c-live-url').value;
    const authMode = document.getElementById('console-auth-mode').value;
    const key = document.getElementById('c-key').value.trim();
    const card = document.getElementById('c-response-card');
    const statusBadge = document.getElementById('c-res-status');
    const timeSpan = document.getElementById('c-res-time');
    const sizeSpan = document.getElementById('c-res-size');
    const pre = document.getElementById('c-res-body');
    const sseTerminal = document.getElementById('c-sse-terminal');

    card.classList.remove('hidden');

    // Handle SSE mode
    if (activeEp === 'stream') {
        pre.classList.add('hidden');
        sseTerminal.classList.remove('hidden');
        if (sseConsoleSource) {
            sseConsoleSource.close();
        }
        sseTerminal.innerHTML = `<div class="text-amber-400 font-mono">// [${new Date().toLocaleTimeString()}] Connecting to ${url} ...</div>`;
        statusBadge.textContent = 'CONNECTING...';
        statusBadge.className = 'px-2 py-0.5 rounded font-bold text-white bg-amber-500';

        const sseUrl = new URL(url);
        if (authMode === 'header' && key) {
            sseUrl.searchParams.set('api_key', key);
        }

        try {
            sseConsoleSource = new EventSource(sseUrl.toString());
            sseConsoleSource.addEventListener('update', (e) => {
                statusBadge.textContent = '200 STREAMING';
                statusBadge.className = 'px-2 py-0.5 rounded font-bold text-white bg-green-600';
                const time = new Date().toLocaleTimeString();
                const div = document.createElement('div');
                div.innerHTML = `<span class="text-blue-400">[${time}]</span> <span class="text-green-300">event: update</span> -> ${escapeHtml(e.data)}`;
                sseTerminal.appendChild(div);
                sseTerminal.scrollTop = sseTerminal.scrollHeight;
            });
            sseConsoleSource.onerror = () => {
                statusBadge.textContent = 'DISCONNECTED';
                statusBadge.className = 'px-2 py-0.5 rounded font-bold text-white bg-red-600';
            };
        } catch(err) {
            sseTerminal.innerHTML += `<div class="text-red-400">Error: ${err.message}</div>`;
        }
        return;
    }

    // Standard REST Request
    if (sseConsoleSource) {
        sseConsoleSource.close();
        sseConsoleSource = null;
    }
    sseTerminal.classList.add('hidden');
    pre.classList.remove('hidden');

    pre.textContent = 'Executing request...';
    statusBadge.textContent = 'LOADING...';
    statusBadge.className = 'px-2 py-0.5 rounded font-bold text-white bg-blue-500';

    const headers = {};
    if (authMode === 'header' && key) {
        headers['X-API-Key'] = key;
    }

    const startTime = performance.now();
    try {
        const res = await fetch(url, { headers });
        const endTime = performance.now();
        const duration = Math.round(endTime - startTime);

        const status = res.status;
        const statusText = res.statusText || (status === 200 ? 'OK' : 'Error');
        statusBadge.textContent = `${status} ${statusText}`;
        statusBadge.className = status >= 200 && status < 300 
            ? 'px-2 py-0.5 rounded font-bold text-white bg-green-600' 
            : 'px-2 py-0.5 rounded font-bold text-white bg-red-600';

        timeSpan.textContent = `⏱️ ${duration} ms`;

        const contentType = res.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const json = await res.json();
            const text = JSON.stringify(json, null, 2);
            sizeSpan.textContent = `📦 ${(new Blob([text]).size / 1024).toFixed(2)} KB`;
            pre.innerHTML = syntaxHighlightJson(text);
        } else {
            const text = await res.text();
            sizeSpan.textContent = `📦 ${(new Blob([text]).size / 1024).toFixed(2)} KB`;
            pre.textContent = text;
        }

    } catch (err) {
        statusBadge.textContent = 'FAILED';
        statusBadge.className = 'px-2 py-0.5 rounded font-bold text-white bg-red-600';
        pre.textContent = `Network / Execution Error: ${err.message}`;
    }
}

function syntaxHighlightJson(json) {
    json = escapeHtml(json);
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        let cls = 'text-green-400'; // number
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'text-blue-300 font-bold'; // key
            } else {
                cls = 'text-amber-300'; // string
            }
        } else if (/true|false/.test(match)) {
            cls = 'text-purple-400 font-semibold'; // boolean
        } else if (/null/.test(match)) {
            cls = 'text-gray-400 italic'; // null
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

function copyCurl() {
    const text = document.getElementById('c-curl-box').value;
    navigator.clipboard.writeText(text).then(() => alert('cURL copied to clipboard!'));
}

function copyConsoleResponse() {
    const pre = document.getElementById('c-res-body');
    navigator.clipboard.writeText(pre.innerText).then(() => alert('Response copied to clipboard!'));
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php include 'includes/footer.php'; ?>
