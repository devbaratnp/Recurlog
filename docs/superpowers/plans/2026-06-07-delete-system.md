# Delete System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add permanent hard-delete functionality for admin users across tasks, orders, and customers.

**Architecture:** Fix cascade deletes in existing API endpoints, then add delete buttons + confirmation modals to 5 UI pages. Delete uses HTTP DELETE via fetch(), confirms via modal, removes DOM element on success.

**Tech Stack:** PHP 8+, MySQL (InnoDB FK), Tailwind CSS, Lucide icons, SweetAlert2 toasts

---

### Task 1: Fix API cascade deletes

**Files:**
- Modify: `api/customers.php:76-83`
- Modify: `api/services.php:119-126`

**Why:** Current DELETE endpoints only delete the parent row, which fails with FK constraint errors because child rows exist.

- [ ] **Step 1: Fix `api/customers.php` DELETE — cascade children in transaction**

Replace the entire `case 'DELETE':` block (lines 76-83):

```php
    case 'DELETE':
        if (!$id) jsonError('ID is required');
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("DELETE FROM fscrm_orders WHERE customer_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $taskStmt = $db->prepare("DELETE FROM fscrm_tasks WHERE customer_id = ?");
            $taskStmt->bind_param('i', $id);
            $taskStmt->execute();

            $svcStmt = $db->prepare("DELETE FROM fscrm_services WHERE customer_id = ?");
            $svcStmt->bind_param('i', $id);
            $svcStmt->execute();

            $custStmt = $db->prepare("DELETE FROM fscrm_customers WHERE id = ?");
            $custStmt->bind_param('i', $id);
            $custStmt->execute();
            if ($custStmt->affected_rows === 0) {
                $db->rollback();
                jsonError('Customer not found', 404);
            }
            $db->commit();
            jsonResponse(['message' => 'Customer deleted']);
        } catch (Exception $e) {
            $db->rollback();
            jsonError('Delete failed: ' . $e->getMessage(), 500);
        }
        break;
```

- [ ] **Step 2: Fix `api/services.php` DELETE — cascade tasks**

Replace the entire `case 'DELETE':` block (lines 119-126):

```php
    case 'DELETE':
        if (!$id) jsonError('ID is required');
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("DELETE FROM fscrm_tasks WHERE service_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $svcStmt = $db->prepare("DELETE FROM fscrm_services WHERE id = ?");
            $svcStmt->bind_param('i', $id);
            $svcStmt->execute();
            if ($svcStmt->affected_rows === 0) {
                $db->rollback();
                jsonError('Service not found', 404);
            }
            $db->commit();
            jsonResponse(['message' => 'Service deleted']);
        } catch (Exception $e) {
            $db->rollback();
            jsonError('Delete failed: ' . $e->getMessage(), 500);
        }
        break;
```

- [ ] **Step 3: Quick sanity test**

Open browser, open DevTools console, run:
```js
// Test task delete (should work already — no cascade needed)
fetch('../api/tasks.php?id=1', { method: 'DELETE' }).then(r => r.json()).then(console.log)
```
Expected: `{message: "Task deleted"}` or `{error: "Task not found"}` (if ID 1 already deleted).

---

### Task 2: Add delete to `pages/tasks.php`

**Files:**
- Modify: `pages/tasks.php:284-321` (task card loop) and add modal + JS

- [ ] **Step 1: Add delete button to task card**

In the task card loop (around line 312), change the action bar from:
```php
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end">
              <?php if ($isPending): ?>
                <button class="complete-btn px-4 py-1.5 bg-brand text-white text-xs font-semibold rounded-lg hover:bg-brand/90 transition-colors brand-glow flex items-center gap-1.5" data-task-id="<?= $task['id'] ?>"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Mark Complete</button>
              <?php else: ?>
                <button class="px-4 py-1.5 bg-gray-100 text-gray-400 text-xs font-semibold rounded-lg flex items-center gap-1.5 opacity-50 cursor-not-allowed" disabled><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> <?= $task['status'] === 'completed' ? 'Completed' : 'Missed' ?></button>
              <?php endif; ?>
            </div>
```

To:
```php
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end gap-2">
              <?php if ($isPending): ?>
                <button class="complete-btn px-4 py-1.5 bg-brand text-white text-xs font-semibold rounded-lg hover:bg-brand/90 transition-colors brand-glow flex items-center gap-1.5" data-task-id="<?= $task['id'] ?>"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Mark Complete</button>
              <?php else: ?>
                <button class="px-4 py-1.5 bg-gray-100 text-gray-400 text-xs font-semibold rounded-lg flex items-center gap-1.5 opacity-50 cursor-not-allowed" disabled><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> <?= $task['status'] === 'completed' ? 'Completed' : 'Missed' ?></button>
              <?php endif; ?>
              <button class="delete-task-btn px-3 py-1.5 bg-red-50 text-red-600 text-xs font-semibold rounded-lg hover:bg-red-100 transition-colors flex items-center gap-1.5" data-task-id="<?= $task['id'] ?>" data-task-title="<?= htmlspecialchars($task['title']) ?>" data-task-customer="<?= htmlspecialchars($task['customer_name'] ?? 'Unknown') ?>" data-task-status="<?= $task['status'] ?>" data-task-date="<?= $task['scheduled_date'] ?>"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete</button>
            </div>
```

- [ ] **Step 2: Add delete confirmation modal (before `<!-- MARK COMPLETE MODAL -->`, around line 326)**

```php
  <!-- DELETE CONFIRM MODAL -->
  <div id="delete-modal" class="modal-overlay" style="display:none">
    <div class="modal-content" style="max-width:420px" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900">Delete Task?</h3>
        <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div id="delete-modal-body" class="text-sm text-gray-600 mb-1 space-y-1">
        <p><span class="font-medium">Task:</span> <span id="del-task-title"></span></p>
        <p><span class="font-medium">Customer:</span> <span id="del-task-customer"></span></p>
        <p><span class="font-medium">Status:</span> <span id="del-task-status"></span></p>
        <p><span class="font-medium">Date:</span> <span id="del-task-date"></span></p>
      </div>
      <p class="text-sm text-red-600 font-semibold mt-3 mb-5">This action cannot be undone.</p>
      <div class="flex gap-3">
        <button type="button" onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
        <button type="button" id="delete-confirm-btn" class="flex-1 px-4 py-2.5 bg-danger text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">Delete</button>
      </div>
    </div>
  </div>
```

- [ ] **Step 3: Add delete JS (after the complete modal JS, around line 409)**

Add before the closing `</script>` tag:
```php
    // ========== DELETE TASK ==========
    var deleteTaskId = null;

    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.delete-task-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          deleteTaskId = parseInt(this.dataset.taskId, 10);
          document.getElementById('del-task-title').textContent = this.dataset.taskTitle;
          document.getElementById('del-task-customer').textContent = this.dataset.taskCustomer;
          document.getElementById('del-task-status').textContent = this.dataset.taskStatus;
          document.getElementById('del-task-date').textContent = this.dataset.taskDate;
          document.getElementById('delete-modal').style.display = 'flex';
        });
      });

      document.getElementById('delete-confirm-btn').addEventListener('click', async function () {
        if (!deleteTaskId) return;
        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Deleting...';
        try {
          var res = await fetch('../api/tasks.php?id=' + deleteTaskId, { method: 'DELETE' });
          var data = await res.json();
          if (!res.ok) { showToast(data.error || 'Delete failed', 'error'); return; }
          var card = document.querySelector('.delete-task-btn[data-task-id="' + deleteTaskId + '"]').closest('.task-card');
          if (card) card.remove();
          showToast('Task deleted successfully', 'success');
          closeDeleteModal();
        } catch (e) {
          showToast('Network error', 'error');
        } finally {
          btn.disabled = false;
          btn.textContent = 'Delete';
          try { lucide.createIcons(); } catch (e) {}
        }
      });
    });

    function closeDeleteModal() {
      document.getElementById('delete-modal').style.display = 'none';
      deleteTaskId = null;
    }
```

---

### Task 3: Add delete to `pages/onetime-task.php`

**Files:**
- Modify: `pages/onetime-task.php:190-196` (table action column) + add modal + JS

- [ ] **Step 1: Add delete icon to action column**

Change line 190-194 from:
```php
                    <td class="px-4 py-3 text-right">
                      <a href="task-edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-ghost p-1.5" title="Edit">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                      </a>
                    </td>
```

To:
```php
                    <td class="px-4 py-3 text-right">
                      <div class="flex items-center justify-end gap-1">
                        <a href="task-edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-ghost p-1.5" title="Edit">
                          <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        </a>
                        <button class="delete-ot-btn btn btn-sm btn-ghost p-1.5 text-red-500 hover:text-red-700" title="Delete" data-ot-id="<?= $t['id'] ?>" data-ot-title="<?= htmlspecialchars($t['title']) ?>" data-ot-customer="<?= htmlspecialchars($t['customer_name'] ?? '—') ?>" data-ot-date="<?= $t['scheduled_date'] ?>">
                          <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                      </div>
                    </td>
```

- [ ] **Step 2: Add delete confirmation modal before `</body>`**

```php
  <!-- DELETE CONFIRM MODAL -->
  <div id="delete-ot-modal" class="modal-overlay" style="display:none">
    <div class="modal-content" style="max-width:420px" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900">Delete Task?</h3>
        <button type="button" onclick="closeOtDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div id="delete-ot-body" class="text-sm text-gray-600 mb-1 space-y-1">
        <p><span class="font-medium">Task:</span> <span id="del-ot-title"></span></p>
        <p><span class="font-medium">Customer:</span> <span id="del-ot-customer"></span></p>
        <p><span class="font-medium">Date:</span> <span id="del-ot-date"></span></p>
      </div>
      <p class="text-sm text-red-600 font-semibold mt-3 mb-5">This action cannot be undone.</p>
      <div class="flex gap-3">
        <button type="button" onclick="closeOtDeleteModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
        <button type="button" id="delete-ot-confirm-btn" class="flex-1 px-4 py-2.5 bg-danger text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">Delete</button>
      </div>
    </div>
  </div>
```

- [ ] **Step 3: Add delete JS in the existing `<script>` block**

Find the existing `<script>` block (near the end of the file) and add before the closing `</script>`:
```php
    // ========== DELETE ONE-TIME TASK ==========
    var deleteOtId = null;

    document.querySelectorAll('.delete-ot-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        deleteOtId = parseInt(this.dataset.otId, 10);
        document.getElementById('del-ot-title').textContent = this.dataset.otTitle;
        document.getElementById('del-ot-customer').textContent = this.dataset.otCustomer;
        document.getElementById('del-ot-date').textContent = this.dataset.otDate;
        document.getElementById('delete-ot-modal').style.display = 'flex';
      });
    });

    document.getElementById('delete-ot-confirm-btn').addEventListener('click', async function () {
      if (!deleteOtId) return;
      var btn = this;
      btn.disabled = true;
      btn.textContent = 'Deleting...';
      try {
        var res = await fetch('../api/tasks.php?id=' + deleteOtId, { method: 'DELETE' });
        var data = await res.json();
        if (!res.ok) { showToast(data.error || 'Delete failed', 'error'); btn.disabled = false; btn.textContent = 'Delete'; return; }
        var row = document.querySelector('.delete-ot-btn[data-ot-id="' + deleteOtId + '"]').closest('tr');
        if (row) row.remove();
        showToast('Task deleted successfully', 'success');
        closeOtDeleteModal();
        try { lucide.createIcons(); } catch (e) {}
      } catch (e) {
        showToast('Network error', 'error');
        btn.disabled = false;
        btn.textContent = 'Delete';
      }
    });

    function closeOtDeleteModal() {
      document.getElementById('delete-ot-modal').style.display = 'none';
      deleteOtId = null;
    }
```

---

### Task 4: Add delete to `pages/recurring-task.php`

**Files:**
- Modify: `pages/recurring-task.php:194-199` (table action column) + add modal + JS

**Note:** The recurring-task.php table is structurally identical to onetime-task.php. Follow the same pattern.

- [ ] **Step 1: Add delete icon to action column**

Change lines 194-198 from:
```php
                    <td class="px-4 py-3 text-right">
                      <a href="task-edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-ghost p-1.5" title="Edit">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                      </a>
                    </td>
```

To:
```php
                    <td class="px-4 py-3 text-right">
                      <div class="flex items-center justify-end gap-1">
                        <a href="task-edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-ghost p-1.5" title="Edit">
                          <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        </a>
                        <button class="delete-rt-btn btn btn-sm btn-ghost p-1.5 text-red-500 hover:text-red-700" title="Delete" data-rt-id="<?= $t['id'] ?>" data-rt-title="<?= htmlspecialchars($t['title']) ?>" data-rt-customer="<?= htmlspecialchars($t['customer_name'] ?? '—') ?>" data-rt-date="<?= $t['scheduled_date'] ?>">
                          <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </button>
                      </div>
                    </td>
```

- [ ] **Step 2: Add delete confirmation modal before `</body>`**

Same modal HTML as onetime-task.php, but IDs prefixed with `delete-rt-` instead of `delete-ot-`:

```php
  <!-- DELETE CONFIRM MODAL -->
  <div id="delete-rt-modal" class="modal-overlay" style="display:none">
    <div class="modal-content" style="max-width:420px" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900">Delete Task?</h3>
        <button type="button" onclick="closeRtDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="text-sm text-gray-600 mb-1 space-y-1">
        <p><span class="font-medium">Task:</span> <span id="del-rt-title"></span></p>
        <p><span class="font-medium">Customer:</span> <span id="del-rt-customer"></span></p>
        <p><span class="font-medium">Date:</span> <span id="del-rt-date"></span></p>
      </div>
      <p class="text-sm text-red-600 font-semibold mt-3 mb-5">This action cannot be undone.</p>
      <div class="flex gap-3">
        <button type="button" onclick="closeRtDeleteModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
        <button type="button" id="delete-rt-confirm-btn" class="flex-1 px-4 py-2.5 bg-danger text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">Delete</button>
      </div>
    </div>
  </div>
```

- [ ] **Step 3: Add delete JS in existing `<script>` block**

```php
    // ========== DELETE RECURRING TASK ==========
    var deleteRtId = null;

    document.querySelectorAll('.delete-rt-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        deleteRtId = parseInt(this.dataset.rtId, 10);
        document.getElementById('del-rt-title').textContent = this.dataset.rtTitle;
        document.getElementById('del-rt-customer').textContent = this.dataset.rtCustomer;
        document.getElementById('del-rt-date').textContent = this.dataset.rtDate;
        document.getElementById('delete-rt-modal').style.display = 'flex';
      });
    });

    document.getElementById('delete-rt-confirm-btn').addEventListener('click', async function () {
      if (!deleteRtId) return;
      var btn = this;
      btn.disabled = true;
      btn.textContent = 'Deleting...';
      try {
        var res = await fetch('../api/tasks.php?id=' + deleteRtId, { method: 'DELETE' });
        var data = await res.json();
        if (!res.ok) { showToast(data.error || 'Delete failed', 'error'); btn.disabled = false; btn.textContent = 'Delete'; return; }
        var row = document.querySelector('.delete-rt-btn[data-rt-id="' + deleteRtId + '"]').closest('tr');
        if (row) row.remove();
        showToast('Task deleted successfully', 'success');
        closeRtDeleteModal();
        try { lucide.createIcons(); } catch (e) {}
      } catch (e) {
        showToast('Network error', 'error');
        btn.disabled = false;
        btn.textContent = 'Delete';
      }
    });

    function closeRtDeleteModal() {
      document.getElementById('delete-rt-modal').style.display = 'none';
      deleteRtId = null;
    }
```

---

### Task 5: Add delete to `pages/orders.php`

**Files:**
- Modify: `pages/orders.php:351-359` (order card action buttons) + add modal + JS

- [ ] **Step 1: Add delete button to order card action row**

In the order card, around lines 351-359, change from:
```php
          <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end gap-2 flex-wrap">
            <?php if ($o['status'] === 'pending'): ?>
            <button class="assign-btn px-3 py-1.5 bg-purple-600 text-white text-xs font-semibold rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="user-plus" class="w-3.5 h-3.5"></i> Assign</button>
            <button class="cancel-btn px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="x" class="w-3.5 h-3.5"></i> Cancel</button>
            <?php elseif ($o['status'] === 'assigned'): ?>
            <button class="complete-order-btn px-3 py-1.5 bg-brand text-white text-xs font-semibold rounded-lg hover:bg-brand/90 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Complete</button>
            <button class="cancel-btn px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="x" class="w-3.5 h-3.5"></i> Cancel</button>
            <?php endif; ?>
          </div>
```

To:
```php
          <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end gap-2 flex-wrap">
            <?php if ($o['status'] === 'pending'): ?>
            <button class="assign-btn px-3 py-1.5 bg-purple-600 text-white text-xs font-semibold rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="user-plus" class="w-3.5 h-3.5"></i> Assign</button>
            <button class="cancel-btn px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="x" class="w-3.5 h-3.5"></i> Cancel</button>
            <?php elseif ($o['status'] === 'assigned'): ?>
            <button class="complete-order-btn px-3 py-1.5 bg-brand text-white text-xs font-semibold rounded-lg hover:bg-brand/90 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Complete</button>
            <button class="cancel-btn px-3 py-1.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-1" data-order-id="<?= $o['id'] ?>"><i data-lucide="x" class="w-3.5 h-3.5"></i> Cancel</button>
            <?php endif; ?>
            <button class="delete-order-btn px-3 py-1.5 bg-red-50 text-red-600 text-xs font-semibold rounded-lg hover:bg-red-100 transition-colors flex items-center gap-1.5" data-order-id="<?= $o['id'] ?>" data-order-customer="<?= htmlspecialchars($o['customer_name'] ?? 'Unknown') ?>" data-order-problem="<?= htmlspecialchars($o['problem'] ?? '') ?>" data-order-status="<?= $o['status'] ?>"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete</button>
          </div>
```

- [ ] **Step 2: Add delete confirmation modal before `</body>`**

```php
  <!-- DELETE ORDER CONFIRM MODAL -->
  <div id="delete-order-modal" class="modal-overlay" style="display:none">
    <div class="modal-content" style="max-width:420px" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900">Delete Order?</h3>
        <button type="button" onclick="closeOrderDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="text-sm text-gray-600 mb-1 space-y-1">
        <p><span class="font-medium">Customer:</span> <span id="del-order-customer"></span></p>
        <p><span class="font-medium">Problem:</span> <span id="del-order-problem"></span></p>
        <p><span class="font-medium">Status:</span> <span id="del-order-status"></span></p>
      </div>
      <p class="text-sm text-red-600 font-semibold mt-3 mb-5">This action cannot be undone.</p>
      <div class="flex gap-3">
        <button type="button" onclick="closeOrderDeleteModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
        <button type="button" id="delete-order-confirm-btn" class="flex-1 px-4 py-2.5 bg-danger text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">Delete</button>
      </div>
    </div>
  </div>
```

- [ ] **Step 3: Add delete JS in the existing `<script>` section**

Find the page's main `<script>` block and add:
```php
    // ========== DELETE ORDER ==========
    var deleteOrderId = null;

    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.delete-order-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          deleteOrderId = parseInt(this.dataset.orderId, 10);
          document.getElementById('del-order-customer').textContent = this.dataset.orderCustomer;
          document.getElementById('del-order-problem').textContent = this.dataset.orderProblem;
          document.getElementById('del-order-status').textContent = this.dataset.orderStatus;
          document.getElementById('delete-order-modal').style.display = 'flex';
        });
      });

      document.getElementById('delete-order-confirm-btn').addEventListener('click', async function () {
        if (!deleteOrderId) return;
        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Deleting...';
        try {
          var res = await fetch('../api/orders.php?id=' + deleteOrderId, { method: 'DELETE' });
          var data = await res.json();
          if (!res.ok) { showToast(data.error || 'Delete failed', 'error'); btn.disabled = false; btn.textContent = 'Delete'; return; }
          var card = document.querySelector('.delete-order-btn[data-order-id="' + deleteOrderId + '"]').closest('.order-card');
          if (card) card.remove();
          showToast('Order deleted successfully', 'success');
          closeOrderDeleteModal();
          try { lucide.createIcons(); } catch (e) {}
        } catch (e) {
          showToast('Network error', 'error');
          btn.disabled = false;
          btn.textContent = 'Delete';
        }
      });
    });

    function closeOrderDeleteModal() {
      document.getElementById('delete-order-modal').style.display = 'none';
      deleteOrderId = null;
    }
```

---

### Task 6: Add delete to `pages/customers.php`

**Files:**
- Modify: `pages/customers.php:163-172` (table action column) + add modal + JS

- [ ] **Step 1: Add delete button to customer table action column**

In the client-side JS render function (around line 163), change from:
```js
              '<div class="flex items-center justify-end gap-1.5">' +
              '<a href="customer-add.php?id=' + c.id + '" class="btn btn-sm btn-ghost p-1.5" title="Edit">' +
                '<i data-lucide="pencil" class="w-3.5 h-3.5"></i>' +
              '</a>' +
              '<button onclick="goToCustomer(' + c.id + ')" class="btn btn-sm btn-primary">' +
                '<i data-lucide="eye" class="w-3.5 h-3.5"></i> View' +
              '</button>' +
              '</div>' +
```

To:
```js
              '<div class="flex items-center justify-end gap-1.5">' +
              '<button class="delete-cust-btn btn btn-sm btn-ghost p-1.5 text-red-500 hover:text-red-700" title="Delete" data-cust-id="' + c.id + '" data-cust-name="' + c.name.replace(/'/g, "\\'") + '" data-cust-phone="' + (c.phone || '') + '" data-cust-address="' + (c.address || '').replace(/'/g, "\\'") + '">' +
                '<i data-lucide="trash-2" class="w-3.5 h-3.5"></i>' +
              '</button>' +
              '<a href="customer-add.php?id=' + c.id + '" class="btn btn-sm btn-ghost p-1.5" title="Edit">' +
                '<i data-lucide="pencil" class="w-3.5 h-3.5"></i>' +
              '</a>' +
              '<button onclick="goToCustomer(' + c.id + ')" class="btn btn-sm btn-primary">' +
                '<i data-lucide="eye" class="w-3.5 h-3.5"></i> View' +
              '</button>' +
              '</div>' +
```

- [ ] **Step 2: Add delete confirmation modal before `</body>`**

```php
  <!-- DELETE CUSTOMER CONFIRM MODAL -->
  <div id="delete-cust-modal" class="modal-overlay" style="display:none">
    <div class="modal-content" style="max-width:420px" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900">Delete Customer?</h3>
        <button type="button" onclick="closeCustDeleteModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
      <div class="text-sm text-gray-600 mb-1 space-y-1">
        <p><span class="font-medium">Name:</span> <span id="del-cust-name"></span></p>
        <p><span class="font-medium">Phone:</span> <span id="del-cust-phone"></span></p>
        <p><span class="font-medium">Address:</span> <span id="del-cust-address"></span></p>
      </div>
      <p class="text-sm text-red-600 font-semibold mt-4 mb-1">This will also delete:</p>
      <ul class="text-xs text-red-500 list-disc pl-5 mb-4 space-y-0.5">
        <li>All services and tasks for this customer</li>
        <li>All orders for this customer</li>
      </ul>
      <p class="text-sm text-red-600 font-semibold mb-5">This action cannot be undone.</p>
      <div class="flex gap-3">
        <button type="button" onclick="closeCustDeleteModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
        <button type="button" id="delete-cust-confirm-btn" class="flex-1 px-4 py-2.5 bg-danger text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-colors">Delete Everything</button>
      </div>
    </div>
  </div>
```

- [ ] **Step 3: Add delete JS logic in the existing `<script>` block**

Add before the closing `</script>` tag:
```php
    // ========== DELETE CUSTOMER ==========
    var deleteCustId = null;

    function attachCustDeleteHandlers() {
      document.querySelectorAll('.delete-cust-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          deleteCustId = parseInt(this.dataset.custId, 10);
          document.getElementById('del-cust-name').textContent = this.dataset.custName;
          document.getElementById('del-cust-phone').textContent = this.dataset.custPhone || '—';
          document.getElementById('del-cust-address').textContent = this.dataset.custAddress || '—';
          document.getElementById('delete-cust-modal').style.display = 'flex';
        });
      });
    }

    document.getElementById('delete-cust-confirm-btn').addEventListener('click', async function () {
      if (!deleteCustId) return;
      var btn = this;
      btn.disabled = true;
      btn.textContent = 'Deleting...';
      try {
        var res = await fetch('../api/customers.php?id=' + deleteCustId, { method: 'DELETE' });
        var data = await res.json();
        if (!res.ok) { showToast(data.error || 'Delete failed', 'error'); btn.disabled = false; btn.textContent = 'Delete Everything'; return; }
        var row = document.querySelector('.delete-cust-btn[data-cust-id="' + deleteCustId + '"]').closest('tr');
        if (row) row.remove();
        showToast('Customer deleted successfully', 'success');
        closeCustDeleteModal();
        try { lucide.createIcons(); } catch (e) {}
      } catch (e) {
        showToast('Network error', 'error');
        btn.disabled = false;
        btn.textContent = 'Delete Everything';
      }
    });

    function closeCustDeleteModal() {
      document.getElementById('delete-cust-modal').style.display = 'none';
      deleteCustId = null;
    }

    // Re-attach delete handlers after render
    var origRender = renderTable;
    renderTable = function(filter) {
      origRender(filter);
      attachCustDeleteHandlers();
    };
```

- [ ] **Step 4: Initialize handlers on DOMContentLoaded**

In the existing `DOMContentLoaded` listener, add after `renderTable('')`:
```php
      attachCustDeleteHandlers();
```
