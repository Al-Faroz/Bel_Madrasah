(function () {
    'use strict';

    if (window.__belMadrasahSchedulerStarted) return;
    window.__belMadrasahSchedulerStarted = true;

    const endpoint = 'scheduler.php';
    const player = document.createElement('audio');
    player.preload = 'none';
    player.setAttribute('aria-hidden', 'true');
    player.style.display = 'none';
    document.body.appendChild(player);

    let pollTimer = null;
    let polling = false;
    let isPlaying = false;
    let activeRunId = 0;

    const statusLabels = {
        completed: 'selesai',
        failed: 'gagal',
        missed: 'terlewat',
        skipped: 'dilewati',
        started: 'sedang diproses'
    };

    function setStatus(message, state) {
        document.querySelectorAll('[data-scheduler-status]').forEach(function (node) {
            const text = node.querySelector('[data-scheduler-status-text]');
            if (text) {
                text.textContent = message;
            } else {
                node.textContent = message;
            }
            node.dataset.schedulerState = state || 'waiting';
        });
    }

    async function postForm(values, keepalive) {
        const body = new URLSearchParams(values);
        const response = await fetch(endpoint, {
            method: 'POST',
            body: body,
            cache: 'no-store',
            keepalive: Boolean(keepalive),
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) {
            throw new Error(payload.message || 'Permintaan scheduler gagal.');
        }
        return payload;
    }

    async function finishPlayback(status, message) {
        if (!activeRunId) return;
        const runId = activeRunId;
        activeRunId = 0;
        isPlaying = false;
        player.pause();

        try {
            await postForm({
                action: 'finish',
                run_id: String(runId),
                status: status,
                message: message || (status === 'completed' ? 'Audio selesai diputar.' : 'Audio gagal diputar.')
            });
        } catch (error) {
            setStatus('Audio ' + statusLabels[status] + ', tetapi status log belum dapat diperbarui: ' + error.message, 'failed');
        }

        if (status === 'completed') {
            setStatus('Audio selesai diputar. Memeriksa jadwal berikutnya…', 'completed');
        } else {
            setStatus('Pemutaran audio gagal: ' + (message || 'Periksa file audio dan kebijakan autoplay.'), 'failed');
        }
        poll();
    }

    async function startPlayback(data) {
        if (!data || !data.run_id || !data.audio_url || isPlaying) return;
        activeRunId = Number(data.run_id);
        isPlaying = true;
        player.volume = Math.max(0, Math.min(100, Number(data.volume ?? 80))) / 100;
        player.src = data.audio_url;
        player.load();
        setStatus('Memutar ' + data.event_name + ' (' + data.scheduled_for.slice(11, 16) + ')…', 'playing');

        try {
            const playPromise = player.play();
            if (playPromise && typeof playPromise.then === 'function') {
                await playPromise;
            }
        } catch (error) {
            const reason = error && error.name === 'NotAllowedError'
                ? 'Browser menolak autoplay. Periksa kebijakan autoplay PHP Desktop.'
                : (error && error.message ? error.message : 'Audio tidak dapat dimulai.');
            await finishPlayback('failed', reason);
        }
    }

    player.addEventListener('ended', function () {
        finishPlayback('completed', 'Audio selesai diputar.');
    });

    player.addEventListener('error', function () {
        if (!activeRunId) return;
        const mediaError = player.error;
        const reason = mediaError
            ? 'Elemen audio gagal membaca file (kode media ' + mediaError.code + ').'
            : 'Elemen audio gagal membaca file.';
        finishPlayback('failed', reason);
    });

    async function poll() {
        if (polling || isPlaying || document.visibilityState === 'prerender') return;
        polling = true;
        try {
            const response = await fetch(endpoint + '?action=tick&_=' + Date.now(), { cache: 'no-store' });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Scheduler tidak dapat membaca jadwal.');
            }

            if (data.state === 'playing') {
                await startPlayback(data);
            } else if (data.state === 'skipped' || data.state === 'failed') {
                setStatus(data.message || 'Kegiatan tidak dapat diputar.', data.state);
            } else if (data.state === 'conflict') {
                setStatus(data.message || 'Konflik aturan jadwal; pola Normal digunakan.', 'conflict');
            } else if (data.state === 'idle') {
                setStatus(data.message || 'Belum ada paket jadwal aktif.', 'idle');
            } else if (data.last_log && ['failed', 'missed', 'skipped'].includes(data.last_log.status)) {
                const statusText = statusLabels[data.last_log.status] || data.last_log.status;
                setStatus(
                    data.last_log.event_name + ' ' + statusText + ': ' + data.last_log.message,
                    data.last_log.status
                );
            } else {
                setStatus(data.message || 'Menunggu jadwal berikutnya.', 'waiting');
            }
        } catch (error) {
            setStatus('Scheduler belum dapat memeriksa jadwal: ' + error.message, 'error');
        } finally {
            polling = false;
        }
    }

    window.addEventListener('pagehide', function () {
        if (!activeRunId) return;
        const payload = new URLSearchParams({
            action: 'finish',
            run_id: String(activeRunId),
            status: 'failed',
            message: 'Pemutaran terhenti karena halaman ditutup atau berpindah.'
        });
        if (navigator.sendBeacon) {
            navigator.sendBeacon(endpoint, payload);
        } else {
            fetch(endpoint, {
                method: 'POST',
                body: payload,
                keepalive: true,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }
            }).catch(function () {});
        }
        player.pause();
        player.removeAttribute('src');
        activeRunId = 0;
        isPlaying = false;
    });

    async function initialize() {
        try {
            await fetch(endpoint + '?action=page_start&_=' + Date.now(), { cache: 'no-store' });
        } catch (error) {
            setStatus('Scheduler belum siap: ' + error.message, 'error');
        }
        poll();
        pollTimer = window.setInterval(poll, 1000);
    }

    initialize();

})();
