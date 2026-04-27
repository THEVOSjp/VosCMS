<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($iname) ? htmlspecialchars($iname) . ' — ' : '' ?>VosCMS 마켓플레이스</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class' }</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
<style>body { font-family: 'Pretendard', system-ui, sans-serif; }</style>
<script>
if (localStorage.getItem('darkMode') === 'true' ||
    (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}
</script>
</head>
<body class="bg-zinc-50 dark:bg-zinc-900 min-h-screen text-zinc-900 dark:text-zinc-100">
<nav class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
        <a href="<?= $baseUrl ?>/marketplace" class="font-bold text-zinc-800 dark:text-white">VosCMS 마켓플레이스</a>
        <div class="flex items-center gap-4 text-sm">
            <a href="<?= $baseUrl ?>/marketplace?type=plugin" class="text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">플러그인</a>
            <a href="<?= $baseUrl ?>/marketplace?type=theme" class="text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">테마</a>
            <a href="<?= $baseUrl ?>/marketplace?type=widget" class="text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">위젯</a>
        </div>
    </div>
</nav>
