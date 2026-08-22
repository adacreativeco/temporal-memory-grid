<?php
require_once 'auth.php';
require_once 'i18n.php';
\Temporal\Auth::getInstance()->requireLogin();
$current_page = 'anomalies';
include 'includes/header.php';
?>

<div class="px-4 py-6 sm:px-0">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo __('anomalies_title'); ?></h1>
        <p class="text-gray-600"><?php echo __('anomalies_subtitle'); ?></p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('filters_title'); ?></h2>
        <form id="anomaly-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('metric'); ?></label>
                <select id="metric-type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="total_events" selected><?php echo __('total_events'); ?></option>
                    <option value="events_by_type">Event Type</option>
                    <option value="events_by_source">Source ID</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('bucket_size'); ?></label>
                <select id="bucket-size" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="1m">1 min</option>
                    <option value="5m" selected>5 min</option>
                    <option value="15m">15 min</option>
                    <option value="1h">1 hour</option>
                    <option value="1d">1 day</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('baseline'); ?></label>
                <select id="baseline" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="historical" selected><?php echo __('historical_avg'); ?></option>
                    <option value="moving_average"><?php echo __('moving_avg'); ?></option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('ma_window'); ?></label>
                <input id="ma-window" type="number" value="6" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('threshold_percent'); ?></label>
                <input id="threshold" type="number" value="50" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('start_date'); ?></label>
                <input id="start" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('end_date'); ?></label>
                <input id="end" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('event_type'); ?></label>
                <input id="type" type="text" placeholder="sensor_alert" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo __('source_id'); ?></label>
                <input id="source-id" type="text" placeholder="sensor_001" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
        </form>
        <div class="mt-4">
            <button id="run-anomaly" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition"><?php echo __('find_anomalies'); ?></button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-md font-semibold text-gray-900 mb-2"><?php echo __('timeseries_chart'); ?></h3>
        <div class="h-64"><canvas id="anomaly-chart"></canvas></div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-md font-semibold text-gray-900 mb-2"><?php echo __('anomaly_buckets'); ?></h3>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('bucket_start'); ?></th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('observed_value'); ?></th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('expected_value'); ?></th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"><?php echo __('deviation_percent'); ?></th>
                </tr>
            </thead>
            <tbody id="anomaly-table" class="bg-white divide-y divide-gray-200"></tbody>
        </table>
    </div>
</div>

<script>
let anomalyChart = null;

document.addEventListener('DOMContentLoaded', () => {
    setDefaultRange();
    document.getElementById('run-anomaly').addEventListener('click', runAnomaly);
    document.getElementById('baseline').addEventListener('change', () => {
        const val = document.getElementById('baseline').value;
        document.getElementById('ma-window').parentElement.style.display = (val === 'moving_average') ? 'block' : 'none';
    });
    document.getElementById('ma-window').parentElement.style.display = 'none';
});

function setDefaultRange(){
    const now = new Date();
    const dayAgo = new Date(Date.now()-24*60*60*1000);
    document.getElementById('start').value = toLocalInput(dayAgo);
    document.getElementById('end').value = toLocalInput(now);
}

function toLocalInput(dt){
    const pad=n=>String(n).padStart(2,'0');
    return `${dt.getFullYear()}-${pad(dt.getMonth()+1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
}

async function runAnomaly(){
    const params = collectParams();
    const url = new URL('/api/v1/anomalies.php', window.location.origin);
    url.searchParams.append('api_key','temporal_grid_api_key_2024');
    Object.entries(params).forEach(([k,v])=>{ if(v!==null && v!==undefined) url.searchParams.append(k,v); });
    const resp = await fetch(url);
    const json = await resp.json();
    if(!resp.ok){ alert(json.error||'Error'); return; }
    const d = json.data;
    renderTable(d.anomaly_buckets);
    renderChart(d);
}

function collectParams(){
    const iso = s => new Date(s).toISOString();
    return {
        metric_type: document.getElementById('metric-type').value,
        bucket_size: document.getElementById('bucket-size').value,
        deviation_threshold: document.getElementById('threshold').value,
        start_time: iso(document.getElementById('start').value),
        end_time: iso(document.getElementById('end').value),
        type: document.getElementById('type').value || null,
        source_id: document.getElementById('source-id').value || null,
        baseline: document.getElementById('baseline').value,
        ma_window: document.getElementById('baseline').value === 'moving_average' ? document.getElementById('ma-window').value : undefined
    };
}

function renderTable(rows){
    const tbody = document.getElementById('anomaly-table');
    tbody.innerHTML = '';
    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="px-4 py-4 text-center text-gray-400 text-sm">No anomalies found for selected criteria.</td></tr>`;
        return;
    }
    rows.forEach(r=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-4 py-2 text-sm">${new Date(r.bucket_start).toLocaleString(window.TMG_LOCALE || 'tr-TR')}</td>
                        <td class="px-4 py-2 text-sm font-semibold text-red-600">${r.observed_value}</td>
                        <td class="px-4 py-2 text-sm">${r.expected_value}</td>
                        <td class="px-4 py-2 text-sm font-bold text-red-500">${r.deviation_percent}%</td>`;
        tbody.appendChild(tr);
    });
}

function renderChart(d){
    const labels = (d.anomaly_buckets || []).map(b=> new Date(b.bucket_start).toLocaleString(window.TMG_LOCALE || 'tr-TR'));
    const values = (d.anomaly_buckets || []).map(b=> b.observed_value);
    const ctx = document.getElementById('anomaly-chart').getContext('2d');
    if(anomalyChart) anomalyChart.destroy();
    anomalyChart = new Chart(ctx,{
        type:'line',
        data:{ labels, datasets:[{ label: window.t('observed_value', 'Observed'), data: values, borderColor:'rgb(239,68,68)', backgroundColor:'rgba(239,68,68,0.1)', tension:0.1, fill:true }]},
        options:{ responsive:true, maintainAspectRatio:false }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
