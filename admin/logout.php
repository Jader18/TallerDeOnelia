<?php
session_start();

// Destruir sesión completamente
session_unset();
session_destroy();

// Redirigir al inicio público
header("Location: /");
exit;
?>