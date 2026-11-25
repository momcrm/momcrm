<?php

use App\Inbox\InboxChannel;
use App\Portal\PortalAuth;
use App\Portal\PortalCustomerTicketsChannel;
use App\Portal\PortalTicketChannel;
use App\Ticket\TeamTicketsChannel;
use App\Ticket\TicketChannel;
use Illuminate\Support\Facades\Broadcast;

// Staff, signed in to the app.
Broadcast::channel(InboxChannel::NAME, InboxChannel::class, ['guards' => ['web']]);
Broadcast::channel(TicketChannel::NAME, TicketChannel::class, ['guards' => ['web']]);
Broadcast::channel(TeamTicketsChannel::NAME, TeamTicketsChannel::class, ['guards' => ['web']]);

// Customers' contacts in the portal, signed in with its own guard.
Broadcast::channel(PortalTicketChannel::NAME, PortalTicketChannel::class, ['guards' => [PortalAuth::GUARD]]);
Broadcast::channel(PortalCustomerTicketsChannel::NAME, PortalCustomerTicketsChannel::class, ['guards' => [PortalAuth::GUARD]]);
