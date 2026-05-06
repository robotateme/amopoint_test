(function () {
    'use strict';

    var endpoint = document.currentScript && document.currentScript.dataset.endpoint
        ? document.currentScript.dataset.endpoint
        : '/api/visits';

    function deviceType() {
        var ua = navigator.userAgent || '';

        if (/tablet|ipad|playbook|silk/i.test(ua)) {
            return 'tablet';
        }

        if (/mobile|iphone|ipod|android|blackberry|opera mini|iemobile/i.test(ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    function getFingerprint() {
        var key = 'amopoint_visit_fingerprint';
        var stored = localStorage.getItem(key);

        if (stored) {
            return stored;
        }

        var fingerprint = [
            Date.now(),
            Math.random().toString(16).slice(2),
            navigator.userAgent,
            screen.width + 'x' + screen.height
        ].join(':');

        localStorage.setItem(key, fingerprint);

        return fingerprint;
    }

    function sendVisit() {
        var payload = {
            fingerprint: getFingerprint(),
            device: deviceType(),
            page_url: location.href,
            referrer: document.referrer || ''
        };

        fetch(endpoint, {
            method: 'POST',
            mode: 'cors',
            credentials: 'omit',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            keepalive: true
        }).catch(function () {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sendVisit, { once: true });
    } else {
        sendVisit();
    }
})();
