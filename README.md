# RentaBike

A PHP-based bike rental management system built with Laravel Blade templating and modern web technologies.

## Overview

RentaBike is a comprehensive bike rental platform designed to streamline the booking, management, and tracking of bicycle rentals. Whether you're running a small local bike shop or a large-scale rental service, RentaBike provides the tools you need to manage your fleet efficiently.

## Features

- **Bike Fleet Management** - Easily add, update, and manage your bicycle inventory
- **Booking System** - Intuitive booking interface for customers to reserve bikes
- **User Authentication** - Secure login and user account management
- **Rental Tracking** - Monitor active rentals and rental history
- **Admin Dashboard** - Comprehensive dashboard for administrators to oversee operations
- **Responsive Design** - Works seamlessly on desktop, tablet, and mobile devices

## Tech Stack

- **Frontend**: Blade (62.6%) - Laravel's powerful templating engine
- **Backend**: PHP (37.1%) - Server-side logic and API endpoints
- **Framework**: Laravel - Modern PHP web application framework
- **Database**: MySQL/PostgreSQL - Data persistence
- **Other**: Additional supporting technologies (0.3%)

## Requirements

- PHP 8.0 or higher
- Composer
- Node.js & npm (for asset compilation)
- MySQL or PostgreSQL database
- Laravel 9.x or higher

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/fomalhautsanz/RentaBike.git
   cd RentaBike
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Configure database**
   - Edit `.env` and set your database credentials
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rentabike
   DB_USERNAME=root
   DB_PASSWORD=
   ```

7. **Run migrations**
   ```bash
   php artisan migrate
   ```

8. **Build assets**
   ```bash
   npm run dev
   ```

9. **Start development server**
   ```bash
   php artisan serve
   ```

Access the application at `http://localhost:8000`

## Project Structure

```
RentaBike/
├── app/              # Application logic (controllers, models, etc.)
├── resources/        # Views and asset files
│   └── views/        # Blade templates
├── database/         # Migrations and seeders
├── routes/           # Application routes
├── public/           # Public assets
└── config/           # Configuration files
```

## Usage

### For Customers
1. Create an account or log in
2. Browse available bikes
3. Select rental dates and times
4. Complete booking and payment
5. Pick up your bike and enjoy!

### For Administrators
1. Log in with admin credentials
2. Access the dashboard
3. Manage bike inventory
4. View and process rental requests
5. Generate reports and analytics

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support, please:
- Open an issue in the [GitHub Issues](https://github.com/fomalhautsanz/RentaBike/issues) section
- Contact the development team

## Roadmap

- [ ] Payment gateway integration
- [ ] Email notifications
- [ ] Advanced booking calendar
- [ ] Mobile app
- [ ] Real-time GPS tracking
- [ ] Customer reviews and ratings
- [ ] Loyalty program

## Authors

- **fomalhautsanz** - Initial work

---

**Happy renting! 🚴**