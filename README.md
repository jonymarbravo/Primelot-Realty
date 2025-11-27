# 🏘️ Primelot Realty - Real Estate Booking System

![Primelot Realty Banner](https://img.shields.io/badge/Primelot-Realty-orange?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Status](https://img.shields.io/badge/Status-Live-success?style=for-the-badge)

> **Live Demo:** [https://primelot.kesug.com/](https://primelot.kesug.com/)

A comprehensive web-based real estate booking platform developed for **educational purposes**. This system allows users to browse properties, schedule appointments, and manage their bookings while providing administrators with powerful management tools.

---

## 📋 Table of Contents

- [Features](#-features)
- [Screenshots](#-screenshots)
- [Technologies Used](#-technologies-used)
- [System Requirements](#-system-requirements)
- [Installation](#-installation)
- [Database Setup](#-database-setup)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [Project Structure](#-project-structure)
- [Admin Credentials](#-admin-credentials)
- [GitHub Repository Notes](#-github-repository-notes)
- [Contributing](#-contributing)
- [License](#-license)
- [Disclaimer](#-disclaimer)
- [Contact](#-contact)

---

## ✨ Features

### 🔐 **Authentication System**
- User registration with email verification
- Secure login with password hashing (bcrypt)
- Password reset via email verification code (10-minute expiry)
- Session management with timeout
- Role-based access control (User/Admin)

### 👤 **User Features**
- **Property Browsing**: View modern small and luxurious large mansions
- **Appointment Booking**: Schedule property viewings (Monday-Friday, 8:00 AM - 4:00 PM)
- **Appointment Management**: View, track, and cancel appointments
- **Profile Management**: 
  - Edit profile information
  - Change password
  - Delete account permanently
- **Support System**: Contact support with categorized ticket system
- **Help Center**: FAQ and documentation access
- **Responsive Design**: Mobile-friendly interface

### 🛠️ **Admin Features**
- **Dashboard**: Welcome page with navigation
- **Appointment Management**: 
  - View all user appointments
  - Delete appointments
  - Track booking history
- **Statistics & Analytics**:
  - Monthly appointment trends (Chart.js visualization)
  - Current month statistics
  - Total appointments tracking
  - Average appointments per month
  - Interactive bar chart with current month highlighting
- **Real-time Session Monitoring**: Auto-logout on session expiry

### 📧 **Email Features**
- Password reset verification codes
- Support request confirmations
- Account deletion confirmations
- Professional HTML email templates with gradient designs

### 🔒 **Security Features**
- Password hashing with PHP `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF protection via session validation
- Session timeout (24 hours)
- Rate limiting for appointments (max 2 per time slot)

---

## 📸 Screenshots

### Home Page
*Modern landing page with property showcase*

### User Dashboard
*Appointment booking and management interface*

### Admin Dashboard
*Statistics and appointment management*

### Profile Management
*User profile with advanced options*

---

## 🛠️ Technologies Used

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Custom styling with gradients and animations
- **Bootstrap 5.3.3** - Responsive framework
- **Font Awesome 6.2.0** - Icon library
- **Chart.js 3.9.1** - Data visualization

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL/MariaDB** - Database management
- **PHPMailer 6.x** - Email functionality

### APIs & Libraries
- **Google Fonts (Poppins)** - Typography
- **SMTP (Gmail)** - Email delivery

### Hosting
- **InfinityFree** - Free web hosting
- **Custom Domain** - kesug.com

---

## 💻 System Requirements

- **Web Server**: Apache 2.4+
- **PHP**: 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.4+
- **SMTP Server**: Gmail (or any SMTP service)
- **Browser**: Modern browsers (Chrome, Firefox, Safari, Edge)

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/jonymarbravo/Primelot-Realty.git
cd Primelot-Realty
```

### 2. Install PHPMailer

**⚠️ IMPORTANT NOTE:** The PHPMailer library is **included** in this repository. However, if you experience any issues, you can reinstall it using one of these methods:

#### **Method A: Composer (Recommended)**
```bash
composer require phpmailer/phpmailer
```

#### **Method B: Manual Download**
1. Download PHPMailer from [GitHub Releases](https://github.com/PHPMailer/PHPMailer/releases)
2. Extract the archive
3. Copy the `PHPMailer/src/` folder to your project root
4. Ensure the structure matches:
   ```
   PHPMailer/
   └── src/
       ├── Exception.php
       ├── PHPMailer.php
       └── SMTP.php
   ```

#### **Method C: Direct Clone**
```bash
git clone https://github.com/PHPMailer/PHPMailer.git
cp -r PHPMailer/src ./PHPMailer/
rm -rf PHPMailer/.git
```

### 3. Upload to Web Server

Upload all files to your web server's `htdocs` or `public_html` directory:

```
htdocs/
├── admin_and_users_css/
│   ├── admin_page.css
│   └── user_page.css
├── PHPMailer/
│   ├── src/
│   │   ├── Exception.php
│   │   ├── PHPMailer.php
│   │   └── SMTP.php
├── Images/
│   └── [all image files]
├── admin_page.php
├── user_page.php
├── index.php
├── login_register.php
├── config.php
├── check_session.php
├── logout.php
├── update_profile.php
├── change_password.php
├── send_support.php
├── delete_account.php
└── README.md
```

---

## 🗄️ Database Setup

### 1. Import Database Schema

Import the provided SQL file `if0_39993233_users_db.sql` into your MySQL database:

```sql
-- Via phpMyAdmin:
-- 1. Go to phpMyAdmin
-- 2. Select your database
-- 3. Click "Import" tab
-- 4. Choose the SQL file
-- 5. Click "Go"
```

### 2. Database Structure

The system uses the following tables:

#### **users** table
```sql
- id (Primary Key)
- name (VARCHAR 255)
- email (VARCHAR 255, UNIQUE)
- password (VARCHAR 255, HASHED)
- role (ENUM: 'user', 'admin')
- created_at (TIMESTAMP)
```

#### **appointments** table
```sql
- id (Primary Key)
- user_id (Foreign Key → users.id)
- full_name (VARCHAR 255)
- age (INT)
- gender (ENUM: 'Male', 'Female', 'Other')
- contact_number (VARCHAR 20)
- appointment_date (DATE)
- appointment_time (TIME)
- status (ENUM: 'pending', 'completed', 'cancelled')
- created_at (TIMESTAMP)
```

#### **password_reset_tokens** table
```sql
- id (Primary Key)
- email (VARCHAR 255, Foreign Key → users.email)
- token (VARCHAR 6)
- expires_at (DATETIME)
- created_at (TIMESTAMP)
```

#### **support_requests** table (Optional)
```sql
- id (Primary Key)
- user_id (Foreign Key → users.id)
- user_name (VARCHAR 255)
- user_email (VARCHAR 255)
- subject (VARCHAR 255)
- message (TEXT)
- status (ENUM: 'pending', 'resolved', 'closed')
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### 3. Create Admin Account

The default admin account is included in the SQL file:

```
Email: admin@primelot.com
Password: admin123
```

**⚠️ IMPORTANT: Change this password immediately after first login!**

---

## ⚙️ Configuration

### 1. Database Configuration

**⚠️ SECURITY NOTE:** The `config.php` file in this repository contains placeholder values only. You must update it with your actual database credentials.

**Setup Instructions:**

1. Open `config.php` in a text editor

2. Replace the placeholder values with your actual database credentials:
   ```php
   <?php 
   $host = "your_host";           // e.g., "localhost" or "sql301.infinityfree.com"
   $user = "your_username";       // Database username
   $password = "your_password";   // Database password
   $database = "your_database";   // Database name

   $conn = new mysqli($host, $user, $password, $database);

   if ($conn->connect_error) {
       die("Connection failed: " . $conn->connect_error);
   }
   ?>
   ```

3. **IMPORTANT:** Never commit real database credentials to public repositories!

### 2. Email Configuration

Configure SMTP settings in the following files:
- `login_register.php` (Password reset emails)
- `send_support.php` (Support emails)
- `delete_account.php` (Account deletion emails)

Update these lines:

```php
$mail->Username   = 'your_email@gmail.com';        // Your Gmail
$mail->Password   = 'your_app_password';           // Gmail App Password
```

**How to get Gmail App Password:**
1. Go to Google Account Settings
2. Security → 2-Step Verification
3. App Passwords → Generate new password
4. Use the 16-character password

---

## 📖 Usage

### For Users

1. **Register**: Create an account with name, email, and password
2. **Login**: Access your dashboard
3. **Browse Properties**: View available mansions and properties
4. **Book Appointment**: Select date, time, and property
5. **Manage Appointments**: View, track, or cancel bookings
6. **Profile Management**: Update profile, change password, or delete account
7. **Get Support**: Contact support team for assistance

### For Administrators

1. **Login**: Use admin credentials
2. **View Dashboard**: Access admin panel
3. **Manage Appointments**: 
   - Click "Appointments" to view all bookings
   - Delete appointments as needed
4. **View Statistics**:
   - Click "Statistics" for analytics
   - View monthly trends and charts
5. **Logout**: Secure logout from admin panel

---

## 📁 Project Structure

```
primelot-realty/
│
├── admin_and_users_css/          # Stylesheets
│   ├── admin_page.css            # Admin dashboard styles
│   └── user_page.css             # User interface styles
│
├── PHPMailer/                    # Email library
│   └── src/
│       ├── Exception.php
│       ├── PHPMailer.php
│       └── SMTP.php
│
├── Images/                       # Image assets
│   ├── logo-black.svg
│   ├── logo-white.svg
│   ├── profile-pic.jpg
│   ├── cover-photo.jpg
│   └── [property images]
│
├── admin_page.php                # Admin dashboard
├── user_page.php                 # User dashboard
├── index.php                     # Login/Register page
├── login_register.php            # Authentication handler
├── config.php                    # Database configuration (template)
├── check_session.php             # Session validation API
├── logout.php                    # Logout handler
├── update_profile.php            # Profile update handler
├── change_password.php           # Password change handler
├── send_support.php              # Support ticket handler
├── delete_account.php            # Account deletion handler
├── hash.php                      # Password hash generator (utility)
├── test_email.php                # Email testing utility
├── if0_39993233_users_db.sql    # Database schema
└── README.md                     # Documentation
```

---

## 🔑 Admin Credentials

**Default Admin Account:**
```
Email: admin@primelot.com
Password: admin123
```

**⚠️ Security Notice:**
- Change the default password immediately after installation
- Do not share admin credentials
- Use strong passwords (minimum 8 characters, mixed case, numbers, symbols)

---

## 📦 GitHub Repository Notes

### Configuration File Security

The `config.php` file in this repository contains **placeholder values only** for security reasons. Real database credentials should never be committed to public repositories.

**Before deploying:**
1. Update `config.php` with your actual database credentials
2. Ensure `config.php` is in your `.gitignore` if you fork this repo
3. Never commit files containing passwords or API keys

### Committing Large Projects to GitHub

If you're experiencing issues with large repositories, here are proven solutions:

#### **Option 1: Use Git Command Line (Recommended)**
The command line doesn't have the 100-file limit:

```bash
# Initialize repository
git init

# Add all files (including PHPMailer)
git add .

# Commit everything at once
git commit -m "Initial commit with all files"

# Add remote repository
git remote add origin https://github.com/yourusername/primelot-realty.git

# Push to GitHub
git push -u origin main
```

#### **Option 2: Use .gitignore + Dependency Manager**
Create a `.gitignore` file to exclude vendor libraries:

```gitignore
# .gitignore
PHPMailer/
vendor/
node_modules/
.env
config.php
*.log
```

Then document dependencies in `composer.json`:

```json
{
    "require": {
        "phpmailer/phpmailer": "^6.9"
    }
}
```

Users install dependencies with: `composer install`

#### **Option 3: GitHub Desktop**
Use [GitHub Desktop](https://desktop.github.com/) - it handles large commits automatically:
1. Download and install GitHub Desktop
2. Add your local repository
3. Commit all files at once
4. Push to GitHub

#### **Option 4: Git LFS (Large File Storage)**
For projects with large binary files:

```bash
# Install Git LFS
git lfs install

# Track large files
git lfs track "*.psd"
git lfs track "*.mp4"

# Commit normally
git add .
git commit -m "Add large files"
git push
```

#### **Option 5: Split Commits by Directory**
If forced to use web interface:

```bash
# Commit in batches
git add admin_and_users_css/
git commit -m "Add CSS files"

git add Images/
git commit -m "Add images"

git add *.php
git commit -m "Add PHP files"

git push
```

### Recommended Approach for This Project

1. **Use Git command line** to commit everything including PHPMailer
2. **OR** exclude PHPMailer and use Composer for dependency management
3. **Add `.gitignore`** for sensitive files like `config.php`

### `.gitignore` Template

Create a `.gitignore` file in your project root:

```gitignore
# Dependencies (if using Composer)
vendor/

# Sensitive Configuration
config.php
.env

# Logs
*.log
error_log

# OS Files
.DS_Store
Thumbs.db

# IDE
.vscode/
.idea/
*.swp
*.swo

# Temporary files
*.tmp
*.bak
*~
```

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Contribution Guidelines
- Follow PSR-12 coding standards for PHP
- Write clean, commented code
- Test thoroughly before submitting
- Update documentation as needed
- Never commit sensitive credentials or API keys

---

## 📄 License

This project is licensed under the **MIT License**.

```
MIT License

Copyright (c) 2025 Jony Mar Barrete

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## ⚠️ Disclaimer

**IMPORTANT EDUCATIONAL NOTICE:**

This website is created for **educational purposes only**. 

- All property information, images, and pricing are for **demonstration purposes** and should not be considered factual.
- Images used are sourced from free stock platforms such as **Pexels** and **Pixabay**, with full credit to their respective owners.
- This is a **learning project** and not a real real estate business.
- No actual transactions, bookings, or services are provided.
- All content is fictional and used solely for design practice and portfolio demonstration.

**Privacy & Data:**
- User data is stored for demonstration purposes only
- Do not enter sensitive or real personal information
- This system should not be used for production without proper security audits

**Security Notice:**
- The `config.php` file in this repository contains placeholder values only
- Real database credentials should never be stored in public repositories
- Always use environment variables or secure configuration management in production

---

## 📞 Contact

**Developer:** Jony Mar Barrete

- **Email:** jonymarbarrete88@gmail.com
- **GitHub:** [@jonymarbravo](https://github.com/jonymarbravo)
- **Facebook:** [Jony Mar Barrete](https://www.facebook.com/jonymar.barrete.1)
- **Instagram:** [@barrzzz69](https://www.instagram.com/barrzzz69/)
- **LinkedIn:** [Jony Mar Barrete](https://www.linkedin.com/in/jonymar-barrete-520733387/)
- **Portfolio:** [Live Demo](https://primelot.kesug.com/)

---

## 🌟 Acknowledgments

- **Bootstrap Team** - For the amazing responsive framework
- **Font Awesome** - For beautiful icons
- **Chart.js** - For data visualization
- **PHPMailer** - For email functionality
- **Pexels & Pixabay** - For free stock images
- **InfinityFree** - For free hosting services
- **Google Fonts** - For Poppins typography

---

## 📊 Project Stats

- **Lines of Code:** ~16,850+
- **Files:** 123+
- **Development Time:** Educational Project
- **Technologies:** 8+ (PHP, MySQL, Bootstrap, etc.)
- **Features:** 25+
- **Database Tables:** 4

---

## 🚧 Future Enhancements

- [ ] Payment gateway integration (PayPal, Stripe)
- [ ] SMS notifications for appointments
- [ ] Property comparison feature
- [ ] Virtual property tours (360° view)
- [ ] User reviews and ratings
- [ ] Multi-language support
- [ ] Advanced search filters
- [ ] Property recommendations based on user preferences
- [ ] Admin dashboard analytics improvements
- [ ] Mobile app (React Native)

---

## 📝 Changelog

### Version 1.0.0 (2025)
- ✅ Initial release
- ✅ User authentication system
- ✅ Appointment booking system
- ✅ Admin dashboard
- ✅ Email notifications
- ✅ Profile management
- ✅ Support ticket system
- ✅ Account deletion feature
- ✅ Statistics and analytics

---

<div align="center">

### ⭐ If you found this project helpful, please give it a star!

**Made with ❤️ by Jony Mar Barrete**

[🌐 Visit Live Site](https://primelot.kesug.com/) | [📧 Contact Developer](mailto:jonymarbarrete88@gmail.com) | [📂 GitHub Repository](https://github.com/jonymarbravo/Primelot-Realty)

---

**© 2025 Primelot Realty - Educational Purposes Only**

</div>
