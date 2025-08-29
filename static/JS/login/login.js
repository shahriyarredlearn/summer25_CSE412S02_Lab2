document.addEventListener("DOMContentLoaded", () => {
  const togglePassword = document.querySelector(".toggle-password");
  const passwordInput = document.querySelector("#password");

  togglePassword.addEventListener("click", () => {
    if (passwordInput.type === "password") {
      passwordInput.type = "text";
      togglePassword.textContent = "🙈";
    } else {
      passwordInput.type = "password";
      togglePassword.textContent = "👁️";
    }
  });

  // Mobile navbar toggle
  const menuToggle = document.querySelector(".menu-toggle");
  const navLinks = document.querySelector(".nav-links");

  menuToggle.addEventListener("click", () => {
    navLinks.classList.toggle("show");
  });
});
