# Web-based Irrigation Management System with SMS Notification

A capstone project for managing irrigation operations, farmer records, schedules, requests, reports, and SMS notifications.

## Features

- Farmer registration and profile management
- Irrigation area management
- Request and scheduling system
- Task assignment and tracking
- SMS notification integration
- Reports and logs
- User management and authentication

## Tech Stack

- PHP
- MySQL
- XAMPP
- HTML, CSS, JavaScript

## Project Structure

- `areas/` - irrigation area management
- `farmers/` - farmer records and dashboard
- `forms/` - printable and encoded forms
- `includes/` - shared PHP files, config, auth, SMS integration
- `logs/` - system logs
- `pages/` - login and dashboard pages
- `reports/` - reporting module
- `requests/` - farmer/service requests
- `schedules/` - irrigation schedules
- `sms_logs/` - SMS history
- `tasks/` - technician tasks
- `users/` - user management

## Setup Instructions

1. Copy the project to `C:\xampp\htdocs\testIMS`
2. Start Apache and MySQL in XAMPP
3. Create a database named `nia_irrigation`
4. Import your SQL file manually in phpMyAdmin
5. Create `includes/.env` for secrets

## Environment Variables

Create `includes/.env` and add:

```env
SMS_API_KEY=your_api_key_here
SMS_API_SECRET=your_api_secret_here
SMS_SENDER=IrrigationSystem
DB_HOST=localhost
DB_NAME=nia_irrigation
DB_USER=root
DB_PASS="# NIA-IMS" 
