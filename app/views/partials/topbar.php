<nav class="topbar d-flex align-items-center justify-content-between px-3 px-md-4">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="sidebar-toggle" id="btn-sidebar-toggle" aria-label="Abrir menú">
            <i class="bi bi-list"></i>
        </button>
        <span class="topbar-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 2h2v7h7v2h-7v11h-2V11H4V9h7V2z" fill="#d98c22"/>
            </svg>
        </span>
        <span class="topbar-title">Sistema de Control Financiero</span>
    </div>
    <div class="d-flex align-items-center gap-2 gap-md-3">
        <span class="topbar-user"><?= htmlspecialchars($usuario['nombre_completo']) ?></span>
        <span class="badge rounded-pill topbar-role"><?= htmlspecialchars($usuario['rol_nombre']) ?></span>
        <a href="index.php?c=perfil&a=cambiarPassword" class="topbar-logout" title="Cambiar contraseña">
            <i class="bi bi-key d-inline d-sm-none"></i>
            <span class="d-none d-sm-inline">Cambiar contraseña</span>
        </a>
        <a href="index.php?c=auth&a=logout" class="topbar-logout" title="Cerrar sesión">
            <i class="bi bi-box-arrow-right d-inline d-sm-none"></i>
            <span class="d-none d-sm-inline">Cerrar sesión</span>
        </a>
    </div>
</nav>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
