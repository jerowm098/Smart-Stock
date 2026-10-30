import { useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import Alert from '../../components/Feedback.jsx';
import SuccessDialog from '../../components/SuccessDialog.jsx';
import { del, get, post } from '../../lib/api.js';
import { formatDate } from '../../lib/format.js';

const PER_PAGE = 6;

export default function Backups() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [running, setRunning] = useState(false);
    const [deleting, setDeleting] = useState(null);
    const [keep, setKeep] = useState(7);
    const [sortBy, setSortBy] = useState(null);
    const [sortDir, setSortDir] = useState(null);
    const [page, setPage] = useState(1);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            setData(await get('/api/backups'));
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

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

    // Reserve exactly one full page (6 rows) of height, like Stock Inventory,
    // so the pager sits at a fixed spot even when a page holds fewer rows.
    // Measured from the rendered table instead of hard-coded pixels so it
    // stays correct across fonts, zoom levels and theme changes.
    const tableRef = useRef(null);
    const wrapRef = useRef(null);

    useLayoutEffect(() => {
        const table = tableRef.current;
        const wrap = wrapRef.current;
        if (!table || !wrap) {
            return;
        }

        const head = table.querySelector('thead');
        const row = table.querySelector('tbody tr');

        // No rows to measure (empty list / loading): let the wrap size itself.
        if (!head || !row) {
            wrap.style.minHeight = '';
            return;
        }

        const headerHeight = head.getBoundingClientRect().height;
        const rowHeight = row.getBoundingClientRect().height;

        wrap.style.minHeight = `${headerHeight + rowHeight * PER_PAGE}px`;
    }, [pagedBackups.length, loading]);

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

    async function remove(file) {
        if (!window.confirm(`Delete backup "${file}"? This cannot be undone.`)) {
            return;
        }

        setDeleting(file);
        setSuccess(null);
        setError(null);

        try {
            const response = await del(`/api/backups/${file}`);
            setSuccess({
                title: 'Backup deleted',
                description: response.message || 'The backup file has been removed from the server.',
            });
            await load();
        } catch (err) {
            setError(err.message);
        } finally {
            setDeleting(null);
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
                </div>
                {!loading && !error && (
                    <div className="table-shell">
                        <div className="table-wrap data-rows-6" ref={wrapRef}>
                            <table className="table" ref={tableRef}>
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
                                                <a className="btn btn-ghost btn-sm" href={`/api/backups/download/${backup.name}`}>
                                                    Download
                                                </a>
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-sm btn-danger-text"
                                                    disabled={deleting === backup.name}
                                                    onClick={() => remove(backup.name)}
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {sortedBackups.length === 0 && (
                                <p className="muted padded">No backups yet — run one now to create the first dump.</p>
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
            </>
            )}

            {success && (
                <SuccessDialog
                    title={success.title}
                    description={success.description}
                    onClose={() => setSuccess(null)}
                />
            )}
        </div>
    );
}
