# Advertising Leads Management System

A comprehensive multi-channel advertising and leads management platform built with PHP and HTML. Manage leads from SEO, Social Media Marketing (SMM), Web campaigns, and WhatsApp automation in one centralized system.

## 📁 Project Structure

```
Adver/
├── Admin/                    # Admin dashboard and authentication
│   ├── index.php            # Admin dashboard
│   ├── login.php            # Admin login
│   ├── logout.php           # Admin logout
│   └── assets/
│       └── style.css        # Admin styling
├── SEO-Landing/             # SEO campaign landing page
│   ├── index.html
│   └── submit.php           # Form submission handler
├── SMM_Landing/             # Social Media Marketing landing page
│   ├── SMM.html
│   ├── submit.php           # Form submission handler
│   └── assets/
│       └── styles.css
├── Web_Landing/             # General web campaign landing page
│   ├── index.html
│   ├── submit.php           # Form submission handler
│   └── assets/
│       ├── script.js
│       └── style.css
├── Whatsapp_Automation/     # WhatsApp automation landing page
│   ├── index.html
│   ├── submit.php           # Form submission handler
│   └── assets/
│       ├── script.js
│       ├── style.css
│       └── tailwind.config.js
├── db_connect.php           # Database connection (⚠️ Keep private)
├── adver_leads.sql          # Database schema
├── .gitignore               # Git ignore rules
└── README.md                # This file
```

## 🚀 Features

- **Multi-Channel Lead Capture**: Collect leads from multiple marketing channels
- **Admin Dashboard**: Centralized management interface
- **Lead Tracking**: Track and manage all incoming leads
- **Responsive Design**: Mobile-friendly landing pages
- **Database Integration**: MySQL/MariaDB backend for lead storage
- **Secure Authentication**: Admin login/logout functionality

## 🔧 Setup Instructions

### Prerequisites
- XAMPP or similar PHP environment
- MySQL/MariaDB database
- PHP 7.0+
- Web browser

### Installation

1. **Clone/Extract the project** to your web root:
   ```bash
   c:\xampp\htdocs\Php\Adver\
   ```

2. **Import the database schema**:
   ```bash
   # Using phpMyAdmin or MySQL CLI
   mysql -u root < adver_leads.sql
   ```

3. **Configure database connection**:
   - Edit `db_connect.php`
   - Update database credentials:
     ```php
     $servername = "localhost";
     $username = "your_db_user";
     $password = "your_db_password";
     $dbname = "adver_leads";
     ```

4. **Start XAMPP** and access the application:
   - Admin Dashboard: `http://localhost/Php/Adver/Admin/`
   - SEO Landing: `http://localhost/Php/Adver/SEO-Landing/`
   - SMM Landing: `http://localhost/Php/Adver/SMM_Landing/`
   - Web Landing: `http://localhost/Php/Adver/Web_Landing/`
   - WhatsApp Automation: `http://localhost/Php/Adver/Whatsapp_Automation/`

## 📝 Usage

### Capturing Leads
1. Direct users to the appropriate landing page (SEO, SMM, Web, or WhatsApp)
2. Users fill out and submit forms
3. Data is automatically saved to the database via `submit.php`

### Admin Dashboard
1. Navigate to `/Admin/`
2. Log in with your credentials
3. View and manage all captured leads
4. Access analytics and reports

## 🔐 Security Considerations

- **Sensitive Data**: `db_connect.php` contains database credentials and is listed in `.gitignore`
- **Never commit** credentials or sensitive configuration
- Use environment variables for production deployments
- Implement CSRF protection on forms
- Validate and sanitize all user inputs
- Use prepared statements to prevent SQL injection

## 🗄️ Database Schema

The project uses a MySQL database with the schema defined in `adver_leads.sql`. Key tables typically include:
- Leads (with timestamp, channel, contact info, etc.)
- Admin users (for authentication)

## 🛠️ Technologies Used

- **Backend**: PHP
- **Frontend**: HTML, CSS, JavaScript
- **Framework**: Tailwind CSS (WhatsApp Automation)
- **Database**: MySQL/MariaDB
- **Server**: Apache (via XAMPP)

## 📧 Lead Submission Flow

```
Landing Page → Form Submission → submit.php → Database
                                    ↓
                            Email Notification (Optional)
```

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| Database connection error | Check `db_connect.php` credentials and MySQL service status |
| Form submission not working | Verify `submit.php` file permissions and database connection |
| Admin login issues | Check admin user exists in database |
| Styling not loading | Clear browser cache and verify asset file paths |

## 📦 File Permissions

Ensure the following have appropriate permissions:
- `db_connect.php` - 600 (Read/Write owner only)
- `Admin/` - 755 (Readable by web server)
- `submit.php` files - 755 (Readable and executable by web server)

## 📞 Support

For issues or questions:
1. Check the troubleshooting section
2. Review database logs
3. Verify file permissions
4. Check browser console for JavaScript errors

## 📄 License

All rights reserved. This project is proprietary.

---

**Last Updated**: 2026-06-27
