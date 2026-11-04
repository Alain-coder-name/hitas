<?php
// Attend que $pageTitle soit défini par la page appelante avant l'inclusion.
$pageTitle = $pageTitle ?? 'Administration';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitle) ?> - Admin HITAS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin.css" rel="stylesheet">
    <style>
        /* Notifications (toasts) — succès en vert, erreur en rouge */
        .hitas-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 360px;
            max-width: calc(100vw - 24px);
        }
        .hitas-toast {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fff;
            color: #1c2b3a;
            border-left: 5px solid transparent;
            border-radius: 10px;
            box-shadow: 0 12px 30px rgba(0, 20, 40, 0.18);
            padding: 14px 40px 14px 16px;
            font-size: 0.92rem;
            line-height: 1.45;
            animation: hitasToastIn 0.35s ease forwards;
        }
        .hitas-toast--success { border-left-color: #1f9d55; }
        .hitas-toast--success .hitas-toast-icon { color: #1f9d55; }
        .hitas-toast--error { border-left-color: #d64545; }
        .hitas-toast--error .hitas-toast-icon { color: #d64545; }
        .hitas-toast-icon { font-size: 1.3rem; line-height: 1; margin-top: 2px; flex-shrink: 0; }
        .hitas-toast-body { flex: 1; }
        .hitas-toast-body ul { margin: 0; padding-left: 1.1rem; }
        .hitas-toast-close {
            position: absolute;
            top: 6px;
            right: 8px;
            border: none;
            background: none;
            color: #8a97a3;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
            padding: 4px;
        }
        .hitas-toast-close:hover { color: #1c2b3a; }
        .hitas-toast.is-leaving { animation: hitasToastOut 0.3s ease forwards; }
        @keyframes hitasToastIn {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes hitasToastOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(30px); }
        }
        @media (max-width: 576px) {
            .hitas-toast-container { top: 12px; right: 12px; left: 12px; width: auto; }
        }
    </style>
</head>
<body>
<div class="admin-wrapper d-flex">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="admin-content flex-grow-1">
        <nav class="topbar d-flex align-items-center justify-content-between px-4">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <span class="fw-semibold"><?= e($pageTitle) ?></span>
            <div class="dropdown">
                <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i> <?= e($_SESSION['admin_username'] ?? '') ?>
                    <span class="badge bg-light text-dark border ms-2 small fw-normal">
                        <?= e(adminRoleLabels()[$_SESSION['admin_role'] ?? ''] ?? ($_SESSION['admin_role'] ?? '')) ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/logout.php">Déconnexion</a></li>
                </ul>
            </div>
        </nav>

        <main class="p-4">
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="alert alert-success"><?= e($_SESSION['flash_success']) ?></div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger"><?= e($_SESSION['flash_error']) ?></div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>
