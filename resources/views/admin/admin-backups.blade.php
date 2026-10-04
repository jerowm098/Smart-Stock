@extends('layouts.dashboard-main-frame')

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
                    <div class="policy-info">
                <div class="policy-title">Backup Policy</div>
                <div class="policy-text" id="policyText">Automatic backup daily at 2:00 AM (Manila)</div>
                    </div>
                    <div class="policy-action">
                                            <button class="btn-primary btn-run" id="runBtn" onclick="runBackup()">
                                                <span class="btn-run-label" id="runBtnLabel">▶ Run Backup Now</span>
                                                <span class="btn-run-path">Files are saved in <code>storage/app/private/backups</code></span>
                                            </button>
                                        </div>
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
        <div class="table-wrapper is-loading">
                <table>
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Created</th>
                                                        <th>Actions</th>
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
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 20px;
        }
        .white-form:last-child {
            margin-bottom: 0;
        }
        body.light-theme .white-form {
            background: #ffffff;
            border-color: rgba(15, 23, 42, 0.08);
        }
        /* No divider under the header — the card's own background already
                   separates it from the content below. */
                .section-card-header {
                    margin-bottom: 20px;
                    padding-bottom: 0;
                }
        .section-card-title {
            font-size: 18px;
            font-weight: 700;
            color: #f8fafc;
            margin: 0 0 6px 0;
        }
        .section-card-desc {
            font-size: 13px;
            color: #94a3b8;
            margin: 0;
        }
        body.light-theme .section-card-title { color: #0f172a; }
        body.light-theme .section-card-desc { color: #64748b; }
        .table-wrapper {
            background: transparent;
            border: none;
            border-radius: 0;
            overflow-x: auto;
            overflow-y: visible;
        }
        .page-title { font-size: 22px; font-weight: 700; color: #f8fafc; margin-bottom: 6px; }
        .page-subtitle { color: #64748b; font-size: 14px; margin-bottom: 20px; }
        /* Last child of the card, so no bottom margin — the card's own padding
                   already provides the gap, and a margin here stacked with it to
                   leave a dead band under the policy block. */
                .policy-card {
                    display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
                    background: rgba(96,165,250,0.06); border: 1px solid rgba(96,165,250,0.2);
                    border-radius: 12px; padding: 18px 20px;
                }
        .policy-title { font-size: 14px; font-weight: 700; color: #e2e8f0; margin-bottom: 4px; }
        body.light-theme .policy-title { color: #0f172a; }
        .policy-text { font-size: 13px; color: #cbd5e1; }
        /* The action button carries its own save-path as a small second line,
                   so the destination is visible on the control that creates it. */
                .policy-action { flex-shrink: 0; }
                /* Two-class selector: the shared layout's `.btn-primary` is defined later
                   in the cascade at equal specificity and would otherwise reset
                   the padding that gives the path line room to breathe. */
                .btn-primary.btn-run {
                    display: flex; flex-direction: column; align-items: center; justify-content: center;
                    gap: 3px; padding: 9px 34px; line-height: 1.35; white-space: nowrap;
                    min-width: 260px;
                }
                .btn-run-label { font-size: 13px; font-weight: 600; }
                .btn-run-path {
                    font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 500;
                    color: rgba(255,255,255,0.75);
                }
                .btn-run-path code {
                    font-family: monospace; font-size: 11px; font-weight: 500;
                    color: rgba(255,255,255,0.92);
                }
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
        /* The single full-width "Loading..." row never needs the table's
           min-width, so suppress the horizontal scrollbar until data lands. */
        .table-wrapper.is-loading { overflow: hidden; }
        .table-wrapper.is-loading table { min-width: 0; }
        /* `table-layout: fixed` divides the width evenly across all columns, but the
           actions cell must fit Download + 8px gap + Delete inside its own padding:
                      99 + 8 + 78 + 32 = 217px. At 200px the flex row overflowed the cell and
                      Delete crossed the table's right border. Hand that column an explicit
                      width and let the remaining four share what is left. */
                   table { width: 100%; min-width: 680px; border-collapse: separate; border-spacing: 0; border: 1px solid rgba(255,255,255,0.08); table-layout: fixed; }
                   table th:last-child, table td:last-child { width: 220px; }
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
        /* Text-only pill: no fill, no border — just a slightly darker blue so the
                   type stays readable without a tinted chip behind it. */
                .type-pill {
                    display: inline-block; font-size: 10px; font-weight: 700; text-transform: uppercase;
                    padding: 4px 10px; border-radius: 20px; background: transparent; border: none;
                    color: #3b82f6; white-space: nowrap;
                }
                body.light-theme .type-pill { color: #1d4ed8; }
        .row-actions { display: flex; gap: 8px; }
                /* Row actions, matching the Transactions `.view-btn` treatment: no fill at
                   rest, the border tracks the text color, and hover shades that same
                   color. A single `currentColor` rule covers both the green Download and
                   the red Delete variants. */
                .btn-dl, .btn-del {
                    display: inline-flex; align-items: center; gap: 5px;
                    background: none; border: 1px solid currentColor; border-radius: 7px;
                    padding: 5px 11px; font-size: 12px; font-weight: 600; cursor: pointer;
                    white-space: nowrap; transition: all 0.15s;
                }
                .btn-dl svg, .btn-del svg { width: 13px; height: 13px; flex-shrink: 0; }
                .btn-dl  { color: #22c55e; }
                .btn-del { color: #ef4444; }
                .btn-dl:hover  { background: color-mix(in srgb, currentColor 14%, transparent); color: #4ade80; }
                .btn-del:hover { background: color-mix(in srgb, currentColor 14%, transparent); color: #f87171; }
                body.light-theme .btn-dl  { color: #16a34a; }
                body.light-theme .btn-del { color: #dc2626; }
                body.light-theme .btn-dl:hover  { background: rgba(22,163,74,0.1);  color: #15803d; }
                body.light-theme .btn-del:hover { background: rgba(220,38,38,0.1);  color: #b91c1c; }
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
                        const wrapper = body.closest('.table-wrapper');
            try {
                const res = await fetch('/api/backups');
                if (!res.ok) throw new Error('Failed (' + res.status + ')');
                const d = await res.json();
                document.getElementById('policyText').textContent = d.schedule || '';
                meta.textContent = d.count + ' backup(s) stored';
                                wrapper.classList.remove('is-loading');
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
                            <button class="btn-dl" onclick="downloadBackup('${escapeHtml(b.name)}')" title="Download this backup file"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>Download</button>
                                                        <button class="btn-del" onclick="deleteBackup('${escapeHtml(b.name)}')" title="Permanently delete this backup file"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>Delete</button>
                        </div></td>
                    </tr>`).join('');
            } catch (e) {
                wrapper.classList.remove('is-loading');
                body.innerHTML = '<tr><td colspan="5" class="empty-state">Unable to load backups</td></tr>';
                console.error('Backups load error:', e);
            }
        }

        window.runBackup = async function () {
            const btn = document.getElementById('runBtn');
                    const label = document.getElementById('runBtnLabel');
                    btn.disabled = true;
                    label.textContent = '⏳ Running...';
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
                                label.textContent = '▶ Run Backup Now';
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
