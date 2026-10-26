<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
</main>

<footer class="page-gutter mx-auto max-w-7xl px-4 pb-10 pt-4 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
    Clan Reunion Directory &middot; Four branches, one family.
</footer>

<?php foreach ([
    'vendor/jquery/jquery-3.7.1.min.js',
    'vendor/jquery-ui/jquery-ui-1.14.2.min.js',
    'vendor/timepicker/jquery-ui-timepicker-addon.min.js',
    'vendor/sweetalert2/sweetalert2.all.min.js',
    'vendor/toastr/toastr.min.js',
    'js/app.js',
] as $ui_script): ?>
    <script src="<?= base_url('assets/' . $ui_script) ?>?v=<?= filemtime(FCPATH . 'assets/' . $ui_script) ?>" defer></script>
<?php endforeach; ?>

</body>
</html>
