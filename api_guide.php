<?php
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Temporal Aggregates API Guide</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<div class="max-w-4xl mx-auto p-6 space-y-6">
<div class="text-center space-y-2">
<h1 class="text-3xl font-bold text-gray-900">Temporal Aggregates API</h1>
<p class="text-gray-600">Zaman serisi, trend ve anomali API’lerini hızlıca deneyin</p>
</div>

<div class="bg-white rounded-lg shadow p-6 space-y-2">
  <div class="font-semibold text-gray-900">Ortam Bilgileri</div>
  <div class="text-sm text-gray-700">Üretim Base URL: <span class="font-mono">https://tmg.adacreative.co</span></div>
  <div class="text-sm text-gray-700">Aktif Base URL: <span class="font-mono" id="cur-base">(algılanıyor)</span></div>
  <div class="text-sm text-gray-700">API Key: <span class="font-mono">temporal_grid_api_key_2024</span> (Header: <span class="font-mono">X-API-Key</span> ya da query <span class="font-mono">api_key</span>)</div>
</div>

<div class="bg-white rounded-lg shadow p-6 space-y-2">
  <div class="font-semibold text-gray-900">Geçerli API Anahtarları</div>
  <div class="text-sm text-gray-700"><span class="font-mono">temporal_grid_api_key_2024</span></div>
  <div class="text-sm text-gray-700"><span class="font-mono">demo_key_12345</span></div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<a href="/docs/aggregate_api_guide.md" class="block bg-white rounded-lg shadow p-5 hover:shadow-md">
<div class="font-semibold text-gray-900">Rehber Dokümantasyon</div>
<div class="text-sm text-gray-600">Parametreler, örnek istekler ve hata kodları</div>
</a>
<a href="/postman/temporal_memory_grid.postman_collection.json" class="block bg-white rounded-lg shadow p-5 hover:shadow-md">
<div class="font-semibold text-gray-900">Postman Koleksiyonu</div>
<div class="text-sm text-gray-600">Hazır isteklerle hızlı başlat</div>
</a>
</div>

<div class="bg-white rounded-lg shadow p-6 space-y-4">
<div class="font-semibold text-gray-900">Hızlı Başlat</div>
<div class="text-sm text-gray-600">Son 5 dakikalık pencere için örnek zaman serisi çağrıları</div>
<div class="space-y-3" id="quick-links"></div>
</div>

<div class="bg-white rounded-lg shadow p-6 space-y-4">
<div class="font-semibold text-gray-900">Giriş Yap</div>
<div class="text-sm text-gray-600">Yönetim ve dashboard için oturum açın</div>
<a href="/login.php" class="inline-block bg-blue-600 text-white px-4 py-2 rounded">Login</a>
</div>
</div>

<script>
function iso(dt){return new Date(dt).toISOString()}
function build(){
 const now=new Date();
 const end=iso(now);
 const start=iso(new Date(now.getTime()-5*60*1000));
 const apiKey='temporal_grid_api_key_2024';
 const base=window.location.origin;
 const items=[
  {label:'Timeseries Total (JSON, 1m)',url:`${base}/api/v1/timeseries.php?api_key=${apiKey}&metric_type=total_events&bucket_size=1m&start_time=${start}&end_time=${end}`},
  {label:'Timeseries Total (CSV, 1m)',url:`${base}/api/v1/timeseries.php?api_key=${apiKey}&metric_type=total_events&bucket_size=1m&start_time=${start}&end_time=${end}&format=csv`},
  {label:'Trend Total',url:`${base}/api/v1/trend.php?api_key=${apiKey}&metric_type=total_events&primary_start_time=${start}&primary_end_time=${end}&compare_start_time=${iso(new Date(now.getTime()-24*60*60*1000-5*60*1000))}&compare_end_time=${iso(new Date(now.getTime()-24*60*60*1000))}`},
  {label:'Anomalies Total (1m)',url:`${base}/api/v1/anomalies.php?api_key=${apiKey}&metric_type=total_events&bucket_size=1m&start_time=${start}&end_time=${end}&baseline=historical&deviation_threshold=50`}
 ];
 const wrap=document.getElementById('quick-links');
 items.forEach(i=>{
  const a=document.createElement('a');
  a.href=i.url; a.className='block bg-gray-100 rounded px-4 py-2 hover:bg-gray-200'; a.textContent=i.label;
  wrap.appendChild(a);
 });
 document.getElementById('cur-base').textContent=window.location.origin;
}
build();
</script>
</body>
</html>
