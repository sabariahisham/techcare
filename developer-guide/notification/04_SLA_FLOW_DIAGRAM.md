# 🔄 SLA Notification Flow Diagram

## Complete Flow: Ticket Creation → Resolution

```
┌──────────────────────────────────────────────────────────────────────┐
│                         ADUAN BARU                                   │
│                    (New Ticket Created)                              │
│                                                                      │
│  ┌────────────────────────────────────────────────┐                │
│  │  Table: tickets                                │                │
│  │  - ticket_nolog: HELP-YYYY-MM-0001            │                │
│  │  - tick_status: 1 (Aduan Baru)                │                │
│  │  - user_id: Pengadu                           │                │
│  └────────────────────────────────────────────────┘                │
└──────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────────┐
│                    ASSIGN TO LEVEL 1                                 │
│                (Ketua / Supervisor)                                  │
│                                                                      │
│  ┌────────────────────────────────────────────────┐                │
│  │  Table: tickets_chronologies                   │                │
│  │  - ticket_id: [ID]                            │                │
│  │  - tick_status: 2 (Dalam Tindakan)            │                │
│  │  - escalate_to: [User ID Level 1]             │                │
│  │  - level_pegtugas_id: 1                       │                │
│  │  - sla_duedate: [Calculated Date]             │                │
│  │  - sla_severity: HIGH/MEDIUM/LOW              │                │
│  └────────────────────────────────────────────────┘                │
└──────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────────┐
│                    SLA REMINDERS (LEVEL 1)                           │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  1. SLA-1 (Due Tomorrow) - 9:00 AM Daily                   │   │
│  │     Subject: ⚠️ Peringatan SLA - Hampir Tamat Tempoh       │   │
│  │     To: Level 1 Officer (escalate_to)                      │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                              │                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  2. Due Today - 8:00 AM Daily                              │   │
│  │     Subject: 🔔 Peringatan SLA - Tamat Tempoh HARI INI     │   │
│  │     To: Level 1 Officer (escalate_to)                      │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                              │                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  3. Due Date+1 (Yesterday) - 10:00 AM Daily                │   │
│  │     Subject: ⚠️ Peringatan SLA - Tempoh Tamat Semalam      │   │
│  │     To: Level 1 Officer (escalate_to)                      │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                              │                                       │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │  4. Overdue (>1 day) - 11:00 AM Daily                      │   │
│  │     Subject: ⛔ Tindakan Segera: SLA (OVERDUE)             │   │
│  │     To: Level 1 Officer (escalate_to)                      │   │
│  └─────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────┘
                              │
                    ┌─────────┴─────────┐
                    │                   │
                    ▼                   ▼
        ┌─────────────────┐   ┌─────────────────┐
        │   LEVEL 1       │   │   LEVEL 1       │
        │   SELESAIKAN?   │   │   TIDAK DAPAT   │
        │                 │   │   SELESAIKAN?   │
        └─────────────────┘   └─────────────────┘
                │                       │
                │ YA                    │ TIDAK
                ▼                       ▼
    ┌─────────────────────┐   ┌─────────────────────────┐
    │     SELESAI         │   │  ESCALATE TO LEVEL 2    │
    │                     │   │  (Pelaksana/Technician) │
    │  tick_status: 5/6   │   │                         │
    └─────────────────────┘   └─────────────────────────┘
                                          │
                                          ▼
                        ┌──────────────────────────────────────┐
                        │  Table: tickets_chronologies (NEW)   │
                        │  - ticket_id: [ID]                  │
                        │  - tick_status: 2 (Dalam Tindakan) │
                        │  - escalate_to: [User ID Level 2]  │
                        │  - level_pegtugas_id: 2            │
                        │  - sla_duedate: [New Calculated]   │
                        │  - sla_severity: HIGH/MEDIUM/LOW   │
                        └──────────────────────────────────────┘
                                          │
                                          ▼
                        ┌──────────────────────────────────────┐
                        │   SLA REMINDERS (LEVEL 2)           │
                        │                                      │
                        │  Same 4 reminder types:             │
                        │  1. SLA-1 (Due Tomorrow)            │
                        │  2. Due Today                       │
                        │  3. Due Date+1 (Yesterday)          │
                        │  4. Overdue (>1 day)                │
                        │                                      │
                        │  To: Level 2 Officer (escalate_to)  │
                        └──────────────────────────────────────┘
                                          │
                                          ▼
                                ┌─────────────────┐
                                │    SELESAI      │
                                │                 │
                                │ tick_status: 5/6│
                                └─────────────────┘
```

## Database Tables Relationship

```
┌─────────────────────────────────────────────────────────────────────┐
│                         tickets                                      │
├─────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                             │
│ ticket_nolog                                                        │
│ user_id (FK → users)                  ┌──────────────────────────┐ │
│ cat_id (FK → ref_categories)          │                          │ │
│ subcat_id (FK → ref_subcategories)    │                          │ │
│ tick_status (FK → ref_ticket_statuses)│                          │ │
│ tick_duedate                          │                          │ │
│ ticket_title                          │                          │ │
│ ticket_desc                           │                          │ │
└───────────────────────────────────────┼──────────────────────────┘ │
                                        │                            │
                                        │ One to Many                │
                                        ▼                            │
┌──────────────────────────────────────────────────────────────────────┐
│                    tickets_chronologies                              │
├──────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                             │
│ ticket_id (FK → tickets)                                            │
│ frm_user_id (FK → users) - User yang buat action                    │
│ tick_status (FK → ref_ticket_statuses) - Status baru               │
│ escalate_to (FK → users) - Pegawai Tugasan (Level 1 atau 2)        │
│ level_pegtugas_id (FK → subcat_level_pegtugas) - 1 or 2            │
│ sla_duedate (datetime) - Tarikh akhir SLA                          │
│ sla_days (int) - Bilangan hari SLA                                 │
│ sla_severity (varchar) - HIGH / MEDIUM / LOW                       │
│ created_at, updated_at                                              │
└──────────────────────────────────────────────────────────────────────┘
                                        │
                                        │
                                        ▼
                        ┌──────────────────────────┐
                        │        users             │
                        ├──────────────────────────┤
                        │ id (PK)                 │
                        │ name                    │
                        │ email ◄── Email sent here
                        │ nama                    │
                        └──────────────────────────┘
```

## Notification Logic

### Query untuk SLA-1 (Due Tomorrow)

```sql
SELECT tc.*, t.*, u.email
FROM tickets_chronologies tc
INNER JOIN tickets t ON tc.ticket_id = t.id
INNER JOIN users u ON tc.escalate_to = u.id
WHERE tc.sla_duedate >= 'TOMORROW 00:00:00'
  AND tc.sla_duedate <= 'TOMORROW 23:59:59'
  AND t.tick_status NOT IN (5, 6)  -- Not Selesai or Tutup
  AND tc.escalate_to IS NOT NULL
```

### Query untuk Due Today

```sql
WHERE tc.sla_duedate >= 'TODAY 00:00:00'
  AND tc.sla_duedate <= 'TODAY 23:59:59'
```

### Query untuk Due Date+1 (Yesterday)

```sql
WHERE tc.sla_duedate >= 'YESTERDAY 00:00:00'
  AND tc.sla_duedate <= 'YESTERDAY 23:59:59'
```

### Query untuk Overdue (>1 day)

```sql
WHERE tc.sla_duedate < 'YESTERDAY 00:00:00'
```

## Service Method Flow

```
TicketSLAReminderService
├── sendBeforeDueReminders()
│   ├── Query: Tomorrow's due dates
│   ├── Get recipient from escalate_to
│   ├── Send: SLAReminderNotification('before_due')
│   └── Log: Success/Failed/Skipped
│
├── sendDueTodayReminders()
│   ├── Query: Today's due dates
│   ├── Get recipient from escalate_to
│   ├── Send: SLAReminderNotification('due_today')
│   └── Log: Success/Failed/Skipped
│
├── sendAfterDueReminders()
│   ├── Query: Yesterday's due dates
│   ├── Get recipient from escalate_to
│   ├── Send: SLAReminderNotification('after_due')
│   └── Log: Success/Failed/Skipped
│
├── sendOverdueNotifications()
│   ├── Query: Before yesterday
│   ├── Get recipient from escalate_to
│   ├── Send: SLAOverdueNotification()
│   └── Log: Success/Failed/Skipped
│
└── getUpcomingSLATickets($daysAhead)
    ├── Query: Next X days
    └── Return: Collection of tickets
```

## Email Template Decision Tree

```
┌─────────────────────────┐
│  Determine Reminder     │
│  Type Based on Date     │
└───────────┬─────────────┘
            │
    ┌───────┴────────┐
    │                │
    ▼                ▼
┌────────┐    ┌──────────┐
│ before │    │ due_today│
│ _due   │    │          │
└────┬───┘    └─────┬────┘
     │              │
     │    ┌─────────┴──────┐
     │    │                │
     ▼    ▼                ▼
  ┌──────────┐      ┌───────────┐
  │ after    │      │ overdue   │
  │ _due     │      │           │
  └────┬─────┘      └─────┬─────┘
       │                  │
       └──────┬───────────┘
              ▼
    ┌──────────────────┐
    │ Load Email       │
    │ Template:        │
    │ - sla-reminder   │
    │ - sla-overdue    │
    └──────────────────┘
              │
              ▼
    ┌──────────────────┐
    │ Populate Data:   │
    │ - ticket info    │
    │ - SLA due date   │
    │ - assigned user  │
    │ - reminder msg   │
    └──────────────────┘
              │
              ▼
    ┌──────────────────┐
    │ Send Email via   │
    │ Mail Driver      │
    │ (SMTP/Log/etc)   │
    └──────────────────┘
```

## Status Codes Reference

```
┌─────────┬──────────────────┬─────────────────────────┐
│ Status  │ Name            │ Notification            │
├─────────┼──────────────────┼─────────────────────────┤
│ 1       │ Aduan Baru      │ ✅ Send notifications   │
│ 2       │ Dalam Tindakan  │ ✅ Send notifications   │
│ 3       │ Dalam Proses    │ ✅ Send notifications   │
│ 4       │ Pending         │ ✅ Send notifications   │
│ 5       │ Selesai         │ ❌ Skip notifications   │
│ 6       │ Tutup           │ ❌ Skip notifications   │
└─────────┴──────────────────┴─────────────────────────┘
```

## Cron Schedule Recommendation

```
# /etc/cron.d/laravel-scheduler
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1

# Inside app/Console/Kernel.php schedule() method:
08:00 - Send Due Today reminders
09:00 - Send SLA-1 (Due Tomorrow) reminders
10:00 - Send After Due (Due Date+1) reminders
11:00 - Send Overdue (>1 day) notifications
```

---

**Note**: Diagram ini menunjukkan complete flow dari ticket creation hingga resolution, dengan focus kepada SLA notification system dan eskalasi Level 1 → Level 2.
