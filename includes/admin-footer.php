        </main>

        <footer class="px-4 md:px-8 py-6 text-center text-xs text-on-surface-variant border-t border-outline-variant/20">
            © <?= date('Y') ?> FoodExpress Dakar · Espace d'administration
        </footer>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('sidebarOverlay').classList.toggle('hidden');
    }
</script>
</body>
</html>
