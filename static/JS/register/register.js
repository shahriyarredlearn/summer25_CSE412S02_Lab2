document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const password = document.getElementById("password");
    const confirm = document.getElementById("confirm");

    form.addEventListener("submit", function (e) {
        if (password.value !== confirm.value) {
            e.preventDefault();
            alert("Passwords do not match!");
        }
    });
});
