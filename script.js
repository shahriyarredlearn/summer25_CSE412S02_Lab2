// Global Variables
let currentUser = null;
let users = JSON.parse(localStorage.getItem('users') || '{}');
let userFiles = JSON.parse(localStorage.getItem('userFiles') || '{}');

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    checkAuthStatus();
    setupEventListeners();
});

// Setup Event Listeners
function setupEventListeners() {
    document.getElementById('loginFormElement').addEventListener('submit', handleLogin);
    document.getElementById('registerFormElement').addEventListener('submit', handleRegister);
    document.getElementById('fileUploadForm').addEventListener('submit', handleFileUpload);
}

// Check if user is already logged in
function checkAuthStatus() {
    const savedUser = localStorage.getItem('currentUser');
    if (savedUser) {
        currentUser = JSON.parse(savedUser);
        showFileSection();
    }
}

// Toggle between login and register forms
function toggleAuth() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    loginForm.classList.toggle('hidden');
    registerForm.classList.toggle('hidden');
    
    // Clear any existing messages
    document.getElementById('messageContainer').innerHTML = '';
}

// Handle user login
function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    // Validate credentials
    if (users[email] && users[email].password === password) {
        currentUser = { email: email };
        localStorage.setItem('currentUser', JSON.stringify(currentUser));
        showMessage('Login successful!', 'success');
        
        setTimeout(() => {
            showFileSection();
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

    // Validate password confirmation
    if (password !== confirmPassword) {
        showMessage('Passwords do not match', 'error');
        return;
    }

    // Check if email already exists
    if (users[email]) {
        showMessage('Email already registered', 'error');
        return;
    }

    // Register new user
    users[email] = { password: password };
    localStorage.setItem('users', JSON.stringify(users));
    showMessage('Account created successfully! Please login.', 'success');
    
    // Switch to login form after successful registration
    setTimeout(() => {
        toggleAuth();
        document.getElementById('registerFormElement').reset();
    }, 1500);
}

// Handle file upload
function handleFileUpload(e) {
    e.preventDefault();
    const fileName = document.getElementById('fileName').value;
    const fileInput = document.getElementById('fileInput');
    const file = fileInput.files[0];

    // Validate file selection
    if (!file) {
        showMessage('Please select a file', 'error');
        return;
    }

    // Check file size (max 5MB for demo)
    if (file.size > 5 * 1024 * 1024) {
        showMessage('File size must be less than 5MB', 'error');
        return;
    }

    // Read file and store
    const reader = new FileReader();
    reader.onload = function(event) {
        const fileData = {
            id: Date.now().toString(),
            name: fileName,
            originalName: file.name,
            type: file.type,
            size: file.size,
            data: event.target.result,
            uploadDate: new Date().toISOString()
        };

        // Initialize user files array if it doesn't exist
        if (!userFiles[currentUser.email]) {
            userFiles[currentUser.email] = [];
        }

        // Add file to user's files
        userFiles[currentUser.email].push(fileData);
        localStorage.setItem('userFiles', JSON.stringify(userFiles));

        showMessage('File uploaded successfully!', 'success');
        document.getElementById('fileUploadForm').reset();
        displayFiles();
    };

    reader.readAsDataURL(file);
}

// Show file management section
function showFileSection() {
    document.getElementById('authSection').classList.add('hidden');
    document.getElementById('fileSection').classList.remove('hidden');
    document.getElementById('welcomeMessage').textContent = `Welcome, ${currentUser.email}!`;
    displayFiles();
}

// Display user's files
function displayFiles() {
    const filesContainer = document.getElementById('filesContainer');
    const files = userFiles[currentUser.email] || [];

    if (files.length === 0) {
        filesContainer.innerHTML = '<p style="text-align: center; color: #666;">No files uploaded yet</p>';
        return;
    }

    filesContainer.innerHTML = files.map(file => `
        <div class="file-item">
            <div class="file-info">
                <div class="file-name">${escapeHtml(file.name)}</div>
                <div class="file-type">${escapeHtml(file.originalName)} (${formatFileSize(file.size)})</div>
                <div class="file-date" style="font-size: 0.8rem; color: #888; margin-top: 3px;">
                    Uploaded: ${formatDate(file.uploadDate)}
                </div>
            </div>
            <div>
                <button onclick="downloadFile('${file.id}')" class="btn" 
                        style="padding: 8px 12px; font-size: 0.9rem; margin-right: 10px; width: auto;">
                    Download
                </button>
                <button onclick="deleteFile('${file.id}')" class="delete-btn">Delete</button>
            </div>
        </div>
    `).join('');
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
        userFiles[currentUser.email] = userFiles[currentUser.email].filter(f => f.id !== fileId);
        localStorage.setItem('userFiles', JSON.stringify(userFiles));
        showMessage('File deleted successfully!', 'success');
        displayFiles();
    }
}

// Logout user
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        currentUser = null;
        localStorage.removeItem('currentUser');
        document.getElementById('authSection').classList.remove('hidden');
        document.getElementById('fileSection').classList.add('hidden');
        document.getElementById('loginFormElement').reset();
        document.getElementById('registerFormElement').reset();
        document.getElementById('messageContainer').innerHTML = '';
        showMessage('Logged out successfully!', 'success');
    }
}

// Show success/error messages
function showMessage(message, type) {
    const messageContainer = document.getElementById('messageContainer');
    const messageClass = type === 'success' ? 'success-message' : 'error-message';
    
    messageContainer.innerHTML = `<div class="${messageClass}">${escapeHtml(message)}</div>`;
    
    // Auto-hide message after 3 seconds
    setTimeout(() => {
        messageContainer.innerHTML = '';
    }, 3000);
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

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Clear all data (for testing purposes)
function clearAllData() {
    if (confirm('This will delete all users and files. Are you sure?')) {
        localStorage.removeItem('users');
        localStorage.removeItem('userFiles');
        localStorage.removeItem('currentUser');
        location.reload();
    }
}

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+L for logout (when logged in)
    if (e.ctrlKey && e.key === 'l' && currentUser) {
        e.preventDefault();
        logout();
    }
    
    // Escape key to close any open modals or forms
    if (e.key === 'Escape') {
        document.getElementById('messageContainer').innerHTML = '';
    }
});