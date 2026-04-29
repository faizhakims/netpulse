document.addEventListener('DOMContentLoaded', function () {

    const pingBtn = document.getElementById('pingBtn');
    const rebootBtn = document.getElementById('rebootBtn');

    const DEVICE_NAME = window.DEVICE_NAME;
    const CSRF_TOKEN  = window.CSRF_TOKEN;

    /* ───── TOAST ───── */
    function showToast(message, type = 'success') {
        let container = document.getElementById('toastContainer');

        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.textContent = message;
        toast.style.cssText = `
            background:${type === 'error' ? '#ef4444' : '#10b981'};
            color:white;
            padding:10px 16px;
            margin-top:10px;
            border-radius:8px;
            font-size:14px;
            box-shadow:0 4px 12px rgba(0,0,0,0.15);
        `;

        container.appendChild(toast);

        setTimeout(() => toast.remove(), 3000);
    }

    /* ───── PING ───── */
    if (pingBtn) {
        pingBtn.addEventListener('click', async function () {
            const icon = this.querySelector('.material-symbols-outlined');
            const orig = icon.textContent;

            pingBtn.disabled = true;

            try {
                icon.textContent = 'sync';
                icon.style.animation = 'spin 0.8s linear infinite';

                const res = await fetch('/device/ping', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({
                        device: DEVICE_NAME
                    })
                });

                if (!res.ok) throw new Error();

                const data = await res.json();

                showToast(`Ping OK (${data.latency_ms} ms)`);

            } catch (err) {
                console.error(err);
                showToast('Ping gagal!', 'error');
            } finally {
                pingBtn.disabled = false;
                icon.textContent = orig;
                icon.style.animation = '';
            }
        });
    }

    /* ───── REBOOT ───── */
    if (rebootBtn) {
        let pressTimer = null;
        const HOLD_DURATION = 3000;

        function clearTimer() {
            if (pressTimer) clearTimeout(pressTimer);
            pressTimer = null;
        }

        function startHold() {
            rebootBtn.classList.add('rebooting');

            pressTimer = setTimeout(async () => {
                try {
                    rebootBtn.disabled = true;

                    const res = await fetch('/device/reboot', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN
                        },
                        body: JSON.stringify({
                            device: DEVICE_NAME
                        })
                    });

                    if (!res.ok) throw new Error();

                    const data = await res.json();

                    showToast(data.message || 'Reboot dikirim');

                } catch (err) {
                    console.error(err);
                    showToast('Reboot gagal!', 'error');
                }

                rebootBtn.classList.remove('rebooting');
                rebootBtn.disabled = false;
                clearTimer();
            }, HOLD_DURATION);
        }

        function cancelHold() {
            rebootBtn.classList.remove('rebooting');
            clearTimer();
        }

        rebootBtn.addEventListener('mousedown', startHold);
        rebootBtn.addEventListener('mouseup', cancelHold);
        rebootBtn.addEventListener('mouseleave', cancelHold);

        rebootBtn.addEventListener('touchstart', startHold);
        rebootBtn.addEventListener('touchend', cancelHold);
        rebootBtn.addEventListener('touchcancel', cancelHold);
    }

});
