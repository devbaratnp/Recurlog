function initRouter() {
  var page = window.location.pathname.split('/').pop() || 'login.html';
  var authed = localStorage.getItem('fscrm_auth') === 'true';

  if (page !== 'login.html' && !authed) {
    navigateTo('login.html');
    return;
  }

  if (page === 'login.html' && authed) {
    navigateTo('dashboard.html');
    return;
  }

  highlightActiveNav();

  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
}

function navigateTo(path) {
  window.location.href = path;
}

function goToCustomer(id) {
  localStorage.setItem('fscrm_currentCustomerId', String(id));
  navigateTo('customer-detail.html');
}

function goToStaff(id) {
  localStorage.setItem('fscrm_currentStaffId', String(id));
  navigateTo('staff-detail.html');
}

function goToTask(id) {
  localStorage.setItem('fscrm_currentTaskId', String(id));
  navigateTo('tasks.html?task=' + id);
}

function goToService(id) {
  localStorage.setItem('fscrm_currentServiceId', String(id));
  navigateTo('customer-detail.html');
}

function highlightActiveNav() {
  var currentPath = window.location.pathname.split('/').pop() || 'index.html';

  document.querySelectorAll('.sidebar-nav-link').forEach(function (link) {
    var href = link.getAttribute('href');
    if (!href) return;
    var linkPage = href.split('/').pop().split('?')[0];
    if (linkPage === currentPath) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  });

  document.querySelectorAll('.bottom-nav a').forEach(function (link) {
    var href = link.getAttribute('href');
    if (!href) return;
    var linkPage = href.split('/').pop().split('?')[0];
    if (linkPage === currentPath) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  });
}

function logout() {
  localStorage.removeItem('fscrm_auth');
  navigateTo('login.html');
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
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
    }, 350);
  }, duration);
}

window.initRouter = initRouter;
window.navigateTo = navigateTo;
window.goToCustomer = goToCustomer;
window.goToStaff = goToStaff;
window.goToTask = goToTask;
window.goToService = goToService;
window.highlightActiveNav = highlightActiveNav;
window.logout = logout;
window.showLoadingSkeleton = showLoadingSkeleton;
