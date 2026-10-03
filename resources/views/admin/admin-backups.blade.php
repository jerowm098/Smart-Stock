@extends('layouts.app')

@section('title', 'Backups - Smart-Stock')

@section('content')
    <div class="page-header hero">
        <div class="hero-content">
            <h1 class="page-title">Backups</h1>
            <p class="page-subtitle">Database backups for admin</p>
        </div>
    </div>

    <!-- ====== POLICY CARD ====== -->
    <div class="white-form">
        <div class="section-card-header">
            <h2 class="section-card-title">Backup Settings</h2>
            <p class="section-card-desc">Configure and manage database backup policies</p>
        </div>
        <div class="policy-card">
            <div>
                <div class="policy-title">Backup Policy (SS-39)</div>
                <div class="policy-text" id="policyText">Auto daily 02:00 (Asia/Manila) · keep newest 7 · Supabase PostgreSQL JSON dump</div>
                <div class="policy-sub" id="driverText"></div>
            </div>
            <button class="btn-primary" id="runBtn" onclick="runBackup()">▶ Run Backup Now</button>
        </div>
    </div>

    <!-- ====== TABLE ====== -->
    <div class="white-form">
        <div class="section-card-header">
            <div class="section-card-header-row">
                <div>
                    <h2 class="section-card-title">Backup History</h2>
                    <p class="section-card-desc">View all database backup records</p>
                </div>
                <!-- The count lives in the header rather than floating between
                     the two cards, where it read as loose body text. -->
                <div class="result-meta" id="resultMeta"></div>
            </div>
        </div>
        <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="backupBody">
                        <tr><td colspan="5" class="empty-state">Loading backups...</td></tr>
                    </tbody>
                </table>
            </div>
    </div>
@endsection

@push('styles')
    <style>
        .white-form {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            margin-bottom: 20px;
        }
        .white-form:last-child {
            margin-bottom: 0;
        }
        body.light-theme .white-form {
            background: #ffffff;
            border-color: rgba(15, 23, 42, 0.08);
        }
        .section-card-header {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }
        .section-card-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
        }
        .section-card-desc {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }
        .table-wrapper {
            background: transparent;
            border: none;
            border-radius: 0;
            overflow-x: auto;
            overflow-y: visible;
        }
        .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 6px; }
        .page-subtitle { color: #64748b; font-size: 14px; margin-bottom: 20px; }
        .policy-card {
            display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
            background: rgba(96,165,250,0.06); border: 1px solid rgba(96,165,250,0.2);
            border-radius: 12px; padding: 18px 20px; margin-bottom: 12px;
        }
        .policy-title { font-size: 14px; font-weight: 700; color: #60a5fa; margin-bottom: 4px; }
        .policy-text { font-size: 13px; color: #cbd5e1; }
        .policy-sub { font-size: 12px; color: #64748b; margin-top: 4px; font-family: monospace; }
        .btn-primary {
            background: #2563eb; color: #fff; border: none;
            border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
        }
        .btn-primary:disabled { opacity: 0.5; cursor: wait; }
        .result-meta { font-size: 12px; color: #64748b; white-space: nowrap; }
        /* Same flex row the Products/Users cards use, so the count sits at the
           top-right of the header instead of below it. */
        .section-card-header-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }
        .table-wrapper {
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px; overflow-x: auto; overflow-y: visible;
        }
        table { width: 100%; min-width: 680px; border-collapse: separate; border-spacing: 0; border: 1px solid rgba(255,255,255,0.08); table-layout: fixed; }
        thead th {
            background: rgba(255,255,255,0.04); padding: 14px 16px; text-align: left;
            font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: normal; border-bottom: 1px solid rgba(255,255,255,0.08); border-right: 1px solid rgba(255,255,255,0.06); line-height: 1.4;
        }
        thead th:last-child { border-right: none; }
        tbody tr { border-top: 1px solid rgba(255,255,255,0.06); }
        tbody tr:hover { background: rgba(255,255,255,0.03); }
        tbody td { padding: 14px 16px; font-size: 13px; color: #cbd5e1; border-right: 1px solid rgba(255,255,255,0.06); border-bottom: 1px solid rgba(255,255,255,0.06); vertical-align: middle; }
        tbody td:last-child { border-right: none; }
        tbody tr:last-child td { border-bottom: none; }
        .file-name { font-family: monospace; font-size: 12px; color: #e2e8f0; }
        .type-pill {
            display: inline-block; font-size: 10px; font-weight: 700; text-transform: uppercase;
            padding: 4px 10px; border-radius: 20px; background: rgba(96,165,250,0.12); color: #60a5fa; white-space: nowrap;
        }
        .row-actions { display: flex; gap: 8px; }
        .btn-dl {
            background: rgba(74,222,128,0.12); color: #4ade80; border: 1px solid rgba(74,222,128,0.3);
            border-radius: 7px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap;
        }
        .btn-dl:hover { background: rgba(74,222,128,0.2); }
        .btn-del {
            background: rgba(248,113,113,0.1); color: #f87171; border: 1px solid rgba(248,113,113,0.25);
            border-radius: 7px; padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap;
        }
        .btn-del:hover { background: rgba(248,113,113,0.2); }
        .empty-state { text-align: center; color: #475569; padding: 48px; font-size: 14px; }
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
        body.light-theme .policy-card { background: rgba(37,99,235,0.05); border-color: rgba(37,99,235,0.2); }
        body.light-theme .policy-text { color: #475569; }
        body.light-theme .table-wrapper { background: #fff; border-color: rgba(15,23,42,0.08); }
        body.light-theme table thead th { background: rgba(15,23,42,0.02); }
        body.light-theme table tbody td { color: #475569; }
        body.light-theme .file-name { color: #0f172a; }
    </style>
@endpush

@push('scripts')
    <script>
    (function () {
        async function loadBackups() {
            const body = document.getElementById('backupBody');
            const meta = document.getElementById('resultMeta');
            try {
                const res = await fetch('/api/backups');
                if (!res.ok) throw new Error('Failed (' + res.status + ')');
                const d = await res.json();
                document.getElementById('policyText').textContent = d.schedule || '';
                document.getElementById('driverText').textContent = 'DB driver: ' + (d.driver || '—') + ' · files in storage/app/private/backups';
                meta.textContent = d.count + ' backup(s) stored';
                if (!d.backups || d.backups.length === 0) {
                    body.innerHTML = '<tr><td colspan="5" class="empty-state">No backups yet. Click “Run Backup Now”.</td></tr>';
                    return;
                }
                body.innerHTML = d.backups.map(b => `
                    <tr>
                        <td class="file-name">${escapeHtml(b.name)}</td>
                        <td><span class="type-pill">${escapeHtml(b.type)}</span></td>
                        <td>${escapeHtml(b.size_human)}</td>
                        <td>${escapeHtml(b.modified)}</td>
                        <td><div class="row-actions">
                            <button class="btn-dl" onclick="downloadBackup('${escapeHtml(b.name)}')">⬇ Download</button>
                            <button class="btn-del" onclick="deleteBackup('${escapeHtml(b.name)}')">Delete</button>
                        </div></td>
                    </tr>`).join('');
            } catch (e) {
                body.innerHTML = '<tr><td colspan="5" class="empty-state">Unable to load backups</td></tr>';
                console.error('Backups load error:', e);
            }
        }

        window.runBackup = async function () {
            const btn = document.getElementById('runBtn');
            btn.disabled = true;
            btn.textContent = '⏳ Running...';
            try {
                const res = await fetch('/api/backups/run', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                });
                const d = await res.json();
                if (!res.ok || !d.success) throw new Error(d.output || 'Backup failed');
                showToast('Backup created!', 'success');
                loadBackups();
            } catch (e) {
                showToast('Backup failed: ' + e.message, 'error');
                console.error(e);
            } finally {
                btn.disabled = false;
                btn.textContent = '▶ Run Backup Now';
            }
        };

        window.downloadBackup = function (name) {
            window.location.href = '/api/backups/download/' + encodeURIComponent(name);
        };

        window.deleteBackup = async function (name) {
            if (!confirm('Delete backup ' + name + '?')) return;
            try {
                const res = await fetch('/api/backups/' + encodeURIComponent(name), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('Delete failed');
                showToast('Backup deleted', 'success');
                loadBackups();
            } catch (e) {
                showToast('Delete failed', 'error');
            }
        };

        function escapeHtml(text) {
            const d = document.createElement('div');
            d.textContent = text || '';
            return d.innerHTML;
        }

        function showToast(msg, type) {
            const t = document.getElementById('toast');
            if (!t) { alert(msg); return; }
            t.textContent = msg;
            t.className = 'toast show ' + (type || 'success');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        loadBackups();
    })();
    </script>
@endpush
