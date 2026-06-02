    </div><!-- /container-fluid -->
</main><!-- /ctisms-main -->
</div><!-- /ctisms-wrapper -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Custom JS -->
<script src="<?= APP_URL ?>/js/app.js"></script>

<?php if (isset($pageScripts)): ?>
<script><?= $pageScripts ?></script>
<?php endif; ?>

</body>
</html>
