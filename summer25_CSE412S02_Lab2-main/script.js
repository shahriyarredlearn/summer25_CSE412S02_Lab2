// HTML/script.js — server-backed version

let currentUser = null;

// Utility: show message
function showMessage(text, type = 'success') {
  const box = document.getElementById('messageContainer');
  if (!box) return;
  box.innerHTML = `<div class="notice ${type}">${escapeHtml(text)}</div>`;
  setTimeout(() => (box.innerHTML = ''), 3000);
}

function escapeHtml(text) {
  const d = document.createElement('div');
  d.textContent = text;
  return d.innerHTML;
}

// Toggle forms on index
function toggleAuth() {
  document.getElementById('loginForm')?.classList.toggle('hidden');
  document.getElementById('registerForm')?.classList.toggle('hidden');
}

// On load
document.addEventListener('DOMContentLoaded', async () => {
  // Wire forms
  document.getElementById('loginFormElement')?.addEventListener('submit', handleLogin);
  document.getElementById('registerFormElement')?.addEventListener('submit', handleRegister);
  document.getElementById('fileUploadForm')?.addEventListener('submit', handleFileUpload);
  document.getElementById('searchInput')?.addEventListener('input', displayFiles);
  document.getElementById('sortSelect')?.addEventListener('change', displayFiles);

  // Who am I?
  await refreshMe();

  // Page-specific
  if (document.getElementById('filesContainer')) {
    await displayFiles();
  }
});

async function refreshMe() {
  try {
    const res = await fetch('../api/me.php', { credentials: 'include' });
    if (res.ok) {
      const data = await res.json();
      currentUser = { email: data.email };
      const welcome = document.getElementById('welcomeMessage');
      if (welcome) welcome.textContent = `Welcome, ${currentUser.email}!`;
    } else {
      currentUser = null;
      // If on files or upload page and not logged in, bounce to index.
      const onProtected = location.pathname.endsWith('files.html') || location.pathname.endsWith('upload.html');
      if (onProtected) location.href = './index.html';
    }
  } catch (e) {
    console.error(e);
  }
}

// Login
async function handleLogin(e) {
  e.preventDefault();
  const email = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value;

  const res = await fetch('../api/login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ email, password })
  });
  const data = await res.json();
  if (!res.ok) {
    showMessage(data.error || 'Login failed', 'error');
    return;
  }
  showMessage('Login successful', 'success');
  setTimeout(() => (location.href = './upload.html'), 800);
}

// Register
async function handleRegister(e) {
  e.preventDefault();
  const email = document.getElementById('registerEmail').value.trim();
  const password = document.getElementById('registerPassword').value;
  const confirmPassword = document.getElementById('confirmPassword').value;

  if (password !== confirmPassword) {
    showMessage('Passwords do not match', 'error');
    return;
  }
  const res = await fetch('../api/register.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ email, password })
  });
  const data = await res.json();
  if (!res.ok) {
    showMessage(data.error || 'Registration failed', 'error');
    return;
  }
  showMessage('Registration successful', 'success');
  setTimeout(() => (location.href = './upload.html'), 800);
}

// Upload with progress (XHR for progress)
async function handleFileUpload(e) {
  e.preventDefault();
  const input = document.getElementById('fileInput');
  const file = input?.files?.[0];
  if (!file) {
    showMessage('Please choose a file', 'error');
    return;
  }

  const formData = new FormData();
  formData.append('file', file);

  const wrap = document.getElementById('progressWrap');
  const bar = document.getElementById('progressBar');
  const txt = document.getElementById('progressText');
  wrap?.classList.remove('hidden');
  bar.value = 0; txt.textContent = '0%';

  await new Promise((resolve) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../api/upload.php');
    xhr.withCredentials = true;
    xhr.upload.addEventListener('progress', (evt) => {
      if (evt.lengthComputable) {
        const pct = Math.round((evt.loaded / evt.total) * 100);
        bar.value = pct; txt.textContent = pct + '%';
      }
    });
    xhr.onload = function () {
      try {
        const data = JSON.parse(xhr.responseText || '{}');
        if (xhr.status >= 200 && xhr.status < 300 && data.ok) {
          showMessage('Uploaded!', 'success');
          setTimeout(() => (location.href = './files.html'), 700);
        } else {
          showMessage(data.error || 'Upload failed', 'error');
        }
      } catch {
        showMessage('Upload failed', 'error');
      }
      resolve();
    };
    xhr.onerror = function () {
      showMessage('Network error during upload', 'error');
      resolve();
    };
    xhr.send(formData);
  });
}

// List files, with search/sort client-side
async function displayFiles() {
  if (!currentUser) return;
  const container = document.getElementById('filesContainer');
  const q = (document.getElementById('searchInput')?.value || '').toLowerCase();
  const sortBy = document.getElementById('sortSelect')?.value || 'date';

  container.innerHTML = '<div class="sub">Loading...</div>';

  const res = await fetch('../api/list_files.php', { credentials: 'include' });
  const data = await res.json();
  if (!res.ok) {
    container.innerHTML = '<div class="notice error">Failed to load files.</div>';
    return;
  }

  let files = data.files || [];
  // search filter
  files = files.filter(f => {
    const dateText = new Date(f.upload_date).toLocaleString();
    return (
      f.original_name.toLowerCase().includes(q) ||
      dateText.toLowerCase().includes(q)
    );
  });

  // sort
  files.sort((a, b) => {
    if (sortBy === 'name') return a.original_name.localeCompare(b.original_name);
    if (sortBy === 'size') return (b.file_size - a.file_size);
    if (sortBy === 'type') return a.file_type.localeCompare(b.file_type);
    // date default
    return new Date(b.upload_date) - new Date(a.upload_date);
  });

  if (files.length === 0) {
    container.innerHTML = '<div class="sub">No files found.</div>';
    return;
  }

  container.innerHTML = files.map(f => {
    const icon = getFileIcon(f.original_name);
    const sizeKB = Math.round(f.file_size / 1024);
    const when = new Date(f.upload_date).toLocaleString();
    return `
      <div class="card">
        <div class="icon">${icon}</div>
        <div class="meta">
          <div class="name">${escapeHtml(f.original_name)}</div>
          <div class="sub">${escapeHtml(f.file_type)} · ${sizeKB} KB · ${escapeHtml(when)}</div>
        </div>
        <div class="actions">
          <a class="btn" href="../api/download.php?id=${f.id}">Download</a>
          <button class="btn" onclick="deleteFile(${f.id})">Delete</button>
        </div>
      </div>
    `;
  }).join('');
}

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

async function deleteFile(id) {
  if (!confirm('Delete this file?')) return;
  const res = await fetch('../api/delete_file.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ id })
  });
  const data = await res.json();
  if (!res.ok) {
    showMessage(data.error || 'Delete failed', 'error');
    return;
  }
  showMessage('Deleted', 'success');
  displayFiles();
}

async function logout() {
  await fetch('../api/logout.php', { method: 'POST', credentials: 'include' });
  location.href = './index.html';
}
