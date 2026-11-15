import type { CSSProperties } from 'react';

/**
 * The hues pickers offer, every 30° round the OKLCH colour wheel. A record can
 * also have no hue (null), which shows as neutral grey.
 */
export const HUE_PRESETS = [
    0, 30, 60, 90, 120, 150, 180, 210, 240, 270, 300, 330,
] as const;

/**
 * The CSS variables the hue-dot, hue-text and hue-soft utilities read, for a
 * record's stored hue. No hue gives neutral grey.
 */
export function hueStyle(hue: number | null | undefined): CSSProperties {
    return {
        '--hue': hue ?? 0,
        '--hue-chroma': hue === null || hue === undefined ? 0 : 1,
    } as CSSProperties;
}
