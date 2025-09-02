# Drone Soccer Management System

A comprehensive web-based management system for drone soccer tournaments and matches. This system provides tools for managing teams, matches, scores, and user permissions in drone soccer competitions.

## 🚀 Live Demo

Visit the live demo: [https://serhatdemir.com/dronesoccer](https://serhatdemir.com/dronesoccer)

## 📋 Features

### Match Management
- **Match Recording**: Create and manage drone soccer matches
- **Real-time Scoring**: Live score tracking and updates
- **Match History**: Complete history of all matches and results
- **Team Management**: Register and manage drone soccer teams

### User System
- **Multi-level Authentication**: Different user roles with specific permissions
- **User Registration**: New user signup and account management
- **Permission System**: Role-based access control
  - **Upper Level Admin**: Full system access
  - **Admin**: Administrative privileges
  - **User**: Basic access

### Administrative Tools
- **Guide System**: Built-in permission and user guide
- **Score Management**: Comprehensive scoring system
- **User Management**: Admin panel for user administration
- **Data Export**: Export match and score data

## 🛠️ Technology Stack

- **Backend**: PHP
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 4.3.1
- **Database**: MySQL (via XAMPP)
- **Email**: PHPMailer integration
- **Server**: Apache (XAMPP)

## 📦 Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser
- Text editor (optional)

### Setup Instructions

1. **Download and Install XAMPP**
   ```bash
   # Download XAMPP from https://www.apachefriends.org/
   # Install and start Apache and MySQL services
   ```

2. **Clone/Download the Project**
   ```bash
   # Place the project files in your XAMPP htdocs directory
   # Path: C:\xampp\htdocs\dronesoccer\
   ```

3. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database for the project
   - Import the database schema (if SQL file is provided)
   - Update database connection settings in configuration files

4. **Start the Application**
   ```
   http://localhost/dronesoccer
   ```

## 🎮 Usage

### For Users
1. **Registration**: Create a new account through the registration system
2. **Login**: Access the system with your credentials
3. **View Matches**: Browse current and past drone soccer matches
4. **Check Scores**: View live scores and match results

### For Administrators
1. **Match Management**: Create new matches and manage existing ones
2. **User Administration**: Manage user accounts and permissions
3. **Score Tracking**: Update and monitor match scores
4. **System Configuration**: Access administrative tools and settings

### For Upper Level Admins
1. **Full System Access**: Complete control over all system features
2. **Permission Management**: Assign and modify user roles
3. **System Monitoring**: Access to all system logs and data
4. **Configuration**: Modify system-wide settings

## 📁 Project Structure

```
dronesoccer/
├── css/                    # Stylesheets
├── js/                     # JavaScript files
│   ├── bootstrap.js        # Bootstrap framework
│   ├── bootstrap.bundle.js # Bootstrap with dependencies
│   └── *.min.js           # Minified versions
├── vendor/                 # Third-party libraries
│   └── phpmailer/         # Email functionality
├── guide.php              # User permission guide
├── index.php              # Main entry point
├── login.php              # User authentication
├── register.php           # User registration
├── score.php              # Score management
├── mac-kayitlari.php      # Match records
└── README.md              # This file
```

## 🔐 User Roles & Permissions

| Role | Permissions |
|------|------------|
| **Upper Level Admin** | Full system access, user management, system configuration |
| **Admin** | Match management, score updates, user moderation |
| **User** | View matches, check scores, basic functionality |

## 🤝 Contributing

1. Fork the project
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions:
- Visit the demo: [https://serhatdemir.com/dronesoccer](https://serhatdemir.com/dronesoccer)
- Create an issue in the project repository
- Contact the development team

## 📊 System Requirements

- **PHP**: 7.0 or higher
- **MySQL**: 5.7 or higher
- **Apache**: 2.4 or higher
- **Browser**: Modern web browser with JavaScript enabled
