import { useCallback, useEffect, useState } from 'react';
import Alert, { Loading } from '../../components/Feedback.jsx';
import { del, get, post } from '../../lib/api.js';
import { formatDate } from '../../lib/format.js';

export default function Backups() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [message, setMessage] = useState(null);
    const [running, setRunning] = useState(false);
    const [deleting, setDeleting] = useState(null);
    const [keep, setKeep] = useState(7);

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

    async function runBackup() {
        setRunning(true);
        setMessage(null);
        setError(null);

        try {
            const response = await post('/api/backups/run', { keep });
            setMessage(response.success ? 'Backup created successfully.' : response.output);
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
        setMessage(null);
        setError(null);

        try {
            const response = await del(`/api/backups/${file}`);
            setMessage(response.message);
            await load();
        } catch (err) {
            setError(err.message);
        } finally {
            setDeleting(null);
        }
    }

    return (
        <div className="page">
            <header className="page-head">
                <div>
                    <h2>Backups</h2>
                    <p>Portable JSON dumps of the whole database, stored on the server.</p>
                </div>
                <div className="head-actions">
                    <label className="inline-select">
                        <span>Keep newest</span>
                        <select className="input" value={keep} onChange={(event) => setKeep(Number(event.target.value))}>
                            {[7, 14, 30].map((value) => (
                                <option key={value} value={value}>{value}</option>
                            ))}
                        </select>
                    </label>
                    <button type="button" className="btn btn-primary" onClick={runBackup} disabled={running}>
                        {running ? 'Running…' : 'Run backup now'}
                    </button>
                </div>
            </header>

            {message && <Alert tone="success" onClose={() => setMessage(null)}>{message}</Alert>}
            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}

            {data && (
                <div className="stat-grid stat-grid-3">
                    <article className="card stat">
                        <span>Backups on file</span>
                        <strong>{data.count}</strong>
                    </article>
                    <article className="card stat">
                        <span>Schedule</span>
                        <strong className="stat-sub">{data.schedule}</strong>
                    </article>
                    <article className="card stat">
                        <span>Driver</span>
                        <strong className="stat-sub">{data.driver}</strong>
                    </article>
                </div>
            )}

            <section className="card table-card">
                {loading && <Loading label="Loading backups…" />}

                {!loading && !error && (
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Type</th>
                                    <th className="text-right">Size</th>
                                    <th>Created</th>
                                    <th className="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {(data?.backups ?? []).map((backup) => (
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

                        {(data?.backups ?? []).length === 0 && (
                            <p className="muted padded">No backups yet — run one now to create the first dump.</p>
                        )}
                    </div>
                )}
            </section>
        </div>
    );
}
