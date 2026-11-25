# Changelog

All notable changes to MomCRM will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Laravel 13 application with the Inertia React starter kit (React 19, TypeScript, Tailwind CSS and shadcn/ui).
- Authentication: registration, login, password reset, email verification, two-factor authentication and passkeys.
- User settings pages for profile, security (password, two-factor and passkeys) and appearance.
- MySQL as the default database.
- Pest test suite.
- GitHub Actions CI running linting, type checks and tests against PHP 8.4 and MySQL 8.4.
- Project documentation: README, contributing guide, security policy and MIT license.
- Contributor Covenant 2.1 code of conduct.
- Issue forms for bug reports and feature requests, and a pull request template.
- Release workflow that publishes a GitHub release with notes from this changelog when a version tag is pushed.
- Laravel Boost for AI coding assistants.
- mom/data v6.2.2 for data transfer objects, with its Laravel Boost guidelines.
- Architecture tests (`tests/Arch`) enforce the domain conventions: strict types, final invokable controllers, final actions, FormRequests and mom/data Properties and DTOs, string-backed enums, and a test beside every controller.
- Audit trail: every create, update and delete of users, teams and team memberships is recorded with old/new values (secrets never stored), actor, IP and request id.
- Activity feed: sign-ins, sign-outs, password, two-factor, passkey and email-verification events, plus team renames and switches. Viewable under Settings → Activity (your account) and Settings → Team → Activity (team owners).
- Onboarding: new accounts answer three optional questions, name their workspace and pick a starting point before reaching the dashboard. Existing accounts skip it.
- Landing page, sign-up and email verification copy for MomCRM, plus a welcome email once an address is confirmed.
- Team invitations: owners and admins invite people by email from onboarding or Settings → Team, see pending invites and revoke them. Invitation links work for new and existing accounts and expire after 7 days. Adds an Admin team role.
- Customers and contacts: add, search, edit and delete the businesses you work for and the people at each, with one primary contact per customer. Every record belongs to the current team and is numbered per team.
- Locations, assets and services on each customer: record their sites, the equipment you look after and what they get from you, with pricing in AUD.
- Tickets: open, assign, prioritise, close and reopen requests for a customer, numbered per team. Filter open, closed or all tickets and search by title or number. Opening, closing and reopening show in the team's activity.
- Invoices: draft invoices with line items (fill a line from a customer's service), GST at 10% by default, totals in AUD, numbered INV-0001 per team. Mark them sent, paid or void; sent invoices past their due date show as overdue.
- Starting point: new workspaces can start with sample customers (removable in one click from a banner), import customers and contacts from a CSV, or start blank. The CSV import is also on the Customers page.
- Dashboard: a getting-started checklist (add a customer, a contact, a ticket, invite a teammate, add a passkey) that ticks itself off and stays pinned in the sidebar with a progress ring until done or dismissed. Afterwards the dashboard shows open tickets, customers, unpaid invoices and your assigned tickets, plus a passkey suggestion for accounts without one.
- Friendlier confirmation messages for saving your profile, changing your password, renaming and switching teams.
- Notion-style sign-up and log-in: one email field, then a 6-digit code emailed to you (valid 10 minutes, 5 tries), then "What should we call you?" with an optional password. Passwords still work for people who set one, two-factor still applies, and people without a password confirm sensitive actions with an emailed code.
- Continue with Google or Microsoft (shown once their OAuth keys are set). Only a Google-verified email links to an existing account directly; Microsoft and unverified addresses confirm with an emailed code first.
- Structured logging: every request gets an `X-Request-Id`, and log lines carry the request, user and team ids. Logs default to daily-rotated JSON files.
- Audit and activity retention (365 and 180 days by default, `AUDIT_LOG_RETENTION_DAYS` / `ACTIVITY_RETENTION_DAYS`), pruned daily by the scheduler.
- Team-based tenancy: every user gets a personal team, can switch their current team from the sidebar, and owners can rename it under Settings → Team. Team-owned models are isolated with `CurrentTeamScope`.
- Notification emails: inbox notifications can also be emailed, with who did what, the comment it came with and a link to open it. Under Settings → Notifications each person chooses which kinds (mentions, assignments, comments, updates) they are emailed; mentions and assignments until they choose.
- Direct messages: members message one teammate or several from the inbox's new Messages tab, with unread counts on the tab and the sidebar's Inbox badge. An open inbox checks for new messages every few seconds. Sending to the same people again continues their conversation. Messages can be emailed too (on until someone chooses otherwise): the email waits five minutes, is skipped if the message has been read by then, and is sent once per run of unread messages.

### Changed

- `composer update` refreshes Laravel Boost's AI guidelines and skills (`app:boost-update`), and skips it on machines where Boost isn't set up.
- CI runs the test suite on MySQL 8.4 instead of in-memory SQLite, and tests run in parallel (`php artisan test --parallel`). ParaTest is pinned to 7.27.5 because Pest 5.3.5's parallel runner fails with ParaTest 7.28.
- Every Eloquent model must have a morph alias, defined once in `App\Shared\MorphMapKeyEnum` and enforced with `Relation::enforceMorphMap()`; tests fail if a model is added without one or a stored alias is renamed.
- All application tables (users, teams, team memberships, passkeys and the sessions user reference) use UUIDv7 primary and foreign keys. The framework's queue tables keep their own keys.
- Tests for application code live beside it in `app/` (e.g. `UpdateTeamControllerTest.php` next to `UpdateTeamController.php`) and use Pest's functional style; `tests/` keeps the test bootstrap and Fortify flow tests.
- Backend code is organised by domain (`app/User`, `app/Team`, `app/TeamUser`) with mom/data DTOs, single-action controllers and actions, replacing the starter kit's `app/Models`, `app/Http/Controllers/Settings`, `app/Http/Requests`, `app/Actions` and `app/Concerns`.
- MomCRM has its own logo, a mother holding a baby, in place of the Laravel one: in the app, the browser tab icon and the home-screen icon.
- Emails have MomCRM's own look in place of Laravel's: the app's warm neutrals and indigo, a white card on a soft background, left-aligned buttons and a dark mode. Emails a team sends its customers (invoices, receipts, statements, portal invitations and ticket replies) carry the team's name instead of MomCRM's, and show the amount up front. Sign-in codes are shown large and spaced out.

[Unreleased]: https://github.com/momcrm/momcrm/commits/main

### Fixed

- Skipped tests no longer crash: the class-map exclusion for co-located tests is limited to `app/`, so PHPUnit's own classes load again.
