<!-- admin/admin_header.php -->
<header class="admin-header">
    <h1 class="admin-title">Panel de Administración</h1>
    
    <div class="user-controls">
        <?php if (basename($_SERVER['PHP_SELF']) !== 'dashboard.php'): ?>
            <a href="dashboard.php" class="btn-action btn-back">Volver al Dashboard</a>
        <?php endif; ?>

        <a href="/" class="btn-action btn-back">Volver al sitio</a>
        <a href="logout.php" class="btn-action btn-logout">Cerrar sesión</a>
    </div>
</header>