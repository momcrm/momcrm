<?php

use App\Inbox\InboxChannel;
use App\Ticket\TeamTicketsChannel;
use App\Ticket\TicketChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(InboxChannel::NAME, InboxChannel::class);
Broadcast::channel(TicketChannel::NAME, TicketChannel::class);
Broadcast::channel(TeamTicketsChannel::NAME, TeamTicketsChannel::class);
