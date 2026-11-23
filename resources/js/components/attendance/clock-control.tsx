import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, Clock, Coffee, LogOut, Play } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    browserTimezone,
    formatClockTime,
    formatDuration,
    secondsSince,
    useNow,
} from '@/lib/attendance';
import { cn } from '@/lib/utils';
import { clockIn, clockOut, time as attendanceTime } from '@/routes/attendance';
import {
    end as endBreak,
    start as startBreak,
} from '@/routes/attendance/break';

type Step = { url: string; data?: Record<string, string | undefined> };

/**
 * Clock in, take a break and clock out from the header of every page. While
 * clocked in it shows the time worked so far, or how long the break has run.
 */
export function ClockControl() {
    const { attendanceClock } = usePage().props;
    const [busy, setBusy] = useState(false);
    const clockedIn = Boolean(attendanceClock?.clocked_in_at);
    const now = useNow(clockedIn);

    if (!attendanceClock) {
        return null;
    }

    const send = ({ url, data }: Step) => {
        router.post(url, data ?? {}, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
            onError: (errors) =>
                toast.error(
                    errors.attendance ?? 'That didn’t work. Try again.',
                ),
        });
    };

    if (!attendanceClock.clocked_in_at) {
        return (
            <Button
                variant="outline"
                size="sm"
                className="h-7 gap-1.5 px-2"
                disabled={busy}
                onClick={() =>
                    send({
                        url: clockIn.url(),
                        data: { timezone: browserTimezone() },
                    })
                }
            >
                <Play className="size-3.5!" />
                Clock in
            </Button>
        );
    }

    const onBreak = attendanceClock.on_break_since !== null;
    const breakSeconds =
        attendanceClock.break_seconds +
        (attendanceClock.on_break_since
            ? secondsSince(attendanceClock.on_break_since, now)
            : 0);
    const worked =
        secondsSince(attendanceClock.clocked_in_at, now) - breakSeconds;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={busy}
                    className="h-7 gap-1.5 px-2 tabular-nums"
                    aria-label={
                        onBreak
                            ? `On a break for ${formatDuration(secondsSince(attendanceClock.on_break_since ?? '', now))}`
                            : `Clocked in, ${formatDuration(worked)} worked`
                    }
                >
                    <span
                        aria-hidden
                        className={cn(
                            'size-1.5 rounded-full',
                            onBreak ? 'bg-warning' : 'bg-success',
                        )}
                    />
                    {onBreak
                        ? `Break · ${formatDuration(secondsSince(attendanceClock.on_break_since ?? '', now))}`
                        : formatDuration(worked)}
                    <ChevronDown className="size-3.5! text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-52">
                <DropdownMenuLabel className="text-xs font-normal text-muted-foreground">
                    Clocked in at{' '}
                    {formatClockTime(attendanceClock.clocked_in_at)} ·{' '}
                    {formatDuration(worked)} worked
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {onBreak ? (
                    <DropdownMenuItem
                        onSelect={() => send({ url: endBreak.url() })}
                    >
                        <Play />
                        End break
                    </DropdownMenuItem>
                ) : (
                    <DropdownMenuItem
                        onSelect={() => send({ url: startBreak.url() })}
                    >
                        <Coffee />
                        Take a break
                    </DropdownMenuItem>
                )}
                <DropdownMenuItem
                    onSelect={() => send({ url: clockOut.url() })}
                >
                    <LogOut />
                    Clock out
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href={attendanceTime()}>
                        <Clock />
                        My time
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
