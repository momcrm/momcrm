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
php artisan momcrm:install
composer run dev
```

`momcrm:install` asks for the address, database and mail server, writes them to `.env`, creates the database and its tables, makes your account and builds the front-end assets. It is safe to run again: it starts from the current settings and only writes what changed.

For a scripted install, put the settings in `.env` or the environment and run it without questions:

```bash
php artisan momcrm:install --no-interaction \
    --email=you@example.com --first-name=Ada --last-name=Lovelace --password='a strong password'
```

The app runs at http://localhost:8000.

## Testing

```bash
composer test
```

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) and please follow our [Code of Conduct](CODE_OF_CONDUCT.md).

## Security

If you find a security vulnerability, please report it privately through [GitHub's private vulnerability reporting](https://github.com/momcrm/momcrm/security/advisories/new) instead of opening a public issue. See [SECURITY.md](SECURITY.md).

## License

MomCRM is open-source software licensed under the [MIT license](LICENSE).
