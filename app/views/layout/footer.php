    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const btnToggle = document.getElementById('btn-sidebar-toggle');
    const backdrop = document.getElementById('sidebar-backdrop');

    function cerrarSidebar() {
        document.body.classList.remove('sidebar-open');
    }

    if (btnToggle) {
        btnToggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', cerrarSidebar);
    }

    document.querySelectorAll('.sidebar-link').forEach(function (link) {
        link.addEventListener('click', cerrarSidebar);
    });
})();
</script>
</body>
</html>
