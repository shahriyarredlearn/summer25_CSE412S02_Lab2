// Global Variables
let currentUser = null;
let users = JSON.parse(localStorage.getItem('users') || '{}');
let userFiles = JSON.parse(localStorage.getItem('userFiles') || '{}');

// Initialize the application
document.addEventListener('DOMContentLoaded', function () {
    checkAuthStatus();
    setupEventListeners();
});

// Setup Event Listeners
function setupEventListeners() {
    const loginForm = document.getElementById('loginFormElement');
    const registerForm = document.getElementById('registerFormElement');
    const uploadForm = document.getElementById('fileUploadForm');
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');

    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    if (registerForm) registerForm.addEventListener('submit', handleRegister);
    if (uploadForm) uploadForm.addEventListener('submit', handleFileUpload);
    if (searchInput) searchInput.addEventListener('input', displayFiles);
    if (sortSelect) sortSelect.addEventListener('change', displayFiles);
}

// Check if user is already logged in
function checkAuthStatus() {
    const savedUser = localStorage.getItem('currentUser');
    if (savedUser) {
        currentUser = JSON.parse(savedUser);
        if (document.getElementById('fileSection')) {
            showFileSection();
        }
    }
}

// Toggle between login and register forms
function toggleAuth() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    loginForm.classList.toggle('hidden');
    registerForm.classList.toggle('hidden');

    document.getElementById('messageContainer').innerHTML = '';
}

// Handle user login
function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    if (users[email] && users[email].password === password) {
        currentUser = { email: email };
        localStorage.setItem('currentUser', JSON.stringify(currentUser));
        showMessage('Login successful!', 'success');

        setTimeout(() => {
            window.location.href = 'upload.html'; // Redirect after login
        }, 1000);
    } else {
        showMessage('Invalid email or password', 'error');
    }
}

// Handle user registration
function handleRegister(e) {
    e.preventDefault();
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (password !== confirmPassword) {
        showMessage('Passwords do not match', 'error');
        return;
    }

    if (users[email]) {
        showMessage('Email already registered', 'error');
        return;
    }

    users[email] = { password: password };
    localStorage.setItem('users', JSON.stringify(users));
    showMessage('Account created successfully! Please login.', 'success');

    setTimeout(() => {
        toggleAuth();
        document.getElementById('registerFormElement').reset();
    }, 1500);
}

// Handle file upload with redirect to files.html
function handleFileUpload(e) {
    e.preventDefault();
    const fileName = document.getElementById('fileName').value;
    const fileInput = document.getElementById('fileInput');
    const file = fileInput.files[0];

    if (!file) {
        showMessage('Please select a file', 'error');
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        showMessage('File size must be less than 5MB', 'error');
        return;
    }

    const reader = new FileReader();
    reader.onload = function (event) {
        const fileData = {
            id: Date.now().toString(),
            name: fileName,
            originalName: file.name,
            type: file.type,
            size: file.size,
            data: event.target.result,
            uploadDate: new Date().toISOString()
        };

        if (!userFiles[currentUser.email]) {
            userFiles[currentUser.email] = [];
        }

        userFiles[currentUser.email].push(fileData);
        localStorage.setItem('userFiles', JSON.stringify(userFiles));

        showMessage('File uploaded successfully!', 'success');
        document.getElementById('fileUploadForm').reset();

        // Redirect to files.html
        setTimeout(() => {
            window.location.href = 'files.html';
        }, 1000);
    };

    reader.readAsDataURL(file);
}

// Show file management section
function showFileSection() {
    document.getElementById('authSection')?.classList.add('hidden');
    document.getElementById('fileSection')?.classList.remove('hidden');
    document.getElementById('welcomeMessage').textContent = `Welcome, ${currentUser.email}!`;
    displayFiles();
}

// Display user's files
function displayFiles() {
    const searchInput = document.getElementById('searchInput');
    const filter = searchInput?.value.toLowerCase() || '';
    const filesContainer = document.getElementById('filesContainer');
    const files = userFiles[currentUser.email] || [];

    let filteredFiles = files.filter(file => {
        return file.name.toLowerCase().includes(filter) ||
            file.originalName.toLowerCase().includes(filter) ||
            formatDate(file.uploadDate).toLowerCase().includes(filter);
    });

    const sortValue = document.getElementById('sortSelect')?.value;

    filteredFiles.sort((a, b) => {
        if (sortValue === 'name') return a.name.localeCompare(b.name);
        if (sortValue === 'size') return a.size - b.size;
        if (sortValue === 'date') return new Date(b.uploadDate) - new Date(a.uploadDate);
        return 0;
    });

    if (filteredFiles.length === 0) {
        filesContainer.innerHTML = '<p style="text-align: center; color: #666;">No files match your search</p>';
        return;
    }

    filesContainer.innerHTML = filteredFiles.map(file => `
        <div class="file-item">
            <div class="file-icon">${getFileIcon(file.originalName)}</div>
            <div class="file-info">
                <div class="file-name">${escapeHtml(file.name)}</div>
                <div class="file-type">${escapeHtml(file.originalName)} (${formatFileSize(file.size)})</div>
                <div class="file-date" style="font-size: 0.8rem; color: #888; margin-top: 3px;">
                    Uploaded: ${formatDate(file.uploadDate)}
                </div>
            </div>
            <div>
                <button onclick="downloadFile('${file.id}')" class="btn">Download</button>
                <button onclick="deleteFile('${file.id}')" class="delete-btn">Delete</button>
            </div>
        </div>
    `).join('');
}

// Determine file icon
function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    switch (ext) {
        case 'pdf': return '📄';
        case 'jpg': case 'jpeg': case 'png': case 'gif': return '🖼️';
        case 'doc': case 'docx': return '📃';
        case 'xls': case 'xlsx': return '📊';
        case 'txt': return '📝';
        case 'zip': case 'rar': return '📦';
        default: return '📁';
    }
}

// Download file
function downloadFile(fileId) {
    const files = userFiles[currentUser.email] || [];
    const file = files.find(f => f.id === fileId);

    if (file) {
        const link = document.createElement('a');
        link.href = file.data;
        link.download = file.originalName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showMessage('File downloaded successfully!', 'success');
    }
}

// Delete file
function deleteFile(fileId) {
    if (confirm('Are you sure you want to delete this file?')) {
        userFiles = JSON.parse(localStorage.getItem('userFiles') || '{}');

        if (userFiles[currentUser.email]) {
            userFiles[currentUser.email] = userFiles[currentUser.email].filter(f => f.id !== fileId);
            localStorage.setItem('userFiles', JSON.stringify(userFiles));
            showMessage('File deleted successfully!', 'success');
            displayFiles();
        } else {
            showMessage('No files found for current user.', 'error');
        }
    }
}

// Logout
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        currentUser = null;
        localStorage.removeItem('currentUser');
        window.location.href = 'index.html'; // Back to login
    }
}

// Show messages
function showMessage(message, type) {
    const messageContainer = document.getElementById('messageContainer');
    const messageClass = type === 'success' ? 'success-message' : 'error-message';
    if (messageContainer) {
        messageContainer.innerHTML = `<div class="${messageClass}">${escapeHtml(message)}</div>`;
        setTimeout(() => {
            messageContainer.innerHTML = '';
        }, 3000);
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Clear all data (for testing)
function clearAllData() {
    if (confirm('This will delete all users and files. Are you sure?')) {
        localStorage.removeItem('users');
        localStorage.removeItem('userFiles');
        localStorage.removeItem('currentUser');
        location.reload();
    }
}
function displayFiles() {
  const user = localStorage.getItem("currentUser");
  if (!user) {
    alert("Please log in first.");
    window.location.href = "index.html";
    return;
  }

  fetch("backend/fetch_files.php?email=" + encodeURIComponent(user))
    .then(response => response.json())
    .then(files => {
      const container = document.getElementById("filesContainer");
      container.innerHTML = "";

      if (!files.length) {
        container.innerHTML = "<p style='text-align:center;'>No files found.</p>";
        return;
      }

      files.forEach(file => {
        const div = document.createElement("div");
        div.innerHTML = `
          <strong>${file.original_name}</strong> <br>
          Type: ${file.file_type} | Size: ${Math.round(file.file_size / 1024)} KB | Uploaded: ${file.upload_date}
          <hr>
        `;
        container.appendChild(div);
      });
    })
    .catch(error => {
      console.error("Error fetching files:", error);
      document.getElementById("filesContainer").innerHTML = "<p>Error loading files.</p>";
    });
}
