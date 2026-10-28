(function () {
  'use strict';

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

  function parseDate(val) {
    if (!val) return null;
    const d = new Date(val);
    return isNaN(d.getTime()) ? null : d;
  }

  function fmtDate(val) {
    if (!val) return '';
    const d = new Date(val);
    if (isNaN(d.getTime())) return val;
    return d.toLocaleDateString('fr-TN');
  }

  function today() {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }


  function validateField(input, form) {
    const val  = input.value.trim();
    const name = input.name;

    if (input.required && !val) {
      const label = getGroup(input)?.querySelector('label')?.textContent?.replace('*','').trim() || 'Ce champ';
      return showError(input, `${label} est obligatoire.`);
    }

    if (name === 'date_debut' && val) {
      const debut = parseDate(val);
      if (debut && debut < today()) {
        return showError(input, 'La date de début ne peut pas être dans le passé.');
      }
    }

    if (name === 'date_fin' && val) {
      const fin   = parseDate(val);
      const debut = parseDate(form.querySelector('input[name="date_debut"]')?.value);
      if (fin && debut && fin <= debut) {
        return showError(input, `La date de fin doit être après le ${fmtDate(form.querySelector('input[name="date_debut"]').value)}.`);
      }
      if (fin && fin < today()) {
        return showError(input, 'La date de fin ne peut pas être dans le passé.');
      }
    }

    if (name === 'date_limite' && val) {
      const lim   = parseDate(val);
      const debut = parseDate(form.querySelector('input[name="date_debut"]')?.value);
      if (lim && debut && lim >= debut) {
        return showError(input, `La date limite de candidature doit être avant le ${fmtDate(form.querySelector('input[name="date_debut"]').value)}.`);
      }
      if (lim && lim < today()) {
        return showError(input, 'La date limite ne peut pas être dans le passé.');
      }
    }

    if (name === 'nombre_ouvriers' && val) {
      const n = Number(val);
      if (!Number.isInteger(n) || n < 1) {
        return showError(input, 'Le nombre d\'ouvriers doit être un entier supérieur ou égal à 1.');
      }
      if (n > 500) {
        return showError(input, 'Le nombre d\'ouvriers ne peut pas dépasser 500.');
      }
    }

    if (name === 'prix_journee' && val) {
      const p = parseFloat(val);
      if (isNaN(p) || p <= 0) {
        return showError(input, 'Le prix doit être un nombre positif (ex. 25.00 DT).');
      }
      if (p > 10000) {
        return showError(input, 'Le prix journalier semble très élevé. Vérifiez la valeur.');
      }
    }

    if (name === 'adresse' && val && val.length < 5) {
      return showError(input, 'L\'adresse doit contenir au moins 5 caractères.');
    }

    return clearError(input);
  }


  function linkDateFields(form) {
    const names = ['date_debut', 'date_fin', 'date_limite'];
    names.forEach(function (n) {
      const input = form.querySelector(`input[name="${n}"]`);
      if (!input) return;
      input.addEventListener('change', function () {
        // Re-validate all three dates
        names.forEach(function (nn) {
          const other = form.querySelector(`input[name="${nn}"]`);
          if (other && other.value) validateField(other, form);
        });
      });
    });
  }

  function setDateMinimums(form) {
    const todayStr = new Date().toISOString().split('T')[0];
    form.querySelectorAll('input[type="date"]').forEach(function (d) {
      // Only set min if not already set
      if (!d.getAttribute('min')) d.setAttribute('min', todayStr);
    });
  }


  function buildDurationPreview(form) {
    const debutInput = form.querySelector('input[name="date_debut"]');
    const finInput   = form.querySelector('input[name="date_fin"]');
    if (!debutInput || !finInput) return;

    const preview = document.createElement('div');
    preview.className = 'duration-preview';
    preview.style.cssText = [
      'font-size:0.8rem', 'color:var(--olive-dark,#4a5a20)', 'margin-top:4px',
      'font-weight:600', 'transition:opacity 0.3s'
    ].join(';');
    finInput.closest('.form-group')?.appendChild(preview);

    function update() {
      const d1 = parseDate(debutInput.value);
      const d2 = parseDate(finInput.value);
      if (d1 && d2 && d2 > d1) {
        const diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
        preview.textContent = `✅ Durée du chantier : ${diff} jour${diff > 1 ? 's' : ''}`;
        preview.style.opacity = '1';
      } else {
        preview.textContent = '';
        preview.style.opacity = '0';
      }
    }

    debutInput.addEventListener('change', update);
    finInput.addEventListener('change', update);
  }

  function numericInputGuards(form) {
    const nb = form.querySelector('input[name="nombre_ouvriers"]');
    if (nb) {
      nb.addEventListener('keypress', function (e) {
        if (!/\d/.test(e.key) && !['Backspace','Delete','Tab','ArrowLeft','ArrowRight'].includes(e.key)) {
          e.preventDefault();
        }
      });
    }
    const prix = form.querySelector('input[name="prix_journee"]');
    if (prix) {
      prix.addEventListener('keypress', function (e) {
        if (!/[\d.]/.test(e.key) && !['Backspace','Delete','Tab','ArrowLeft','ArrowRight'].includes(e.key)) {
          e.preventDefault();
        }
      });
    }
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

    setDateMinimums(form);
    linkDateFields(form);
    buildDurationPreview(form);
    numericInputGuards(form);

    form.querySelectorAll('input, select, textarea').forEach(function (input) {
      input.addEventListener('blur', function () { validateField(input, form); });
      input.addEventListener('input', function () {
        if (input.classList.contains('error')) validateField(input, form);
      });
      input.addEventListener('change', function () { validateField(input, form); });
    });

    form.addEventListener('submit', function (e) {
      let valid = true;
      form.querySelectorAll('input, select, textarea').forEach(function (f) {
        if (!validateField(f, form)) valid = false;
      });

      if (!valid) {
        e.preventDefault();
        const firstError = form.querySelector('.error');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });

    autoDismissAlerts();
  });

})();
