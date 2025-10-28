# TODO: Enable Real-Time Messages in Tickets

## Objective
Make messages in tickets update in real-time using auto-polling every 3 seconds.

## Tasks
- [x] Uncomment the auto-refresh interval in script.js to enable polling every 3 seconds.
- [ ] Test the auto-refresh functionality on both view_tickets.php and update_ticket.php.
- [ ] Ensure messages appear instantly after sending without manual refresh.

## Notes
- Auto-polling is simpler than WebSockets and doesn't require additional server setup.
- The refreshMessages function already exists and fetches messages via AJAX.
- After sending a message, it refreshes immediately, and polling continues.
