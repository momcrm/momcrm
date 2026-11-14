# MomCRM

An open-source CRM built with Laravel and Inertia. Website: [momcrm.app](https://momcrm.app).

## Stack

- [Laravel 13](https://laravel.com) (PHP 8.4+)
- [Inertia.js](https://inertiajs.com) + React 19 + TypeScript
- Tailwind CSS + [shadcn/ui](https://ui.shadcn.com)
- MySQL
- Pest for tests

## Getting started

Requirements: PHP 8.4+, Composer, Node.js 22+, MySQL 8+.

```bash
git clone <repo-url> momcrm
cd momcrm
composer install
npm install
cp .env.example .env
php artisan key:generate
# create a `momcrm` database and update the DB_* values in .env as needed
php artisan migrate
composer run dev
```

The app runs at http://localhost:8000.

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

If you find a security vulnerability, please report it privately through [GitHub's private vulnerability reporting](https://github.com/momcrm/momcrm/security/advisories/new) instead of opening a public issue. See [SECURITY.md](SECURITY.md).

## License

MomCRM is open-source software licensed under the [MIT license](LICENSE).
