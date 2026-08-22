<?php
require_once 'auth.php';
require_once 'database_pdo.php';
require_once 'i18n.php';
\Temporal\Auth::getInstance()->requireLogin();
$current_page = 'logs';
include 'includes/header.php';
?>

<div class="px-4 py-6 sm:px-0">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo __('logs_title'); ?></h1>
        <p class="text-gray-600"><?php echo __('logs_subtitle'); ?></p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo __('log_date'); ?></th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo __('log_job_type'); ?></th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo __('log_status'); ?></th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo __('log_message'); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php
                    $db = \Temporal\Database::getInstance();
                    $logs = $db->query("SELECT created_at, job_type, status, message FROM system_logs ORDER BY created_at DESC LIMIT 100");
                    if (empty($logs)): ?>
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No logs found.</td></tr>
                    <?php else:
                        foreach ($logs as $row): 
                            $statusColor = $row['status'] === 'success' ? 'bg-green-100 text-green-800' : ($row['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800');
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2.5 font-mono text-xs text-gray-500"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td class="px-4 py-2.5 font-semibold text-gray-800"><?php echo htmlspecialchars($row['job_type']); ?></td>
                                <td class="px-4 py-2.5">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?php echo $statusColor; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-gray-700 font-mono text-xs"><?php echo htmlspecialchars($row['message']); ?></td>
                            </tr>
                        <?php endforeach; 
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
