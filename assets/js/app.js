/*
 * FIELD SERVICE CRM - Application Logic
 *
 * PAGE FILE MAPPING:
 * login.html → Login / Sign In
 * dashboard.html → Dashboard / Home
 * customers.html → Customers List
 * customer-add.html → Add / Edit Customer
 * customer-detail.html → Customer Detail
 * service-add.html → Add Service (Recurrence Engine)
 * tasks.html → Tasks (Today / Upcoming / Missed tabs via URL hash)
 * staff.html → Staff List
 * staff-detail.html → Staff Detail
 * reports.html → Reports (4 sections: Recurring, One-Time, Staff-Wise, Category-Wise)
 * notifications.html → Notifications Inbox
 * settings.html → Settings & Profile
 */

// ========== HELPERS ==========

function getNextId() {
  var id = parseInt(localStorage.getItem('fscrm_next_id') || '1', 10);
  localStorage.setItem('fscrm_next_id', String(id + 1));
  return id;
}

function todayISO() {
  var d = new Date();
  return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

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

// ========== DATA ACCESSORS ==========

window.getCustomers = function() {
  try { return JSON.parse(localStorage.getItem('fscrm_customers') || '[]'); } catch(e) { return []; }
};

window.getCustomer = function(id) {
  var customers = window.getCustomers();
  return customers.find(function(c) { return String(c.id) === String(id); }) || null;
};

window.saveCustomer = function(customer) {
  var customers = window.getCustomers();
  if (!customer.id) {
    customer.id = getNextId();
    customers.push(customer);
    window.pushNotification('New customer ' + customer.name + ' registered', 'customer_added', customer.id);
  } else {
    var idx = customers.findIndex(function(c) { return String(c.id) === String(customer.id); });
    if (idx >= 0) customers[idx] = customer;
    else { customers.push(customer); }
  }
  localStorage.setItem('fscrm_customers', JSON.stringify(customers));
  return customer;
};

window.deleteCustomer = function(id) {
  var customers = window.getCustomers();
  customers = customers.filter(function(c) { return String(c.id) !== String(id); });
  localStorage.setItem('fscrm_customers', JSON.stringify(customers));
};

window.getServices = function(filter) {
  try { var services = JSON.parse(localStorage.getItem('fscrm_services') || '[]');
  if (!filter) return services;
  return services.filter(function(s) {
    var match = true;
    if (filter.customerId) match = match && String(s.customerId) === String(filter.customerId);
    if (filter.categoryId) match = match && String(s.categoryId) === String(filter.categoryId);
    if (filter.isRecurring !== undefined) match = match && s.isRecurring === filter.isRecurring;
    return match;
  }); } catch(e) { return []; }
};

window.getService = function(id) {
  var services = window.getServices();
  return services.find(function(s) { return String(s.id) === String(id); }) || null;
};

window.saveService = function(service) {
  var services = window.getServices();
  var isNew = !service.id;
  if (isNew) {
    service.id = getNextId();
    services.push(service);
  } else {
    var idx = services.findIndex(function(s) { return String(s.id) === String(service.id); });
    if (idx >= 0) services[idx] = service;
    else services.push(service);
  }
  localStorage.setItem('fscrm_services', JSON.stringify(services));

  if (isNew) {
    var customer = window.getCustomer(service.customerId);
    var staff = window.getStaffMember(service.assignedTo);
    var cat = window.getCategory(service.categoryId);
    var taskDate = service.firstScheduledDate || todayISO();
    var taskTitle = (cat ? cat.name + ' - ' : '') + (customer ? customer.name : '');
    if (service.problem) taskTitle = taskTitle + ' (' + service.problem.substring(0, 40) + ')';
    var task = {
      id: getNextId(),
      serviceId: service.id,
      customerId: service.customerId,
      title: taskTitle,
      status: 'pending',
      scheduledDate: taskDate,
      completedDate: null,
      assignedTo: service.assignedTo,
      notes: service.notes || '',
      categoryId: service.categoryId
    };
    var tasks = window.getTasks();
    tasks.push(task);
    localStorage.setItem('fscrm_tasks', JSON.stringify(tasks));

    var staffName = staff ? staff.name : 'Unassigned';
    var customerName = customer ? customer.name : 'Unknown';
    window.pushNotification(staffName + ' assigned to ' + (cat ? cat.name + ' for ' : '') + customerName + ' on ' + formatDate(taskDate), 'service_added', service.id);
  }
  return service;
};

window.getTasks = function(filter) {
  try { var tasks = JSON.parse(localStorage.getItem('fscrm_tasks') || '[]');
  if (!filter) return tasks;
  return tasks.filter(function(t) {
    var match = true;
    if (filter.status) match = match && t.status === filter.status;
    if (filter.customerId) match = match && String(t.customerId) === String(filter.customerId);
    if (filter.staffId) match = match && String(t.assignedTo) === String(filter.staffId);
    if (filter.serviceId) match = match && String(t.serviceId) === String(filter.serviceId);
    if (filter.date) match = match && t.scheduledDate === filter.date;
    if (filter.startDate) match = match && t.scheduledDate >= filter.startDate;
    if (filter.endDate) match = match && t.scheduledDate <= filter.endDate;
    return match;
  }); } catch(e) { return []; }
};

window.getTask = function(id) {
  var tasks = window.getTasks();
  return tasks.find(function(t) { return String(t.id) === String(id); }) || null;
};

window.completeTask = function(taskId, completedDate, notes) {
  var tasks = window.getTasks();
  var idx = tasks.findIndex(function(t) { return String(t.id) === String(taskId); });
  if (idx < 0) return null;
  var task = tasks[idx];
  task.status = 'completed';
  task.completedDate = completedDate || todayISO();
  if (notes) task.notes = (task.notes ? task.notes + ' | ' : '') + notes;
  tasks[idx] = task;
  localStorage.setItem('fscrm_tasks', JSON.stringify(tasks));

  var staff = window.getStaffMember(task.assignedTo);
  var customer = window.getCustomer(task.customerId);
  var service = window.getService(task.serviceId);
  var nextTask = null;

  if (service && service.isRecurring) {
    var nextDate = window.getNextDueDate(service, task.completedDate || task.scheduledDate, task.scheduledDate);
    if (nextDate) {
      nextTask = {
        id: getNextId(),
        serviceId: service.id,
        customerId: service.customerId,
        title: task.title,
        status: 'pending',
        scheduledDate: nextDate,
        completedDate: null,
        assignedTo: service.assignedTo,
        notes: '',
        categoryId: service.categoryId
      };
      tasks.push(nextTask);
      localStorage.setItem('fscrm_tasks', JSON.stringify(tasks));
    }
    var staffName = staff ? staff.name : 'Someone';
    var customerName = customer ? customer.name : 'a customer';
    window.pushNotification(staffName + ' completed ' + task.title + ' for ' + customerName + '. Next service: ' + formatDate(nextDate), 'task_completed', task.id);
    window.showToast('Task completed! Next occurrence scheduled for ' + formatDate(nextDate), 'success');
  } else {
    var staffName = staff ? staff.name : 'Someone';
    var customerName = customer ? customer.name : 'a customer';
    window.pushNotification(staffName + ' completed ' + task.title + ' for ' + customerName, 'task_completed', task.id);
    window.showToast('Task marked as completed!', 'success');
  }

  window.updateStaffActiveCounts();
  return { task: task, nextTask: nextTask };
};

window.getTasksByWeek = function(startDate, endDate) {
  var tasks = window.getTasks({ startDate: startDate, endDate: endDate });
  var weeks = {};
  tasks.forEach(function(t) {
    if (!t.scheduledDate) return;
    var d = new Date(t.scheduledDate + 'T00:00:00');
    var weekStart = new Date(d);
    weekStart.setDate(d.getDate() - d.getDay());
    var key = weekStart.getFullYear() + '-' + String(weekStart.getMonth() + 1).padStart(2, '0') + '-' + String(weekStart.getDate()).padStart(2, '0');
    if (!weeks[key]) weeks[key] = { total: 0, completed: 0, missed: 0 };
    weeks[key].total++;
    if (t.status === 'completed') weeks[key].completed++;
    if (t.status === 'missed') weeks[key].missed++;
  });
  return weeks;
};

window.getStaff = function() {
  try { return JSON.parse(localStorage.getItem('fscrm_staff') || '[]'); } catch(e) { return []; }
};

window.getStaffMember = function(id) {
  var staff = window.getStaff();
  return staff.find(function(s) { return String(s.id) === String(id); }) || null;
};

window.getStaffStats = function(staffId) {
  var tasks = window.getTasks({ staffId: staffId });
  var total = tasks.length;
  var completed = tasks.filter(function(t) { return t.status === 'completed'; }).length;
  var missed = tasks.filter(function(t) { return t.status === 'missed'; }).length;
  var pending = total - completed - missed;
  return {
    total: total,
    completed: completed,
    missed: missed,
    pending: pending,
    completionRate: total > 0 ? Math.round((completed / total) * 100) : 0
  };
};

window.getStaffWithStats = function() {
  var staff = window.getStaff();
  return staff.map(function(s) {
    var stats = window.getStaffStats(s.id);
    return { ...s, ...stats };
  });
};

window.updateStaffActiveCounts = function() {
  var staff = window.getStaff();
  var tasks = window.getTasks({ status: 'pending' });
  staff.forEach(function(s) {
    s.activeTasks = tasks.filter(function(t) { return String(t.assignedTo) === String(s.id); }).length;
  });
  localStorage.setItem('fscrm_staff', JSON.stringify(staff));
};

window.getCategories = function() {
  try { return JSON.parse(localStorage.getItem('fscrm_categories') || '[]'); } catch(e) { return []; }
};

window.getCategory = function(id) {
  var cats = window.getCategories();
  return cats.find(function(c) { return String(c.id) === String(id); }) || null;
};

window.getNotifications = function() {
  try {
    var notifs = JSON.parse(localStorage.getItem('fscrm_notifications') || '[]');
    return notifs.sort(function(a, b) { return new Date(b.createdAt) - new Date(a.createdAt); });
  } catch(e) { return []; }
};

window.getUnreadCount = function() {
  var notifs = window.getNotifications();
  return notifs.filter(function(n) { return !n.isRead; }).length;
};

window.markAllRead = function() {
  var notifs = window.getNotifications();
  notifs.forEach(function(n) { n.isRead = true; });
  localStorage.setItem('fscrm_notifications', JSON.stringify(notifs));
  var badge = document.getElementById('notification-badge');
  if (badge) badge.style.display = 'none';
};

window.pushNotification = function(text, type, relatedId) {
  var notifs = window.getNotifications();
  notifs.unshift({
    id: getNextId(),
    text: text,
    type: type || 'info',
    relatedId: relatedId || null,
    isRead: false,
    createdAt: new Date().toISOString()
  });
  localStorage.setItem('fscrm_notifications', JSON.stringify(notifs));
};

// ========== SERVICE TYPES HELPERS ==========

window.getServiceTypes = function() {
  try { return JSON.parse(localStorage.getItem('fscrm_service_types') || '[]'); } catch(e) { return []; }
};

window.storeServiceTypes = function(types) {
  localStorage.setItem('fscrm_service_types', JSON.stringify(types));
};

// ========== ORDER CRUD ==========

window.getOrders = function(filter) {
  try {
    var orders = JSON.parse(localStorage.getItem('fscrm_orders') || '[]');
    if (!filter) return orders;
    return orders.filter(function(o) {
      var match = true;
      if (filter.status) match = match && o.status === filter.status;
      if (filter.customerId) match = match && String(o.customerId) === String(filter.customerId);
      if (filter.priority) match = match && o.priority === filter.priority;
      return match;
    });
  } catch(e) { return []; }
};

window.getOrder = function(id) {
  var orders = window.getOrders();
  return orders.find(function(o) { return String(o.id) === String(id); }) || null;
};

window.createOrder = function(orderData) {
  var orders = window.getOrders();
  orderData.id = getNextId();
  orderData.createdAt = new Date().toISOString();
  orders.push(orderData);
  localStorage.setItem('fscrm_orders', JSON.stringify(orders));
  // notification message: avoid referencing serviceFor since it's removed
  window.pushNotification('New order created for ' + orderData.customerName, 'order_created', orderData.id);
  try { localStorage.removeItem('fscrm_new_order_type'); } catch(e) {}
  return orderData;
};

window.updateOrder = function(orderId, updates) {
  var orders = window.getOrders();
  var idx = orders.findIndex(function(o) { return String(o.id) === String(orderId); });
  if (idx < 0) return null;
  for (var key in updates) {
    if (updates.hasOwnProperty(key)) {
      orders[idx][key] = updates[key];
    }
  }
  localStorage.setItem('fscrm_orders', JSON.stringify(orders));
  return orders[idx];
};

window.assignOrder = function(orderId, staffId, staffName, scheduledDate) {
  var updates = {
    status: 'assigned',
    assignedTo: staffId,
    assignedStaffName: staffName,
    scheduledDate: scheduledDate
  };
  var order = window.updateOrder(orderId, updates);
  if (order) {
    window.pushNotification('Order #' + orderId + ' assigned to ' + staffName + ' for ' + order.customerName, 'order_assigned', orderId);
  }
  return order;
};

window.completeOrder = function(orderId, notes, createTask) {
  var order = window.getOrder(orderId);
  if (!order) return null;
  var updates = {
    status: 'completed',
    notes: notes || ''
  };
  order = window.updateOrder(orderId, updates);
  if (order && createTask) {
    var task = {
      id: getNextId(),
      serviceId: null,
      customerId: order.customerId,
      title: 'Order #' + orderId + ' - ' + order.customerName,
      status: 'completed',
      scheduledDate: order.scheduledDate || todayISO(),
      completedDate: todayISO(),
      assignedTo: order.assignedTo,
      notes: notes || '',
      categoryId: null
    };
    var tasks = window.getTasks();
    tasks.push(task);
    localStorage.setItem('fscrm_tasks', JSON.stringify(tasks));
  }
  window.pushNotification('Order #' + orderId + ' completed for ' + order.customerName, 'order_completed', orderId);
  return order;
};

window.storeOrders = function(orders) {
  localStorage.setItem('fscrm_orders', JSON.stringify(orders));
};

window.cancelOrder = function(orderId) {
  var order = window.updateOrder(orderId, { status: 'cancelled' });
  if (order) {
    window.pushNotification('Order #' + orderId + ' cancelled for ' + order.customerName, 'task_missed', orderId);
  }
  return order;
};

// ========== DASHBOARD STATS ==========

window.getDashboardStats = function() {
  var tasks = window.getTasks();
  var customers = window.getCustomers();
  var services = window.getServices();
  var staff = window.getStaff();
  var orders = window.getOrders();
  var today = todayISO();

  var todayTasks = tasks.filter(function(t) { return t.scheduledDate === today; });
  var missedTasks = tasks.filter(function(t) { return t.status === 'missed'; });

  var recurringServiceIds = services.filter(function(s) { return s.isRecurring; }).map(function(s) { return s.id; });
  var oneTimeServiceIds = services.filter(function(s) { return !s.isRecurring; }).map(function(s) { return s.id; });

  var oneTimeCustomers = [];
  var recurringCustomers = [];
  customers.forEach(function(c) {
    var custServices = services.filter(function(s) { return String(s.customerId) === String(c.id); });
    var hasRecurring = custServices.some(function(s) { return s.isRecurring; });
    var hasOneTime = custServices.some(function(s) { return !s.isRecurring; });
    if (hasRecurring && recurringCustomers.indexOf(c.id) < 0) recurringCustomers.push(c.id);
    if (hasOneTime && oneTimeCustomers.indexOf(c.id) < 0) oneTimeCustomers.push(c.id);
  });

  var oneTimeTasks = tasks.filter(function(t) { return t.serviceId && oneTimeServiceIds.indexOf(t.serviceId) >= 0; });
  var recurringTasks = tasks.filter(function(t) { return t.serviceId && recurringServiceIds.indexOf(t.serviceId) >= 0; });

  var areaWise = {};
  customers.forEach(function(c) {
    if (!c.area) return;
    if (!areaWise[c.area]) areaWise[c.area] = { total: 0, today: 0, missed: 0 };
    var custTasks = tasks.filter(function(t) { return String(t.customerId) === String(c.id); });
    areaWise[c.area].total += custTasks.length;
    areaWise[c.area].today += custTasks.filter(function(t) { return t.scheduledDate === today; }).length;
    areaWise[c.area].missed += custTasks.filter(function(t) { return t.status === 'missed'; }).length;
  });

  var staffWise = {};
  staff.forEach(function(s) {
    var sTasks = tasks.filter(function(t) { return String(t.assignedTo) === String(s.id); });
    staffWise[s.id] = {
      name: s.name,
      total: sTasks.length,
      today: sTasks.filter(function(t) { return t.scheduledDate === today; }).length,
      missed: sTasks.filter(function(t) { return t.status === 'missed'; }).length
    };
  });

  var pendingOrders = orders.filter(function(o) { return o.status === 'pending'; }).length;
  var urgentOrders = orders.filter(function(o) { return o.status !== 'cancelled' && o.priority === 'urgent'; }).length;
  var totalOrders = orders.length;

  return {
    totalCustomers: customers.length,
    totalStaff: staff.length,
    oneTimeCustomers: oneTimeCustomers.length,
    recurringCustomers: recurringCustomers.length,
    oneTimeTasks: oneTimeTasks,
    recurringTasks: recurringTasks,
    oneTimeToday: oneTimeTasks.filter(function(t) { return t.scheduledDate === today; }).length,
    oneTimeMissed: oneTimeTasks.filter(function(t) { return t.status === 'missed'; }).length,
    recurringToday: recurringTasks.filter(function(t) { return t.scheduledDate === today; }).length,
    recurringMissed: recurringTasks.filter(function(t) { return t.status === 'missed'; }).length,
    todayTasks: todayTasks.length,
    missedTasks: missedTasks.length,
    areaWise: areaWise,
    staffWise: staffWise,
    totalOrders: totalOrders,
    pendingOrders: pendingOrders,
    urgentOrders: urgentOrders
  };
};

// ========== DRILL-DOWN FILTER HELPER ==========

window.navigateWithFilter = function(filterObj) {
  try {
    localStorage.setItem('fscrm_task_filter', JSON.stringify(filterObj));
  } catch(e) {}
  window.location.href = 'tasks.html';
};

window.clearTaskFilter = function() {
  try {
    localStorage.removeItem('fscrm_task_filter');
  } catch(e) {}
};

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

// ========== REPORT HELPERS ==========

function applyReportFilter(tasks, filter, startDate, endDate) {
  // filter can be 'all','missed','today','thisMonth'
  if (!filter || filter === 'all') filter = 'all';
  var today = todayISO();
  var now = new Date();
  var monthStart = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-01';
  return tasks.filter(function(t) {
    if (!t.scheduledDate) return false;
    // date range filtering
    if (startDate && t.scheduledDate < startDate) return false;
    if (endDate && t.scheduledDate > endDate) return false;
    switch (filter) {
      case 'missed': if (t.status !== 'missed') return false; break;
      case 'today': if (t.scheduledDate !== today) return false; break;
      case 'thisMonth': if (t.scheduledDate < monthStart) return false; break;
    }
    return true;
  });
}

window.getRecurringTasksReport = function(filter, startDate, endDate) {
  var services = window.getServices({ isRecurring: true });
  var serviceIds = services.map(function(s) { return s.id; });
  var tasks = window.getTasks().filter(function(t) { return serviceIds.indexOf(t.serviceId) >= 0; });
  var filtered = applyReportFilter(tasks, filter, startDate, endDate);
  var total = filtered.length;
  var completed = filtered.filter(function(t) { return t.status === 'completed'; }).length;
  var missed = filtered.filter(function(t) { return t.status === 'missed'; }).length;
  return {
    tasks: filtered,
    total: total,
    completed: completed,
    missed: missed,
    completionRate: total > 0 ? Math.round((completed / total) * 100) : 0
  };
};

window.getOneTimeTasksReport = function(filter, startDate, endDate) {
  var services = window.getServices({ isRecurring: false });
  var oneTimeServiceIds = services.map(function(s) { return s.id; });
  var allServices = window.getServices();
  var recurringIds = allServices.filter(function(s) { return s.isRecurring; }).map(function(s) { return s.id; });
  var tasks = window.getTasks().filter(function(t) { return recurringIds.indexOf(t.serviceId) < 0; });
  var filtered = applyReportFilter(tasks, filter, startDate, endDate);
  var total = filtered.length;
  var completed = filtered.filter(function(t) { return t.status === 'completed'; }).length;
  var missed = filtered.filter(function(t) { return t.status === 'missed'; }).length;
  return {
    tasks: filtered,
    total: total,
    completed: completed,
    missed: missed,
    completionRate: total > 0 ? Math.round((completed / total) * 100) : 0
  };
};

window.getStaffWiseReport = function(staffId, filter, startDate, endDate) {
  var tasks = window.getTasks({ staffId: staffId });
  var filtered = applyReportFilter(tasks, filter, startDate, endDate);
  var total = filtered.length;
  var completed = filtered.filter(function(t) { return t.status === 'completed'; }).length;
  var missed = filtered.filter(function(t) { return t.status === 'missed'; }).length;
  return {
    tasks: filtered,
    total: total,
    completed: completed,
    missed: missed,
    completionRate: total > 0 ? Math.round((completed / total) * 100) : 0
  };
};

window.getCategoryWiseReport = function(categoryId, filter, startDate, endDate) {
  var tasks = window.getTasks({ categoryId: categoryId });
  var filtered = applyReportFilter(tasks, filter, startDate, endDate);
  var total = filtered.length;
  var completed = filtered.filter(function(t) { return t.status === 'completed'; }).length;
  var missed = filtered.filter(function(t) { return t.status === 'missed'; }).length;
  return {
    tasks: filtered,
    total: total,
    completed: completed,
    missed: missed,
    completionRate: total > 0 ? Math.round((completed / total) * 100) : 0
  };
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
  if (toasts.length > 3) {
    container.removeChild(toasts[0]);
  }
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
    if (date.indexOf('T') >= 0) {
      d = new Date(date);
    } else {
      d = new Date(date + 'T00:00:00');
    }
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
    if (date.indexOf('T') >= 0) {
      d = new Date(date);
    } else {
      d = new Date(date + 'T00:00:00');
    }
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

// ========== LOCALITY/LOCATION MANAGEMENT ==========

window.getLocalities = function() {
  try { return JSON.parse(localStorage.getItem('fscrm_localities') || '[]'); } catch(e) { return []; }
};

window.addLocality = function(name) {
  var list = window.getLocalities();
  var exists = list.some(function(x) { return x.toLowerCase() === name.toLowerCase(); });
  if (!exists) {
    list.push(name);
    localStorage.setItem('fscrm_localities', JSON.stringify(list));
  }
  return name;
};

// ========== INLINE CUSTOMER CREATION (for order form) ==========

window.createCustomerInline = function(data) {
  var customers = window.getCustomers();
  var customer = {
    id: getNextId(),
    name: data.name,
    address: data.address,
    area: data.area || '',
    phone: data.phone || '',
    servicesFor: data.servicesFor || [],
    location: data.location || { lat: 27.00, lng: 84.87 }
  };
  customers.push(customer);
  localStorage.setItem('fscrm_customers', JSON.stringify(customers));
  window.pushNotification('New customer ' + customer.name + ' registered', 'customer_added', customer.id);
  return customer;
};

// ========== SEARCHABLE DROPDOWN BUILDER ==========

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
  menu.innerHTML = '<input type="text" class="dropdown-search" placeholder="' + (config.searchPlaceholder || 'Search...') + '">' +
    '<div class="dropdown-options"></div>';
  container.appendChild(menu);

  var optionsContainer = menu.querySelector('.dropdown-options');
  var searchInput = menu.querySelector('.dropdown-search');

  function renderOptions(filter) {
    var q = (filter || '').toLowerCase().trim();
    var filtered = q ? config.options.filter(function(o) { return o.label.toLowerCase().indexOf(q) >= 0; }) : config.options;
    if (filtered.length === 0) {
      optionsContainer.innerHTML = '<div class="dropdown-empty">No results found</div>';
      return;
    }
    optionsContainer.innerHTML = filtered.map(function(o) {
      var selected = config.selectedValue !== undefined && String(o.value) === String(config.selectedValue) ? ' selected' : '';
      return '<div class="dropdown-option' + selected + '" data-value="' + o.value + '">' +
        '<span class="option-check"><i data-lucide="check" class="w-3 h-3"></i></span>' +
        '<span>' + o.label + '</span></div>';
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
    if (!isOpen) {
      searchInput.value = '';
      renderOptions('');
      setTimeout(function() { searchInput.focus(); }, 50);
    }
  });

  searchInput.addEventListener('input', function() { renderOptions(this.value); });
  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { menu.classList.remove('open'); }
  });

  document.addEventListener('click', function(e) {
    if (!container.contains(e.target)) { menu.classList.remove('open'); }
  });

  renderOptions('');

  container._setValue = function(val) {
    var option = config.options.find(function(o) { return String(o.value) === String(val); });
    if (option) {
      trigger.querySelector('.trigger-text').textContent = option.label;
      trigger.querySelector('.trigger-text').classList.remove('trigger-placeholder');
      optionsContainer.querySelectorAll('.dropdown-option').forEach(function(o) {
        o.classList.toggle('selected', String(o.dataset.value) === String(val));
      });
    }
  };

  return container;
};

// ========== INIT ==========

// Run seed data on first load
if (typeof seedData !== 'undefined') {
  seedData.init();
}

// Update staff active counts on every page load
window.updateStaffActiveCounts();

// Call initRouter on every page
if (typeof initRouter === 'function') {
  document.addEventListener('DOMContentLoaded', function() {
    initRouter();
  });
}
