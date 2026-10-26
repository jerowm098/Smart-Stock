import { useCallback, useEffect, useState } from 'react';
import Modal from '../../components/Modal.jsx';
import Alert, { FieldError, Loading } from '../../components/Feedback.jsx';
import { del, get, post, validationMessages } from '../../lib/api.js';
import { formatDate } from '../../lib/format.js';

const EMPTY = { name: '', contact_person: '', phone: '', email: '' };

export default function Suppliers() {
    const [suppliers, setSuppliers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [message, setMessage] = useState(null);

    const [showAdd, setShowAdd] = useState(false);
    const [form, setForm] = useState(EMPTY);
    const [saving, setSaving] = useState(false);
    const [formError, setFormError] = useState(null);
    const [fieldErrors, setFieldErrors] = useState([]);
    const [deletingId, setDeletingId] = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const rows = await get('/api/suppliers/active');
            setSuppliers(Array.isArray(rows) ? rows : []);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    async function handleSave(event) {
        event.preventDefault();
        setSaving(true);
        setFormError(null);
        setFieldErrors([]);

        try {
            const response = await post('/api/suppliers', {
                ...form,
                contact_person: form.contact_person || null,
                phone: form.phone || null,
                email: form.email || null,
            });

            setMessage(response.message);
            setForm(EMPTY);
            setShowAdd(false);
            await load();
        } catch (err) {
            setFormError(err.message);
            setFieldErrors(validationMessages(err));
        } finally {
            setSaving(false);
        }
    }

    async function handleDeactivate(supplier) {
        if (!window.confirm(`Remove "${supplier.name}" from the active supplier list?`)) {
            return;
        }

        setDeletingId(supplier.id);
        setMessage(null);
        setError(null);

        try {
            const response = await del(`/api/suppliers/${supplier.id}`);
            setMessage(response.message);
            await load();
        } catch (err) {
            setError(err.message);
        } finally {
            setDeletingId(null);
        }
    }

    const setField = (name) => (event) => setForm((current) => ({ ...current, [name]: event.target.value }));

    return (
        <div className="page">
            <header className="page-head">
                <div>
                    <h2>Suppliers</h2>
                    <p>Directory of active suppliers used by the Stock-In screen.</p>
                </div>
                <button type="button" className="btn btn-primary" onClick={() => setShowAdd(true)}>
                    Add supplier
                </button>
            </header>

            {message && <Alert tone="success" onClose={() => setMessage(null)}>{message}</Alert>}
            {error && <Alert tone="error" onClose={() => setError(null)}>{error}</Alert>}

            <section className="card table-card">
                {loading && <Loading label="Loading suppliers…" />}

                {!loading && !error && (
                    <div className="table-wrap">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Contact person</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Added</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {suppliers.map((supplier) => (
                                    <tr key={supplier.id}>
                                        <td><strong>{supplier.name}</strong></td>
                                        <td>{supplier.contact_person || '—'}</td>
                                        <td className="mono">{supplier.phone || '—'}</td>
                                        <td>{supplier.email || '—'}</td>
                                        <td className="muted">{formatDate(supplier.created_at)}</td>
                                        <td className="text-right">
                                            <button
                                                type="button"
                                                className="btn btn-ghost btn-sm"
                                                disabled={deletingId === supplier.id}
                                                onClick={() => handleDeactivate(supplier)}
                                            >
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {suppliers.length === 0 && <p className="muted padded">No active suppliers yet.</p>}
                    </div>
                )}
            </section>

            {showAdd && (
                <Modal title="Add supplier" onClose={() => setShowAdd(false)}>
                    <form className="form" onSubmit={handleSave}>
                        <label className="field">
                            <span>Supplier name *</span>
                            <input type="text" required maxLength={255} value={form.name} onChange={setField('name')} autoFocus />
                        </label>
                        <div className="field-row">
                            <label className="field">
                                <span>Contact person</span>
                                <input type="text" maxLength={255} value={form.contact_person} onChange={setField('contact_person')} />
                            </label>
                            <label className="field">
                                <span>Phone</span>
                                <input type="text" maxLength={50} value={form.phone} onChange={setField('phone')} />
                            </label>
                        </div>
                        <label className="field">
                            <span>Email</span>
                            <input type="email" maxLength={255} value={form.email} onChange={setField('email')} />
                        </label>

                        {formError && (
                            <div className="alert alert-error" role="alert">
                                <span>{formError}</span>
                                <FieldError messages={fieldErrors} />
                            </div>
                        )}

                        <div className="modal-actions">
                            <button type="button" className="btn btn-ghost" onClick={() => setShowAdd(false)}>
                                Cancel
                            </button>
                            <button type="submit" className="btn btn-primary" disabled={saving}>
                                {saving ? 'Saving…' : 'Save supplier'}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}
        </div>
    );
}
