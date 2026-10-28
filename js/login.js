
(function () {
  'use strict';

  function showError(input, msg) {
    input.classList.add('error');
    const err = input.closest('.form-group')?.querySelector('.error-msg');
    if (err) { err.textContent = msg; err.classList.add('show'); }
    return false;
  }

  function clearError(input) {
    input.classList.remove('error');
    const err = input.closest('.form-group')?.querySelector('.error-msg');
    if (err) { err.textContent = ''; err.classList.remove('show'); }
    return true;
  }


  function validateField(input) {
    const val = input.value.trim();
    const name = input.name;

  
    if (input.required && !val) {
      return showError(input, 'Ce champ est obligatoire.');
    }

    if (name === 'role' && !val) {
      return showError(input, 'Veuillez choisir un rôle.');
    }

    if (name === 'pseudo' && val && val.length < 2) {
      return showError(input, 'Le pseudo doit contenir au moins 2 caractères.');
    }

    if (name === 'password' && val.length === 0) {
      return showError(input, 'Veuillez saisir votre mot de passe.');
    }

    return clearError(input);
  }


  function validateForm(form) {
    let valid = true;
    form.querySelectorAll('input, select').forEach(function (f) {
      if (!validateField(f)) valid = false;
    });
    return valid;
  }


  function addPasswordToggle(input) {
    const wrapper = input.closest('.form-group');
    if (!wrapper || wrapper.querySelector('.pwd-toggle')) return;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'pwd-toggle';
    btn.setAttribute('aria-label', 'Afficher/masquer le mot de passe');
    btn.innerHTML = '👁';
    btn.style.cssText = [
      'position:absolute', 'right:0.75rem', 'top:50%', 'transform:translateY(-50%)',
      'background:none', 'border:none', 'cursor:pointer', 'font-size:1rem',
      'color:var(--text-light,#888)', 'padding:0', 'line-height:1'
    ].join(';');

    // Make wrapper relative if needed
    const computed = getComputedStyle(wrapper);
    if (computed.position === 'static') wrapper.style.position = 'relative';

    // Push input right so text isn't hidden under button
    input.style.paddingRight = '2.5rem';

    wrapper.appendChild(btn);

    btn.addEventListener('click', function () {
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      btn.innerHTML = isHidden ? '' : '';
    });
  }


  function autoDismissAlerts() {
    document.querySelectorAll('.alert').forEach(function (alert) {
      setTimeout(function () {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(function () { alert.remove(); }, 500);
      }, 4000);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[data-validate]');
    if (!form) return;

    // Live validation on blur/input
    form.querySelectorAll('input, select').forEach(function (input) {
      input.addEventListener('blur', function () { validateField(input); });
      input.addEventListener('input', function () {
        if (input.classList.contains('error')) validateField(input);
      });
    });

    // Password toggle
    const pwdInput = form.querySelector('input[name="password"]');
    if (pwdInput) addPasswordToggle(pwdInput);

    // Submit guard
    form.addEventListener('submit', function (e) {
      if (!validateForm(form)) {
        e.preventDefault();
        const firstError = form.querySelector('.error');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });

    autoDismissAlerts();
  });

})();
