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

                let latency = data.latency_ms ?? data.latency ?? data.time;
                let msg = latency !== undefined ? `Ping OK (${latency} ms)` : (data.message || 'Ping OK');
                showToast(msg);

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

    /* ───── DELETE ───── */
    const deleteBtn = document.querySelector('.btn-delete');

    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            openDeleteModal();
        });
    }

    function openDeleteModal() {
        // Buat modal konfirmasi delete
        const existing = document.getElementById('deleteModal');
        if (existing) { existing.style.display = 'flex'; return; }

        const modal = document.createElement('div');
        modal.id = 'deleteModal';
        modal.style.cssText = `
            position: fixed; inset: 0;
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        `;

        modal.innerHTML = `
            <div style="
                background: #fff;
                border-radius: 20px;
                width: 440px;
                max-width: 95vw;
                box-shadow: 0 20px 60px rgba(15,23,42,0.2);
                overflow: hidden;
                animation: modalIn 0.2s ease;
            ">
                <!-- Header -->
                <div style="
                    display: flex; justify-content: space-between;
                    align-items: center;
                    padding: 24px 28px 20px;
                    border-bottom: 1px solid #e2e8f0;
                ">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="
                            width:40px; height:40px;
                            background:#fef2f2;
                            border-radius:10px;
                            display:flex; align-items:center; justify-content:center;
                        ">
                            <span class="material-symbols-outlined" style="color:#ef4444; font-size:20px;">delete_forever</span>
                        </div>
                        <h2 style="
                            font-family:'Space Grotesk',sans-serif;
                            font-size:18px; font-weight:700;
                            color:#0f172a; margin:0;
                            letter-spacing:-0.03em;
                        ">Delete Device</h2>
                    </div>
                    <button onclick="closeDeleteModal()" style="
                        background:none; border:none;
                        font-size:22px; color:#94a3b8;
                        cursor:pointer; padding:4px 8px;
                        border-radius:6px; line-height:1;
                    ">&times;</button>
                </div>

                <!-- Body -->
                <div style="padding:24px 28px;">
                    <p style="
                        font-family:'Inter',sans-serif;
                        font-size:14px; color:#374151;
                        line-height:1.6; margin:0 0 16px;
                    ">
                        Kamu akan menghapus device
                        <strong style="color:#0f172a;">${DEVICE_NAME}</strong> secara permanen.
                    </p>
                    <div style="
                        background:#fef2f2;
                        border:1px solid #fecaca;
                        border-radius:10px;
                        padding:12px 16px;
                        font-family:'Inter',sans-serif;
                        font-size:13px; color:#991b1b;
                        line-height:1.5;
                    ">
                        <strong>Perhatian:</strong> Semua data monitoring device ini
                        (ping, SNMP, traffic) akan di-archive ke Supabase
                        lalu dihapus dari database lokal.
                        Tindakan ini tidak dapat dibatalkan.
                    </div>

                    <!-- Input konfirmasi -->
                    <div style="margin-top:20px;">
                        <label style="
                            display:block;
                            font-family:'Inter',sans-serif;
                            font-size:13px; font-weight:600;
                            color:#374151; margin-bottom:8px;
                        ">
                            Ketik <code style="
                                background:#f1f5f9;
                                padding:2px 6px;
                                border-radius:4px;
                                font-size:12px;
                                color:#ef4444;
                            ">${DEVICE_NAME}</code> untuk konfirmasi:
                        </label>
                        <input
                            type="text"
                            id="deleteConfirmInput"
                            placeholder="Ketik nama device..."
                            oninput="checkDeleteInput()"
                            style="
                                width:100%; border:1.5px solid #e2e8f0;
                                border-radius:10px; padding:10px 14px;
                                font-family:'Inter',sans-serif;
                                font-size:14px; color:#0f172a;
                                background:#f8fafc; outline:none;
                                box-sizing:border-box;
                                transition: border-color 0.15s, box-shadow 0.15s;
                            "
                        >
                    </div>

                    <div id="deleteAlert" style="display:none; margin-top:12px;
                        padding:10px 14px; border-radius:8px;
                        font-family:'Inter',sans-serif; font-size:13px;
                    "></div>
                </div>

                <!-- Footer -->
                <div style="
                    display:flex; justify-content:flex-end; gap:12px;
                    padding:20px 28px 24px;
                    border-top:1px solid #e2e8f0;
                ">
                    <button onclick="closeDeleteModal()" style="
                        background:#f1f5f9; color:#475569; border:none;
                        border-radius:10px; padding:10px 20px;
                        font-family:'Inter',sans-serif; font-size:14px;
                        font-weight:600; cursor:pointer;
                    ">Cancel</button>
                    <button id="btnConfirmDelete" onclick="submitDelete()" disabled style="
                        background:#94a3b8; color:#fff; border:none;
                        border-radius:10px; padding:10px 24px;
                        font-family:'Inter',sans-serif; font-size:14px;
                        font-weight:600; cursor:not-allowed;
                        transition: background 0.15s;
                    ">
                        <span id="deleteBtnText">Delete Device</span>
                        <span id="deleteBtnSpinner" style="display:none;">Deleting...</span>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Tutup saat klik overlay
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeDeleteModal();
        });

        // Focus input
        setTimeout(() => {
            const input = document.getElementById('deleteConfirmInput');
            if (input) input.focus();
        }, 100);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        if (modal) modal.style.display = 'none';
        const input = document.getElementById('deleteConfirmInput');
        if (input) input.value = '';
        const btn = document.getElementById('btnConfirmDelete');
        if (btn) {
            btn.disabled = true;
            btn.style.background = '#94a3b8';
            btn.style.cursor = 'not-allowed';
        }
        const alert = document.getElementById('deleteAlert');
        if (alert) alert.style.display = 'none';
        const spinner = document.getElementById('deleteBtnSpinner');
        const text    = document.getElementById('deleteBtnText');
        if (spinner) spinner.style.display = 'none';
        if (text)    text.style.display    = 'inline';
    }

    function checkDeleteInput() {
        const input = document.getElementById('deleteConfirmInput');
        const btn   = document.getElementById('btnConfirmDelete');
        if (!input || !btn) return;

        const match = input.value.trim() === DEVICE_NAME;
        btn.disabled        = !match;
        btn.style.background  = match ? '#ef4444' : '#94a3b8';
        btn.style.cursor      = match ? 'pointer'  : 'not-allowed';
    }

    async function submitDelete() {
        const btn     = document.getElementById('btnConfirmDelete');
        const text    = document.getElementById('deleteBtnText');
        const spinner = document.getElementById('deleteBtnSpinner');
        const alert   = document.getElementById('deleteAlert');

        btn.disabled         = true;
        text.style.display   = 'none';
        spinner.style.display = 'inline';

        try {
            const res = await fetch(`/device/${DEVICE_NAME}/delete`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
            });

            const data = await res.json();

            if (data.status === 'ok') {
                alert.style.cssText = `
                    display:block;
                    background:#ecfdf5; color:#065f46;
                    border:1px solid #a7f3d0;
                    padding:10px 14px; border-radius:8px;
                    font-family:'Inter',sans-serif; font-size:13px;
                `;
                alert.textContent = '✓ Device berhasil dihapus. Mengalihkan ke halaman devices...';

                setTimeout(() => {
                    window.location.href = '/device';
                }, 1500);
            } else {
                throw new Error(data.message || 'Gagal menghapus device');
            }

        } catch (err) {
            btn.disabled         = false;
            text.style.display   = 'inline';
            spinner.style.display = 'none';
            btn.style.background  = '#ef4444';
            btn.style.cursor      = 'pointer';

            alert.style.cssText = `
                display:block;
                background:#fef2f2; color:#991b1b;
                border:1px solid #fecaca;
                padding:10px 14px; border-radius:8px;
                font-family:'Inter',sans-serif; font-size:13px;
            `;
            alert.textContent = '✗ ' + (err.message || 'Gagal terhubung ke server');
        }
    }

    window.closeDeleteModal = closeDeleteModal;
    window.checkDeleteInput = checkDeleteInput;
    window.submitDelete = submitDelete;

    // Tutup modal dengan ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
});
