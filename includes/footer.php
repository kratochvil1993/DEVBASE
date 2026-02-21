</main>

<footer class="container py-4 mt-auto border-top border-light border-opacity-10 text-center text-white-50">
    <p class="small mb-1">&copy; <?php echo date('Y'); ?> DevBase - The OneNote Killer</p>
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
<script src="assets/js/main.js"></script>
</body>
</html>
