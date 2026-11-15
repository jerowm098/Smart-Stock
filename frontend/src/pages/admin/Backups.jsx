import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import Modal from '../../components/Modal.jsx';
import ConfirmDialog from '../../components/ConfirmDialog.jsx';
import Alert, { Toast } from '../../components/Feedback.jsx';
import SuccessDialog from '../../components/SuccessDialog.jsx';
import MessageDialog from '../../components/MessageDialog.jsx';
import { del, exportFile, get, post, validationMessages } from '../../lib/api.js';
import { formatDate } from '../../lib/format.js';

const PER_PAGE = 6;

// Recent Activity: action → colour tone + short verb, mirroring Products.
const ACTIVITY_TONE = {
    backup_created: 'ok',
    backup_downloaded: 'ok',
    backup_deleted: 'danger',
};

const ACTIVITY_VERB = {
    backup_created: 'Created backup',
    backup_downloaded: 'Downloaded backup',
    backup_deleted: 'Deleted backup',
};

const ACTIVITY_ICONS = {
    backup_created: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <ellipse cx="12" cy="5" rx="8" ry="3" />
            <path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5" />
            <path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3" />
        </svg>
    ),
    backup_downloaded: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <path d="M7 10l5 5 5-5" />
            <path d="M12 15V3" />
        </svg>
    ),
    backup_deleted: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="M3 6h18" />
            <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
            <path d="M10 11v6M14 11v6" />
        </svg>
    ),
    default: (
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8h.01M11 12h1v4h1" />
        </svg>
    ),
};

// One-line row summary, e.g. "Created backup smart-stock-backup-....json".
function activitySummary(item) {
    const verb = ACTIVITY_VERB[item.action];
    if (verb && item.subject_name) {
        return `${verb} ${item.subject_name}`;
    }
    return String(item.description ?? 'Backup activity');
}

// The "changes made" lines for the detail popup.
function activityChanges(item) {
    const explicit = item?.meta?.changes;
    if (Array.isArray(explicit) && explicit.length > 0) {
        return explicit.map((change) => change.text ?? String(change));
    }

    const meta = item?.meta ?? {};
    const lines = [];

    switch (item?.action) {
        case 'backup_created':
            if (meta.filename) lines.push(`File ${meta.filename}`);
            if (meta.keep != null) lines.push(`Keep the newest ${meta.keep} backups`);
            lines.push('Stored in the database and on the server');
            break;
        case 'backup_downloaded':
            if (meta.filename) lines.push(`File ${meta.filename}`);
            lines.push('JSON database dump');
            break;
        case 'backup_deleted':
            if (meta.filename) lines.push(`File ${meta.filename}`);
            lines.push('Removed from the database and server storage');
            break;
        default:
            break;
    }

    return lines;
}

// Label for the detail-popup change list — matches the action performed.
function activityChangesLabel(action) {
    if (action === 'backup_created') return 'Back up made';
    if (action === 'backup_downloaded') return 'Download made';
    return 'Changes made';
}

export default function Backups() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [running, setRunning] = useState(false);
    const [downloading, setDownloading] = useState(null);
    const [downloadConfirm, setDownloadConfirm] = useState(null);
    const [downloadError, setDownloadError] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [deleteConfirm, setDeleteConfirm] = useState(null);
    const [deleteError, setDeleteError] = useState(null);
    const [keep, setKeep] = useState(7);
    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);

    // Recent Activity List state.
    const [activity, setActivity] = useState([]);
    const [activityDetail, setActivityDetail] = useState(null);
    const [activityPage, setActivityPage] = useState(1);
    const [activityOptionsOpen, setActivityOptionsOpen] = useState(false);
    const activityOptionsRef = useRef(null);

    // Backup Files kebab (seed sample data / delete all) state.
    const [filesOptionsOpen, setFilesOptionsOpen] = useState(false);
    const filesOptionsRef = useRef(null);
    const [seeding, setSeeding] = useState(false);
    const [deleteAllConfirm, setDeleteAllConfirm] = useState(false);
    const [deletingAll, setDeletingAll] = useState(false);
    const [deleteAllError, setDeleteAllError] = useState(null);

    // Info popup when a clear/delete-all action fires on an already-empty list.
    const [emptyNotice, setEmptyNotice] = useState(null);
    const [clearConfirm, setClearConfirm] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [clearError, setClearError] = useState(null);
    const [toast, setToast] = useState(null);
    const ACTIVITY_PER_PAGE = 3;

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const [payload, feed] = await Promise.all([
                get('/api/backups'),
                // Backups page owns its slice of the audit feed.
                get('/api/activity/recent', {
                    limit: 12,
                    actions: 'backup_created,backup_deleted,backup_downloaded',
                }).catch(() => null),
            ]);

            setData(payload);
            setActivity(Array.isArray(feed) ? feed : []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    // Options dropdowns (kebabs) on the Backup Files and Recent Activity card
    // headers: close on outside click and Escape, same behaviour as the other
    // admin pages.
    useEffect(() => {
        if (!activityOptionsOpen && !filesOptionsOpen) {
            return undefined;
        }

        const onDown = (event) => {
            if (activityOptionsRef.current && !activityOptionsRef.current.contains(event.target)) {
                setActivityOptionsOpen(false);
            }
            if (filesOptionsRef.current && !filesOptionsRef.current.contains(event.target)) {
                setFilesOptionsOpen(false);
            }
        };

        const onKey = (event) => {
            if (event.key === 'Escape') {
                setActivityOptionsOpen(false);
                setFilesOptionsOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [activityOptionsOpen, filesOptionsOpen]);

    /** Column sort: 1st click asc → 2nd desc → 3rd back to the default order. */
    function toggleSort(field) {
        if (sortBy === field) {
            if (sortDir === 'asc') {
                setSortDir('desc');
            } else {
                setSortBy(null);
                setSortDir(null);
            }
        } else {
            setSortBy(field);
            setSortDir('asc');
        }
    }

    // Reset to the first page whenever the sort changes (mirrors Stock Inventory).
    useEffect(() => {
        setPage(1);
    }, [sortBy, sortDir]);

    const sortedBackups = useMemo(() => {
        const rows = data?.backups ?? [];
        if (!sortBy) {
            return rows;
        }

        const dir = sortDir === 'asc' ? 1 : -1;
        return [...rows].sort((a, b) => {
            // Numeric comparison for file size
            if (sortBy === 'size') {
                return (Number(a.size_bytes ?? 0) - Number(b.size_bytes ?? 0)) * dir;
            }

            // Date comparison for created date
            if (sortBy === 'created') {
                return (new Date(a.modified ?? 0).getTime() - new Date(b.modified ?? 0).getTime()) * dir;
            }

            // String comparison for file name and type
            const av = String(a[sortBy] ?? '').toLowerCase();
            const bv = String(b[sortBy] ?? '').toLowerCase();
            if (av < bv) return -1 * dir;
            if (av > bv) return 1 * dir;
            return 0;
        });
    }, [data, sortBy, sortDir]);

    const lastPage = Math.max(1, Math.ceil(sortedBackups.length / PER_PAGE));
    const safePage = Math.min(page, lastPage);
    const pagedBackups = useMemo(
        () => sortedBackups.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [sortedBackups, safePage]
    );

    // Recent Activity pagination (3 rows per page, matching Products).
    const activityLastPage = Math.max(1, Math.ceil(activity.length / ACTIVITY_PER_PAGE));
    const activitySafePage = Math.min(activityPage, activityLastPage);
    const pagedActivity = useMemo(
        () => activity.slice((activitySafePage - 1) * ACTIVITY_PER_PAGE, activitySafePage * ACTIVITY_PER_PAGE),
        [activity, activitySafePage, ACTIVITY_PER_PAGE]
    );

    // Keep the activity page in range when the feed shrinks.
    useEffect(() => {
        setActivityPage(1);
    }, [activity.length]);

    async function runBackup() {
        setRunning(true);
        setSuccess(null);
        setError(null);

        try {
            const response = await post('/api/backups/run', { keep });

            if (response.success) {
                setSuccess({
                    title: 'Backup created successfully',
                    description: 'A new database dump has been saved to the server.',
                });
            } else {
                setError(response.output || 'The backup did not complete.');
            }

            await load();
        } catch (err) {
            setError(err.payload?.output || err.message);
        } finally {
            setRunning(false);
        }
    }

    // Delete asks through the reusable ConfirmDialog (never the browser's
    // native confirm), mirroring the Clear-activity flow below.
    function openDeleteConfirm(file) {
        setSuccess(null);
        setDeleteError(null);
        setDeleteConfirm(file);
    }

    async function handleDeleteConfirm() {
        const file = deleteConfirm;
        setDeleting(file);
        setDeleteError(null);

        try {
            const response = await del(`/api/backups/${file}`);

            setDeleteConfirm(null);
            setToast({ tone: 'success', text: response?.message ?? 'Backup deleted successfully.' });
            await load();
        } catch (err) {
            setDeleteError(validationMessages(err)[0] ?? err.message ?? 'Could not delete the backup.');
        } finally {
            setDeleting(null);
        }
    }

    async function handleClearActivity() {
        setClearing(true);
        setClearError(null);

        try {
            const response = await del('/api/activity/recent?actions=backup_created,backup_deleted,backup_downloaded');

            setClearConfirm(false);
            setActivity([]);
            setActivityPage(1);
            setToast({ tone: 'success', text: response?.message ?? 'Activity list cleared.' });
        } catch (err) {
            setClearError(validationMessages(err)[0] ?? err.message ?? 'Could not clear the activity list.');
        } finally {
            setClearing(false);
        }
    }

    // Download asks through the ConfirmDialog first (same convention as the
    // Delete flow), then streams the file with a success toast + a silent
    // refresh of the Recent Activity feed so the download row appears
    // immediately — no page reload, no skeleton phase, no scroll jump.
    function openDownloadConfirm(file) {
        setDownloadError(null);
        setDownloadConfirm(file);
    }

    async function handleDownloadConfirm() {
        const file = downloadConfirm;
        setDownloading(file);
        setDownloadError(null);

        try {
            await exportFile(`/api/backups/download/${file}`);

            setDownloadConfirm(null);
            setToast({ tone: 'success', text: 'Backup downloaded successfully.' });

            const feed = await get('/api/activity/recent', {
                limit: 12,
                actions: 'backup_created,backup_deleted,backup_downloaded',
            }).catch(() => null);

            if (Array.isArray(feed)) {
                setActivity(feed);
                setActivityPage(1);
            }
        } catch (err) {
            setDownloadError(validationMessages(err)[0] ?? err.message ?? 'Could not download the backup.');
        } finally {
            setDownloading(null);
        }
    }

    // Backup Files kebab action 1: generate 7 sample backup rows (demo data
    // only, not real dumps).
    async function handleSeed() {
        if (seeding) return;
        setSeeding(true);

        try {
            const response = await post('/api/backups/seed');
            setToast({ tone: 'success', text: response?.message ?? '7 sample backups added.' });
            await load();
        } catch (err) {
            setToast({
                tone: 'error',
                text: validationMessages(err)[0] ?? err.message ?? 'Could not add the sample backups.',
            });
        } finally {
            setSeeding(false);
        }
    }

    // Backup Files kebab action 2: delete every backup in the list
    // (danger ConfirmDialog first).
    async function handleDeleteAllConfirm() {
        if (deletingAll) return;
        setDeletingAll(true);
        setDeleteAllError(null);

        try {
            const response = await del('/api/backups');

            setDeleteAllConfirm(false);
            setToast({ tone: 'success', text: response?.message ?? 'All backups deleted.' });
            await load();
        } catch (err) {
            setDeleteAllError(validationMessages(err)[0] ?? err.message ?? 'Could not delete the backups.');
        } finally {
            setDeletingAll(false);
        }
    }

    return (
        <div className="page">
            <section className={`page-hero${loading ? ' is-loading' : ''}`}>
                <div className="page-loadbar" aria-hidden="true" />
                <header className="page-head">
                    <div>
                        <h2>Backups</h2>
                        <p>Manage database backups stored on the server.</p>
                    </div>
                </header>
            </section>

            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}
            {toast && <Toast tone={toast.tone} onClose={() => setToast(null)}>{toast.text}</Toast>}

            {loading ? (
                /* Loading phase: the hero above stays put (its loadbar is the
                   progress UI) while these sections paint skeletons mirroring
                   the real page — no spinners anywhere. */
                <div className="page-loading-phase" aria-busy="true" aria-live="polite">
                    {/* Run Backup Now panel */}
                    <section className="card panel panel-add">
                        <div className="panel-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                            <span className="skeleton" style={{ width: 118, height: 38, borderRadius: 8 }} />
                        </div>
                    </section>

                    {/* Backup Files table + pagination */}
                    <section className="card padded">
                        <div className="filter-head">
                            <div>
                                <span className="skeleton skeleton-lg" style={{ marginBottom: 8 }} />
                                <span className="skeleton skeleton-line skeleton-sm" />
                            </div>
                        </div>
                        <div className="table-shell">
                            <table className="table" aria-hidden="true">
                                <thead>
                                    <tr>
                                        {[0, 1, 2, 3, 4].map((i) => (
                                            <th key={i} className={i >= 2 ? 'text-right' : undefined}>
                                                <span className="skeleton skeleton-line skeleton-sm" style={{ width: '70%' }} />
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {[0, 1, 2, 3, 4, 5].map((r) => (
                                        <tr key={r}>
                                            {[0, 1, 2, 3, 4].map((c) => (
                                                <td key={c} className={c >= 2 ? 'text-right' : undefined}>
                                                    <span className="skeleton skeleton-line" style={{ width: c === 4 ? '62%' : '85%' }} />
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="pagination" aria-hidden="true">
                            <span className="skeleton" style={{ width: 34, height: 34 }} />
                            <span className="skeleton" style={{ width: 20, height: 12 }} />
                            <span className="skeleton" style={{ width: 34, height: 34 }} />
                        </div>
                    </section>
                </div>
            ) : (
            <>
            <section className="card panel panel-add">
                <header className="panel-head">
                    <div>
                        <h3>Run Backup Now</h3>
                        <p className="muted">Create an immediate on-demand backup of the entire database.</p>
                    </div>
                    <button type="button" className="btn btn-primary" onClick={runBackup} disabled={running}>
                        {running ? 'Running…' : 'Run backup now'}
                    </button>
                </header>
            </section>

            <section className="card padded">
                <div className="filter-head">
                    <div>
                        <h3>Backup Files</h3>
                        <p className="muted">Every database dump stored on the server, ordered by creation date.</p>
                    </div>
                    <div className="activity-options" ref={filesOptionsRef}>
                        <button
                            type="button"
                            className="icon-btn is-outlined"
                            onClick={() => setFilesOptionsOpen((open) => !open)}
                            aria-haspopup="menu"
                            aria-expanded={filesOptionsOpen}
                            aria-label="Backup options"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                <circle cx="12" cy="5" r="1.7" />
                                <circle cx="12" cy="12" r="1.7" />
                                <circle cx="12" cy="19" r="1.7" />
                            </svg>
                        </button>
                        {filesOptionsOpen && (
                            <div className="activity-options-panel" role="menu" aria-label="Backup options">
                                <button
                                    type="button"
                                    className="activity-options-item"
                                    role="menuitem"
                                    disabled={seeding || deletingAll}
                                    onClick={() => {
                                        setFilesOptionsOpen(false);
                                        handleSeed();
                                    }}
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <ellipse cx="12" cy="5" rx="8" ry="3" />
                                        <path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5" />
                                        <path d="M12 12v7" />
                                        <path d="M9 16l3 3 3-3" />
                                    </svg>
                                    <span>Add 7 sample backups</span>
                                </button>
                                <button
                                    type="button"
                                    className="activity-options-item activity-options-danger"
                                    role="menuitem"
                                    disabled={seeding || deletingAll}
                                    onClick={() => {
                                        setFilesOptionsOpen(false);

                                        if (sortedBackups.length === 0) {
                                            setEmptyNotice({
                                                title: 'No backups to delete',
                                                description: 'The backup list is already empty. Run a backup or add the 7 sample rows to populate it first.',
                                            });
                                            return;
                                        }

                                        setDeleteAllError(null);
                                        setDeleteAllConfirm(true);
                                    }}
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="M3 6h18" />
                                        <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                    </svg>
                                    <span>Delete all backups</span>
                                </button>
                            </div>
                        )}
                    </div>
                </div>
                {!loading && !error && (
                    <div className="table-shell">
                        <div className="table-wrap">
                            <table className="table">
                                <thead>
                                    <tr>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('name')}
                                        >
                                            File{sortBy === 'name' && sortDir === 'asc' ? ' ▲' : sortBy === 'name' ? ' ▼' : ''}
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('type')}
                                        >
                                            Type{sortBy === 'type' && sortDir === 'asc' ? ' ▲' : sortBy === 'type' ? ' ▼' : ''}
                                        </th>
                                        <th
                                            className="sort-header text-right"
                                            onClick={() => toggleSort('size')}
                                        >
                                            Size{sortBy === 'size' && sortDir === 'asc' ? ' ▲' : sortBy === 'size' ? ' ▼' : ''}
                                        </th>
                                        <th
                                            className="sort-header"
                                            onClick={() => toggleSort('created')}
                                        >
                                            Created{sortBy === 'created' && sortDir === 'asc' ? ' ▲' : sortBy === 'created' ? ' ▼' : ''}
                                        </th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pagedBackups.map((backup) => (
                                        <tr key={backup.name}>
                                            <td className="mono">{backup.name}</td>
                                            <td>{backup.type}</td>
                                            <td className="text-right">{backup.size_human}</td>
                                            <td className="muted">{formatDate(backup.modified)}</td>
                                            <td className="text-right actions-cell">
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-sm"
                                                    onClick={() => openDownloadConfirm(backup.name)}
                                                >
                                                    Download
                                                </button>
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-sm btn-danger-text"
                                                    disabled={deleting === backup.name}
                                                    onClick={() => openDeleteConfirm(backup.name)}
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {sortedBackups.length === 0 && (
                                <div className="table-empty table-empty-solid">
                                    <span className="table-empty-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                            <ellipse cx="12" cy="5" rx="8" ry="3" />
                                            <path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5" />
                                            <path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3" />
                                        </svg>
                                    </span>
                                    <p className="table-empty-title">No backups yet</p>
                                    <p className="table-empty-sub">Run a backup or add the 7 sample rows to populate this list.</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {!loading && !error && sortedBackups.length > 0 && (
                    <div className="pagination">
                        <button
                            type="button"
                            className="btn btn-ghost btn-sm page-btn"
                            disabled={safePage <= 1}
                            onClick={() => setPage((p) => Math.max(1, p - 1))}
                            aria-label="Previous page"
                        >
                            <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </button>
                        <span className="page-count">{safePage}</span>
                        <button
                            type="button"
                            className="btn btn-ghost btn-sm page-btn"
                            disabled={safePage >= lastPage}
                            onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                            aria-label="Next page"
                        >
                            <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                <path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </button>
                    </div>
                )}
            </section>

            <section className="card padded recent-activity">
                <div className="filter-head">
                    <div>
                        <h3>Recent Activity List</h3>
                        <p className="muted">See the backup changes you made recently.</p>
                    </div>
                    <div className="activity-options" ref={activityOptionsRef}>
                        <button
                            type="button"
                            className="icon-btn is-outlined"
                            onClick={() => setActivityOptionsOpen((open) => !open)}
                            aria-haspopup="menu"
                            aria-expanded={activityOptionsOpen}
                            aria-label="Activity options"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                                <circle cx="12" cy="5" r="1.7" />
                                <circle cx="12" cy="12" r="1.7" />
                                <circle cx="12" cy="19" r="1.7" />
                            </svg>
                        </button>
                        {activityOptionsOpen && (
                            <div className="activity-options-panel" role="menu" aria-label="Activity options">
                                <button
                                    type="button"
                                    className="activity-options-item activity-options-danger"
                                    role="menuitem"
                                    onClick={() => {
                                        setActivityOptionsOpen(false);

                                        if (activity.length === 0) {
                                            setEmptyNotice({
                                                title: 'Nothing to clear',
                                                description: 'The activity list is already empty. Run, download or delete a backup and its activity will show up here.',
                                            });
                                            return;
                                        }

                                        setClearError(null);
                                        setClearConfirm(true);
                                    }}
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                                        <path d="M3 6h18" />
                                        <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                    </svg>
                                    <span>Clear activity</span>
                                </button>
                            </div>
                        )}
                    </div>
                </div>

                {activity.length === 0 ? (
                    <div className="table-empty">
                        <span className="table-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
                                <ellipse cx="12" cy="5" rx="8" ry="3" />
                                <path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5" />
                                <path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3" />
                            </svg>
                        </span>
                        <p className="table-empty-title">No backup activity yet</p>
                        <p className="table-empty-sub">Run, download or delete a backup and it will show up here.</p>
                    </div>
                ) : (
                    <>
                        <div className="activity-shell">
                            <ul className="activity-list">
                                {pagedActivity.map((item) => {
                                    const tone = ACTIVITY_TONE[item.action] ?? 'neutral';
                                    return (
                                        <li key={item.id}>
                                            <button
                                                type="button"
                                                className="activity-row"
                                                onClick={() => setActivityDetail(item)}
                                                aria-label={`View details: ${activitySummary(item)}`}
                                            >
                                                <span className={`activity-icon activity-icon-${tone}`} aria-hidden="true">
                                                    {ACTIVITY_ICONS[item.action] ?? ACTIVITY_ICONS.default}
                                                </span>
                                                <div className="activity-body">
                                                    <p className="activity-desc">{activitySummary(item)}</p>
                                                    <p className="activity-meta muted">
                                                        <span className="activity-user">{item.user_name}</span>
                                                        {item.user_role && <span className="activity-role">{item.user_role}</span>}
                                                    </p>
                                                </div>
                                                <time
                                                    className="activity-time muted"
                                                    dateTime={item.created_at ?? undefined}
                                                    title={item.created_at ? new Date(item.created_at).toLocaleString() : undefined}
                                                >
                                                    {item.created_at_human ?? ''}
                                                </time>
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>

                        {activity.length > ACTIVITY_PER_PAGE && (
                            <div className="pagination">
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm page-btn"
                                    disabled={activitySafePage <= 1}
                                    onClick={() => setActivityPage((p) => Math.max(1, p - 1))}
                                    aria-label="Previous page"
                                >
                                    <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                        <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </button>
                                <span className="page-count">{activitySafePage}</span>
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm page-btn"
                                    disabled={activitySafePage >= activityLastPage}
                                    onClick={() => setActivityPage((p) => Math.min(activityLastPage, p + 1))}
                                    aria-label="Next page"
                                >
                                    <svg className="page-chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
                                        <path d="M9 18l6-6-6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </button>
                                <span className="page-detail">{Math.min(activitySafePage * ACTIVITY_PER_PAGE, activity.length)} of {activity.length}</span>
                            </div>
                        )}
                    </>
                )}
            </section>
            </>
            )}

            {success && (
                <SuccessDialog
                    title={success.title}
                    description={success.description}
                    onClose={() => setSuccess(null)}
                />
            )}

            {activityDetail && (
                <Modal
                    title={(
                        <span className="activity-detail-title">
                            <span className="activity-detail-infoicon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 11v5M12 8h.01" />
                                </svg>
                            </span>
                            Activity details
                        </span>
                    )}
                    className="modal-activity"
                    noClose
                    onClose={() => setActivityDetail(null)}
                >
                    <dl className="activity-detail-list">
                        <div className="activity-detail-item">
                            <dt>Action</dt>
                            <dd>{ACTIVITY_VERB[activityDetail.action] ?? activityDetail.action}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>By</dt>
                            <dd>
                                {activityDetail.user_name}
                                {activityDetail.user_role && <span className="activity-role">{activityDetail.user_role}</span>}
                            </dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>When</dt>
                            <dd>
                                {activityDetail.created_at
                                    ? new Date(activityDetail.created_at).toLocaleString()
                                    : activityDetail.created_at_human}
                            </dd>
                        </div>
                        {activityDetail.subject_name && (
                            <div className="activity-detail-item">
                                <dt>Backup</dt>
                                <dd className="mono">{activityDetail.subject_name}</dd>
                            </div>
                        )}
                        {activityChanges(activityDetail).length > 0 && (
                            <div className="activity-detail-item">
                                <dt>{activityChangesLabel(activityDetail.action)}</dt>
                                <dd>
                                    <ul className="activity-detail-changes">
                                        {activityChanges(activityDetail).map((text, index) => (
                                            <li key={`${text}-${index}`}>{text}</li>
                                        ))}
                                    </ul>
                                </dd>
                            </div>
                        )}
                    </dl>

                    <div className="modal-actions modal-actions-center">
                        <button type="button" className="btn btn-ghost" onClick={() => setActivityDetail(null)}>Close</button>
                    </div>
                </Modal>
            )}

            {emptyNotice && (
                <MessageDialog
                    tone="info"
                    title={emptyNotice.title}
                    description={emptyNotice.description}
                    onClose={() => setEmptyNotice(null)}
                />
            )}

            {deleteAllConfirm && (
                <ConfirmDialog
                    tone="danger"
                    title="Delete all backups?"
                    description={`This permanently deletes all ${sortedBackups.length} backups from the database and server storage. This action cannot be undone.`}
                    confirmLabel="Delete all"
                    loadingLabel="Deleting…"
                    loading={deletingAll}
                    error={deleteAllError}
                    onConfirm={handleDeleteAllConfirm}
                    onCancel={() => {
                        if (!deletingAll) {
                            setDeleteAllConfirm(false);
                            setDeleteAllError(null);
                        }
                    }}
                />
            )}

            {downloadConfirm && (
                <ConfirmDialog
                    tone="info"
                    title="Download this backup?"
                    description={`This saves "${downloadConfirm}" to your device. The backup stays on the server.`}
                    confirmLabel="Download"
                    loadingLabel="Downloading…"
                    loading={downloading != null}
                    error={downloadError}
                    onConfirm={handleDownloadConfirm}
                    onCancel={() => {
                        if (downloading == null) {
                            setDownloadConfirm(null);
                            setDownloadError(null);
                        }
                    }}
                />
            )}

            {deleteConfirm && (
                <ConfirmDialog
                    tone="danger"
                    title="Delete this backup?"
                    description={`This permanently deletes "${deleteConfirm}" from the database and server storage. This action cannot be undone.`}
                    confirmLabel="Delete"
                    loadingLabel="Deleting…"
                    loading={deleting != null}
                    error={deleteError}
                    onConfirm={handleDeleteConfirm}
                    onCancel={() => {
                        if (deleting == null) {
                            setDeleteConfirm(null);
                            setDeleteError(null);
                        }
                    }}
                />
            )}

            {clearConfirm && (
                <ConfirmDialog
                    tone="danger"
                    title="Clear activity list?"
                    description="This permanently deletes the backup activity history from the activity log. The backup files themselves are not affected. This action cannot be undone."
                    confirmLabel="Clear"
                    loadingLabel="Clearing…"
                    loading={clearing}
                    error={clearError}
                    onConfirm={handleClearActivity}
                    onCancel={() => {
                        if (!clearing) {
                            setClearConfirm(false);
                            setClearError(null);
                        }
                    }}
                />
            )}
        </div>
    );
}
