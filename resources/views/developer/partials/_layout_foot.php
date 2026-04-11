</main>
<footer class="border-t border-zinc-200 dark:border-zinc-700 mt-12 py-6 text-center text-xs text-zinc-400 dark:text-zinc-500">
    VosCMS Developer Portal &copy; <?= date('Y') ?>
</footer>
<script>
// 다크모드 토글
document.getElementById('darkModeBtn')?.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);
});
</script>
</body>
</html>
