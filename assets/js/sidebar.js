(function () {
  'use strict';

  var SIDEBAR_STORAGE_KEY = 'fscrm_sidebar_collapsed';

  function getPageName() {
    return window.location.pathname.split('/').pop() || 'dashboard.html';
  }

  function sidebarLink(href, icon, label, match) {
    var page = getPageName();
    var active = page === match || (match === 'dashboard.html' && (page === '' || page === 'index.html'));
    var cls = active
      ? 'sidebar-nav-link active'
      : 'sidebar-nav-link';
    return '<a href="' + href + '" class="' + cls + '"><i data-lucide="' + icon + '"></i><span>' + label + '</span></a>';
  }

  function injectSidebar() {
    var page = getPageName();

    var collapsed = 'false';
    try {
      collapsed = localStorage.getItem(SIDEBAR_STORAGE_KEY) || 'false';
    } catch (e) {}

    document.write(
      '<div class="sidebar-backdrop" id="sidebar-backdrop"></div>' +
      '<aside class="sidebar' + (collapsed === 'true' ? ' collapsed' : '') + '" id="sidebar" tabindex="-1">' +
        '<div class="flex items-center justify-between px-5 py-4 border-b border-white/10">' +
          '<a href="dashboard.html" class="flex items-center gap-3" style="text-decoration:none">' +
            '<div class="w-9 h-9 bg-brand rounded-xl flex items-center justify-center shadow-lg shadow-brand/25">' +
              '<i data-lucide="wrench" class="w-5 h-5 text-white"></i>' +
            '</div>' +
            '<span class="sidebar-brand-name text-lg font-bold tracking-tight">Recurlog</span>' +
          '</a>' +
          '<div class="flex items-center gap-1">' +
            '<button class="sidebar-desktop-toggle sidebar-collapse-btn" id="sidebar-collapse-btn" aria-label="Toggle sidebar">' +
              '<i data-lucide="panel-left-close" class="w-4 h-4"></i>' +
            '</button>' +
            '<button class="sidebar-close-btn" id="sidebar-close-btn" aria-label="Close sidebar">' +
              '<i data-lucide="x" class="w-5 h-5"></i>' +
            '</button>' +
          '</div>' +
        '</div>' +
        '<nav class="sidebar-nav">' +
          sidebarLink('customers.html', 'users', 'Customer', 'customers.html') +
          sidebarLink('orders.html', 'clipboard-list', 'Order', 'orders.html') +
          sidebarLink('onetime-task.html', 'calendar-check', 'Onetime Task', 'onetime-task.html') +
          sidebarLink('recurring-task.html', 'repeat', 'Recurring Task', 'recurring-task.html') +
          sidebarLink('staff.html', 'briefcase', 'Staff', 'staff.html') +
          sidebarLink('daybook.html', 'book-open', 'Daybook', 'daybook.html') +
          sidebarLink('reports.html', 'bar-chart-3', 'Report', 'reports.html') +
          sidebarLink('notifications.html', 'bell', 'Notification', 'notifications.html') +
          sidebarLink('settings.html', 'settings', 'Setting', 'settings.html') +
        '</nav>' +
        '<div class="p-4 border-t border-white/10 flex flex-col gap-2">' +
          '<div class="flex items-center gap-3">' +
            '<div class="w-8 h-8 rounded-full bg-brand flex items-center justify-center text-xs font-bold text-white">AU</div>' +
            '<div class="sidebar-user-info text-sm">' +
              '<p class="font-medium text-white">Admin User</p>' +
              '<p class="text-xs text-white/40">Admin</p>' +
            '</div>' +
          '</div>' +
          '<button onclick="window.logout()" class="sidebar-logout sidebar-nav-link" style="width:100%;text-align:left;margin-top:4px">' +
            '<i data-lucide="log-out" class="w-4 h-4"></i><span>Logout</span>' +
          '</button>' +
        '</div>' +
      '</aside>'
    );
  }

  // Store the inject function globally
  window.injectSidebar = injectSidebar;

  // ===== BOTTOM NAV (mobile) — single source of truth =====
  function injectBottomNav() {
    var page = getPageName();
    function item(href, icon, label, match) {
      var active = (page === match) || (match === 'customers.html' && page.indexOf('customer') === 0);
      return '<a href="' + href + '" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1' + (active ? ' active' : ' text-gray-500') + '">' +
        '<i data-lucide="' + icon + '" class="w-5 h-5"></i>' +
        '<span class="text-[10px] font-medium truncate w-full text-center">' + label + '</span>' +
      '</a>';
    }
    document.write(
      '<nav class="bottom-nav md:hidden">' +
        item('dashboard.html', 'layout-dashboard', 'Dashboard', 'dashboard.html') +
        item('customers.html', 'users', 'Customers', 'customers.html') +
        item('orders.html', 'clipboard-list', 'Orders', 'orders.html') +
        item('daybook.html', 'book-open', 'Daybook', 'daybook.html') +
        '<button onclick="toggleSidebar()" class="flex flex-col items-center justify-center gap-0.5 px-2 py-1 rounded-lg min-w-0 flex-1 text-gray-500">' +
          '<i data-lucide="menu" class="w-5 h-5"></i>' +
          '<span class="text-[10px] font-medium truncate w-full text-center">More</span>' +
        '</button>' +
      '</nav>'
    );
  }
  window.injectBottomNav = injectBottomNav;

  // ===== TOGGLE LOGIC =====

  var sidebar, backdrop, body, collapseBtn, closeBtn;

  function cacheElements() {
    sidebar = document.getElementById('sidebar');
    backdrop = document.getElementById('sidebar-backdrop');
    body = document.body;
    collapseBtn = document.getElementById('sidebar-collapse-btn');
    closeBtn = document.getElementById('sidebar-close-btn');
  }

  function isMobile() {
    return window.innerWidth < 768;
  }

  function openDrawer() {
    if (!sidebar || !backdrop) return;
    sidebar.classList.add('open');
    backdrop.classList.add('open');
    body.classList.add('sidebar-open');
    sidebar.focus();
  }

  function closeDrawer() {
    if (!sidebar || !backdrop) return;
    sidebar.classList.remove('open');
    backdrop.classList.remove('open');
    body.classList.remove('sidebar-open');
  }

  function toggleDesktopCollapse() {
    if (!sidebar) return;
    var nowCollapsed = !sidebar.classList.contains('collapsed');
    sidebar.classList.toggle('collapsed', nowCollapsed);
    try {
      localStorage.setItem(SIDEBAR_STORAGE_KEY, String(nowCollapsed));
    } catch (e) {}
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  }

  window.toggleSidebar = function () {
    cacheElements();
    if (isMobile()) {
      if (sidebar && sidebar.classList.contains('open')) {
        closeDrawer();
      } else {
        openDrawer();
      }
    } else {
      toggleDesktopCollapse();
    }
  };

  window.closeSidebar = function () {
    cacheElements();
    closeDrawer();
  };

  // ===== EVENT BINDING =====

  function bindEvents() {
    cacheElements();

    // Backdrop click closes drawer
    if (backdrop) {
      backdrop.addEventListener('click', closeDrawer);
    }

    // Close button
    if (closeBtn) {
      closeBtn.addEventListener('click', closeDrawer);
    }

    // Desktop collapse button
    if (collapseBtn) {
      collapseBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleDesktopCollapse();
      });
    }

    // Esc key closes drawer
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        if (isMobile() && sidebar && sidebar.classList.contains('open')) {
          closeDrawer();
        }
      }
    });

    // Window resize — handle transitions between mobile and desktop
    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        cacheElements();
        if (!isMobile()) {
          closeDrawer();
        }
        // Desktop sidebar always visible
        if (!isMobile() && sidebar) {
          sidebar.style.transform = '';
        }
      }, 200);
    });
  }

  // Listen for DOMContentLoaded to set up handlers
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      cacheElements();
      bindEvents();
      // Collapse button icon sync
      if (collapseBtn) {
        var isCollapsed = sidebar && sidebar.classList.contains('collapsed');
        collapseBtn.innerHTML = isCollapsed
          ? '<i data-lucide="panel-left" class="w-4 h-4"></i>'
          : '<i data-lucide="panel-left-close" class="w-4 h-4"></i>';
      }
    });
  } else {
    cacheElements();
    bindEvents();
  }

  // Also init on load for cases where DOM is ready before script
  window.addEventListener('load', function () {
    cacheElements();
    if (collapseBtn) {
      var isCollapsed = sidebar && sidebar.classList.contains('collapsed');
      collapseBtn.innerHTML = isCollapsed
        ? '<i data-lucide="panel-left" class="w-4 h-4"></i>'
        : '<i data-lucide="panel-left-close" class="w-4 h-4"></i>';
    }
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  });

})();
