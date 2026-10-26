export default function Pagination({ page, lastPage, total, perPage, onPage }) {
    if (!lastPage || lastPage <= 1) {
        return null;
    }

    const from = (page - 1) * perPage + 1;
    const to = Math.min(page * perPage, total);

    return (
        <div className="pagination">
            <button type="button" className="btn btn-ghost" disabled={page <= 1} onClick={() => onPage(page - 1)}>
                ← Prev
            </button>
            <span className="muted">
                Page {page} of {lastPage} · {total ? `${from}–${to} of ${total}` : '0 results'}
            </span>
            <button type="button" className="btn btn-ghost" disabled={page >= lastPage} onClick={() => onPage(page + 1)}>
                Next →
            </button>
        </div>
    );
}
