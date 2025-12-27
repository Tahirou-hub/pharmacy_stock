<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Bouton menu mobile -->
<button id="mobileMenuBtn" class="lg:hidden fixed top-4 left-4 z-30 p-2 bg-blue-600 text-white rounded-lg shadow-lg">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-gradient-to-b from-blue-50 via-white to-gray-50 border-r border-blue-100 shadow-xl flex flex-col z-20 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
    <!-- Overlay pour mobile -->
    <div id="sidebarOverlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-10 hidden"></div>
    
    <div class="p-6 border-b border-blue-200 bg-gradient-to-r from-blue-600 to-blue-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-blue-600 text-2xl font-bold shadow-lg">
                    💊
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white">Pharmacy Stock</h1>
                    <p class="text-xs text-blue-100">Gestion de Stock</p>
                </div>
            </div>
            <button id="closeSidebar" class="lg:hidden text-white hover:text-blue-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md transition-all duration-200 <?= $current_page === 'dashboard.php' ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg font-semibold' : '' ?>">
            <span class="text-xl">🏠</span>
            <span>Dashboard</span>
        </a>
        
        <a href="medicaments.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md transition-all duration-200 <?= $current_page === 'medicaments.php' || $current_page === 'edit_medicament.php' ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg font-semibold' : '' ?>">
            <span class="text-xl">💼</span>
            <span>Médicaments</span>
        </a>
        
        <a href="ventes.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md transition-all duration-200 <?= $current_page === 'ventes.php' || $current_page === 'add_vente.php' || $current_page === 'facture.php' ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg font-semibold' : '' ?>">
            <span class="text-xl">🛒</span>
            <span>Ventes</span>
        </a>
        
        <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md transition-all duration-200 <?= $current_page === 'index.php' || $current_page === 'rapport.php' || $current_page === 'export_rapport.php' ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg font-semibold' : '' ?>">
            <span class="text-xl">📈</span>
            <span>Historique</span>
        </a>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="pt-4 mt-4 border-t border-blue-200">
                <p class="px-4 text-xs font-bold text-blue-600 uppercase tracking-wider mb-2">Administration</p>
            </div>
            
            <a href="achats.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md transition-all duration-200 <?= $current_page === 'achats.php' ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg font-semibold' : '' ?>">
                <span class="text-xl">📦</span>
                <span>Achats</span>
            </a>
            
            <a href="rupture_stock.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md transition-all duration-200 <?= $current_page === 'rupture_stock.php' ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg font-semibold' : '' ?>">
                <span class="text-xl">⚠️</span>
                <span>Ruptures</span>
            </a>
            
            <a href="parametres.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:bg-blue-50 hover:text-blue-700 hover:shadow-md transition-all duration-200 <?= $current_page === 'parametres.php' ? 'bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg font-semibold' : '' ?>">
                <span class="text-xl">⚙️</span>
                <span>Paramètres</span>
            </a>
        <?php endif; ?>
    </nav>
    
    <div class="p-4 border-t border-blue-200 bg-gradient-to-r from-gray-50 to-blue-50">
        <div class="mb-3 p-3 bg-white rounded-lg shadow-sm">
            <p class="text-xs text-gray-500 mb-1">Connecté en tant que</p>
            <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($_SESSION['username']) ?></p>
        </div>
        <span class="inline-block px-3 py-1.5 rounded-full text-xs font-bold shadow-sm <?= $_SESSION['role'] === 'admin' ? 'bg-gradient-to-r from-purple-500 to-purple-600 text-white' : 'bg-gradient-to-r from-blue-500 to-blue-600 text-white' ?>">
            <?= $_SESSION['role'] === 'admin' ? '👑 Admin' : '👤 Agent' ?>
        </span>
        <a href="logout.php" class="mt-3 flex items-center gap-2 px-4 py-2.5 rounded-xl text-white bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 shadow-md hover:shadow-lg transition-all duration-200 w-full font-semibold">
            <span>🚪</span>
            <span class="text-sm">Déconnexion</span>
        </a>
    </div>
</aside>

<script>
// Gestion du menu mobile
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const closeSidebar = document.getElementById('closeSidebar');

function openSidebar() {
    sidebar.classList.remove('-translate-x-full');
    sidebarOverlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeSidebarFunc() {
    sidebar.classList.add('-translate-x-full');
    sidebarOverlay.classList.add('hidden');
    document.body.style.overflow = '';
}

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', openSidebar);
}

if (closeSidebar) {
    closeSidebar.addEventListener('click', closeSidebarFunc);
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebarFunc);
}

// Fermer le menu si on clique sur un lien
document.querySelectorAll('#sidebar nav a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 1024) {
            closeSidebarFunc();
        }
    });
});
</script>
