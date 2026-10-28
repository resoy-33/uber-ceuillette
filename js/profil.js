(function () {
  'use strict';

  const ALLOWED_TYPES  = ['image/jpeg', 'image/jpg', 'image/png'];
  const ALLOWED_EXTS   = ['.jpg', '.jpeg', '.png'];
  const MAX_SIZE_BYTES = 2 * 1024 * 1024; // 2 MB
  const MAX_SIZE_LABEL = '2 Mo';

  const REGEX = {
    password: /^[a-zA-Z0-9À-ÿ]{7,}[$#]$/,
    email:    /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
  };


  function getGroup(input) {
    return input.closest('.form-group');
  }

  function showError(input, msg) {
    input.classList.add('error');
    input.setAttribute('aria-invalid', 'true');
    const err = getGroup(input)?.querySelector('.error-msg');
    if (err) { err.textContent = msg; err.classList.add('show'); }
    return false;
  }

  function clearError(input) {
    input.classList.remove('error');
    input.removeAttribute('aria-invalid');
    const err = getGroup(input)?.querySelector('.error-msg');
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
      preview.alt = 'Aperçu de la nouvelle photo';
      preview.style.cssText = [
        'display:none', 'margin-top:0.6rem', 'max-width:120px', 'max-height:120px',
        'border-radius:50%', 'border:3px solid var(--olive,#6b7c3e)',
        'object-fit:cover', 'box-shadow:0 2px 12px rgba(0,0,0,0.18)'
      ].join(';');
      fileInput.insertAdjacentElement('afterend', preview);
    }

    const errSpan = group.querySelector('.error-msg');

    fileInput.addEventListener('change', function () {
      const file = fileInput.files[0];

      if (!file) {
        preview.style.display = 'none';
        preview.src = '';
        clearError(fileInput);
        return;
      }

      const ext = '.' + file.name.split('.').pop().toLowerCase();
      if (!ALLOWED_TYPES.includes(file.type) && !ALLOWED_EXTS.includes(ext)) {
        fileInput.classList.add('error');
        if (errSpan) { errSpan.textContent = `Format invalide. Acceptés : ${ALLOWED_EXTS.join(', ')}.`; errSpan.classList.add('show'); }
        preview.style.display = 'none';
        fileInput.value = '';
        return;
      }

      if (file.size > MAX_SIZE_BYTES) {
        fileInput.classList.add('error');
        if (errSpan) { errSpan.textContent = `Taille maximale : ${MAX_SIZE_LABEL}.`; errSpan.classList.add('show'); }
        preview.style.display = 'none';
        fileInput.value = '';
        return;
      }

      clearError(fileInput);

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

    if (input.required && input.type !== 'file' && !val) {
      const label = getGroup(input)?.querySelector('label')?.textContent?.replace('*','').trim() || 'Ce champ';
      return showError(input, `${label} est obligatoire.`);
    }

    if (name === 'email' && val) {
      if (!REGEX.email.test(val)) return showError(input, 'Adresse e-mail invalide.');
    }

    if (name === 'password' && val) {
      if (!REGEX.password.test(val)) {
        return showError(input, 'Le mot de passe doit contenir au moins 8 caractères (lettres/chiffres) et finir par $ ou #.');
      }
    }

    if (name === 'confirm_password' && val) {
      const pwdInput = form.querySelector('input[name="password"]');
      if (pwdInput && pwdInput.value !== val) {
        return showError(input, 'Les mots de passe ne correspondent pas.');
      }
    }

    if (input.type === 'file') return clearError(input);

    return clearError(input);
  }


  function addPasswordToggle(input) {
    const group = getGroup(input);
    if (!group || group.querySelector('.pwd-toggle')) return;

    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'position:relative;';
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


  function trackUnsavedChanges(form) {
    let dirty = false;
    form.querySelectorAll('input, textarea, select').forEach(function (f) {
      f.addEventListener('input', function () { dirty = true; });
      f.addEventListener('change', function () { dirty = true; });
    });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
      if (dirty) {
        e.preventDefault();
        e.returnValue = 'Vous avez des modifications non enregistrées. Voulez-vous quitter la page ?';
      }
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

    form.querySelectorAll('input[name="password"], input[name="confirm_password"]').forEach(addPasswordToggle);

    trackUnsavedChanges(form);

    form.querySelectorAll('input, select, textarea').forEach(function (input) {
      input.addEventListener('blur', function () { validateField(input, form); });
      input.addEventListener('input', function () {
        if (input.classList.contains('error')) validateField(input, form);
      });
    });

    const pwd  = form.querySelector('input[name="password"]');
    const conf = form.querySelector('input[name="confirm_password"]');
    if (pwd && conf) {
      pwd.addEventListener('input', function () {
        if (conf.value) validateField(conf, form);
      });
    }

    form.addEventListener('submit', function (e) {
      let valid = true;
      form.querySelectorAll('input, select, textarea').forEach(function (f) {
        if (!validateField(f, form)) valid = false;
      });

      if (photoInput && photoInput.files.length > 0) {
        const file = photoInput.files[0];
        const ext  = '.' + file.name.split('.').pop().toLowerCase();
        if (!ALLOWED_TYPES.includes(file.type) && !ALLOWED_EXTS.includes(ext)) {
          showError(photoInput, `Format invalide. Acceptés : ${ALLOWED_EXTS.join(', ')}.`);
          valid = false;
        } else if (file.size > MAX_SIZE_BYTES) {
          showError(photoInput, `Taille maximale : ${MAX_SIZE_LABEL}.`);
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
