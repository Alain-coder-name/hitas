<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pdo = getDB();
$sections = $pdo->query('SELECT * FROM home_sections ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Sections de l\'accueil';
include __DIR__ . '/../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">Blocs affichés sur la page d'accueil</h5>
        <p class="text-muted small mb-0">Glissez-déposez un bloc pour changer son ordre d'affichage, ou désactivez-le pour le masquer temporairement du site public.</p>
    </div>
</div>

<div class="card p-3">
    <ul class="list-group" id="sectionsSortable" data-reorder-url="<?= BASE_URL ?>/admin/home_sections/reorder.php" data-csrf="<?= e(csrfToken()) ?>">
        <?php foreach ($sections as $s): ?>
            <li class="list-group-item d-flex align-items-center gap-3 section-drag-item" draggable="true" data-id="<?= (int)$s['id'] ?>">
                <span class="drag-handle text-muted" title="Glisser pour réordonner"><i class="bi bi-grip-vertical fs-5"></i></span>
                <span class="flex-grow-1">
                    <span class="fw-semibold"><?= e($s['label']) ?></span>
                    <span class="text-muted small ms-2"><?= e($s['section_key']) ?></span>
                </span>
                <div class="form-check form-switch mb-0 section-toggle" data-id="<?= (int)$s['id'] ?>">
                    <input class="form-check-input" type="checkbox" role="switch" <?= $s['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label small text-muted">Actif</label>
                </div>
            </li>
        <?php endforeach; ?>
        <?php if (!$sections): ?>
            <li class="list-group-item text-center text-muted py-4">Aucune section configurée.</li>
        <?php endif; ?>
    </ul>
</div>

<style>
.section-drag-item { cursor: grab; }
.section-drag-item.is-dragging { opacity: .4; }
.section-drag-item.drag-over { border-top: 2px solid var(--bs-primary); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var list = document.getElementById('sectionsSortable');
    if (!list) return;
    var reorderUrl = list.dataset.reorderUrl;
    var csrfToken = list.dataset.csrf;
    var dragged = null;

    function sendOrder() {
        var ids = Array.prototype.map.call(list.querySelectorAll('.section-drag-item'), function (li) {
            return parseInt(li.dataset.id, 10);
        });
        fetch(reorderUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: csrfToken, order: ids })
        }).catch(function () {});
    }

    list.querySelectorAll('.section-drag-item').forEach(function (item) {
        item.addEventListener('dragstart', function () {
            dragged = item;
            item.classList.add('is-dragging');
        });
        item.addEventListener('dragend', function () {
            item.classList.remove('is-dragging');
            list.querySelectorAll('.section-drag-item').forEach(function (li) { li.classList.remove('drag-over'); });
            sendOrder();
        });
        item.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (item !== dragged) { item.classList.add('drag-over'); }
        });
        item.addEventListener('dragleave', function () {
            item.classList.remove('drag-over');
        });
        item.addEventListener('drop', function (e) {
            e.preventDefault();
            item.classList.remove('drag-over');
            if (!dragged || dragged === item) return;
            var items = Array.prototype.slice.call(list.querySelectorAll('.section-drag-item'));
            var draggedIndex = items.indexOf(dragged);
            var targetIndex = items.indexOf(item);
            if (draggedIndex < targetIndex) {
                item.after(dragged);
            } else {
                item.before(dragged);
            }
        });
    });

    list.querySelectorAll('.section-toggle input').forEach(function (input) {
        input.addEventListener('change', function () {
            var wrapper = input.closest('.section-toggle');
            var id = wrapper.dataset.id;
            fetch('<?= BASE_URL ?>/admin/home_sections/toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: csrfToken, id: id, is_active: input.checked ? 1 : 0 })
            }).catch(function () {});
        });
    });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>
