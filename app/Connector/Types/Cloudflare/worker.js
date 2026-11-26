/**
 * Hands MomCRM every email that Cloudflare Email Routing receives for an
 * address it takes mail for. MomCRM puts this Worker in the Cloudflare
 * account when the account is connected, with where to hand mail over
 * (MOMCRM_URL) and the secret that proves it is this Worker (MOMCRM_SECRET).
 *
 * The email is handed over whole, as it arrived. When MomCRM cannot take it,
 * the sender's mail server is told so, and the sender hears that the email
 * did not arrive instead of it being lost without a word.
 */

const ATTEMPTS = 3;

export default {
    async email(message, env) {
        // The email can be read only once, so it is read in full first.
        const source = await new Response(message.raw).arrayBuffer();
        let status = 0;

        for (let attempt = 1; attempt <= ATTEMPTS; attempt++) {
            try {
                const response = await fetch(env.MOMCRM_URL, {
                    method: 'POST',
                    headers: {
                        Authorization: `Bearer ${env.MOMCRM_SECRET}`,
                        'Content-Type': 'message/rfc822',
                        'X-Mail-From': encodeURIComponent(message.from),
                        'X-Mail-To': encodeURIComponent(message.to),
                    },
                    body: source,
                });

                status = response.status;
            } catch {
                status = 0;
            }

            // Only trouble that may pass is worth another attempt.
            if (status !== 0 && status !== 429 && status < 500) {
                break;
            }

            if (attempt < ATTEMPTS) {
                await new Promise((resolve) =>
                    setTimeout(resolve, attempt * 1000),
                );
            }
        }

        if (status >= 200 && status < 300) {
            return;
        }

        if (status === 404) {
            message.setReject('This address is not taking mail.');
        } else if (status === 413) {
            message.setReject('This message is too large.');
        } else {
            message.setReject(
                'This message could not be delivered. Try again later.',
            );
        }
    },
};
