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

## Deploy on Railway

[![Deploy on Railway](https://railway.com/button.svg)](https://railway.com/deploy/Kb5B1S?utm_medium=integration&utm_source=button&utm_campaign=momcrm)

The template sets up the app, a queue worker, the scheduler, MySQL and a bucket for uploaded files. It asks for one thing: the email address of the first account.

- **Signing in.** The first account's password is generated. Copy `ADMIN_PASSWORD` from the MomCRM service's variables, sign in with it, then change it in the app.
- **Email.** Emails are only written to the logs until you set `MAIL_MAILER` to `smtp` and fill in the `MAIL_*` variables. Sign-in codes and invitations need it.
- **Your own domain.** Add it to the MomCRM service and set `APP_URL` to it.

Anywhere else behind a load balancer or a host's proxy, set `TRUSTED_PROXIES` so MomCRM knows requests arrive over HTTPS. See `.env.example`.

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
