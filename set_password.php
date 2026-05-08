<?php
require_once __DIR__ . '/config.php';

if (!is_logged_in()) {
  header('Location: login.php');
  exit;
}

$user_id = $_SESSION['user']['id'];

// Ensure password_set column exists
@$mysqli->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS password_set TINYINT(1) NOT NULL DEFAULT 0");

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm_password'] ?? '';

  $errors = [];
  if ($password !== $confirm) {
    $errors[] = 'Passwords do not match.';
  }
  if (function_exists('validate_password_strength')) {
    $errs = validate_password_strength($password);
    if (!empty($errs)) { $errors = array_merge($errors, $errs); }
  } else {
    if (strlen($password) < 8) { $errors[] = 'Password must be at least 8 characters.'; }
  }

  if (empty($errors)) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("UPDATE users SET password_hash=?, password_set=1 WHERE id=?");
    $stmt->bind_param('si', $hash, $user_id);
    if ($stmt->execute()) {
      unset($_SESSION['require_password_set']);
      $redirect = $_GET['redirect'] ?? 'index.php';
      header('Location: ' . $redirect);
      exit;
    } else {
      $error = 'Failed to save password. Please try again.';
    }
  } else {
    $error = implode('<br>', $errors);
  }
}

$PAGE_BG = 'auth-hero';
include __DIR__ . '/partials/header.php';
?>
<section class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-lg border-0">
        <div class="card-body p-4">
          <h3 class="fw-bold mb-1">Set Your Password</h3>
          <p class="text-muted mb-4">Create a password for future logins.</p>
          <?php if ($error): ?><div class="alert alert-danger small"><?= $error ?></div><?php endif; ?>
          <?php if ($msg): ?><div class="alert alert-success small"><?= $msg ?></div><?php endif; ?>
          <form method="post" id="setPwForm" autocomplete="off">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <div class="input-group" style="border-radius: 0.375rem; overflow: hidden;">
                <input type="password" name="password" id="password" class="form-control" required style="border-right: 0;">
                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')" id="toggleIcon-password" aria-label="Show password" style="border-left: 0;">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="mt-2">
                <div class="progress" style="height:5px;">
                  <div id="pw-strength-bar" class="progress-bar" role="progressbar" style="width:0%"></div>
                </div>
                <small id="pw-strength-text" class="text-muted"></small>
              </div>
              <small class="text-muted d-block mt-1">
                <div id="password-requirements">
                  <div id="req-length" class="text-danger"><i class="bi bi-x-circle"></i> At least 8 characters</div>
                  <div id="req-uppercase" class="text-danger"><i class="bi bi-x-circle"></i> One uppercase letter</div>
                  <div id="req-lowercase" class="text-danger"><i class="bi bi-x-circle"></i> One lowercase letter</div>
                  <div id="req-number" class="text-danger"><i class="bi bi-x-circle"></i> One number</div>
                </div>
              </small>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirm Password</label>
              <div class="input-group" style="border-radius: 0.375rem; overflow: hidden;">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required style="border-right: 0;">
                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')" id="toggleIcon-confirm_password" aria-label="Show password" style="border-left: 0;">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <small id="match-text" class="text-muted"></small>
            </div>
            <button id="submitBtn" class="btn btn-primary w-100 fw-bold" disabled>Save Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<script>
(function(){
  function updateRequirement(id, ok){
    const el = document.getElementById(id);
    if(!el) return;
    if(ok){ el.classList.remove('text-danger'); el.classList.add('text-success'); el.innerHTML = '<i class="bi bi-check-circle"></i> ' + el.textContent.replace(/^.*?\s/, ''); }
    else { el.classList.add('text-danger'); el.classList.remove('text-success'); var txt = el.textContent.replace(/^.*?\s/, ''); el.innerHTML = '<i class="bi bi-x-circle"></i> ' + txt; }
  }

  window.togglePassword = function(fieldId){
    const input = document.getElementById(fieldId);
    const btn = document.getElementById('toggleIcon-' + fieldId);
    if(!input || !btn) return;
    const icon = btn.querySelector('i');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  }

  const pw = document.getElementById('password');
  const cpw = document.getElementById('confirm_password');
  const bar = document.getElementById('pw-strength-bar');
  const text = document.getElementById('pw-strength-text');
  const matchText = document.getElementById('match-text');
  const submitBtn = document.getElementById('submitBtn');

  function evaluate(){
    const p = pw.value || '';
    const hasLen = p.length >= 8;
    const hasU = /[A-Z]/.test(p);
    const hasL = /[a-z]/.test(p);
    const hasN = /[0-9]/.test(p);
    const hasS = /[!@#$%^&*(),.?":{}|<>]/.test(p);

    updateRequirement('req-length', hasLen);
    updateRequirement('req-uppercase', hasU);
    updateRequirement('req-lowercase', hasL);
    updateRequirement('req-number', hasN);

    let strength = 0;
    if(hasLen) strength++;
    if(hasU) strength++;
    if(hasL) strength++;
    if(hasN) strength++;
    if(p.length >= 12) strength++;
    if(hasS) strength++;

    const pct = (strength/6)*100;
    bar.style.width = pct + '%';
    if (strength <= 2){ bar.className = 'progress-bar bg-danger'; text.textContent = 'Weak'; text.className = 'text-danger'; }
    else if (strength <= 4){ bar.className = 'progress-bar bg-warning'; text.textContent = 'Medium'; text.className = 'text-warning'; }
    else { bar.className = 'progress-bar bg-success'; text.textContent = 'Strong'; text.className = 'text-success'; }

    const match = cpw.value && (pw.value === cpw.value);
    matchText.textContent = cpw.value ? (match ? 'Passwords match' : 'Passwords do not match') : '';
    matchText.className = match ? 'text-success' : (cpw.value ? 'text-danger' : 'text-muted');

    const meetsMin = hasLen && hasU && hasL && hasN;
    submitBtn.disabled = !(meetsMin && match);
  }

  if (pw) pw.addEventListener('input', evaluate);
  if (cpw) cpw.addEventListener('input', evaluate);
  evaluate();
})();
</script>
<?php include __DIR__ . '/partials/footer.php';
