// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(toggle => {
  toggle.addEventListener('click', () => {
    let input = toggle.previousElementSibling;
    if (input.type === "password") {
      input.type = "text";
      toggle.textContent = "🙈";
    } else {
      input.type = "password";
      toggle.textContent = "👁️";
    }
  });
});

// Password match check
document.querySelector('.register-card').addEventListener('submit', function(e) {
  const pass = document.getElementById('password').value;
  const confirm = document.getElementById('confirm_password').value;

  if (pass !== confirm) {
    e.preventDefault();
    alert("❌ Passwords do not match!");
  }
});
