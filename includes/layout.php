<?php
// includes/layout.php
// Template de base pour toutes les pages
if (!function_exists('renderPage')) {
    function renderPage($title, $content, $showSidebar = true) {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($title) ?> - Pharmacy Stock</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="assets/css/app.css">
            <style>
                @media print {
                    .no-print { display: none !important; }
                    body { background: white; }
                }
            </style>
        </head>
        <body class="bg-gray-50 min-h-screen">
            <?php if ($showSidebar): ?>
                <?php require_once "includes/sidebar.php"; ?>
            <?php endif; ?>
            
            <main class="<?= $showSidebar ? 'ml-64' : '' ?> p-6 min-h-screen">
                <?= $content ?>
            </main>
            
            <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
                <script>
                    setTimeout(() => {
                        const url = new URL(window.location);
                        url.searchParams.delete('success');
                        url.searchParams.delete('error');
                        window.history.replaceState({}, '', url);
                    }, 5000);
                </script>
            <?php endif; ?>
        </body>
        </html>
        <?php
    }
}
?>


