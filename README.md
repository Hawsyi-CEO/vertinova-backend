# Vertinova Backend API

Backend API untuk aplikasi Vertinova Finance menggunakan Laravel 11.

🌐 **Production:** https://vertinova.id/api

## Requirements

- PHP 8.2 atau lebih tinggi
- Composer
- MySQL 8.0+ atau MariaDB 10.6+
- Node.js 18+ & NPM (untuk Laravel Vite)
- Nginx atau Apache (untuk production)

## Installation (Development)

1. Clone repository ini:
```bash
git clone https://github.com/Hawsyi-CEO/vertinova-backend.git
cd vertinova-backend
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Setup environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Konfigurasi database di file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vertinova
DB_USERNAME=root
DB_PASSWORD=
```

5. Jalankan migrasi dan seeder:
```bash
php artisan migrate:fresh --seed
```

6. Jalankan development server:
```bash
php artisan serve
```

API akan berjalan di `http://localhost:8000`

## Deployment (Production)

Lihat [DEPLOYMENT_GUIDE.md](../DEPLOYMENT_GUIDE.md) untuk panduan lengkap deployment ke production.

### Quick Deploy

```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart php8.2-fpm nginx
```

## API Endpoints

### Authentication
- `POST /api/register` - Register user baru
- `POST /api/login` - Login user
- `POST /api/logout` - Logout user (requires auth)

### Transactions
- `GET /api/transactions` - Get semua transaksi
- `POST /api/transactions` - Buat transaksi baru
- `GET /api/transactions/{id}` - Get detail transaksi
- `PUT /api/transactions/{id}` - Update transaksi
- `DELETE /api/transactions/{id}` - Hapus transaksi

### Employee Payments
- `GET /api/employee-payments` - Get semua pembayaran karyawan
- `POST /api/employee-payments` - Buat pembayaran baru
- `GET /api/employee-payments/{id}` - Get detail pembayaran
- `PUT /api/employee-payments/{id}` - Update pembayaran
- `DELETE /api/employee-payments/{id}` - Hapus pembayaran

## Testing

```bash
php artisan test
```

## Tech Stack

- Laravel 11
- MySQL
- Laravel Sanctum (API Authentication)

## License

MIT

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
