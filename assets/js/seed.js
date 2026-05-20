window.SEED_DATA = (function () {
  'use strict';

  var now = new Date();
  var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

  function daysFromToday(n) {
    var d = new Date(today);
    d.setDate(d.getDate() + n);
    return d;
  }

  function toISO(d) {
    return d.toISOString();
  }

  function addDays(date, n) {
    var d = new Date(date);
    d.setDate(d.getDate() + n);
    return d;
  }

  function addMonths(date, n) {
    var d = new Date(date);
    d.setMonth(d.getMonth() + n);
    return d;
  }

  var nextId = 1;

  var customers = [
    { id: 1, name: 'Sharma Family', address: 'Adarsh Nagar, Birgunj', area: 'Adarsh Nagar', phone: '+977-9801234001', servicesFor: ['RO', 'Refrigerator'], location: { lat: 27.00, lng: 84.87 } },
    { id: 2, name: 'Gupta Electronics', address: 'Main Road, Birgunj', area: 'Station Road', phone: '+977-9801234002', servicesFor: ['TV', 'AC'], location: { lat: 27.01, lng: 84.88 } },
    { id: 3, name: 'Hotel Makalu', address: 'Ghantaghar, Birgunj', area: 'Ghantaghar Chowk', phone: '+977-9801234003', servicesFor: ['AC', 'Refrigerator'], location: { lat: 27.005, lng: 84.875 } },
    { id: 4, name: 'Patel Residence', address: 'Powerhouse Road, Birgunj', area: 'Powerhouse Road', phone: '+977-9801234004', servicesFor: ['RO'], location: { lat: 26.99, lng: 84.86 } },
    { id: 5, name: 'Singh Niwas', address: 'Adarshanagar, Birgunj', area: 'Mahabirsthan', phone: '+977-9801234005', servicesFor: ['Washing Machine'], location: { lat: 27.015, lng: 84.885 } },
    { id: 6, name: 'Modern Pharmacy', address: 'Adarsh Nagar, Birgunj', area: 'Adarsh Nagar', phone: '+977-9801234006', servicesFor: ['Refrigerator', 'AC'], location: { lat: 27.008, lng: 84.878 } },
    { id: 7, name: 'Khanal House', address: 'Murli Chowk, Birgunj', area: 'Murli Chowk', phone: '+977-9801234007', servicesFor: ['RO', 'TV'], location: { lat: 26.995, lng: 84.865 } },
    { id: 8, name: 'Birgunj Sweets', address: 'Maisthan, Birgunj', area: 'Maisthan', phone: '+977-9801234008', servicesFor: ['Refrigerator'], location: { lat: 27.02, lng: 84.89 } }
  ];

  var staff = [
    { id: 1, name: 'Ramesh Yadav', phone: '+977-9812345001', avatar: 'https://ui-avatars.com/api/?name=Ramesh+Yadav&background=1DB954&color=fff&size=200', activeTasks: 0 },
    { id: 2, name: 'Suresh Thakur', phone: '+977-9812345002', avatar: 'https://ui-avatars.com/api/?name=Suresh+Thakur&background=0B1E3D&color=fff&size=200', activeTasks: 0 },
    { id: 3, name: 'Bikash Sah', phone: '+977-9812345003', avatar: 'https://ui-avatars.com/api/?name=Bikash+Sah&background=F59E0B&color=fff&size=200', activeTasks: 0 },
    { id: 4, name: 'Anita Devi', phone: '+977-9812345004', avatar: 'https://ui-avatars.com/api/?name=Anita+Devi&background=EF4444&color=fff&size=200', activeTasks: 0 },
    { id: 5, name: 'Manoj Kumar', phone: '+977-9812345005', avatar: 'https://ui-avatars.com/api/?name=Manoj+Kumar&background=0EA5E9&color=fff&size=200', activeTasks: 0 }
  ];

  var categories = [
    { id: 1, name: 'Annual Maintenance', color: '#1DB954' },
    { id: 2, name: 'Filter Change', color: '#0EA5E9' },
    { id: 3, name: 'Repair', color: '#F59E0B' },
    { id: 4, name: 'Deep Cleaning', color: '#8B5CF6' },
    { id: 5, name: 'Installation', color: '#EC4899' },
    { id: 6, name: 'Inspection', color: '#6366F1' }
  ];

  var serviceTypes = ['RO', 'Chimney', 'Refrigerator', 'TV', 'Washing Machine', 'AC', 'Other'];

  var orders = [
    { id: 1, customerId: 1, customerName: 'Sharma Family', serviceFor: 'RO', problem: 'Water pressure very low, filter needs urgent check', status: 'pending', priority: 'urgent', assignedTo: null, assignedStaffName: null, createdAt: toISO(daysFromToday(-5)), scheduledDate: null, notes: '' },
    { id: 2, customerId: 2, customerName: 'Gupta Electronics', serviceFor: 'AC', problem: 'AC not cooling properly, gas might be low', status: 'assigned', priority: 'normal', assignedTo: 1, assignedStaffName: 'Ramesh Yadav', createdAt: toISO(daysFromToday(-3)), scheduledDate: toISO(daysFromToday(1)), notes: 'Customer called in the morning' },
    { id: 3, customerId: 3, customerName: 'Hotel Makalu', serviceFor: 'Refrigerator', problem: 'Commercial fridge making unusual noise, cooling inconsistent', status: 'assigned', priority: 'normal', assignedTo: 2, assignedStaffName: 'Suresh Thakur', createdAt: toISO(daysFromToday(-4)), scheduledDate: toISO(daysFromToday(0)), notes: 'Priority customer - hotel business' },
    { id: 4, customerId: 4, customerName: 'Patel Residence', serviceFor: 'RO', problem: 'RO is leaking from the bottom, water all over the floor', status: 'completed', priority: 'urgent', assignedTo: 4, assignedStaffName: 'Anita Devi', createdAt: toISO(daysFromToday(-10)), scheduledDate: toISO(daysFromToday(-9)), notes: 'Leak fixed, replaced seal' },
    { id: 5, customerId: 5, customerName: 'Singh Niwas', serviceFor: 'Washing Machine', problem: 'Drum not spinning, error code E4 showing on display', status: 'pending', priority: 'normal', assignedTo: null, assignedStaffName: null, createdAt: toISO(daysFromToday(-2)), scheduledDate: null, notes: '' },
    { id: 6, customerId: 6, customerName: 'Modern Pharmacy', serviceFor: 'AC', problem: 'AC installed last week but not blowing cold air', status: 'cancelled', priority: 'urgent', assignedTo: 3, assignedStaffName: 'Bikash Sah', createdAt: toISO(daysFromToday(-12)), scheduledDate: toISO(daysFromToday(-11)), notes: 'Customer cancelled - hired another service' },
    { id: 7, customerId: 7, customerName: 'Khanal House', serviceFor: 'TV', problem: 'TV screen flickering when connected to HDMI', status: 'pending', priority: 'normal', assignedTo: null, assignedStaffName: null, createdAt: toISO(daysFromToday(-1)), scheduledDate: null, notes: 'Customer says issue started after power cut' },
    { id: 8, customerId: 8, customerName: 'Birgunj Sweets', serviceFor: 'Refrigerator', problem: 'Display cooler not maintaining temperature, sweets getting spoiled', status: 'assigned', priority: 'urgent', assignedTo: 5, assignedStaffName: 'Manoj Kumar', createdAt: toISO(daysFromToday(-4)), scheduledDate: toISO(daysFromToday(0)), notes: 'URGENT - food safety concern' },
    { id: 9, customerId: 2, customerName: 'Gupta Electronics', serviceFor: 'TV', problem: 'TV not turning on, power light blinking', status: 'pending', priority: 'normal', assignedTo: null, assignedStaffName: null, createdAt: toISO(daysFromToday(-6)), scheduledDate: null, notes: '' },
    { id: 10, customerId: 1, customerName: 'Sharma Family', serviceFor: 'Refrigerator', problem: 'Ice maker not working, water dispenser also jammed', status: 'completed', priority: 'normal', assignedTo: 2, assignedStaffName: 'Suresh Thakur', createdAt: toISO(daysFromToday(-15)), scheduledDate: toISO(daysFromToday(-14)), notes: 'Ice maker repaired, water line unclogged' },
    { id: 11, customerId: 4, customerName: 'Patel Residence', serviceFor: 'RO', problem: 'Bad taste in water, membrane might need replacement', status: 'pending', priority: 'normal', assignedTo: null, assignedStaffName: null, createdAt: toISO(daysFromToday(-3)), scheduledDate: null, notes: '' },
    { id: 12, customerId: 6, customerName: 'Modern Pharmacy', serviceFor: 'Refrigerator', problem: 'Vaccine storage fridge temperature fluctuating', status: 'pending', priority: 'urgent', assignedTo: null, assignedStaffName: null, createdAt: toISO(daysFromToday(-1)), scheduledDate: null, notes: 'CRITICAL - contains vaccines' },
    { id: 13, customerId: 3, customerName: 'Hotel Makalu', serviceFor: 'AC', problem: 'One AC unit in lobby making loud rattling sound', status: 'assigned', priority: 'normal', assignedTo: 4, assignedStaffName: 'Anita Devi', createdAt: toISO(daysFromToday(-7)), scheduledDate: toISO(daysFromToday(-5)), notes: 'May need fan motor replacement' }
  ];

  var services = [];
  var tasks = [];
  var notifications = [];

  var startWindow = daysFromToday(-75);
  var endWindow = daysFromToday(15);

  var noteTemplates = {
    1: ['Routine annual maintenance performed.', 'All components checked and functioning.', 'Preventive maintenance completed.', 'System running efficiently after service.', 'Annual checkup completed without issues.'],
    2: ['Filter replaced with new unit.', 'Old filter was clogged, replaced successfully.', 'Filter change completed.', 'Customer advised on next filter change schedule.', 'Standard filter replacement done.'],
    3: ['Faulty component identified and replaced.', 'Repair completed successfully.', 'Part needed to be ordered, repaired on revisit.', 'Diagnosed issue and fixed on site.', 'Customer reported problem, resolved after inspection.'],
    4: ['Thorough cleaning completed.', 'Coils and vents cleaned thoroughly.', 'Deep cleaning performed with disinfectant.', 'Removed accumulated dust and debris.', 'Cleaning completed; unit performing better.'],
    5: ['New unit installed successfully.', 'Installation completed and tested.', 'Customer trained on basic operation.', 'Mounting and setup completed.', 'Installation done as per customer preference.'],
    6: ['Detailed inspection carried out.', 'All parameters within normal range.', 'Minor issues noted, customer advised.', 'Inspection report shared with customer.', 'System checked; no major concerns found.']
  };

  function getRandomNote(catId) {
    var notes = noteTemplates[catId] || noteTemplates[6];
    return notes[Math.floor(Math.random() * notes.length)];
  }

  function getCompletionProb(staffId) {
    if (staffId === 1 || staffId === 4) return 0.90;
    if (staffId === 5) return 0.70;
    return 0.80;
  }

  function getStatus(scheduledDate, staffId) {
    var sDate = new Date(scheduledDate);
    if (sDate > today) return 'pending';
    var prob = getCompletionProb(staffId);
    return Math.random() < prob ? 'completed' : 'missed';
  }

  function getCompletionDate(scheduledDate) {
    var s = new Date(scheduledDate);
    var daysToAdd = Math.random() < 0.7 ? 0 : 1;
    var d = addDays(s, daysToAdd);
    if (d > today) return toISO(today);
    return toISO(d);
  }

  var rawServices = [
    [1, 2, 'RO', 'RO Filter Change', true, 30, 'days', -75, 1],
    [1, 6, 'RO', 'RO System Inspection', true, 45, 'days', -80, 4],
    [1, 4, 'Refrigerator', 'Refrigerator Deep Cleaning', true, 90, 'days', -60, 2],
    [1, 1, 'Refrigerator', 'Refrigerator Annual Maintenance', true, 90, 'days', -70, 1],
    [1, 3, 'RO', 'RO Membrane Replacement', false, 0, '', -45, 4],
    [1, 5, 'RO', 'RO System Installation', false, 0, '', -120, 3],
    [1, 6, 'Refrigerator', 'Temperature Calibration Check', true, 45, 'days', -70, 5],
    [2, 6, 'TV', 'TV Calibration', true, 90, 'days', -65, 2],
    [2, 1, 'AC', 'AC Annual Maintenance', true, 45, 'days', -75, 1],
    [2, 3, 'AC', 'AC Gas Refill', false, 0, '', -30, 3],
    [2, 2, 'AC', 'AC Filter Cleaning', true, 30, 'days', -80, 4],
    [2, 5, 'TV', 'TV Mounting Service', false, 0, '', -90, 5],
    [2, 6, 'AC', 'AC Performance Inspection', true, 45, 'days', -65, 1],
    [2, 3, 'TV', 'TV Display Repair', false, 0, '', -15, 2],
    [3, 1, 'AC', 'AC Annual Maintenance', true, 45, 'days', -80, 1],
    [3, 4, 'Refrigerator', 'Refrigerator Deep Cleaning', true, 45, 'days', -70, 4],
    [3, 3, 'AC', 'AC Compressor Repair', false, 0, '', -55, 3],
    [3, 3, 'Refrigerator', 'Door Seal Replacement', false, 0, '', -40, 2],
    [3, 6, 'AC', 'AC Filter Inspection', true, 30, 'days', -85, 4],
    [3, 6, 'Refrigerator', 'Commercial Cooler Inspection', true, 30, 'days', -75, 1],
    [3, 1, 'Refrigerator', 'Walk-in Cooler Maintenance', true, 45, 'days', -60, 5],
    [3, 3, 'AC', 'AC Thermostat Calibration', false, 0, '', -10, 2],
    [4, 2, 'RO', 'RO Filter Change', true, 30, 'days', -80, 4],
    [4, 3, 'RO', 'RO Membrane Replacement', false, 0, '', -50, 1],
    [4, 6, 'RO', 'Water Quality Test', true, 60, 'days', -60, 3],
    [4, 5, 'RO', 'RO Faucet Installation', false, 0, '', -100, 5],
    [4, 6, 'RO', 'RO System Inspection', true, 45, 'days', -70, 4],
    [5, 3, 'Washing Machine', 'Drum Bearing Repair', false, 0, '', -35, 3],
    [5, 5, 'Washing Machine', 'Washing Machine Installation', false, 0, '', -95, 2],
    [5, 1, 'Washing Machine', 'Annual Maintenance', true, 45, 'days', -75, 1],
    [5, 6, 'Washing Machine', 'Performance Inspection', true, 30, 'days', -80, 4],
    [5, 4, 'Washing Machine', 'Drum Deep Cleaning', true, 60, 'days', -120, 5],
    [5, 3, 'Washing Machine', 'Water Inlet Valve Replacement', false, 0, '', -20, 2],
    [6, 1, 'Refrigerator', 'Vaccine Storage Unit Maintenance', true, 30, 'days', -80, 1],
    [6, 6, 'Refrigerator', 'Temperature Log Inspection', true, 14, 'days', -75, 4],
    [6, 1, 'AC', 'AC Annual Maintenance', true, 45, 'days', -65, 2],
    [6, 4, 'Refrigerator', 'Coil Deep Cleaning', true, 60, 'days', -70, 3],
    [6, 6, 'AC', 'AC Air Quality Inspection', true, 30, 'days', -80, 4],
    [6, 3, 'Refrigerator', 'Thermostat Calibration Repair', false, 0, '', -25, 1],
    [6, 5, 'Refrigerator', 'Backup Unit Installation', false, 0, '', -110, 5],
    [6, 3, 'AC', 'AC Drain Line Repair', false, 0, '', -5, 2],
    [7, 2, 'RO', 'RO Filter Change', true, 30, 'days', -80, 4],
    [7, 6, 'TV', 'TV Picture Calibration', true, 90, 'days', -60, 2],
    [7, 6, 'RO', 'RO Water Quality Inspection', true, 45, 'days', -75, 1],
    [7, 3, 'TV', 'TV Wall Mount Repair', false, 0, '', -42, 3],
    [7, 3, 'RO', 'RO Pressure Pump Repair', false, 0, '', -18, 5],
    [7, 6, 'RO', 'RO System Audit', true, 60, 'days', -120, 4],
    [8, 4, 'Refrigerator', 'Display Cooler Deep Cleaning', true, 30, 'days', -75, 1],
    [8, 3, 'Refrigerator', 'Compressor Repair', false, 0, '', -50, 3],
    [8, 1, 'Refrigerator', 'Commercial Fridge Maintenance', true, 30, 'days', -80, 4],
    [8, 6, 'Refrigerator', 'Temperature Calibration Check', true, 14, 'days', -70, 2],
    [8, 5, 'Refrigerator', 'New Display Unit Installation', false, 0, '', -85, 5],
    [8, 6, 'Refrigerator', 'Condenser Coil Inspection', true, 45, 'days', -65, 1],
    [8, 4, 'Refrigerator', 'Storage Room Cooler Cleaning', true, 60, 'days', -90, 4],
    [8, 3, 'Refrigerator', 'Door Hinge Replacement', false, 0, '', -8, 2],
    [1, 6, 'RO', 'RO Preventive Inspection', true, 14, 'days', -72, 2],
    [1, 4, 'Refrigerator', 'Condenser Coil Cleaning', true, 30, 'days', -78, 5],
    [2, 4, 'AC', 'AC Condenser Cleaning', true, 30, 'days', -82, 3],
    [2, 6, 'TV', 'TV Surge Protector Check', true, 45, 'days', -68, 1],
    [3, 6, 'AC', 'Refrigerant Level Check', true, 30, 'days', -76, 4],
    [3, 6, 'Refrigerator', 'Kitchen Exhaust Inspection', true, 14, 'days', -70, 5],
    [4, 6, 'RO', 'RO Pressure Check', true, 30, 'days', -74, 2],
    [5, 6, 'Washing Machine', 'Belt Tension Inspection', true, 30, 'days', -80, 1],
    [5, 3, 'Washing Machine', 'Drain Hose Replacement', true, 60, 'days', -66, 3],
    [6, 6, 'Refrigerator', 'Backup Cooler Temperature Check', true, 14, 'days', -73, 4],
    [6, 4, 'AC', 'AC Duct Deep Cleaning', true, 45, 'days', -69, 2],
    [7, 6, 'RO', 'Storage Tank Pressure Check', true, 45, 'days', -77, 5],
    [7, 5, 'TV', 'Smart TV Setup Configuration', false, 0, '', -12, 1],
    [8, 6, 'Refrigerator', 'Door Gasket Seal Check', true, 30, 'days', -79, 3],
    [8, 4, 'Refrigerator', 'Ice Machine Cleaning', true, 14, 'days', -71, 4]
  ];

  rawServices.forEach(function (s) {
    var service = {
      id: nextId++,
      customerId: s[0],
      categoryId: s[1],
      serviceFor: s[2],
      title: s[3],
      problem: '',
      isRecurring: s[4],
      firstScheduledDate: toISO(daysFromToday(s[7])),
      assignedTo: s[8],
      notes: getRandomNote(s[1])
    };
    if (s[4]) {
      service.recurrence = { value: s[5], unit: s[6], repeatFrom: 'last_service' };
    } else {
      service.recurrence = null;
    }
    services.push(service);
  });

  services.forEach(function (service) {
    if (service.isRecurring) {
      var firstDate = new Date(service.firstScheduledDate);
      var interval = service.recurrence.unit === 'months'
        ? service.recurrence.value * 30
        : service.recurrence.value;
      var current = new Date(firstDate);

      while (current <= endWindow) {
        if (current >= startWindow) {
          var status = getStatus(current, service.assignedTo);
          tasks.push({
            id: nextId++,
            serviceId: service.id,
            customerId: service.customerId,
            title: service.title,
            status: status,
            scheduledDate: toISO(current),
            completedDate: status === 'completed' ? getCompletionDate(current) : null,
            assignedTo: service.assignedTo,
            notes: getRandomNote(service.categoryId),
            categoryId: service.categoryId
          });
        }
        current = new Date(current.getTime() + interval * 24 * 60 * 60 * 1000);
      }
    } else {
      var sDate = new Date(service.firstScheduledDate);
      if (sDate < startWindow) sDate = new Date(startWindow.getTime() + Math.floor(Math.random() * 75) * 24 * 60 * 60 * 1000);
      if (sDate > endWindow) sDate = new Date(startWindow.getTime() + Math.floor(Math.random() * 75) * 24 * 60 * 60 * 1000);
      var status = getStatus(sDate, service.assignedTo);
      tasks.push({
        id: nextId++,
        serviceId: service.id,
        customerId: service.customerId,
        title: service.title,
        status: status,
        scheduledDate: toISO(sDate),
        completedDate: status === 'completed' ? getCompletionDate(sDate) : null,
        assignedTo: service.assignedTo,
        notes: getRandomNote(service.categoryId),
        categoryId: service.categoryId
      });
    }
  });

  var recentTasks = tasks.filter(function (t) {
    var d = new Date(t.scheduledDate);
    return d >= daysFromToday(-30) && d <= today;
  });

  recentTasks.sort(function (a, b) {
    return new Date(b.scheduledDate) - new Date(a.scheduledDate);
  });

  var pickedTasks = recentTasks.slice(0, 22);

  pickedTasks.forEach(function (t) {
    var isRead = Math.random() < 0.4;
    var type = t.status === 'completed' ? 'task_completed' : (t.status === 'missed' ? 'task_missed' : 'task_missed');

    notifications.push({
      id: nextId++,
      text: t.status === 'completed'
        ? t.title + ' completed for ' + customers.filter(function (c) { return c.id === t.customerId; })[0].name
        : t.title + ' missed at ' + customers.filter(function (c) { return c.id === t.customerId; })[0].name,
      type: type,
      relatedId: t.id,
      isRead: isRead,
      createdAt: t.completedDate || t.scheduledDate
    });
  });

  var extraNotifs = [
    { text: 'New customer registered: Sharma Family', type: 'customer_added', age: -35 },
    { text: 'New service added: AC Annual Maintenance for Gupta Electronics', type: 'service_added', age: -28 },
    { text: 'New customer registered: Hotel Makalu', type: 'customer_added', age: -20 },
    { text: 'New service added: RO Filter Change for Patel Residence', type: 'service_added', age: -15 },
    { text: 'Ramesh Yadav completed 15 tasks this week', type: 'task_completed', age: -3 }
  ];

  extraNotifs.forEach(function (n) {
    var createdAt = daysFromToday(n.age);
    if (createdAt > today) createdAt = today;
    notifications.push({
      id: nextId++,
      text: n.text,
      type: n.type,
      relatedId: null,
      isRead: Math.random() < 0.5,
      createdAt: toISO(createdAt)
    });
  });

  notifications.sort(function (a, b) {
    return new Date(b.createdAt) - new Date(a.createdAt);
  });

  return {
    customers: customers,
    staff: staff,
    categories: categories,
    services: services,
    tasks: tasks,
    notifications: notifications,
    orders: orders,
    serviceTypes: serviceTypes,
    nextId: nextId
  };
})();

var seedData = {
  init: function () {
    if (!localStorage.getItem('fscrm_seeded')) {
      var data = window.SEED_DATA;
      localStorage.setItem('fscrm_customers', JSON.stringify(data.customers));
      localStorage.setItem('fscrm_staff', JSON.stringify(data.staff));
      localStorage.setItem('fscrm_categories', JSON.stringify(data.categories));
      localStorage.setItem('fscrm_services', JSON.stringify(data.services));
      localStorage.setItem('fscrm_tasks', JSON.stringify(data.tasks));
      localStorage.setItem('fscrm_notifications', JSON.stringify(data.notifications));
      localStorage.setItem('fscrm_orders', JSON.stringify(data.orders));
      localStorage.setItem('fscrm_service_types', JSON.stringify(data.serviceTypes));
      localStorage.setItem('fscrm_next_id', data.nextId.toString());
      localStorage.setItem('fscrm_seeded', 'true');
    } else {
      if (!localStorage.getItem('fscrm_service_types')) {
        localStorage.setItem('fscrm_service_types', JSON.stringify(['RO', 'Chimney', 'Refrigerator', 'TV', 'Washing Machine', 'AC', 'Other']));
      }
      if (!localStorage.getItem('fscrm_orders')) {
        localStorage.setItem('fscrm_orders', JSON.stringify([]));
      }
    }
  }
};

window.seedData = seedData;
