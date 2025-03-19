<?php
// layout/footer.php
if (!defined('BASE_URL')) {
    include_once __DIR__ . '/../config.php';
}
?>
<footer class="footer grey darken-4">
    <div class="container center" style="padding-top: 5px;">
        <div class="row justify-content-md-center">
            <div class="col col-lg-5">
                &copy; 2023 Fersus x Soursop Solutions, all rights reserved
            </div>
            <div class="col-md-auto">
                <a href="https://soursop.services/" target="_blank" title="Powered by">
                    <img src="<?php echo BASE_URL; ?>assets/img/soursop.png" alt="Soursop" class="logo">
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts globales que deseas cargar en todo el panel -->
<!-- Ajusta el orden si uno depende de otro -->
<script src="<?php echo BASE_URL; ?>assets/js/charts.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/admin.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/products.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/empleados/tarjeta.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/material.min.js"></script>
</body>
</html>
