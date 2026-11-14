import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import SwitchCurrentTeamController from '@/actions/App/Team/Controllers/SwitchCurrentTeamController';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';
import type { Team } from '@/types';

export function TeamSwitcher() {
    const { auth } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    if (!auth.currentTeam) {
        return null;
    }

    const switchTeam = (team: Team) => {
        if (team.id === auth.currentTeam?.id) {
            return;
        }

        router.visit(SwitchCurrentTeamController(), {
            data: { team_id: team.id },
            preserveScroll: true,
        });
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            className="h-8 gap-2 px-1.5 font-semibold text-foreground data-[state=open]:bg-sidebar-accent"
                            tooltip={auth.currentTeam.name}
                            data-test="team-switcher-button"
                        >
                            <span className="flex size-5 shrink-0 items-center justify-center rounded-md bg-foreground text-background">
                                <AppLogoIcon className="size-3 fill-current" />
                            </span>
                            <span className="truncate">
                                {auth.currentTeam.name}
                            </span>
                            <ChevronsUpDown className="size-3.5! shrink-0 text-muted-foreground" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'right'
                                  : 'bottom'
                        }
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Teams
                        </DropdownMenuLabel>
                        {auth.teams.map((team) => (
                            <DropdownMenuItem
                                key={team.id}
                                onSelect={() => switchTeam(team)}
                            >
                                <span className="truncate">{team.name}</span>
                                {team.id === auth.currentTeam?.id && (
                                    <Check className="ml-auto size-4" />
                                )}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
