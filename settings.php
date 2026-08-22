<?php
require_once 'auth.php';
require_once 'cache.php';
require_once 'system_logs.php';
require_once 'i18n.php';
\Temporal\Auth::getInstance()->requireLogin();
$current_page = 'settings';
include 'includes/header.php';
?>

<div class="px-4 py-6 sm:px-0">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo __('settings_title'); ?></h1>
        <p class="text-gray-600"><?php echo __('settings_subtitle'); ?></p>
    </div>

    <!-- Worker & Automation Card -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6 border border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 pb-3 border-b border-gray-100">
            <div>
                <h2 class="text-lg font-semibold text-gray-900"><?php echo __('worker_card_title'); ?></h2>
                <p class="text-sm text-gray-500"><?php echo __('worker_card_desc'); ?></p>
            </div>
            <div id="settings-worker-badge" class="mt-2 md:mt-0 inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border">
                <span class="w-2 h-2 mr-2 rounded-full bg-gray-400"></span> <?php echo __('worker_badge_loading'); ?>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                <span class="text-xs text-gray-500 block"><?php echo __('last_heartbeat'); ?></span>
                <span id="worker-last-beat" class="text-sm font-semibold text-gray-800">-</span>
            </div>
            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                <span class="text-xs text-gray-500 block"><?php echo __('loop_interval'); ?></span>
                <span id="worker-interval" class="text-sm font-semibold text-gray-800">10s</span>
            </div>
            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                <span class="text-xs text-gray-500 block"><?php echo __('last_status'); ?></span>
                <span id="worker-status-text" class="text-sm font-semibold text-gray-800">-</span>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" id="btn-run-worker-once" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm transition">
                <?php echo __('run_worker_once'); ?>
            </button>
            <span class="text-xs text-gray-500">
                CLI: <code class="bg-gray-100 px-2 py-1 rounded text-gray-800 font-mono">php worker.php</code> | <code class="bg-gray-100 px-2 py-1 rounded text-gray-800 font-mono">scripts\run_worker.bat</code>
            </span>
        </div>
        <div id="worker-action-result" class="text-sm text-gray-600 mt-2"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- API Keys Management -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900"><?php echo __('api_keys_title'); ?></h2>
                    <p class="text-xs text-gray-500"><?php echo __('api_keys_desc'); ?></p>
                </div>
                <button type="button" id="btn-show-create-key" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1.5 rounded-md shadow-sm">
                    <?php echo __('create_new_key'); ?>
                </button>
            </div>

            <!-- Create Key Form (Hidden by default) -->
            <div id="create-key-box" class="hidden mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-sm font-semibold text-blue-900 mb-2"><?php echo __('create_new_key'); ?></h3>
                <div class="space-y-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-700"><?php echo __('key_name'); ?></label>
                        <input id="new-key-name" type="text" placeholder="e.g. Production Mobile App" class="w-full px-3 py-1.5 text-sm border rounded-md">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700"><?php echo __('key_rate_limit'); ?></label>
                            <input id="new-key-rate" type="number" value="100" class="w-full px-3 py-1.5 text-sm border rounded-md">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700"><?php echo __('key_custom'); ?></label>
                            <input id="new-key-custom" type="text" placeholder="Auto-generated" class="w-full px-3 py-1.5 text-sm border rounded-md">
                        </div>
                    </div>
                    <div class="flex space-x-2 pt-1">
                        <button type="button" id="btn-save-key" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1.5 rounded-md"><?php echo __('create'); ?></button>
                        <button type="button" id="btn-cancel-key" class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-xs font-medium px-3 py-1.5 rounded-md"><?php echo __('cancel'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Keys Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('key_table_name'); ?></th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('key_table_key'); ?></th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('key_table_status'); ?></th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500"><?php echo __('key_table_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="api-keys-table" class="bg-white divide-y divide-gray-100">
                        <tr><td colspan="4" class="px-3 py-3 text-center text-gray-400">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="key-result" class="text-xs text-gray-600 mt-2"></div>
        </div>

        <!-- Security / Password Change -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-1"><?php echo __('security_title'); ?></h2>
            <p class="text-xs text-gray-500 mb-4"><?php echo __('security_desc'); ?></p>
            <form id="password-form" class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700"><?php echo __('current_password'); ?></label>
                    <input id="current-password" type="password" class="mt-1 w-full px-3 py-1.5 text-sm border rounded-md" placeholder="••••••••">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700"><?php echo __('new_password'); ?></label>
                        <input id="new-password" type="password" class="mt-1 w-full px-3 py-1.5 text-sm border rounded-md" placeholder="Min 6 characters">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700"><?php echo __('confirm_password'); ?></label>
                        <input id="confirm-password" type="password" class="mt-1 w-full px-3 py-1.5 text-sm border rounded-md" placeholder="Repeat new password">
                    </div>
                </div>
                <div class="pt-1">
                    <button type="button" id="btn-change-password" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-4 py-2 rounded-md shadow-sm">
                        <?php echo __('update_password'); ?>
                    </button>
                </div>
            </form>
            <div id="password-result" class="text-xs text-gray-600 mt-2"></div>
        </div>

        <!-- Retention Settings -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('retention_title'); ?></h2>
            <form id="retention-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700"><?php echo __('raw_event_retention'); ?></label>
                    <input id="raw-days" type="number" value="60" class="mt-1 w-full px-3 py-2 border rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700"><?php echo __('agg_retention'); ?></label>
                    <input id="agg-days" type="number" value="365" class="mt-1 w-full px-3 py-2 border rounded-md">
                </div>
                <div class="flex space-x-2">
                    <button type="button" id="save-retention" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('save'); ?></button>
                    <button type="button" id="run-cleanup" class="bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('run_cleanup'); ?></button>
                </div>
            </form>
            <div id="retention-result" class="text-sm text-gray-600 mt-3"></div>
        </div>

        <!-- Cache Management -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('cache_title'); ?></h2>
            <div class="space-y-3">
                <p class="text-sm text-gray-600"><?php echo __('cache_desc'); ?></p>
                <button id="clear-cache" class="bg-gray-600 hover:bg-gray-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('clear_cache'); ?></button>
                <div id="cache-result" class="text-sm text-gray-600"></div>
            </div>
        </div>

        <!-- External Live API Configuration -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('external_api_title'); ?></h2>
            <form id="external-api-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">API URL</label>
                    <input id="external-api-url" type="text" class="mt-1 w-full px-3 py-2 border rounded-md" placeholder="http://localhost:8081/api/v1/public/events.php">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo __('header_name'); ?></label>
                        <input id="external-api-header" type="text" class="mt-1 w-full px-3 py-2 border rounded-md" value="X-API-Key">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo __('token_optional'); ?></label>
                        <input id="external-api-token" type="text" class="mt-1 w-full px-3 py-2 border rounded-md" placeholder="test_key">
                    </div>
                </div>
                <div>
                    <label class="inline-flex items-center">
                        <input id="external-api-insecure" type="checkbox" class="mr-2">
                        <span class="text-sm text-gray-700"><?php echo __('insecure_ssl'); ?></span>
                    </label>
                </div>
                <div class="space-x-2 pt-1">
                    <button type="button" id="save-external-api" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('save'); ?></button>
                    <button type="button" id="test-external-api" class="bg-gray-600 hover:bg-gray-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('test_connection'); ?></button>
                </div>
            </form>
            <div id="external-api-result" class="text-sm text-gray-600 mt-3"></div>
        </div>

        <!-- Manual JSON Import -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('manual_json_title'); ?></h2>
            <p class="text-sm text-gray-600 mb-2"><?php echo __('manual_json_desc'); ?></p>
            <textarea id="manual-json" class="w-full h-32 px-3 py-2 border rounded-md font-mono text-xs" placeholder='[{"type":"vehicle_movement","source":"sensor_01","timestamp":1733872741}]'></textarea>
            <div class="mt-3">
                <button type="button" id="import-manual-json" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('import_and_aggregate'); ?></button>
            </div>
            <div id="manual-import-result" class="text-sm text-gray-600 mt-3"></div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadSettings();
    loadExternalApi();
    loadWorkerStatus();
    loadApiKeys();

    setInterval(loadWorkerStatus, 8000);

    document.getElementById('save-retention').addEventListener('click', saveSettings);
    document.getElementById('run-cleanup').addEventListener('click', runCleanup);
    document.getElementById('clear-cache').addEventListener('click', clearCache);
    document.getElementById('save-external-api').addEventListener('click', saveExternalApi);
    document.getElementById('test-external-api').addEventListener('click', testExternalApi);
    document.getElementById('import-manual-json').addEventListener('click', importManualJson);
    document.getElementById('btn-run-worker-once').addEventListener('click', runWorkerOnce);
    document.getElementById('btn-change-password').addEventListener('click', changePassword);

    // API Keys UI
    document.getElementById('btn-show-create-key').addEventListener('click', () => {
        document.getElementById('create-key-box').classList.toggle('hidden');
    });
    document.getElementById('btn-cancel-key').addEventListener('click', () => {
        document.getElementById('create-key-box').classList.add('hidden');
    });
    document.getElementById('btn-save-key').addEventListener('click', createApiKey);
});

async function loadWorkerStatus() {
    try {
        const res = await fetch('/actions/worker_status.php');
        const json = await res.json();
        if (json.success && json.data) {
            const d = json.data;
            const badge = document.getElementById('settings-worker-badge');
            if (d.is_live) {
                badge.className = 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-300';
                badge.innerHTML = `<span class="w-2 h-2 mr-2 rounded-full bg-green-500 animate-pulse"></span> ${window.t('active', 'Active')} (${d.seconds_ago !== null ? d.seconds_ago + 's' : ''})`;
            } else {
                badge.className = 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-300';
                badge.innerHTML = `<span class="w-2 h-2 mr-2 rounded-full bg-gray-400"></span> ${window.t('passive', 'Offline')}`;
            }
            document.getElementById('worker-last-beat').textContent = d.last_beat || window.t('none', 'None');
            document.getElementById('worker-interval').textContent = d.interval + 's';
            document.getElementById('worker-status-text').textContent = d.status_text || '-';
        }
    } catch (e) {}
}

async function runWorkerOnce() {
    const btn = document.getElementById('btn-run-worker-once');
    const resultBox = document.getElementById('worker-action-result');
    btn.disabled = true;
    btn.textContent = '⏳ ...';
    resultBox.textContent = '';

    try {
        const res = await fetch('/actions/worker_control.php', { method: 'POST' });
        const json = await res.json();
        resultBox.textContent = json.message || (json.success ? 'Done' : 'Error');
        loadWorkerStatus();
    } catch (e) {
        resultBox.textContent = 'Error: ' + e.message;
    } finally {
        btn.disabled = false;
        btn.textContent = window.t('run_worker_once', '⚡ Run Cycle Now (Run Once)');
    }
}

async function loadApiKeys() {
    try {
        const res = await fetch('/actions/api_keys.php?action=list');
        const json = await res.json();
        const tbody = document.getElementById('api-keys-table');
        if (!json.success || !json.data || json.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-3 py-3 text-center text-gray-400">No API keys found.</td></tr>';
            return;
        }

        tbody.innerHTML = json.data.map(k => `
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 font-medium text-gray-800">${escapeHtml(k.name)}</td>
                <td class="px-3 py-2 font-mono text-gray-600 flex items-center gap-1">
                    <span>${escapeHtml(k.key_value.substring(0, 14))}...</span>
                    <button onclick="copyToClipboard('${escapeHtml(k.key_value)}')" class="text-blue-500 hover:text-blue-700 text-xs px-1 py-0.5 bg-blue-50 rounded" title="Copy">📋</button>
                </td>
                <td class="px-3 py-2">
                    <button onclick="toggleApiKey(${k.id}, ${k.is_active ? 0 : 1})" class="px-2 py-0.5 rounded-full text-xs font-semibold ${k.is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-200 text-gray-600 hover:bg-gray-300'}">
                        ${k.is_active ? window.t('active', 'Active') : window.t('passive', 'Inactive')}
                    </button>
                </td>
                <td class="px-3 py-2 text-right">
                    <button onclick="deleteApiKey(${k.id})" class="text-red-500 hover:text-red-700 font-semibold text-xs">${window.t('delete', 'Delete')}</button>
                </td>
            </tr>
        `).join('');

    } catch (e) {
        document.getElementById('api-keys-table').innerHTML = '<tr><td colspan="4" class="px-3 py-3 text-center text-red-400">Error loading API keys.</td></tr>';
    }
}

async function createApiKey() {
    const name = document.getElementById('new-key-name').value.trim();
    const rate = document.getElementById('new-key-rate').value;
    const custom = document.getElementById('new-key-custom').value.trim();

    if (!name) { alert('Please enter key name'); return; }

    const fd = new FormData();
    fd.append('action', 'create');
    fd.append('name', name);
    fd.append('rate_limit', rate);
    if (custom) fd.append('custom_key', custom);

    const res = await fetch('/actions/api_keys.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
        document.getElementById('new-key-name').value = '';
        document.getElementById('new-key-custom').value = '';
        document.getElementById('create-key-box').classList.add('hidden');
        document.getElementById('key-result').textContent = 'Key created: ' + json.data.key_value;
        loadApiKeys();
    } else {
        alert(json.error || 'Error creating key');
    }
}

async function toggleApiKey(id, status) {
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    fd.append('is_active', status);
    await fetch('/actions/api_keys.php', { method: 'POST', body: fd });
    loadApiKeys();
}

async function deleteApiKey(id) {
    if (!confirm('Are you sure you want to delete this key?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    await fetch('/actions/api_keys.php', { method: 'POST', body: fd });
    loadApiKeys();
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard: ' + text);
    });
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function changePassword() {
    const current = document.getElementById('current-password').value;
    const next = document.getElementById('new-password').value;
    const confirm = document.getElementById('confirm-password').value;
    const resultBox = document.getElementById('password-result');

    if (!current || !next || !confirm) {
        resultBox.textContent = 'Please fill in all fields.';
        resultBox.className = 'text-xs text-red-600 mt-2';
        return;
    }

    if (next !== confirm) {
        resultBox.textContent = 'Passwords do not match.';
        resultBox.className = 'text-xs text-red-600 mt-2';
        return;
    }

    const fd = new FormData();
    fd.append('current_password', current);
    fd.append('new_password', next);
    fd.append('confirm_password', confirm);

    const res = await fetch('/actions/change_password.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
        resultBox.textContent = json.message || 'Password updated successfully.';
        resultBox.className = 'text-xs text-green-600 mt-2';
        document.getElementById('current-password').value = '';
        document.getElementById('new-password').value = '';
        document.getElementById('confirm-password').value = '';
    } else {
        resultBox.textContent = json.error || 'Error updating password.';
        resultBox.className = 'text-xs text-red-600 mt-2';
    }
}

async function loadSettings(){
    const res = await fetch('/actions/get_settings.php');
    const json = await res.json();
    if (json.success) {
        document.getElementById('raw-days').value = json.data.raw_retention_days;
        document.getElementById('agg-days').value = json.data.agg_retention_days;
    }
}

async function saveSettings(){
    const raw = parseInt(document.getElementById('raw-days').value, 10);
    const agg = parseInt(document.getElementById('agg-days').value, 10);
    const formData = new FormData();
    formData.append('raw_retention_days', raw);
    formData.append('agg_retention_days', agg);
    const res = await fetch('/actions/save_settings.php', { method: 'POST', body: formData });
    const json = await res.json();
    document.getElementById('retention-result').textContent = json.message || 'Saved';
}

async function runCleanup(){
    const res = await fetch('/actions/run_cleanup.php');
    const json = await res.json();
    alert(json.message || 'Cleanup complete');
}

async function clearCache(){
    fetch('/actions/clear_cache.php').then(r=>r.text()).then(t=>{
        document.getElementById('cache-result').textContent = 'Cache cleared.';
    }).catch(()=>{
        document.getElementById('cache-result').textContent = 'Error clearing cache';
    });
}

async function loadExternalApi(){
    const res = await fetch('/actions/get_external_api.php');
    const json = await res.json();
    if (json.success) {
        document.getElementById('external-api-url').value = json.data.external_api_url || 'http://localhost:8081/api/v1/public/events.php';
        document.getElementById('external-api-token').value = json.data.external_api_token || 'test_key';
        document.getElementById('external-api-header').value = json.data.external_api_header_name || 'X-API-Key';
        document.getElementById('external-api-insecure').checked = !!(json.data.external_api_insecure);
    }
}

async function saveExternalApi(){
    const fd = new FormData();
    fd.append('external_api_url', document.getElementById('external-api-url').value);
    fd.append('external_api_token', document.getElementById('external-api-token').value);
    fd.append('external_api_header_name', document.getElementById('external-api-header').value);
    fd.append('external_api_insecure', document.getElementById('external-api-insecure').checked ? '1' : '0');
    const res = await fetch('/actions/save_external_api.php', { method: 'POST', body: fd });
    const json = await res.json();
    document.getElementById('external-api-result').textContent = json.message || 'Saved';
}

async function testExternalApi(){
    const url = new URL('/actions/test_external_api.php', window.location.origin);
    url.searchParams.append('limit', '50');
    if (document.getElementById('external-api-insecure').checked) { url.searchParams.append('insecure', '1'); }
    const res = await fetch(url);
    const ct = res.headers.get('content-type') || '';
    if (ct.includes('application/json')) {
        const json = await res.json().catch(()=>null);
        document.getElementById('external-api-result').textContent = json && json.success ? (json.message + ' (count: ' + (json.data?.count ?? '-') + ')') : ((json && json.error) || 'Error');
    } else {
        const text = await res.text();
        document.getElementById('external-api-result').textContent = text || 'Empty response';
    }
}

async function importManualJson(){
    try {
        let txt = document.getElementById('manual-json').value || '';
        if (!txt.trim()) { document.getElementById('manual-import-result').textContent = 'Empty payload'; return; }
        txt = txt.trim();
        if (txt.startsWith('```')) { txt = txt.replace(/^```[\s\S]*?\n/, '').replace(/\n```$/, '').trim(); }
        let payload = null;
        try { payload = JSON.parse(txt); } catch(e) { payload = null; }
        if (!payload || typeof payload !== 'object') { document.getElementById('manual-import-result').textContent = 'Invalid JSON payload'; return; }
        const res = await fetch('/actions/ingest_bridge.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        const text = await res.text();
        let json = null;
        try { json = JSON.parse(text); } catch(e) { json = null; }
        const msg = json && json.success ? (json.message + ' (inserted: ' + (json.data?.inserted_events ?? '-') + ', buckets: ' + (json.data?.processed_buckets ?? '-') + ')') : ((json && json.error) || 'Error');
        document.getElementById('manual-import-result').textContent = msg;
    } catch (e) {
        document.getElementById('manual-import-result').textContent = 'Error: ' + (e?.message || e);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
