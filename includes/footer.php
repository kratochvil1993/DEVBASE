</main>

<footer class="container py-4 mt-auto border-top border-light border-opacity-10 text-center text-white-50">
    <p class="small mb-1">&copy; <?php echo date('Y'); ?> DevBase - The OneNote Killer</p>
    <p class="small mb-1">v 1.0.3</p>
    <p class="small"><a href="https://www.jirikratochvil.eu/" target="_blank" class="link-light link-opacity-50 link-opacity-100-hover text-decoration-none">jirikratochvil.eu</a></p>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Prism.js core -->
<script src="assets/vendor/prism/prism.min.js"></script>
<!-- Prism.js autoload languages -->
<script src="assets/vendor/prism/plugins/prism-autoloader.min.js"></script>
<script>
    if (window.Prism && Prism.plugins && Prism.plugins.autoloader) {
        Prism.plugins.autoloader.languages_path = 'assets/vendor/prism/components/';
    }
</script>
<!-- Marked.js pro Markdown Rendering -->
<script src="assets/vendor/marked/marked.min.js"></script>
<!-- Quill.js core -->
<script src="assets/vendor/quill/quill.js"></script>
<!-- Custom JS -->
<script src="assets/js/main.js?v=<?php echo filemtime('assets/js/main.js'); ?>"></script>
<!-- Back to Top Button -->
<button id="backToTop" class="btn btn-back-to-top d-flex d-lg-none" title="Nahoru">
    <i class="bi bi-arrow-up-short"></i>
</button>

<style>
.btn-back-to-top {
    position: fixed;
    bottom: 25px;
    right: 20px;
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(142, 84, 233, 0.3) !important;
    backdrop-filter: blur(15px) !important;
    -webkit-backdrop-filter: blur(15px) !important;
    border: 1px solid rgba(142, 84, 233, 0.3) !important;
    color: white;
    z-index: 1050;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4), 0 0 10px rgba(142, 84, 233, 0.3);
    padding: 0;
    align-items: center;
    justify-content: center;
    
    /* Hidden state (slides right) */
    opacity: 0;
    pointer-events: none;
    transform: translateX(80px) scale(0.8);
}

.btn-back-to-top.show {
    opacity: 1;
    pointer-events: auto;
    transform: translateX(0) scale(1);
}

.btn-back-to-top:hover {
    background: rgba(142, 84, 233, 1.0) ;
    box-shadow: 0 0 25px rgba(142, 84, 233, 1.0), 0 0 10px rgba(255, 255, 255, 0.2) ;
    transform: translateX(0) scale(1) ; /* Keep it stable */
}

.btn-back-to-top i {
    font-size: 1.8rem;
    line-height: 1;
}

@media (min-width: 992px) {
    .btn-back-to-top {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const backToTop = document.getElementById('backToTop');
    if (!backToTop) return;
    
    const handleScroll = () => {
        if (window.innerWidth < 992) {
            if (window.scrollY > 100) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        }
    };
    
    window.addEventListener('scroll', handleScroll);
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});
</script>

</body>
</html>
