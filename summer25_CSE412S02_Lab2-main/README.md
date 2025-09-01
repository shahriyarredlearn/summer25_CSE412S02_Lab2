# File Manager System

A comprehensive file management system with user authentication, role-based access control, and admin dashboard.

## 🚀 Features

### User Features
- **Authentication**: Login, Registration, Password Reset
- **File Management**: Upload, Download, Delete, Search, Sort
- **Session Management**: Secure session handling with 30-minute timeout
- **File Types**: Supports various file types (images, documents, archives, media)

### Admin Features
- **User Management**: Create, Delete, Monitor Users
- **Online Monitoring**: Track active users and their activities
- **Storage Analytics**: Monitor storage usage per user
- **Password Management**: Reset user passwords
- **System Overview**: Comprehensive system statistics

## 🏗️ System Architecture

### Backend
- **PHP 8+** with PDO database abstraction
- **MySQL 8.0** database with proper indexing
- **Docker** containerization for easy deployment
- **Session Management** with security features

### Database Schema
- `users` - User accounts and roles
- `user_files` - File metadata and storage info
- `user_sessions` - Active user sessions tracking

### Security Features
- Password hashing with bcrypt
- Session hijacking protection
- CORS configuration
- Input validation and sanitization
- Role-based access control

## 🐳 Docker Setup

### Prerequisites
- Docker and Docker Compose installed
- Port 80 available for web server
- Port 3306 available for MySQL (optional)

### Quick Start
```bash
# Clone the repository
git clone <repository-url>
cd summer25_CSE412S02_Lab2-main

# Start the system
docker-compose up -d

# Initialize database (first time only)
docker exec -it php-web php /var/www/html/init_db.php
```

### Default Credentials
- **Admin**: `admin@example.com` / `Admin123`
- **Database**: `dbuser` / `dbpassword`

## 📁 File Structure

```
├── config/
│   └── database.php          # Database configuration and functions
├── uploads/                  # File storage directory
├── index.html               # Main login page
├── files.html               # File management interface
├── admin.html               # Admin dashboard
├── auth.php                 # Authentication functions
├── login.php                # Login API endpoint
├── register.php             # Registration API endpoint
├── upload.php               # File upload handler
├── list_files.php           # File listing API
├── delete_file.php          # File deletion API
├── download.php             # File download handler
├── admin_*.php              # Admin API endpoints
└── .htaccess                # Apache configuration
```

## 🔧 Configuration

### Database Configuration
Edit `config/database.php` to modify:
- Database connection parameters
- Table creation scripts
- Default admin user

### File Upload Limits
Modify `.htaccess` for:
- Maximum file size (default: 50MB)
- Upload timeout settings
- Memory limits

### Session Configuration
Session settings in `.htaccess`:
- Timeout: 30 minutes
- Secure cookies enabled
- HTTP-only cookies
- SameSite policy

## 📱 API Endpoints

### Authentication
- `POST /login.php` - User login
- `POST /register.php` - User registration
- `POST /logout.php` - User logout
- `GET /me.php` - Current user info

### File Management
- `POST /upload.php` - File upload
- `GET /list_files.php` - List user files
- `POST /delete_file.php` - Delete file
- `GET /download.php?id=X` - Download file

### Admin Functions
- `GET /admin_list_users.php` - List all users
- `GET /admin_online_users.php` - Show online users
- `GET /admin_storage_usage.php` - Storage analytics
- `POST /admin_create_user.php` - Create new user
- `POST /admin_delete_user.php` - Delete user
- `POST /admin_reset_password.php` - Reset user password

## 🔒 Security Features

### Session Security
- Automatic session timeout (30 minutes)
- Session hijacking protection
- Secure cookie settings
- IP address tracking

### File Security
- File type validation
- Size limit enforcement
- User ownership verification
- Soft delete implementation

### Access Control
- Role-based permissions
- Admin-only functions
- User data isolation
- Input validation

## 📊 Admin Dashboard

### User Management
- View all registered users
- Monitor user activity
- Create new user accounts
- Delete user accounts
- Reset user passwords

### System Monitoring
- Real-time online user tracking
- Storage usage per user
- File upload statistics
- System performance metrics

### Analytics
- Total file count
- Storage utilization
- User activity patterns
- System health status

## 🚀 Usage Examples

### User Login
```javascript
fetch('/login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password123'
    })
});
```

### File Upload
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);

fetch('/upload.php', {
    method: 'POST',
    body: formData
});
```

### File Listing
```javascript
fetch('/list_files.php?search=document&sort=upload_date&order=DESC')
    .then(response => response.json())
    .then(data => console.log(data.files));
```

## 🐛 Troubleshooting

### Common Issues
1. **Session Expired**: Check browser cookies and session settings
2. **Upload Failed**: Verify file size limits and permissions
3. **Database Error**: Check Docker container status and database connection
4. **Permission Denied**: Ensure user has appropriate role

### Debug Commands
```bash
# Check Docker containers
docker ps

# View web server logs
docker logs php-web

# View database logs
docker logs mysql-db

# Test database connection
docker exec -it php-web php /var/www/html/config/database.php
```

## 📈 Performance Optimization

### Database
- Proper indexing on frequently queried columns
- Connection pooling with PDO
- Prepared statements for security

### File Handling
- Efficient file size calculations
- Pagination for large file lists
- Soft delete for better performance

### Session Management
- Automatic cleanup of expired sessions
- Efficient session tracking
- Minimal database queries

## 🔄 Maintenance

### Regular Tasks
- Monitor storage usage
- Clean up expired sessions
- Review user activity logs
- Update security patches

### Backup
- Database backup: `mysqldump -u dbuser -p filerepository > backup.sql`
- File backup: Copy `uploads/` directory
- Configuration backup: Version control for config files

## 📄 License

This project is developed for educational purposes as part of CSE412S02 Lab2.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For technical support or questions:
- Check the troubleshooting section
- Review Docker logs
- Verify configuration settings
- Test individual components

---

**System Status**: ✅ Fully Functional  
**Last Updated**: June 2025  
**Version**: 2.0.0



