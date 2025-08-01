# Playcloud - Music Streaming Platform

A modern, feature-rich music streaming website built with PHP, MySQL, and JavaScript. Features include AI-powered recommendations, admin panel, user management, and a beautiful dark-themed interface.

## Features

### 🎵 Music Features
- **Music Player**: Full-featured audio player with play/pause, volume control, and progress tracking
- **Playlists**: Create, manage, and share playlists
- **AI Recommendations**: Intelligent music suggestions based on listening history
- **Search**: Advanced search functionality for tracks, artists, and albums
- **Favorites**: Like and save your favorite tracks

### 🤖 AI-Powered Features
- **Smart Recommendations**: AI analyzes your listening patterns to suggest new music
- **Auto-Generated Playlists**: Create playlists based on mood, genre, and duration preferences
- **Personalized Experience**: Learning algorithm adapts to your music taste

### 👨‍💼 Admin Panel
- **Dashboard**: Comprehensive statistics and overview
- **Content Management**: Add, edit, and delete tracks, albums, artists
- **User Management**: Manage user accounts and permissions
- **Page Management**: Create and manage custom pages
- **Menu Management**: Customize site navigation
- **Database Management**: Backup and restore database

### 🎨 User Interface
- **Dark Theme**: Modern, eye-friendly dark interface
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Modern UI**: Clean, intuitive interface similar to popular streaming services
- **Real-time Updates**: Dynamic content updates without page refresh

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- cPanel hosting (recommended)

### Step 1: Upload Files
1. Upload all files to your web server directory
2. Ensure the following directories are writable:
   - `uploads/music/`
   - `uploads/covers/`
   - `database/backups/`

### Step 2: Database Setup
1. Create a MySQL database
2. Run the installation script: `yourdomain.com/install.php`
3. This will automatically create all necessary tables and sample data

### Step 3: Configuration
The system is pre-configured with your database credentials:
- **Database:** `gbtechir_loov`
- **User:** `gbtechir_mmd`
- **Password:** `H.m33343536`
- **Host:** `localhost`

### Step 4: Admin Access
- **Default Admin Account**:
  - Username: `admin`
  - Password: `admin123`
- **Login URL**: `yourdomain.com/login.php`
- **Admin Panel**: `yourdomain.com/admin/`

## File Structure

```
playcloud/
├── admin/                 # Admin panel files
│   ├── index.php         # Admin dashboard
│   ├── tracks.php        # Track management
│   ├── albums.php        # Album management
│   ├── artists.php       # Artist management
│   ├── users.php         # User management
│   ├── pages.php         # Page management
│   └── assets/           # Admin CSS/JS
├── api/                  # API endpoints
│   ├── track.php         # Track API
│   ├── ai-recommendations.php
│   ├── generate-playlist.php
│   ├── search.php
│   └── log-playback.php
├── assets/               # Frontend assets
│   ├── css/
│   │   └── style.css    # Main stylesheet
│   └── js/
│       └── player.js    # Music player
├── config/               # Configuration
│   └── database.php     # Database connection settings
├── database/             # Database management
│   ├── schema.sql       # Database table structures
│   ├── init.php         # Database initialization
│   └── backup.php       # Database backup/restore
├── includes/             # PHP includes
│   └── functions.php    # Utility functions
├── uploads/              # Uploaded files
│   ├── music/           # Audio files
│   └── covers/          # Cover images
├── index.php            # Main homepage
├── login.php            # Login page
├── register.php         # Registration page
├── install.php          # Installation script
└── README.md           # This file
```

## Database Management

### Manual Database Setup
1. **Import Schema**: Use `database/schema.sql` to create tables
2. **Initialize Data**: Run `database/init.php` to add sample data
3. **Backup/Restore**: Use `database/backup.php` for database management

### Database Files
- **`config/database.php`**: Database connection settings
- **`database/schema.sql`**: Complete database structure
- **`database/init.php`**: Database initialization and sample data
- **`database/backup.php`**: Database backup and restore functionality

## Usage

### For Users
1. **Register/Login**: Create an account or login
2. **Browse Music**: Explore tracks, albums, and playlists
3. **Create Playlists**: Build your own music collections
4. **Get Recommendations**: Discover new music through AI suggestions
5. **Search**: Find specific tracks, artists, or albums

### For Administrators
1. **Login to Admin Panel**: Use admin credentials
2. **Manage Content**: Add tracks, albums, and artists
3. **User Management**: Monitor and manage user accounts
4. **Site Customization**: Create pages and customize menus
5. **Database Management**: Backup and restore database
6. **Analytics**: View site statistics and user activity

## API Endpoints

### Track API
- `GET /api/track.php?id={track_id}` - Get track information

### AI Recommendations
- `GET /api/ai-recommendations.php` - Get personalized recommendations

### Playlist Generation
- `POST /api/generate-playlist.php` - Generate AI playlist

### Search
- `GET /api/search.php?q={query}` - Search tracks, artists, albums

### Playback Logging
- `POST /api/log-playback.php` - Log user listening activity

## Customization

### Adding New Features
1. Create new PHP files in appropriate directories
2. Add database tables if needed (use `database/schema.sql`)
3. Update admin panel for new content types
4. Add corresponding API endpoints

### Styling
- Main styles: `assets/css/style.css`
- Admin styles: `admin/assets/admin.css`
- Color variables defined in CSS root

### Database Schema
The system uses the following main tables:
- `users` - User accounts and profiles
- `tracks` - Music tracks
- `albums` - Music albums
- `artists` - Music artists
- `playlists` - User playlists
- `playlist_tracks` - Playlist-track relationships
- `listening_history` - User listening data
- `pages` - Custom pages
- `menu_items` - Navigation menu
- `ai_recommendations` - AI recommendation data

## Security Features

- **Password Hashing**: Secure password storage using PHP password_hash()
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Token-based CSRF protection
- **Session Security**: Secure session management
- **File Upload Security**: Type and size validation

## Performance Optimization

- **Database Indexing**: Optimized queries with proper indexing
- **File Caching**: Efficient file serving
- **Minified Assets**: Optimized CSS and JavaScript
- **Responsive Images**: Optimized image loading

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running

2. **File Upload Issues**
   - Check directory permissions (755 for directories, 644 for files)
   - Verify upload_max_filesize in php.ini

3. **Admin Panel Not Accessible**
   - Ensure you're logged in as admin user
   - Check file permissions

4. **Music Player Not Working**
   - Verify audio files are uploaded to `uploads/music/`
   - Check browser console for JavaScript errors

5. **Database Issues**
   - Run `database/init.php` to reinitialize database
   - Use `database/backup.php` to backup/restore data

### Support
For technical support or feature requests, please contact the development team.

## License

This project is proprietary software. All rights reserved.

## Version History

- **v1.0.0** - Initial release with core features
  - Music player functionality
  - Admin panel
  - AI recommendations
  - User management
  - Responsive design
  - Database management tools

---

**Playcloud** - Your Music, Your Way 🎵