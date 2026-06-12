<?php
require_once '../includes/config.php';
requireAuth();
$db = getDB();

$editId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$customer = ['name'=>'', 'address'=>'', 'area'=>'', 'phone'=>'', 'services_for'=>'', 'location_lat'=>'27.0000', 'location_lng'=>'84.8700'];

if ($editId) {
    $stmt = $db->prepare("SELECT * FROM fscrm_customers WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $c = $stmt->get_result()->fetch_assoc();
    if ($c) $customer = $c;
}

$localities = [];
$locResult = $db->query("SELECT DISTINCT area FROM fscrm_customers WHERE area IS NOT NULL AND area != '' ORDER BY area ASC");
while ($row = $locResult->fetch_assoc()) {
    $localities[] = $row['area'];
}
$localitiesJson = json_encode($localities);

if ($_SERVER['REQUEST_METHOD'] === 'POST') { requireCsrfToken();
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $address = trim($_POST['address'] ?? '');
        $area = trim($_POST['area'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $services = trim($_POST['services_for'] ?? '');
        $lat = trim($_POST['location_lat'] ?? '27.0000');
        $lng = trim($_POST['location_lng'] ?? '84.8700');
        if ($editId) {
            $stmt = $db->prepare("UPDATE fscrm_customers SET name=?, address=?, area=?, phone=?, services_for=?, location_lat=?, location_lng=? WHERE id=?");
            $stmt->bind_param('sssssssi', $name, $address, $area, $phone, $services, $lat, $lng, $editId);
        } else {
            $stmt = $db->prepare("INSERT INTO fscrm_customers (name, address, area, phone, services_for, location_lat, location_lng) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssss', $name, $address, $area, $phone, $services, $lat, $lng);
        }
        $stmt->execute();
        if (!$editId) $editId = $db->insert_id;
        header('Location: customer-detail.php?id=' . $editId);
        exit;
    }
    $error = 'Name is required';
}
?><?php $pageTitle = $editId ? 'Edit Customer' : 'Add Customer'; require_once '../includes/header.php'; ?>
  <div class="page-content" id="page-content">
    <header class="page-header">
      <div class="page-header-inner">
        <div class="flex items-center gap-2">
          <button onclick="toggleSidebar()" class="sidebar-toggle-btn" aria-label="Toggle menu">
            <i data-lucide="menu" class="w-5 h-5"></i>
          </button>
          <a href="customers.php" class="p-2 -ml-1 text-gray-400 hover:text-navy transition-colors rounded-lg hover:bg-gray-100">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
          </a>
          <h1 class="page-title"><?= $editId ? 'Edit Customer' : 'Add Customer' ?></h1>
        </div>
      </div>
    </header>

    <div class="p-4 md:p-6 max-w-3xl mx-auto">

      <p class="text-gray-500 text-sm mb-4"><?= $editId ? 'Update customer information' : 'Register a new customer and their service details' ?></p>

<?php if (!empty($error)): ?>
      <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

      <form method="POST" action=""><?= csrfHiddenField() ?>
      <div class="card p-5 sm:p-8 space-y-6">

        <div>
          <label for="cust-name" class="form-label">Customer Name <span class="text-danger">*</span></label>
           <input type="text" id="cust-name" name="name" placeholder="e.g. Sharma Family" value="<?= htmlspecialchars($customer['name']) ?>" class="form-input" maxlength="100" />
        </div>

        <div>
          <label for="cust-address" class="form-label">Address <span class="text-danger">*</span></label>
           <input type="text" id="cust-address" name="address" placeholder="e.g. Adarsh Nagar, Birgunj" value="<?= htmlspecialchars($customer['address']) ?>" class="form-input" maxlength="1000" />
        </div>

        <div>
          <label for="cust-contact" class="form-label">Contact Number <span class="text-danger">*</span></label>
           <input type="text" id="cust-contact" name="phone" value="<?= htmlspecialchars($customer['phone'] ?: '+977-') ?>" placeholder="+977-98XXXXXXXX" class="form-input" maxlength="20" />
        </div>

        <div>
          <label for="cust-area" class="form-label">Area / Locality <span class="text-danger">*</span></label>
          <div class="flex gap-2 items-center">
            <select id="cust-area" name="area" class="form-select flex-1"></select>
            <button id="btn-add-locality" type="button" class="btn btn-sm btn-ghost">Add</button>
          </div>
          <div id="locality-add-row" class="mt-2 hidden">
            <div class="flex gap-2">
              <input type="text" id="new-locality-input" class="form-input flex-1" placeholder="Type new locality and press Save" />
              <button id="save-locality-btn" type="button" class="btn btn-sm btn-primary">Save</button>
              <button id="cancel-locality-btn" type="button" class="btn btn-sm btn-secondary">Cancel</button>
            </div>
            <p id="locality-error" class="text-xs text-danger mt-1 hidden"></p>
          </div>
        </div>

        <div>
          <label class="form-label">Location Map</label>
          <div id="map-container" class="map-grid rounded-xl overflow-hidden border border-gray-200 cursor-pointer" style="height:300px;background-color:#f8fafc;">
            <div id="map-pin" class="map-pin" style="left:50%;top:50%;"></div>
          </div>
          <div class="grid grid-cols-2 gap-4 mt-3">
            <div>
              <label for="cust-lat" class="form-label text-xs">Latitude</label>
               <input type="text" id="cust-lat" name="location_lat" readonly value="<?= htmlspecialchars($customer['location_lat'] ?? '27.0000') ?>" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600" maxlength="255" />
            </div>
            <div>
              <label for="cust-lng" class="form-label text-xs">Longitude</label>
               <input type="text" id="cust-lng" name="location_lng" readonly value="<?= htmlspecialchars($customer['location_lng'] ?? '84.8700') ?>" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600" maxlength="255" />
            </div>
          </div>
        </div>

        <input type="hidden" name="services_for" id="services_for" value="" />

      </div>

      <div class="mt-6 flex gap-3">
        <a href="customers.php" class="btn btn-md btn-secondary flex-1 md:flex-none">Cancel</a>
        <button type="submit" id="btn-save-customer" class="btn btn-md btn-primary flex-1 md:flex-none brand-glow"><?= $editId ? 'Update Customer' : 'Save Customer' ?></button>
      </div>
      </form>

    </div>
  </div>

  <script>
    window.__LOCALITIES = <?= $localitiesJson ?>;
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      lucide.createIcons();

    (function() {
      var mapContainer = document.getElementById('map-container');
      var mapPin = document.getElementById('map-pin');
      var latInput = document.getElementById('cust-lat');
      var lngInput = document.getElementById('cust-lng');

      var defaultLat = 27.00;
      var defaultLng = 84.87;
      var latRange = 0.04;
      var lngRange = 0.04;

      function updatePinFromLatLng(lat, lng) {
        var rect = mapContainer.getBoundingClientRect();
        var latPct = ((lat - (defaultLat - latRange)) / (latRange * 2)) * 100;
        var lngPct = ((lng - (defaultLng - lngRange)) / (lngRange * 2)) * 100;
        latPct = Math.max(0, Math.min(100, latPct));
        lngPct = Math.max(0, Math.min(100, lngPct));
        mapPin.style.left = lngPct + '%';
        mapPin.style.top = (100 - latPct) + '%';
        latInput.value = lat.toFixed(4);
        lngInput.value = lng.toFixed(4);
      }

      function updateLatLngFromClick(clientX, clientY) {
        var rect = mapContainer.getBoundingClientRect();
        var x = clientX - rect.left;
        var y = clientY - rect.top;
        var lngPct = x / rect.width;
        var latPct = 1 - (y / rect.height);
        var lat = (defaultLat - latRange) + latPct * (latRange * 2);
        var lng = (defaultLng - lngRange) + lngPct * (lngRange * 2);
        updatePinFromLatLng(lat, lng);
      }

      mapContainer.addEventListener('click', function(e) {
        updateLatLngFromClick(e.clientX, e.clientY);
      });

      var isDragging = false;
      mapPin.addEventListener('mousedown', function(e) {
        e.preventDefault();
        isDragging = true;
        mapPin.style.cursor = 'grabbing';
      });

      document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        updateLatLngFromClick(e.clientX, e.clientY);
      });

      document.addEventListener('mouseup', function() {
        if (isDragging) {
          isDragging = false;
          mapPin.style.cursor = 'grab';
        }
      });

      updatePinFromLatLng(defaultLat, defaultLng);

      function populateLocalities() {
        var select = document.getElementById('cust-area');
        select.innerHTML = '';
        var list = window.__LOCALITIES || [];
        list.forEach(function(l) {
          var opt = document.createElement('option');
          opt.value = l;
          opt.textContent = l;
          select.appendChild(opt);
        });
      }

      document.getElementById('btn-add-locality').addEventListener('click', function() {
        document.getElementById('locality-add-row').classList.remove('hidden');
        document.getElementById('new-locality-input').focus();
      });
      document.getElementById('cancel-locality-btn').addEventListener('click', function() {
        document.getElementById('locality-add-row').classList.add('hidden');
        document.getElementById('new-locality-input').value = '';
        document.getElementById('locality-error').classList.add('hidden');
      });
      document.getElementById('save-locality-btn').addEventListener('click', function() {
        var val = document.getElementById('new-locality-input').value.trim();
        var errEl = document.getElementById('locality-error');
        errEl.classList.add('hidden');
        if (!val) { errEl.textContent = 'Enter a locality name.'; errEl.classList.remove('hidden'); return; }
        var list = window.__LOCALITIES || [];
        var exists = list.some(function(x){ return x.toLowerCase() === val.toLowerCase(); });
        if (exists) { errEl.textContent = 'Locality already exists.'; errEl.classList.remove('hidden'); return; }
        list.push(val);
        window.__LOCALITIES = list;
        populateLocalities();
        document.getElementById('cust-area').value = val;
        document.getElementById('locality-add-row').classList.add('hidden');
        document.getElementById('new-locality-input').value = '';
      });

      populateLocalities();

      <?php if ($customer['area']): ?>
      document.getElementById('cust-area').value = <?= json_encode($customer['area']) ?>;
      <?php endif; ?>

      // Add client-side validation
      document.getElementById('btn-save-customer').addEventListener('click', function(e) {
        var name = document.getElementById('cust-name').value.trim();
        var address = document.getElementById('cust-address').value.trim();
        var contact = document.getElementById('cust-contact').value.trim();
        var area = document.getElementById('cust-area').value.trim();

        if (!name) {
          window.showToast('Please enter the customer name.', 'error');
          document.getElementById('cust-name').focus();
          e.preventDefault();
          return;
        }
        if (!address) {
          window.showToast('Please enter the customer address.', 'error');
          document.getElementById('cust-address').focus();
          e.preventDefault();
          return;
        }
        if (!contact || contact === '+977-') {
          window.showToast('Please enter a valid contact number.', 'error');
          document.getElementById('cust-contact').focus();
          e.preventDefault();
          return;
        }
        if (!area) {
          window.showToast('Please enter the area / locality.', 'error');
          document.getElementById('cust-area').focus();
          e.preventDefault();
          return;
        }
      });
    })();
    });
  </script>
</main>
<?php require_once '../includes/footer.php'; ?>
