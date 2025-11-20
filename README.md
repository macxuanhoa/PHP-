# 🎓 Student Portal - Social Learning Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

> A modern, interactive web-based learning platform designed for Vietnamese students to share knowledge, ask questions, and collaborate on academic subjects.

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Prerequisites](#-prerequisites)
- [Installation](#-installation)
- [Database Setup](#-database-setup)
- [Project Structure](#-project-structure)
- [Usage](#-usage)
- [User Roles](#-user-roles)
- [API Endpoints](#-api-endpoints)
- [Screenshots](#-screenshots)
- [Security Features](#-security-features)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🌟 Overview

**Student Portal** is a comprehensive social learning platform that enables students to:
- 📝 Create and share educational posts organized by subjects
- 💬 Engage with peers through comments and likes
- 🏷️ Tag and categorize content for easy discovery
- 📊 Track personal learning statistics
- 🌓 Enjoy a modern UI with dark/light mode support
- 📱 Access from any device with responsive design

### Key Objectives
- Create a safe and friendly digital learning space
- Support knowledge exchange through subject-based posts
- Build an active learning community with social interaction features
- Provide efficient content management tools for administrators
- Develop teamwork and digital communication skills

---

## ✨ Features

### 👨‍🎓 For Students

#### 📚 Post Management
- ✅ Create, edit, and delete learning posts
- 🖼️ Upload images for visual content
- 🎯 Categorize posts by subjects
- 🏷️ Add multiple tags for better organization
- 🔒 Set post visibility (public/private)

#### 🤝 Social Interaction
- ❤️ Like posts to show appreciation
- 💬 Comment on posts for discussions
- 👤 View other students' profiles
- 📈 See personal activity statistics

#### 🔍 Discovery & Navigation
- 🔎 Search posts by keywords
- 🗂️ Filter by subjects and tags
- 🌐 Browse global feed of all public posts
- 📂 Access personal posts dashboard
- 📊 View subject-specific content

#### 👤 Profile Management
- 📸 Upload and update profile avatar
- ✏️ Edit personal information
- 🔐 Change password securely
- 📊 View activity statistics

### 👨‍💼 For Administrators

#### 👥 User Management
- ➕ Add new users (students/admins)
- ✏️ Edit user information
- 🗑️ Delete user accounts
- 👁️ View user analytics and rankings

#### 📖 Subject Management
- ➕ Create new subjects
- ✏️ Edit subject details
- 🗑️ Delete subjects
- 📋 View all subjects with post counts

#### 📝 Post Management
- 👁️ View all posts across the platform
- ✏️ Edit any post
- 🗑️ Delete inappropriate content
- 📊 Monitor post statistics

#### 📊 Analytics Dashboard
- 📈 System-wide statistics
- 👥 User engagement metrics
- 📝 Content activity tracking
- 🏆 Top contributors leaderboard

### 🎨 Common Features

- 🌓 **Dark/Light Mode** - Toggle theme for comfortable viewing
- 📱 **Responsive Design** - Works on desktop, tablet, and mobile
- 🔔 **Toast Notifications** - Real-time feedback for user actions
- 📧 **Contact Form** - Send messages with file attachments via PHPMailer
- 🔒 **Security** - Password hashing, SQL injection prevention, XSS protection
- 🎯 **Clean UI/UX** - Modern, intuitive interface with smooth animations

---

## 🛠️ Tech Stack

### Backend
- **PHP 8.0+** - Server-side programming language
- **MySQL/MariaDB 10.4+** - Relational database management system
- **PDO (PHP Data Objects)** - Database abstraction layer for security
- **PHPMailer 7.0** - Email sending library
- **Composer** - Dependency management

### Frontend
- **HTML5 & CSS3** - Structure and styling
- **Bootstrap 5.3** - Responsive CSS framework
- **JavaScript (ES6+)** - Client-side interactivity
- **Chart.js** - Data visualization for analytics
- **Font Awesome 6** - Icon library
- **Swiper.js** - Touch slider for carousels

### Development Tools
- **XAMPP** - Local development environment (Apache + MySQL + PHP)
- **Git** - Version control system
- **VS Code** - Recommended code editor

---

## 📦 Prerequisites

Before installing Student Portal, ensure you have:

- **PHP 8.0 or higher**
- **MySQL 8.0 or MariaDB 10.4+**
- **Apache Web Server** (included in XAMPP)
- **Composer** (for dependency management)
- **Git** (optional, for cloning repository)

---

## 🚀 Installation

### 1. Clone or Download the Repository

```bash
# Using Git
git clone https://github.com/yourusername/student-portal.git

# Or download ZIP and extract to xampp/htdocs/
```

### 2. Install Dependencies

```bash
cd student-portal
composer install
```

This will install:
- PHPMailer for email functionality

### 3. Configure Database

Edit `includes/config.php` with your database credentials:

```php
$host = 'localhost';
$dbname = 'dbs';
$username = 'root';
$password = '';
```

### 4. Start XAMPP Services

- Start **Apache** and **MySQL** from XAMPP Control Panel
- Ensure ports 80 (Apache) and 3306 (MySQL) are available

### 5. Access the Application

Open your browser and navigate to:
```
http://localhost/student-portal/
```

---

## 💾 Database Setup

### Automatic Setup

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create a new database named `dbs`
3. Import the SQL file: `dbs (1).sql`
4. Database tables will be created automatically

### Database Schema

The system uses 7 main tables:

| Table | Description |
|-------|-------------|
| `users` | User accounts (students & admins) |
| `posts` | Learning posts/articles |
| `subjects` | Academic subjects/categories |
| `comments` | User comments on posts |
| `post_likes` | Like system for posts |
| `tags` | Tags for post categorization |
| `post_tags` | Many-to-many relationship for post tags |

### Entity Relationships

```
users (1) ─────< (N) posts
posts (1) ─────< (N) comments
posts (1) ─────< (N) post_likes
posts (N) ─────< (N) tags (via post_tags)
subjects (1) ───< (N) posts
```

### Default Admin Account

After importing the database, you can login with:
- **Email:** `Nam1@gmail.com`
- **Password:** Check the database or create a new admin account

---

## 📁 Project Structure

```
student-portal/
├── admin/                      # Admin panel
│   ├── assets/                # Admin-specific CSS/JS
│   ├── handlers/              # Form processing scripts
│   ├── includes/              # Header, sidebar, footer
│   ├── add_post.php           # Create new post
│   ├── add_subject.php        # Create new subject
│   ├── add_user.php           # Create new user
│   ├── dashboard.php          # Admin dashboard
│   ├── edit_post.php          # Edit existing post
│   ├── edit_subject.php       # Edit existing subject
│   ├── edit_user.php          # Edit existing user
│   ├── manage_posts.php       # Posts management
│   ├── manage_subjects.php    # Subjects management
│   ├── manage_users.php       # Users management
│   ├── settings.php           # Admin settings
│   └── user_analytics.php     # User statistics & rankings
│
├── student/                   # Student portal
│   ├── assets/                # Student-specific CSS/JS
│   │   ├── css/              # Stylesheets
│   │   └── js/               # JavaScript files
│   ├── includes/             # Header, taskbar, theme manager
│   ├── api/                  # API endpoints
│   ├── add_post.php          # Create post form
│   ├── dashboard.php         # Student dashboard with charts
│   ├── delete_post.php       # Delete post handler
│   ├── edit_post.php         # Edit post form
│   ├── global_feed.php       # All public posts feed
│   ├── handle_comment.php    # Comment processing
│   ├── handle_like.php       # Like/unlike processing
│   ├── my_posts.php          # User's own posts
│   ├── post_detail.php       # Single post view
│   ├── profile.php           # User profile & settings
│   ├── subject_detail.php    # Subject page with posts
│   ├── subjects.php          # All subjects list
│   └── tag_detail.php        # Posts by tag
│
├── includes/                  # Shared utilities
│   ├── config.php            # Database configuration
│   ├── database.php          # Database helper functions
│   ├── helpers.php           # Common helper functions
│   └── session_manager.php   # Session handling
│
├── login_register/           # Authentication
│   ├── login_register.php    # Combined login/register page
│   └── login_register.css    # Authentication styles
│
├── home/                     # Landing page
│   ├── home.php              # Homepage
│   └── home.css              # Homepage styles
│
├── footer/                   # Shared footer
│   ├── footer.php            # Footer component
│   └── footer.css            # Footer styles
│
├── assets/                   # Global assets
│   └── uploads/              # Uploaded files
│       └── contact_attachments/  # Contact form files
│
├── uploads/                  # User-uploaded content
│   ├── avatars/              # Profile pictures
│   └── posts/                # Post images
│
├── vendor/                   # Composer dependencies
│   └── phpmailer/            # PHPMailer library
│
├── contact.php               # Contact form with email
├── logout.php                # Logout handler
├── composer.json             # Composer dependencies
├── dbs (1).sql              # Database schema
├── BaoCao_DoAn.md           # Project report (Vietnamese)
├── ERD_Visual.md            # Entity Relationship Diagram
└── README.md                # This file
```

---

## 💡 Usage

### For Students

#### 1. Register an Account
- Navigate to the registration page
- Fill in your name, email, and password
- Account is created as "student" role by default

#### 2. Create a Post
- Go to **Dashboard** → **Add Post**
- Select a subject category
- Write your title and content
- (Optional) Upload an image
- (Optional) Add tags separated by commas
- Choose visibility (public/private)
- Click **Submit**

#### 3. Interact with Posts
- **Like**: Click the heart icon on any post
- **Comment**: Click on a post to view details and add comments
- **Search**: Use the search bar to find specific content
- **Filter**: Select subjects or tags to narrow results

#### 4. Manage Profile
- Click your avatar → **Profile**
- Update personal information
- Change password
- Upload new profile picture

### For Administrators

#### 1. Access Admin Panel
- Login with admin credentials
- You'll be redirected to the admin dashboard

#### 2. Manage Users
- **Add User**: Create new student or admin accounts
- **Edit User**: Modify user information
- **Delete User**: Remove users (deletes all their content)
- **View Analytics**: See user contribution rankings

#### 3. Manage Subjects
- **Add Subject**: Create new subject categories
- **Edit Subject**: Update subject details
- **Delete Subject**: Remove subjects (reassigns posts)

#### 4. Moderate Content
- View all posts in **Manage Posts**
- Edit or delete inappropriate content
- Monitor community activity

---

## 👥 User Roles

### Student Role
- Create, edit, delete own posts
- Like and comment on posts
- View public content
- Manage personal profile
- Access personal dashboard

### Admin Role
- All student capabilities
- Manage all users
- Manage all subjects
- Moderate all posts
- Access analytics dashboard
- System configuration

---

## 🔌 API Endpoints

### User API
```
GET /student/api/users.php
- Returns list of users for mentions/autocomplete
```

### Post Operations
```
POST /student/add_post.php
- Create new post
- Parameters: title, content, subject_id, image, tags, visibility

POST /student/edit_post.php?id={post_id}
- Update existing post
- Parameters: same as add_post

POST /student/delete_post.php?id={post_id}
- Delete post
- Requires ownership or admin role
```

### Social Interactions
```
POST /student/handle_like.php
- Toggle like on post
- Parameters: post_id

POST /student/handle_comment.php
- Add comment to post
- Parameters: post_id, content
```

---

## 📸 Screenshots

### Student Portal
- **Dashboard**: Personal statistics with Chart.js visualizations
- **Global Feed**: All public posts with search and filter
- **Post Detail**: Full post view with comments and likes
- **Profile**: User information and activity stats

### Admin Panel
- **Dashboard**: System-wide statistics
- **User Management**: CRUD operations for users
- **Subject Management**: Organize learning categories
- **Analytics**: Top contributors and engagement metrics

---

## 🔒 Security Features

### Implemented Security Measures

✅ **Authentication & Authorization**
- Password hashing with `password_hash()` (bcrypt)
- Role-based access control (Student/Admin)
- Session management with secure tokens

✅ **Input Validation**
- Server-side validation for all forms
- Email format validation
- File upload type and size restrictions

✅ **SQL Injection Prevention**
- PDO prepared statements for all queries
- Parameterized queries throughout

✅ **XSS Protection**
- `htmlspecialchars()` on all user-generated output
- Content Security Policy headers

✅ **CSRF Protection**
- POST method for all data modifications
- Session validation

✅ **File Upload Security**
- Allowed file types whitelist
- File size limits (5MB for images)
- Unique filename generation
- Upload directory outside web root

### Best Practices
- Passwords are never stored in plain text
- Database credentials in separate config file
- Error messages don't expose sensitive information
- User input sanitization before processing

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. **Commit your changes**
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```
4. **Push to the branch**
   ```bash
   git push origin feature/AmazingFeature
   ```
5. **Open a Pull Request**

### Development Guidelines
- Follow PSR-12 coding standards for PHP
- Write meaningful commit messages
- Test thoroughly before submitting
- Update documentation for new features

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**Mac Xuan Hoa**
- Email: hoamxgcd220422@fpt.edu.vn
- Institution: FPT University

---

## 🙏 Acknowledgments

- **FPT University** - For academic support and resources
- **Bootstrap Team** - For the excellent CSS framework
- **PHPMailer** - For reliable email functionality
- **Chart.js** - For beautiful data visualizations
- **Font Awesome** - For comprehensive icon library
- **Open Source Community** - For inspiration and best practices

---

## 📚 Additional Documentation

For more detailed information, please refer to:
- **[BaoCao_DoAn.md](BaoCao_DoAn.md)** - Comprehensive project report (Vietnamese)
- **[ERD_Visual.md](ERD_Visual.md)** - Database entity relationship diagram

---

## 🐛 Known Issues

- [ ] Theme toggle may not persist across all pages in some browsers
- [ ] Large image uploads may timeout on slow connections
- [ ] Mobile keyboard may overlap input fields on some devices

## 🚧 Roadmap

### Upcoming Features
- [ ] Real-time notifications with WebSocket
- [ ] Direct messaging between students
- [ ] File attachment support for posts (PDF, documents)
- [ ] Advanced search with filters
- [ ] Email verification for new accounts
- [ ] Password reset via email
- [ ] Export posts to PDF
- [ ] Mobile app (React Native)

---

## 📞 Support

If you encounter any issues or have questions:

1. Check the [documentation](BaoCao_DoAn.md)
2. Search existing [issues](https://github.com/yourusername/student-portal/issues)
3. Create a new issue with detailed information
4. Contact via email: hoamxgcd220422@fpt.edu.vn

---

<div align="center">

**⭐ Star this repository if you found it helpful!**

Made with ❤️ by Mac Xuan Hoa

</div>
