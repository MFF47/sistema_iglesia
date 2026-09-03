<nav class="topbar d-flex align-items-center justify-content-between px-4">
    <div class="d-flex align-items-center gap-2">
        <span class="topbar-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 2h2v7h7v2h-7v11h-2V11H4V9h7V2z" fill="#d98c22"/>
            </svg>
        </span>
        <span class="topbar-title">Sistema de Control Financiero</span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="topbar-user"><?= htmlspecialchars($usuario['nombre_completo']) ?></span>
        <span class="badge rounded-pill topbar-role"><?= htmlspecialchars($usuario['rol_nombre']) ?></span>
        <a href="index.php?c=auth&a=logout" class="topbar-logout">Cerrar sesión</a>
    </div>
</nav>
