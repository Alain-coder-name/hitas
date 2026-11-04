/* =========================================================================
   Visionneuse photos + vidéos (galerie « Vie étudiante », photos d'une
   actualité) : flèches avant/arrière, miniatures, défilement automatique
   réglable depuis l'admin (Paramètres > Visionneuse), lecture des vidéos
   avec leur son.

   Déclaration côté HTML (aucune configuration JS) :
     data-lb-group="nom"   éléments d'un même diaporama (ordre du DOM)
     data-lb-type="image" | "video"
     data-lb-src="URL du média en grand"
     data-lb-poster="URL d'aperçu (vidéo)"        (facultatif)
     data-lb-thumb="URL de la miniature"            (facultatif)
     data-lb-title / data-lb-text                   (légende, facultatif)

   Scène intégrée à une page (article) : un conteneur [data-stage] dont les
   miniatures portent data-stage-thumb : un clic sur une miniature la fait
   prendre la place de l'image principale ; un clic sur l'image principale
   ouvre la visionneuse plein écran.
   ========================================================================= */
(function () {
    'use strict';

    var DEFAULT_DELAY = (window.HITAS_LIGHTBOX_DELAY || 4) * 1000;

    function qsa(root, sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); }
    function el(tag, cls, html) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (html) n.innerHTML = html;
        return n;
    }

    function itemFrom(node) {
        return {
            node: node,
            type: node.getAttribute('data-lb-type') === 'video' ? 'video' : 'image',
            src: node.getAttribute('data-lb-src') || '',
            poster: node.getAttribute('data-lb-poster') || '',
            thumb: node.getAttribute('data-lb-thumb') || node.getAttribute('data-lb-poster') || node.getAttribute('data-lb-src') || '',
            title: node.getAttribute('data-lb-title') || '',
            text: node.getAttribute('data-lb-text') || ''
        };
    }

    function groupItems(name) {
        return qsa(document, '[data-lb-group="' + String(name).replace(/"/g, '\\"') + '"]')
            .map(itemFrom)
            .filter(function (it) { return it.src; });
    }

    /* ---------------------------------------------------------------------
       Visionneuse plein écran
       --------------------------------------------------------------------- */
    var box = null;
    var state = { items: [], index: 0, playing: true, timer: null, delay: DEFAULT_DELAY, opener: null, tStart: 0 };

    function build() {
        if (box) return box;
        box = el('div', 'hlb');
        box.hidden = true;
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-label', 'Visionneuse de photos et vidéos');
        box.innerHTML =
            '<button type="button" class="hlb-close" aria-label="Fermer"><i class="bi bi-x-lg"></i></button>' +
            '<div class="hlb-main">' +
                '<button type="button" class="hlb-nav hlb-prev" aria-label="Précédent"><i class="bi bi-chevron-left"></i></button>' +
                '<div class="hlb-stage"></div>' +
                '<button type="button" class="hlb-nav hlb-next" aria-label="Suivant"><i class="bi bi-chevron-right"></i></button>' +
            '</div>' +
            '<div class="hlb-bar">' +
                '<div class="hlb-caption"><strong class="hlb-title"></strong><p class="hlb-text"></p></div>' +
                '<div class="hlb-controls">' +
                    '<button type="button" class="hlb-play" aria-label="Pause"><i class="bi bi-pause-fill"></i></button>' +
                    '<span class="hlb-count"></span>' +
                '</div>' +
            '</div>' +
            '<div class="hlb-progress"><span></span></div>' +
            '<div class="hlb-thumbs"></div>';
        document.body.appendChild(box);

        box.querySelector('.hlb-close').addEventListener('click', close);
        box.querySelector('.hlb-prev').addEventListener('click', function () { go(-1, true); });
        box.querySelector('.hlb-next').addEventListener('click', function () { go(1, true); });
        box.querySelector('.hlb-play').addEventListener('click', function () { setPlaying(!state.playing); });
        box.addEventListener('click', function (e) {
            if (e.target === box || e.target.classList.contains('hlb-main') || e.target.classList.contains('hlb-stage')) close();
        });

        // Balayage tactile (téléphone) : gauche = suivant, droite = précédent.
        var sx = null;
        box.addEventListener('touchstart', function (e) { sx = e.touches.length === 1 ? e.touches[0].clientX : null; }, { passive: true });
        box.addEventListener('touchend', function (e) {
            if (sx === null) return;
            var dx = e.changedTouches[0].clientX - sx;
            sx = null;
            if (Math.abs(dx) > 50) go(dx < 0 ? 1 : -1, true);
        }, { passive: true });

        document.addEventListener('keydown', function (e) {
            if (box.hidden) return;
            if (e.key === 'Escape') close();
            else if (e.key === 'ArrowRight') go(1, true);
            else if (e.key === 'ArrowLeft') go(-1, true);
            else if (e.key === ' ' && e.target.tagName !== 'VIDEO' && e.target.tagName !== 'BUTTON') { e.preventDefault(); setPlaying(!state.playing); }
        });
        return box;
    }

    function clearTimer() {
        if (state.timer) { clearTimeout(state.timer); state.timer = null; }
        var bar = box && box.querySelector('.hlb-progress span');
        if (bar) { bar.style.transition = 'none'; bar.style.width = '0'; }
    }

    function setPlaying(on) {
        state.playing = on;
        var btn = box.querySelector('.hlb-play');
        btn.setAttribute('aria-label', on ? 'Pause' : 'Lecture automatique');
        btn.innerHTML = '<i class="bi ' + (on ? 'bi-pause-fill' : 'bi-play-fill') + '"></i>';
        if (on) schedule(); else clearTimer();
    }

    // Programme le passage automatique à la suivante (images uniquement :
    // une vidéo n'est jamais coupée, on attend sa fin).
    function schedule() {
        clearTimer();
        if (!state.playing || state.items.length < 2) return;
        var current = state.items[state.index];
        if (current.type === 'video') return;
        var bar = box.querySelector('.hlb-progress span');
        void bar.offsetWidth;
        bar.style.transition = 'width ' + state.delay + 'ms linear';
        bar.style.width = '100%';
        state.timer = setTimeout(function () { go(1, false); }, state.delay);
    }

    function go(step, manual) {
        var n = state.items.length;
        if (!n) return;
        show((state.index + step + n) % n);
        if (manual && !state.playing) clearTimer();
    }

    function show(i) {
        clearTimer();
        state.index = i;
        var it = state.items[i];
        var stage = box.querySelector('.hlb-stage');
        stage.innerHTML = '';
        var media;
        if (it.type === 'video') {
            media = el('video', 'hlb-media');
            media.controls = true;
            media.autoplay = true;
            media.playsInline = true;
            if (it.poster) media.poster = it.poster;
            media.src = it.src;
            media.addEventListener('ended', function () {
                if (state.playing && state.items.length > 1) go(1, false);
            });
            var p = media.play && media.play();
            if (p && p.catch) p.catch(function () { /* le visiteur lancera la lecture lui-même */ });
        } else {
            media = el('img', 'hlb-media');
            media.alt = it.title || '';
            media.src = it.src;
        }
        stage.appendChild(media);

        box.querySelector('.hlb-title').textContent = it.title;
        box.querySelector('.hlb-text').textContent = it.text;
        box.querySelector('.hlb-caption').hidden = !(it.title || it.text);
        box.querySelector('.hlb-count').textContent = (i + 1) + ' / ' + state.items.length;

        qsa(box, '.hlb-thumb').forEach(function (t, k) {
            t.classList.toggle('is-active', k === i);
            if (k === i && t.scrollIntoView) t.scrollIntoView({ block: 'nearest', inline: 'center' });
        });

        var multi = state.items.length > 1;
        qsa(box, '.hlb-nav, .hlb-play, .hlb-count, .hlb-thumbs').forEach(function (n) { n.hidden = !multi; });

        // Précharge la suivante pour un passage instantané.
        var next = state.items[(i + 1) % state.items.length];
        if (next && next.type === 'image') { var pre = new Image(); pre.src = next.src; }

        schedule();
    }

    function open(items, index, opener, delayMs) {
        if (!items.length) return;
        build();
        state.items = items;
        state.delay = delayMs || DEFAULT_DELAY;
        state.opener = opener || null;
        state.playing = true;

        var thumbs = box.querySelector('.hlb-thumbs');
        thumbs.innerHTML = '';
        items.forEach(function (it, k) {
            var b = el('button', 'hlb-thumb');
            b.type = 'button';
            b.setAttribute('aria-label', 'Voir ' + (k + 1) + ' sur ' + items.length);
            if (it.thumb && it.type === 'image') b.style.backgroundImage = 'url("' + it.thumb.replace(/"/g, '%22') + '")';
            else if (it.poster) b.style.backgroundImage = 'url("' + it.poster.replace(/"/g, '%22') + '")';
            if (it.type === 'video') b.appendChild(el('i', 'bi bi-play-circle-fill hlb-thumb-play'));
            b.addEventListener('click', function () { show(k); });
            thumbs.appendChild(b);
        });

        setPlaying(true);
        box.hidden = false;
        document.documentElement.classList.add('hlb-open');
        show(Math.max(0, Math.min(index, items.length - 1)));
        box.querySelector('.hlb-close').focus();
    }

    function close() {
        if (!box || box.hidden) return;
        clearTimer();
        var v = box.querySelector('video');
        if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
        box.querySelector('.hlb-stage').innerHTML = '';
        box.hidden = true;
        document.documentElement.classList.remove('hlb-open');
        if (state.opener && state.opener.focus) state.opener.focus();
    }

    /* ---------------------------------------------------------------------
       Clic sur un élément de galerie (hors scène intégrée)
       --------------------------------------------------------------------- */
    function openFromNode(node) {
        var group = node.getAttribute('data-lb-group');
        var items = groupItems(group);
        var idx = 0;
        items.forEach(function (it, k) { if (it.node === node) idx = k; });
        var delay = parseFloat(node.getAttribute('data-lb-delay'));
        open(items, idx, node, delay ? delay * 1000 : 0);
    }

    document.addEventListener('click', function (e) {
        var node = e.target.closest ? e.target.closest('[data-lb-group]') : null;
        if (!node || node.hasAttribute('data-stage-thumb')) return;
        e.preventDefault();
        openFromNode(node);
    });
    document.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches && e.target.matches('[data-lb-group]:not([data-stage-thumb])')) {
            e.preventDefault();
            openFromNode(e.target);
        }
    });

    /* ---------------------------------------------------------------------
       Scène intégrée (page d'une actualité)
       --------------------------------------------------------------------- */
    qsa(document, '[data-stage]').forEach(function (stageRoot) {
        var view = stageRoot.querySelector('[data-stage-view]');
        var thumbs = qsa(stageRoot, '[data-stage-thumb]');
        var items = thumbs.map(itemFrom);
        var current = 0;
        if (!view || !items.length) return;

        function render(i) {
            current = i;
            var it = items[i];
            view.innerHTML = '';
            var m;
            if (it.type === 'video') {
                m = el('video', 'stage-media');
                m.controls = true;
                m.playsInline = true;
                m.preload = 'metadata';
                if (it.poster) m.poster = it.poster;
                m.src = it.src;
            } else {
                m = el('img', 'stage-media stage-media--zoom');
                m.alt = it.title || '';
                m.src = it.src;
                m.title = 'Cliquer pour agrandir';
                m.addEventListener('click', function () {
                    open(items, current, m, parseFloat(stageRoot.getAttribute('data-lb-delay')) * 1000 || 0);
                });
            }
            view.appendChild(m);
            thumbs.forEach(function (t, k) { t.classList.toggle('is-active', k === i); t.setAttribute('aria-current', k === i ? 'true' : 'false'); });
            var counter = stageRoot.querySelector('[data-stage-count]');
            if (counter) counter.textContent = (i + 1) + ' / ' + items.length;
        }

        thumbs.forEach(function (t, k) {
            t.addEventListener('click', function (e) { e.preventDefault(); render(k); });
        });
        var prev = stageRoot.querySelector('[data-stage-prev]');
        var next = stageRoot.querySelector('[data-stage-next]');
        if (prev) prev.addEventListener('click', function () { render((current - 1 + items.length) % items.length); });
        if (next) next.addEventListener('click', function () { render((current + 1) % items.length); });
        var expand = stageRoot.querySelector('[data-stage-expand]');
        if (expand) expand.addEventListener('click', function () {
            open(items, current, expand, parseFloat(stageRoot.getAttribute('data-lb-delay')) * 1000 || 0);
        });

        render(0);
    });
})();
