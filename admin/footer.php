        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
    /* Notifications (toasts) — transforme les messages .alert-success /
       .alert-danger déjà affichés (enregistrement, suppression...) en
       notification flottante : verte si succès, rouge si erreur. */
    (function () {
        function hitasIconFor(type) {
            if (type === 'success') return 'bi-check-circle-fill';
            return 'bi-exclamation-triangle-fill';
        }

        function hitasCloseToast(toast) {
            toast.classList.add('is-leaving');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }

        function hitasInitToasts() {
            var alerts = document.querySelectorAll('.alert-success:not([data-hitas-toast]), .alert-danger:not([data-hitas-toast]), .alert-warning:not([data-hitas-toast])');
            if (!alerts.length) return;

            var container = document.querySelector('.hitas-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'hitas-toast-container';
                container.setAttribute('aria-live', 'polite');
                document.body.appendChild(container);
            }

            alerts.forEach(function (el) {
                el.setAttribute('data-hitas-toast', '1');
                var type = el.classList.contains('alert-success') ? 'success' : 'error';
                var content = el.innerHTML;
                el.parentNode.removeChild(el);

                var toast = document.createElement('div');
                toast.className = 'hitas-toast hitas-toast--' + type;
                toast.innerHTML =
                    '<i class="bi ' + hitasIconFor(type) + ' hitas-toast-icon"></i>' +
                    '<div class="hitas-toast-body">' + content + '</div>' +
                    '<button type="button" class="hitas-toast-close" aria-label="Fermer">&times;</button>';
                container.appendChild(toast);

                var timer = setTimeout(function () { hitasCloseToast(toast); }, 6000);
                toast.querySelector('.hitas-toast-close').addEventListener('click', function () {
                    clearTimeout(timer);
                    hitasCloseToast(toast);
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', hitasInitToasts);
        } else {
            hitasInitToasts();
        }
    })();
</script>
</body>
</html>
