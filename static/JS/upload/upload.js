// Show selected file name
const fileInput = document.getElementById("file");
const fileName = document.getElementById("file-name");

if (fileInput) {
  fileInput.addEventListener("change", () => {
    if (fileInput.files.length > 0) {
      fileName.textContent = fileInput.files[0].name;
    } else {
      fileName.textContent = "No file chosen";
    }
  });
}
