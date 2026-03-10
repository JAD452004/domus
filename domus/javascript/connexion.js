document.addEventListener("DOMContentLoaded", () => {
  const container = document.getElementById("container");
  const signUpBtn = document.getElementById("signUp");
  const signInBtn = document.getElementById("signIn");
  const forgotBtn = document.getElementById("forgot-password-btn");
  const closeForgotBtn = document.getElementById("close-forgot-btn");

  signUpBtn.addEventListener("click", () => {
    container.classList.add("right-panel-active");
  });

  signInBtn.addEventListener("click", () => {
    container.classList.remove("right-panel-active");
  });

  forgotBtn.addEventListener("click", (e) => {
    e.preventDefault();
    container.classList.add("forgot-active");
  });

  closeForgotBtn.addEventListener("click", () => {
    container.classList.remove("forgot-active");
  });

  function showToast(message, type = "success") {
    const toastContainer = document.getElementById("toast-container");
    const toast = document.createElement("div");
    toast.classList.add("toast", type);
    const icon =
      type === "success" ? "fa-check-circle" : "fa-exclamation-circle";
    toast.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;
    toastContainer.appendChild(toast);
    setTimeout(() => toast.classList.add("show"), 100);
    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 400);
    }, 3000);
  }

  document
    .getElementById("form-signup")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      const btn = this.querySelector("button");
      const originalText = btn.innerText;
      btn.innerText = "Traitement...";
      btn.style.opacity = "0.7";
      setTimeout(() => {
        showToast("Compte créé avec succès ! Bienvenue.", "success");
        btn.innerText = originalText;
        btn.style.opacity = "1";
        this.reset();
        setTimeout(
          () => container.classList.remove("right-panel-active"),
          1500,
        );
      }, 1500);
    });

  document
    .getElementById("form-signin")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      const btn = this.querySelector("button");
      const originalText = btn.innerText;
      btn.innerText = "Connexion...";
      btn.style.opacity = "0.7";
      setTimeout(() => {
        showToast("Connexion réussie ! Redirection...", "success");
        btn.innerText = originalText;
        btn.style.opacity = "1";
      }, 1500);
    });

  document
    .getElementById("form-forgot")
    .addEventListener("submit", function (e) {
      e.preventDefault();
      const btn = this.querySelector("button");
      const originalText = btn.innerText;
      btn.innerText = "Envoi...";
      setTimeout(() => {
        showToast("Lien de récupération envoyé par email.", "success");
        btn.innerText = originalText;
        container.classList.remove("forgot-active");
        this.reset();
      }, 1500);
    });
});
