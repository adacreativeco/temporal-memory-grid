<?php
require_once 'auth.php';
require_once 'i18n.php';

// If already logged in, redirect to dashboard
if (\Temporal\Auth::getInstance()->isLoggedIn()) {
    header('Location: /');
    exit();
}

$lang = \Temporal\I18n::getLang();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (\Temporal\Auth::getInstance()->login($username, $password)) {
        header('Location: /');
        exit();
    } else {
        $error = __('invalid_credentials');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
        <!-- Language Switcher -->
        <div class="flex justify-end">
            <div class="inline-flex rounded-md shadow-sm" role="group">
                <a href="?lang=tr" class="px-2.5 py-1 text-xs font-semibold rounded-l-md border <?php echo $lang === 'tr' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                    🇹🇷 TR
                </a>
                <a href="?lang=en" class="px-2.5 py-1 text-xs font-semibold rounded-r-md border-t border-b border-r <?php echo $lang === 'en' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                    🇬🇧 EN
                </a>
            </div>
        </div>

        <div class="text-center">
            <div class="w-12 h-12 bg-blue-600 text-white font-black text-xl flex items-center justify-center rounded-xl mx-auto mb-3 shadow-md">
                TMG
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900">
                <?php echo __('app_name'); ?>
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                <?php echo __('app_subtitle'); ?>
            </p>
        </div>
        
        <form class="mt-6 space-y-4" method="POST">
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="space-y-3">
                <div>
                    <label for="username" class="block text-xs font-semibold text-gray-700 mb-1"><?php echo __('username'); ?></label>
                    <input id="username" name="username" type="text" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" 
                           placeholder="<?php echo __('username'); ?>" value="admin">
                </div>
                <div>
                    <label for="password" class="block text-xs font-semibold text-gray-700 mb-1"><?php echo __('password'); ?></label>
                    <input id="password" name="password" type="password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" 
                           placeholder="<?php echo __('password'); ?>">
                </div>
            </div>
            
            <div class="pt-2">
                <button type="submit" 
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent text-sm font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm transition duration-200">
                    <?php echo __('login'); ?>
                </button>
            </div>

            <div class="text-center pt-2">
                <a href="/api_guide.php" class="text-blue-600 hover:text-blue-700 text-xs font-medium"><?php echo __('nav_api_guide'); ?> →</a>
            </div>
        </form>
    </div>
</body>
</html>
