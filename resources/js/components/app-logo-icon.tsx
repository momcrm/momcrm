import type { SVGAttributes } from 'react';

/**
 * The MomCRM mark: a mother leaning over the baby she holds in the crook of
 * her arm. It is drawn in the current text colour.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="7.3" r="4.3" fill="currentColor" />
            <circle cx="17.6" cy="19.2" r="3.7" fill="currentColor" />
            <path
                d="M7.2 15.5C5.5 22.5 10.5 27.8 17 27.8c5.2 0 8.6-3.6 8.6-8.3"
                fill="none"
                stroke="currentColor"
                strokeWidth="4"
                strokeLinecap="round"
            />
        </svg>
    );
}
