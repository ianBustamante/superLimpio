// assets/js/validation.js

document.addEventListener("DOMContentLoaded", () => {
  initLoginForm();
  initRegistroForm();
  initRecuperarForm();
});

/* ============================
   UTILIDADES GENERALES
   ============================ */

// Valida formato básico de correo
function esCorreoValido(correo) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(correo);
}

// Mostrar / ocultar contraseña para un input concreto
function configurarTogglePassword(toggleId, inputId) {
  const btn = document.getElementById(toggleId);
  const input = document.getElementById(inputId);
  if (!btn || !input) return;

  btn.addEventListener("click", (e) => {
    e.preventDefault();
    const esPassword = input.type === "password";
    input.type = esPassword ? "text" : "password";
    btn.textContent = esPassword ? "Ocultar" : "Mostrar";
  });
}

/* ============================
   LOGIN
   ============================ */

function initLoginForm() {
  const form = document.getElementById("loginForm");
  if (!form) return; // esta página no es login

  const correoInput = document.getElementById("correo");
  const passInput   = document.getElementById("password");

  configurarTogglePassword("togglePass", "password");

  form.addEventListener("submit", (e) => {
    const errores = [];

    if (!correoInput.value.trim() || !passInput.value.trim()) {
      errores.push("Todos los campos son obligatorios.");
    }

    if (!esCorreoValido(correoInput.value)) {
      errores.push("Correo inválido.");
    }

    if (errores.length) {
      e.preventDefault();
      alert(errores.join("\n"));
    }
  });
}

/* ============================
   REGISTRO
   ============================ */

function initRegistroForm() {
  const form = document.getElementById("registerForm");
  if (!form) return; // esta página no es registro

  const correoInput = document.getElementById("correo");
  const passInput   = document.getElementById("password");
  const passConf    = document.getElementById("passwordConfirm");
  const strengthBar = document.getElementById("strengthBar");

  configurarTogglePassword("togglePass1", "password");
  configurarTogglePassword("togglePass2", "passwordConfirm");

  // Fuerza visual de contraseña
  if (passInput && strengthBar) {
    passInput.addEventListener("input", () => {
      const value = passInput.value;
      let score = 0;

      if (value.length >= 8) score++;
      if (/[A-Z]/.test(value)) score++;
      if (/\d/.test(value)) score++;

      // Reseteamos estilos
      strengthBar.style.width = "0%";
      strengthBar.className = "";

      if (score === 1) {
        strengthBar.style.width = "33%";
        strengthBar.classList.add("weak");
      } else if (score === 2) {
        strengthBar.style.width = "66%";
        strengthBar.classList.add("medium");
      } else if (score === 3) {
        strengthBar.style.width = "100%";
        strengthBar.classList.add("strong");
      }
    });
  }

  form.addEventListener("submit", (e) => {
    const errores = [];

    if (!correoInput.value.trim() ||
        !passInput.value.trim()   ||
        !passConf.value.trim()) {
      errores.push("Todos los campos son obligatorios.");
    }

    if (!esCorreoValido(correoInput.value)) {
      errores.push("Correo inválido.");
    }

    if (passInput.value !== passConf.value) {
      errores.push("Las contraseñas no coinciden.");
    }

    // misma regla que en PHP: mín 8, una mayúscula, un número
    if (!(/[A-Z]/.test(passInput.value) &&
          /\d/.test(passInput.value) &&
          passInput.value.length >= 8)) {
      errores.push("La contraseña debe tener 8+ caracteres, 1 mayúscula y 1 número.");
    }

    if (errores.length) {
      e.preventDefault();
      alert(errores.join("\n"));
    }
  });
}

/* ============================
   RECUPERAR CONTRASEÑA
   ============================ */

function initRecuperarForm() {
  // Primer formulario: solicitar código
  const reqCodeForm = document.getElementById("reqCodeForm");
  const correoRecInput = document.getElementById("correo_recuperacion");

  if (reqCodeForm && correoRecInput) {
    reqCodeForm.addEventListener("submit", (e) => {
      const errores = [];
      if (!correoRecInput.value.trim()) {
        errores.push("El correo es obligatorio.");
      }
      if (!esCorreoValido(correoRecInput.value)) {
        errores.push("Ingrese un correo válido.");
      }
      if (errores.length) {
        e.preventDefault();
        alert(errores.join("\n"));
      }
    });
  }

  // Segundo formulario: aplicar código y nueva contraseña
  const applyCodeForm = document.getElementById("applyCodeForm");
  if (!applyCodeForm) return; // si no está, no seguimos

  const codigoInput   = document.getElementById("codigo");
  const newPassInput  = document.getElementById("nueva_contrasena");
  const confPassInput = document.getElementById("confirmar_contrasena");

  configurarTogglePassword("toggleNew",  "nueva_contrasena");
  configurarTogglePassword("toggleNew2", "confirmar_contrasena");

  applyCodeForm.addEventListener("submit", (e) => {
    const errs = [];

    if (!codigoInput.value.trim()) {
      errs.push("El código de recuperación es obligatorio.");
    }

    if (!newPassInput.value.trim() || !confPassInput.value.trim()) {
      errs.push("Completa ambos campos de contraseña.");
    }

    if (newPassInput.value !== confPassInput.value) {
      errs.push("Las contraseñas no coinciden.");
    }

    if (!( /[A-Z]/.test(newPassInput.value) &&
           /\d/.test(newPassInput.value) &&
           newPassInput.value.length >= 8 )) {
      errs.push("La contraseña debe tener 8+ caracteres, 1 mayúscula y 1 número.");
    }

    if (errs.length) {
      e.preventDefault();
      alert(errs.join("\n"));
    }
  });
}
