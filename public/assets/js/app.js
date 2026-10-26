/**
 * Veen Africa HMS - Universal UI Enhancements
 * 1. CSRF Token Injection on Fetch
 * 2. Searchable Select Dropdowns across all views
 * 3. Instant On-Keyup Table Filtering
 * 4. Bidirectional DOB <-> Age Auto-calculation
 * 5. Universal Patient Autocomplete Search with Age & National ID
 */

// ----------------------------------------------------
// GLOBAL CSRF & AJAX FETCH INTERCEPTOR
// ----------------------------------------------------
if (window.fetch) {
  const _origFetch = window.fetch;
  window.fetch = function(resource, init) {
    init = init || {};
    init.headers = init.headers || {};
    const token = window.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    if (init.headers instanceof Headers) {
      if (!init.headers.has('X-Requested-With')) init.headers.append('X-Requested-With', 'XMLHttpRequest');
      if (token && !init.headers.has('X-CSRF-TOKEN') && init.method && init.method.toUpperCase() !== 'GET' && init.method.toUpperCase() !== 'HEAD') {
        init.headers.append('X-CSRF-TOKEN', token);
      }
    } else if (Array.isArray(init.headers)) {
      if (!init.headers.some(h => h[0].toLowerCase() === 'x-requested-with')) {
        init.headers.push(['X-Requested-With', 'XMLHttpRequest']);
      }
      if (token && !init.headers.some(h => h[0].toLowerCase() === 'x-csrf-token') && init.method && init.method.toUpperCase() !== 'GET' && init.method.toUpperCase() !== 'HEAD') {
        init.headers.push(['X-CSRF-TOKEN', token]);
      }
    } else {
      if (!init.headers['X-Requested-With']) init.headers['X-Requested-With'] = 'XMLHttpRequest';
      if (token && !init.headers['X-CSRF-TOKEN'] && init.method && init.method.toUpperCase() !== 'GET' && init.method.toUpperCase() !== 'HEAD') {
        init.headers['X-CSRF-TOKEN'] = token;
      }
    }
    return _origFetch.call(this, resource, init);
  };
}

// ----------------------------------------------------
// 1. UNIVERSAL SEARCHABLE SELECT DROPDOWNS
// ----------------------------------------------------
function initSearchableSelects(root = document) {
  const selects = root.querySelectorAll('select:not(.no-search):not([data-searchable-init])');
  
  selects.forEach(select => {
    // Skip hidden selects or custom file inputs
    if (select.closest('.custom-select-wrap')) return;
    select.setAttribute('data-searchable-init', 'true');

    // Create custom wrapper
    const wrap = document.createElement('div');
    wrap.className = 'custom-select-wrap';
    wrap.style.position = 'relative';
    wrap.style.width = '100%';

    // Hide original select visually but keep it accessible for forms
    select.style.display = 'none';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);

    // Selected display button
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = select.className || 'input';
    btn.style.display = 'flex';
    btn.style.justifyContent = 'space-between';
    btn.style.alignItems = 'center';
    btn.style.textAlign = 'left';
    btn.style.cursor = 'pointer';
    btn.style.width = '100%';
    btn.style.boxSizing = 'border-box';

    const getSelectedText = () => {
      const opt = select.options[select.selectedIndex];
      return opt ? opt.text : (select.getAttribute('placeholder') || 'Select option...');
    };

    btn.innerHTML = `
      <span class="cs-label" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-right:8px;">${getSelectedText()}</span>
      <svg style="width:14px; height:14px; opacity:0.6; flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    `;
    wrap.appendChild(btn);

    // Dropdown panel
    const panel = document.createElement('div');
    panel.className = 'custom-select-panel';
    panel.style.display = 'none';
    panel.style.position = 'absolute';
    panel.style.top = '100%';
    panel.style.left = '0';
    panel.style.right = '0';
    panel.style.zIndex = '9999';
    panel.style.background = 'var(--surface-solid, #111827)';
    panel.style.backgroundColor = 'var(--surface-solid, #111827)';
    panel.style.border = '1px solid var(--border-strong, #374151)';
    panel.style.borderRadius = '8px';
    panel.style.boxShadow = '0 16px 40px rgba(0,0,0,0.85)';
    panel.style.marginTop = '4px';
    panel.style.padding = '8px';
    panel.style.boxSizing = 'border-box';

    // Search input inside panel
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'input';
    searchInput.placeholder = '🔍 Search options...';
    searchInput.style.width = '100%';
    searchInput.style.fontSize = '12px';
    searchInput.style.padding = '6px 10px';
    searchInput.style.marginBottom = '6px';
    searchInput.style.boxSizing = 'border-box';
    panel.appendChild(searchInput);

    // Options list container
    const list = document.createElement('div');
    list.style.maxHeight = '200px';
    list.style.overflowY = 'auto';
    panel.appendChild(list);

    function renderOptions(filter = '') {
      list.innerHTML = '';
      const filterLower = filter.toLowerCase().trim();
      let matches = 0;

      Array.from(select.options).forEach((opt, idx) => {
        const text = opt.text;
        const val = opt.value;
        if (filterLower && !text.toLowerCase().includes(filterLower)) {
          return;
        }
        matches++;
        const item = document.createElement('div');
        item.style.padding = '7px 10px';
        item.style.fontSize = '13px';
        item.style.borderRadius = '6px';
        item.style.cursor = 'pointer';
        item.style.display = 'flex';
        item.style.justifyContent = 'space-between';
        item.style.alignItems = 'center';
        item.style.background = select.selectedIndex === idx ? 'rgba(20, 184, 166, 0.15)' : 'transparent';
        item.style.color = select.selectedIndex === idx ? 'var(--brand, #14b8a6)' : 'inherit';
        item.textContent = text;

        item.onmouseover = () => { if (select.selectedIndex !== idx) item.style.background = 'rgba(255,255,255,0.05)'; };
        item.onmouseout = () => { if (select.selectedIndex !== idx) item.style.background = 'transparent'; };

        item.onclick = (e) => {
          e.stopPropagation();
          select.selectedIndex = idx;
          btn.querySelector('.cs-label').textContent = text;
          panel.style.display = 'none';
          // Trigger native onchange
          select.dispatchEvent(new Event('change', { bubbles: true }));
        };
        list.appendChild(item);
      });

      if (matches === 0) {
        list.innerHTML = '<div style="padding:10px; font-size:12px; color:var(--muted); text-align:center;">No matching options</div>';
      }
    }

    renderOptions();

    // Toggle dropdown
    btn.onclick = (e) => {
      e.stopPropagation();
      const isOpen = panel.style.display === 'block';
      // Close other dropdowns
      document.querySelectorAll('.custom-select-panel').forEach(p => p.style.display = 'none');
      document.querySelectorAll('.custom-select-wrap').forEach(w => w.style.zIndex = '');
      document.querySelectorAll('.card').forEach(c => { if (c.style.zIndex === '99998') c.style.zIndex = ''; });
      if (!isOpen) {
        renderOptions();
        panel.style.display = 'block';
        wrap.style.zIndex = '99999';
        wrap.style.position = 'relative';
        const cardParent = wrap.closest('.card');
        if (cardParent) {
          cardParent.style.zIndex = '99998';
          cardParent.style.position = 'relative';
        }
        searchInput.value = '';
        setTimeout(() => searchInput.focus(), 50);
      }
    };

    searchInput.onclick = (e) => e.stopPropagation();
    searchInput.oninput = (e) => renderOptions(e.target.value);

    wrap.appendChild(panel);
  });
}

// Close custom select dropdowns when clicking outside
document.addEventListener('click', () => {
  document.querySelectorAll('.custom-select-panel').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.custom-select-wrap').forEach(w => w.style.zIndex = '');
  document.querySelectorAll('.card').forEach(c => { if (c.style.zIndex === '99998') c.style.zIndex = ''; });
});

// ----------------------------------------------------
// 2. UNIVERSAL REAL-TIME TABLE FILTER ON KEY UP & CSV EXPORT
// ----------------------------------------------------
window.tableSearch = function(tableId, query) {
  let targetId = tableId;
  let qStr = query;
  // Handle swapped args if called as tableSearch(query, tableId)
  if (typeof query === 'string' && (document.getElementById(query) || document.querySelector(query))) {
    targetId = query;
    qStr = tableId;
  }
  const q = (typeof qStr === 'string' ? qStr : (qStr ? qStr.value : '')).toLowerCase().trim();
  const table = document.getElementById(targetId) || document.querySelector(targetId);
  if (!table) return;

  const rows = table.querySelectorAll('tbody tr');
  rows.forEach(row => {
    // Skip empty notices
    if (row.classList.contains('empty') || row.querySelector('td[colspan]')) return;
    const text = row.textContent.toLowerCase();
    row.style.display = (q === '' || text.includes(q)) ? '' : 'none';
  });
};

window.filterTable = function(inputOrTableId, targetTableIdOrQuery) {
  // Support both (input, tableId) and (tableId, query)
  if (typeof inputOrTableId === 'string' && (document.getElementById(inputOrTableId) || document.querySelector(inputOrTableId))) {
    window.tableSearch(inputOrTableId, targetTableIdOrQuery);
  } else {
    window.tableSearch(targetTableIdOrQuery, inputOrTableId);
  }
};

window.exportTableToCSV = function(tableId, filenamePrefix = 'export') {
  const table = document.getElementById(tableId) || document.querySelector(tableId);
  if (!table) {
    alert('Table not found for export.');
    return;
  }

  const rows = Array.from(table.querySelectorAll('tr'));
  if (rows.length === 0) {
    alert('No data rows to export.');
    return;
  }

  // Identify action column indices to exclude
  const headerCells = Array.from(rows[0].querySelectorAll('th, td'));
  const skipIndices = new Set();
  headerCells.forEach((th, idx) => {
    const txt = th.textContent.toLowerCase().trim();
    if (txt.includes('action') || txt === '' || th.classList.contains('no-export')) {
      skipIndices.add(idx);
    }
  });

  const csvLines = [];

  rows.forEach(row => {
    // Skip empty state notice rows
    if (row.classList.contains('empty') || row.querySelector('td[colspan]')) return;
    // Skip rows hidden by client search filters
    if (row.style.display === 'none') return;

    const cells = Array.from(row.querySelectorAll('th, td'));
    if (cells.length === 0) return;

    const line = cells
      .filter((_, idx) => !skipIndices.has(idx))
      .map(cell => {
        // Clone to safely remove hidden/icon elements
        const clone = cell.cloneNode(true);
        clone.querySelectorAll('button, svg, .no-export, style, script').forEach(el => el.remove());
        let text = clone.innerText || clone.textContent || '';
        text = text.replace(/[\r\n\t]+/g, ' ').trim();
        // Escape quotes
        return `"${text.replace(/"/g, '""')}"`;
      })
      .join(',');

    csvLines.push(line);
  });

  if (csvLines.length <= 1) {
    alert('No visible rows to export.');
    return;
  }

  const csvContent = '\uFEFF' + csvLines.join('\r\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const now = new Date().toISOString().slice(0, 10);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', `${filenamePrefix}_${now}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
};

// ----------------------------------------------------
// 3. BIDIRECTIONAL DOB <-> AGE AUTO-CALCULATOR
// ----------------------------------------------------
window.calculateAge = function(dobString) {
  if (!dobString) return '';
  const dob = new Date(dobString);
  if (isNaN(dob.getTime())) return '';
  const today = new Date();
  let years = today.getFullYear() - dob.getFullYear();
  let m = today.getMonth() - dob.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
    years--;
  }
  return years >= 0 ? years : 0;
};

window.estimateDobFromAge = function(ageNumber) {
  const age = parseInt(ageNumber, 10);
  if (isNaN(age) || age < 0) return '';
  const today = new Date();
  const birthYear = today.getFullYear() - age;
  return `${birthYear}-01-01`;
};

function initDobAgeAutoCalculators(root = document) {
  const dobInputs = root.querySelectorAll('input[type="date"][name="dob"], input[type="date"]#dob, input[type="date"].dob-calc');
  dobInputs.forEach(dobIn => {
    if (dobIn.getAttribute('data-dob-init')) return;
    dobIn.setAttribute('data-dob-init', 'true');

    // Find or create age input adjacent
    let ageIn = dobIn.form ? dobIn.form.querySelector('input[name="age"], input#age_calc') : null;
    
    // Auto-update age when DOB is picked
    dobIn.addEventListener('input', () => {
      const age = window.calculateAge(dobIn.value);
      if (ageIn) {
        ageIn.value = age !== '' ? age : '';
      }
    });

    // Auto-update DOB when Age is typed
    if (ageIn) {
      ageIn.addEventListener('input', () => {
        const est = window.estimateDobFromAge(ageIn.value);
        if (est && !dobIn.value) {
          dobIn.value = est;
        }
      });
    }
  });
}

// ----------------------------------------------------
// 4. UNIVERSAL PATIENT AUTOCOMPLETE SEARCH
// ----------------------------------------------------
window.attachPatientAutocomplete = function(inputEl, hiddenIdEl, resultsContainerEl, onSelect) {
  if (!inputEl) return;
  let timer = null;

  async function fetchPatients(q) {
    try {
      const baseUrl = window.PATIENTS_SEARCH_URL || (window.APP_BASE ? window.APP_BASE + '/patients/search' : '/patients/search');
      const url = baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q || '');
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      return await res.json();
    } catch (e) {
      console.error('Error fetching patients:', e);
      return [];
    }
  }

  function render(pts) {
    if (!resultsContainerEl) return;
    if (!pts || !pts.length) {
      resultsContainerEl.innerHTML = '<div style="padding:10px; font-size:12px; color:var(--muted); text-align:center;">No matching patients found</div>';
      resultsContainerEl.style.display = 'block';
      return;
    }
    resultsContainerEl.innerHTML = pts.map(p => `
      <div class="patient-search-card" style="padding:8px 12px; border-bottom:1px solid var(--border); cursor:pointer; display:flex; justify-content:space-between; align-items:center;" onclick="window._pickPatientAuto(${p.id}, '${escapeHtml(p.first_name + ' ' + p.last_name)}', '${p.mrn}', '${p.phone || ''}', '${p.national_id || ''}', ${p.age || 'null'})">
        <div>
          <strong style="font-size:14px;">${escapeHtml(p.first_name + ' ' + p.last_name)}</strong>
          <div class="dim mono" style="font-size:11px; margin-top:2px;">
            MRN: <span style="color:var(--brand); font-weight:700;">${p.mrn}</span>
            ${p.national_id ? ' • ID: ' + p.national_id : ''}
            ${p.age ? ' • ' + p.age + ' yrs' : ''}
            ${p.phone ? ' • Tel: ' + p.phone : ''}
          </div>
        </div>
        <span class="badge badge-brand">+ Pick</span>
      </div>
    `).join('');
    resultsContainerEl.style.display = 'block';
  }

  // Show recent patients on focus/click immediately
  inputEl.addEventListener('focus', async () => {
    const pts = await fetchPatients(inputEl.value.trim());
    render(pts);
  });
  inputEl.addEventListener('click', async () => {
    const pts = await fetchPatients(inputEl.value.trim());
    render(pts);
  });

  document.addEventListener('click', (e) => {
    if (resultsContainerEl && !inputEl.contains(e.target) && !resultsContainerEl.contains(e.target)) {
      resultsContainerEl.style.display = 'none';
    }
  });

  // Filter on key up
  inputEl.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
      const pts = await fetchPatients(inputEl.value.trim());
      render(pts);
    }, 100);
  });

  window._pickPatientAuto = function(id, name, mrn, phone, nationalId, age) {
    if (hiddenIdEl) hiddenIdEl.value = id;
    inputEl.value = `${name} (${mrn})`;
    if (resultsContainerEl) resultsContainerEl.style.display = 'none';
    if (typeof onSelect === 'function') {
      onSelect({ id, name, mrn, phone, nationalId, age });
    }
  };
};

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// ----------------------------------------------------
// 5. UNIVERSAL PASSWORD VISIBILITY EYE TOGGLE
// ----------------------------------------------------
window.togglePasswordVisibility = function(inputIdOrEl, btn) {
  const input = typeof inputIdOrEl === 'string' ? document.getElementById(inputIdOrEl) : inputIdOrEl;
  if (!input) return;
  const isPassword = input.type === 'password';
  input.type = isPassword ? 'text' : 'password';

  const eyeOpen = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
  const eyeSlash = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>`;

  if (btn) {
    btn.innerHTML = isPassword ? eyeSlash : eyeOpen;
    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    btn.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
  }
};

function initPasswordToggles(root = document) {
  const inputs = root.querySelectorAll('input[type="password"]:not([data-no-toggle]):not([data-password-toggle-init])');
  inputs.forEach(input => {
    input.setAttribute('data-password-toggle-init', 'true');
    let wrap = input.closest('.password-field-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'password-field-wrap';
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);
    }
    if (!wrap.querySelector('.password-toggle-btn')) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'password-toggle-btn';
      btn.setAttribute('aria-label', 'Show password');
      btn.setAttribute('title', 'Show password');
      btn.innerHTML = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 18px; height: 18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        window.togglePasswordVisibility(input, btn);
      });
      wrap.appendChild(btn);
    }
  });
}

// Auto-initialize on DOM ready and observe dynamic modals
document.addEventListener('DOMContentLoaded', () => {
  initSearchableSelects();
  initDobAgeAutoCalculators();
  initPasswordToggles();

  // MutationObserver to auto-upgrade newly opened modals
  const observer = new MutationObserver(() => {
    initSearchableSelects();
    initDobAgeAutoCalculators();
    initPasswordToggles();
  });
  observer.observe(document.body, { childList: true, subtree: true });
});
