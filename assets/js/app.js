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
  var iconMap = { success: 'success', error: 'error', info: 'info', warning: 'warning' };
  Swal.fire({
    icon: iconMap[type] || 'info',
    title: message,
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true
  });
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

// ========== NOTIFICATION SOUND (Web Audio API) ==========

window.playNotificationSound = function() {
  try {
    var ctx = new (window.AudioContext || window.webkitAudioContext)();
    var oscillator = ctx.createOscillator();
    var gain = ctx.createGain();
    oscillator.connect(gain);
    gain.connect(ctx.destination);
    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(880, ctx.currentTime);
    oscillator.frequency.setValueAtTime(660, ctx.currentTime + 0.1);
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
    oscillator.start(ctx.currentTime);
    oscillator.stop(ctx.currentTime + 0.3);
  } catch(e) {}
};

// ========== WEB PUSH SETUP ==========

window.initWebPush = function(vapidPublicKey) {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
  if (Notification.permission !== 'granted') return;
  if (!vapidPublicKey) return;

  var swUrl = (window.__APP_BASE || '') + '/sw.js';
  navigator.serviceWorker.register(swUrl).then(function(reg) {
    return reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: base64UrlToUint8Array(vapidPublicKey)
    });
  }).then(function(sub) {
    fetch('../api/push_register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        platform: 'web',
        endpoint: sub.endpoint,
        p256dh: arrayBufferToBase64(sub.getKey('p256dh')),
        auth: arrayBufferToBase64(sub.getKey('auth')),
        deviceName: navigator.userAgent
      })
    });
  }).catch(function(err) {
    if (err.code === 20 && err.name === 'AbortError') return;
    console.warn('Web push registration:', err.message);
  });
};

// ========== STAFF REASSIGNMENT ==========

window.reassignStaff = function(config) {
  var entityType = config.entityType;
  var entityId = config.entityId;
  var currentStaffId = config.currentStaffId;
  var onSuccess = config.onSuccess || function() { window.location.reload(); };

  fetch('../api/staff.php')
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (!res.success || !res.data) throw new Error('Failed to load staff');
      var staffList = res.data;
      var currentStaff = staffList.find(function(s) { return String(s.id) === String(currentStaffId); });

      var modalHtml =
        '<div id="reassign-modal" class="modal-overlay" style="display:flex">' +
          '<div class="modal-content" onclick="event.stopPropagation()" style="max-width:400px">' +
            '<div class="flex items-center justify-between mb-4">' +
              '<h3 class="text-lg font-bold text-gray-900">Change Assignee</h3>' +
              '<button type="button" onclick="document.getElementById(\'reassign-modal\').remove()" class="text-gray-400 hover:text-gray-600 transition-colors">' +
                '<i data-lucide="x" class="w-5 h-5"></i>' +
              '</button>' +
            '</div>' +
            '<div class="mb-3 text-sm text-gray-500">' +
              'Current: <span class="font-medium text-gray-700">' + (currentStaff ? currentStaff.name : 'Unassigned') + '</span>' +
            '</div>' +
            '<div class="mb-4">' +
              '<label class="block text-sm font-semibold text-gray-700 mb-1.5">Assign To</label>' +
              '<select id="reassign-staff-select" class="form-select w-full">' +
                '<option value="">Unassigned</option>';
      staffList.forEach(function(s) {
        modalHtml += '<option value="' + s.id + '" ' + (String(s.id) === String(currentStaffId) ? 'selected' : '') + '>' + s.name + '</option>';
      });
      modalHtml +=
              '</select>' +
            '</div>' +
            '<div class="flex gap-3">' +
              '<button type="button" onclick="document.getElementById(\'reassign-modal\').remove()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>' +
              '<button type="button" id="reassign-confirm-btn" class="flex-1 px-4 py-2.5 bg-brand text-white text-sm font-semibold rounded-lg hover:bg-brand/90 transition-colors brand-glow">Save</button>' +
            '</div>' +
          '</div>' +
        '</div>';

      var wrapper = document.createElement('div');
      wrapper.innerHTML = modalHtml;
      document.body.appendChild(wrapper);
      try { lucide.createIcons(); } catch(e) {}

      document.getElementById('reassign-confirm-btn').addEventListener('click', function() {
        var newStaffId = document.getElementById('reassign-staff-select').value;
        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Saving...';

        fetch('../api/reassign.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('input[name=\'csrf_token\']')?.value || '' },
          body: JSON.stringify({
            entity_type: entityType,
            entity_id: entityId,
            new_assignee_id: newStaffId || null
          })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
          if (res.success) {
            window.showToast('Assignee changed successfully', 'success');
            document.getElementById('reassign-modal').remove();
            if (onSuccess) onSuccess(res.data);
          } else {
            window.showToast(res.error || 'Reassignment failed', 'error');
            btn.disabled = false;
            btn.textContent = 'Save';
          }
        })
        .catch(function() {
          window.showToast('Network error', 'error');
          btn.disabled = false;
          btn.textContent = 'Save';
        });
      });

      document.getElementById('reassign-modal').addEventListener('click', function(e) {
        if (e.target === this) this.remove();
      });
    })
    .catch(function(err) {
      window.showToast('Failed to load staff list', 'error');
    });
};

function base64UrlToUint8Array(base64Url) {
  var padding = '='.repeat((4 - base64Url.length % 4) % 4);
  var base64 = (base64Url + padding).replace(/-/g, '+').replace(/_/g, '/');
  var raw = atob(base64);
  var arr = new Uint8Array(raw.length);
  for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
  return arr;
}

function arrayBufferToBase64(buffer) {
  var binary = '';
  var bytes = new Uint8Array(buffer);
  for (var i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
  return btoa(binary);
}
