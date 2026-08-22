<?php
require_once 'auth.php';
require_once 'database_pdo.php';
\Temporal\Auth::getInstance()->requireLogin();
$current_page = 'logs';
include 'includes/header.php';
?>

<div class="px-4 py-6 sm:px-0">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Temporal Jobs Log</h1>
        <p class="text-gray-600">Agregasyon ve temizlik işlemlerinin geçmişi.</p>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tarih/Saat</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">İş Tipi</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mesaj</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
$db = \Temporal\Database::getInstance();
                $logs = $db->query("SELECT created_at, job_type, status, message FROM system_logs ORDER BY created_at DESC LIMIT 100");
                foreach ($logs as $row): ?>
                    <tr>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['job_type']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['status']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($row['message']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
