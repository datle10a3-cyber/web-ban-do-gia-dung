</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
    
    document.querySelectorAll('.d-lg-none').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelector('.admin-sidebar').classList.toggle('show');
        });
    });
</script>
</body>
</html>