@extends('layouts.app')

@section('title', 'User Accounts - Smart-Stock')

@section('content')
    <h1 class="page-title">User Accounts</h1>
    <p class="page-subtitle">
        Admin-only. Accounts are created here &mdash; staff cannot register themselves.
        Deactivating an account keeps its sales history intact.
    </p>

    <!-- ====== CREATE ACCOUNT ====== -->
    <div class="filter-bar">
        <div class="filter-group">
            <label for="newName">Full name</label>
            <input type="text" id="newName" placeholder="Juan Dela Cruz" autocomplete="off">
        </div>
        <div class="filter-group">
            <label for="newUsername">Username</label>
            <input type="text" id="newUsername" placeholder="juan" autocomplete="off">
        </div>
        <div class="filter-group">
            <label for="newEmail">Email</label>
            <input type="email" id="newEmail" placeholder="juan@example.com" autocomplete="off">
        </div>
        <div class="filter-group">
            <label for="newRole">Role</label>
            <select id="newRole">
                <option value="cashier">Staff (POS only)</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="newPassword">Password</label>
            <input type="password" id="newPassword" placeholder="Min. 6 characters" autocomplete="new-password">
        </div>
        <div class="filter-group">
            <label for="newPasswordConfirm">Confirm</label>
            <input type="password" id="newPasswordConfirm" placeholder="Repeat password" autocomplete="new-password">
        </div>
        <div class="filter-actions">
            <button class="btn-primary" onclick="createUser()">Create Account</button>
        </div>
    </div>

    <!-- ====== ACCOUNT LIST ====== -->
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <tr>
                    <td colspan="7" class="empty-state">
                        <span class="spinner" aria-hidden="true"></span> Loading accounts...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <style>
        .role-badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
        .role-badge.admin  { background:rgba(59,130,246,.16); color:#60a5fa; border:1px solid rgba(59,130,246,.35); }
        .role-badge.cashier{ background:rgba(16,185,129,.14); color:#34d399; border:1px solid rgba(16,185,129,.32); }
        .status-badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:700; }
        .status-badge.active  { background:rgba(16,185,129,.14); color:#34d399; border:1px solid rgba(16,185,129,.32); }
        .status-badge.inactive{ background:rgba(148,163,184,.14); color:#94a3b8; border:1px solid rgba(148,163,184,.32); }
        .row-inactive td { opacity:.55; }
        .btn-mini { padding:5px 11px; font-size:12px; border-radius:7px; border:1px solid rgba(148,163,184,.3); background:transparent; color:inherit; cursor:pointer; }
        .btn-mini:hover { background:rgba(148,163,184,.12); }
        .btn-mini.danger { border-color:rgba(239,68,68,.4); color:#f87171; }
        .btn-mini.danger:hover { background:rgba(239,68,68,.12); }
        .btn-mini + .btn-mini { margin-left:6px; }
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

    async function loadUsers() {
        const tbody = document.getElementById('userTableBody');
        try {
            const res  = await fetch('/api/users', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Unable to load accounts');
            const data = await res.json();
            USERS = data.users || [];
            renderUsers();
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Unable to load accounts.</td></tr>';
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
                <td style="text-align:right;white-space:nowrap;">
                    <button class="btn-mini" onclick="promptResetPassword(${u.id}, ${escapeHtml(JSON.stringify(u.username))})">Reset Password</button>
                    <button class="btn-mini" onclick="changeRole(${u.id})">Change Role</button>
                    ${u.is_active
                        ? `<button class="btn-mini danger" onclick="setActive(${u.id}, false)">Deactivate</button>`
                        : `<button class="btn-mini" onclick="setActive(${u.id}, true)">Reactivate</button>`}
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
