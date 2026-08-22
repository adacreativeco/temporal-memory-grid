<?php
require_once 'auth.php';
require_once 'i18n.php';

// If already logged in, redirect to dashboard
if (\Temporal\Auth::getInstance()->isLoggedIn()) {
    header('Location: /');
    exit();
}

$lang = \Temporal\I18n::getLang();
$supportedLanguages = \Temporal\I18n::getSupportedLanguages();
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
<html lang="<?php echo htmlspecialchars($lang); ?>" dir="<?php echo htmlspecialchars($supportedLanguages[$lang]['dir'] ?? 'ltr'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
        <!-- Language Switcher Bar -->
        <div class="flex justify-center flex-wrap gap-1 pb-2 border-b border-gray-100">
            <?php foreach ($supportedLanguages as $code => $info): ?>
                <a href="<?php echo \Temporal\I18n::getSwitchUrl($code); ?>" class="px-2.5 py-1 text-xs font-semibold rounded-lg transition <?php echo $code === $lang ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?>">
                    <?php echo $info['flag']; ?> <?php echo strtoupper($code); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center">
            <div class="w-12 h-12 bg-blue-600 text-white font-black text-xl flex items-center justify-center rounded-xl mx-auto mb-3 shadow-md">
                TMG
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                <?php echo __('app_name'); ?>
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                <?php echo __('app_subtitle'); ?>
            </p>
        </div>
        
        <form class="mt-6 space-y-4" method="POST">
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg flex items-center gap-2">
                    <span class="text-red-500 font-bold">⚠️</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="space-y-3">
                <div>
                    <label for="username" class="block text-xs font-semibold text-gray-700 mb-1"><?php echo __('username'); ?></label>
                    <input id="username" name="username" type="text" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm shadow-sm" 
                           placeholder="<?php echo __('username'); ?>" value="admin">
                </div>
                <div>
                    <label for="password" class="block text-xs font-semibold text-gray-700 mb-1"><?php echo __('password'); ?></label>
                    <input id="password" name="password" type="password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm shadow-sm" 
                           placeholder="<?php echo __('password'); ?>">
                </div>
            </div>
            
            <div class="pt-2">
                <button type="submit" 
                        class="w-full flex justify-center py-2.5 px-4 border border-transparent text-sm font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-md hover:shadow-lg transition duration-200">
                    <?php echo __('login'); ?>
                </button>
            </div>

            <div class="text-center pt-2">
                <a href="/api_guide.php" class="text-blue-600 hover:text-blue-700 text-xs font-semibold"><?php echo __('nav_api_guide'); ?> →</a>
            </div>
        </form>
    </div>
</body>
</html>
