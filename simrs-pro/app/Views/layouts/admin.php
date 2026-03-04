<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'SIMRS Pro' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bs-primary: #0d6efd; --sidebar-width: 260px; }
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: #fff; border-right: 1px solid #eee; }
        .main-content { margin-left: var(--sidebar-width); padding: 2rem; }
        .nav-link { padding: 0.8rem 1.5rem; border-radius: 10px; margin: 0.2rem 1rem; color: #666; }
        .nav-link.active { background: var(--bs-primary); color: #fff; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="p-4"><h5 class="fw-bold text-primary">SIMRS PRO</h5></div>
        <nav class="nav flex-column">
            <?php 
            $menu = $GLOBALS['app']->moduleManager->getSidebarMenu();
            foreach($menu as $m): ?>
                <?php foreach($m['items'] as $item): ?>
                    <a href="<?= $item['url'] ?>" class="nav-link <?= (strpos($_SERVER['REQUEST_URI'], $item['url']) !== false) ? 'active' : '' ?>">
                        <i class="bi <?= $m['icon'] ?> me-2"></i> <?= $item['label'] ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="main-content">
        <?= $content ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
