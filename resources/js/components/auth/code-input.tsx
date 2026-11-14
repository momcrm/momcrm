import { REGEXP_ONLY_DIGITS } from 'input-otp';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';

export const CODE_LENGTH = 6;

/**
 * Six boxes for an emailed code. Calls onComplete once all six digits are in,
 * so the form can submit itself.
 */
export default function CodeInput({
    value,
    onChange,
    onComplete,
    disabled,
}: {
    value: string;
    onChange: (value: string) => void;
    onComplete?: (value: string) => void;
    disabled?: boolean;
}) {
    return (
        <div className="flex justify-center">
            <InputOTP
                name="code"
                maxLength={CODE_LENGTH}
                value={value}
                onChange={onChange}
                onComplete={onComplete}
                disabled={disabled}
                pattern={REGEXP_ONLY_DIGITS}
                autoComplete="one-time-code"
                inputMode="numeric"
                autoFocus
            >
                <InputOTPGroup>
                    {Array.from({ length: CODE_LENGTH }, (_, index) => (
                        <InputOTPSlot key={index} index={index} />
                    ))}
                </InputOTPGroup>
            </InputOTP>
        </div>
    );
}
