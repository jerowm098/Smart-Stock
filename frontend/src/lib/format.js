const peso = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
});

export function formatPeso(value) {
    return peso.format(Number(value) || 0);
}

/**
 * Title-case a person's name: "jerome mallari" -> "Jerome Mallari".
 * Capitalizes the first letter of each whitespace/hyphen separated word
 * without lowercasing the rest of the letters (so "McDonald" survives).
 */
export function titleCaseName(value) {
    if (!value) {
        return '';
    }

    return String(value)
        .trim()
        .split(/(\s+|-|')/)
        .map((part) => (/^[a-z]/.test(part) ? part.charAt(0).toUpperCase() + part.slice(1) : part))
        .join('');
}

export function formatDate(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

/**
 * Relative time for "Latest received" style columns: "just now",
 * "3 days ago", "1 month ago". Accepts ISO strings from the API.
 */
export function timeAgo(value) {
    if (!value) {
        return '—';
    }

    const then = new Date(value).getTime();
    if (!Number.isFinite(then)) {
        return '—';
    }

    const seconds = Math.max(0, Math.floor((Date.now() - then) / 1000));
    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours} hour${hours === 1 ? '' : 's'} ago`;
    }

    const days = Math.floor(hours / 24);
    if (days < 7) {
        return `${days} day${days === 1 ? '' : 's'} ago`;
    }

    if (days < 31) {
        const weeks = Math.floor(days / 7);
        return `${weeks} week${weeks === 1 ? '' : 's'} ago`;
    }

    if (days < 365) {
        const months = Math.floor(days / 30.44);
        return `${months} month${months === 1 ? '' : 's'} ago`;
    }

    const years = Math.floor(days / 365.25);
    return `${years} year${years === 1 ? '' : 's'} ago`;
}
