<?php
require_once __DIR__ . '/../i18n.php';
$lang = \Temporal\I18n::getLang();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
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
        window.TMG_LOCALE = '<?php echo $lang === 'en' ? 'en-US' : 'tr-TR'; ?>';
        window.t = function(key, fallback) {
            return window.TMG_LANG[key] || fallback || key;
        };
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/index.php" class="text-xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                            <span class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center text-white text-xs font-black shadow-sm">TMG</span>
                            <?php echo __('app_name'); ?>
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-6">
                        <a href="/index.php" class="<?php echo ($current_page ?? '') === 'dashboard' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_dashboard'); ?>
                        </a>
                        <a href="/trends.php" class="<?php echo ($current_page ?? '') === 'trends' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_trends'); ?>
                        </a>
                        <a href="/anomalies.php" class="<?php echo ($current_page ?? '') === 'anomalies' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_anomalies'); ?>
                        </a>
                        <a href="/settings.php" class="<?php echo ($current_page ?? '') === 'settings' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_settings'); ?>
                        </a>
                        <a href="/logs.php" class="<?php echo ($current_page ?? '') === 'logs' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_logs'); ?>
                        </a>
                        <a href="/api_guide.php" class="<?php echo ($current_page ?? '') === 'api_guide' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo __('nav_api_guide'); ?>
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- Language Selector -->
                    <div class="inline-flex rounded-md shadow-sm" role="group">
                        <a href="?lang=tr" class="px-2.5 py-1 text-xs font-semibold rounded-l-md border <?php echo $lang === 'tr' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                            🇹🇷 TR
                        </a>
                        <a href="?lang=en" class="px-2.5 py-1 text-xs font-semibold rounded-r-md border-t border-b border-r <?php echo $lang === 'en' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                            🇬🇧 EN
                        </a>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="flex items-center space-x-3 pl-2 border-l border-gray-200">
                            <span class="text-gray-600 text-xs font-medium hidden md:inline"><?php echo __('welcome_user', htmlspecialchars($_SESSION['username'])); ?></span>
                            <a href="/logout.php" class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-1.5 rounded-md text-xs font-medium transition duration-200">
                                <?php echo __('logout'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
