// ========== HELPERS ==========

function todayISO() {
  var d = new Date();
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function tomorrowISO() {
  var d = new Date();
  d.setDate(d.getDate() + 1);
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}
window.tomorrowISO = tomorrowISO;

function formatDateISO(date) {
  if (!date) return '';
  var d = typeof date === 'string' ? new Date(date + 'T00:00:00') : new Date(date);
  if (isNaN(d.getTime())) return '';
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function addToDate(dateStr, value, unit) {
  var d = new Date(dateStr + 'T00:00:00');
  if (isNaN(d.getTime())) return null;
  switch (unit) {
    case 'days': d.setDate(d.getDate() + value); break;
    case 'weeks': d.setDate(d.getDate() + value * 7); break;
    case 'months': d.setMonth(d.getMonth() + value); break;
    case 'years': d.setFullYear(d.getFullYear() + value); break;
  }
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

// ========== RECURRENCE ENGINE ==========

window.getNextDueDate = function(service, lastCompletedDate, previousScheduledDate) {
  if (!service || !service.recurrence) return null;
  var rec = service.recurrence;
  var baseDate;
  if (rec.repeatFrom === 'last-done' && lastCompletedDate) {
    baseDate = lastCompletedDate;
  } else if (rec.repeatFrom === 'fixed-schedule' && previousScheduledDate) {
    baseDate = previousScheduledDate;
  } else {
    baseDate = lastCompletedDate || previousScheduledDate || todayISO();
  }
  return addToDate(baseDate, rec.value, rec.unit);
};

// ========== SIGNATURE COMPRESSION ==========

window.compressSignature = function(base64Data, quality, maxWidth) {
  quality = quality || 0.6;
  maxWidth = maxWidth || 400;
  return new Promise(function(resolve) {
    var img = new Image();
    img.onload = function() {
      var canvas = document.createElement('canvas');
      var w = img.width;
      var h = img.height;
      if (w > maxWidth) {
        h = h * (maxWidth / w);
        w = maxWidth;
      }
      canvas.width = w;
      canvas.height = h;
      var ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, w, h);
      ctx.drawImage(img, 0, 0, w, h);
      resolve(canvas.toDataURL('image/jpeg', quality));
    };
    img.src = base64Data;
  });
};

// ========== UI HELPERS ==========

window.showToast = function(message, type) {
  type = type || 'info';
  var container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    container.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:360px;width:100%;pointer-events:none';
    document.body.appendChild(container);
  }
  var borderColor = type === 'success' ? '#1DB954' : type === 'error' ? '#EF4444' : '#3B82F6';
  var iconName = type === 'success' ? 'check-circle' : type === 'error' ? 'alert-circle' : 'info';
  var toast = document.createElement('div');
  toast.className = 'toast toast-' + type;
  toast.style.cssText = 'background:white;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.12);border-left:4px solid ' + borderColor + ';padding:12px 16px;display:flex;align-items:center;gap:10px;animation:slideIn 0.25s ease-out;pointer-events:auto;font-size:14px';
  toast.innerHTML = '<i data-lucide="' + iconName + '" class="w-5 h-5" style="color:' + borderColor + ';flex-shrink:0"></i><span style="flex:1">' + message + '</span>';
  container.appendChild(toast);
  try { lucide.createIcons(); } catch(e) {}
  setTimeout(function() {
    toast.style.animation = 'slideOut 0.25s ease-in forwards';
    setTimeout(function() {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 250);
  }, 2500);
  var toasts = container.querySelectorAll('.toast');
  if (toasts.length > 3) container.removeChild(toasts[0]);
};

window.renderStatusPill = function(status) {
  var configs = {
    pending: { bg: '#FEF3C7', text: '#92400E', icon: 'clock', label: 'Pending' },
    completed: { bg: '#D1FAE5', text: '#065F46', icon: 'check-circle', label: 'Completed' },
    missed: { bg: '#FEE2E2', text: '#991B1B', icon: 'alert-circle', label: 'Missed' }
  };
  var cfg = configs[status] || configs.pending;
  return '<span class="status-pill" style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:500;background:' + cfg.bg + ';color:' + cfg.text + '"><i data-lucide="' + cfg.icon + '" class="w-3 h-3"></i> ' + cfg.label + '</span>';
};

window.formatDate = function(date) {
  if (!date) return '';
  var d;
  if (typeof date === 'string') {
    d = date.indexOf('T') >= 0 ? new Date(date) : new Date(date + 'T00:00:00');
  } else {
    d = new Date(date);
  }
  if (isNaN(d.getTime())) return '';
  var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
};

window.formatRelative = function(date) {
  if (!date) return '';
  var d;
  if (typeof date === 'string') {
    d = date.indexOf('T') >= 0 ? new Date(date) : new Date(date + 'T00:00:00');
  } else {
    d = new Date(date);
  }
  if (isNaN(d.getTime())) return '';
  var now = new Date();
  now.setHours(0,0,0,0);
  d.setHours(0,0,0,0);
  var diff = Math.round((d - now) / 86400000);
  if (diff === 0) return 'Today';
  if (diff === -1) return 'Yesterday';
  if (diff === 1) return 'Tomorrow';
  if (diff > 1 && diff <= 7) return 'In ' + diff + ' days';
  if (diff < 0 && diff >= -7) return Math.abs(diff) + ' days ago';
  return window.formatDate(date);
};

// ========== NAVIGATION ==========

function navigateTo(path) {
  window.location.href = path;
}

function goToCustomer(id) {
  localStorage.setItem('fscrm_currentCustomerId', String(id));
  navigateTo('customer-detail.php?id=' + id);
}

function goToStaff(id) {
  localStorage.setItem('fscrm_currentStaffId', String(id));
  navigateTo('staff-detail.php');
}

function goToTask(id) {
  localStorage.setItem('fscrm_currentTaskId', String(id));
  navigateTo('tasks.php?task=' + id);
}

function goToService(id) {
  localStorage.setItem('fscrm_currentServiceId', String(id));
  navigateTo('customer-detail.php?id=' + id);
}

function showLoadingSkeleton(duration) {
  duration = duration || 200;
  if (document.querySelector('.skeleton-overlay')) return;
  var overlay = document.createElement('div');
  overlay.className = 'skeleton-overlay fixed inset-0 z-[100] bg-white p-4 animate-pulse';
  overlay.innerHTML =
    '<div class="max-w-4xl mx-auto space-y-4">' +
      '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">' +
        '<div class="h-24 bg-gray-200 rounded-xl"></div>' +
        '<div class="h-24 bg-gray-200 rounded-xl"></div>' +
        '<div class="h-24 bg-gray-200 rounded-xl"></div>' +
        '<div class="h-24 bg-gray-200 rounded-xl"></div>' +
      '</div>' +
      '<div class="space-y-3 mt-6">' +
        '<div class="h-16 bg-gray-200 rounded-xl"></div>' +
        '<div class="h-16 bg-gray-200 rounded-xl"></div>' +
        '<div class="h-16 bg-gray-200 rounded-xl"></div>' +
      '</div>' +
    '</div>';
  document.body.appendChild(overlay);
  setTimeout(function () {
    overlay.classList.remove('animate-pulse');
    overlay.style.transition = 'opacity 0.3s ease';
    overlay.style.opacity = '0';
    setTimeout(function () {
      if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
    }, 350);
  }, duration);
}

// ========== SEARCHABLE DROPDOWN ==========

window.buildSearchableDropdown = function(config) {
  var container = document.createElement('div');
  container.className = 'searchable-dropdown';
  var trigger = document.createElement('button');
  trigger.type = 'button';
  trigger.className = 'dropdown-trigger';
  trigger.innerHTML = '<span class="trigger-text trigger-placeholder">' + (config.placeholder || 'Select...') + '</span><i data-lucide="chevron-down" class="w-4 h-4" style="flex-shrink:0"></i>';
  container.appendChild(trigger);
  var menu = document.createElement('div');
  menu.className = 'dropdown-menu';
  menu.innerHTML = '<input type="text" class="dropdown-search" placeholder="' + (config.searchPlaceholder || 'Search...') + '"><div class="dropdown-options"></div>';
  container.appendChild(menu);
  var optionsContainer = menu.querySelector('.dropdown-options');
  var searchInput = menu.querySelector('.dropdown-search');
  function renderOptions(filter) {
    var q = (filter || '').toLowerCase().trim();
    var filtered = q ? config.options.filter(function(o) { return o.label.toLowerCase().indexOf(q) >= 0; }) : config.options;
    if (filtered.length === 0) { optionsContainer.innerHTML = '<div class="dropdown-empty">No results found</div>'; return; }
    optionsContainer.innerHTML = filtered.map(function(o) {
      var selected = config.selectedValue !== undefined && String(o.value) === String(config.selectedValue) ? ' selected' : '';
      return '<div class="dropdown-option' + selected + '" data-value="' + o.value + '"><span class="option-check"><i data-lucide="check" class="w-3 h-3"></i></span><span>' + o.label + '</span></div>';
    }).join('');
    optionsContainer.querySelectorAll('.dropdown-option').forEach(function(el) {
      el.addEventListener('click', function() {
        var val = this.dataset.value;
        var label = this.querySelector('span:last-child').textContent;
        optionsContainer.querySelectorAll('.dropdown-option').forEach(function(o) { o.classList.remove('selected'); });
        this.classList.add('selected');
        trigger.querySelector('.trigger-text').textContent = label;
        trigger.querySelector('.trigger-text').classList.remove('trigger-placeholder');
        menu.classList.remove('open');
        if (config.onChange) config.onChange(val, label);
      });
    });
    try { lucide.createIcons(); } catch(e) {}
  }
  trigger.addEventListener('click', function(e) {
    e.stopPropagation();
    var isOpen = menu.classList.contains('open');
    document.querySelectorAll('.searchable-dropdown .dropdown-menu.open').forEach(function(m) { if (m !== menu) m.classList.remove('open'); });
    menu.classList.toggle('open');
    if (!isOpen) { searchInput.value = ''; renderOptions(''); setTimeout(function() { searchInput.focus(); }, 50); }
  });
  searchInput.addEventListener('input', function() { renderOptions(this.value); });
  searchInput.addEventListener('keydown', function(e) { if (e.key === 'Escape') menu.classList.remove('open'); });
  document.addEventListener('click', function(e) { if (!container.contains(e.target)) menu.classList.remove('open'); });
  renderOptions('');
  container._setValue = function(val) {
    var option = config.options.find(function(o) { return String(o.value) === String(val); });
    if (option) {
      trigger.querySelector('.trigger-text').textContent = option.label;
      trigger.querySelector('.trigger-text').classList.remove('trigger-placeholder');
      optionsContainer.querySelectorAll('.dropdown-option').forEach(function(o) { o.classList.toggle('selected', String(o.dataset.value) === String(val)); });
    }
  };
  return container;
};

// ========== EXPORTS ==========

window.todayISO = todayISO;
window.formatDateISO = formatDateISO;
window.addToDate = addToDate;
window.navigateTo = navigateTo;
window.goToCustomer = goToCustomer;
window.goToStaff = goToStaff;
window.goToTask = goToTask;
window.goToService = goToService;
window.showLoadingSkeleton = showLoadingSkeleton;
