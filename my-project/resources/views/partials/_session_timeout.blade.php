<script>
(function () {
    const timeoutMinutes = 15;
    const timeoutMilliseconds = timeoutMinutes * 60 * 1000;
    const logoutUrl = @json($logoutUrl);
    let inactivityTimer;
    let loggingOut = false;

    function logoutDueToInactivity() {
        if (loggingOut) return;
        loggingOut = true;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = logoutUrl;

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
    }

    function resetInactivityTimer() {
        window.clearTimeout(inactivityTimer);
        inactivityTimer = window.setTimeout(logoutDueToInactivity, timeoutMilliseconds);
    }

    ['mousedown', 'mousemove', 'keydown', 'touchstart', 'scroll', 'click'].forEach(function (eventName) {
        document.addEventListener(eventName, resetInactivityTimer, { passive: true });
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) resetInactivityTimer();
    });

    resetInactivityTimer();
})();
</script>
