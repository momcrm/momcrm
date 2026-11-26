<?php

declare(strict_types=1);

namespace App\Connector\Types\Cloudflare;

use App\Shared\Types;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mom\Connectors\Connection;
use Mom\Connectors\CouldNotReachConnector;
use Throwable;

/**
 * Cloudflare's API for one account, with what the account was connected
 * with: an API token that was pasted in, or the access a sign-in gave. Only
 * this class knows Cloudflare's addresses and the shape of what it answers.
 */
final readonly class CloudflareApi
{
    private const string BASE_URL = 'https://api.cloudflare.com/client/v4';

    /**
     * The most Cloudflare lists on one page.
     */
    private const int PAGE_SIZE = 50;

    /**
     * How many pages of a listing are read at most. A domain holds 200
     * routing rules, which is four pages.
     */
    private const int MAX_PAGES = 10;

    /**
     * The Workers runtime the Worker is written for.
     */
    private const string COMPATIBILITY_DATE = '2025-01-01';

    private const string WORKER_FILE = 'worker.js';

    public function __construct(
        private string $accountId,
        private string $token,
        private bool $isApiToken = true,
    ) {}

    /**
     * @throws CouldNotReachConnector
     */
    public static function forConnection(Connection $connection): self
    {
        $apiToken = $connection->credentials()[CloudflareTokenConnector::API_TOKEN] ?? null;

        return is_string($apiToken) && $apiToken !== ''
            ? new self($connection->account(), $apiToken)
            : new self($connection->account(), $connection->accessToken(), isApiToken: false);
    }

    /**
     * Whether Cloudflare still accepts the token. A token belongs to a
     * person or to an account, and each kind is asked about in its own place.
     *
     * @throws CouldNotReachConnector
     */
    public function tokenIsActive(): bool
    {
        foreach (['user/tokens/verify', "accounts/{$this->accountId}/tokens/verify"] as $path) {
            $response = $this->get($path);

            if ($response->status() === 429 || $response->serverError()) {
                throw CouldNotReachConnector::unavailable(CloudflareAccount::COMPANY);
            }

            if ($response->successful() && $response->json('result.status') === 'active') {
                return true;
            }
        }

        return false;
    }

    /**
     * The domains in the account that the token can see.
     *
     * @return list<array{id: string, name: string, account_name: ?string}>
     *
     * @throws CouldNotReachConnector
     */
    public function zones(): array
    {
        $zones = [];

        foreach ($this->listed('zones', (string) __("list the account's domains"), ['account.id' => $this->accountId]) as $zone) {
            $account = is_array($zone['account'] ?? null) ? $zone['account'] : [];

            if (is_string($zone['id'] ?? null) && is_string($zone['name'] ?? null)) {
                $zones[] = [
                    'id' => $zone['id'],
                    'name' => mb_strtolower($zone['name']),
                    'account_name' => is_string($account['name'] ?? null) ? $account['name'] : null,
                ];
            }
        }

        return $zones;
    }

    /**
     * The domain in the account that mail for a host belongs to: the host
     * itself, or the domain it is a subdomain of. Null when the account has
     * neither.
     *
     * @return array{id: string, name: string}|null
     *
     * @throws CouldNotReachConnector
     */
    public function zoneFor(string $host): ?array
    {
        $labels = explode('.', mb_strtolower($host));

        while (count($labels) >= 2) {
            $name = implode('.', $labels);
            $zone = $this->listed('zones', (string) __('look up the domain'), ['account.id' => $this->accountId, 'name' => $name])[0] ?? null;

            if ($zone !== null && is_string($zone['id'] ?? null)) {
                return ['id' => $zone['id'], 'name' => $name];
            }

            array_shift($labels);
        }

        return null;
    }

    /**
     * Whether Email Routing is turned on for a domain. Null when the token
     * may not look, which is not a reason to stop: routing a domain's mail
     * needs less than reading its settings.
     *
     * @throws CouldNotReachConnector
     */
    public function routingIsOn(string $zoneId): ?bool
    {
        $response = $this->get("zones/{$zoneId}/email/routing");

        if (in_array($response->status(), [401, 403], true)) {
            return null;
        }

        return ($this->result($response, (string) __("read the domain's Email Routing settings"))['enabled'] ?? null) === true;
    }

    /**
     * The routing rule for mail sent to an address, and the Worker it hands
     * that mail to, if it hands it to one. Null when the address has no rule.
     *
     * @return array{id: string, worker: ?string}|null
     *
     * @throws CouldNotReachConnector
     */
    public function ruleFor(string $zoneId, string $address): ?array
    {
        foreach ($this->listed("zones/{$zoneId}/email/routing/rules", (string) __("list the domain's routing rules")) as $rule) {
            if (! is_string($rule['id'] ?? null) || ! self::matches($rule, $address)) {
                continue;
            }

            foreach (is_array($rule['actions'] ?? null) ? $rule['actions'] : [] as $action) {
                $worker = is_array($action) && ($action['type'] ?? null) === 'worker' && is_array($action['value'] ?? null)
                    ? ($action['value'][0] ?? null)
                    : null;

                if (is_string($worker)) {
                    return ['id' => $rule['id'], 'worker' => $worker];
                }
            }

            return ['id' => $rule['id'], 'worker' => null];
        }

        return null;
    }

    /**
     * Has Cloudflare hand the mail sent to an address to a Worker.
     *
     * @throws CouldNotReachConnector
     */
    public function createRule(string $zoneId, string $address, string $worker): void
    {
        $this->result($this->write('POST', "zones/{$zoneId}/email/routing/rules", [
            'name' => "MomCRM: {$address}",
            'enabled' => true,
            'matchers' => [['type' => 'literal', 'field' => 'to', 'value' => $address]],
            'actions' => [['type' => 'worker', 'value' => [$worker]]],
        ]), (string) __('add a routing rule'));
    }

    /**
     * @throws CouldNotReachConnector
     */
    public function deleteRule(string $zoneId, string $ruleId): void
    {
        $this->result($this->write('DELETE', "zones/{$zoneId}/email/routing/rules/{$ruleId}"), (string) __('remove the routing rule'));
    }

    /**
     * Puts a Worker in the account, or replaces the one of that name, with
     * the values it reads and the secrets it keeps.
     *
     * @param  array<string, string>  $values
     * @param  array<string, string>  $secrets
     *
     * @throws CouldNotReachConnector
     */
    public function deployWorker(string $name, string $script, array $values, array $secrets): void
    {
        $bindings = [];

        foreach ($values as $key => $text) {
            $bindings[] = ['type' => 'plain_text', 'name' => $key, 'text' => $text];
        }

        foreach ($secrets as $key => $text) {
            $bindings[] = ['type' => 'secret_text', 'name' => $key, 'text' => $text];
        }

        $metadata = json_encode([
            'main_module' => self::WORKER_FILE,
            'compatibility_date' => self::COMPATIBILITY_DATE,
            'bindings' => $bindings,
        ], JSON_THROW_ON_ERROR);

        try {
            $response = $this->request()
                ->attach('metadata', $metadata, 'metadata.json', ['Content-Type' => 'application/json'])
                ->attach(self::WORKER_FILE, $script, self::WORKER_FILE, ['Content-Type' => 'application/javascript+module'])
                ->put("accounts/{$this->accountId}/workers/scripts/{$name}");
        } catch (ConnectionException $exception) {
            throw CouldNotReachConnector::unavailable(CloudflareAccount::COMPANY, $exception);
        }

        $this->result($response, (string) __('put its Worker in the account'));
    }

    /**
     * Sends an email through Email Sending, once. What Cloudflare says about
     * it comes back: the Message-ID it gave the email and who it reached.
     *
     * @param  array<string, mixed>  $email
     * @return array<array-key, mixed>
     *
     * @throws CouldNotReachConnector
     */
    public function send(array $email): array
    {
        return $this->result($this->write('POST', "accounts/{$this->accountId}/email/sending/send", $email), (string) __('send email'), [
            10102 => (string) __('The API token may not send email. Create one that has Email Sending (Edit), and connect Cloudflare again.'),
            10105 => (string) __('This Cloudflare account cannot use Email Sending yet.'),
            10203 => (string) __('Email Sending is not turned on for this domain. Turn it on in Cloudflare, under Email Service, then send again.'),
            10207 => (string) __('Cloudflare has paused Email Sending for this account.'),
            10208 => (string) __('This Cloudflare plan only sends to addresses verified in the account. The Workers Paid plan sends to anyone.'),
        ]);
    }

    /**
     * Whether a routing rule is for mail sent to exactly this address.
     *
     * @param  array<string, mixed>  $rule
     */
    private static function matches(array $rule, string $address): bool
    {
        foreach (is_array($rule['matchers'] ?? null) ? $rule['matchers'] : [] as $matcher) {
            if (is_array($matcher)
                && ($matcher['type'] ?? null) === 'literal'
                && ($matcher['field'] ?? null) === 'to'
                && is_string($matcher['value'] ?? null)
                && mb_strtolower($matcher['value']) === $address) {
                return true;
            }
        }

        return false;
    }

    /**
     * Everything a listing holds, page after page.
     *
     * @param  string  $doing  what the listing is for, to finish "Cloudflare did not let MomCRM …"
     * @param  array<string, string>  $query
     * @return list<array<string, mixed>>
     *
     * @throws CouldNotReachConnector
     */
    private function listed(string $path, string $doing, array $query = []): array
    {
        $items = [];
        $page = 1;

        do {
            $response = $this->get($path, [...$query, 'page' => (string) $page, 'per_page' => (string) self::PAGE_SIZE]);

            foreach ($this->result($response, $doing) as $item) {
                if (is_array($item)) {
                    $items[] = Types::stringKeyed($item);
                }
            }

            $pages = $response->json('result_info.total_pages');
            $page++;
        } while (is_int($pages) && $page <= min($pages, self::MAX_PAGES));

        return $items;
    }

    /**
     * What Cloudflare answered with, once it has said the request worked.
     *
     * @param  string  $doing  what was being done, to finish "Cloudflare did not let MomCRM …"
     * @param  array<int, string>  $refusals  what to tell people for the error codes this request is known to be refused with
     * @return array<array-key, mixed>
     *
     * @throws CouldNotReachConnector
     */
    private function result(Response $response, string $doing, array $refusals = []): array
    {
        if ($response->successful() && $response->json('success') === true) {
            $result = $response->json('result');

            return is_array($result) ? $result : [];
        }

        throw $this->failure($response, $doing, $refusals);
    }

    /**
     * Why Cloudflare refused, in the terms the rest of the app understands.
     * Cloudflare answers an API token it no longer accepts and one that
     * lacks a permission in the same way, so the token is asked about to
     * tell them apart: only the first is fixed by connecting again. Access
     * from a sign-in is fresh each time, so there a refusal to know us at
     * all means the sign-in was withdrawn. A refusal for want of a
     * permission says what was being done, so it is clear which one is missing.
     *
     * @param  array<int, string>  $refusals
     */
    private function failure(Response $response, string $doing, array $refusals): CouldNotReachConnector
    {
        if ($response->status() === 429 || $response->serverError()) {
            return CouldNotReachConnector::unavailable(CloudflareAccount::COMPANY);
        }

        $code = $response->json('errors.0.code');
        $reason = $response->json('errors.0.message');

        if (is_int($code) && isset($refusals[$code])) {
            return CouldNotReachConnector::refused($refusals[$code]);
        }

        if (! $this->isApiToken && in_array($response->status(), [401, 403], true)) {
            return $response->status() === 401
                ? CouldNotReachConnector::needsReconnect(CloudflareAccount::COMPANY)
                : CouldNotReachConnector::refused((string) __('Cloudflare did not let MomCRM :doing. Connect the account again and agree to everything MomCRM asks for.', ['doing' => $doing]));
        }

        if (in_array($response->status(), [401, 403], true)) {
            try {
                $isActive = $this->tokenIsActive();
            } catch (CouldNotReachConnector $exception) {
                return $exception;
            }

            return $isActive
                ? CouldNotReachConnector::refused((string) __('Cloudflare did not let MomCRM :doing. Check that the API token has every permission MomCRM asks for, and that it covers this domain.', ['doing' => $doing]))
                : CouldNotReachConnector::needsReconnect(CloudflareAccount::COMPANY);
        }

        return CouldNotReachConnector::refused(is_string($reason) && $reason !== ''
            ? (string) __('Cloudflare refused the request: :reason', ['reason' => $reason])
            : (string) __('Cloudflare refused the request.'));
    }

    /**
     * A request that changes something. It is never retried here: sending
     * one twice could send a customer the same email twice.
     *
     * @param  array<string, mixed>  $body
     *
     * @throws CouldNotReachConnector
     */
    private function write(string $method, string $path, array $body = []): Response
    {
        try {
            return $this->request()->send($method, $path, $body === [] ? [] : ['json' => $body]);
        } catch (ConnectionException $exception) {
            throw CouldNotReachConnector::unavailable(CloudflareAccount::COMPANY, $exception);
        }
    }

    /**
     * @param  array<string, string>  $query
     *
     * @throws CouldNotReachConnector
     */
    private function get(string $path, array $query = []): Response
    {
        try {
            return $this->request()
                ->retry([200, 1000], when: self::isTransient(...), throw: false)
                ->get($path, $query);
        } catch (ConnectionException $exception) {
            throw CouldNotReachConnector::unavailable(CloudflareAccount::COMPANY, $exception);
        }
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken($this->token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20);
    }

    /**
     * Failures worth another attempt: no connection, throttling and server errors.
     */
    private static function isTransient(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException
                && ($exception->response->serverError() || $exception->response->status() === 429));
    }
}
