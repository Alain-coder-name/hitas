/* =========================================================================
   Apparition douce des sections au défilement (.reveal) — VOLONTAIREMENT
   isolée ici, en tout premier et dans son propre bloc protégé (try/catch).
   Sans ça, la moindre erreur JavaScript ailleurs sur la page (dans une
   fonctionnalité annexe : bulles, curseur, diaporama...) arrêtait
   l'exécution du script AVANT que ce code ne s'exécute — et comme la
   quasi-totalité des sections du site utilisent ".reveal" (invisibles tant
   que le script ne les révèle pas), toute la page semblait "blanche".
   Isolé ici, ce système fonctionne désormais MÊME si autre chose plante.
   ========================================================================= */
document.addEventListener('DOMContentLoaded', function () {
    try {
        var reduceMotionReveal = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var revealTargets = document.querySelectorAll('.reveal');

        if (reduceMotionReveal || !('IntersectionObserver' in window)) {
            revealTargets.forEach(function (el) { el.classList.add('is-visible'); });
        } else {
            var revealObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        revealObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

            revealTargets.forEach(function (el, i) {
                el.style.transitionDelay = Math.min(i % 4, 3) * 80 + 'ms';
                revealObserver.observe(el);
            });

            // Filet de sécurité supplémentaire : si, pour une raison
            // quelconque (navigateur particulier, mise en cache d'une
            // ancienne version du script...), certaines sections restent
            // invisibles trop longtemps, on les force à apparaître plutôt
            // que de risquer une page bloquée en blanc indéfiniment.
            setTimeout(function () {
                document.querySelectorAll('.reveal:not(.is-visible)').forEach(function (el) {
                    el.classList.add('is-visible');
                });
            }, 2500);
        }
    } catch (e) {
        // Filet de sécurité ultime : si même ce bloc échoue pour une raison
        // imprévue, on rend directement tout visible plutôt que de risquer
        // une page qui reste cachée.
        document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('is-visible'); });
    }
});

document.addEventListener('DOMContentLoaded', function () {
  try {
    // --- Ouverture automatique d'une question FAQ ciblée par ancre (ex: faq.php#faq-admission) ---
    var faqAccordion = document.getElementById('faqAccordion');
    if (faqAccordion && window.location.hash) {
        var faqTarget = document.querySelector(window.location.hash);
        if (faqTarget && faqTarget.classList.contains('accordion-item')) {
            var faqCollapseEl = faqTarget.querySelector('.accordion-collapse');
            var faqButtonEl = faqTarget.querySelector('.accordion-button');
            if (faqCollapseEl) {
                faqAccordion.querySelectorAll('.accordion-collapse.show').forEach(function (openEl) {
                    if (openEl !== faqCollapseEl) {
                        if (window.bootstrap) {
                            bootstrap.Collapse.getOrCreateInstance(openEl).hide();
                        } else {
                            openEl.classList.remove('show');
                        }
                    }
                });
                if (window.bootstrap) {
                    bootstrap.Collapse.getOrCreateInstance(faqCollapseEl, { toggle: false }).show();
                } else {
                    faqCollapseEl.classList.add('show');
                }
            }
            if (faqButtonEl) {
                faqButtonEl.classList.remove('collapsed');
                faqButtonEl.setAttribute('aria-expanded', 'true');
            }
            setTimeout(function () {
                faqTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 250);
        }
    }

    // --- Diaporama du bloc d'accueil (défilement automatique, durée et texte par slide) ---
    var heroSlideshow = document.querySelector('.hero-slideshow');
    if (heroSlideshow) {
        var slides = Array.prototype.slice.call(heroSlideshow.querySelectorAll('.hero-slide'));
        var reduceMotionSlides = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Texte par défaut du hero (bloc "Accueil"), utilisé quand une slide ne définit pas son propre texte
        var heroEyebrowEl = document.querySelector('[data-hero-eyebrow]');
        var heroTitleEl = document.querySelector('[data-hero-title]');
        var heroLeadEl = document.querySelector('[data-hero-lead]');
        var heroCtaEl = document.querySelector('[data-hero-cta]');
        var defaultHeroTitle = heroTitleEl ? heroTitleEl.textContent : '';
        var defaultHeroLead = heroLeadEl ? heroLeadEl.textContent : '';
        var defaultHeroCtaText = heroCtaEl ? heroCtaEl.textContent : '';
        var defaultHeroCtaLink = heroCtaEl ? heroCtaEl.getAttribute('href') : '';

        function applySlideText(slide) {
            if (!slide) { return; }
            var title = slide.dataset.title || '';
            var subtitle = slide.dataset.subtitle || '';
            var btnText = slide.dataset.btnText || '';
            var btnLink = slide.dataset.btnLink || '';

            if (heroTitleEl) { heroTitleEl.textContent = title || defaultHeroTitle; }
            if (heroLeadEl) { heroLeadEl.textContent = subtitle || defaultHeroLead; }
            if (heroCtaEl && btnText) {
                heroCtaEl.textContent = btnText;
                if (btnLink) { heroCtaEl.setAttribute('href', btnLink); }
            } else if (heroCtaEl) {
                heroCtaEl.textContent = defaultHeroCtaText;
                heroCtaEl.setAttribute('href', defaultHeroCtaLink);
            }
        }

        if (slides.length) {
            applySlideText(slides[0]);
        }

        if (slides.length > 1 && !reduceMotionSlides) {
            var currentSlide = 0;
            var advance = function () {
                slides[currentSlide].classList.remove('is-active');
                currentSlide = (currentSlide + 1) % slides.length;
                var next = slides[currentSlide];
                next.classList.add('is-active');
                applySlideText(next);
                var video = next.querySelector('.hero-slide-video');
                if (video) {
                    var playPromise = video.play();
                    if (playPromise && playPromise.catch) { playPromise.catch(function () {}); }
                }
                var duration = parseInt(next.dataset.duration, 10) || 6000;
                setTimeout(advance, duration);
            };
            var firstDuration = parseInt(slides[0].dataset.duration, 10) || 6000;
            setTimeout(advance, firstDuration);
        }
    }

    // --- Cluster de bulles "Actualités" du hero : une bulle grandit à son
    //     tour pour révéler une actualité (photo, titre, lien), puis cède
    //     la place à la suivante. S'il y a plus de 6 actualités, les 6
    //     bulles servent d'emplacements tournants pour toutes les défiler. ---
    var heroBubbles = document.querySelector('.hero-bubbles');
    if (heroBubbles) {
        var bubbleNewsData = [];
        try {
            bubbleNewsData = JSON.parse(heroBubbles.getAttribute('data-news') || '[]');
        } catch (e) {
            bubbleNewsData = [];
        }
        var bubbleEls = Array.prototype.slice.call(heroBubbles.querySelectorAll('.hero-bubble'));
        var reduceMotionBubbles = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var bubbleDuration = parseInt(heroBubbles.dataset.duration, 10) || 4500;

        function fillBubble(bubble, item) {
            var titleEl = bubble.querySelector('.hero-bubble-title');
            var linkEl = bubble.querySelector('.hero-bubble-link');
            var photoEl = bubble.querySelector('.hero-bubble-photo');
            if (titleEl) { titleEl.textContent = item.title; }
            if (linkEl) {
                linkEl.setAttribute('href', item.url);
                if (item.blank) {
                    linkEl.setAttribute('target', '_blank');
                    linkEl.setAttribute('rel', 'noopener sponsored');
                } else {
                    linkEl.setAttribute('target', '_self');
                    linkEl.removeAttribute('rel');
                }
            }
            if (photoEl) {
                // Vidéo prioritaire sur la photo, si fournie (voir admin > Actualités).
                var existingVideo = photoEl.querySelector('video');
                if (item.video) {
                    photoEl.style.backgroundImage = 'none';
                    if (!existingVideo) {
                        existingVideo = document.createElement('video');
                        existingVideo.className = 'hero-bubble-video';
                        existingVideo.muted = true;
                        existingVideo.loop = true;
                        existingVideo.autoplay = true;
                        existingVideo.playsInline = true;
                        photoEl.appendChild(existingVideo);
                    }
                    if (existingVideo.getAttribute('src') !== item.video) {
                        existingVideo.setAttribute('src', item.video);
                    }
                    var playPromise = existingVideo.play();
                    if (playPromise && playPromise.catch) { playPromise.catch(function () {}); }
                } else {
                    if (existingVideo) { existingVideo.pause(); existingVideo.remove(); }
                    photoEl.style.backgroundImage = item.image ? "url('" + item.image + "')" : 'none';
                }
            }
        }

        if (bubbleEls.length && bubbleNewsData.length) {
            var activeSlot = 0;
            var newsIndex = 0;

            fillBubble(bubbleEls[0], bubbleNewsData[0]);
            bubbleEls[0].classList.add('is-active');

            if (bubbleNewsData.length > 1 && !reduceMotionBubbles) {
                var advanceBubbles = function () {
                    var prevBubble = bubbleEls[activeSlot];
                    prevBubble.classList.remove('is-active');

                    activeSlot = (activeSlot + 1) % bubbleEls.length;
                    newsIndex = (newsIndex + 1) % bubbleNewsData.length;

                    var nextBubble = bubbleEls[activeSlot];
                    fillBubble(nextBubble, bubbleNewsData[newsIndex]);
                    nextBubble.classList.add('is-active');

                    setTimeout(advanceBubbles, bubbleDuration);
                };
                setTimeout(advanceBubbles, bubbleDuration);
            }
        }
    }

    // --- Diaporamas par groupes de 3 ("Filières phares" et "Actualités") ---
    // Même comportement qu'avant (lecture automatique, rythme piloté par
    // data-duration) + contrôles manuels : les points deviennent cliquables.
    function initGroupSlideshow(root, slideSelector, dotSelector, defaultDuration) {
        if (!root) { return; }
        var slides = Array.prototype.slice.call(root.querySelectorAll(slideSelector));
        var dots = Array.prototype.slice.call(root.querySelectorAll(dotSelector));
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (slides.length < 2) { return; }

        var duration = parseInt(root.dataset.duration, 10) || defaultDuration;
        var index = 0;
        var timer = null;

        function show(newIndex) {
            slides[index].classList.remove('is-active');
            if (dots[index]) { dots[index].classList.remove('is-active'); }
            index = (newIndex + slides.length) % slides.length;
            slides[index].classList.add('is-active');
            if (dots[index]) { dots[index].classList.add('is-active'); }
        }

        function stop() {
            if (timer) { clearInterval(timer); timer = null; }
        }

        function start() {
            if (reduceMotion) { return; }
            stop();
            timer = setInterval(function () { show(index + 1); }, duration);
        }

        dots.forEach(function (dot, i) {
            dot.style.cursor = 'pointer';
            dot.addEventListener('click', function () {
                show(i);
                start();
            });
        });

        start();
    }

    initGroupSlideshow(document.querySelector('.pillars-slideshow'), '.pillars-slide', '.pillars-dot', 6000);
    initGroupSlideshow(document.querySelector('.news-slideshow'), '.news-slide', '.news-dot', 6000);
    // Galerie "Vie étudiante" (À propos) : une catégorie = un diaporama indépendant.
    document.querySelectorAll('.gallery-slideshow').forEach(function (root) {
        initGroupSlideshow(root, '.gallery-slide', '.gallery-dot', 6000);
    });
    // Diaporamas réutilisables gérés depuis /admin/page_galleries
    // (À propos, Notre mission, Conditions d'admission).
    document.querySelectorAll('.about-image-slideshow').forEach(function (root) {
        initGroupSlideshow(root, '.about-image-slide', '.about-image-dot', 5000);
    });
    initGroupSlideshow(document.querySelector('.admissions-visual-slideshow'), '.admissions-visual-slide', '.admissions-visual-dot', 5000);

    // --- Ombre de la navbar au scroll ---
    // Seuils différents pour activer/désactiver (hystérésis) + un seul calcul
    // par frame (requestAnimationFrame) : évite que la navbar rétrécisse et
    // reprenne sa taille en boucle quand le défilement oscille autour d'un
    // même point (cause des "mouvements bizarres" précédemment observés).
    var navbar = document.getElementById('siteNavbar');
    if (navbar) {
        var isScrolled = false;
        var scrollTicking = false;

        var applyNavbarState = function () {
            var y = window.scrollY;
            if (!isScrolled && y > 60) {
                isScrolled = true;
                navbar.classList.add('is-scrolled');
            } else if (isScrolled && y < 20) {
                isScrolled = false;
                navbar.classList.remove('is-scrolled');
            }
            scrollTicking = false;
        };

        applyNavbarState();
        window.addEventListener('scroll', function () {
            if (!scrollTicking) {
                window.requestAnimationFrame(applyNavbarState);
                scrollTicking = true;
            }
        }, { passive: true });
    }
  } catch (e) {
    // Filet de sécurité : si une fonctionnalité annexe plante, ça
    // n'empêche plus le reste du site de fonctionner (l'apparition des
    // sections, elle, est de toute façon protégée séparément plus haut).
    if (window.console && console.error) { console.error('HITAS site.js:', e); }
  }
});

/* =========================================================
   Notifications (toasts) — transforme automatiquement tout
   message .alert-success / .alert-danger / .alert-warning déjà
   présent dans la page (admin ou site public) en notification
   flottante : verte si succès, rouge si erreur/avertissement.
   ========================================================= */
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
        // Uniquement les messages explicitement marqués "js-toast-alert" —
        // PAS tous les .alert-warning/.alert-danger/.alert-success du site.
        // (Bug corrigé : ça capturait aussi des messages fixes de la page,
        // comme "Aucun avis publié pour le moment", et les faisait
        // disparaître de leur emplacement pour les transformer en
        // notification flottante — cassant visuellement la page.)
        var alerts = document.querySelectorAll('.js-toast-alert:not([data-hitas-toast])');
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

            var timer = setTimeout(function () { hitasCloseToast(toast); }, 4000);
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

/* =========================================================================
   Autocomplétion de la barre de recherche "Quelle filière veux-tu
   étudier ?" (page Programmes). Saisie libre : les filières correspondantes
   s'affichent au fur et à mesure, sélection au clic ou au clavier.
   ========================================================================= */
(function () {
    var wrapper = document.querySelector('.finder-autocomplete');
    if (!wrapper) return;

    var input = wrapper.querySelector('.finder-autocomplete-input');
    var list = wrapper.querySelector('.finder-autocomplete-list');
    var form = document.getElementById('programFinderForm');
    var programs = [];

    try {
        programs = JSON.parse(wrapper.getAttribute('data-programs') || '[]');
    } catch (e) {
        programs = [];
    }

    var activeIndex = -1;
    var currentMatches = [];

    function normalize(str) {
        return (str || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function closeList() {
        list.hidden = true;
        list.innerHTML = '';
        input.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
        currentMatches = [];
    }

    // Cliquer une suggestion est un raccourci pratique (pr\u00e9-remplit le texte
    // et lance la recherche), mais ce n'est jamais obligatoire : taper un
    // mot-cl\u00e9 quelconque puis "Rechercher" fonctionne tout autant, la
    // recherche se fait par mot-cl\u00e9 c\u00f4t\u00e9 serveur sur toutes les fili\u00e8res.
    function selectProgram(program) {
        input.value = program.title;
        closeList();
        if (form) form.submit();
    }

    function renderMatches(query) {
        var q = normalize(query);
        var matches = q === ''
            ? programs.slice(0, 8)
            : programs.filter(function (p) {
                return normalize(p.title).indexOf(q) !== -1 || normalize(p.keywords || '').indexOf(q) !== -1;
            }).slice(0, 8);

        currentMatches = matches;
        activeIndex = -1;
        list.innerHTML = '';

        if (!matches.length) {
            var emptyLi = document.createElement('li');
            emptyLi.className = 'is-empty';
            emptyLi.textContent = 'Aucune filière ne correspond à votre recherche.';
            list.appendChild(emptyLi);
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            return;
        }

        matches.forEach(function (program, index) {
            var li = document.createElement('li');
            li.textContent = program.title;
            li.setAttribute('role', 'option');
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectProgram(program);
            });
            li.addEventListener('mouseenter', function () {
                setActive(index);
            });
            list.appendChild(li);
        });
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function setActive(index) {
        var items = list.querySelectorAll('li:not(.is-empty)');
        items.forEach(function (item) { item.classList.remove('is-active'); });
        if (items[index]) {
            items[index].classList.add('is-active');
            items[index].scrollIntoView({ block: 'nearest' });
        }
        activeIndex = index;
    }

    input.addEventListener('input', function () {
        renderMatches(input.value);
    });

    input.addEventListener('focus', function () {
        renderMatches(input.value);
    });

    input.addEventListener('keydown', function (e) {
        if (list.hidden) return;
        var items = list.querySelectorAll('li:not(.is-empty)');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(Math.min(activeIndex + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(Math.max(activeIndex - 1, 0));
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0 && currentMatches[activeIndex]) {
                e.preventDefault();
                selectProgram(currentMatches[activeIndex]);
            }
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) closeList();
    });
})();

/* =========================================================================
   Pack "chic" — fait suivre le spotlight doré exactement la position du
   curseur sur les images des diaporamas (Filières phares, Actualités,
   Galerie Vie étudiante).
   ========================================================================= */
(function () {
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) return;

    function trackCursor(el) {
        var ticking = false;
        var lastEvent = null;

        function applySpotlight() {
            var rect = el.getBoundingClientRect();
            if (rect.width && rect.height) {
                var x = ((lastEvent.clientX - rect.left) / rect.width) * 100;
                var y = ((lastEvent.clientY - rect.top) / rect.height) * 100;
                el.style.setProperty('--mx', x + '%');
                el.style.setProperty('--my', y + '%');
            }
            ticking = false;
        }

        el.addEventListener('mousemove', function (e) {
            lastEvent = e;
            if (!ticking) {
                window.requestAnimationFrame(applySpotlight);
                ticking = true;
            }
        });
        el.addEventListener('mouseleave', function () {
            el.style.setProperty('--mx', '50%');
            el.style.setProperty('--my', '50%');
        });
    }

    document.querySelectorAll('.program-img, .gallery-card-img').forEach(trackCursor);
})();

/* =========================================================================
   Sélecteur de langue (navbar) — pilote le widget externe Google
   Traduction en coulisses, sans jamais afficher son interface par défaut.
   Lit/écrit un cookie "googtrans" que Google Traduction reconnaît lui-même
   pour savoir dans quelle langue re-générer la page au chargement.
   ========================================================================= */
(function () {
    var switcherItems = document.querySelectorAll('.lang-switcher-item');
    if (!switcherItems.length) return;

    var currentLabel = document.querySelector('.lang-switcher-current');

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }

    function setLangCookie(lang) {
        var value = lang === 'fr' ? '/fr/fr' : '/fr/' + lang;
        var host = location.hostname;
        document.cookie = 'googtrans=' + value + '; path=/';
        if (host && host.indexOf('.') !== -1) {
            document.cookie = 'googtrans=' + value + '; path=/; domain=.' + host;
        }
    }

    // Applique la langue en pilotant directement le menu déroulant que
    // Google Traduction injecte lui-même dans la page (le plus fiable :
    // évite d'attendre un rechargement complet quand c'est possible).
    function applyViaGoogleSelect(lang) {
        var select = document.querySelector('#google_translate_element select.goog-te-combo');
        if (!select) return false;
        select.value = lang;
        select.dispatchEvent(new Event('change'));
        return true;
    }

    function switchLanguage(lang) {
        setLangCookie(lang);
        switcherItems.forEach(function (item) {
            item.classList.toggle('is-active', item.dataset.lang === lang);
        });
        if (currentLabel) { currentLabel.textContent = lang.toUpperCase(); }

        if (lang === 'fr') {
            // Revenir au français : Google Traduction ne "dé-traduit" proprement
            // qu'en rechargeant la page une fois le cookie remis à jour.
            window.location.reload();
            return;
        }

        var applied = applyViaGoogleSelect(lang);
        if (!applied) {
            // Le widget Google n'est pas encore chargé : quelques tentatives
            // rapprochées, sinon on retombe sur un simple rechargement
            // (le cookie qu'on vient de poser sera lu au prochain chargement).
            var attempts = 0;
            var retry = setInterval(function () {
                attempts++;
                if (applyViaGoogleSelect(lang) || attempts > 15) {
                    clearInterval(retry);
                    if (attempts > 15) { window.location.reload(); }
                }
            }, 300);
        }
    }

    switcherItems.forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            switchLanguage(item.dataset.lang);
        });
    });

    // Reflète la langue déjà active (cookie posé lors d'une visite précédente).
    var existing = getCookie('googtrans');
    if (existing) {
        var parts = existing.split('/');
        var activeLang = parts[2] || 'fr';
        switcherItems.forEach(function (item) {
            item.classList.toggle('is-active', item.dataset.lang === activeLang);
        });
        if (currentLabel) { currentLabel.textContent = activeLang.toUpperCase(); }
    }
})();
