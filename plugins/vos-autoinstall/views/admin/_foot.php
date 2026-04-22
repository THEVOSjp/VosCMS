        </div>
    </main>
</div>
<script>
function toggleSiteMenu() { document.getElementById('siteSubMenu')?.classList.toggle('hidden'); document.getElementById('siteMenuArrow')?.classList.toggle('rotate-180'); }
function toggleSettingsMenu() { document.getElementById('settingsSubMenu')?.classList.toggle('hidden'); document.getElementById('settingsMenuArrow')?.classList.toggle('rotate-180'); }
function toggleMembersMenu() { document.getElementById('membersSubMenu')?.classList.toggle('hidden'); document.getElementById('membersMenuArrow')?.classList.toggle('rotate-180'); }
</script>
<?php include BASE_PATH . '/resources/views/admin/partials/result-modal.php'; ?>
</body>
</html>
