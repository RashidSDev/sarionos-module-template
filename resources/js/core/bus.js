window.SarionOS = window.SarionOS || {};

(function () {
    const listeners = {};

    SarionOS.bus = {
        on(event, cb) {
            listeners[event] = listeners[event] || [];
            listeners[event].push(cb);
        },

        off(event, cb) {
            if (!listeners[event]) return;
            listeners[event] = listeners[event].filter(fn => fn !== cb);
        },

        emit(event, payload) {
            if (!listeners[event]) return;
            listeners[event].forEach(fn => fn(payload));
        }
    };
})();
