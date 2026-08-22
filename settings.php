<?php
require_once 'auth.php';
require_once 'database_pdo.php';
require_once 'i18n.php';

// Require login
$auth = \Temporal\Auth::getInstance();
$auth->requireLogin();
$isAdmin = $auth->isAdmin();
$currentUserRole = $auth->getRole();

$current_page = 'settings';
include 'includes/header.php';
?>

<div class="px-4 py-6 sm:px-0">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo __('settings_title'); ?></h1>
        <p class="text-gray-600"><?php echo __('settings_subtitle'); ?></p>
    </div>

    <?php if (!$isAdmin): ?>
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-center gap-2 font-medium mb-6 shadow-sm">
            <span class="text-base">ℹ️</span>
            <span><?php echo __('viewer_restricted_notice'); ?></span>
        </div>
    <?php endif; ?>

    <div class="space-y-6">
        <!-- User & Role Management Card (RBAC) -->
        <?php if ($isAdmin): ?>
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-gray-100 mb-4 gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <span>👥</span> <?php echo __('users_title'); ?>
                    </h2>
                    <p class="text-xs text-gray-500"><?php echo __('users_desc'); ?></p>
                </div>
                <button type="button" id="btn-show-create-user" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium px-3 py-1.5 rounded-md shadow-sm transition">
                    <?php echo __('create_new_user'); ?>
                </button>
            </div>

            <!-- Create User Form (Hidden by default) -->
            <div id="create-user-box" class="hidden mb-4 p-4 bg-purple-50/60 border border-purple-200 rounded-lg">
                <h3 class="text-sm font-semibold text-purple-900 mb-3"><?php echo __('create_new_user'); ?></h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1"><?php echo __('username'); ?></label>
                        <input id="new-username" type="text" placeholder="e.g. data_analyst_01" class="w-full px-3 py-1.5 border rounded-md">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1"><?php echo __('password'); ?></label>
                        <input id="new-user-password" type="password" placeholder="Min 6 characters" class="w-full px-3 py-1.5 border rounded-md">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1"><?php echo __('user_role'); ?></label>
                        <select id="new-user-role" class="w-full px-3 py-1.5 border rounded-md bg-white">
                            <option value="viewer" selected><?php echo __('role_viewer'); ?></option>
                            <option value="analyst"><?php echo __('role_analyst'); ?></option>
                            <option value="admin"><?php echo __('role_admin'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="flex space-x-2 pt-3">
                    <button type="button" id="btn-save-user" class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium px-4 py-2 rounded-md"><?php echo __('create'); ?></button>
                    <button type="button" id="btn-cancel-user" class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-xs font-medium px-3 py-2 rounded-md"><?php echo __('cancel'); ?></button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('username'); ?></th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('user_role'); ?></th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('user_created'); ?></th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('user_last_login'); ?></th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500"><?php echo __('key_table_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="users-table" class="bg-white divide-y divide-gray-100">
                        <tr><td colspan="5" class="px-3 py-3 text-center text-gray-400">Loading user accounts...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="user-result" class="text-xs text-gray-600 mt-2"></div>
        </div>
        <?php endif; ?>

        <!-- Worker Daemon & Background Automation -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-gray-100 gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900"><?php echo __('worker_title'); ?></h2>
                    <p class="text-xs text-gray-500"><?php echo __('worker_desc'); ?></p>
                </div>
                <div class="flex items-center space-x-2">
                    <span id="settings-worker-badge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                        <span class="w-2 h-2 mr-2 rounded-full bg-gray-400"></span>
                        Loading...
                    </span>
                    <?php if ($isAdmin): ?>
                    <button type="button" id="btn-run-worker-once" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1.5 rounded-md shadow-sm transition">
                        ⚡ <?php echo __('run_once_btn'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="p-3 bg-gray-50 rounded-md border">
                    <div class="text-gray-500 font-medium"><?php echo __('worker_last_beat'); ?></div>
                    <div id="worker-last-beat" class="text-sm font-bold text-gray-800 mt-1 font-mono">-</div>
                </div>
                <div class="p-3 bg-gray-50 rounded-md border">
                    <div class="text-gray-500 font-medium"><?php echo __('worker_interval'); ?></div>
                    <div id="worker-interval" class="text-sm font-bold text-gray-800 mt-1">10s</div>
                </div>
                <div class="p-3 bg-gray-50 rounded-md border">
                    <div class="text-gray-500 font-medium"><?php echo __('worker_last_status'); ?></div>
                    <div id="worker-last-status" class="text-sm font-bold text-gray-800 mt-1 font-mono truncate">-</div>
                </div>
            </div>

            <div class="mt-4 p-3 bg-blue-50/50 rounded-md border border-blue-100 text-xs text-gray-700">
                <span class="font-semibold text-blue-900"><?php echo __('cli_daemon_command'); ?>:</span>
                <code class="ml-1 bg-white px-2 py-0.5 rounded border text-blue-700 font-mono">php worker.php --interval=10 --simulate</code>
            </div>
            <div id="worker-action-result" class="text-xs text-gray-600 mt-2"></div>
        </div>

        <!-- API Keys Management -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-gray-100 mb-4 gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900"><?php echo __('api_keys_title'); ?></h2>
                    <p class="text-xs text-gray-500"><?php echo __('api_keys_desc'); ?></p>
                </div>
                <?php if ($isAdmin): ?>
                <button type="button" id="btn-show-create-key" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1.5 rounded-md shadow-sm">
                    <?php echo __('create_new_key'); ?>
                </button>
                <?php endif; ?>
            </div>

            <!-- Create Key Form (Hidden by default) -->
            <?php if ($isAdmin): ?>
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
            <?php endif; ?>

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

        <!-- Alerting & Webhooks Management Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-gray-100 mb-4 gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <span>🚨</span> <?php echo __('alerting_title'); ?>
                    </h2>
                    <p class="text-xs text-gray-500"><?php echo __('alerting_desc'); ?></p>
                </div>
                <?php if ($isAdmin): ?>
                <button type="button" id="btn-show-create-alert" class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-3 py-1.5 rounded-md shadow-sm transition">
                    <?php echo __('create_new_alert'); ?>
                </button>
                <?php endif; ?>
            </div>

            <!-- Create Alert Form (Hidden by default) -->
            <?php if ($isAdmin): ?>
            <div id="create-alert-box" class="hidden mb-4 p-4 bg-red-50/60 border border-red-200 rounded-lg">
                <h3 class="text-sm font-semibold text-red-900 mb-3"><?php echo __('create_new_alert'); ?></h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1"><?php echo __('rule_name'); ?></label>
                        <input id="alert-name" type="text" placeholder="e.g. Critical Spike > 500 events" class="w-full px-3 py-1.5 border rounded-md">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1"><?php echo __('rule_type'); ?></label>
                        <select id="alert-rule-type" class="w-full px-3 py-1.5 border rounded-md bg-white">
                            <option value="volume_threshold" selected><?php echo __('volume_threshold'); ?></option>
                            <option value="anomaly_spike"><?php echo __('anomaly_spike'); ?></option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1"><?php echo __('threshold'); ?></label>
                        <input id="alert-threshold" type="number" value="500" class="w-full px-3 py-1.5 border rounded-md">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1"><?php echo __('cooldown_minutes'); ?></label>
                        <input id="alert-cooldown" type="number" value="5" class="w-full px-3 py-1.5 border rounded-md">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-medium text-gray-700 mb-1"><?php echo __('webhook_url'); ?></label>
                        <input id="alert-webhook-url" type="text" placeholder="https://discord.com/api/webhooks/... or https://hooks.slack.com/services/..." class="w-full px-3 py-1.5 border rounded-md font-mono">
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1"><?php echo __('webhook_format'); ?></label>
                        <select id="alert-format" class="w-full px-3 py-1.5 border rounded-md bg-white">
                            <option value="generic_json" selected>Generic JSON</option>
                            <option value="discord">Discord Webhook Embed</option>
                            <option value="slack">Slack Webhook Block</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium text-gray-700 mb-1"><?php echo __('bucket_size'); ?></label>
                        <select id="alert-bucket-size" class="w-full px-3 py-1.5 border rounded-md bg-white">
                            <option value="1m" selected>1m</option>
                            <option value="5m">5m</option>
                            <option value="15m">15m</option>
                            <option value="1h">1h</option>
                        </select>
                    </div>
                </div>
                <div class="flex space-x-2 pt-3">
                    <button type="button" id="btn-save-alert" class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-4 py-2 rounded-md"><?php echo __('create'); ?></button>
                    <button type="button" id="btn-cancel-alert" class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-xs font-medium px-3 py-2 rounded-md"><?php echo __('cancel'); ?></button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Alert Rules Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('rule_name'); ?></th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('rule_type'); ?></th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('threshold'); ?></th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('last_triggered'); ?></th>
                            <th class="px-3 py-2 text-left font-medium text-gray-500"><?php echo __('key_table_status'); ?></th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500"><?php echo __('key_table_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="alert-rules-table" class="bg-white divide-y divide-gray-100">
                        <tr><td colspan="6" class="px-3 py-3 text-center text-gray-400">Loading alert rules...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="alert-result" class="text-xs text-gray-600 mt-2"></div>
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
                    <input id="raw-days" type="number" value="60" <?php echo !$isAdmin ? 'disabled' : ''; ?> class="mt-1 w-full px-3 py-2 border rounded-md <?php echo !$isAdmin ? 'bg-gray-100 cursor-not-allowed' : ''; ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700"><?php echo __('agg_retention'); ?></label>
                    <input id="agg-days" type="number" value="365" <?php echo !$isAdmin ? 'disabled' : ''; ?> class="mt-1 w-full px-3 py-2 border rounded-md <?php echo !$isAdmin ? 'bg-gray-100 cursor-not-allowed' : ''; ?>">
                </div>
                <?php if ($isAdmin): ?>
                <div class="flex space-x-2">
                    <button type="button" id="save-retention" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('save'); ?></button>
                    <button type="button" id="run-cleanup" class="bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('run_cleanup'); ?></button>
                </div>
                <?php endif; ?>
            </form>
            <div id="retention-result" class="text-sm text-gray-600 mt-3"></div>
        </div>

        <!-- Cache Management -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('cache_title'); ?></h2>
            <div class="space-y-3">
                <p class="text-sm text-gray-600"><?php echo __('cache_desc'); ?></p>
                <?php if ($isAdmin): ?>
                <button id="clear-cache" class="bg-gray-600 hover:bg-gray-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('clear_cache'); ?></button>
                <?php endif; ?>
                <div id="cache-result" class="text-sm text-gray-600"></div>
            </div>
        </div>

        <!-- External Live API Configuration -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('external_api_title'); ?></h2>
            <form id="external-api-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">API URL</label>
                    <input id="external-api-url" type="text" <?php echo !$isAdmin ? 'disabled' : ''; ?> class="mt-1 w-full px-3 py-2 border rounded-md <?php echo !$isAdmin ? 'bg-gray-100 cursor-not-allowed' : ''; ?>" placeholder="http://localhost:8081/api/v1/public/events.php">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo __('header_name'); ?></label>
                        <input id="external-api-header" type="text" <?php echo !$isAdmin ? 'disabled' : ''; ?> class="mt-1 w-full px-3 py-2 border rounded-md <?php echo !$isAdmin ? 'bg-gray-100 cursor-not-allowed' : ''; ?>" value="X-API-Key">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo __('token_optional'); ?></label>
                        <input id="external-api-token" type="text" <?php echo !$isAdmin ? 'disabled' : ''; ?> class="mt-1 w-full px-3 py-2 border rounded-md <?php echo !$isAdmin ? 'bg-gray-100 cursor-not-allowed' : ''; ?>" placeholder="test_key">
                    </div>
                </div>
                <div>
                    <label class="inline-flex items-center">
                        <input id="external-api-insecure" type="checkbox" <?php echo !$isAdmin ? 'disabled' : ''; ?> class="mr-2">
                        <span class="text-sm text-gray-700"><?php echo __('insecure_ssl'); ?></span>
                    </label>
                </div>
                <?php if ($isAdmin): ?>
                <div class="space-x-2 pt-1">
                    <button type="button" id="save-external-api" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('save'); ?></button>
                    <button type="button" id="test-external-api" class="bg-gray-600 hover:bg-gray-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('test_connection'); ?></button>
                </div>
                <?php endif; ?>
            </form>
            <div id="external-api-result" class="text-sm text-gray-600 mt-3"></div>
        </div>

        <!-- Manual JSON Import -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo __('manual_json_title'); ?></h2>
            <p class="text-sm text-gray-600 mb-2"><?php echo __('manual_json_desc'); ?></p>
            <textarea id="manual-json" <?php echo !$isAdmin ? 'disabled' : ''; ?> class="w-full h-32 px-3 py-2 border rounded-md font-mono text-xs <?php echo !$isAdmin ? 'bg-gray-100 cursor-not-allowed' : ''; ?>" placeholder='[{"type":"vehicle_movement","source":"sensor_01","timestamp":1733872741}]'></textarea>
            <?php if ($isAdmin): ?>
            <div class="mt-3">
                <button type="button" id="import-manual-json" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-md"><?php echo __('import_and_aggregate'); ?></button>
            </div>
            <?php endif; ?>
            <div id="manual-import-result" class="text-sm text-gray-600 mt-3"></div>
        </div>
    </div>
</div>

<script>
const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;

document.addEventListener('DOMContentLoaded', () => {
    loadSettings();
    loadExternalApi();
    loadWorkerStatus();
    loadApiKeys();
    loadAlertRules();

    if (IS_ADMIN) {
        loadUsers();
    }

    setInterval(loadWorkerStatus, 8000);

    const saveRetentionBtn = document.getElementById('save-retention');
    if (saveRetentionBtn) saveRetentionBtn.addEventListener('click', saveSettings);

    const runCleanupBtn = document.getElementById('run-cleanup');
    if (runCleanupBtn) runCleanupBtn.addEventListener('click', runCleanup);

    const clearCacheBtn = document.getElementById('clear-cache');
    if (clearCacheBtn) clearCacheBtn.addEventListener('click', clearCache);

    const saveExtApiBtn = document.getElementById('save-external-api');
    if (saveExtApiBtn) saveExtApiBtn.addEventListener('click', saveExternalApi);

    const testExtApiBtn = document.getElementById('test-external-api');
    if (testExtApiBtn) testExtApiBtn.addEventListener('click', testExternalApi);

    const importJsonBtn = document.getElementById('import-manual-json');
    if (importJsonBtn) importJsonBtn.addEventListener('click', importManualJson);

    const runWorkerBtn = document.getElementById('btn-run-worker-once');
    if (runWorkerBtn) runWorkerBtn.addEventListener('click', runWorkerOnce);

    document.getElementById('btn-change-password').addEventListener('click', changePassword);

    // API Keys UI
    const showKeyBtn = document.getElementById('btn-show-create-key');
    if (showKeyBtn) {
        showKeyBtn.addEventListener('click', () => {
            document.getElementById('create-key-box').classList.toggle('hidden');
        });
        document.getElementById('btn-cancel-key').addEventListener('click', () => {
            document.getElementById('create-key-box').classList.add('hidden');
        });
        document.getElementById('btn-save-key').addEventListener('click', createApiKey);
    }

    // Alerts UI
    const showAlertBtn = document.getElementById('btn-show-create-alert');
    if (showAlertBtn) {
        showAlertBtn.addEventListener('click', () => {
            document.getElementById('create-alert-box').classList.toggle('hidden');
        });
        document.getElementById('btn-cancel-alert').addEventListener('click', () => {
            document.getElementById('create-alert-box').classList.add('hidden');
        });
        document.getElementById('btn-save-alert').addEventListener('click', createAlertRule);
    }

    // Users UI (RBAC)
    const showUserBtn = document.getElementById('btn-show-create-user');
    if (showUserBtn) {
        showUserBtn.addEventListener('click', () => {
            document.getElementById('create-user-box').classList.toggle('hidden');
        });
        document.getElementById('btn-cancel-user').addEventListener('click', () => {
            document.getElementById('create-user-box').classList.add('hidden');
        });
        document.getElementById('btn-save-user').addEventListener('click', createUser);
    }
});

/* Users & RBAC Management */
async function loadUsers() {
    const tbody = document.getElementById('users-table');
    if (!tbody) return;

    try {
        const res = await fetch('/actions/users.php?action=list');
        const json = await res.json();
        if (!json.success || !json.data || json.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-3 py-3 text-center text-gray-400">No users found.</td></tr>';
            return;
        }

        tbody.innerHTML = json.data.map(u => {
            const roleBadge = u.role === 'admin' 
                ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">Admin</span>'
                : (u.role === 'analyst' 
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Analyst</span>'
                    : '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">Viewer</span>');

            const lastLogin = u.last_login_at ? new Date(u.last_login_at).toLocaleString() : 'Never';
            const created = u.created_at ? new Date(u.created_at).toLocaleDateString() : '-';

            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2.5 font-bold text-gray-900 flex items-center gap-1.5">
                        <span>👤</span>
                        <span>${escapeHtml(u.username)}</span>
                    </td>
                    <td class="px-3 py-2.5">
                        <select onchange="updateUserRole(${u.id}, this.value)" class="text-xs px-2 py-1 border rounded bg-white font-medium">
                            <option value="viewer" ${u.role === 'viewer' ? 'selected' : ''}>Viewer</option>
                            <option value="analyst" ${u.role === 'analyst' ? 'selected' : ''}>Analyst</option>
                            <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Admin</option>
                        </select>
                    </td>
                    <td class="px-3 py-2.5 text-gray-500 font-mono text-[11px]">${created}</td>
                    <td class="px-3 py-2.5 text-gray-500 font-mono text-[11px]">${lastLogin}</td>
                    <td class="px-3 py-2.5 text-right">
                        <button onclick="deleteUserAccount(${u.id})" class="text-red-600 hover:text-red-800 font-semibold text-[11px] bg-red-50 px-2 py-0.5 rounded border border-red-200">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-3 py-3 text-center text-red-500">Error loading users</td></tr>';
    }
}

async function createUser() {
    const username = document.getElementById('new-username').value.trim();
    const password = document.getElementById('new-user-password').value;
    const role = document.getElementById('new-user-role').value;

    if (!username || !password) {
        alert('Please fill in username and password.');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'create');
    fd.append('username', username);
    fd.append('password', password);
    fd.append('role', role);

    const res = await fetch('/actions/users.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
        document.getElementById('create-user-box').classList.add('hidden');
        document.getElementById('new-username').value = '';
        document.getElementById('new-user-password').value = '';
        loadUsers();
    } else {
        alert('Error: ' + (json.error || 'Failed to create user'));
    }
}

async function updateUserRole(id, role) {
    const fd = new FormData();
    fd.append('action', 'update_role');
    fd.append('id', id);
    fd.append('role', role);

    const res = await fetch('/actions/users.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (!json.success) {
        alert('Error: ' + (json.error || 'Failed to update role'));
        loadUsers();
    }
}

async function deleteUserAccount(id) {
    if (!confirm('Are you sure you want to delete this user account?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    const res = await fetch('/actions/users.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
        loadUsers();
    } else {
        alert('Error: ' + (json.error || 'Failed to delete user'));
    }
}

/* Alert Rules Management */
async function loadAlertRules() {
    const tbody = document.getElementById('alert-rules-table');
    try {
        const res = await fetch('/actions/alert_rules.php?action=list');
        const json = await res.json();
        if (!json.success || !json.data || json.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-3 py-4 text-center text-gray-400">No alert rules configured yet.</td></tr>';
            return;
        }

        tbody.innerHTML = json.data.map(rule => {
            const statusBadge = rule.is_active == 1 
                ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">Active</span>'
                : '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">Inactive</span>';

            const typeLabel = rule.rule_type === 'volume_threshold' ? 'Volume Threshold' : 'Anomaly Spike';
            const thresholdLabel = rule.rule_type === 'volume_threshold' ? `${rule.threshold_value} / ${rule.bucket_size}` : `+${rule.threshold_value}% deviation`;
            const lastTrigger = rule.last_triggered_at ? new Date(rule.last_triggered_at).toLocaleString() : 'Never';

            const actionBtns = IS_ADMIN ? `
                <button onclick="testAlertRule(${rule.id})" class="text-blue-600 hover:text-blue-800 font-semibold text-[11px] bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                    🔔 Test
                </button>
                <button onclick="toggleAlertRule(${rule.id}, ${rule.is_active == 1 ? 0 : 1})" class="text-amber-600 hover:text-amber-800 font-semibold text-[11px] bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                    ${rule.is_active == 1 ? 'Disable' : 'Enable'}
                </button>
                <button onclick="deleteAlertRule(${rule.id})" class="text-red-600 hover:text-red-800 font-semibold text-[11px] bg-red-50 px-2 py-0.5 rounded border border-red-200">
                    Delete
                </button>
            ` : `<button onclick="testAlertRule(${rule.id})" class="text-blue-600 hover:text-blue-800 font-semibold text-[11px] bg-blue-50 px-2 py-0.5 rounded border border-blue-200">🔔 Test</button>`;

            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2.5 font-semibold text-gray-900">${escapeHtml(rule.name)}</td>
                    <td class="px-3 py-2.5 text-gray-600">${typeLabel}</td>
                    <td class="px-3 py-2.5 font-mono font-bold text-gray-800">${thresholdLabel}</td>
                    <td class="px-3 py-2.5 text-gray-500 font-mono text-[11px]">${lastTrigger}</td>
                    <td class="px-3 py-2.5">${statusBadge}</td>
                    <td class="px-3 py-2.5 text-right space-x-1.5">
                        ${actionBtns}
                    </td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-3 py-3 text-center text-red-500">Error loading alert rules</td></tr>';
    }
}

async function createAlertRule() {
    const name = document.getElementById('alert-name').value.trim();
    const ruleType = document.getElementById('alert-rule-type').value;
    const threshold = document.getElementById('alert-threshold').value;
    const cooldown = document.getElementById('alert-cooldown').value;
    const webhookUrl = document.getElementById('alert-webhook-url').value.trim();
    const format = document.getElementById('alert-format').value;
    const bucketSize = document.getElementById('alert-bucket-size').value;

    if (!name || !webhookUrl) {
        alert('Please fill in Rule Name and Webhook URL.');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'create');
    fd.append('name', name);
    fd.append('rule_type', ruleType);
    fd.append('threshold_value', threshold);
    fd.append('cooldown_minutes', cooldown);
    fd.append('webhook_url', webhookUrl);
    fd.append('webhook_format', format);
    fd.append('bucket_size', bucketSize);

    const res = await fetch('/actions/alert_rules.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
        document.getElementById('create-alert-box').classList.add('hidden');
        document.getElementById('alert-name').value = '';
        document.getElementById('alert-webhook-url').value = '';
        loadAlertRules();
    } else {
        alert('Error: ' + (json.error || 'Failed to create alert rule'));
    }
}

async function toggleAlertRule(id, newStatus) {
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    fd.append('is_active', newStatus);

    await fetch('/actions/alert_rules.php', { method: 'POST', body: fd });
    loadAlertRules();
}

async function deleteAlertRule(id) {
    if (!confirm('Are you sure you want to delete this alert rule?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    await fetch('/actions/alert_rules.php', { method: 'POST', body: fd });
    loadAlertRules();
}

async function testAlertRule(id) {
    const resBox = document.getElementById('alert-result');
    resBox.textContent = 'Sending test webhook payload...';
    resBox.className = 'text-xs text-blue-600 mt-2 font-semibold';

    try {
        const res = await fetch(`/actions/alert_rules.php?action=test&id=${id}`);
        const json = await res.json();
        if (json.success) {
            resBox.textContent = '✅ ' + json.message;
            resBox.className = 'text-xs text-green-600 mt-2 font-semibold';
        } else {
            resBox.textContent = '❌ ' + json.message;
            resBox.className = 'text-xs text-red-600 mt-2 font-semibold';
        }
    } catch (e) {
        resBox.textContent = '❌ Test request failed: ' + e.message;
        resBox.className = 'text-xs text-red-600 mt-2';
    }
}

async function loadWorkerStatus() {
    try {
        const res = await fetch('/actions/worker_status.php');
        const json = await res.json();
        if (json.success && json.data) {
            const d = json.data;
            const badge = document.getElementById('settings-worker-badge');
            if (d.is_live) {
                badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800';
                badge.innerHTML = `<span class="w-2 h-2 mr-2 rounded-full bg-green-500 animate-pulse"></span>${window.t('active', 'Active')} (${d.seconds_ago !== null ? d.seconds_ago + 's' : ''})`;
            } else {
                badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600';
                badge.innerHTML = `<span class="w-2 h-2 mr-2 rounded-full bg-gray-400"></span>${window.t('worker_badge_offline', 'Offline')}`;
            }

            document.getElementById('worker-last-beat').textContent = d.last_beat ? formatDate(d.last_beat) : 'Never';
            document.getElementById('worker-interval').textContent = (d.interval || 10) + 's';
            document.getElementById('worker-last-status').textContent = d.last_status || 'Idle';
        }
    } catch (e) {}
}

async function runWorkerOnce() {
    const resBox = document.getElementById('worker-action-result');
    resBox.textContent = 'Executing worker single cycle...';
    try {
        const res = await fetch('/actions/worker_control.php?action=run_once');
        const json = await res.json();
        if (json.success) {
            resBox.textContent = json.message || 'Worker cycle executed successfully.';
            loadWorkerStatus();
        } else {
            resBox.textContent = 'Error: ' + (json.error || 'Execution failed');
        }
    } catch (e) {
        resBox.textContent = 'Error executing worker: ' + e.message;
    }
}

async function loadApiKeys() {
    const tbody = document.getElementById('api-keys-table');
    try {
        const res = await fetch('/actions/api_keys.php?action=list');
        const json = await res.json();
        if (!json.success || !json.data || json.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-3 py-3 text-center text-gray-400">No API keys found.</td></tr>';
            return;
        }

        tbody.innerHTML = json.data.map(k => {
            const statusBadge = k.is_active == 1 
                ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-800">Active</span>'
                : '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">Revoked</span>';

            const actionBtns = IS_ADMIN ? `
                <button onclick="toggleApiKey(${k.id}, ${k.is_active == 1 ? 0 : 1})" class="text-xs text-amber-600 hover:underline">
                    ${k.is_active == 1 ? 'Revoke' : 'Activate'}
                </button>
                <button onclick="deleteApiKey(${k.id})" class="text-xs text-red-600 hover:underline ml-2">
                    Delete
                </button>
            ` : `<span class="text-gray-400 text-xs">Read-Only</span>`;

            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium text-gray-900">${escapeHtml(k.name)}</td>
                    <td class="px-3 py-2 font-mono text-gray-600">
                        <span class="bg-gray-100 px-1.5 py-0.5 rounded text-[11px]">${escapeHtml(k.key_value)}</span>
                        <button onclick="copyToClipboard('${k.key_value}')" class="ml-1 text-blue-600 hover:underline text-[10px]">Copy</button>
                    </td>
                    <td class="px-3 py-2">${statusBadge}</td>
                    <td class="px-3 py-2 text-right space-x-1">
                        ${actionBtns}
                    </td>
                </tr>
            `;
        }).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-3 py-3 text-center text-red-500">Error loading keys</td></tr>';
    }
}

async function createApiKey() {
    const name = document.getElementById('new-key-name').value.trim();
    const rate = document.getElementById('new-key-rate').value;
    const custom = document.getElementById('new-key-custom').value.trim();

    if (!name) {
        alert('Please enter a key name');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'create');
    fd.append('name', name);
    fd.append('rate_limit', rate);
    if (custom) fd.append('custom_key', custom);

    const res = await fetch('/actions/api_keys.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
        document.getElementById('create-key-box').classList.add('hidden');
        document.getElementById('new-key-name').value = '';
        document.getElementById('new-key-custom').value = '';
        loadApiKeys();
    } else {
        alert('Error: ' + (json.error || 'Failed to create key'));
    }
}

async function toggleApiKey(id, newStatus) {
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    fd.append('is_active', newStatus);

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
        alert('API Key copied to clipboard!');
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

    if (!current || !next) {
        resultBox.textContent = 'Please fill all password fields.';
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
