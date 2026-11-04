<?php $siteName = getSetting('site_name', 'HITAS'); $activeCampus = getCurrentCampus(); ?>
</main>

<footer class="site-footer mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="display-font fs-4 text-white mb-1">HITAS</div>
                <p class="small fw-bold mb-2" translate="no">Hanseatic Institute of Technology and Applied Sciences</p>
                <p class="small fst-italic mb-0">« Pour une autre vision de l'excellence académique et professionnelle »</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6>Explorer</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/programmes.php">Trouver ma filière</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/partenaires.php">Partenaires</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/postulez.php">Postulez</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6>École</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="<?= BASE_URL ?>/a-propos.php">À propos</a></li>
                    <li class="mb-2"><a href="<?= BASE_URL ?>/postulez.php">Postulez</a></li>
                </ul>
            </div>
            <div class="col-lg-4" id="footer-phone-contact">
                <h6>Contact</h6>
                <ul class="list-unstyled small">
                    <?php $footerAddress = $activeCampus['address'] ?: getSetting('address'); if ($footerAddress): $addressLines = parseAddressLines($footerAddress); ?>
                        <?php foreach ($addressLines as $line): ?>
                            <li class="mb-2"><i class="bi bi-geo-alt me-1"></i> <?= e($line) ?></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php $poBox = trim((string) getSetting('po_box')); if ($poBox !== ''): ?>
                        <li class="mb-2"><i class="bi bi-mailbox me-1"></i> <?= e($poBox) ?></li>
                    <?php endif; ?>
                    <?php $contactPhone = $activeCampus['phone'] ?: getSetting('contact_phone'); if ($contactPhone): $phoneNumbers = array_values(array_filter(array_map('trim', preg_split('/\s*[\/\\\\]\s*/', $contactPhone)), static fn($p) => $p !== '')); ?>
                        <?php foreach ($phoneNumbers as $phoneNumber): $phoneWhatsAppUrl = getWhatsAppUrl($phoneNumber); ?>
                            <?php if ($phoneWhatsAppUrl): ?>
                                <li class="mb-2"><i class="bi bi-whatsapp me-1"></i><a href="<?= e($phoneWhatsAppUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($phoneNumber) ?></a></li>
                            <?php else: ?>
                                <li class="mb-2"><i class="bi bi-telephone me-1"></i> <?= e($phoneNumber) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php $contactEmails = parseContactEmails($activeCampus['email'] ?: getSetting('contact_email')); if ($contactEmails): ?>
                        <?php foreach ($contactEmails as $email): ?>
                            <li class="mb-2"><i class="bi bi-envelope me-1"></i><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <div class="d-flex gap-3 fs-5 mt-2">
                    <?php $facebookUrl = getFacebookUrl(getSetting('facebook_url')); if ($facebookUrl): ?><a href="<?= e($facebookUrl) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-facebook"></i></a><?php endif; ?>
                    <?php if (getSetting('linkedin_url')): ?><a href="<?= e(getSetting('linkedin_url')) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-linkedin"></i></a><?php endif; ?>
                    <?php if (getSetting('instagram_url')): ?><a href="<?= e(getSetting('instagram_url')) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-instagram"></i></a><?php endif; ?>
                    <?php if (getSetting('tiktok_url')): ?><a href="<?= e(getSetting('tiktok_url')) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-tiktok"></i></a><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer-bottom text-center">
            © <?= date('Y') ?> <?= e($siteName) ?>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="<?= BASE_URL ?>/assets/js/site.js?v=<?= @filemtime(__DIR__ . '/../assets/js/site.js') ?: '1' ?>"></script>
<script>window.HITAS_LIGHTBOX_DELAY = <?= max(2, min(60, (int)getSetting('lightbox_delay', '4') ?: 4)) ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/lightbox.js?v=<?= @filemtime(__DIR__ . '/../assets/js/lightbox.js') ?: '1' ?>"></script>

<?php $waChannelUrl = trim((string) getSetting('whatsapp_channel_url')); if ($waChannelUrl !== ''): ?>
<div class="wa-channel-overlay" id="waChannelOverlay" hidden></div>
<div class="wa-channel-popup" id="waChannelPopup" hidden role="dialog" aria-modal="true" aria-labelledby="waChannelPopupTitle">
    <button type="button" class="wa-channel-popup-close" id="waChannelPopupClose" aria-label="Fermer">&times;</button>
    <div class="wa-channel-popup-icon"><i class="bi bi-whatsapp"></i></div>
    <div class="wa-channel-popup-body">
        <strong id="waChannelPopupTitle">Suivez notre actualité sur WhatsApp</strong>
        <p>Rejoignez notre chaîne WhatsApp pour ne rien manquer des admissions et actualités HITAS.</p>
        <a href="<?= e($waChannelUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-hitas-primary" id="waChannelPopupLink">Rejoindre la chaîne</a>
    </div>
</div>
<script>
    (function () {
        // Invitation à rejoindre la chaîne WhatsApp : apparaît 6 secondes après
        // l'ouverture du site, au centre de l'écran (avec fond assombri) pour
        // bien attirer l'attention — une seule fois par session de navigation.
        var POPUP_DELAY_MS = 6000;
        var STORAGE_KEY = 'hitasWaChannelPromptShown';
        var popup = document.getElementById('waChannelPopup');
        var overlay = document.getElementById('waChannelOverlay');
        if (!popup || sessionStorage.getItem(STORAGE_KEY)) return;

        var closeBtn = document.getElementById('waChannelPopupClose');
        var link = document.getElementById('waChannelPopupLink');

        function showPopup() {
            overlay.hidden = false;
            popup.hidden = false;
            requestAnimationFrame(function () {
                overlay.classList.add('is-visible');
                popup.classList.add('is-visible');
            });
            sessionStorage.setItem(STORAGE_KEY, '1');
        }

        function hidePopup() {
            overlay.classList.remove('is-visible');
            popup.classList.remove('is-visible');
            setTimeout(function () {
                overlay.hidden = true;
                popup.hidden = true;
            }, 300);
        }

        var timer = setTimeout(showPopup, POPUP_DELAY_MS);
        closeBtn.addEventListener('click', function () {
            clearTimeout(timer);
            hidePopup();
        });
        overlay.addEventListener('click', hidePopup);
        link.addEventListener('click', hidePopup);
    })();
</script>
<?php endif; ?>
</body>
</html>
