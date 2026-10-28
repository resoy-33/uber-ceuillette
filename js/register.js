(function () {
  'use strict';

  const ALLOWED_TYPES  = ['image/jpeg', 'image/jpg', 'image/png'];
  const ALLOWED_EXTS   = ['.jpg', '.jpeg', '.png'];
  const MAX_SIZE_BYTES = 2 * 1024 * 1024; // 2 MB
  const MAX_SIZE_LABEL = '2 Mo';

  const REGEX = {
    pseudo:   /^[a-zA-ZÀ-ÿ]+$/,
    password: /^[a-zA-Z0-9À-ÿ]{7,}[$#]$/,
    cin:      /^\d{8}$/,
    email:    /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
  };

  const MESSAGES = {
    required:         'Ce champ est obligatoire.',
    pseudo:           'Le pseudo doit contenir uniquement des lettres (sans chiffres ni espaces).',
    password:         'Le mot de passe doit contenir au moins 8 caractères (lettres/chiffres) et finir par $ ou #.',
    cin:              'Le CIN doit contenir exactement 8 chiffres.',
    email:            'Adresse e-mail invalide.',
    confirmPassword:  'Les mots de passe ne correspondent pas.',
    photoType:        `Format de photo invalide. Formats acceptés : ${ALLOWED_EXTS.join(', ')}.`,
    photoSize:        `La photo ne doit pas dépasser ${MAX_SIZE_LABEL}.`,
    minValue:         'La valeur doit être supérieure à 0.',
  };


  function getGroup(input) {
    return input.closest('.form-group');
  }

  function showError(input, msg) {
    input.classList.add('error');
    input.setAttribute('aria-invalid', 'true');
    const group = getGroup(input);
    const err = group?.querySelector('.error-msg');
    if (err) { err.textContent = msg; err.classList.add('show'); }
    return false;
  }

  function clearError(input) {
    input.classList.remove('error');
    input.removeAttribute('aria-invalid');
    const group = getGroup(input);
    const err = group?.querySelector('.error-msg');
    if (err) { err.textContent = ''; err.classList.remove('show'); }
    return true;
  }

  function showFileError(input, msg) {
    input.classList.add('error');
    const group = getGroup(input);
    const err = group?.querySelector('.error-msg');
    if (err) { err.textContent = msg; err.classList.add('show'); }
    // Also reset preview
    const preview = group?.querySelector('.photo-preview');
    if (preview) { preview.src = ''; preview.style.display = 'none'; }
    return false;
  }

  function clearFileError(input) {
    input.classList.remove('error');
    const group = getGroup(input);
    const err = group?.querySelector('.error-msg');
    if (err) { err.textContent = ''; err.classList.remove('show'); }
    return true;
  }


  function buildPhotoPreview(fileInput) {
    const group = getGroup(fileInput);
    if (!group) return;

    let preview = group.querySelector('.photo-preview');
    if (!preview) {
      preview = document.createElement('img');
      preview.className = 'photo-preview';
      preview.alt = 'Aperçu de la photo';
      preview.style.cssText = [
        'display:none', 'margin-top:0.6rem', 'max-width:120px', 'max-height:120px',
        'border-radius:8px', 'border:2px solid var(--olive,#6b7c3e)',
        'object-fit:cover', 'box-shadow:0 2px 8px rgba(0,0,0,0.15)'
      ].join(';');
      fileInput.insertAdjacentElement('afterend', preview);
    }

    fileInput.addEventListener('change', function () {
      const file = fileInput.files[0];
      if (!file) {
        preview.style.display = 'none';
        preview.src = '';
        return;
      }

      const ext = '.' + file.name.split('.').pop().toLowerCase();
      if (!ALLOWED_TYPES.includes(file.type) && !ALLOWED_EXTS.includes(ext)) {
        showFileError(fileInput, MESSAGES.photoType);
        fileInput.value = '';
        return;
      }

      if (file.size > MAX_SIZE_BYTES) {
        showFileError(fileInput, MESSAGES.photoSize);
        fileInput.value = '';
        return;
      }

      clearFileError(fileInput);

      const reader = new FileReader();
      reader.onload = function (e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });
  }


  function validateField(input, form) {
    const val  = input.value.trim();
    const name = input.name;

    if (input.required && !val && input.type !== 'file') {
      return showError(input, MESSAGES.required);
    }

    if (name === 'pseudo' && val) {
      if (!REGEX.pseudo.test(val)) return showError(input, MESSAGES.pseudo);
    }

    if (name === 'password' && val) {
      if (!REGEX.password.test(val)) return showError(input, MESSAGES.password);
    }

    if (name === 'confirm_password' && val) {
      const pwdInput = form.querySelector('input[name="password"]');
      if (pwdInput && pwdInput.value !== val) {
        return showError(input, MESSAGES.confirmPassword);
      }
    }

    if ((name === 'CIN' || name === 'cin') && val) {
      if (!REGEX.cin.test(val)) return showError(input, MESSAGES.cin);
    }

    if (name === 'email' && val) {
      if (!REGEX.email.test(val)) return showError(input, MESSAGES.email);
    }

    if (input.type === 'file' && input.files.length > 0) {
      const file = input.files[0];
      const ext  = '.' + file.name.split('.').pop().toLowerCase();
      if (!ALLOWED_TYPES.includes(file.type) && !ALLOWED_EXTS.includes(ext)) {
        return showError(input, MESSAGES.photoType);
      }
      if (file.size > MAX_SIZE_BYTES) {
        return showError(input, MESSAGES.photoSize);
      }
    }

    return clearError(input);
  }


  function buildPasswordStrength(pwdInput) {
    const group = getGroup(pwdInput);
    if (!group) return;

    const bar = document.createElement('div');
    bar.className = 'pwd-strength-bar';
    bar.style.cssText = [
      'height:4px', 'border-radius:2px', 'margin-top:4px',
      'background:#e0e0e0', 'transition:background 0.3s, width 0.3s',
      'width:0%'
    ].join(';');

    const label = document.createElement('div');
    label.className = 'pwd-strength-label';
    label.style.cssText = 'font-size:0.75rem;margin-top:2px;color:var(--text-light,#888);';

    const hint = group.querySelector('.error-msg');
    if (hint) {
      hint.insertAdjacentElement('beforebegin', bar);
      hint.insertAdjacentElement('beforebegin', label);
    }

    pwdInput.addEventListener('input', function () {
      const v = pwdInput.value;
      let score = 0;
      if (v.length >= 8)  score++;
      if (/[A-Z]/.test(v)) score++;
      if (/[0-9]/.test(v)) score++;
      if (/[$#@!%^&*]/.test(v)) score++;

      const levels = [
        { pct: '0%',   color: '#e0e0e0', text: '' },
        { pct: '25%',  color: '#e74c3c', text: 'Très faible' },
        { pct: '50%',  color: '#e67e22', text: 'Faible' },
        { pct: '75%',  color: '#f1c40f', text: 'Moyen' },
        { pct: '100%', color: '#27ae60', text: 'Fort' },
      ];
      const lvl = levels[score] || levels[0];
      bar.style.width     = lvl.pct;
      bar.style.background = lvl.color;
      label.textContent   = lvl.text;
      label.style.color   = lvl.color;
    });
  }


  function addPasswordToggle(input) {
    const group = getGroup(input);
    if (!group || group.querySelector('.pwd-toggle')) return;

    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'position:relative;';

    // Wrap the input
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

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

    input.style.paddingRight = '2.5rem';
    wrapper.appendChild(btn);

    btn.addEventListener('click', function () {
      const hidden = input.type === 'password';
      input.type = hidden ? 'text' : 'password';
      btn.innerHTML = hidden ? '🙈' : '👁';
    });
  }


  function linkConfirmToPassword(form) {
    const pwd  = form.querySelector('input[name="password"]');
    const conf = form.querySelector('input[name="confirm_password"]');
    if (!pwd || !conf) return;

    pwd.addEventListener('input', function () {
      if (conf.value) validateField(conf, form);
    });
  }


  function cinDigitsOnly(form) {
    const cin = form.querySelector('input[name="CIN"], input[name="cin"]');
    if (!cin) return;
    cin.addEventListener('keypress', function (e) {
      if (!/\d/.test(e.key) && !['Backspace','Delete','Tab','ArrowLeft','ArrowRight'].includes(e.key)) {
        e.preventDefault();
      }
    });
    cin.addEventListener('paste', function (e) {
      const pasted = (e.clipboardData || window.clipboardData).getData('text');
      if (/\D/.test(pasted)) e.preventDefault();
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

    const photoInput = form.querySelector('input[type="file"][name="photo"]');
    if (photoInput) buildPhotoPreview(photoInput);

    form.querySelectorAll('input[type="password"][name="password"]').forEach(function (p) {
      buildPasswordStrength(p);
      addPasswordToggle(p);
    });
    form.querySelectorAll('input[type="password"][name="confirm_password"]').forEach(addPasswordToggle);

    cinDigitsOnly(form);

   
    linkConfirmToPassword(form);

    
    form.querySelectorAll('input, select, textarea').forEach(function (input) {
      input.addEventListener('blur', function () { validateField(input, form); });
      input.addEventListener('input', function () {
        if (input.classList.contains('error')) validateField(input, form);
      });
    });

    // Submit guard
    form.addEventListener('submit', function (e) {
      let valid = true;
      form.querySelectorAll('input, select, textarea').forEach(function (f) {
        if (!validateField(f, form)) valid = false;
      });

      // Re-check photo if present (already validated on change, but ensure state)
      if (photoInput && photoInput.files.length > 0) {
        const file = photoInput.files[0];
        const ext  = '.' + file.name.split('.').pop().toLowerCase();
        if (!ALLOWED_TYPES.includes(file.type) && !ALLOWED_EXTS.includes(ext)) {
          showFileError(photoInput, MESSAGES.photoType);
          valid = false;
        } else if (file.size > MAX_SIZE_BYTES) {
          showFileError(photoInput, MESSAGES.photoSize);
          valid = false;
        }
      }

      if (!valid) {
        e.preventDefault();
        const firstError = form.querySelector('.error');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });

    autoDismissAlerts();
  });

})();
