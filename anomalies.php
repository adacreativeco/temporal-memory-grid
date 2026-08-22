<?php
require_once 'auth.php';
\Temporal\Auth::getInstance()->requireLogin();
$current_page = 'anomalies';
include 'includes/header.php';
?>

<div class="px-4 py-6 sm:px-0">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Anomali Görünümü</h1>
        <p class="text-gray-600">Seçilen aralıkta normal dışı kovaları işaretleyin.</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Parametreler</h2>
        <form id="anomaly-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Metrik</label>
                <select id="metric-type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="total_events" selected>Toplam Event</option>
                    <option value="events_by_type">Event Türüne Göre</option>
                    <option value="events_by_source">Kaynağa Göre</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bucket Boyutu</label>
                <select id="bucket-size" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="1m">1 Dakika</option>
                    <option value="5m" selected>5 Dakika</option>
                    <option value="15m">15 Dakika</option>
                    <option value="1h">1 Saat</option>
                    <option value="1d">1 Gün</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Baseline</label>
                <select id="baseline" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="historical" selected>Tarihsel Ortalama</option>
                    <option value="moving_average">Hareketli Ortalama</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">MA Pencere</label>
                <input id="ma-window" type="number" value="6" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Eşik (%)</label>
                <input id="threshold" type="number" value="50" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Başlangıç</label>
                <input id="start" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bitiş</label>
                <input id="end" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Event Türü (ops.)</label>
                <input id="type" type="text" placeholder="sensor_alert" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Source ID (ops.)</label>
                <input id="source-id" type="text" placeholder="sensor_001" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
        </form>
        <div class="mt-4">
            <button id="run-anomaly" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md font-medium">Anomalileri Bul</button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-md font-semibold text-gray-900 mb-2">Zaman Serisi Grafiği</h3>
        <div class="h-64"><canvas id="anomaly-chart"></canvas></div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-md font-semibold text-gray-900 mb-2">Anomali Kovaları</h3>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bucket Başlangıç</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Gözlenen</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Beklenen</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sapma (%)</th>
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
    if(!resp.ok){ alert(json.error||'Hata'); return; }
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
    rows.forEach(r=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-4 py-2">${new Date(r.bucket_start).toLocaleString('tr-TR')}</td>
                        <td class="px-4 py-2">${r.observed_value}</td>
                        <td class="px-4 py-2">${r.expected_value}</td>
                        <td class="px-4 py-2">${r.deviation_percent}%</td>`;
        tbody.appendChild(tr);
    });
}

function renderChart(d){
    const labels = d.anomaly_buckets.map(b=> new Date(b.bucket_start).toLocaleString('tr-TR'));
    const values = d.anomaly_buckets.map(b=> b.observed_value);
    const ctx = document.getElementById('anomaly-chart').getContext('2d');
    if(anomalyChart) anomalyChart.destroy();
    anomalyChart = new Chart(ctx,{
        type:'line',
        data:{ labels, datasets:[{ label:'Gözlenen', data: values, borderColor:'rgb(239,68,68)', backgroundColor:'rgba(239,68,68,0.1)', tension:0.1, fill:true }]},
        options:{ responsive:true, maintainAspectRatio:false }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
