<?php
require_once 'auth.php';
require_once 'database_pdo.php';
require_once 'i18n.php';

\Temporal\Auth::getInstance()->requireLogin();
$current_page = 'logs';
include 'includes/header.php';
?>

<div class="px-4 py-6 sm:px-0">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 pb-4 border-b border-gray-200 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-1"><?php echo __('logs_title'); ?></h1>
            <p class="text-gray-600 text-sm"><?php echo __('logs_subtitle'); ?></p>
        </div>
        
        <div class="flex items-center gap-2">
            <!-- Auto-Refresh Toggle -->
            <label class="inline-flex items-center cursor-pointer bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm text-xs font-semibold text-gray-700">
                <input type="checkbox" id="auto-refresh-toggle" class="sr-only peer">
                <div class="w-7 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[3px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-blue-600 relative mr-2"></div>
                <span>Auto-Refresh (8s)</span>
            </label>

            <!-- Clear Logs Button -->
            <button type="button" id="btn-clear-logs" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm transition">
                <?php echo __('clear_all_logs'); ?>
            </button>
        </div>
    </div>

    <!-- Filters & Search Bar Card -->
    <div class="bg-white rounded-xl shadow-md p-5 mb-6 border border-gray-100 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
            <!-- Search Bar -->
            <div>
                <label class="block font-semibold text-gray-700 mb-1"><?php echo __('search_logs'); ?></label>
                <div class="relative">
                    <input type="text" id="log-search" placeholder="<?php echo __('search_logs'); ?>" class="w-full pl-8 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <span class="absolute left-2.5 top-2.5 text-gray-400">🔍</span>
                </div>
            </div>

            <!-- Job Type Filter -->
            <div>
                <label class="block font-semibold text-gray-700 mb-1"><?php echo __('filter_job_type'); ?></label>
                <select id="log-job-type" class="w-full px-3 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value=""><?php echo __('all_jobs'); ?></option>
                    <option value="worker">⚡ worker</option>
                    <option value="aggregation">📊 aggregation</option>
                    <option value="data_puller">📥 data_puller</option>
                    <option value="derive_rollups">🔄 derive_rollups</option>
                    <option value="alerting">🚨 alerting</option>
                    <option value="cleanup">🧹 cleanup</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block font-semibold text-gray-700 mb-1"><?php echo __('filter_status'); ?></label>
                <select id="log-status" class="w-full px-3 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value=""><?php echo __('all_statuses'); ?></option>
                    <option value="success">🟢 success</option>
                    <option value="failed">🔴 failed</option>
                    <option value="running">🟡 running</option>
                </select>
            </div>

            <!-- Per Page -->
            <div>
                <label class="block font-semibold text-gray-700 mb-1"><?php echo __('per_page'); ?></label>
                <select id="log-per-page" class="w-full px-3 py-2 border rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="15">15 <?php echo __('per_page'); ?></option>
                    <option value="30" selected>30 <?php echo __('per_page'); ?></option>
                    <option value="50">50 <?php echo __('per_page'); ?></option>
                    <option value="100">100 <?php echo __('per_page'); ?></option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-gray-100 text-xs">
            <div id="logs-count-info" class="font-medium text-gray-500">
                Loading logs...
            </div>
            <button type="button" id="btn-refresh-logs" class="bg-gray-100 hover:bg-gray-200 text-gray-700 border px-3 py-1.5 rounded-md font-semibold transition">
                🔄 <?php echo __('refresh'); ?>
            </button>
        </div>
    </div>

    <!-- Logs Table Card -->
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50 text-gray-500 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left w-36"><?php echo __('log_date'); ?></th>
                        <th class="px-4 py-3 text-left w-40"><?php echo __('log_job_type'); ?></th>
                        <th class="px-4 py-3 text-left w-24"><?php echo __('log_status'); ?></th>
                        <th class="px-4 py-3 text-left"><?php echo __('log_message'); ?></th>
                        <th class="px-4 py-3 text-right w-20">Action</th>
                    </tr>
                </thead>
                <tbody id="logs-tbody" class="bg-white divide-y divide-gray-100">
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Loading system logs...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
            <div id="pagination-info" class="text-gray-600 font-medium">
                Page 1 of 1
            </div>

            <div class="flex items-center gap-1" id="pagination-controls">
                <!-- Page buttons injected via JS -->
            </div>
        </div>
    </div>
</div>

<!-- Modal for full log detail -->
<div id="log-detail-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 space-y-4 border">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <span>📄</span> Log Details
            </h3>
            <button onclick="closeLogModal()" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
        </div>
        <div class="space-y-2 text-xs font-mono">
            <div><span class="font-bold text-gray-600">Timestamp:</span> <span id="modal-log-time" class="text-gray-900"></span></div>
            <div><span class="font-bold text-gray-600">Job Type:</span> <span id="modal-log-type" class="text-gray-900 font-bold"></span></div>
            <div><span class="font-bold text-gray-600">Status:</span> <span id="modal-log-status"></span></div>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Message Content:</label>
            <pre id="modal-log-msg" class="bg-gray-900 text-gray-100 p-3 rounded-lg text-xs font-mono max-h-60 overflow-auto whitespace-pre-wrap"></pre>
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t">
            <button onclick="copyModalMsg()" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md font-semibold text-xs border">
                📋 Copy Message
            </button>
            <button onclick="closeLogModal()" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-semibold text-xs shadow-sm">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let autoRefreshTimer = null;
let searchDebounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    // Read query params from URL if present
    const params = new URLSearchParams(window.location.search);
    if (params.has('page')) currentPage = parseInt(params.get('page'), 10) || 1;
    if (params.has('job_type')) document.getElementById('log-job-type').value = params.get('job_type');
    if (params.has('status')) document.getElementById('log-status').value = params.get('status');
    if (params.has('per_page')) document.getElementById('log-per-page').value = params.get('per_page');
    if (params.has('search')) document.getElementById('log-search').value = params.get('search');

    fetchLogs();
    setupEventListeners();
});

function setupEventListeners() {
    // Search with debounce
    document.getElementById('log-search').addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            currentPage = 1;
            fetchLogs();
        }, 300);
    });

    // Dropdowns
    ['log-job-type', 'log-status', 'log-per-page'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => {
            currentPage = 1;
            fetchLogs();
        });
    });

    // Refresh button
    document.getElementById('btn-refresh-logs').addEventListener('click', () => fetchLogs());

    // Auto-refresh toggle
    document.getElementById('auto-refresh-toggle').addEventListener('change', function() {
        if (this.checked) {
            autoRefreshTimer = setInterval(fetchLogs, 8000);
        } else {
            clearInterval(autoRefreshTimer);
            autoRefreshTimer = null;
        }
    });

    // Clear logs button
    document.getElementById('btn-clear-logs').addEventListener('click', clearAllLogs);
}

async function fetchLogs() {
    const search = document.getElementById('log-search').value.trim();
    const jobType = document.getElementById('log-job-type').value;
    const status = document.getElementById('log-status').value;
    const perPage = document.getElementById('log-per-page').value;

    const url = new URL('/actions/get_logs.php', window.location.origin);
    url.searchParams.append('page', currentPage);
    url.searchParams.append('per_page', perPage);
    if (search) url.searchParams.append('search', search);
    if (jobType) url.searchParams.append('job_type', jobType);
    if (status) url.searchParams.append('status', status);

    // Update browser URL without reload
    window.history.replaceState({}, '', url.search);

    const tbody = document.getElementById('logs-tbody');
    try {
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success || !json.data) {
            tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-red-500">${escapeHtml(json.error || 'Error loading logs')}</td></tr>`;
            return;
        }

        const { logs, distinct_job_types, pagination } = json.data;

        // Populate distinct job types if new types exist
        populateJobTypes(distinct_job_types, jobType);

        // Update count info
        const startItem = pagination.total_items > 0 ? (pagination.current_page - 1) * pagination.per_page + 1 : 0;
        const endItem = Math.min(pagination.current_page * pagination.per_page, pagination.total_items);
        document.getElementById('logs-count-info').textContent = 
            `Showing ${startItem} - ${endItem} of ${formatNumber(pagination.total_items)} entries`;

        if (!logs || logs.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 font-sans">${window.t('no_logs', 'No job logs match the selected filters.')}</td></tr>`;
            renderPagination(pagination);
            return;
        }

        tbody.innerHTML = logs.map(row => {
            const statusColor = row.status === 'success' ? 'bg-green-100 text-green-800' : (row.status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800');
            const icon = getJobIcon(row.job_type);

            return `
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-2.5 font-mono text-gray-500 whitespace-nowrap">${escapeHtml(row.created_at)}</td>
                    <td class="px-4 py-2.5 font-semibold text-gray-800 flex items-center gap-1.5">
                        <span>${icon}</span>
                        <span>${escapeHtml(row.job_type)}</span>
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="px-2.5 py-0.5 rounded-full font-bold text-[11px] uppercase ${statusColor}">
                            ${escapeHtml(row.status)}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-gray-700 font-mono text-xs truncate max-w-md">
                        ${escapeHtml(row.message)}
                    </td>
                    <td class="px-4 py-2.5 text-right">
                        <button onclick='openLogModal(${JSON.stringify(row)})' class="text-blue-600 hover:text-blue-800 font-semibold text-[11px] bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                            View
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination(pagination);

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-red-500">Error: ${e.message}</td></tr>`;
    }
}

function getJobIcon(jobType) {
    switch (jobType) {
        case 'worker': return '⚡';
        case 'aggregation': return '📊';
        case 'data_puller': return '📥';
        case 'derive_rollups': return '🔄';
        case 'alerting': return '🚨';
        case 'cleanup': return '🧹';
        default: return '⚙️';
    }
}

function populateJobTypes(types, selected) {
    if (!types || types.length === 0) return;
    const select = document.getElementById('log-job-type');
    const existing = Array.from(select.options).map(o => o.value);
    types.forEach(t => {
        if (!existing.includes(t)) {
            const opt = document.createElement('option');
            opt.value = t;
            opt.textContent = `${getJobIcon(t)} ${t}`;
            select.appendChild(opt);
        }
    });
}

function renderPagination(p) {
    const info = document.getElementById('pagination-info');
    info.textContent = `Page ${p.current_page} of ${p.total_pages}`;

    const wrap = document.getElementById('pagination-controls');
    wrap.innerHTML = '';

    if (p.total_pages <= 1) return;

    // First button
    const firstBtn = document.createElement('button');
    firstBtn.textContent = '« First';
    firstBtn.disabled = !p.has_prev;
    firstBtn.className = `px-2.5 py-1 rounded border text-xs font-semibold ${p.has_prev ? 'bg-white hover:bg-gray-100 text-gray-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}`;
    firstBtn.onclick = () => { currentPage = 1; fetchLogs(); };
    wrap.appendChild(firstBtn);

    // Prev button
    const prevBtn = document.createElement('button');
    prevBtn.textContent = '‹ Prev';
    prevBtn.disabled = !p.has_prev;
    prevBtn.className = `px-2.5 py-1 rounded border text-xs font-semibold ${p.has_prev ? 'bg-white hover:bg-gray-100 text-gray-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}`;
    prevBtn.onclick = () => { currentPage--; fetchLogs(); };
    wrap.appendChild(prevBtn);

    // Page Number buttons (window of 5 pages)
    const startPage = Math.max(1, p.current_page - 2);
    const endPage = Math.min(p.total_pages, p.current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        const isActive = (i === p.current_page);
        btn.className = `px-3 py-1 rounded border text-xs font-bold ${isActive ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white hover:bg-gray-100 text-gray-700'}`;
        btn.onclick = () => { currentPage = i; fetchLogs(); };
        wrap.appendChild(btn);
    }

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.textContent = 'Next ›';
    nextBtn.disabled = !p.has_next;
    nextBtn.className = `px-2.5 py-1 rounded border text-xs font-semibold ${p.has_next ? 'bg-white hover:bg-gray-100 text-gray-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}`;
    nextBtn.onclick = () => { currentPage++; fetchLogs(); };
    wrap.appendChild(nextBtn);

    // Last button
    const lastBtn = document.createElement('button');
    lastBtn.textContent = 'Last »';
    lastBtn.disabled = !p.has_next;
    lastBtn.className = `px-2.5 py-1 rounded border text-xs font-semibold ${p.has_next ? 'bg-white hover:bg-gray-100 text-gray-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed'}`;
    lastBtn.onclick = () => { currentPage = p.total_pages; fetchLogs(); };
    wrap.appendChild(lastBtn);
}

function openLogModal(row) {
    document.getElementById('modal-log-time').textContent = row.created_at;
    document.getElementById('modal-log-type').textContent = row.job_type;
    const statusEl = document.getElementById('modal-log-status');
    statusEl.textContent = row.status.toUpperCase();
    statusEl.className = row.status === 'success' ? 'font-bold text-green-600' : 'font-bold text-red-600';
    document.getElementById('modal-log-msg').textContent = row.message;

    document.getElementById('log-detail-modal').classList.remove('hidden');
}

function closeLogModal() {
    document.getElementById('log-detail-modal').classList.add('hidden');
}

function copyModalMsg() {
    const txt = document.getElementById('modal-log-msg').textContent;
    navigator.clipboard.writeText(txt).then(() => alert('Message copied to clipboard!'));
}

async function clearAllLogs() {
    if (!confirm(window.t('clear_logs_confirm', 'Are you sure you want to delete all system job logs?'))) return;

    try {
        const res = await fetch('/actions/clear_logs.php', { method: 'POST' });
        const json = await res.json();
        if (json.success) {
            currentPage = 1;
            fetchLogs();
            alert(json.message || 'Logs cleared.');
        } else {
            alert('Error: ' + (json.error || 'Failed to clear logs'));
        }
    } catch (e) {
        alert('Error: ' + e.message);
    }
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php include 'includes/footer.php'; ?>
