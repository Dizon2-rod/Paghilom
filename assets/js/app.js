
// Paghilom Cafe - Modern JavaScript Application

document.addEventListener('DOMContentLoaded', () => {
  AOS.init({ duration: 700, once: true, offset: 50, easing: 'ease-out-cubic' });
  const y = document.getElementById('year'); if (y) y.textContent = new Date().getFullYear();
  initTheme();
  initNavbarScroll();
  initPasswordStrength();
  window.showToast = showToast;
  initFormValidation();
  initLoadingStates();
  initCart();
});

function initTheme() {
  const btn = document.getElementById('themeToggle');
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const saved = localStorage.getItem('theme');
  const isDark = saved ? saved==='dark' : prefersDark;
  document.body.classList.toggle('dark', isDark);
  if (btn) btn.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    btn.style.transform = 'rotate(360deg) scale(1.2)';
    setTimeout(() => { btn.style.transform = ''; }, 300);
  });
}

function initNavbarScroll() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;
  window.addEventListener('scroll', () => {
    if (window.pageYOffset > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });
}

function initPasswordStrength() {
  const passwordInputs = document.querySelectorAll('input[type="password"][data-strength-check]');
  passwordInputs.forEach(input => {
    const container = document.createElement('div');
    container.className = 'password-strength mt-2';
    container.innerHTML = '<div class="password-strength-bar"></div>';
    input.parentNode.appendChild(container);
    const feedback = document.createElement('small');
    feedback.className = 'text-muted d-block mt-1';
    input.parentNode.appendChild(feedback);
    const bar = container.querySelector('.password-strength-bar');
    input.addEventListener('input', () => {
      const password = input.value;
      let strength = 0;
      if (password.length >= 8) strength++;
      if (password.length >= 12) strength++;
      if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
      if (/[0-9]/.test(password)) strength++;
      if (/[^A-Za-z0-9]/.test(password)) strength++;
      strength = Math.min(4, strength);
      bar.className = 'password-strength-bar';
      if (password.length === 0) { feedback.textContent = ''; return; }
      switch(strength) {
        case 1: bar.classList.add('strength-weak'); feedback.textContent = 'Weak password'; feedback.style.color = '#dc3545'; break;
        case 2: bar.classList.add('strength-fair'); feedback.textContent = 'Fair password'; feedback.style.color = '#ffc107'; break;
        case 3: bar.classList.add('strength-good'); feedback.textContent = 'Good password'; feedback.style.color = '#17a2b8'; break;
        case 4: bar.classList.add('strength-strong'); feedback.textContent = 'Strong password!'; feedback.style.color = '#28a745'; break;
      }
    });
  });
}

function showToast(message, type = 'info', duration = 3000) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast align-items-center text-white bg-${type} border-0`;
  toast.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
  container.appendChild(toast);
  const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: duration });
  bsToast.show();
  toast.addEventListener('hidden.bs.toast', () => { toast.remove(); });
}

function initFormValidation() {
  const forms = document.querySelectorAll('.needs-validation');
  forms.forEach(form => {
    form.addEventListener('submit', (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
        showToast('Please fill in all required fields', 'warning');
      }
      form.classList.add('was-validated');
    }, false);
  });
}

function initLoadingStates() {
  const forms = document.querySelectorAll('form[data-loading]');
  forms.forEach(form => {
    form.addEventListener('submit', function(e) {
      const btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.disabled) {
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        setTimeout(() => { btn.disabled = false; btn.innerHTML = originalText; }, 10000);
      }
    });
  });
}

function initCart() {
  updateCartCount();
  document.addEventListener('click', (e) => {
    if (e.target.matches('[data-add-to-cart]')) {
      const productId = e.target.dataset.addToCart;
      let cart = JSON.parse(localStorage.getItem('cart') || '[]');
      const existingItem = cart.find(item => item.id === productId);
      if (existingItem) { existingItem.quantity++; } else { cart.push({ id: productId, quantity: 1 }); }
      localStorage.setItem('cart', JSON.stringify(cart));
      updateCartCount();
      showToast('Item added to cart!', 'success');
    }
  });
}

function updateCartCount() {
  const cart = JSON.parse(localStorage.getItem('cart') || '[]');
  const count = cart.reduce((sum, item) => sum + item.quantity, 0);
  document.querySelectorAll('.cart-count').forEach(badge => {
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-block' : 'none';
  });
}

window.copyToClipboard = function(text, msg = 'Copied!') {
  navigator.clipboard.writeText(text).then(() => showToast(msg, 'success', 2000)).catch(() => showToast('Failed to copy', 'danger'));
};

window.formatCurrency = function(amount) {
  return '₱' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
};

const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

console.log('🎉 Paghilom Cafe loaded!');
