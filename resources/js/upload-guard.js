/**
 * Client-side upload size guard.
 *
 * Any <input type="file" data-max-bytes="N"> gets checked the moment files
 * are picked: files larger than N trigger an inline warning under the input
 * AND a custom validity message, so the form refuses to submit until the
 * selection is fixed — no doomed multi-MB upload, no opaque server 413.
 *
 * Multi-file inputs can additionally carry data-max-total-bytes="N" (the
 * whole-request cap, post_max_size) — useful when each file individually
 * fits but the batch would blow the request limit.
 *
 * Server-side validation still runs regardless; this is UX, not security.
 */

function formatBytes(bytes) {
    if (bytes >= 1024 * 1024) {
        const mb = bytes / (1024 * 1024);
        return (Number.isInteger(mb) ? mb : mb.toFixed(1)) + ' MB';
    }
    return Math.round(bytes / 1024) + ' KB';
}

function clearWarning(input) {
    input.setCustomValidity('');
    const existing = input.parentElement.querySelector('[data-upload-guard-warning]');
    if (existing) existing.remove();
}

function showWarning(input, message) {
    input.setCustomValidity(message);

    let el = input.parentElement.querySelector('[data-upload-guard-warning]');
    if (!el) {
        el = document.createElement('p');
        el.setAttribute('data-upload-guard-warning', '');
        el.className = 'mt-1 text-xs font-medium text-rose-700 dark:text-rose-400';
        el.setAttribute('role', 'alert');
        input.insertAdjacentElement('afterend', el);
    }
    el.textContent = message;

    // Surface the browser's native validity bubble immediately so the
    // user sees feedback even without trying to submit.
    input.reportValidity();
}

document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.type !== 'file' || !input.dataset.maxBytes) {
        return;
    }

    clearWarning(input);

    const maxBytes = parseInt(input.dataset.maxBytes, 10);
    const files = Array.from(input.files ?? []);
    if (!files.length || !maxBytes) return;

    const oversize = files.filter((f) => f.size > maxBytes);
    if (oversize.length) {
        const names = oversize
            .slice(0, 3)
            .map((f) => `${f.name} (${formatBytes(f.size)})`)
            .join(', ');
        const more = oversize.length > 3 ? ` and ${oversize.length - 3} more` : '';
        showWarning(
            input,
            `Too large — the limit is ${formatBytes(maxBytes)} per file: ${names}${more}. ` +
            'Please resize or pick smaller files.',
        );
        return;
    }

    const maxTotal = parseInt(input.dataset.maxTotalBytes ?? '0', 10);
    if (maxTotal > 0) {
        const total = files.reduce((sum, f) => sum + f.size, 0);
        if (total > maxTotal) {
            showWarning(
                input,
                `The selected files add up to ${formatBytes(total)}, but a single upload can be at most ` +
                `${formatBytes(maxTotal)}. Please upload in smaller batches.`,
            );
        }
    }
});
