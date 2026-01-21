# Strona - Car Rental System

## Overview
A PHP-based vehicle rental web application with multi-language support (Polish, English, Ukrainian). Users can register, login, browse available vehicles, and make reservations.

## Project Structure
```
├── css/              # Stylesheets
│   └── style.css
├── data/             # JSON data storage
│   ├── users.json
│   ├── vehicles.json
│   └── reservations.json
├── images/           # Vehicle and logo images
├── includes/         # PHP includes
│   ├── config.php    # Main configuration
│   ├── db.php        # Database helpers
│   ├── lang.php      # Language system
│   ├── header.php    # Page header
│   ├── footer.php    # Page footer
│   └── langs/        # Translation files
│       ├── en.php
│       ├── pl.php
│       └── uk.php
├── index.php         # Dashboard (requires login)
├── login.php         # Login page
├── register.php      # Registration page
├── vehicles.php      # Browse vehicles
├── reserve.php       # Make reservations
├── reservations.php  # View reservations
├── profile.php       # User profile
└── schema.sql        # Database schema (optional)
```

## Running the Application
The app runs on PHP's built-in server:
```bash
php -S 0.0.0.0:5000
```

## Data Storage
- Primary: JSON files in `data/` directory
- Optional: MySQL database (falls back to JSON if unavailable)

## Languages
- Polish (pl) - default
- English (en)
- Ukrainian (uk)

Change language by appending `?lang=en` to any URL.

## Recent Changes
- 2026-01-21: Project restructured for Replit environment
  - Created proper directory structure (includes/, css/, data/, images/)
  - Moved language files to includes/langs/
  - Configured PHP server on port 5000
