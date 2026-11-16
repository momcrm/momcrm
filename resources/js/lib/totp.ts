const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function base32Bytes(secret: string): Uint8Array<ArrayBuffer> | null {
    const clean = secret.replace(/[\s=-]/g, '').toUpperCase();
    let bits = '';

    for (const character of clean) {
        const index = BASE32.indexOf(character);

        if (index === -1) {
            return null;
        }

        bits += index.toString(2).padStart(5, '0');
    }

    const bytes = new Uint8Array(Math.floor(bits.length / 8));

    for (let index = 0; index < bytes.length; index++) {
        bytes[index] = parseInt(bits.slice(index * 8, index * 8 + 8), 2);
    }

    return bytes;
}

/**
 * The current six-digit one-time code (RFC 6238, SHA-1, 30 seconds) for a
 * base32 TOTP secret, or null when the secret isn't valid base32.
 */
export async function totpCode(
    secret: string,
    now: number = Date.now(),
): Promise<string | null> {
    const key = base32Bytes(secret);

    if (!key || key.length === 0) {
        return null;
    }

    const counter = new DataView(new ArrayBuffer(8));
    counter.setBigUint64(0, BigInt(Math.floor(now / 1000 / 30)));

    const cryptoKey = await crypto.subtle.importKey(
        'raw',
        key,
        { name: 'HMAC', hash: 'SHA-1' },
        false,
        ['sign'],
    );
    const hmac = new Uint8Array(
        await crypto.subtle.sign('HMAC', cryptoKey, counter.buffer),
    );
    const offset = hmac[hmac.length - 1] & 0x0f;
    const binary =
        ((hmac[offset] & 0x7f) << 24) |
        (hmac[offset + 1] << 16) |
        (hmac[offset + 2] << 8) |
        hmac[offset + 3];

    return String(binary % 1_000_000).padStart(6, '0');
}
