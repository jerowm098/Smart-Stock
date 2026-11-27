import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import Modal from './Modal.jsx';
import ConfirmDialog from './ConfirmDialog.jsx';
import MessageDialog from './MessageDialog.jsx';
import { Toast } from './Feedback.jsx';
import { del, get, validationMessages } from '../lib/api.js';
import { formatPeso } from '../lib/format.js';
import { clearConnectedNav } from '../lib/navBadges.js';

/**
 * Recent Activity List for the Admin POS Checkout page.
 *
 * Same section/pattern as the Recent Activity blocks on Stock Inventory,
 * Products and Order Suggestions: paginated row list, detail popup, and a
 * kebab options menu that can clear this page's slice of the feed.
 *
 * Feed scope: `sale_completed` only (written by PosCheckoutController). The
 * Cashier POS does not render this component, and the read/clear endpoints
 * are admin-guarded anyway.
 */

const PER_PAGE = 3;

const SALE_ICON = (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M6 3h12a1 1 0 0 1 1 1v17l-3-2-2 2-2-2-2 2-2-2-3 2V4a1 1 0 0 1 1-1z" />
        <path d="M9 8h6" />
        <path d="M9 12h6" />
    </svg>
);

function summary(item) {
    return item?.subject_name ? `Completed sale #${item.subject_name}` : item?.description ?? 'Completed sale';
}

function itemLines(item) {
    const items = item?.meta?.items;
    return Array.isArray(items) ? items : [];
}

function channelLabel(item) {
    return item?.meta?.channel === 'admin' ? 'Admin POS' : 'Cashier POS';
}

export default function PosRecentActivity({ refreshKey = 0 }) {
    const [activity, setActivity] = useState([]);
    const [detail, setDetail] = useState(null);
    const [page, setPage] = useState(1);
    const [toast, setToast] = useState(null);

    const [optionsOpen, setOptionsOpen] = useState(false);
    const optionsRef = useRef(null);

    const [clearConfirm, setClearConfirm] = useState(false);
    const [clearing, setClearing] = useState(false);
    const [clearError, setClearError] = useState(null);
    const [emptyNotice, setEmptyNotice] = useState(null);

    const load = useCallback(async () => {
        try {
            const feed = await get('/api/activity/recent', { limit: 12, actions: 'sale_completed' });
            setActivity(Array.isArray(feed) ? feed : []);
        } catch {
            // Silent: the terminal already surfaces checkout errors; a failed
            // activity fetch must never block selling.
        }
    }, []);

    useEffect(() => {
        load();
    }, [load, refreshKey]);

    // Options dropdown closes on outside click and Escape (topbar-menu behaviour).
    useEffect(() => {
        if (!optionsOpen) {
            return;
        }

        const onDown = (event) => {
            if (optionsRef.current && !optionsRef.current.contains(event.target)) {
                setOptionsOpen(false);
            }
        };

        const onKey = (event) => {
            if (event.key === 'Escape') {
                setOptionsOpen(false);
            }
        };

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [optionsOpen]);

    const lastPage = Math.max(1, Math.ceil(activity.length / PER_PAGE));
    const safePage = Math.min(page, lastPage);

    const pagedActivity = useMemo(
        () => activity.slice((safePage - 1) * PER_PAGE, safePage * PER_PAGE),
        [activity, safePage],
    );

    useEffect(() => {
        setPage(1);
    }, [activity.length]);

    async function handleClear() {
        setClearing(true);
        setClearError(null);

        try {
            const response = await del('/api/activity/recent?actions=sale_completed');

            setClearConfirm(false);
            setActivity([]);
            clearConnectedNav('saleCompleted');
            setPage(1);
            setToast(response?.message ?? 'Activity list cleared.');
            await load();
        } catch (err) {
            setClearError(validationMessages(err)[0] ?? err.message ?? 'Could not clear the activity list.');
        } finally {
            setClearing(false);
        }
    }

    return (
        <section className="card padded recent-activity">
            <div className="filter-head">
                <div>
                    <h3>Recent Activity List</h3>
                    <p className="muted">See the sales completed recently.</p>
                </div>
                <div className="activity-options" ref={optionsRef}>
                    <button
                        type="button"
                        className="icon-btn is-outlined"
                        onClick={() => setOptionsOpen((open) => !open)}
                        aria-haspopup="menu"
                        aria-expanded={optionsOpen}
                        aria-label="Activity options"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none" aria-hidden="true">
                            <circle cx="12" cy="5" r="1.7" />
                            <circle cx="12" cy="12" r="1.7" />
                            <circle cx="12" cy="19" r="1.7" />
                        </svg>
                    </button>
                    {optionsOpen && (
                        <div className="activity-options-panel" role="menu" aria-label="Activity options">
                            <button
                                type="button"
                                className="activity-options-item activity-options-danger"
                                role="menuitem"
                                onClick={() => {
                                    setOptionsOpen(false);

                                    if (activity.length === 0) {
                                        setEmptyNotice({
                                            title: 'Nothing to clear',
                                            description: 'The activity list is already empty. Complete a sale and its activity will show up here.',
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
                            <path d="M6 3h12a1 1 0 0 1 1 1v17l-3-2-2 2-2-2-2 2-2-2-3 2V4a1 1 0 0 1 1-1z" />
                            <path d="M9 8h6" />
                            <path d="M9 12h6" />
                        </svg>
                    </span>
                    <p className="table-empty-title">No sale activity yet</p>
                    <p className="table-empty-sub">Complete a sale and it will show up here.</p>
                </div>
            ) : (
                <>
                    <div className="activity-shell">
                        <ul className="activity-list">
                            {pagedActivity.map((item) => (
                                <li key={item.id}>
                                    <button
                                        type="button"
                                        className="activity-row"
                                        onClick={() => setDetail(item)}
                                        aria-label={`View details: ${summary(item)}`}
                                    >
                                        <span className="activity-icon activity-icon-ok" aria-hidden="true">
                                            {SALE_ICON}
                                        </span>
                                        <div className="activity-body">
                                            <p className="activity-desc">{summary(item)}</p>
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
                            ))}
                        </ul>
                    </div>

                    {activity.length > PER_PAGE && (
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
                            <span className="page-detail">{Math.min(safePage * PER_PAGE, activity.length)} of {activity.length}</span>
                        </div>
                    )}
                </>
            )}

            {detail && (
                <Modal
                    title={(
                        <span className="activity-detail-title">
                            <span className="activity-detail-infoicon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 11v5M12 8h.01" />
                                </svg>
                            </span>
                            Sale details
                        </span>
                    )}
                    className="modal-activity"
                    noClose
                    onClose={() => setDetail(null)}
                >
                    <dl className="activity-detail-list">
                        <div className="activity-detail-item">
                            <dt>Sale</dt>
                            <dd>{summary(detail)}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>By</dt>
                            <dd>
                                {detail.user_name}
                                {detail.user_role && <span className="activity-role">{detail.user_role}</span>}
                            </dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>When</dt>
                            <dd>
                                {detail.created_at
                                    ? new Date(detail.created_at).toLocaleString()
                                    : detail.created_at_human}
                            </dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Channel</dt>
                            <dd>
                                <span className={`pill ${detail.meta?.channel === 'admin' ? 'pill-info' : 'pill-neutral'}`}>
                                    {channelLabel(detail)}
                                </span>
                            </dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Total</dt>
                            <dd>{formatPeso(detail.meta?.total_amount ?? 0)}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Payment</dt>
                            <dd>{formatPeso(detail.meta?.payment_amount ?? 0)}</dd>
                        </div>
                        <div className="activity-detail-item">
                            <dt>Change</dt>
                            <dd>{formatPeso(detail.meta?.change_amount ?? 0)}</dd>
                        </div>
                        {itemLines(detail).length > 0 && (
                            <div className="activity-detail-item">
                                <dt>Items</dt>
                                <dd>
                                    <ul className="activity-detail-changes">
                                        {itemLines(detail).map((line, index) => (
                                            <li key={`${line.name}-${index}`}>
                                                {line.quantity}× {line.name} @ {formatPeso(line.unit_price)} = {formatPeso(line.line_total)}
                                            </li>
                                        ))}
                                    </ul>
                                </dd>
                            </div>
                        )}
                    </dl>
                    <div className="modal-actions modal-actions-center">
                        <button type="button" className="btn btn-ghost" onClick={() => setDetail(null)}>Close</button>
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

            {clearConfirm && (
                <ConfirmDialog
                    tone="danger"
                    title="Clear activity list?"
                    description="This permanently deletes the completed-sale history from the activity log. The sales themselves stay on the Transactions page. This action cannot be undone."
                    confirmLabel="Clear"
                    loadingLabel="Clearing…"
                    loading={clearing}
                    error={clearError}
                    onConfirm={handleClear}
                    onCancel={() => {
                        if (!clearing) {
                            setClearConfirm(false);
                            setClearError(null);
                        }
                    }}
                />
            )}

            {toast && <Toast tone="success" onClose={() => setToast(null)}>{toast}</Toast>}
        </section>
    );
}
