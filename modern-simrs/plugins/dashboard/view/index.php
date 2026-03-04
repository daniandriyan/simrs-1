<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Modern SIMRS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 hidden md:flex flex-col">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-sky-600 flex items-center gap-2">
                    <i data-lucide="activity"></i> SIMRS <span class="text-slate-400 font-light">Pro</span>
                </h1>
            </div>
            <nav class="flex-1 px-4 space-y-1">
                <a href="#" class="flex items-center gap-3 px-4 py-3 bg-sky-50 text-sky-600 rounded-xl font-medium">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl transition">
                    <i data-lucide="users" class="w-5 h-5"></i> Pasien
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl transition">
                    <i data-lucide="user-cog" class="w-5 h-5"></i> Dokter
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl transition">
                    <i data-lucide="clipboard-list" class="w-5 h-5"></i> Registrasi
                </a>
                <a href="#" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl transition">
                    <i data-lucide="pill" class="w-5 h-5"></i> Farmasi
                </a>
            </nav>
            <div class="p-4 border-t border-slate-100">
                <button class="w-full flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl transition">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Keluar
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-auto">
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <button class="md:hidden text-slate-600">
                        <i data-lucide="menu"></i>
                    </button>
                    <h2 class="text-lg font-semibold text-slate-800">Ringkasan Hari Ini</h2>
                </div>
                <div class="flex items-center gap-6">
                    <div class="relative">
                        <i data-lucide="bell" class="text-slate-400 w-6 h-6"></i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center border-2 border-white">3</span>
                    </div>
                    <div class="flex items-center gap-3 pl-6 border-l border-slate-200">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold">Dr. Dani Ramdani</p>
                            <p class="text-xs text-slate-400">Administrator</p>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=Dani+Ramdani&background=0ea5e9&color=fff" class="w-10 h-10 rounded-full border-2 border-white shadow-sm" alt="Avatar">
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="p-8">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Pasien -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-3 bg-sky-50 text-sky-600 rounded-xl">
                                <i data-lucide="users" class="w-6 h-6"></i>
                            </div>
                            <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">+12%</span>
                        </div>
                        <h3 class="text-slate-400 text-sm font-medium mb-1">Total Pasien</h3>
                        <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['total_pasien']); ?></p>
                    </div>

                    <!-- Pasien Hari Ini -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                                <i data-lucide="user-plus" class="w-6 h-6"></i>
                            </div>
                            <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">+5%</span>
                        </div>
                        <h3 class="text-slate-400 text-sm font-medium mb-1">Pasien Hari Ini</h3>
                        <p class="text-2xl font-bold text-slate-800"><?php echo $stats['pasien_hari_ini']; ?></p>
                    </div>

                    <!-- IGD -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-3 bg-red-50 text-red-600 rounded-xl">
                                <i data-lucide="flame" class="w-6 h-6"></i>
                            </div>
                            <span class="text-xs font-medium text-red-600 bg-red-50 px-2 py-1 rounded-lg">High</span>
                        </div>
                        <h3 class="text-slate-400 text-sm font-medium mb-1">Kunjungan IGD</h3>
                        <p class="text-2xl font-bold text-slate-800"><?php echo $stats['kunjungan_igd']; ?></p>
                    </div>

                    <!-- Stok Obat -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                        <div class="flex justify-between items-start mb-4">
                            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                                <i data-lucide="package" class="w-6 h-6"></i>
                            </div>
                            <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded-lg">Warning</span>
                        </div>
                        <h3 class="text-slate-400 text-sm font-medium mb-1">Stok Obat Kritis</h3>
                        <p class="text-2xl font-bold text-slate-800"><?php echo $stats['stok_obat_kritis']; ?></p>
                    </div>
                </div>

                <!-- Recent Activity Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="font-bold text-slate-800 text-lg">Kunjungan Terakhir</h3>
                        <button class="text-sky-600 text-sm font-medium hover:underline">Lihat Semua</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 text-slate-400 text-xs font-semibold uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Nama Pasien</th>
                                    <th class="px-6 py-4">Nomor RM</th>
                                    <th class="px-6 py-4">Poliklinik</th>
                                    <th class="px-6 py-4">Waktu</th>
                                    <th class="px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-700">Ahmad Subardjo</td>
                                    <td class="px-6 py-4 text-slate-500">001245</td>
                                    <td class="px-6 py-4">Poli Umum</td>
                                    <td class="px-6 py-4 text-slate-500">09:15 WIB</td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-full text-xs font-medium">Antri</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 font-medium text-slate-700">Siti Aminah</td>
                                    <td class="px-6 py-4 text-slate-500">001246</td>
                                    <td class="px-6 py-4">Poli Gigi</td>
                                    <td class="px-6 py-4 text-slate-500">09:30 WIB</td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 bg-sky-50 text-sky-600 rounded-full text-xs font-medium">Diperiksa</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
