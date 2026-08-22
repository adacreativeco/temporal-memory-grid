<?php
require_once __DIR__ . '/../i18n.php';
$lang = \Temporal\I18n::getLang();
$supportedLanguages = \Temporal\I18n::getSupportedLanguages();
$currentLangInfo = $supportedLanguages[$lang] ?? $supportedLanguages['tr'];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>" dir="<?php echo htmlspecialchars($currentLangInfo['dir'] ?? 'ltr'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('app_name'); ?> - <?php echo __('app_subtitle'); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom styles -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .hover-card:hover {
            transform: translateY(-2px);
            transition: transform 0.2s ease-in-out;
        }
    </style>

    <!-- Client-side Translations -->
    <script>
        window.TMG_LANG = <?php echo json_encode(\Temporal\I18n::getAll()); ?>;
        window.TMG_LOCALE = '<?php echo htmlspecialchars($currentLangInfo['locale'] ?? 'en-US'); ?>';
        window.TMG_CURRENT_LANG = '<?php echo htmlspecialchars($lang); ?>';
        window.t = function(key, fallback) {
            return window.TMG_LANG[key] || fallback || key;
        };
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-md border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/index.php" class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                            <span class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center text-white text-xs font-black shadow-sm">TMG</span>
                            <span class="hidden sm:inline"><?php echo __('app_name'); ?></span>
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-4 lg:space-x-6">
                        <a href="/index.php" class="<?php echo ($current_page ?? '') === 'dashboard' ? 'border-blue-500 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_dashboard'); ?>
                        </a>
                        <a href="/trends.php" class="<?php echo ($current_page ?? '') === 'trends' ? 'border-blue-500 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_trends'); ?>
                        </a>
                        <a href="/anomalies.php" class="<?php echo ($current_page ?? '') === 'anomalies' ? 'border-blue-500 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_anomalies'); ?>
                        </a>
                        <a href="/settings.php" class="<?php echo ($current_page ?? '') === 'settings' ? 'border-blue-500 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_settings'); ?>
                        </a>
                        <a href="/logs.php" class="<?php echo ($current_page ?? '') === 'logs' ? 'border-blue-500 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_logs'); ?>
                        </a>
                        <a href="/api_guide.php" class="<?php echo ($current_page ?? '') === 'api_guide' ? 'border-blue-500 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_api_guide'); ?>
                        </a>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Advanced Language Selector Dropdown -->
                    <div class="relative inline-block text-left">
                        <button type="button" id="lang-menu-btn" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 text-xs font-semibold rounded-lg bg-white text-gray-700 hover:bg-gray-50 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span><?php echo $currentLangInfo['flag']; ?></span>
                            <span class="hidden md:inline"><?php echo htmlspecialchars($currentLangInfo['native']); ?></span>
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="lang-dropdown" class="hidden absolute right-0 mt-2 w-40 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-50 overflow-hidden">
                            <div class="py-1">
                                <?php foreach ($supportedLanguages as $code => $info): ?>
                                    <a href="<?php echo \Temporal\I18n::getSwitchUrl($code); ?>" class="flex items-center justify-between px-4 py-2 text-xs text-gray-700 hover:bg-blue-50 hover:text-blue-700 <?php echo $code === $lang ? 'font-bold bg-gray-50 text-blue-600' : ''; ?>">
                                        <span class="flex items-center gap-2">
                                            <span><?php echo $info['flag']; ?></span>
                                            <span><?php echo htmlspecialchars($info['native']); ?></span>
                                        </span>
                                        <?php if ($code === $lang): ?>
                                            <span class="text-blue-600 font-bold">✓</span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): 
                        $userRole = $_SESSION['role'] ?? 'viewer';
                        $roleBadgeColor = $userRole === 'admin' 
                            ? 'bg-purple-100 text-purple-800 border-purple-200' 
                            : ($userRole === 'analyst' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-blue-100 text-blue-800 border-blue-200');
                    ?>
                        <div class="flex items-center space-x-2 pl-2 border-l border-gray-200">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-gray-700">
                                <span>👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border <?php echo $roleBadgeColor; ?>">
                                    <?php echo htmlspecialchars($userRole); ?>
                                </span>
                            </div>
                            <a href="/logout.php" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-1.5 rounded-md text-xs font-semibold transition duration-200">
                                <?php echo __('logout'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <script>
    // Language dropdown toggle
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('lang-menu-btn');
        const dropdown = document.getElementById('lang-dropdown');
        if (btn && dropdown) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', () => {
                dropdown.classList.add('hidden');
            });
        }
    });
    </script>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
