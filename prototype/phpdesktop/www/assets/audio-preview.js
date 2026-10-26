(() => {
    'use strict';

    let activeAudio = null;
    let activeButton = null;
    let activeStatus = null;

    function resetActivePreview(message) {
        if (activeAudio) {
            activeAudio.pause();
            activeAudio.removeAttribute('src');
            activeAudio.load();
        }
        if (activeButton) {
            activeButton.textContent = '▶ Dengarkan';
            activeButton.setAttribute('aria-pressed', 'false');
        }
        if (activeStatus && message) {
            activeStatus.textContent = message;
            activeStatus.dataset.previewState = 'idle';
        }
        activeAudio = null;
        activeButton = null;
        activeStatus = null;
    }

    function setPreviewStatus(status, message) {
        if (!activeStatus) return;
        activeStatus.dataset.previewState = status;
        activeStatus.textContent = message;
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-audio-preview]');
        if (!button) return;

        const selectId = button.getAttribute('data-audio-select') || '';
        const select = selectId ? document.getElementById(selectId) : null;
        const statusId = button.getAttribute('data-audio-preview-status') || '';
        const status = statusId ? document.getElementById(statusId) : null;
        if (!status) return;

        if (activeButton === button && activeAudio && !activeAudio.paused) {
            resetActivePreview('Pratinjau dihentikan.');
            return;
        }

        if (activeButton && activeButton !== button) {
            resetActivePreview('Pratinjau sebelumnya dihentikan.');
        }

        let source = '';
        const selectedAsset = select ? String(select.value || '').trim() : '';
        const eventId = Number.parseInt(button.getAttribute('data-audio-event-id') || '0', 10);

        if (selectedAsset === '__clear__') {
            status.dataset.previewState = 'error';
            status.textContent = 'Referensi audio akan dihapus. Pilih file audio dahulu untuk pratinjau.';
            return;
        }
        if (selectedAsset !== '') {
            source = 'audio-preview.php?asset=' + encodeURIComponent(selectedAsset);
        } else if (eventId > 0) {
            source = 'audio-preview.php?event_id=' + encodeURIComponent(String(eventId));
        } else {
            status.dataset.previewState = 'error';
            status.textContent = 'Pilih file audio dahulu untuk mendengarkan pratinjau.';
            return;
        }

        const audio = new Audio();
        audio.preload = 'none';
        audio.src = source;
        activeAudio = audio;
        activeButton = button;
        activeStatus = status;

        button.textContent = 'Ⅱ Hentikan';
        button.setAttribute('aria-pressed', 'true');
        setPreviewStatus('loading', 'Memuat pratinjau audio…');

        audio.addEventListener('playing', () => {
            setPreviewStatus('playing', 'Sedang memutar pratinjau audio.');
        });
        audio.addEventListener('ended', () => {
            resetActivePreview('Pratinjau selesai.');
        });
        audio.addEventListener('error', () => {
            resetActivePreview('Pratinjau gagal diputar. Periksa file audio dan formatnya.');
            if (status) status.dataset.previewState = 'error';
        });

        const playResult = audio.play();
        if (playResult && typeof playResult.catch === 'function') {
            playResult.catch(() => {
                if (activeAudio === audio) {
                    resetActivePreview('Pratinjau gagal dimulai. Periksa file audio dan izin pemutaran.');
                    if (status) status.dataset.previewState = 'error';
                }
            });
        }
    });

    document.addEventListener('change', (event) => {
        const select = event.target;
        if (!(select instanceof HTMLSelectElement) || !select.matches('[data-audio-preview-select]')) return;
        if (activeButton && activeButton.getAttribute('data-audio-select') === select.id) {
            resetActivePreview('Pilihan audio berubah. Tekan Dengarkan untuk memeriksa file yang dipilih.');
        }
    });

    window.addEventListener('pagehide', () => {
        if (activeAudio) activeAudio.pause();
    });
})();
