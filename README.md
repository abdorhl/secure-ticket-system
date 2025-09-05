# 🎫 Ticket Management System

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

> **A modern, responsive ticket management system built with PHP and Tailwind CSS for efficient incident tracking and resolution.**

## ✨ Features

### 🚀 Core Functionality
- **User Authentication** - Secure login system with role-based access control
- **Ticket Management** - Create, track, and manage support tickets
- **Problem Type Classification** - Hardware and Software problem categorization
- **Priority System** - Low, Medium, and High priority levels
- **Status Tracking** - Open, In Progress, Resolved, Closed, and Non-Resolved statuses
- **Screenshot Upload** - Attach screenshots to tickets for better context
- **Real-time Updates** - Live status updates and notifications
- **PDF Reports** - Generate professional reports for unresolved tickets
- **Audit Trail** - Complete history tracking with soft delete functionality

### 👥 User Roles
- **👤 Regular Users** - Create tickets, upload screenshots, track progress
- **👨‍💼 Administrators** - Manage all tickets, assign staff, view analytics, generate reports

### 🎨 User Interface
- **Responsive Design** - Works seamlessly on desktop, tablet, and mobile
- **Modern UI/UX** - Clean, intuitive interface built with Tailwind CSS
- **Interactive Elements** - Smooth animations and transitions
- **Professional Styling** - Company-branded design with consistent color scheme

## 🛠️ Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| **Backend** | PHP | 8.0+ |
| **Database** | MySQL | 8.0+ |
| **Frontend** | HTML5 + Tailwind CSS | Latest |
| **JavaScript** | Vanilla JS + Fetch API | ES6+ |
| **PDF Generation** | TCPDF | 6.10+ |
| **Server** | Apache/Nginx | Any |

## 📋 Prerequisites

Before running this application, make sure you have:

- ✅ **PHP 8.0+** with PDO and MySQL extensions
- ✅ **MySQL 8.0+** or MariaDB 10.0+
- ✅ **Web Server** (Apache/Nginx) or PHP built-in server
- ✅ **Composer** (for dependency management)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/abdorhl/php-tickets-system.git
cd ticket-system
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Database Setup
```sql
-- Create the database
CREATE DATABASE incident_management;
USE incident_management;

-- Import the schema
mysql -u root -p incident_management < database.sql
```

### 4. Configuration
Edit `config/database.php` with your database credentials:
```php
private $host = 'localhost';
private $database = 'incident_management';
private $username = 'your_username';
private $password = 'your_password';
```

### 5. Web Server Setup
#### Option A: PHP Built-in Server (Development)
```bash
php -S localhost:8000
```

#### Option B: Apache/Nginx (Production)
- Copy files to your web server directory
- Ensure proper permissions on `uploads/` folder
- Configure URL rewriting if needed

### 6. Access the Application
Open your browser and navigate to:
- **Development**: `http://localhost:8000`
- **Production**: `http://your-domain.com`



## 📁 Project Structure

```
ticket-system/
├── 📁 api/                    # API endpoints
│   ├── tickets.php           # Ticket CRUD operations
│   ├── users.php             # User management
│   ├── update_ticket.php     # Ticket status updates
│   ├── delete_ticket.php     # Ticket deletion
│   ├── generate_report.php   # PDF report generation
│   ├── historique.php        # Audit trail management
│   └── screenshot.php        # Screenshot uploads
├── 📁 auth/                  # Authentication
│   ├── login.php             # Login handler
│   └── logout.php            # Logout handler
├── 📁 classes/               # PHP classes
│   ├── PDFGenerator.php      # PDF generation utilities
│   └── FileUploader.php      # File upload utilities
├── 📁 config/                # Configuration files
│   └── database.php          # Database connection
├── 📁 includes/              # Shared components
│   └── header.php            # Common header
├── 📁 static/                # Static assets
│   └── logo.png              # Application logo
├── 📁 uploads/               # User uploads
│   └── screenshots/          # Screenshot storage
├── 🎫 index.php              # Main login page
├── 🏠 user_dashboard.php     # User dashboard
├── ⚙️ admin_dashboard.php    # Admin dashboard
├── 📋 ticket_details.php     # Ticket details view
├── 📚 database.sql           # Database schema
└── 📦 composer.json          # Dependencies
```

## 🔧 Configuration Options

### Database Configuration
```php
// config/database.php
private $host = 'localhost';        // Database host
private $database = 'incident_management';  // Database name
private $username = 'root';         // Database username
private $password = '';             // Database password
```

### File Upload Settings
```php
// Maximum file size (in bytes)
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Allowed file types
$allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
```

## 📱 API Endpoints

### Authentication Required Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/tickets` | Create new ticket |
| `POST` | `/api/update_ticket` | Update ticket status |
| `DELETE` | `/api/delete_ticket` | Delete ticket (soft delete) |
| `POST` | `/api/screenshot` | Upload screenshot |
| `GET` | `/api/users` | Get user list (Admin only) |
| `POST` | `/api/users` | Create new user (Admin only) |
| `DELETE` | `/api/users` | Delete user (Admin only) |
| `GET` | `/api/generate_report` | Get unresolved tickets |
| `POST` | `/api/generate_report` | Generate PDF reports |
| `GET` | `/api/historique` | Get audit trail |
| `POST` | `/api/historique` | Manage audit trail |

### Request Examples

#### Create Ticket
```json
POST /api/tickets
{
  "title": "System Error",
  "description": "Application crashes on startup",
  "priority": "high",
  "problem_type": "software"
}
```

#### Update Ticket Status
```json
POST /api/update_ticket
{
  "ticket_id": 123,
  "status": "in_progress"
}
```

## 🎨 Customization

### Styling
The application uses Tailwind CSS with custom color schemes. Modify `includes/header.php` to change colors:

```javascript
tailwind.config = {
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: 'hsl(354, 98%, 44%)',  // Customize primary color
          foreground: 'hsl(0, 0%, 100%)'
        }
        // Add more custom colors...
      }
    }
  }
}
```

### Adding New Features
1. Create new PHP files in appropriate directories
2. Add database tables if needed
3. Update navigation and routing
4. Test thoroughly before deployment

## 🧪 Testing

### Manual Testing Checklist
- [ ] User registration and login
- [ ] Ticket creation and management
- [ ] Hardware/Software problem type selection
- [ ] Screenshot upload functionality
- [ ] Admin dashboard access
- [ ] User role permissions
- [ ] PDF report generation
- [ ] Audit trail functionality
- [ ] Responsive design on mobile devices

## 🚀 Deployment

### Production Checklist
- [ ] Update database credentials
- [ ] Set proper file permissions
- [ ] Configure SSL certificate
- [ ] Set up backup procedures
- [ ] Configure error logging
- [ ] Performance optimization
- [ ] Change default passwords

### Environment Variables
```bash
# Production environment
DB_HOST=production-db-host
DB_NAME=production_db_name
DB_USER=production_user
DB_PASS=secure_password
```

## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/AmazingFeature`)
3. **Commit** your changes (`git commit -m 'Add AmazingFeature'`)
4. **Push** to the branch (`git push origin feature/AmazingFeature`)
5. **Open** a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Add comments for complex logic
- Test your changes thoroughly
- Update documentation as needed

## 📝 Changelog

### Version 2.0.0 (Current)
- ✨ Added hardware/software problem type classification
- 🎫 Enhanced ticket management system
- 👥 Improved user and admin roles
- 📸 Enhanced screenshot upload functionality
- 📊 Professional PDF report generation
- 🔍 Complete audit trail with soft delete
- 🎨 Modern responsive UI

### Version 1.0.0
- ✨ Initial release
- 🎫 Basic ticket management system
- 👥 User and admin roles
- 📸 Screenshot upload functionality
- 🎨 Modern responsive UI

### Planned Features
- 🔔 Email notifications
- 📊 Advanced analytics dashboard
- 🔍 Advanced search and filtering
- 📱 Mobile app companion
- 🌐 Multi-language support
- 🔄 API rate limiting

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Error
```bash
# Check if MySQL is running
sudo systemctl status mysql

# Verify credentials in config/database.php
# Ensure database exists
```

#### File Upload Issues
```bash
# Check upload directory permissions
chmod 755 uploads/screenshots/

# Verify PHP upload settings in php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

#### Session Issues
```bash
# Check session directory permissions
# Verify session configuration in php.ini
session.save_handler = files
session.save_path = "/tmp"
```

## 📞 Support

Need help? Here are your options:

- 🐛 **Bug Reports**: Create an issue
- 💡 **Feature Requests**: Submit a suggestion
- 📧 **Email Support**: support@company.com
- 💬 **Community**: Join our discussion forum

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Tailwind CSS** for the beautiful UI framework
- **TCPDF** for professional PDF generation
- **PHP Community** for excellent documentation
- **Contributors** who helped improve this project

---

<div align="center">

**Made with ❤️ by the Development Team**

[![GitHub](https://img.shields.io/badge/GitHub-100000?style=for-the-badge&logo=github&logoColor=white)](https://github.com)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-0077B5?style=for-the-badge&logo=linkedin&logoColor=white)](https://linkedin.com)
[![Twitter](https://img.shields.io/badge/Twitter-1DA1F2?style=for-the-badge&logo=twitter&logoColor=white)](https://twitter.com)

**⭐ Star this repository if you found it helpful!**

</div>