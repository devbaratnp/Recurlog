(function () {
  'use strict';

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
      document.cookie = 'fscrm_sidebar_collapsed=' + nowCollapsed + '; path=/';
    } catch(e) {}
    if (typeof lucide !== 'undefined') lucide.createIcons();
  }

  window.toggleSidebar = function () {
    cacheElements();
    if (isMobile()) {
      if (sidebar && sidebar.classList.contains('open')) { closeDrawer(); } else { openDrawer(); }
    } else {
      toggleDesktopCollapse();
    }
  };

  window.closeSidebar = function () {
    cacheElements();
    closeDrawer();
  };

  function bindEvents() {
    cacheElements();
    if (backdrop) backdrop.addEventListener('click', closeDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (collapseBtn) {
      collapseBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleDesktopCollapse();
      });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isMobile() && sidebar && sidebar.classList.contains('open')) {
        closeDrawer();
      }
    });
    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        cacheElements();
        if (!isMobile()) closeDrawer();
        if (!isMobile() && sidebar) sidebar.style.transform = '';
      }, 200);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { cacheElements(); bindEvents(); });
  } else {
    cacheElements();
    bindEvents();
  }

  window.addEventListener('load', function () {
    cacheElements();
    if (typeof lucide !== 'undefined') lucide.createIcons();
  });
})();
