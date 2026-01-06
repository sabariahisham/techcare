{{--
    Ticket Module Styles Partial
    ============================

    This partial contains any remaining inline styles that are specific to tickets
    and cannot be moved to SCSS files (e.g., dynamic styles, PHP-generated styles).

    Usage: @include('tickets.partials.styles')
--}}

<style>
    /* Any dynamic styles or PHP-generated CSS that cannot be in SCSS files */

    /* Example: Dynamic color based on ticket status */
    @if (isset($ticket) && $ticket->status)
        .ticket-status-{{ $ticket->status }} {
            background-color: {{ $ticket->getStatusColor() }};
        }
    @endif

    /* Override any vendor styles that conflict with ticket module */
    .datatables-tickets {
        font-size: 0.875rem;
    }

    /* Quick fixes that will be moved to SCSS later */

    /* Remove these styles once confirmed SCSS is working properly:
       - All offcanvas styles (moved to components/offcanvas-tickets.scss)
       - All form styles (moved to pages/tickets.scss)
       - All Select2 styles (moved to pages/tickets.scss)
    */
</style>
