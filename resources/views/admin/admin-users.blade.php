@extends('layouts.dashboard-main-frame')

@section('title', 'User Accounts - Smart-Stock')

@section('content')
    <div class="page-header hero">
        <div class="hero-content">
            <h1 class="page-title">User Accounts</h1>
            <p class="page-subtitle">Create and manage staff accounts</p>
        </div>
    </div>

    <!-- ====== CREATE ACCOUNT ====== -->
    <div class="section-card">
        <div class="section-card-header">
            <h2 class="section-card-title">Create Account</h2>
            <p class="section-card-desc">Add a new staff member or administrator</p>
        </div>
        <div class="filter-bar">
            <div class="filter-bar-inner">
                <div class="filter-group">
                    <label class="filter-label" for="newName">Full Name</label>
                    <input type="text" class="field-input" id="newName" placeholder="Juan Dela Cruz" autocomplete="off">
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="newUsername">Username</label>
                    <input type="text" class="field-input" id="newUsername" placeholder="juan" autocomplete="off">
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="newEmail">Email</label>
                    <input type="email" class="field-input" id="newEmail" placeholder="juan@example.com" autocomplete="off">
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="newRole">Role</label>
                    <select class="field-input" id="newRole">
                        <option value="cashier">Staff (POS only)</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="newPassword">Password</label>
                    <input type="password" class="field-input" id="newPassword" placeholder="Min. 6 characters" autocomplete="new-password">
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="newPasswordConfirm">Confirm</label>
                    <input type="password" class="field-input" id="newPasswordConfirm" placeholder="Repeat password" autocomplete="new-password">
                </div>
                <div class="filter-actions">
                    <button class="btn-add" onclick="createUser()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>Create Account</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== ACCOUNT LIST ====== -->
    <div class="section-card">
        <div class="section-card-header">
            <div class="section-card-header-row">
                <div>
                    <h2 class="section-card-title">Account List</h2>
                    <p class="section-card-desc">Overview of all registered user accounts</p>
                </div>
                <div class="view-toggle">
                    <button class="view-toggle-btn active" id="tableViewBtn" onclick="switchView('table')" title="Table View">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="table-wrapper" id="userTableView">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr><td colspan="7" class="empty-state">Loading accounts...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .page-header.hero {
            --hero-card-bg: #13294f;
            --hero-card-border: rgba(96,165,250,0.18);
            --hero-card-title: #f8fafc;
            --hero-card-subtitle: rgba(226,232,240,0.88);
        }
        .page-title { font-size: 30px; font-weight: 800; color: var(--hero-card-title, #f8fafc); margin: 0 0 6px 0; line-height: 1.25; letter-spacing: -0.02em; }
        .page-subtitle { color: var(--hero-card-subtitle, rgba(226,232,240,0.88)); font-size: 14px; margin: 0; line-height: 1.5; }
        .page-header.hero .page-subtitle { margin-bottom: 0; }
        .page-header.hero {
            position: relative; overflow: hidden; border-radius: 16px;
            border: 1px solid var(--hero-card-border); margin-bottom: 20px;
            min-height: 140px; display: flex; align-items: flex-end;
            padding: 28px 28px 24px; background: var(--hero-card-bg);
        }
        .hero-content { position: relative; z-index: 1; max-width: 640px; }
        body.light-theme .page-header.hero {
            --hero-card-bg: #ffffff;
            --hero-card-border: rgba(37,99,235,0.15);
            --hero-card-title: #0f172a;
            --hero-card-subtitle: #475569;
        }

        /* SECTION CARD */
        .section-card {
            background: rgba(255,255,255,0.025);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        body.light-theme .section-card {
            background: #ffffff;
            border-color: rgba(15,23,42,0.08);
        }
        .section-card-header { padding: 18px 20px 16px 20px; }
        .section-card-header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }
        .section-card-title { font-size: 15px; font-weight: 700; color: #e2e8f0; margin: 0 0 3px 0; }
        body.light-theme .section-card-title { color: #1e293b; }
        .section-card-desc { font-size: 13px; color: #64748b; margin: 0; }
        body.light-theme .section-card-desc { color: #94a3b8; }

        /* FILTER BAR */
        .filter-bar {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 14px 16px;
            margin: 0 16px 16px 16px;
        }
        body.light-theme .filter-bar { background: #f8fafc; border-color: rgba(15,23,42,0.08); }
        /* Two-column field grid instead of a single wrapping flex row. */
        .filter-bar-inner {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 16px;
            align-items: end;
            /* The shared layout makes `.filter-bar` a flex container, so this
               grid would shrink-to-fit its content and leave the right half
               of the bar empty. Claim the full bar width. */
            width: 100%;
            min-width: 0;
        }
        .filter-group { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
        /* The submit action sits on its own row under the fields but stays
           content-width instead of stretching the full bar. The shared layout
           sets `.filter-actions { margin-left: auto }`, which would push it to
           the far right in a grid context — reset so it aligns with the fields. */
        .filter-bar-inner > .filter-actions {
            grid-column: 1 / -1;
            margin-left: 0;
            justify-content: flex-end;
        }
        .filter-bar-inner > .filter-actions .btn-add { width: auto; padding: 0 22px; }
        .filter-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        body.light-theme .filter-label { color: #94a3b8; }

        /* FIELDS */
        .field-input {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px;
            padding: 0 12px;
            height: 36px;
            color: #f8fafc;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border-color 0.2s;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
        }
        input.field-input { cursor: text; padding: 0 12px 0 14px; }
        select.field-input {
            padding: 0 30px 0 12px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px;
        }
        .field-input:focus { border-color: #3b82f6; }
        .field-input::placeholder { color: #64748b; }
        select.field-input option { background: #1e293b; color: #e2e8f0; }
        body.light-theme .field-input { background: #ffffff; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .field-input::placeholder { color: #94a3b8; }
        body.light-theme select.field-input option { background: #ffffff; color: #0f172a; }

        /* BUTTONS & TOGGLE */
        .btn-add {
            background: #2563eb; color: #fff; border: none; border-radius: 8px;
            padding: 0 18px; height: 36px; font-size: 13px; font-weight: 600; cursor: pointer;
            font-family: 'Inter', sans-serif; display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.15s; white-space: nowrap;
            justify-content: center;
        }
        .btn-add:hover { opacity: 0.9; transform: translateY(-1px); }
        /* Single column only when the card is too narrow for two usable fields. */
        @media (max-width: 620px) {
            .filter-bar-inner { grid-template-columns: minmax(0, 1fr); }
        }
        .view-toggle {
            display: flex; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; overflow: hidden;
        }
        body.light-theme .view-toggle { background: #f1f5f9; border-color: rgba(15,23,42,0.1); }
        .view-toggle-btn {
            display: flex; align-items: center; justify-content: center; width: 36px; height: 34px;
            background: transparent; border: none; color: #64748b; cursor: pointer; transition: all 0.15s;
        }
        .view-toggle-btn:hover { color: #94a3b8; }
        .view-toggle-btn.active { background: rgba(59,130,246,0.15); color: #60a5fa; }
        body.light-theme .view-toggle-btn { color: #94a3b8; }
        body.light-theme .view-toggle-btn.active { background: rgba(59,130,246,0.1); color: #3b82f6; }

        /* TABLE */
        .section-card .table-wrapper {
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;
            margin: 0 16px 16px 16px; width: calc(100% - 32px); overflow-x: auto; overflow-y: visible;
        }
        body.light-theme .section-card .table-wrapper { border-color: rgba(15,23,42,0.12); background: #ffffff; }
        .section-card .table-wrapper .data-table {
            width: 100%; min-width: 1000px;
            border-collapse: separate; border-spacing: 0 4px;
            border: none;
            table-layout: auto;
        }
        .section-card .table-wrapper .data-table th,
        .section-card .table-wrapper .data-table td { border: none; }
        .section-card .table-wrapper .data-table th:nth-child(1), .section-card .table-wrapper .data-table td:nth-child(1) { width: 14%; }
        .section-card .table-wrapper .data-table th:nth-child(2), .section-card .table-wrapper .data-table td:nth-child(2) { width: 10%; }
        .section-card .table-wrapper .data-table th:nth-child(3), .section-card .table-wrapper .data-table td:nth-child(3) { width: 17%; }
        .section-card .table-wrapper .data-table th:nth-child(4), .section-card .table-wrapper .data-table td:nth-child(4) { width: 8%; }
        .section-card .table-wrapper .data-table th:nth-child(5), .section-card .table-wrapper .data-table td:nth-child(5) { width: 9%; }
        .section-card .table-wrapper .data-table th:nth-child(6), .section-card .table-wrapper .data-table td:nth-child(6) { width: 14%; white-space: nowrap; }
        .section-card .table-wrapper .data-table th:nth-child(7), .section-card .table-wrapper .data-table td:nth-child(7) { width: 28%; }
        .section-card .table-wrapper .data-table thead th {
            background: rgba(255,255,255,0.04); padding: 14px 16px; text-align: left;
            font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: normal;
            border: none; line-height: 1.4;
        }
        .section-card .table-wrapper .data-table thead th:first-child { border-top-left-radius: 9px; }
        .section-card .table-wrapper .data-table thead th:last-child  { border-top-right-radius: 9px; text-align: right; }
        .section-card .table-wrapper .data-table tbody tr,
        .section-card .table-wrapper .data-table tbody tr:hover {
            background: rgba(255,255,255,0.045); border: none; border-radius: 8px; transition: none;
        }
        .section-card .table-wrapper .data-table tbody td {
            padding: 14px 16px; font-size: 13px; color: #cbd5e1; border: none; vertical-align: middle;
        }
        .section-card .table-wrapper .data-table tbody td:last-child { text-align: right; }

        /* LOADING STATE */
        #userTableView.loading tbody td:not(.empty-state) { color: transparent; }
        #userTableView.loading tbody td.empty-state { color: #64748b; }
        /* `min-width: 0` alone is not enough here: with `table-layout: auto` the
           seven percentage columns still resolve their content width, so the
           placeholder row overflows and shows a scrollbar. `fixed` makes the
           table obey the wrapper width while loading. Loaded rows keep `auto`. */
        #userTableView.loading .data-table { min-width: 0; table-layout: fixed; }
        #userTableView.loading { overflow-x: hidden; }
        /* The placeholder must be re-scoped under .data-table tbody td
           (0,3,2). A bare `.empty-state` (0,1,0) loses to it and the loading
           row collapses to a single data-row height instead of the tall
           centred block the other tabs use.
           `:last-child` is repeated deliberately: the placeholder is the
           row's only cell, so it also matches the `td:last-child` rule above
           and would be pushed to the right edge without the extra weight. */
        .section-card .table-wrapper .data-table tbody td.empty-state,
        .section-card .table-wrapper .data-table tbody td.empty-state:last-child {
            padding: 48px;
            font-size: 14px;
            text-align: center;
        }

        /* BADGES */
        /* Badges are text-only: no fill, no border — just a slightly darker
           shade of the state color so they stay legible on both themes. */
        .role-badge { display:inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .role-badge.admin   { background:transparent; border:none; color:#3b82f6; }
        .role-badge.cashier { background:transparent; border:none; color:#059669; }
        .status-badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:700; }
        .status-badge.active  { background:transparent; border:none; color:#059669; }
        .status-badge.inactive{ background:transparent; border:none; color:#64748b; }
        body.light-theme .role-badge.admin    { color:#1d4ed8; }
        body.light-theme .role-badge.cashier  { color:#047857; }
        body.light-theme .status-badge.active { color:#047857; }
        body.light-theme .status-badge.inactive { color:#475569; }
        .row-inactive td { opacity:.55; }

        /* ROW ACTION BUTTONS */
        .btn-mini { padding:5px 9px; font-size:11.5px; border-radius:7px; border:1px solid rgba(148,163,184,.3); background:transparent; color:inherit; cursor:pointer; font-family: 'Inter', sans-serif; transition: all 0.15s; white-space: nowrap; }
        .btn-mini:hover { background:rgba(148,163,184,.12); }
        .btn-mini.danger { border-color:rgba(239,68,68,.4); color:#f87171; }
        .btn-mini.danger:hover { background:rgba(239,68,68,.12); }
        .btn-mini + .btn-mini { margin-left:6px; }
        .actions-cell { text-align: right; white-space: nowrap; }
        .actions-inner { display: flex; align-items: center; justify-content: flex-end; gap: 6px; flex-wrap: nowrap; }

        /* LIGHT THEME TABLE */
        body.light-theme .section-card .table-wrapper .data-table thead th {
            background: rgba(100,116,139,0.16); color: #1e293b;
        }
        body.light-theme .section-card .table-wrapper .data-table tbody tr,
        body.light-theme .section-card .table-wrapper .data-table tbody tr:hover { background: rgba(15,23,42,0.04); }
        body.light-theme .section-card .table-wrapper .data-table tbody td { color: #334155; }

        @media (max-width: 640px) {
            .section-card .table-wrapper { margin: 0 12px 12px 12px; width: calc(100% - 24px); }
            .section-card-header { padding: 14px 14px 0 14px; }
            .section-card .filter-bar { margin: 10px 12px 12px 12px; padding: 12px; }
        }
    </style>
@endsection

@push('scripts')
<script>
    let USERS = [];

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function toast(message, type = 'success') {
        if (typeof showToast === 'function') { showToast(message, type); return; }
        alert(message);
    }

    function switchView(view) {
        const btn    = document.getElementById('tableViewBtn');
        const target = document.getElementById('userTableView');
        if (view === 'table' && btn && target) btn.classList.add('active');
    }

    async function loadUsers() {
        const tbody = document.getElementById('userTableBody');
        const wrapper = document.getElementById('userTableView');
        wrapper.classList.add('loading');
        try {
            const res  = await fetch('/api/users', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Unable to load accounts');
            const data = await res.json();
            USERS = data.users || [];
            renderUsers();
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Unable to load accounts.</td></tr>';
        } finally {
            wrapper.classList.remove('loading');
        }
    }

    function renderUsers() {
        const tbody = document.getElementById('userTableBody');

        if (USERS.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No accounts yet.</td></tr>';
            return;
        }

        tbody.innerHTML = USERS.map(u => `
            <tr class="${u.is_active ? '' : 'row-inactive'}">
                <td>${escapeHtml(u.name)}</td>
                <td>${escapeHtml(u.username)}</td>
                <td>${escapeHtml(u.email)}</td>
                <td><span class="role-badge ${escapeHtml(u.role)}">${u.role === 'admin' ? 'Admin' : 'Staff'}</span></td>
                <td><span class="status-badge ${u.is_active ? 'active' : 'inactive'}">${u.is_active ? 'Active' : 'Deactivated'}</span></td>
                <td>${escapeHtml(u.created_at || '—')}</td>
                <td class="actions-cell">
                    <div class="actions-inner">
                        <button class="btn-mini" onclick="promptResetPassword(${u.id}, ${escapeHtml(JSON.stringify(u.username))})">Reset Password</button>
                        <button class="btn-mini" onclick="changeRole(${u.id})">Change Role</button>
                        ${u.is_active
                            ? `<button class="btn-mini danger" onclick="setActive(${u.id}, false)">Deactivate</button>`
                            : `<button class="btn-mini" onclick="setActive(${u.id}, true)">Reactivate</button>`}
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async function createUser() {
        const payload = {
            name:     document.getElementById('newName').value.trim(),
            username: document.getElementById('newUsername').value.trim(),
            email:    document.getElementById('newEmail').value.trim(),
            role:     document.getElementById('newRole').value,
            password: document.getElementById('newPassword').value,
            password_confirmation: document.getElementById('newPasswordConfirm').value,
        };

        if (!payload.name || !payload.username || !payload.email || !payload.password) {
            toast('Please fill in every field.', 'error');
            return;
        }
        if (payload.password !== payload.password_confirmation) {
            toast('Passwords do not match.', 'error');
            return;
        }

        const res = await fetch('/api/users', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            const first = data.errors ? Object.values(data.errors)[0][0] : data.message;
            toast(first || 'Could not create the account.', 'error');
            return;
        }

        ['newName','newUsername','newEmail','newPassword','newPasswordConfirm'].forEach(id => {
            document.getElementById(id).value = '';
        });
        toast(data.message || 'Account created.');
        loadUsers();
    }

    async function setActive(id, active) {
        const verb = active ? 'reactivate' : 'deactivate';
        if (!active && !confirm('Deactivate this account? The employee keeps their sales history, but can no longer sign in.')) return;

        const res = await fetch(`/api/users/${id}/${verb}`, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json().catch(() => ({}));
        toast(data.message || 'Done', res.ok ? 'success' : 'error');
        loadUsers();
    }

    async function changeRole(id) {
        const user  = USERS.find(u => u.id === id);
        if (!user) return;

        const next  = user.role === 'admin' ? 'cashier' : 'admin';
        const label = next === 'admin' ? 'Admin (full access)' : 'Staff (POS only)';
        if (!confirm(`Change ${user.username}'s role to ${label}?`)) return;

        const res = await fetch(`/api/users/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ role: next }),
        });
        const data = await res.json().catch(() => ({}));
        const msg  = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Done');
        toast(msg, res.ok ? 'success' : 'error');
        loadUsers();
    }

    function promptResetPassword(id, username) {
        const next = prompt(`New password for "${username}" (min. 6 characters):`);
        if (next === null) return;
        if (next.length < 6) { toast('Password must be at least 6 characters.', 'error'); return; }

        const confirmPw = prompt('Confirm the new password:');
        if (confirmPw === null) return;
        if (next !== confirmPw) { toast('Passwords do not match.', 'error'); return; }

        fetch(`/api/users/${id}/reset-password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ password: next, password_confirmation: confirmPw }),
        })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            const msg = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Done');
            toast(msg, ok ? 'success' : 'error');
        })
        .catch(() => toast('Could not reset the password.', 'error'));
    }

    loadUsers();
</script>
@endpush
