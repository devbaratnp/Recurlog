<?php require_once '../includes/config.php'; if (isAuthed()) { $st = $_SESSION['user_staff_id'] ?? null; header('Location: ' . ($st ? 'staff-dashboard.php' : 'dashboard.php')); exit; } $error = ''; if ($_SERVER['REQUEST_METHOD'] === 'POST') { $email = trim($_POST['email'] ?? ''); $password = $_POST['password'] ?? ''; if (!$email || !$password) { $error = 'Please enter email and password'; } else { if (!checkRateLimit('login_' . $_SERVER['REMOTE_ADDR'], 5, 300)) { $error = 'Too many login attempts. Please try again in 5 minutes.'; } else { $db = getDB(); $stmt = $db->prepare("SELECT id, name, email, password, role, staff_id FROM fscrm_users WHERE email = ?"); $stmt->bind_param('s', $email); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc(); if ($user && password_verify($password, $user['password'])) { session_regenerate_id(true); $_SESSION['user_id'] = (int)$user['id']; $_SESSION['user_name'] = $user['name']; $_SESSION['user_email'] = $user['email']; $_SESSION['user_role'] = $user['role']; $_SESSION['user_staff_id'] = !empty($user['staff_id']) ? (int)$user['staff_id'] : null; header('Location: ' . ($_SESSION['user_staff_id'] ? 'staff-dashboard.php' : 'dashboard.php')); exit; } else { $error = 'Invalid email or password'; } } } } ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Login - Recurlog</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link rel="manifest" href="../manifest.json">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="../assets/css/custom.css?v=<?= cacheBust() ?>">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: { brand: '#22C55E', navy: '#0B1E3D', amber: '#F59E0B', danger: '#EF4444' },
          fontFamily: { sans: ['Poppins', 'sans-serif'] }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen flex font-sans bg-[#F2F2F7]">
  <div class="hidden lg:flex lg:w-1/2 bg-navy flex-col justify-between p-12 relative overflow-hidden">
    <div class="absolute inset-0 opacity-[0.04]">
      <div class="absolute top-20 left-20 w-72 h-72 bg-brand rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 right-20 w-96 h-96 bg-brand rounded-full blur-3xl"></div>
    </div>
    <div class="relative z-10">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-11 h-11 bg-brand rounded-xl flex items-center justify-center shadow-lg shadow-brand/25">
          <i data-lucide="wrench" class="w-6 h-6 text-white"></i>
        </div>
        <span class="text-2xl font-bold text-white tracking-tight">Recurlog</span>
      </div>
      <p class="text-white/50 text-sm font-medium">Field Service Management</p>
    </div>
    <div class="relative z-10">
      <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/10 max-w-sm">
        <div class="flex items-center gap-3 mb-4">
          <div class="flex -space-x-2">
            <div class="w-8 h-8 rounded-full border-2 border-navy bg-brand flex items-center justify-center text-xs text-white font-bold">RY</div>
            <div class="w-8 h-8 rounded-full border-2 border-navy bg-amber flex items-center justify-center text-xs text-white font-bold">ST</div>
            <div class="w-8 h-8 rounded-full border-2 border-navy bg-danger flex items-center justify-center text-xs text-white font-bold">BS</div>
          </div>
          <span class="text-white/70 text-sm">Active now</span>
        </div>
        <div class="space-y-3">
          <div class="flex justify-between text-sm"><span class="text-white/50">Today's Tasks</span><span class="text-white font-semibold">17</span></div>
          <div class="flex justify-between text-sm"><span class="text-white/50">Completed</span><span class="text-brand font-semibold">12</span></div>
          <div class="flex justify-between text-sm"><span class="text-white/50">Field Staff</span><span class="text-white font-semibold">5</span></div>
        </div>
      </div>
    </div>
    <div class="relative z-10 text-white/30 text-xs">2025 Recurlog Enterprise</div>
  </div>

  <div class="w-full lg:w-1/2 flex items-center justify-center p-6">
    <div class="w-full max-w-sm">
      <div class="flex lg:hidden items-center gap-3 mb-10 justify-center">
        <div class="w-10 h-10 bg-brand rounded-xl flex items-center justify-center shadow-lg shadow-brand/25">
          <i data-lucide="wrench" class="w-5 h-5 text-white"></i>
        </div>
        <span class="text-xl font-bold text-navy tracking-tight">Recurlog</span>
      </div>

      <?php if ($error): ?>
      <div class="bg-red-50 text-red-600 text-sm p-3 rounded-xl mb-4 flex items-center gap-2 border border-red-100">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold text-navy mb-1">Welcome back</h2>
        <p class="text-gray-500 text-sm mb-7">Sign in to your account to continue</p>
        <form method="POST" action="" class="space-y-5"><?= csrfHiddenField() ?>
          <div>
            <label class="form-label" for="email">Email</label>
            <input id="email" name="email" type="email" placeholder="admin@demo.com" class="form-input" maxlength="255" />
          </div>
          <div>
            <label class="form-label" for="password">Password</label>
            <div class="relative">
              <input id="password" name="password" type="password" placeholder="Enter password" class="form-input w-full pr-10" maxlength="255" />
              <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1" tabindex="-1" aria-label="Toggle password visibility">
                <i data-lucide="eye" id="password-eye-icon" class="w-4 h-4"></i>
              </button>
            </div>
          </div>
          <div class="flex justify-end">
            <a href="#" class="text-sm text-brand hover:underline font-medium">Forgot password?</a>
          </div>
          <button type="submit" class="btn btn-lg btn-primary w-full brand-glow">
            Login
          </button>
        </form>
        <p class="text-center text-sm text-gray-400 mt-6">
          Don't have an account? <a href="#" class="text-brand hover:underline font-medium">Contact admin</a>
        </p>
      </div>

      <!-- PWA Install Button -->
      <div id="pwa-install-container" class="hidden mt-6">
        <button id="pwa-install-btn" class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-white border border-gray-200 text-navy hover:bg-gray-50 transition-colors font-medium shadow-sm">
          <i data-lucide="download" class="w-4 h-4"></i>
          Install Recurlog App
        </button>
      </div>
    </div>
  </div>

  <script src="../assets/js/sidebar.js"></script>
<script src="../assets/js/app.js"></script>
<script>
let deferredPrompt;
const installContainer = document.getElementById('pwa-install-container');
const installBtn = document.getElementById('pwa-install-btn');

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  installContainer.classList.remove('hidden');
});

installBtn.addEventListener('click', async () => {
  if (!deferredPrompt) return;
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  if (outcome === 'accepted') installContainer.classList.add('hidden');
  deferredPrompt = null;
});

window.addEventListener('appinstalled', () => {
  installContainer.classList.add('hidden');
  deferredPrompt = null;
});

function togglePassword() {
  var input = document.getElementById('password');
  var icon = document.getElementById('password-eye-icon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.setAttribute('data-lucide', 'eye-off');
  } else {
    input.type = 'password';
    icon.setAttribute('data-lucide', 'eye');
  }
  lucide.createIcons();
}
</script>
</body>
</html>
