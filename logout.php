<?php
session_start();
session_destroy();
?>
<script>
    // Guardamos una bandera en el almacenamiento local del navegador
    localStorage.setItem('logout_event', 'logout_' + Date.now());
    // Redirigimos al index
    window.location.href = "index.php";
</script>
<?php
exit();
?>