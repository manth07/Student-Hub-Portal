/**
 * ==========================================================
 * StudentHub Portal - Asynchronous AJAX Event Registration Controller
 * Practical 13: JavaScript Fetch API, Dynamic DOM & Live JSON API Console
 * ==========================================================
 */

let allRegistrations = [];

document.addEventListener('DOMContentLoaded', () => {
  // Initial asynchronous data loading
  loadStats();
  loadDropdowns();
  loadRegistrations();

  // Attach event listeners
  const form = document.getElementById('ajax-enroll-form');
  if (form) {
    form.addEventListener('submit', handleEnrollSubmit);
  }

  const searchInput = document.getElementById('reg-search');
  if (searchInput) {
    searchInput.addEventListener('input', renderRegistrationsTable);
  }

  const catSelect = document.getElementById('reg-category');
  if (catSelect) {
    catSelect.addEventListener('change', renderRegistrationsTable);
  }

  const refreshBtn = document.getElementById('refresh-btn');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', () => {
      loadStats();
      loadRegistrations();
      showAlert('Refreshed data directly from /studenthub/php/api.php.', 'info');
    });
  }

  // Collapsible JSON Inspector toggle
  const toggleBtn = document.getElementById('inspector-toggle-btn');
  const inspectorHeader = document.getElementById('inspector-header');
  const inspectorBody = document.getElementById('inspector-body');

  function toggleInspector() {
    if (inspectorBody.style.display === 'none') {
      inspectorBody.style.display = 'block';
      toggleBtn.textContent = 'Hide Inspector';
    } else {
      inspectorBody.style.display = 'none';
      toggleBtn.textContent = 'Show Inspector';
    }
  }

  if (toggleBtn) toggleBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleInspector(); });
  if (inspectorHeader) inspectorHeader.addEventListener('click', toggleInspector);
});

/**
 * 1. Fetch live KPI stats from php/api.php?action=stats
 */
function loadStats() {
  fetch('/studenthub/php/api.php?action=stats', { cache: 'no-store' })
    .then(res => res.json())
    .then(res => {
      updateJsonInspector('GET /studenthub/php/api.php?action=stats', res);

      if (res.status === 'success' && res.data) {
        const d = res.data;
        document.getElementById('kpi-total-registrations').textContent = d.total_registrations;
        document.getElementById('kpi-open-events').textContent = d.open_events;
        document.getElementById('kpi-total-students').textContent = d.total_students;
        document.getElementById('kpi-api-status').textContent = res.api_status;
        document.getElementById('ajax-status-badge').textContent = 'API Connected (HTTP 200)';
      }
    })
    .catch(err => {
      console.error('Failed to load stats:', err);
      document.getElementById('kpi-api-status').textContent = 'Error connecting to API';
      document.getElementById('ajax-status-badge').textContent = 'API Offline';
      document.getElementById('ajax-status-badge').className = 'badge badge-closed';
    });
}

/**
 * 2. Fetch students & events to populate form dropdowns
 */
function loadDropdowns() {
  // Populate students dropdown
  fetch('/studenthub/php/api.php?action=list_students', { cache: 'no-store' })
    .then(res => res.json())
    .then(res => {
      const studentSelect = document.getElementById('enroll-student');
      if (res.status === 'success' && res.data) {
        studentSelect.innerHTML = '<option value="">-- Choose Student --</option>' +
          res.data.map(s => `<option value="${s.id}">${s.enrollment_no} - ${s.name} (${s.course})</option>`).join('');
      } else {
        studentSelect.innerHTML = '<option value="">Failed to load students</option>';
      }
    })
    .catch(err => console.error('Error loading students dropdown:', err));

  // Populate events dropdown
  fetch('/studenthub/php/api.php?action=list_events', { cache: 'no-store' })
    .then(res => res.json())
    .then(res => {
      const eventSelect = document.getElementById('enroll-event');
      if (res.status === 'success' && res.data) {
        eventSelect.innerHTML = '<option value="">-- Choose Campus Event --</option>' +
          res.data.map(e => `<option value="${e.id}">${e.title} [${e.category}] (${e.event_date})</option>`).join('');
      } else {
        eventSelect.innerHTML = '<option value="">Failed to load events</option>';
      }
    })
    .catch(err => console.error('Error loading events dropdown:', err));
}

/**
 * 3. Fetch all registrations from php/api.php?action=list_registrations
 */
function loadRegistrations() {
  const tbody = document.getElementById('registrations-tbody');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding: 20px;">Fetching registrations...</td></tr>';

  fetch('/studenthub/php/api.php?action=list_registrations', { cache: 'no-store' })
    .then(res => res.json())
    .then(res => {
      updateJsonInspector('GET /studenthub/php/api.php?action=list_registrations', res);

      if (res.status === 'success' && Array.isArray(res.data)) {
        allRegistrations = res.data;
        renderRegistrationsTable();
      } else {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error: ${res.message || 'Unable to load data'}</td></tr>`;
      }
    })
    .catch(err => {
      console.error('Failed to load registrations:', err);
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Connection error while fetching from /studenthub/php/api.php.</td></tr>';
    });
}

/**
 * 4. Render and filter registrations table dynamically
 */
function renderRegistrationsTable() {
  const tbody = document.getElementById('registrations-tbody');
  const searchVal = (document.getElementById('reg-search').value || '').toLowerCase().trim();
  const categoryVal = document.getElementById('reg-category').value;

  const filtered = allRegistrations.filter(r => {
    const matchesSearch = !searchVal ||
      (r.student_name && r.student_name.toLowerCase().includes(searchVal)) ||
      (r.enrollment_no && r.enrollment_no.toLowerCase().includes(searchVal)) ||
      (r.event_title && r.event_title.toLowerCase().includes(searchVal)) ||
      (r.venue && r.venue.toLowerCase().includes(searchVal));

    const matchesCat = (categoryVal === 'All') || (r.event_category === categoryVal);
    return matchesSearch && matchesCat;
  });

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding: 30px;">No event registration records match the current filter.</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map((r, idx) => {
    const catClass = (r.event_category || '').toLowerCase();
    const roleClass = 'badge-' + (r.role || 'participant').toLowerCase();

    return `
      <tr id="reg-row-${r.id}">
        <td>${idx + 1}</td>
        <td>
          <strong>${escapeHtml(r.student_name)}</strong>
          <div style="font-size:0.8rem; color:var(--text-muted);">${escapeHtml(r.enrollment_no)} (${escapeHtml(r.course)})</div>
          ${r.notes ? `<small style="color:var(--primary-color);">Note: ${escapeHtml(r.notes)}</small>` : ''}
        </td>
        <td>
          <div style="font-weight:600;">${escapeHtml(r.event_title)}</div>
          <span class="badge badge-${catClass}">${escapeHtml(r.event_category)}</span>
        </td>
        <td>
          <div>${escapeHtml(r.event_date)}</div>
          <small class="text-muted">${escapeHtml(r.venue)}</small>
        </td>
        <td>
          <div style="display:flex; align-items:center; gap:6px;">
            <select id="role-select-${r.id}" class="filter-select" style="padding:4px 6px; font-size:0.82rem; min-width:115px;">
              <option value="Participant" ${r.role === 'Participant' ? 'selected' : ''}>Participant</option>
              <option value="Volunteer" ${r.role === 'Volunteer' ? 'selected' : ''}>Volunteer</option>
              <option value="Coordinator" ${r.role === 'Coordinator' ? 'selected' : ''}>Coordinator</option>
              <option value="Speaker" ${r.role === 'Speaker' ? 'selected' : ''}>Speaker</option>
            </select>
            <button class="btn btn-outline btn-sm" style="padding:3px 8px; font-size:0.78rem;" onclick="updateRegistrationRole(${r.id})" type="button">
              Update
            </button>
          </div>
        </td>
        <td>
          <button class="btn btn-danger btn-sm" style="padding:4px 10px; font-size:0.8rem;" onclick="deleteRegistration(${r.id}, '${escapeJs(r.student_name)}', '${escapeJs(r.event_title)}')" type="button">
            Remove
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

/**
 * 5. Handle AJAX POST Submission to enroll student
 */
function handleEnrollSubmit(e) {
  e.preventDefault();

  const studentSelect = document.getElementById('enroll-student');
  const eventSelect = document.getElementById('enroll-event');
  const roleSelect = document.getElementById('enroll-role');
  const notesInput = document.getElementById('enroll-notes');
  const submitBtn = document.getElementById('enroll-submit-btn');

  if (!studentSelect.value || !eventSelect.value) {
    showAlert('Please select both a student and an event from the dropdowns.', 'danger');
    return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Enrolling Student...';

  const formData = new FormData();
  formData.append('student_id', studentSelect.value);
  formData.append('event_id', eventSelect.value);
  formData.append('role', roleSelect.value);
  formData.append('notes', notesInput.value);

  fetch('/studenthub/php/api.php?action=add_registration', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(res => {
      updateJsonInspector('POST /studenthub/php/api.php?action=add_registration', res);

      if (res.status === 'success') {
        showAlert(res.message, 'success');
        studentSelect.value = '';
        eventSelect.value = '';
        notesInput.value = '';
        roleSelect.value = 'Participant';

        // Live update table and stats without full reload
        loadStats();
        loadRegistrations();
      } else {
        showAlert(res.message, 'danger');
      }
    })
    .catch(err => {
      console.error('AJAX add registration error:', err);
      showAlert('Server error during AJAX registration submission.', 'danger');
    })
    .finally(() => {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Register for Event (AJAX POST)';
    });
}

/**
 * 6. Inline AJAX Update for Participation Role
 */
function updateRegistrationRole(regId) {
  const roleSelect = document.getElementById(`role-select-${regId}`);
  if (!roleSelect) return;

  const newRole = roleSelect.value;
  const formData = new FormData();
  formData.append('id', regId);
  formData.append('role', newRole);

  fetch('/studenthub/php/api.php?action=update_role', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(res => {
      updateJsonInspector(`POST /studenthub/php/api.php?action=update_role (ID: ${regId})`, res);

      if (res.status === 'success') {
        showAlert(res.message, 'success');
        // Update local array and refresh view
        const item = allRegistrations.find(r => r.id == regId);
        if (item) item.role = newRole;
        renderRegistrationsTable();
      } else {
        showAlert(res.message, 'danger');
      }
    })
    .catch(err => {
      console.error('AJAX update role error:', err);
      showAlert('Error updating role via AJAX.', 'danger');
    });
}

/**
 * 7. Smooth AJAX Delete Registration Row
 */
function deleteRegistration(regId, studentName, eventTitle) {
  const confirmed = confirm(`Are you sure you want to remove the registration of "${studentName}" from "${eventTitle}"?`);
  if (!confirmed) return;

  const formData = new FormData();
  formData.append('id', regId);

  fetch('/studenthub/php/api.php?action=delete_registration', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(res => {
      updateJsonInspector(`POST /studenthub/php/api.php?action=delete_registration (ID: ${regId})`, res);

      if (res.status === 'success') {
        showAlert(res.message, 'success');
        // Smoothly fade out row
        const row = document.getElementById(`reg-row-${regId}`);
        if (row) {
          row.style.transition = 'opacity 0.3s ease';
          row.style.opacity = '0';
          setTimeout(() => {
            allRegistrations = allRegistrations.filter(r => r.id != regId);
            renderRegistrationsTable();
            loadStats();
          }, 300);
        } else {
          allRegistrations = allRegistrations.filter(r => r.id != regId);
          renderRegistrationsTable();
          loadStats();
        }
      } else {
        showAlert(res.message, 'danger');
      }
    })
    .catch(err => {
      console.error('AJAX delete error:', err);
      showAlert('Failed to remove registration via AJAX.', 'danger');
    });
}

/**
 * Helper: Updates the Live JSON Inspector box
 */
function updateJsonInspector(endpointInfo, jsonPayload) {
  const inspector = document.getElementById('json-inspector');
  if (inspector) {
    const timestamp = new Date().toLocaleTimeString();
    inspector.textContent = `// [${timestamp}] Request: ${endpointInfo}\n` + JSON.stringify(jsonPayload, null, 2);
  }
}

/**
 * Helper: Shows alert message at top of main panel
 */
function showAlert(message, type) {
  const box = document.getElementById('ajax-alert');
  if (!box) return;

  box.className = `alert alert-${type}`;
  box.textContent = message;
  box.style.display = 'block';

  // Scroll smoothly to alert if out of view
  box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  // Auto-dismiss after 5 seconds for success/info alerts
  if (type === 'success' || type === 'info') {
    setTimeout(() => {
      box.style.display = 'none';
    }, 5000);
  }
}

/**
 * Security sanitizers
 */
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function escapeJs(str) {
  if (!str) return '';
  return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
}
