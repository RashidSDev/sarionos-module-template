/* ============================================================================
   SARIONOS — RIGHT PANEL MANAGER (PACKAGE SAFE)
   - Does NOT assume SarionOS.bus exists
   - Attaches when bus becomes available
============================================================================ */

(function () {

    function init(bus) {
        console.log('[RightPanel] initializing');

        const panel    = document.getElementById('so-right-panel');
        const backdrop = document.getElementById('so-right-panel-backdrop');
        const titleEl  = document.getElementById('so-right-panel-title');
        const bodyEl   = document.getElementById('so-right-panel-body');

        if (!panel || !backdrop || !titleEl || !bodyEl) {
            console.warn('[RightPanel] DOM not ready, aborting init');
            return;
        }

        let state = {
            open: false,
            type: null, // 'editor' | 'filters'
        };


        function openPanel({ type, title, content }) {
            if (state.open) closePanel(true);

            state.open = true;
            state.type = type;



            titleEl.textContent = title || '';
            bodyEl.innerHTML = '';

            if (content) {
                bodyEl.appendChild(content);
            }

            panel.classList.remove('translate-x-full');
            backdrop.classList.remove('pointer-events-none');
            backdrop.classList.add('opacity-100');
        }

        function closePanel(force = false) {
            if (!state.open && !force) return;

            state.open = false;
            state.type = null;

            panel.classList.add('translate-x-full');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('pointer-events-none');

            titleEl.textContent = '';
            bodyEl.innerHTML = '';
        }

        // BUS bindings
        bus.on('right-panel:open', (payload) => {
            if (!payload || !payload.content) return;

            // editor > filters priority
            if (state.open && state.type === 'editor' && payload.type !== 'editor') {
                return;
            }

            openPanel(payload);
        });

        bus.on('right-panel:close', () => closePanel());
        bus.on('right-panel:force-close', () => closePanel(true));

        // UI events
        panel.addEventListener('click', (e) => {
            if (e.target.closest('[data-so-right-panel-close]')) {
                bus.emit('right-panel:close');
            }
        });

        backdrop.addEventListener('click', () => {
            bus.emit('right-panel:close');
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                bus.emit('right-panel:close');
            }
        });

        // Optional debug hook
        window.SarionOS = window.SarionOS || {};
        window.SarionOS.rightPanel = {
            open: openPanel,
            close: closePanel
        };

        console.log('[RightPanel] ready');
    }

    // ────────────────────────────────────────────────
    // WAIT FOR BUS (same spirit as workspace.js)
    // ────────────────────────────────────────────────
    function waitForBus() {
        if (window.SarionOS?.bus) {
            init(window.SarionOS.bus);
            return;
        }

        console.log('[RightPanel] waiting for SarionOS.bus...');
        setTimeout(waitForBus, 50);
    }

    document.addEventListener('DOMContentLoaded', waitForBus);

})();
