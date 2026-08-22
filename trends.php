<?php
require_once 'auth.php';
\Temporal\Auth::getInstance()->requireLogin();
$current_page = 'trends';
include 'includes/header.php';
?>

<div class="px-4 py-6 sm:px-0">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Trend Karşılaştırma</h1>
        <p class="text-gray-600">İki zaman aralığını karşılaştırarak değişimi görün.</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Parametreler</h2>
        <form id="trend-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Metrik</label>
                <select id="metric-type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="total_events" selected>Toplam Event</option>
                    <option value="events_by_type">Event Türüne Göre</option>
                    <option value="events_by_source">Kaynağa Göre</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Event Türü</label>
                <input id="type" type="text" placeholder="sensor_alert" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Source ID</label>
                <input id="source-id" type="text" placeholder="sensor_001" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Birinci Başlangıç</label>
                <input id="primary-start" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Birinci Bitiş</label>
                <input id="primary-end" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Karşılaştırma Başlangıç</label>
                <input id="compare-start" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Karşılaştırma Bitiş</label>
                <input id="compare-end" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
        </form>
        <div class="mt-4">
            <button id="run-trend" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md font-medium">Karşılaştır</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-md font-semibold text-gray-900 mb-2">Sonuçlar</h3>
            <div class="space-y-2">
                <div class="flex justify-between"><span>Birinci Dönem:</span><span id="primary-count">-</span></div>
                <div class="flex justify-between"><span>Karşılaştırma:</span><span id="compare-count">-</span></div>
                <div class="flex justify-between"><span>Fark (Mutlak):</span><span id="diff-abs">-</span></div>
                <div class="flex justify-between"><span>Fark (%):</span><span id="diff-pct">-</span></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-md font-semibold text-gray-900 mb-2">Grafik</h3>
            <div class="h-64"><canvas id="trend-chart"></canvas></div>
        </div>
    </div>
</div>

<script>
let trendChart = null;

document.addEventListener('DOMContentLoaded', () => {
    setDefaultRanges();
    document.getElementById('run-trend').addEventListener('click', runTrend);
});

function setDefaultRanges() {
    const now = new Date();
    const oneHourAgo = new Date(Date.now() - 60*60*1000);
    const twoHoursAgo = new Date(Date.now() - 2*60*60*1000);
    const oneHourBeforeTwoHoursAgo = new Date(Date.now() - 3*60*60*1000);
    document.getElementById('primary-start').value = toLocalInput(oneHourAgo);
    document.getElementById('primary-end').value = toLocalInput(now);
    document.getElementById('compare-start').value = toLocalInput(oneHourBeforeTwoHoursAgo);
    document.getElementById('compare-end').value = toLocalInput(twoHoursAgo);
}

function toLocalInput(dt) {
    const pad = n => String(n).padStart(2,'0');
    return `${dt.getFullYear()}-${pad(dt.getMonth()+1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
}

async function runTrend() {
    const params = collectParams();
    const url = new URL('/api/v1/trend.php', window.location.origin);
    url.searchParams.append('api_key','temporal_grid_api_key_2024');
    Object.entries(params).forEach(([k,v])=>{ if(v) url.searchParams.append(k,v); });
    const resp = await fetch(url);
    const json = await resp.json();
    if(!resp.ok) { alert(json.error||'Hata'); return; }
    const d = json.data;
    document.getElementById('primary-count').textContent = d.primary_count;
    document.getElementById('compare-count').textContent = d.compare_count;
    document.getElementById('diff-abs').textContent = d.difference_absolute;
    document.getElementById('diff-pct').textContent = d.difference_percent + '%';
    renderTrendChart(d);
}

function collectParams(){
    const iso = s => new Date(s).toISOString();
    return {
        metric_type: document.getElementById('metric-type').value,
        type: document.getElementById('type').value || null,
        source_id: document.getElementById('source-id').value || null,
        primary_start_time: iso(document.getElementById('primary-start').value),
        primary_end_time: iso(document.getElementById('primary-end').value),
        compare_start_time: iso(document.getElementById('compare-start').value),
        compare_end_time: iso(document.getElementById('compare-end').value)
    };
}

function renderTrendChart(d){
    const ctx = document.getElementById('trend-chart').getContext('2d');
    if(trendChart) trendChart.destroy();
    trendChart = new Chart(ctx,{
        type:'bar',
        data:{
            labels:['Birinci','Karşılaştırma'],
            datasets:[{
                label:'Toplam',
                data:[d.primary_count, d.compare_count],
                backgroundColor:['rgba(59,130,246,0.6)','rgba(234,88,12,0.6)']
            }]
        },
        options:{responsive:true,maintainAspectRatio:false}
    });
}
</script>

<?php include 'includes/footer.php'; ?>
