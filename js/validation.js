const RULES = {
  pseudo:   { regex: /^[a-zA-ZÀ-ÿ]+$/, msg: "Le pseudo doit contenir uniquement des lettres." },
  password: { regex: /^[a-zA-Z0-9À-ÿ]{7,}[$#]$/, msg: "Le mot de passe doit contenir au moins 8 caractères (lettres/chiffres) et finir par $ ou #." },
  cin:      { regex: /^\d{8}$/, msg: "Le CIN doit contenir exactement 8 chiffres." },
  email:    { regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msg: "Adresse email invalide." },
  required: { msg: "Ce champ est obligatoire." }
};

function showError(input, msg) {
  input.classList.add('error');
  const err = input.parentElement.querySelector('.error-msg');
  if (err) { err.textContent = msg; err.classList.add('show'); }
  return false;
}

function clearError(input) {
  input.classList.remove('error');
  const err = input.parentElement.querySelector('.error-msg');
  if (err) err.classList.remove('show');
  return true;
}

function validateField(input) {
  const val = input.value.trim();
  const name = input.name;

  if (input.required && !val) return showError(input, RULES.required.msg);

  if (name === 'pseudo' && val) {
    if (!RULES.pseudo.regex.test(val)) return showError(input, RULES.pseudo.msg);
  }
  if (name === 'password' && val) {
    if (!RULES.password.regex.test(val)) return showError(input, RULES.password.msg);
  }
  if ((name === 'CIN' || name === 'cin') && val) {
    if (!RULES.cin.regex.test(val)) return showError(input, RULES.cin.msg);
  }
  if (name === 'email' && val) {
    if (!RULES.email.regex.test(val)) return showError(input, RULES.email.msg);
  }
  if (name === 'confirm_password' && val) {
    const pwd = document.querySelector('input[name="password"]');
    if (pwd && pwd.value !== val) return showError(input, "Les mots de passe ne correspondent pas.");
  }
  if (name === 'date_fin' && val) {
    const debut = document.querySelector('input[name="date_debut"]');
    if (debut && debut.value && val <= debut.value)
      return showError(input, "La date de fin doit être après la date de début.");
  }
  if (name === 'date_limite' && val) {
    const debut = document.querySelector('input[name="date_debut"]');
    if (debut && debut.value && val >= debut.value)
      return showError(input, "La date limite doit être avant la date de début du chantier.");
  }
  if ((name === 'nombre_ouvriers' || name === 'prix_journee') && val) {
    if (Number(val) <= 0) return showError(input, "La valeur doit être supérieure à 0.");
  }

  return clearError(input);
}


document.addEventListener('DOMContentLoaded', function () {
  const inputs = document.querySelectorAll('input, select, textarea');
  inputs.forEach(input => {
    input.addEventListener('blur', () => validateField(input));
    input.addEventListener('input', () => {
      if (input.classList.contains('error')) validateField(input);
    });
  });

 
  const forms = document.querySelectorAll('form[data-validate]');
  forms.forEach(form => {
    form.addEventListener('submit', function (e) {
      let valid = true;
      const fields = form.querySelectorAll('input, select, textarea');
      fields.forEach(f => { if (!validateField(f)) valid = false; });
      if (!valid) {
        e.preventDefault();
        const first = form.querySelector('.error');
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  });

  
  document.querySelectorAll('.star-rating').forEach(container => {
    const stars = container.querySelectorAll('.star');
    const input = container.querySelector('input[type="hidden"]');
    stars.forEach((star, idx) => {
      star.addEventListener('click', () => {
        const val = idx + 1;
        if (input) input.value = val;
        stars.forEach((s, i) => s.classList.toggle('active', i < val));
      });
      star.addEventListener('mouseenter', () => {
        stars.forEach((s, i) => s.style.color = i <= idx ? '#f0ad4e' : '#ddd');
      });
    });
    container.addEventListener('mouseleave', () => {
      const current = parseInt(input ? input.value : 0);
      stars.forEach((s, i) => s.style.color = i < current ? '#f0ad4e' : '#ddd');
    });
  });

  
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 4000);
  });
});
