<?php

declare(strict_types=1);

namespace App\Connector\Controllers;

use App\Connector\Actions\FindConnector;
use App\Connector\Types\Cloudflare\CloudflareConnector;
use App\Connector\Types\Cloudflare\DeliveredMail;
use App\Mailbox\Actions\FindMailbox;
use App\Mailbox\Jobs\TakeInDeliveredMailJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Where the Worker in a connected Cloudflare account hands over an email
 * that Email Routing received: the whole email as the body, and the address
 * it was sent to in a header.
 *
 * Nobody is signed in. The Worker proves itself with the secret it was given
 * when the account was connected, and the email is kept only for an address
 * that is a mailbox going through that account. Anyone else is told there is
 * nothing here, whatever they got wrong, so the answer says nothing about
 * which addresses take mail.
 */
final class ReceiveCloudflareMailController
{
    public function __invoke(Request $request): Response
    {
        $header = $request->header('X-Mail-To');
        $address = mb_strtolower(rawurldecode(is_string($header) ? $header : ''));

        $mailbox = FindMailbox::byAddress($address);
        $connector = FindConnector::byId($mailbox->getConnectorId()->toNullableString());

        abort_if($mailbox->isNull() || ! $connector->connectorType() instanceof CloudflareConnector, 404);
        abort_unless(hash_equals(CloudflareConnector::deliverySecret($connector->account()), $request->bearerToken() ?? ''), 404);

        $source = $request->getContent();

        abort_if($source === '', 422);

        TakeInDeliveredMailJob::dispatch(
            $mailbox->getId()->toString(),
            DeliveredMail::forAddress($address)->keep($source),
        );

        return response()->noContent();
    }
}
