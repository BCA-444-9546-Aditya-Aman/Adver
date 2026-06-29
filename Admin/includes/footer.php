</div><!-- /main-content -->

<!-- ═══ LEAD DETAIL MODAL ═══════════════════════════════════════════════════ -->
<div class="modal" id="detailsModal">
    <div class="modal-content" style="max-width: 1000px;">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-address-card"></i><span id="modalLeadType">Lead Details</span></div>
            <button class="modal-close" onclick="closeLeadModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalDetailsBody"><!-- Populated via JS --></div>
    </div>
</div>

<!-- ═══ CUSTOM CONTEXT MENU ══════════════════════════════════════════════════ -->
<div id="customContextMenu" class="context-menu" style="display: none; position: absolute; z-index: 10000;">
    <ul>
        <li onclick="contextView()"><i class="fa-regular fa-eye"></i> View Details</li>
        <?php if ($is_super_admin): ?>
        <li onclick="contextDelete()" class="delete"><i class="fa-regular fa-trash-can"></i> Delete Lead</li>
        <?php endif; ?>
    </ul>
</div>


<!-- ═══ ADD ADMIN MODAL ══════════════════════════════════════════════════════ -->
<?php if ($is_super_admin): ?>
<div class="modal" id="addAdminModal">
    <div class="modal-content add-admin-modal-content">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-user-plus"></i> Add Sub-Admin</div>
            <button class="modal-close" onclick="closeAddAdminModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="addAdminErr" style="display:none; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 16px;"></div>
            <div class="aa-form-grid">
                <div>
                    <label class="modal-label">Username *</label>
                    <input type="text" class="aa-form-input" id="aa_username" placeholder="e.g. rahul_s">
                </div>
                <div>
                    <label class="modal-label">Email</label>
                    <input type="email" class="aa-form-input" id="aa_email" placeholder="e.g. rahul@company.com">
                </div>
                <div>
                    <label class="modal-label">Password *</label>
                    <input type="password" class="aa-form-input" id="aa_password" placeholder="Min. 6 characters">
                </div>
            </div>

            <div class="permissions-section">
                <h4><i class="fa-solid fa-shield-halved" style="margin-right: 6px; color: var(--primary);"></i> Tab Access Permissions</h4>
                <div class="permission-toggles-grid">
                    <label class="permission-toggle-item">
                        <span class="pti-label"><i class="fa-solid fa-code"></i> Web Leads</span>
                        <label class="toggle-switch"><input type="checkbox" id="perm_web" checked><span class="toggle-slider"></span></label>
                    </label>
                    <label class="permission-toggle-item">
                        <span class="pti-label"><i class="fa-solid fa-magnifying-glass"></i> SEO Leads</span>
                        <label class="toggle-switch"><input type="checkbox" id="perm_seo" checked><span class="toggle-slider"></span></label>
                    </label>
                    <label class="permission-toggle-item">
                        <span class="pti-label"><i class="fa-solid fa-share-nodes"></i> SMM Leads</span>
                        <label class="toggle-switch"><input type="checkbox" id="perm_smm" checked><span class="toggle-slider"></span></label>
                    </label>
                    <label class="permission-toggle-item">
                        <span class="pti-label"><i class="fa-brands fa-whatsapp"></i> Automation</span>
                        <label class="toggle-switch"><input type="checkbox" id="perm_automation" checked><span class="toggle-slider"></span></label>
                    </label>
                    <label class="permission-toggle-item">
                        <span class="pti-label"><i class="fa-solid fa-shield-halved"></i> Security</span>
                        <label class="toggle-switch"><input type="checkbox" id="perm_security"><span class="toggle-slider"></span></label>
                    </label>
                </div>
            </div>

            <button class="btn btn-primary" id="addAdminSubmitBtn" onclick="submitAddAdmin()" style="width: 100%; height: 46px; justify-content: center; border-radius: 12px; font-size: 14px; font-weight: 600;">
                <i class="fa-solid fa-user-plus"></i> Create Sub-Admin
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ JAVASCRIPT ════════════════════════════════════════════════════════════ -->
<script>
// ── Globals ───────────────────────────────────────────────────────────────────
const IS_SUPER_ADMIN = <?php echo $is_super_admin ? 'true' : 'false'; ?>;
const CURRENT_ADMIN_ID = <?php echo $current_admin_id; ?>;

const SUB_ADMINS = <?php echo json_encode(array_map(function($a) {
    return ['id' => $a['id'], 'name' => $a['username'], 'username' => $a['username'], 'email' => $a['email'] ?? ''];
}, isset($all_sub_admins) ? $all_sub_admins : [])); ?>;

let activeContextLead = null;
let activeContextType = null;
let activeContextId   = null;
let assignTargetLead  = null;
let selectedAdminData = null;

// ── Event delegation for lead rows (left-click + right-click) ────────────────
function rowClick(row, e) {
    if (e.target.tagName.toLowerCase() === 'a' || e.target.closest('a')) return;
    const lead = getLeadFromRow(row);
    if (lead) openLeadDetails(lead);
}

function rowContext(row, e) {
    e.preventDefault();
    const lead = getLeadFromRow(row);
    if (!lead) return;
    activeContextLead = lead;
    activeContextType = row.dataset.type;
    activeContextId   = row.dataset.id;
    const menu = document.getElementById('customContextMenu');
    if (menu) {
        menu.style.display = 'block';
        // Clamp to viewport
        const mw = 200, mh = 110;
        let x = e.pageX, y = e.pageY;
        if (x + mw > window.innerWidth  + window.scrollX) x = x - mw;
        if (y + mh > window.innerHeight + window.scrollY) y = y - mh;
        menu.style.left = x + 'px';
        menu.style.top  = y + 'px';
    }
}

function getLeadFromRow(row) {
    try {
        return JSON.parse(row.dataset.lead);
    } catch(err) {
        console.error('Lead data parse error:', err, row.dataset.lead);
        return null;
    }
}

function closeContextMenu() {
    const menu = document.getElementById('customContextMenu');
    if (menu) menu.style.display = 'none';
}
function contextView()   { if (activeContextLead) openLeadDetails(activeContextLead); }
function contextDelete() {
    if (activeContextType && activeContextId) {
        if (confirm('Are you sure you want to permanently delete this lead?')) {
            window.location.href = `delete.php?type=${activeContextType}&id=${activeContextId}`;
        }
    }
}

// ── Keyboard shortcuts ────────────────────────────────────────────────────────
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeContextMenu(); closeLeadModal(); closeAddAdminModal(); }
});

// ══ LEAD DETAIL MODAL ═════════════════════════════════════════════════════════
function openLeadDetails(lead) {
    const modal = document.getElementById('detailsModal');
    const body  = document.getElementById('modalDetailsBody');
    document.getElementById('modalLeadType').innerHTML =
        `<span class="badge badge-${lead.lead_type}">${lead.display_type}</span> Lead #${lead.id}`;

    // Mark as read
    if (!lead.is_read) {
        const fd = new FormData();
        fd.append('action', 'mark_as_read'); fd.append('type', lead.lead_type); fd.append('id', lead.id);
        fetch('includes/ajax_handler.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
            if (data.success) {
                lead.is_read = 1;
                const row = document.querySelector(`tr[data-id="${lead.id}"].unread-row`);
                if (row) { row.classList.remove('unread-row'); const dot = row.querySelector('.unread-dot'); if (dot) dot.remove(); }
                const badge = document.getElementById('badge-' + lead.lead_type);
                if (badge) { let c = parseInt(badge.textContent)||0; c>1 ? (badge.textContent=c-1) : (badge.textContent='0', badge.style.display='none'); }
            }
        }).catch(()=>{});
    }

    body.innerHTML = buildLeadHtml(lead);
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Fetch timeline async
    const fd2 = new FormData();
    fd2.append('action', 'get_lead_timeline'); fd2.append('lead_type', lead.lead_type); fd2.append('lead_id', lead.id);
    fetch('includes/ajax_handler.php', { method: 'POST', body: fd2 }).then(r => r.json()).then(data => {
        renderTimelineSection(data, lead);
    }).catch(()=>{});
}

function buildLeadHtml(lead) {
    let leftHtml = `<div class="modal-details-grid" style="grid-template-columns: 1fr; gap: 12px;">
        <div class="detail-card"><span class="detail-label"><i class="fa-regular fa-user" style="color:var(--primary);margin-right:6px;"></i> Name</span><span class="detail-value">${escapeHtml(lead.name)}</span></div>
        <div class="detail-card"><span class="detail-label"><i class="fa-regular fa-envelope" style="color:var(--primary);margin-right:6px;"></i> Email</span><span class="detail-value" style="word-break:break-all;">${escapeHtml(lead.email)}</span></div>
        <div class="detail-card"><span class="detail-label"><i class="fa-solid fa-phone" style="color:var(--primary);margin-right:6px;"></i> Phone</span><span class="detail-value">${escapeHtml(lead.phone||'N/A')}</span></div>`;

    if (lead.lead_type === 'web') {
        leftHtml += `<div class="detail-card"><span class="detail-label"><i class="fa-solid fa-layer-group" style="color:var(--info);margin-right:6px;"></i> Service Package</span><span class="detail-value">${escapeHtml(lead.service)}</span></div>
                  <div class="detail-card"><span class="detail-label"><i class="fa-regular fa-comment-dots" style="color:var(--info);margin-right:6px;"></i> Message</span><div class="detail-value message-box" style="margin-top:5px;background:#fff;max-height:100px;">${escapeHtml(lead.message||'No message provided.')}</div></div>`;
    } else if (lead.lead_type === 'seo') {
        leftHtml += `<div class="detail-card"><span class="detail-label"><i class="fa-solid fa-building" style="color:var(--success);margin-right:6px;"></i> Business Name</span><span class="detail-value">${escapeHtml(lead.business_name)}</span></div>
                  <div class="detail-card"><span class="detail-label"><i class="fa-solid fa-globe" style="color:var(--success);margin-right:6px;"></i> Website URL</span><span class="detail-value"><a href="${lead.website&&lead.website.startsWith('http')?'':'https://'}${escapeHtml(lead.website)}" target="_blank" style="color:var(--info);text-decoration:none;font-weight:600;">${escapeHtml(lead.website)} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px;"></i></a></span></div>
                  <div class="detail-card"><span class="detail-label"><i class="fa-solid fa-chart-line" style="color:var(--success);margin-right:6px;"></i> Biggest SEO Need</span><span class="detail-value">${escapeHtml(lead.seo_need)}</span></div>`;
    } else if (lead.lead_type === 'smm') {
        leftHtml += `<div class="detail-card"><span class="detail-label"><i class="fa-solid fa-building" style="color:var(--warning);margin-right:6px;"></i> Business Name</span><span class="detail-value">${escapeHtml(lead.business_name)}</span></div>
                  <div class="detail-card"><span class="detail-label"><i class="fa-brands fa-instagram" style="color:var(--warning);margin-right:6px;"></i> IG Handle / Web</span><span class="detail-value">${escapeHtml(lead.instagram_or_website)}</span></div>
                  <div class="detail-card"><span class="detail-label"><i class="fa-solid fa-bullhorn" style="color:var(--warning);margin-right:6px;"></i> Primary SMM Need</span><span class="detail-value">${escapeHtml(lead.smm_need)}</span></div>`;
    } else if (lead.lead_type === 'automation') {
        leftHtml += `<div class="detail-card"><span class="detail-label"><i class="fa-solid fa-building" style="color:#16a34a;margin-right:6px;"></i> Business Name</span><span class="detail-value">${escapeHtml(lead.business_name)}</span></div>
                  <div class="detail-card"><span class="detail-label"><i class="fa-solid fa-industry" style="color:#16a34a;margin-right:6px;"></i> Business Type</span><span class="detail-value">${escapeHtml(lead.business_type||'Other')}</span></div>
                  <div class="detail-card"><span class="detail-label"><i class="fa-regular fa-comment-dots" style="color:#16a34a;margin-right:6px;"></i> Message</span><div class="detail-value message-box" style="margin-top:5px;background:#fff;max-height:100px;">${escapeHtml(lead.message||'No message provided.')}</div></div>`;
    }

    leftHtml += `<div class="detail-card"><span class="detail-label"><i class="fa-regular fa-calendar-days" style="color:var(--text-light);margin-right:6px;"></i> Captured At</span><span class="detail-value">${new Date(lead.created_at).toLocaleString()}</span></div>`;
    
    leftHtml += `</div>`;

    return `<div class="lead-modal-two-columns">
        <div class="lmtc-column">${leftHtml}</div>
        <div class="lmtc-column" id="lmtcRightCol">
            <div id="timelinePlaceholder" style="text-align:center; color:var(--text-light); padding: 40px 10px; font-size:13px;"><i class="fa-solid fa-spinner fa-spin" style="margin-right:6px;"></i> Loading timeline & pipeline...</div>
        </div>
    </div>`;
}

function renderTimelineSection(data, lead) {
    const rightCol = document.getElementById('lmtcRightCol');
    if (!rightCol) return;

    let html = '';

    // Tab Owners banner (Super Admin view)
    if (IS_SUPER_ADMIN && data.tab_owners && data.tab_owners.length > 0) {
        html += `<div class="assignment-banner" style="margin-bottom: 12px;">
            <div class="assignment-banner-icon"><i class="fa-solid fa-users"></i></div>
            <div class="assignment-banner-body">
                <div class="assignment-banner-title">Managed by: ${escapeHtml(data.tab_owners.join(', '))}</div>
                <div class="assignment-banner-detail">These admins have access to the ${escapeHtml(lead.display_type)} Leads tab.</div>
            </div>
        </div>`;
    } else if (IS_SUPER_ADMIN) {
        html += `<div class="assignment-banner" style="margin-bottom: 12px; background: #fffbeb; border-color: #fde68a; color: #b45309;">
            <div class="assignment-banner-icon" style="background: #fef3c7; color: #d97706;"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="assignment-banner-body">
                <div class="assignment-banner-title" style="color: #92400e;">Unmanaged Tab</div>
                <div class="assignment-banner-detail">No sub-admins currently have access to the ${escapeHtml(lead.display_type)} Leads tab.</div>
            </div>
        </div>`;
    }

    // Update status form (only for sub-admins with access)
    if (!IS_SUPER_ADMIN) {
        html += `<div class="update-status-section" id="updateStatusSection" style="margin-bottom: 16px;">
            <h4 style="margin-top:0;"><i class="fa-solid fa-chart-line"></i> Update Pipeline Stage</h4>
            <label class="modal-label">Pipeline Stage</label>
            <select class="status-select" id="pipelineStatusSelect" style="margin-bottom: 10px;">
                <option value="">— Select a stage —</option>
                <option value="Qualified">Qualified</option>
                <option value="Initial Contact Made">Initial Contact Made</option>
                <option value="Proposal Sent">Proposal Sent</option>
                <option value="In Discussion">In Discussion</option>
                <option value="Follow-Up Scheduled">Follow-Up Scheduled</option>
                <option value="No Response">No Response</option>
                <option value="Closed - Won">Closed – Won</option>
                <option value="Closed - Lost">Closed – Lost</option>
            </select>
            <label class="modal-label">Internal Notes <span style="color:var(--text-light);font-weight:400;text-transform:none;">(optional)</span></label>
            <textarea class="modal-textarea" id="pipelineNoteInput" placeholder="Describe interaction notes..." style="height:60px;min-height:60px;margin-bottom:10px;"></textarea>
            <button class="btn btn-primary" id="pipelineSubmitBtn" onclick="submitPipelineUpdate('${lead.lead_type}', ${lead.id})" style="padding:8px 18px;border-radius:10px;font-size:12px;width:100%;justify-content:center;">
                <i class="fa-solid fa-paper-plane"></i> Log Update
            </button>
        </div>`;
    }

    // Timeline entries
    if (data.timeline && data.timeline.length > 0) {
        html += `<div class="timeline-section" style="max-height: 250px; overflow-y: auto; padding-right:5px;">
            <div class="timeline-section-title" style="margin-bottom:10px;"><i class="fa-solid fa-timeline"></i> Activity Timeline (${data.timeline.length})</div>
            <div class="timeline">`;
        data.timeline.forEach(entry => {
            const cls = getStatusClass(entry.status);
            html += `<div class="timeline-item fade-in" style="margin-bottom: 12px; padding-bottom: 10px;">
                <div class="timeline-dot ${cls.dot}"></div>
                <div class="timeline-item-body">
                    <div class="timeline-item-header">
                        <span class="status-badge ${cls.badge}" style="font-size:10px; padding: 2px 6px;"><i class="fa-solid fa-circle" style="font-size:5px;"></i> ${escapeHtml(entry.status)}</span>
                        <span class="timeline-time" style="font-size:11px;">${new Date(entry.updated_at).toLocaleDateString()}</span>
                    </div>
                    <div class="timeline-by" style="font-size:11px;"><i class="fa-regular fa-user" style="margin-right:4px;"></i>${escapeHtml(entry.updated_by_name)}</div>
                    ${entry.description ? `<div class="timeline-desc" style="font-size:12px; margin-top:4px;">${escapeHtml(entry.description)}</div>` : ''}
                </div>
            </div>`;
        });
        html += `</div></div>`;
    } else {
        html += `<div class="timeline-section">
            <div class="timeline-section-title"><i class="fa-solid fa-timeline"></i> Activity Timeline</div>
            <div style="padding: 15px; text-align: center; color: var(--text-light); font-size: 12px; background: #f9fafb; border-radius: 8px; border: 1px dashed var(--border-color);">
                <i class="fa-regular fa-clock" style="font-size: 20px; display: block; margin-bottom: 6px; opacity: 0.4;"></i>No pipeline updates yet.
            </div>
        </div>`;
    }

    rightCol.innerHTML = html;
}

// ── Status helpers ────────────────────────────────────────────────────────────
function getStatusClass(status) {
    const map = {
        'Qualified':              { badge: 'status-qualified',  dot: 'dot-qualified' },
        'Initial Contact Made':   { badge: 'status-contacted',  dot: 'dot-contacted' },
        'Proposal Sent':          { badge: 'status-proposal',   dot: 'dot-proposal'  },
        'In Discussion':          { badge: 'status-discussion', dot: 'dot-discussion'},
        'Follow-Up Scheduled':    { badge: 'status-followup',   dot: 'dot-followup'  },
        'No Response':            { badge: 'status-noresponse', dot: 'dot-noresponse'},
        'Closed - Won':           { badge: 'status-won',        dot: 'dot-won'       },
        'Closed - Lost':          { badge: 'status-lost',       dot: 'dot-lost'      },
    };
    return map[status] || { badge: 'status-noresponse', dot: 'dot-noresponse' };
}

// ── Pipeline update submit ────────────────────────────────────────────────────
function submitPipelineUpdate(lead_type, lead_id) {
    const status = document.getElementById('pipelineStatusSelect').value;
    const desc   = document.getElementById('pipelineNoteInput').value.trim();
    if (!status) { alert('Please select a pipeline stage.'); return; }
    const btn = document.getElementById('pipelineSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="btn-spinner"></span> Logging...';
    const fd = new FormData();
    fd.append('action','update_lead_status'); fd.append('lead_type',lead_type);
    fd.append('lead_id',lead_id); fd.append('status',status); fd.append('description',desc);
    fetch('includes/ajax_handler.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        if (data.success) {
            showToastMsg(data.message || 'Pipeline stage logged.');
            const fd2 = new FormData();
            fd2.append('action', 'get_lead_timeline');
            fd2.append('lead_type', lead_type);
            fd2.append('lead_id', lead_id);
            fetch('includes/ajax_handler.php',{method:'POST',body:fd2}).then(r2=>r2.json()).then(d2=>{
                const rightCol = document.getElementById('lmtcRightCol');
                if (rightCol) {
                    rightCol.innerHTML = '<div id="timelinePlaceholder" style="text-align:center; color:var(--text-light); padding: 40px 10px; font-size:13px;"><i class="fa-solid fa-spinner fa-spin" style="margin-right:6px;"></i> Loading timeline & pipeline...</div>';
                    renderTimelineSection(d2, {lead_type, id:lead_id});
                }
            });
        } else {
            alert(data.error || 'Failed to update pipeline.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Log Update';
        }
    }).catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-paper-plane"></i> Log Update'; });
}

function closeLeadModal() {
    document.getElementById('detailsModal').classList.remove('active');
    document.body.style.overflow = '';
}
window.addEventListener('click', function(e) {
    // Hide context menu if clicking elsewhere
    const ctxMenu = document.getElementById('customContextMenu');
    if (ctxMenu && ctxMenu.style.display !== 'none') {
        if (!ctxMenu.contains(e.target)) {
            ctxMenu.style.display = 'none';
        }
    }
    const modal = document.getElementById('detailsModal');
    if (e.target === modal) closeLeadModal();
    const assignModal = document.getElementById('assignLeadModal');
    if (assignModal && e.target === assignModal) closeAssignModal();
    const addModal = document.getElementById('addAdminModal');
    if (addModal && e.target === addModal) closeAddAdminModal();
});


// ══ ASSIGN LEAD MODAL ═════════════════════════════════════════════════════════
function openAssignModal(lead) {
    if (!IS_SUPER_ADMIN) return;
    assignTargetLead = lead;
    selectedAdminData = null;
    document.getElementById('selectedAdminId').value = '';
    document.getElementById('assignNote').value = '';
    document.getElementById('adminSearchInput').value = '';
    document.getElementById('selectedAdminChip').style.display = 'none';
    document.getElementById('adminSearchWrapper').style.display = 'block';

    // Populate lead preview
    document.getElementById('alpName').textContent = lead.name + ' — ' + lead.display_type + ' Lead #' + lead.id;
    document.getElementById('alpSub').textContent = lead.email + (lead.phone ? ' · ' + lead.phone : '');

    renderAdminDropdown('');
    document.getElementById('assignLeadModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('adminSearchInput').focus(), 200);
}
function closeAssignModal() {
    const m = document.getElementById('assignLeadModal');
    if (m) { m.classList.remove('active'); document.body.style.overflow = ''; }
}
function openAdminDropdown() {
    document.getElementById('adminDropdown').classList.add('open');
}
function filterAdminDropdown(query) {
    renderAdminDropdown(query);
    document.getElementById('adminDropdown').classList.add('open');
}
function renderAdminDropdown(query) {
    const dd = document.getElementById('adminDropdown');
    const q = query.toLowerCase();
    const matches = SUB_ADMINS.filter(a => a.name.toLowerCase().includes(q) || a.username.toLowerCase().includes(q));
    if (matches.length === 0) {
        dd.innerHTML = `<div class="ss-empty"><i class="fa-regular fa-face-frown" style="margin-right:6px;"></i>No admins found</div>`;
    } else {
        dd.innerHTML = matches.map(a => {
            const init = a.name.charAt(0).toUpperCase();
            return `<div class="ss-dropdown-item" onclick="selectAdmin(${a.id},'${escapeAttr(a.name)}','${escapeAttr(a.username)}')">
                <div class="ss-avatar">${init}</div>
                <div><div style="font-weight:600;">${escapeHtml(a.name)}</div><div style="font-size:11px;color:var(--text-light);">@${escapeHtml(a.username)}</div></div>
            </div>`;
        }).join('');
    }
}
function selectAdmin(id, name, username) {
    selectedAdminData = { id, name, username };
    document.getElementById('selectedAdminId').value = id;
    document.getElementById('chipAvatar').textContent = name.charAt(0).toUpperCase();
    document.getElementById('chipName').textContent = name + ' (@' + username + ')';
    document.getElementById('selectedAdminChip').style.display = 'flex';
    document.getElementById('adminSearchWrapper').style.display = 'none';
    document.getElementById('adminDropdown').classList.remove('open');
}
function clearSelectedAdmin() {
    selectedAdminData = null;
    document.getElementById('selectedAdminId').value = '';
    document.getElementById('selectedAdminChip').style.display = 'none';
    document.getElementById('adminSearchWrapper').style.display = 'block';
    document.getElementById('adminSearchInput').value = '';
    renderAdminDropdown('');
}
document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('adminSearchWrapper');
    const dd = document.getElementById('adminDropdown');
    if (wrapper && dd && !wrapper.contains(e.target)) dd.classList.remove('open');
});

function submitAssignLead() {
    const adminId = document.getElementById('selectedAdminId').value;
    if (!adminId) { alert('Please select an admin to assign this lead to.'); return; }
    if (!assignTargetLead) return;
    const note = document.getElementById('assignNote').value.trim();
    const btn  = document.getElementById('assignSubmitBtn');
    btn.disabled = true; btn.innerHTML = '<span class="btn-spinner"></span> Assigning...';
    const fd = new FormData();
    fd.append('action','assign_lead'); fd.append('lead_type',assignTargetLead.lead_type);
    fd.append('lead_id',assignTargetLead.id); fd.append('assigned_to',adminId); fd.append('note',note);
    fetch('includes/ajax_handler.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-paper-plane"></i> Assign Lead';
        if (data.success) { closeAssignModal(); showToastMsg(data.message || 'Lead assigned successfully.'); }
        else alert(data.error || 'Failed to assign lead.');
    }).catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-paper-plane"></i> Assign Lead'; });
}

// ══ ADD ADMIN MODAL ════════════════════════════════════════════════════════════
function openAddAdminModal() {
    document.getElementById('aa_username').value     = '';
    document.getElementById('aa_email').value        = '';
    document.getElementById('aa_password').value     = '';
    ['web','seo','smm','automation'].forEach(t => { document.getElementById('perm_'+t).checked = true; });
    document.getElementById('perm_security').checked = false;
    document.getElementById('addAdminErr').style.display = 'none';
    document.getElementById('addAdminModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('aa_username').focus(), 150);
}
function closeAddAdminModal() {
    const m = document.getElementById('addAdminModal');
    if (m) { m.classList.remove('active'); document.body.style.overflow = ''; }
}
function submitAddAdmin() {
    const username = document.getElementById('aa_username').value.trim();
    const password = document.getElementById('aa_password').value;
    const errEl    = document.getElementById('addAdminErr');
    if (!username || !password) {
        errEl.textContent = 'Username and password are required.';
        errEl.style.display = 'block'; return;
    }
    const perms = [];
    ['web','seo','smm','automation','security'].forEach(t => { if (document.getElementById('perm_'+t).checked) perms.push(t); });
    const btn = document.getElementById('addAdminSubmitBtn');
    btn.disabled=true; btn.innerHTML='<span class="btn-spinner"></span> Creating...';
    const fd = new FormData();
    fd.append('action','add_admin');
    fd.append('username', username);
    fd.append('password', password);
    fd.append('email', document.getElementById('aa_email').value.trim());
    perms.forEach(p => fd.append('permissions[]', p));
    fetch('includes/ajax_handler.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
        btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-user-plus"></i> Create Sub-Admin';
        if (data.success) { closeAddAdminModal(); showToastMsg(data.message || 'Sub-admin created.'); setTimeout(()=>location.reload(),1200); }
        else { errEl.textContent = data.error||'Failed to create admin.'; errEl.style.display='block'; }
    }).catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="fa-solid fa-user-plus"></i> Create Sub-Admin'; });
}

// ── Utility functions ─────────────────────────────────────────────────────────
function showToastMsg(msg) {
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast'; toast.className = 'toast-msg';
        document.body.appendChild(toast);
    }
    toast.innerHTML = `<span><i class="fa-solid fa-circle-check" style="margin-right:8px;"></i>${escapeHtml(msg)}</span><button onclick="this.parentElement.style.display='none'">&times;</button>`;
    toast.style.display = 'flex';
    setTimeout(() => { if (toast) toast.style.display='none'; }, 4000);
}
function escapeHtml(text) {
    if (!text) return '';
    return text.toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function escapeAttr(text) {
    if (!text) return '';
    return text.toString().replace(/'/g,"\\'");
}
function filterTable(input, tableId) {
    const filter = input.value.toLowerCase();
    const table  = document.getElementById(tableId);
    if (!table) return;
    const trs    = table.getElementsByTagName('tr');
    for (let i=1;i<trs.length;i++) {
        if (trs[i].querySelector('.no-leads')) continue;
        let match = false;
        const tds = trs[i].getElementsByTagName('td');
        for (let j=0;j<tds.length;j++) { if (tds[j].textContent.toLowerCase().indexOf(filter)>-1) { match=true; break; } }
        trs[i].style.display = match ? '' : 'none';
    }
}
function togglePasswordVisibility(inputId, btnEl) {
    const input = document.getElementById(inputId);
    const icon  = btnEl.querySelector('i');
    if (input.type === 'password') { input.type='text'; icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
    else { input.type='password'; icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
}

// ── Mobile sidebar ────────────────────────────────────────────────────────────
(function () {
    const hamburger = document.getElementById('hamburgerBtn');
    const mobileSb  = document.getElementById('mobileSidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    function openSidebar()  { if(mobileSb) mobileSb.classList.add('msb-open'); if(overlay) overlay.classList.add('active'); document.body.style.overflow='hidden'; }
    function closeSidebar() { if(mobileSb) mobileSb.classList.remove('msb-open'); if(overlay) overlay.classList.remove('active'); document.body.style.overflow=''; }
    if (hamburger) hamburger.addEventListener('click', openSidebar);
    if (overlay)   overlay.addEventListener('click', closeSidebar);
    if (mobileSb)  mobileSb.querySelectorAll('.msb-link').forEach(l => l.addEventListener('click', closeSidebar));
})();

window.addEventListener('DOMContentLoaded', () => {
    const list = [
        {id: 'w_lead_fltr', table: 'webTable'},
        {id: 's_lead_fltr', table: 'seoTable'},
        {id: 'm_lead_fltr', table: 'smmTable'},
        {id: 'a_lead_fltr', table: 'automationTable'},
        {id: 'asg_lead_fltr', table: 'assignmentsTable'}
    ];
    list.forEach(item => {
        const input = document.getElementById(item.id);
        if (input) {
            input.value = '';
            filterTable(input, item.table);
        }
    });
});
</script>

</body>
</html>
