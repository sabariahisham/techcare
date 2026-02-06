# 📊 SLA Notification System - Visual Guide

**Last Updated:** February 6, 2026

---

## 🎯 System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                   SLA NOTIFICATION SYSTEM                        │
│                      (Unified v2.0)                              │
└─────────────────────────────────────────────────────────────────┘
                                │
                                │
                    ┌───────────▼───────────┐
                    │  Laravel Scheduler    │
                    │   Every Day 7:30 AM   │
                    └───────────┬───────────┘
                                │
                                │
                    ┌───────────▼───────────┐
                    │  SendSLANotifications │
                    │       Command         │
                    └───────────┬───────────┘
                                │
                ┌───────────────┼───────────────┐
                │               │               │
                ▼               ▼               ▼
        ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
        │  Reminder    │ │   Overdue    │ │  Due Date    │
        │  (1 day      │ │  (1 day      │ │  (3 days     │
        │   before)    │ │   after)     │ │   after)     │
        └──────┬───────┘ └──────┬───────┘ └──────┬───────┘
               │                │                │
               │                │                │
        ┌──────▼────────────────▼────────────────▼───────┐
        │       TicketSLAReminderService                  │
        │  - sendReminderNotifications()                  │
        │  - sendOverdueNotifications()                   │
        │  - sendDueDateNotifications()                   │
        └──────┬────────────────┬────────────────┬────────┘
               │                │                │
        ┌──────▼───────┐ ┌──────▼───────┐ ┌─────▼────────┐
        │   Reminder   │ │SLAOverdue    │ │  DueDate     │
        │ Notification │ │Notification  │ │ Notification │
        └──────┬───────┘ └──────┬───────┘ └──────┬───────┘
               │                │                │
               └────────────────┼────────────────┘
                                │
                    ┌───────────▼───────────┐
                    │    Email Templates    │
                    │  - sla-reminder       │
                    │  - sla-overdue        │
                    │  - due-date           │
                    └───────────┬───────────┘
                                │
                    ┌───────────▼───────────┐
                    │    SMTP Server        │
                    │   Send Email          │
                    │   (NO QUEUE)          │
                    └───────────┬───────────┘
                                │
                    ┌───────────▼───────────┐
                    │   Pegawai Tugasan     │
                    │   (Recipient)         │
                    └───────────────────────┘
```

---

## 🔄 Notification Flow by Type

### 1️⃣ Reminder Flow (1 Day Before)

```
┌─────────────────────────────────────────────────────────────────┐
│ STEP 1: Calculate Reminder Date                                 │
│ reminderDate = addWorkingDays(today, 1)                         │
│ Example: Today = Monday → reminderDate = Tuesday                │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 2: Query Database                                          │
│ SELECT tickets WHERE:                                           │
│   - sla_duedate = reminderDate                                  │
│   - tick_status NOT IN (5, 6) -- 5=Batal, 6=Selesai            │
│   - Latest chronology only                                      │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 3: Process Each Ticket                                     │
│ FOR each ticket:                                                │
│   - Get frm_user_id (Pegawai Tugasan)                           │
│   - Validate email exists                                       │
│   - Create ReminderNotification                                 │
│   - Send email directly (NO QUEUE)                              │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 4: Email Content                                           │
│ Subject: ⏳ Peringatan SLA - Hampir Tamat Tempoh                │
│ Message: Tiket akan tamat tempoh ESOK                           │
│ Template: sla-reminder.blade.php                                │
└─────────────────────────────────────────────────────────────────┘
```

### 2️⃣ Overdue Flow (1 Day After)

```
┌─────────────────────────────────────────────────────────────────┐
│ STEP 1: Calculate Overdue Date                                  │
│ overdueDate = subtractWorkingDays(today, 1)                     │
│ Example: Today = Wednesday → overdueDate = Tuesday              │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 2: Query Database                                          │
│ SELECT tickets WHERE:                                           │
│   - sla_duedate = overdueDate                                   │
│   - tick_status NOT IN (5, 6) -- 5=Batal, 6=Selesai            │
│   - Latest chronology only                                      │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 3: Process Each Ticket                                     │
│ FOR each ticket:                                                │
│   - Get frm_user_id                                             │
│   - Calculate days overdue                                      │
│   - Create SLAOverdueNotification                               │
│   - Send email directly                                         │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 4: Email Content                                           │
│ Subject: 🚨 Amaran: Tiket SLA OVERDUE                           │
│ Message: Tiket telah OVERDUE 1 hari                             │
│ Template: sla-overdue.blade.php                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 3️⃣ Due Date Flow (3 Days After Created, Status=1)

```
┌─────────────────────────────────────────────────────────────────┐
│ STEP 1: Calculate Due Date Check                                │
│ dueDateCheck = subtractWorkingDays(today, 3)                    │
│ Example: Today = Thursday → dueDateCheck = Monday               │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 2: Query Database                                          │
│ SELECT tickets WHERE:                                           │
│   - created_at = dueDateCheck                                   │
│   - tick_status = 1 (Baru/Menunggu)                             │
│   - Latest chronology only                                      │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 3: Process Each Ticket                                     │
│ FOR each ticket:                                                │
│   - Get frm_user_id (Level 1)                                   │
│   - Create DueDateNotification                                  │
│   - Send email directly                                         │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│ STEP 4: Email Content                                           │
│ Subject: 📅 Peringatan: Tiket Menunggu Tindakan                │
│ Message: Tiket sudah 3 hari menunggu tindakan                   │
│ Template: due-date.blade.php                                    │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📅 Timeline Example

### Scenario: Ticket with SLA Due Date on Friday

```
Week 1:
┌────────────────────────────────────────────────────────────────┐
│ Monday    Tuesday   Wednesday  Thursday  Friday    Weekend     │
│                                           ▲                     │
│                                     SLA Due Date                │
└────────────────────────────────────────────────────────────────┘

Week Before:
┌────────────────────────────────────────────────────────────────┐
│ Monday    Tuesday   Wednesday  Thursday   Friday    Weekend    │
│                                  ▲                              │
│                           📧 REMINDER SENT                      │
│                         (1 day before due)                      │
└────────────────────────────────────────────────────────────────┘

Week After:
┌────────────────────────────────────────────────────────────────┐
│ Monday    Tuesday   Wednesday  Thursday   Friday    Weekend    │
│   ▲                                                             │
│ 🚨 OVERDUE SENT                                                │
│ (Skip weekend: Friday → Monday)                                │
└────────────────────────────────────────────────────────────────┘
```

### Scenario: New Ticket Created (Status=1)

```
┌────────────────────────────────────────────────────────────────┐
│ Day 0     Day 1     Day 2     Day 3     Day 4    Day 5        │
│   ▲                           ▲                                │
│ CREATED                    📅 DUE DATE SENT                    │
│ (Status=1)               (3 working days)                      │
└────────────────────────────────────────────────────────────────┘
```

---

## 🗄️ Database Relationships

```
┌─────────────────────────────────────────────────────────────────┐
│                          TICKETS                                │
│  - id                                                           │
│  - ticket_nolog                                                 │
│  - ticket_title                                                 │
│  - created_at  ◄────┐ (Used for Due Date notification)         │
│  - ...              │                                           │
└────────┬────────────┴────────────────────────────────────────┘
         │ 1
         │
         │ N
┌────────▼──────────────────────────────────────────────────────┐
│               TICKETS_CHRONOLOGIES                             │
│  - id                                                          │
│  - ticket_id                                                   │
│  - frm_user_id  ◄──────┐ (Pegawai Tugasan - Recipient)        │
│  - tick_status         │                                       │
│  - sla_duedate  ◄──────┼─┐ (Used for Reminder & Overdue)      │
│  - created_at  ◄───────┼─┼─┐ (For latest chronology)          │
│  - ...                 │ │ │                                   │
└───────────────┬────────┘ │ │                                   │
                │ N        │ │                                   │
                │          │ │                                   │
                │ 1        │ │                                   │
┌───────────────▼──────────┼─┼─────────────────────────────────┐
│              USERS       │ │                                   │
│  - id                    │ │                                   │
│  - nama                  │ │                                   │
│  - email  ◄──────────────┘ │                                   │
│  - ...                     │                                   │
└────────────────────────────┼───────────────────────────────────┘
                             │
                             │ Date Check
┌────────────────────────────▼───────────────────────────────────┐
│               PUBLIC_HOLIDAYS                                  │
│  - ph_date  (Used by WorkingDaysHelper)                        │
│  - ph_name                                                     │
└────────────────────────────────────────────────────────────────┘
```

---

## 🔍 Query Logic Diagram

### Get Latest Chronology Per Ticket

```sql
                    ┌──────────────────────────┐
                    │    All Chronologies      │
                    │  (Multiple per ticket)   │
                    └────────────┬─────────────┘
                                 │
                    ┌────────────▼─────────────┐
                    │  GROUP BY ticket_id      │
                    │  ORDER BY created_at DESC│
                    └────────────┬─────────────┘
                                 │
                    ┌────────────▼─────────────┐
                    │     LIMIT 1 per group    │
                    │  (Latest chronology)     │
                    └────────────┬─────────────┘
                                 │
                    ┌────────────▼─────────────┐
                    │  Final Result:           │
                    │  - Latest status         │
                    │  - Latest sla_duedate    │
                    │  - Latest frm_user_id    │
                    └──────────────────────────┘
```

### Filter Logic

```
                    ┌──────────────────────────┐
                    │   All Tickets with       │
                    │   Latest Chronology      │
                    └────────────┬─────────────┘
                                 │
                    ┌────────────▼─────────────┐
                    │ Filter: deleted_at IS    │
                    │         NULL             │
                    └────────────┬─────────────┘
                                 │
                    ┌────────────▼─────────────┐
                    │ Filter: tick_status      │
                    │         NOT IN (5, 6)    │
                    │  (5=Batal, 6=Selesai)    │
                    └────────────┬─────────────┘
                                 │
         ┌──────────────────────┼──────────────────────┐
         │                      │                      │
         ▼                      ▼                      ▼
┌────────────────┐    ┌────────────────┐    ┌────────────────┐
│   REMINDER     │    │    OVERDUE     │    │   DUE DATE     │
│                │    │                │    │                │
│ sla_duedate =  │    │ sla_duedate =  │    │ created_at =   │
│ tomorrow       │    │ yesterday      │    │ 3 days ago     │
│                │    │                │    │ AND status = 1 │
└────────────────┘    └────────────────┘    └────────────────┘
```

---

## ⚙️ Working Days Calculation

```
┌─────────────────────────────────────────────────────────────────┐
│                 isWorkingDay(date)                               │
└────────────────────────┬────────────────────────────────────────┘
                         │
          ┌──────────────┴──────────────┐
          │                             │
          ▼                             ▼
    ┌───────────┐                 ┌──────────────┐
    │ Weekend?  │ YES → FALSE     │ Public       │ YES → FALSE
    │ (Sat/Sun) │                 │ Holiday?     │
    └─────┬─────┘                 └───────┬──────┘
          │ NO                            │ NO
          └───────────┬───────────────────┘
                      │
                      ▼
              ┌──────────────┐
              │    TRUE      │
              │ (Working Day)│
              └──────────────┘

addWorkingDays(date, days):
┌─────────────────────────────────────────────────────────────────┐
│ Start: date                                                      │
│ Loop: days times                                                │
│   1. Add 1 day                                                  │
│   2. While NOT isWorkingDay(date):                              │
│      - Add 1 more day                                           │
│   3. Count++                                                    │
│ Return: final date                                              │
└─────────────────────────────────────────────────────────────────┘

Example:
Friday + 1 working day = Monday (skip weekend)
Thursday + 1 working day = Friday (if Friday is holiday, then Monday)
```

---

## 🎯 Complete Flow - All 3 Notifications

```
┌─────────────────────────────────────────────────────────────────┐
│                     07:30 AM DAILY                               │
│              Laravel Scheduler Triggered                         │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│              sla:send-notifications                              │
│                   (Console Command)                              │
└─────┬───────────────────┬───────────────────┬────────────────────┘
      │                   │                   │
      │ CALL              │ CALL              │ CALL
      │                   │                   │
      ▼                   ▼                   ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│   Reminder   │    │   Overdue    │    │  Due Date    │
│    Method    │    │    Method    │    │    Method    │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                   │
       ▼                   ▼                   ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│ Calculate    │    │ Calculate    │    │ Calculate    │
│ tomorrow     │    │ yesterday    │    │ 3 days ago   │
│ (working)    │    │ (working)    │    │ (working)    │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                   │
       ▼                   ▼                   ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│ Query DB     │    │ Query DB     │    │ Query DB     │
│ for tickets  │    │ for tickets  │    │ for tickets  │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                   │
       ▼                   ▼                   ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│ Send Emails  │    │ Send Emails  │    │ Send Emails  │
│ (Direct)     │    │ (Direct)     │    │ (Direct)     │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                   │
       └───────────────────┼───────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    LOG RESULTS                                   │
│  - Total found                                                  │
│  - Sent successfully                                            │
│  - Failed                                                       │
│  - Skipped (no email)                                           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📊 Statistics & Monitoring

```
┌─────────────────────────────────────────────────────────────────┐
│                    NOTIFICATION STATS                            │
│                                                                  │
│  Type: Reminder                                                 │
│  ├─ Total Found:    15                                          │
│  ├─ Sent:           14                                          │
│  ├─ Failed:          0                                          │
│  └─ Skipped:         1  (No email)                              │
│                                                                  │
│  Type: Overdue                                                  │
│  ├─ Total Found:     8                                          │
│  ├─ Sent:            7                                          │
│  ├─ Failed:          0                                          │
│  └─ Skipped:         1  (No email)                              │
│                                                                  │
│  Type: Due Date                                                 │
│  ├─ Total Found:     5                                          │
│  ├─ Sent:            5                                          │
│  ├─ Failed:          0                                          │
│  └─ Skipped:         0                                          │
│                                                                  │
│  Total Emails Sent: 26                                          │
│  Execution Time:    3.5 seconds                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔗 Quick Links

- **[Main Guide](developer-guide/notification/03_SLA_NOTIFICATION_GUIDE.md)** - Complete documentation
- **[Quick Reference](developer-guide/notification/SLA_NOTIFICATION_QUICK_REF.md)** - Quick reference
- **[Implementation](developer-guide/notification/SLA_NOTIFICATION_IMPLEMENTATION.md)** - Technical details
- **[Fixed Summary](SLA_NOTIFICATION_FIXED.md)** - What was fixed

---

**Status:** ✅ Production Ready  
**Version:** 2.0 (Unified)  
**Last Updated:** February 6, 2026
