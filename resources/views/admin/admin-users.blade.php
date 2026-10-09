@extends('layouts.dashboard-main-frame')

@section('title', 'User Accounts - Smart-Stock')

@section('content')
    <div class="page-header hero">
        <div class="hero-content">
            <h1 class="page-title">User Accounts</h1>
            <p class="page-subtitle">Create and manage staff accounts</p>
        </div>
    </div>

    <!-- ====== ACCOUNT STATS ====== -->
    <div class="user-stats-grid">
        <div class="user-stat-card">
            <div class="user-stat-header">
                <h3>Total Accounts</h3>
                <span class="user-stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </span>
            </div>
            <div class="user-stat-value blue" id="statTotalUsers">0</div>
            <div class="user-stat-subtext">All registered accounts</div>
        </div>
        <div class="user-stat-card">
            <div class="user-stat-header">
                <h3>Active Accounts</h3>
                <span class="user-stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </span>
            </div>
            <div class="user-stat-value green" id="statActiveUsers">0</div>
            <div class="user-stat-subtext">Accounts with access</div>
        </div>
        <div class="user-stat-card">
            <div class="user-stat-header">
                <h3>Deactivated Accounts</h3>
                <span class="user-stat-icon red">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                </span>
            </div>
            <div class="user-stat-value red" id="statInactiveUsers">0</div>
            <div class="user-stat-subtext">Accounts without access</div>
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
                    <label class="filter-label" for="newFirstName">First Name</label>
                    <div class="valid-wrap has-icon-left">
                        <span class="input-icon-left" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <input type="text" class="field-input" id="newFirstName" placeholder="juan" autocomplete="off" maxlength="50" oninput="this.value = this.value.replace(/[^A-Za-zÑñ ]/g, '').replace(/ {2,}/g, ' '); paintFieldValidity('newFirstName')">
                        <span class="valid-icon" data-valid-for="newFirstName" aria-hidden="true"></span>
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="newLastName">Last Name</label>
                    <div class="valid-wrap has-icon-left">
                        <span class="input-icon-left" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <input type="text" class="field-input" id="newLastName" placeholder="dela cruz" autocomplete="off" maxlength="50" oninput="this.value = this.value.replace(/[^A-Za-zÑñ ]/g, '').replace(/ {2,}/g, ' '); paintFieldValidity('newLastName')">
                        <span class="valid-icon" data-valid-for="newLastName" aria-hidden="true"></span>
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="newUsername">Username</label>
                    <div class="valid-wrap has-icon-left">
                        <span class="input-icon-left" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <input type="text" class="field-input" id="newUsername" placeholder="juan01" autocomplete="off" maxlength="255" oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, ''); paintFieldValidity('newUsername')">
                        <span class="valid-icon" data-valid-for="newUsername" aria-hidden="true"></span>
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="newEmail">Email</label>
                    <div class="valid-wrap has-icon-left">
                        <span class="input-icon-left" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
                        </span>
                        <input type="email" class="field-input" id="newEmail" placeholder="juan01@example.com" autocomplete="off" oninput="paintFieldValidity('newEmail')">
                        <span class="valid-icon" data-valid-for="newEmail" aria-hidden="true"></span>
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="newRoleLabel">Role</label>
                    <div class="role-select" id="roleSelect">
                        <button type="button" class="field-input role-select-btn" id="roleSelectBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="newRoleLabel roleSelectBtnLabel" onclick="toggleRoleSelect(event)">
                            <span id="roleSelectBtnLabel">Staff (POS only)</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="roleSelectList" role="listbox" aria-labelledby="newRoleLabel">
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="cashier" onclick="selectRole('cashier', 'Staff (POS only)')">Staff (POS only)</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="admin" onclick="selectRole('admin', 'Admin')">Admin</button>
                        </div>
                        <input type="hidden" id="newRole" value="cashier">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="newPassword">Password</label>
                    <div class="password-wrap">
                        <input type="password" class="field-input" id="newPassword" placeholder="Min. 6 characters" autocomplete="new-password" oninput="this.classList.remove('input-required'); paintPasswordStrength('newPassword'); paintPasswordMatch('newPassword', 'newPasswordConfirm'); syncConfirmState('newPassword', 'newPasswordConfirm')">
                        <button type="button" class="password-eye" onclick="togglePassword('newPassword', this)" title="Show password" aria-label="Show password"></button>
                    </div>
                    <div class="pw-strength" data-strength-for="newPassword">
                        <div class="pw-strength-bar"><span></span><span></span><span></span></div>
                        <span class="pw-strength-label">Password strength</span>
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" for="newPasswordConfirm">Confirm Password</label>
                    <div class="password-wrap">
                        <input type="password" class="field-input" id="newPasswordConfirm" placeholder="Repeat password" autocomplete="new-password" readonly oninput="paintPasswordMatch('newPassword', 'newPasswordConfirm')" onfocus="guardConfirmPassword('newPassword', 'newPasswordConfirm')" onclick="guardConfirmPassword('newPassword', 'newPasswordConfirm')">
                        <button type="button" class="password-eye" onclick="togglePassword('newPasswordConfirm', this)" title="Show password" aria-label="Show password"></button>
                    </div>
                    <div class="pw-match" data-match-for="newPasswordConfirm"><span class="pw-match-label"></span></div>
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

    <!-- ====== SEARCH & FILTER ====== -->
    <div class="section-card">
        <div class="section-card-header">
            <h2 class="section-card-title">Search &amp; Filter</h2>
            <p class="section-card-desc">Find accounts by name, username, email, role, or status</p>
        </div>
        <div class="filter-bar">
            <div class="user-filter-inner">
                <div class="filter-group user-filter-search">
                    <label class="filter-label" for="userSearch">Search</label>
                    <div class="valid-wrap has-icon-left">
                        <span class="input-icon-left" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </span>
                        <input type="text" class="field-input" id="userSearch" placeholder="Name, username, or email..." autocomplete="off">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="userFilterRoleLabel">Role</label>
                    <div class="role-select" id="userFilterRoleSelect">
                        <button type="button" class="field-input role-select-btn" id="userFilterRoleBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="userFilterRoleLabel userFilterRoleBtnLabel" onclick="toggleFilterSelect(event, 'userFilterRoleSelect')">
                            <span id="userFilterRoleBtnLabel">All roles</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="userFilterRoleList" role="listbox" aria-labelledby="userFilterRoleLabel">
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="" onclick="selectFilterOption('userFilterRole', '', 'All roles')">All roles</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="admin" onclick="selectFilterOption('userFilterRole', 'admin', 'Admin')">Admin</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="cashier" onclick="selectFilterOption('userFilterRole', 'cashier', 'Staff')">Staff</button>
                        </div>
                        <input type="hidden" id="userFilterRole" value="">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="userFilterStatusLabel">Status</label>
                    <div class="role-select" id="userFilterStatusSelect">
                        <button type="button" class="field-input role-select-btn" id="userFilterStatusBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="userFilterStatusLabel userFilterStatusBtnLabel" onclick="toggleFilterSelect(event, 'userFilterStatusSelect')">
                            <span id="userFilterStatusBtnLabel">All statuses</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="userFilterStatusList" role="listbox" aria-labelledby="userFilterStatusLabel">
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="" onclick="selectFilterOption('userFilterStatus', '', 'All statuses')">All statuses</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="active" onclick="selectFilterOption('userFilterStatus', 'active', 'Active')">Active</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="inactive" onclick="selectFilterOption('userFilterStatus', 'inactive', 'Deactivated')">Deactivated</button>
                        </div>
                        <input type="hidden" id="userFilterStatus" value="">
                    </div>
                </div>
                <div class="filter-group">
                    <label class="filter-label" id="userFilterRowsLabel">Rows</label>
                    <div class="role-select" id="userFilterRowsSelect">
                        <button type="button" class="field-input role-select-btn" id="userFilterRowsBtn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="userFilterRowsLabel userFilterRowsBtnLabel" onclick="toggleFilterSelect(event, 'userFilterRowsSelect')">
                            <span id="userFilterRowsBtnLabel">15</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="role-select-list" id="userFilterRowsList" role="listbox" aria-labelledby="userFilterRowsLabel">
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="10" onclick="selectFilterOption('userFilterRows', '10', '10')">10</button>
                            <button type="button" class="role-select-option selected" role="option" aria-selected="true" data-value="15" onclick="selectFilterOption('userFilterRows', '15', '15')">15</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="25" onclick="selectFilterOption('userFilterRows', '25', '25')">25</button>
                            <button type="button" class="role-select-option" role="option" aria-selected="false" data-value="50" onclick="selectFilterOption('userFilterRows', '50', '50')">50</button>
                        </div>
                        <input type="hidden" id="userFilterRows" value="15">
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn-ghost" onclick="resetUserFilters()">Reset</button>
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
                <div class="section-card-actions">
                    <span class="result-meta" id="userResultMeta">Loading...</span>
                    <div class="view-toggle">
                        <button class="view-toggle-btn active" id="tableViewBtn" onclick="switchView('table')" title="Table View">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line><line x1="9" y1="3" x2="9" y2="21"></line></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-wrapper" id="userTableView">
            <table class="data-table">
                <thead>
                    <tr class="user-header-row">
                        <th colspan="7" class="user-header-cell">
                            <div class="user-header-card" aria-hidden="false">
                                <div class="user-header-field">Name</div>
                                <div class="user-header-field">Username</div>
                                <div class="user-header-field">Email</div>
                                <div class="user-header-field">Role</div>
                                <div class="user-header-field">Status</div>
                                <div class="user-header-field">Created</div>
                                <div class="user-header-field">Actions</div>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr><td colspan="7" class="empty-state">Loading accounts...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="pager" id="userPager" style="display:none;"></div>
    </div>

    <!-- ====== CUSTOM INFO MODAL (like home login info popup) ====== -->
    <div class="user-info-overlay" id="userInfoOverlay" role="alertdialog" aria-modal="true" aria-labelledby="userInfoTitle" aria-describedby="userInfoText"
         onclick="if (event.target === this) closeUserInfo();">
        <div class="user-info-modal">
            <div class="user-info-icon error" id="userInfoIcon" aria-hidden="true"></div>
            <div class="user-info-title" id="userInfoTitle">Notice</div>
            <div class="user-info-text" id="userInfoText"></div>
            <button type="button" class="user-info-btn" onclick="closeUserInfo()">OK</button>
        </div>
    </div>

    <!-- ====== CUSTOM CONFIRM MODAL ====== -->
    <div class="user-info-overlay" id="userConfirmOverlay" role="alertdialog" aria-modal="true" aria-labelledby="userConfirmTitle" aria-describedby="userConfirmText"
         onclick="if (event.target === this) cancelUserConfirm();">
        <div class="user-info-modal">
            <div class="user-info-icon info" id="userConfirmIcon" aria-hidden="true"></div>
            <div class="user-info-title" id="userConfirmTitle">Please confirm</div>
            <div class="user-info-text" id="userConfirmText"></div>
            <div class="user-confirm-actions">
                <button type="button" class="user-confirm-btn ghost" onclick="cancelUserConfirm()">Cancel</button>
                <button type="button" class="user-confirm-btn danger" id="userConfirmOkBtn" onclick="acceptUserConfirm()">Confirm</button>
            </div>
        </div>
    </div>

    <!-- ====== CUSTOM RESET PASSWORD MODAL ====== -->
    <div class="user-info-overlay" id="userPasswordOverlay" role="dialog" aria-modal="true" aria-labelledby="userPasswordTitle"
         onclick="if (event.target === this) cancelUserPassword();">
        <div class="user-info-modal user-password-modal">
            <div class="user-info-icon info" id="userPasswordIcon" aria-hidden="true"></div>
            <div class="user-info-title" id="userPasswordTitle">Reset Password</div>
            <div class="user-info-text" id="userPasswordText"></div>
            <div class="user-password-fields">
                <div class="user-password-group">
                    <label for="userNewPassword">New password</label>
                    <div class="password-wrap">
                        <input type="password" id="userNewPassword" placeholder="Min. 6 characters" autocomplete="new-password" oninput="this.classList.remove('input-required'); paintPasswordStrength('userNewPassword'); paintPasswordMatch('userNewPassword', 'userConfirmPassword'); syncConfirmState('userNewPassword', 'userConfirmPassword')">
                        <button type="button" class="password-eye" onclick="togglePassword('userNewPassword', this)" title="Show password" aria-label="Show password"></button>
                    </div>
                    <div class="pw-strength" data-strength-for="userNewPassword">
                        <div class="pw-strength-bar"><span></span><span></span><span></span></div>
                        <span class="pw-strength-label">Password strength</span>
                    </div>
                </div>
                <div class="user-password-group">
                    <label for="userConfirmPassword">Confirm password</label>
                    <div class="password-wrap">
                        <input type="password" id="userConfirmPassword" placeholder="Repeat password" autocomplete="new-password" readonly oninput="paintPasswordMatch('userNewPassword', 'userConfirmPassword')" onfocus="guardConfirmPassword('userNewPassword', 'userConfirmPassword')" onclick="guardConfirmPassword('userNewPassword', 'userConfirmPassword')">
                        <button type="button" class="password-eye" onclick="togglePassword('userConfirmPassword', this)" title="Show password" aria-label="Show password"></button>
                    </div>
                    <div class="pw-match" data-match-for="userConfirmPassword"><span class="pw-match-label"></span></div>
                </div>
                <span class="user-password-error" id="userPasswordError"></span>
            </div>
            <div class="user-confirm-actions">
                <button type="button" class="user-confirm-btn ghost" onclick="cancelUserPassword()">Cancel</button>
                <button type="button" class="user-confirm-btn primary" onclick="acceptUserPassword()">Save Password</button>
            </div>
        </div>
    </div>

    <style>
        .page-header.hero {
            --hero-card-bg: rgba(255,255,255,0.025);
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
            overflow: visible;
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
        .section-card-desc { font-size: 13px; color: rgba(226,232,240,0.88); margin: 0; }
        body.light-theme .section-card-desc { color: #475569; }

        /* ACCOUNT STAT CARDS */
        .user-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        .user-stat-card {
            background: rgba(255,255,255,0.025);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        body.light-theme .user-stat-card {
            background: #ffffff;
            border-color: rgba(15,23,42,0.08);
        }
        .user-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }
        .user-stat-header h3 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #ffffff;
            margin: 0;
        }
        .user-stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .user-stat-icon svg { width: 20px; height: 20px; display: block; }
        .user-stat-icon.blue   { background: rgba(96,165,250,0.15);  color: #60a5fa; }
        .user-stat-icon.green  { background: rgba(74,222,128,0.15);  color: #4ade80; }
        .user-stat-icon.red    { background: rgba(248,113,113,0.15); color: #f87171; }
        .user-stat-value {
            font-size: 26px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            color: #f8fafc;
        }
        .user-stat-value.blue   { color: #60a5fa; }
        .user-stat-value.green  { color: #4ade80; }
        .user-stat-value.red    { color: #f87171; }
        .user-stat-subtext { font-size: 12px; color: #ffffff; }
        body.light-theme .user-stat-header h3 { color: #64748b; }
        body.light-theme .user-stat-value { color: #0f172a; }
        body.light-theme .user-stat-value.blue   { color: #2563eb; }
        body.light-theme .user-stat-value.green  { color: #16a34a; }
        body.light-theme .user-stat-value.red    { color: #dc2626; }
        body.light-theme .user-stat-subtext { color: #94a3b8; }
        body.light-theme .user-stat-icon.blue   { background: rgba(37,99,235,0.1); }
        body.light-theme .user-stat-icon.green  { background: rgba(22,163,74,0.1); }
        body.light-theme .user-stat-icon.red    { background: rgba(220,38,38,0.1); }

        /* FILTER BAR — same surface as table rows, not blue-tinted */
        .filter-bar {
            background: rgba(255,255,255,0.045);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 14px 16px;
            margin: 0 16px 16px 16px;
        }
        body.light-theme .filter-bar { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.08); }
        /* Field grid: 6 columns so row 1 holds First + Last + Username
           (span 2 each), row 2 holds Email + Role (span 3 each),
           row 3 holds Password + Confirm (span 3 each). */
        .filter-bar-inner {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 14px 16px;
            align-items: end;
            /* The shared layout makes `.filter-bar` a flex container, so this
               grid would shrink-to-fit its content and leave the right half
               of the bar empty. Claim the full bar width. */
            width: 100%;
            min-width: 0;
        }
        .filter-bar-inner > .filter-group:nth-child(1),
        .filter-bar-inner > .filter-group:nth-child(2),
        .filter-bar-inner > .filter-group:nth-child(3) { grid-column: span 2; }
        .filter-bar-inner > .filter-group:nth-child(4),
        .filter-bar-inner > .filter-group:nth-child(5),
        .filter-bar-inner > .filter-group:nth-child(6),
        .filter-bar-inner > .filter-group:nth-child(7) { grid-column: span 3; }
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
        .filter-group .filter-label { font-size: 11px; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.5px; }
        body.light-theme .filter-group .filter-label { color: #334155; }

        /* SEARCH & FILTER — row 1 is the search field, row 2 holds the
           Role / Status / Rows selects plus the Reset action. */
        .user-filter-inner {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px 16px;
            align-items: end;
            width: 100%;
            min-width: 0;
        }
        .user-filter-inner > .user-filter-search { grid-column: 1 / -1; }
        .user-filter-inner > .filter-actions {
            grid-column: auto;
            margin-left: 0;
            justify-content: flex-end;
        }
        .user-filter-inner .field-input { cursor: pointer; }
        .user-filter-inner input.field-input { cursor: text; }
        .user-filter-inner .filter-actions .btn-ghost {
            background: transparent; border: 1px solid currentColor; color: #cbd5e1;
            padding: 0 18px; height: 36px; font-weight: 600; font-family: 'Inter', sans-serif;
            transition: all 0.15s;
        }
        .user-filter-inner .filter-actions .btn-ghost:hover {
            border-color: currentColor; background: rgba(203,213,225,0.12); color: #e2e8f0;
        }
        body.light-theme .user-filter-inner .filter-actions .btn-ghost { color: #334155; }
        body.light-theme .user-filter-inner .filter-actions .btn-ghost:hover {
            border-color: currentColor; background: rgba(30,41,59,0.08); color: #0f172a;
        }

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
        .field-input:focus { border-color: #3b82f6; }
        .field-input::placeholder { color: #64748b; }
        body.light-theme .field-input { background: #ffffff; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        body.light-theme .field-input::placeholder { color: #94a3b8; }

        /* CUSTOM ROLE DROPDOWN — width-locked to its field so the list can
           never overflow the card like the native select popup did. */
        .role-select { position: relative; width: 100%; min-width: 0; }
        .role-select-btn {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            width: 100%; text-align: left; font-weight: 400;
        }
        .role-select-btn > span {
            min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .role-select-btn svg { flex-shrink: 0; color: #94a3b8; transition: transform 0.15s; }
        .role-select.open .role-select-btn svg { transform: rotate(180deg); }
        .role-select-list {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 60;
            display: none; flex-direction: column; padding: 4px;
            background: #1e293b; border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px; box-shadow: 0 12px 28px rgba(2,6,23,0.45);
            max-height: 180px; overflow-y: auto;
        }
        .role-select.open .role-select-list { display: flex; }
        /* The last row of the filter grid sits near the card edge, so flip the
           list upward when there is not enough room below the trigger. */
        .role-select.drop-up .role-select-list { top: auto; bottom: calc(100% + 6px); }
        body.light-theme .role-select-list { background: #ffffff; border-color: rgba(15,23,42,0.12); box-shadow: 0 12px 28px rgba(15,23,42,0.15); }
        .role-select-option {
            border: none; background: transparent; text-align: left; cursor: pointer;
            font-family: 'Inter', sans-serif; font-size: 13px; color: #e2e8f0;
            padding: 9px 10px; border-radius: 6px; width: 100%;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .role-select-option:hover { background: rgba(59,130,246,0.15); }
        .role-select-option.selected { color: #93c5fd; font-weight: 600; }
        .role-select-option.selected:hover { background: rgba(59,130,246,0.15); }
        body.light-theme .role-select-option { color: #0f172a; }
        body.light-theme .role-select-option:hover { background: rgba(37,99,235,0.08); }
        body.light-theme .role-select-option.selected { color: #1d4ed8; }
        body.light-theme .role-select-option.selected:hover { background: rgba(37,99,235,0.08); }

        /* BUTTONS & TOGGLE */
        .btn-add {
            background: #2563eb; color: #fff; border: none; border-radius: 8px;
            padding: 0 18px; height: 36px; font-size: 13px; font-weight: 600; cursor: pointer;
            font-family: 'Inter', sans-serif; display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.15s; white-space: nowrap;
            justify-content: center;
        }
        .btn-add:hover { opacity: 0.9; transform: translateY(-1px); }
        /* Keep 2-column feel on medium screens: every field spans full half. */
        @media (max-width: 900px) {
            .user-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .user-filter-inner { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .filter-bar-inner { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .filter-bar-inner > .filter-group:nth-child(1),
            .filter-bar-inner > .filter-group:nth-child(2),
            .filter-bar-inner > .filter-group:nth-child(3),
            .filter-bar-inner > .filter-group:nth-child(4),
            .filter-bar-inner > .filter-group:nth-child(5),
            .filter-bar-inner > .filter-group:nth-child(6),
            .filter-bar-inner > .filter-group:nth-child(7) { grid-column: span 1; }
        }
        @media (max-width: 620px) {
            .user-stats-grid { grid-template-columns: minmax(0, 1fr); }
            .user-filter-inner { grid-template-columns: minmax(0, 1fr); }
            .filter-bar-inner { grid-template-columns: minmax(0, 1fr); }
            .filter-bar-inner > .filter-group:nth-child(1),
            .filter-bar-inner > .filter-group:nth-child(2),
            .filter-bar-inner > .filter-group:nth-child(3),
            .filter-bar-inner > .filter-group:nth-child(4),
            .filter-bar-inner > .filter-group:nth-child(5),
            .filter-bar-inner > .filter-group:nth-child(6),
            .filter-bar-inner > .filter-group:nth-child(7) { grid-column: span 1; }
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
        body.light-theme .view-toggle-btn.active { background: rgba(15,23,42,0.08); color: #0f172a; }

        .section-card-actions { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
        .result-meta { font-size: 12px; color: #64748b; white-space: nowrap; }
        body.light-theme .result-meta { color: #475569; }
        .pager {
            display: flex; align-items: center; justify-content: center; gap: 14px;
            margin: 0 16px 16px 16px;
        }
        .pager-label { font-size: 13px; color: #94a3b8; }
        body.light-theme .pager-label { color: #475569; }
        .pager .btn-ghost {
            background: transparent; border: 1px solid currentColor; color: #cbd5e1;
            padding: 7px 14px; font-weight: 600; font-family: 'Inter', sans-serif;
            transition: all 0.15s;
        }
        .pager .btn-ghost:hover:not(:disabled) {
            border-color: currentColor; background: rgba(203,213,225,0.12); color: #e2e8f0;
        }
        .pager .btn-ghost:disabled { opacity: 0.4; cursor: not-allowed; }
        body.light-theme .pager .btn-ghost { color: #334155; }
        body.light-theme .pager .btn-ghost:hover:not(:disabled) {
            border-color: currentColor; background: rgba(30,41,59,0.08); color: #0f172a;
        }

        /* TABLE */
        .section-card .table-wrapper {
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;
            margin: 0 16px 16px 16px; width: calc(100% - 32px); overflow-x: auto; overflow-y: visible;
        }
        body.light-theme .section-card .table-wrapper { border-color: rgba(15,23,42,0.12); background: #ffffff; }
        .section-card .table-wrapper .data-table {
            width: 100%; min-width: 1080px;
            border-collapse: separate; border-spacing: 0 4px;
            border: none;
            table-layout: fixed;
        }
        .section-card .table-wrapper .data-table th,
        .section-card .table-wrapper .data-table td { border: none; }
        .section-card .table-wrapper .data-table th:nth-child(1), .section-card .table-wrapper .data-table td:nth-child(1) { width: 13%; }
        .section-card .table-wrapper .data-table th:nth-child(2), .section-card .table-wrapper .data-table td:nth-child(2) { width: 10%; }
        .section-card .table-wrapper .data-table th:nth-child(3), .section-card .table-wrapper .data-table td:nth-child(3) { width: 18%; }
        .section-card .table-wrapper .data-table th:nth-child(4), .section-card .table-wrapper .data-table td:nth-child(4) { width: 8%; }
        .section-card .table-wrapper .data-table th:nth-child(5), .section-card .table-wrapper .data-table td:nth-child(5) { width: 9%; }
        .section-card .table-wrapper .data-table th:nth-child(6), .section-card .table-wrapper .data-table td:nth-child(6) { width: 10%; white-space: nowrap; }
        .section-card .table-wrapper .data-table th:nth-child(7), .section-card .table-wrapper .data-table td:nth-child(7) { width: 32%; }
        .section-card .table-wrapper .data-table thead th {
            background: transparent; padding: 0; text-align: center;
            font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; white-space: normal;
            border: none; line-height: 1.4;
        }
        .section-card .table-wrapper .data-table thead th:first-child { border-top-left-radius: 9px; }
        .section-card .table-wrapper .data-table thead th:last-child  { border-top-right-radius: 9px; text-align: center; }
        /* Header as one card: single <th colspan="7"> so the element picker
           grabs the whole header as one unit like the rows. */
        .section-card .table-wrapper .data-table thead tr.user-header-row > th.user-header-cell {
            padding: 0;
            background: transparent;
            border-radius: 9px 9px 0 0;
        }
        .user-header-card {
            display: grid;
            grid-template-columns: 13% 10% 18% 8% 9% 10% 32%;
            align-items: center;
            width: 100%;
            box-sizing: border-box;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            border-radius: 9px 9px 0 0;
        }
        body.light-theme .user-header-card { background: rgba(100,116,139,0.16); border-color: rgba(15,23,42,0.08); border-bottom-color: rgba(15,23,42,0.08); }
        .user-header-field {
            min-width: 0;
            box-sizing: border-box;
            padding: 14px 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.4;
        }
        body.light-theme .user-header-field { color: #1e293b; }
        .section-card .table-wrapper .data-table tbody tr,
        .section-card .table-wrapper .data-table tbody tr:hover {
            background: rgba(255,255,255,0.045); border: none; border-radius: 8px; transition: none;
        }
        .section-card .table-wrapper .data-table tbody td {
            padding: 14px 16px; font-size: 13px; color: #cbd5e1; border: none; vertical-align: middle;
        }
        .section-card .table-wrapper .data-table tbody td:last-child { text-align: right; }
        /* One unified card per row: radius lives on the end cells because
           border-radius on <tr> itself is ignored by browsers. */
        .section-card .table-wrapper .data-table tbody td:first-child {
            border-top-left-radius: 8px; border-bottom-left-radius: 8px;
        }
        .section-card .table-wrapper .data-table tbody td:last-child {
            border-top-right-radius: 8px; border-bottom-right-radius: 8px;
            text-align: center;
        }
        .section-card .table-wrapper .data-table tbody td.empty-state {
            border-radius: 8px;
        }
        body.light-theme .section-card .table-wrapper .data-table tbody tr,
        body.light-theme .section-card .table-wrapper .data-table tbody tr:hover { background: rgba(15,23,42,0.04); }
        body.light-theme .section-card .table-wrapper .data-table tbody td { color: #334155; }

        /* Unified row card: one selectable unit per row (name → actions).
           Each <tr> holds a single <td colspan="7"> so the element picker
           grabs the whole row as one card instead of separate cells. */
        .section-card .table-wrapper .data-table tbody tr.user-card-row,
        .section-card .table-wrapper .data-table tbody tr.user-card-row:hover {
            background: transparent;
        }
        .section-card .table-wrapper .data-table tbody tr.user-card-row > td.user-card-cell {
            padding: 0;
            background: transparent;
            border-radius: 8px;
        }
        .user-row-card {
            display: grid;
            grid-template-columns: 13% 10% 18% 8% 9% 10% 32%;
            align-items: center;
            width: 100%;
            box-sizing: border-box;
            background: transparent;
            border-radius: 8px;
            font-size: 13px;
            color: #cbd5e1;
        }
        body.light-theme .user-row-card { color: #334155; background: transparent; }
        .section-card .table-wrapper .data-table tbody tr.user-card-row:hover .user-row-card {
            background: rgba(255,255,255,0.03);
        }
        body.light-theme .section-card .table-wrapper .data-table tbody tr.user-card-row:hover .user-row-card {
            background: rgba(15,23,42,0.02);
        }
        .user-row-field {
            min-width: 0;
            box-sizing: border-box;
            padding: 14px 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: center;
        }
        .user-row-field.actions-field {
            overflow: visible;
            white-space: normal;
            display: flex;
            justify-content: center;
        }
        /* Badges must never be ellipsized — the ... in the screenshot is the
           parent's text-overflow cutting the badge. Keep full badge visible. */
        .user-row-field:has(.role-badge),
        .user-row-field:has(.status-badge) {
            overflow: visible;
            text-overflow: clip;
            white-space: normal;
        }
        .user-row-card .actions-inner { margin: 0; }
        .user-row-card .btn-mini + .btn-mini { margin-left: 0; }
        /* Search match highlight — bold + tinted, readable in both themes. */
        .search-hit {
            font-weight: 700;
            color: #ffffff;
            background: rgba(59,130,246,0.35);
            border-radius: 3px;
            padding: 0 2px;
        }
        body.light-theme .search-hit {
            color: #1e3a8a;
            background: rgba(37,99,235,0.18);
        }
        /* Deactivated rows share the same card background; only text fades. */
        .section-card .table-wrapper .data-table tbody tr.row-inactive .user-row-card { background: transparent; border: none; filter: saturate(.7); }
        body.light-theme .section-card .table-wrapper .data-table tbody tr.row-inactive .user-row-card { background: transparent; border: none; }
        .section-card .table-wrapper .data-table tbody tr.row-inactive .user-row-field:not(.actions-field) { opacity: .35; }

        /* LOADING STATE — same horizontal scroll as loaded rows; only the
           body text is swapped for the placeholder. */
        #userTableView.loading tbody td:not(.empty-state) { color: transparent; }
        #userTableView.loading tbody td.empty-state { color: #64748b; }
        #userTableView.loading { overflow-x: auto; overflow-y: hidden; }
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
            background: transparent;
        }
        body.light-theme .section-card .table-wrapper .data-table tbody td.empty-state,
        body.light-theme .section-card .table-wrapper .data-table tbody td.empty-state:last-child {
            background: transparent;
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
        .section-card .table-wrapper .data-table tbody tr.row-inactive > td:not(.user-card-cell) { opacity:.55; }

        /* ROW ACTION BUTTONS */
        .btn-mini { padding:5px 9px; font-size:11.5px; border-radius:7px; border:1px solid rgba(148,163,184,.3); background:transparent; color:inherit; cursor:pointer; font-family: 'Inter', sans-serif; transition: all 0.15s; white-space: nowrap; }
        .btn-mini:hover { background:rgba(148,163,184,.12); }
        .btn-mini.danger { border-color:rgba(239,68,68,.4); color:#f87171; }
        .btn-mini.danger:hover { background:rgba(239,68,68,.12); }
        .btn-mini.success { border-color:rgba(34,197,94,.45); color:#4ade80; }
        .btn-mini.success:hover { background:rgba(34,197,94,.12); }
        body.light-theme .btn-mini.success { border-color:rgba(22,163,74,.45); color:#16a34a; }
        body.light-theme .btn-mini.success:hover { background:rgba(22,163,74,.10); }
        .btn-mini:disabled { opacity: .35; cursor: not-allowed; }
        .btn-mini:disabled:hover { background: transparent; }
        .btn-mini + .btn-mini { margin-left:6px; }
        .actions-cell { text-align: center; white-space: nowrap; }
        .actions-inner { display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: nowrap; }

        /* CUSTOM INFO / CONFIRM / PASSWORD MODALS (same look as login info popup) */
        .user-info-overlay {
            position: fixed; inset: 0; z-index: 500;
            background: rgba(2,6,23,0.65); backdrop-filter: blur(3px);
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .user-info-overlay.active { display: flex; }
        .user-info-modal {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.10);
            border-radius: 14px; width: 100%; max-width: 380px; padding: 24px;
            text-align: center; animation: userInfoPop 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }
        body.light-theme .user-info-modal { background: #ffffff; border-color: rgba(15,23,42,0.10); }
        @keyframes userInfoPop { from { opacity: 0; transform: scale(0.92) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .user-info-icon {
            width: 48px; height: 48px; border-radius: 50%; margin: 0 auto 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .user-info-icon.error { background: rgba(239,68,68,0.12); color: #f87171; }
        .user-info-icon.success { background: rgba(34,197,94,0.12); color: #4ade80; }
        .user-info-icon.info { background: rgba(59,130,246,0.12); color: #60a5fa; }
        body.light-theme .user-info-icon.error { background: rgba(220,38,38,0.10); color: #dc2626; }
        body.light-theme .user-info-icon.success { background: rgba(22,163,74,0.10); color: #16a34a; }
        body.light-theme .user-info-icon.info { background: rgba(37,99,235,0.10); color: #2563eb; }
        .user-info-title { font-size: 16px; font-weight: 700; color: #f8fafc; margin-bottom: 8px; }
        body.light-theme .user-info-title { color: #0f172a; }
        .user-info-text { font-size: 13px; color: #94a3b8; line-height: 1.6; margin-bottom: 18px; white-space: pre-line; }
        body.light-theme .user-info-text { color: #64748b; }
        .user-info-btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 120px; padding: 10px 20px; border-radius: 9px; border: none;
            background: #2563eb; color: #fff; font-size: 14px; font-weight: 600;
            font-family: 'Inter', sans-serif; cursor: pointer;
        }
        .user-info-btn:hover { opacity: 0.9; }
        .user-confirm-actions { display: flex; gap: 10px; justify-content: center; }
        .user-confirm-btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 110px; padding: 10px 18px; border-radius: 9px;
            font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif;
            cursor: pointer; border: 1px solid transparent;
        }
        .user-confirm-btn.ghost { background: transparent; color: #94a3b8; border-color: rgba(255,255,255,0.15); }
        .user-confirm-btn.ghost:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
        .user-confirm-btn.danger { background: #dc2626; color: #fff; border: none; }
        .user-confirm-btn.danger:hover { opacity: 0.9; }
        .user-confirm-btn.primary { background: #2563eb; color: #fff; border: none; }
        .user-confirm-btn.primary:hover { opacity: 0.9; }
        body.light-theme .user-confirm-btn.ghost { color: #64748b; border-color: rgba(15,23,42,0.15); }
        body.light-theme .user-confirm-btn.ghost:hover { background: rgba(15,23,42,0.05); color: #0f172a; }
        .user-password-modal { text-align: left; }
        .user-password-modal .user-info-icon,
        .user-password-modal .user-info-title,
        .user-password-modal .user-info-text { text-align: center; }
        .user-password-fields { display: flex; flex-direction: column; gap: 10px; margin-bottom: 6px; }
        .user-password-group { display: flex; flex-direction: column; gap: 4px; text-align: left; }
        .user-password-group label { font-size: 11px; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.5px; }
        body.light-theme .user-password-group label { color: #334155; }
        .user-password-group input {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px; padding: 0 12px; height: 36px; color: #f8fafc;
            font-size: 13px; font-family: 'Inter', sans-serif; outline: none; width: 100%;
        }
        .user-password-group input:focus { border-color: #3b82f6; }
        body.light-theme .user-password-group input { background: #f8fafc; border-color: rgba(15,23,42,0.14); color: #0f172a; }
        .user-password-error { display: block; min-height: 16px; font-size: 12px; color: #f87171; text-align: center; margin-bottom: 10px; }
        body.light-theme .user-password-error { color: #dc2626; }
        .user-password-modal .user-confirm-actions { margin-top: 4px; }

        /* PASSWORD EYE TOGGLE */
        .password-wrap { position: relative; width: 100%; min-width: 0; }
        .password-wrap .field-input,
        .password-wrap input { width: 100%; padding-right: 38px; }
        .password-eye {
            position: absolute; top: 50%; right: 6px; transform: translateY(-50%);
            width: 28px; height: 28px; border-radius: 7px; border: none;
            background: transparent; color: #64748b; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0;
        }
        .password-eye:hover { background: rgba(148,163,184,0.15); color: #e2e8f0; }
        body.light-theme .password-eye { color: #94a3b8; }
        body.light-theme .password-eye:hover { background: rgba(15,23,42,0.06); color: #0f172a; }
        .password-eye svg { width: 16px; height: 16px; display: block; }

        /* PASSWORD STRENGTH (simple: weak 1-2, medium 3-5, strong 6+) */
        .pw-strength { display: flex; align-items: center; gap: 8px; margin-top: 6px; height: 14px; overflow: hidden; }
        .pw-strength-bar { display: flex; gap: 4px; flex: 1; }
        .pw-strength-bar span { height: 4px; flex: 1; border-radius: 2px; background: rgba(148,163,184,0.25); }
        body.light-theme .pw-strength-bar span { background: rgba(15,23,42,0.12); }
        .pw-strength-label { font-size: 11px; font-weight: 600; min-width: 48px; text-align: right; color: #64748b; line-height: 14px; white-space: nowrap; }
        .pw-strength[data-level="weak"] .pw-strength-bar span:nth-child(1) { background: #ef4444; }
        .pw-strength[data-level="weak"] .pw-strength-label { color: #ef4444; }
        .pw-strength[data-level="medium"] .pw-strength-bar span:nth-child(1),
        .pw-strength[data-level="medium"] .pw-strength-bar span:nth-child(2) { background: #f59e0b; }
        .pw-strength[data-level="medium"] .pw-strength-label { color: #f59e0b; }
        .pw-strength[data-level="strong"] .pw-strength-bar span { background: #22c55e; }
        .pw-strength[data-level="strong"] .pw-strength-label { color: #22c55e; }

        /* PASSWORD MATCH — fixed height so showing the text never pushes the form down */
        .pw-match { margin-top: 6px; height: 14px; overflow: hidden; }
        .pw-match-label { display: block; font-size: 11px; font-weight: 600; line-height: 14px; white-space: nowrap; }
        .pw-match[data-state="match"] .pw-match-label { color: #22c55e; }
        .pw-match[data-state="mismatch"] .pw-match-label { color: #ef4444; }

        /* REAL-TIME VALID ICON (check / X inside input, right side) */
        .valid-wrap { position: relative; width: 100%; min-width: 0; }
        .valid-wrap .field-input { width: 100%; padding-right: 34px; }
        .valid-icon {
            position: absolute; top: 50%; right: 10px; transform: translateY(-50%);
            width: 16px; height: 16px; display: none;
            align-items: center; justify-content: center; pointer-events: none;
        }
        .valid-icon.show-valid { display: inline-flex; color: #22c55e; }
        .valid-icon.show-invalid { display: inline-flex; color: #ef4444; }
        .valid-icon svg { width: 16px; height: 16px; display: block; }

        /* LEADING INPUT ICON (left side inside the field) */
        .input-icon-left {
            position: absolute; top: 50%; left: 12px; transform: translateY(-50%);
            width: 16px; height: 16px; display: inline-flex;
            align-items: center; justify-content: center;
            color: #64748b; pointer-events: none;
        }
        .input-icon-left svg { width: 16px; height: 16px; display: block; }
        .valid-wrap.has-icon-left .field-input { padding-left: 36px; }
        body.light-theme .input-icon-left { color: #94a3b8; }

        /* CONFIRM GUARD — require password first */
        .field-input.input-required,
        .user-password-group input.input-required { border-color: #ef4444 !important; box-shadow: 0 0 0 3px rgba(239,68,68,0.15); }
        .password-wrap input[readonly] { cursor: not-allowed; opacity: 0.7; }

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
    let USER_PAGE = 1;
    let _pendingReload = false;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    /* Wraps every occurrence of the current search term in <mark> so the
       matched text stands out. Matching runs on the raw string and each
       slice is escaped separately, so a term like "amp" can never split an
       HTML entity and user input can never inject markup. */
    function highlightMatch(value) {
        const term = (document.getElementById('userSearch')?.value || '').trim();
        const text = String(value ?? '');
        if (!term) return escapeHtml(text);

        const re = new RegExp(term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
        let out = '';
        let last = 0;
        let m;
        while ((m = re.exec(text)) !== null) {
            if (m[0] === '') { re.lastIndex++; continue; }
            out += escapeHtml(text.slice(last, m.index))
                 + `<mark class="search-hit">${escapeHtml(m[0])}</mark>`;
            last = m.index + m[0].length;
        }
        return out + escapeHtml(text.slice(last));
    }

    const EYE_OPEN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    const EYE_CLOSED = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

    function paintEyeButtons(root = document) {
        root.querySelectorAll('.password-eye').forEach((btn) => {
            const input = btn.parentElement?.querySelector('input');
            const showing = input && input.type === 'text';
            btn.innerHTML = showing ? EYE_CLOSED : EYE_OPEN;
            btn.title = showing ? 'Hide password' : 'Show password';
            btn.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
        });
    }

    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        paintEyeButtons(btn ? btn.parentElement?.parentElement || document : document);
        input.focus();
    }

    document.addEventListener('DOMContentLoaded', () => {
        paintEyeButtons();
        syncConfirmState('newPassword', 'newPasswordConfirm');
        syncConfirmState('userNewPassword', 'userConfirmPassword');
    });

    function toggleRoleSelect(e) {
        if (e) e.stopPropagation();
        const wrap = document.getElementById('roleSelect');
        const btn = document.getElementById('roleSelectBtn');
        if (!wrap || !btn) return;
        const open = wrap.classList.toggle('open');
        btn.setAttribute('aria-expanded', String(open));
    }

    /* Generic dropdown used by the Search & Filter selects. The native
       <select> popup is OS-rendered and ignores our theme, so each filter
       uses a button + listbox pair backed by a hidden input. */
    function toggleFilterSelect(e, wrapId) {
        if (e) e.stopPropagation();
        const wrap = document.getElementById(wrapId);
        if (!wrap) return;
        const btn = wrap.querySelector('.role-select-btn');
        const willOpen = !wrap.classList.contains('open');
        closeAllFilterSelects();
        if (willOpen) {
            // Flip the list upward when the viewport bottom would clip it.
            // The list is display:none while closed, so measure it off-screen.
            const list = wrap.querySelector('.role-select-list');
            let listH = 0;
            if (list) {
                list.style.visibility = 'hidden';
                list.style.display = 'flex';
                listH = list.offsetHeight;
                list.style.display = '';
                list.style.visibility = '';
            }
            const spaceBelow = window.innerHeight - btn.getBoundingClientRect().bottom;
            wrap.classList.toggle('drop-up', spaceBelow < listH + 16);
        }
        wrap.classList.toggle('open', willOpen);
        btn?.setAttribute('aria-expanded', String(willOpen));
    }

    function closeAllFilterSelects() {
        document.querySelectorAll('.role-select.open').forEach((wrap) => {
            wrap.classList.remove('open');
            wrap.querySelector('.role-select-btn')?.setAttribute('aria-expanded', 'false');
        });
    }

    function selectFilterOption(inputId, value, label) {
        const input = document.getElementById(inputId);
        if (input) input.value = value;
        const wrap = input?.closest('.role-select');
        const labelEl = wrap?.querySelector('.role-select-btn span');
        if (labelEl) labelEl.textContent = label;
        wrap?.querySelectorAll('.role-select-option').forEach((opt) => {
            const selected = opt.dataset.value === value;
            opt.classList.toggle('selected', selected);
            opt.setAttribute('aria-selected', String(selected));
        });
        closeAllFilterSelects();
        USER_PAGE = 1;
        renderUsers();
    }

    function selectRole(value, label) {
        document.getElementById('newRole').value = value;
        document.getElementById('roleSelectBtnLabel').textContent = label;
        document.querySelectorAll('#roleSelectList .role-select-option').forEach((opt) => {
            const selected = opt.dataset.value === value;
            opt.classList.toggle('selected', selected);
            opt.setAttribute('aria-selected', String(selected));
        });
        document.getElementById('roleSelect')?.classList.remove('open');
        document.getElementById('roleSelectBtn')?.setAttribute('aria-expanded', 'false');
    }

    document.addEventListener('click', (e) => {
        const wrap = document.getElementById('roleSelect');
        if (wrap && wrap.classList.contains('open') && !wrap.contains(e.target)) {
            wrap.classList.remove('open');
            document.getElementById('roleSelectBtn')?.setAttribute('aria-expanded', 'false');
        }
        if (!e.target.closest('.role-select')) closeAllFilterSelects();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.getElementById('roleSelect')?.classList.remove('open');
            document.getElementById('roleSelectBtn')?.setAttribute('aria-expanded', 'false');
            closeAllFilterSelects();
        }
    });

    const VALID_CHECK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    const VALID_X = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';

    function isUsernameTaken(value) {
        const v = (value || '').trim().toLowerCase();
        if (!v) return false;
        return USERS.some(u => (u.username || '').toLowerCase() === v);
    }

    function isEmailTaken(value) {
        const v = (value || '').trim().toLowerCase();
        if (!v) return false;
        return USERS.some(u => (u.email || '').toLowerCase() === v);
    }

    function setValidIcon(inputId, state) {
        const icon = document.querySelector(`[data-valid-for="${inputId}"]`);
        if (!icon) return;
        icon.classList.remove('show-valid', 'show-invalid');
        if (state === 'valid') {
            icon.classList.add('show-valid');
            icon.innerHTML = VALID_CHECK;
        } else if (state === 'invalid') {
            icon.classList.add('show-invalid');
            icon.innerHTML = VALID_X;
        } else {
            icon.innerHTML = '';
        }
    }

    function paintFieldValidity(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const val = input.value.trim();
        if (!val) { setValidIcon(inputId, ''); return; }
        let valid = false;
        if (inputId === 'newFirstName' || inputId === 'newLastName') {
            valid = /^[A-Za-zÑñ]+(?: [A-Za-zÑñ]+)*$/.test(val);
        } else if (inputId === 'newUsername') {
            valid = /^[A-Za-z0-9]{3,255}$/.test(val) && !isUsernameTaken(val);
        } else if (inputId === 'newEmail') {
            valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) && !isEmailTaken(val);
        }
        setValidIcon(inputId, valid ? 'valid' : 'invalid');
    }

    function resetFieldValidity(inputId) {
        setValidIcon(inputId, '');
    }

    function repaintAllFieldValidity() {
        ['newFirstName', 'newLastName', 'newUsername', 'newEmail'].forEach(paintFieldValidity);
    }

    function passwordStrengthLevel(value) {
        const len = (value || '').length;
        if (len <= 0) return '';
        if (len <= 2) return 'weak';
        if (len <= 5) return 'medium';
        return 'strong';
    }

    function paintPasswordStrength(inputId) {
        const input = document.getElementById(inputId);
        const box = document.querySelector(`[data-strength-for="${inputId}"]`);
        if (!input || !box) return;
        const level = passwordStrengthLevel(input.value);
        if (!level) {
            box.removeAttribute('data-level');
            box.querySelector('.pw-strength-label').textContent = 'Password strength';
            return;
        }
        box.setAttribute('data-level', level);
        box.querySelector('.pw-strength-label').textContent = level.charAt(0).toUpperCase() + level.slice(1);
    }

    function resetPasswordStrength(inputId) {
        const box = document.querySelector(`[data-strength-for="${inputId}"]`);
        if (!box) return;
        box.removeAttribute('data-level');
        box.querySelector('.pw-strength-label').textContent = 'Password strength';
    }

    function paintPasswordMatch(pwId, confirmId) {
        const pw = document.getElementById(pwId);
        const confirm = document.getElementById(confirmId);
        const box = document.querySelector(`[data-match-for="${confirmId}"]`);
        if (!pw || !confirm || !box) return;
        const label = box.querySelector('.pw-match-label');
        if (!confirm.value) {
            box.removeAttribute('data-state');
            label.textContent = '';
            return;
        }
        if (pw.value === confirm.value) {
            box.setAttribute('data-state', 'match');
            label.textContent = 'Passwords match';
        } else {
            box.setAttribute('data-state', 'mismatch');
            label.textContent = 'Passwords do not match';
        }
    }

    function resetPasswordMatch(confirmId) {
        const box = document.querySelector(`[data-match-for="${confirmId}"]`);
        if (!box) return;
        box.removeAttribute('data-state');
        box.querySelector('.pw-match-label').textContent = '';
    }

    function syncConfirmState(pwId, confirmId) {
        const pw = document.getElementById(pwId);
        const confirm = document.getElementById(confirmId);
        if (!pw || !confirm) return;
        if (!pw.value) {
            confirm.value = '';
            confirm.readOnly = true;
            const box = document.querySelector(`[data-match-for="${confirmId}"]`);
            if (box) {
                box.removeAttribute('data-state');
                box.querySelector('.pw-match-label').textContent = '';
            }
        } else {
            confirm.readOnly = false;
        }
    }

    function guardConfirmPassword(pwId, confirmId) {
        const pw = document.getElementById(pwId);
        const confirm = document.getElementById(confirmId);
        if (!pw || !confirm) return;
        if (!pw.value) {
            pw.classList.add('input-required');
            const box = document.querySelector(`[data-match-for="${confirmId}"]`);
            if (box) {
                box.setAttribute('data-state', 'mismatch');
                box.querySelector('.pw-match-label').textContent = 'Enter password first';
            }
            if (document.activeElement === confirm) confirm.blur();
            pw.focus();
            clearTimeout(guardConfirmPassword._t);
            guardConfirmPassword._t = setTimeout(() => clearConfirmGuard(pwId, confirmId), 2500);
        }
    }

    function clearConfirmGuard(pwId, confirmId) {
        const pw = document.getElementById(pwId);
        const box = document.querySelector(`[data-match-for="${confirmId}"]`);
        if (pw) pw.classList.remove('input-required');
        if (box && box.querySelector('.pw-match-label').textContent === 'Enter password first') {
            box.removeAttribute('data-state');
            box.querySelector('.pw-match-label').textContent = '';
        }
    }

    document.addEventListener('pointerdown', (e) => {
        const pairs = [['newPassword', 'newPasswordConfirm'], ['userNewPassword', 'userConfirmPassword']];
        for (const [pwId, confirmId] of pairs) {
            const pw = document.getElementById(pwId);
            const confirm = document.getElementById(confirmId);
            const box = document.querySelector(`[data-match-for="${confirmId}"]`);
            const showing = box && box.querySelector('.pw-match-label').textContent === 'Enter password first';
            if (!showing || !pw) continue;
            if (e.target === pw || e.target === confirm || (box && box.contains(e.target))) continue;
            clearConfirmGuard(pwId, confirmId);
        }
    }, true);

    document.addEventListener('focusin', (e) => {
        const pairs = [['newPassword', 'newPasswordConfirm'], ['userNewPassword', 'userConfirmPassword']];
        for (const [pwId, confirmId] of pairs) {
            const pw = document.getElementById(pwId);
            const confirm = document.getElementById(confirmId);
            const box = document.querySelector(`[data-match-for="${confirmId}"]`);
            const showing = box && box.querySelector('.pw-match-label').textContent === 'Enter password first';
            if (!showing || !pw) continue;
            if (e.target === pw || e.target === confirm) continue;
            clearConfirmGuard(pwId, confirmId);
        }
    });

    function userInfoIcons(kind) {
        const icons = {
            error: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
            success: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            info: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };
        return icons[kind] || icons.info;
    }

    function showUserInfo(message, type = 'info', title = '') {
        const overlay = document.getElementById('userInfoOverlay');
        const iconEl  = document.getElementById('userInfoIcon');
        const titleEl = document.getElementById('userInfoTitle');
        const textEl  = document.getElementById('userInfoText');
        if (!overlay || !iconEl || !titleEl || !textEl) return;
        const kind = (type === 'success' || type === 'error') ? type : 'info';
        const titles = { error: 'Something went wrong', success: 'Success', info: 'Notice' };
        iconEl.className = 'user-info-icon ' + kind;
        iconEl.innerHTML = userInfoIcons(kind);
        titleEl.textContent = title || titles[kind];
        textEl.textContent = message || '';
        overlay.classList.add('active');
        const btn = overlay.querySelector('.user-info-btn');
        if (btn) setTimeout(() => btn.focus(), 50);
    }

    function closeUserInfo() {
        document.getElementById('userInfoOverlay')?.classList.remove('active');
        if (_pendingReload) {
            _pendingReload = false;
            loadUsers();
        }
    }

    function toast(message, type = 'success') {
        showUserInfo(message, type === 'error' ? 'error' : (type === 'info' ? 'info' : 'success'));
    }

    let _confirmResolve = null;

    function askUserConfirm({ title = 'Please confirm', message = '', okLabel = 'Confirm', danger = false } = {}) {
        const overlay = document.getElementById('userConfirmOverlay');
        const iconEl  = document.getElementById('userConfirmIcon');
        const titleEl = document.getElementById('userConfirmTitle');
        const textEl  = document.getElementById('userConfirmText');
        const okBtn   = document.getElementById('userConfirmOkBtn');
        iconEl.className = 'user-info-icon ' + (danger ? 'error' : 'info');
        iconEl.innerHTML = userInfoIcons(danger ? 'error' : 'info');
        titleEl.textContent = title;
        textEl.textContent = message;
        okBtn.textContent = okLabel;
        okBtn.className = 'user-confirm-btn ' + (danger ? 'danger' : 'primary');
        overlay.classList.add('active');
        setTimeout(() => okBtn.focus(), 50);
        return new Promise((resolve) => { _confirmResolve = resolve; });
    }

    function acceptUserConfirm() {
        document.getElementById('userConfirmOverlay')?.classList.remove('active');
        if (_confirmResolve) { _confirmResolve(true); _confirmResolve = null; }
    }

    function cancelUserConfirm() {
        document.getElementById('userConfirmOverlay')?.classList.remove('active');
        if (_confirmResolve) { _confirmResolve(false); _confirmResolve = null; }
    }

    let _pwUserId = null;

    function openUserPassword(id, username) {
        _pwUserId = id;
        document.getElementById('userPasswordTitle').textContent = 'Reset Password';
        document.getElementById('userPasswordText').textContent = `Set a new password for "${username}" (min. 6 characters).`;
        document.getElementById('userNewPassword').value = '';
        document.getElementById('userNewPassword').classList.remove('input-required');
        document.getElementById('userConfirmPassword').value = '';
        document.getElementById('userConfirmPassword').readOnly = true;
        document.getElementById('userPasswordError').textContent = '';
        resetPasswordStrength('userNewPassword');
        resetPasswordMatch('userConfirmPassword');
        document.getElementById('userPasswordIcon').className = 'user-info-icon info';
        document.getElementById('userPasswordIcon').innerHTML = userInfoIcons('info');
        document.getElementById('userPasswordOverlay').classList.add('active');
        setTimeout(() => document.getElementById('userNewPassword')?.focus(), 50);
    }

    function cancelUserPassword(reload = true) {
        document.getElementById('userPasswordOverlay')?.classList.remove('active');
        _pwUserId = null;
        if (reload) loadUsers();
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (document.getElementById('userPasswordOverlay')?.classList.contains('active')) cancelUserPassword();
        else if (document.getElementById('userConfirmOverlay')?.classList.contains('active')) cancelUserConfirm();
        else if (document.getElementById('userInfoOverlay')?.classList.contains('active')) closeUserInfo();
    });

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function startUsersLoading() {
        const tbody = document.getElementById('userTableBody');
        const wrapper = document.getElementById('userTableView');
        if (!tbody || !wrapper) return;
        wrapper.classList.add('loading');
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Loading accounts...</td></tr>';
        const meta = document.getElementById('userResultMeta');
        if (meta) meta.textContent = 'Loading...';
        renderUserPager();
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
            USER_PAGE = 1;
            renderUsers();
            repaintAllFieldValidity();
        } catch (e) {
            USERS = [];
            renderUserStats();
            setUserResultMeta(null);
            renderUserPager();
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Unable to load accounts.</td></tr>';
        } finally {
            wrapper.classList.remove('loading');
        }
    }

    function renderUserStats() {
        const total    = USERS.length;
        const active   = USERS.filter(u => u.is_active).length;
        const inactive = total - active;
        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };
        set('statTotalUsers', total);
        set('statActiveUsers', active);
        set('statInactiveUsers', inactive);
    }

    /* ---- Client-side filtering ----------------------------------------
       /api/users returns the whole account list in one response, so search,
       role, status, and rows are narrowed here rather than adding query
       params the controller does not read. */
    function currentUserFilters() {
        return {
            search: (document.getElementById('userSearch')?.value || '').trim().toLowerCase(),
            role:   document.getElementById('userFilterRole')?.value || '',
            status: document.getElementById('userFilterStatus')?.value || '',
            perPage: parseInt(document.getElementById('userFilterRows')?.value, 10) || 15,
        };
    }

    function applyUserFilters(rows) {
        const f = currentUserFilters();
        return rows.filter(u => {
            if (f.role && u.role !== f.role) return false;
            if (f.status === 'active' && !u.is_active) return false;
            if (f.status === 'inactive' && u.is_active) return false;
            if (f.search) {
                const haystack = `${u.name ?? ''} ${u.username ?? ''} ${u.email ?? ''}`.toLowerCase();
                if (!haystack.includes(f.search)) return false;
            }
            return true;
        });
    }

    function resetUserFilters() {
        const search = document.getElementById('userSearch');
        if (search) search.value = '';
        selectFilterOption('userFilterRole', '', 'All roles');
        selectFilterOption('userFilterStatus', '', 'All statuses');
        selectFilterOption('userFilterRows', '15', '15');
        USER_PAGE = 1;
        renderUsers();
    }

    function setUserResultMeta(shown, total) {
        const el = document.getElementById('userResultMeta');
        if (!el) return;
        el.textContent = shown === null
            ? 'Unable to load'
            : `${shown} of ${total} account${total === 1 ? '' : 's'}`;
    }

    function renderUserPager(maxPage = 1) {
        const pager = document.getElementById('userPager');
        if (!pager) return;
        if (maxPage <= 1) { pager.style.display = 'none'; pager.innerHTML = ''; return; }
        pager.style.display = 'flex';
        pager.innerHTML = `
            <button class="btn-ghost" onclick="goUserPage(-1)" ${USER_PAGE === 1 ? 'disabled' : ''}>&larr; Prev</button>
            <span class="pager-label">Page ${USER_PAGE} of ${maxPage}</span>
            <button class="btn-ghost" onclick="goUserPage(1)" ${USER_PAGE === maxPage ? 'disabled' : ''}>Next &rarr;</button>`;
    }

    function goUserPage(delta) {
        const filtered = applyUserFilters(USERS);
        const maxPage = Math.max(1, Math.ceil(filtered.length / currentUserFilters().perPage));
        const next = USER_PAGE + delta;
        if (next < 1 || next > maxPage) return;
        USER_PAGE = next;
        renderUsers();
    }

    function renderUsers() {
        const tbody = document.getElementById('userTableBody');

        renderUserStats();

        if (USERS.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No accounts yet.</td></tr>';
            setUserResultMeta(0, 0);
            renderUserPager();
            return;
        }

        const filtered = applyUserFilters(USERS);
        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No accounts match these filters.</td></tr>';
            setUserResultMeta(0, 0);
            renderUserPager();
            return;
        }

        const perPage = currentUserFilters().perPage;
        const maxPage = Math.max(1, Math.ceil(filtered.length / perPage));
        if (USER_PAGE > maxPage) USER_PAGE = maxPage;
        const start = (USER_PAGE - 1) * perPage;
        const pageRows = filtered.slice(start, start + perPage);

        setUserResultMeta(pageRows.length, filtered.length);
        renderUserPager(maxPage);

        tbody.innerHTML = pageRows.map(u => `
            <tr class="user-card-row ${u.is_active ? '' : 'row-inactive'}">
                <td colspan="7" class="user-card-cell">
                    <div class="user-row-card" data-user-id="${u.id}">
                        <div class="user-row-field">${highlightMatch(u.name)}</div>
                        <div class="user-row-field">${highlightMatch(u.username)}</div>
                        <div class="user-row-field">${highlightMatch(u.email)}</div>
                        <div class="user-row-field"><span class="role-badge ${escapeHtml(u.role)}">${u.role === 'admin' ? 'Admin' : 'Staff'}</span></div>
                        <div class="user-row-field"><span class="status-badge ${u.is_active ? 'active' : 'inactive'}">${u.is_active ? 'Active' : 'Deactivated'}</span></div>
                        <div class="user-row-field">${escapeHtml(u.created_at || '—')}</div>
                        <div class="user-row-field actions-field">
                            <div class="actions-inner">
                                <button class="btn-mini" ${u.is_active ? '' : 'disabled title="Reactivate the account first"'} onclick="promptResetPassword(${u.id}, ${escapeHtml(JSON.stringify(u.username))})">Reset Password</button>
                                <button class="btn-mini" ${u.is_active ? '' : 'disabled title="Reactivate the account first"'} onclick="changeRole(${u.id})">Change Role</button>
                                ${u.is_active
                                    ? `<button class="btn-mini danger" onclick="setActive(${u.id}, false)">Deactivate</button>`
                                    : `<button class="btn-mini success" onclick="setActive(${u.id}, true)">Reactivate</button>`}
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async function createUser() {
        const firstName = document.getElementById('newFirstName').value.trim();
        const lastName  = document.getElementById('newLastName').value.trim();
        const payload = {
            name:     `${firstName} ${lastName}`.trim().replace(/ {2,}/g, ' '),
            username: document.getElementById('newUsername').value.trim(),
            email:    document.getElementById('newEmail').value.trim(),
            role:     document.getElementById('newRole').value,
            password: document.getElementById('newPassword').value,
            password_confirmation: document.getElementById('newPasswordConfirm').value,
        };

        if (!firstName || !lastName || !payload.username || !payload.email || !payload.password) {
            showUserInfo('Please fill in every field before creating the account.', 'error', 'Missing details');
            return;
        }
        if (!/^[A-Za-zÑñ]+(?: [A-Za-zÑñ]+)*$/.test(firstName)) {
            showUserInfo('First name may only contain letters and spaces.', 'error', 'Invalid name');
            return;
        }
        if (!/^[A-Za-zÑñ]+(?: [A-Za-zÑñ]+)*$/.test(lastName)) {
            showUserInfo('Last name may only contain letters and spaces.', 'error', 'Invalid name');
            return;
        }
        if (!/^[A-Za-z0-9]{3,255}$/.test(payload.username)) {
            showUserInfo('Username may only contain letters and numbers (min. 3 characters, no special characters).', 'error', 'Invalid username');
            return;
        }
        if (payload.password.length < 6) {
            showUserInfo('Password must be at least 6 characters.', 'error', 'Password too short');
            return;
        }
        if (payload.password !== payload.password_confirmation) {
            showUserInfo('Passwords do not match. Please retype both password fields.', 'error', 'Password mismatch');
            return;
        }

        try {
            const res = await fetch('/api/users', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                const first = data.errors ? Object.values(data.errors)[0][0] : data.message;
                _pendingReload = true;
                showUserInfo(first || 'Could not create the account.', 'error', 'Cannot create account');
                return;
            }

            ['newFirstName','newLastName','newUsername','newEmail','newPassword','newPasswordConfirm'].forEach(id => {
                document.getElementById(id).value = '';
            });
            document.getElementById('newPassword').classList.remove('input-required');
            document.getElementById('newPasswordConfirm').readOnly = true;
            resetPasswordStrength('newPassword');
            resetPasswordMatch('newPasswordConfirm');
            ['newFirstName','newLastName','newUsername','newEmail'].forEach(resetFieldValidity);
            _pendingReload = true;
            showUserInfo(data.message || 'Account created successfully.', 'success', 'Account created');
        } catch (e) {
            _pendingReload = true;
            showUserInfo('Could not connect to the server. Please try again.', 'error', 'Connection error');
        }
    }

    async function setActive(id, active) {
        const verb = active ? 'activate' : 'deactivate';
        const ok = await askUserConfirm(active ? {
            title: 'Reactivate account?',
            message: 'The employee will be able to sign in again with their existing credentials.',
            okLabel: 'Reactivate',
            danger: false,
        } : {
            title: 'Deactivate account?',
            message: 'The employee keeps their sales history, but can no longer sign in.',
            okLabel: 'Deactivate',
            danger: true,
        });
        if (!ok) { return; }

        try {
            const res = await fetch(`/api/users/${id}/${verb}`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            const data = await res.json().catch(() => ({}));
            _pendingReload = true;
            if (res.ok) {
                showUserInfo(data.message || (active ? 'Account reactivated.' : 'Account deactivated.'), 'success', active ? 'Account reactivated' : 'Account deactivated');
            } else {
                showUserInfo(data.message || 'Could not update the account.', 'error', 'Action failed');
            }
        } catch (e) {
            _pendingReload = true;
            showUserInfo('Could not connect to the server. Please try again.', 'error', 'Connection error');
        }
    }

    async function changeRole(id) {
        const user  = USERS.find(u => u.id === id);
        if (!user) return;

        const next  = user.role === 'admin' ? 'cashier' : 'admin';
        const label = next === 'admin' ? 'Admin (full access)' : 'Staff (POS only)';
        const ok = await askUserConfirm({
            title: 'Change role?',
            message: `Change ${user.username}'s role to ${label}?`,
            okLabel: 'Change Role',
            danger: false,
        });
        if (!ok) { return; }

        try {
            const res = await fetch(`/api/users/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ role: next }),
            });
            const data = await res.json().catch(() => ({}));
            const msg  = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Done');
            _pendingReload = true;
            if (res.ok) {
                showUserInfo(msg, 'success', 'Role updated');
            } else {
                showUserInfo(msg, 'error', 'Cannot change role');
            }
        } catch (e) {
            _pendingReload = true;
            showUserInfo('Could not connect to the server. Please try again.', 'error', 'Connection error');
        }
    }

    function promptResetPassword(id, username) {
        openUserPassword(id, username);
    }

    async function acceptUserPassword() {
        const errEl = document.getElementById('userPasswordError');
        const next = document.getElementById('userNewPassword').value;
        const confirmPw = document.getElementById('userConfirmPassword').value;
        if (!next || next.length < 6) {
            errEl.textContent = 'Password must be at least 6 characters.';
            return;
        }
        if (next !== confirmPw) {
            errEl.textContent = 'Passwords do not match.';
            return;
        }
        errEl.textContent = '';
        const id = _pwUserId;
        cancelUserPassword(false);
        try {
            const res = await fetch(`/api/users/${id}/reset-password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ password: next, password_confirmation: confirmPw }),
            });
            const data = await res.json().catch(() => ({}));
            const msg = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Done');
            _pendingReload = true;
            if (res.ok) {
                showUserInfo(msg, 'success', 'Password reset');
            } else {
                showUserInfo(msg, 'error', 'Cannot reset password');
            }
        } catch (e) {
            _pendingReload = true;
            showUserInfo('Could not reset the password. Please try again.', 'error', 'Connection error');
        }
    }

    /* ---- Search & filter wiring --------------------------------------- */
    (function initUserFilters() {
        const search = document.getElementById('userSearch');

        let debounce;
        search?.addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => { USER_PAGE = 1; renderUsers(); }, 250);
        });
    })();

    loadUsers();
</script>
@endpush
