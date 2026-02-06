# 📧 Email & Notification System Documentation

Dokumentasi lengkap untuk sistem email dan notification dalam eHelp.

## 📋 Table of Contents

### Email System Fundamentals
8. [**Laravel Markdown Email Guide**](08_LARAVEL_MARKDOWN_EMAIL_GUIDE.md)
   - Email template creation dengan Markdown
   - Laravel mail components
   - Best practices untuk email design
   - Styling dan formatting

10. [**Mail Notification Path CAS**](10_MAIL_NOTIFICATION_PATH_CAS.md)
    - CAS authentication email flow
    - Path configuration
    - Integration dengan CAS system

### Reminder Systems
11. [**Due Date Reminder System**](11_DUE_DATE_REMINDER_SYSTEM.md)
    - Basic due date reminders
    - 3-day working day calculation
    - Malaysian public holidays handling
    - Level 1 officer notifications

### SLA Notification System ⭐
12. [**SLA Notification Guide**](03_SLA_NOTIFICATION_GUIDE.md) 🔥 **UPDATED Feb 6, 2026**
    - **Complete FIXED & UNIFIED implementation**
    - **NO DUPLICATES** - Single unified service & command
    - **NO QUEUE** - Direct email sending
    - **7:30 AM Schedule** - Consistent timing
    - 3 types of notifications:
      - **Reminder:** 1 day before (sla_duedate - 1 working day)
      - **Overdue:** 1 day after (sla_duedate + 1 working day)
      - **Due Date:** 3 days after created (status=1 only)
    - Working days calculation (skip weekends & holidays)
    - SQL queries for debugging
    - Complete troubleshooting guide
    - Testing commands

13. [**SLA Quick Reference**](SLA_NOTIFICATION_QUICK_REF.md) 🆕 **NEW**
    - **One-page quick reference**
    - Summary table of all notifications
    - Quick testing commands
    - Common debugging queries
    - Checklist for setup
    - Expected behavior scenarios

14. [**SLA Complete Implementation**](SLA_NOTIFICATION_IMPLEMENTATION.md) 🆕 **NEW**
    - **Technical implementation details**
    - Complete file structure
    - Code examples for all components
    - Database structure & queries
    - Scheduling setup
    - Monitoring & logging guide
    - Production checklist

15. [**SLA Visual Guide**](SLA_NOTIFICATION_VISUAL_GUIDE.md) 🆕 **NEW**
    - **Visual diagrams & flowcharts**
    - System architecture overview
    - Notification flow by type
    - Timeline examples
    - Database relationship diagrams
    - Query logic visualization
    - Working days calculation flow

13. [**SLA Flow Diagram**](04_SLA_FLOW_DIAGRAM.md)
    - Visual flow diagrams
    - Complete ticket lifecycle
    - Database relationships
    - Notification logic flow
    - Query examples
    - Service method flow

14. [**SLA Quick Start**](05_SLA_QUICK_START.md)
    - Quick reference guide
    - Testing commands (PHP & PowerShell)
    - API endpoints summary
    - Configuration checklist
    - Troubleshooting tips

15. [**SLA Working Days Implementation**](sla-working-days-implementation.md) ⭐ **NEW**
    - **Working days calculation (skip weekends & holidays)**
    - Helper functions for date calculations
    - Reminder logic with working days
    - Real-world scenarios and examples
    - Public holidays configuration
    - Testing working days calculations

16. [**Auto-Calculation Public Holidays**](AUTO_HOLIDAYS_IMPLEMENTATION.md) 🆕 **LATEST**
    - **Semi-automatic holiday generation**
    - Fixed holidays auto-generated (6 cuti - 36% less maintenance)
    - Floating holidays manual configuration
    - Warning system for incomplete data
    - Annual update guide (5 minit je!)
    - Test auto-calculation
    - Testing working days calculations
    - API endpoints summary
    - Configuration checklist
    - Troubleshooting tips

## 🎯 Quick Navigation

### For Developers
- **Starting with emails?** → [08_LARAVEL_MARKDOWN_EMAIL_GUIDE.md](08_LARAVEL_MARKDOWN_EMAIL_GUIDE.md)
- **SLA Notifications?** → [12_SLA_NOTIFICATION_GUIDE.md](12_SLA_NOTIFICATION_GUIDE.md)
- **Need flow diagrams?** → [13_SLA_FLOW_DIAGRAM.md](13_SLA_FLOW_DIAGRAM.md)
- **Quick testing?** → [14_SLA_QUICK_START.md](14_SLA_QUICK_START.md)

### For Testing
- **Test SLA notifications** → [14_SLA_QUICK_START.md](14_SLA_QUICK_START.md)
- **Test due date reminders** → [11_DUE_DATE_REMINDER_SYSTEM.md](11_DUE_DATE_REMINDER_SYSTEM.md)
- **Email templates** → [08_LARAVEL_MARKDOWN_EMAIL_GUIDE.md](08_LARAVEL_MARKDOWN_EMAIL_GUIDE.md)

### For System Architecture
- **Complete flow** → [13_SLA_FLOW_DIAGRAM.md](13_SLA_FLOW_DIAGRAM.md)
- **Database design** → [13_SLA_FLOW_DIAGRAM.md](13_SLA_FLOW_DIAGRAM.md#database-tables-relationship)
- **API design** → [12_SLA_NOTIFICATION_GUIDE.md](12_SLA_NOTIFICATION_GUIDE.md#api-endpoints)

## 🆕 Latest Updates

### February 6, 2026 ⭐ **SIMPLIFIED SYSTEM**
- ✅ **SLA Notification System SIMPLIFIED**
  - **REMOVED** "Due Date" notification (status=1 specific)
  - **REMOVED** all files related to `tick_duedate` (Aduan Baru system)
  - **DELETED FILES**:
    - `TicketDueDateController.php`
    - `SendTicketDueDateReminders.php`
    - `due-date-alert.blade.php`
  - **KEPT ONLY 2 notifications**:
    1. **Reminder:** 1 working day BEFORE `sla_duedate` (ALL statuses except 5=Batal, 6=Selesai)
    2. **Overdue:** 1 working day AFTER `sla_duedate` (ALL statuses except 5=Batal, 6=Selesai)
  - **Single unified command**: `php artisan sla:send-notifications`
  - **Single schedule**: Daily at 7:30 AM
  - **NO QUEUE** - Direct email sending
  - All notifications refer to `tickets_chronologies.sla_duedate` ONLY

### January 27, 2026
- ✅ **Working Days Implementation** - Updated reminder calculations
  - Skip weekends (Saturday & Sunday)
  - Skip Malaysian Federal Public Holidays
  - Helper functions: addWorkingDays(), subtractWorkingDays(), isWorkingDay()
  - Test script: test-working-days.php
- ✅ **SLA Notification System** - Complete implementation & tested
  - 4 types of reminders (now using working days)
  - 11 API endpoints
  - Email templates
  - Test scripts (PHP & PowerShell)
- ✅ **Documentation** - Reorganized into notification folder
- ✅ **Test Results** - 100% success rate (2/2 emails sent)

## 🔍 Features Overview

### Email Types

| Type | Timing | Subject | Priority |
|------|--------|---------|----------|
| **SLA-1** | 1 working day before due | ⚠️ Hampir Tamat Tempoh | Medium |
| **Due Today** | On due date (working day only) | 🔔 Tamat Tempoh HARI INI | High |
| **Due Date+1** | 1 working day after due | ⚠️ Tempoh Tamat Semalam | High |
| **Overdue** | >1 working day after due | ⛔ OVERDUE - Tindakan Segera | Critical |

**Note**: All reminders use **working days** calculation (skip weekends & holidays)

### Notification Flow

```
Ticket Created → Assigned Level 1
                       ↓
            SLA Reminders (4 types)
                       ↓
            Level 1 Action?
              ↙️        ↘️
           YES         NO
            ↓           ↓
         Resolved   Escalate Level 2
                       ↓
            SLA Reminders (4 types)
                       ↓
                   Resolved
```

## 📊 System Components

```
Notification System
├── Controllers
│   ├── TicketSLAController (Reporting)
│   └── TicketOverdueController (Notifications)
│
├── Services
│   ├── TicketReminderService
│   └── TicketOverdueService
│
├── Notifications
│   ├── ReminderNotification
│   └── OverdueNotification
│
├── Email Templates
│   ├── sla-reminder.blade.php
│   └── sla-overdue.blade.php
│
└── API Routes
    ├── POST /api/sla/notifications/*
    └── GET /api/sla/tickets/*
```

## 🌐 API Endpoints

### Trigger Notifications
```bash
POST /api/sla/notifications/before-due   # SLA-1
POST /api/sla/notifications/due-today    # Due today
POST /api/sla/notifications/after-due    # Due date+1
POST /api/sla/notifications/overdue      # Overdue
POST /api/sla/notifications/all          # All at once
```

### Reporting
```bash
GET /api/sla/tickets/due-tomorrow   # Tickets due tomorrow
GET /api/sla/tickets/due-today      # Tickets due today
GET /api/sla/tickets/due-yesterday  # Tickets due yesterday
GET /api/sla/tickets/overdue        # Overdue tickets
GET /api/sla/upcoming?days=7        # Upcoming tickets
GET /api/sla/statistics             # SLA statistics
```

## 🧪 Quick Test

### Option 1: PHP Script
```bash
cd c:\Users\sabariah.hisham\Herd\ehelp-fork_29122025
php test-sla-simple.php
```

### Option 2: PowerShell Script
```powershell
.\test-sla-notifications.ps1
```

### Option 3: Manual API Call
```bash
POST http://your-domain/api/sla/notifications/all
Authorization: Bearer YOUR_TOKEN
```

See [14_SLA_QUICK_START.md](14_SLA_QUICK_START.md) for detailed testing instructions.

## ⏰ Automation Setup

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // 8:00 AM - Due today reminders
    $schedule->call(function () {
        app(TicketReminderService::class)->sendDueTodayReminders();
    })->dailyAt('08:00');

    // 9:00 AM - SLA-1 reminders
    $schedule->call(function () {
        app(TicketReminderService::class)->sendBeforeDueReminders();
    })->dailyAt('09:00');

    // 10:00 AM - After due reminders
    $schedule->call(function () {
        app(TicketReminderService::class)->sendAfterDueReminders();
    })->dailyAt('10:00');

    // 11:00 AM - Overdue notifications
    $schedule->call(function () {
        app(TicketReminderService::class)->sendOverdueNotifications();
    })->dailyAt('11:00');
}
```

## 🔧 Configuration

### Email Settings (.env)
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Files Location
- **Email Templates**: `resources/views/emails/ticket/`
- **Services**: `app/Services/`
- **Notifications**: `app/Notifications/Ticket/`
- **Controllers**: `app/Http/Controllers/`
- **Routes**: `routes/api.php`
- **Test Scripts**: Root directory

## 📝 Logging

All notifications are logged in `storage/logs/laravel.log`:

```bash
# View latest logs
tail -f storage/logs/laravel-2026-01-27.log

# Search for SLA notifications
grep "SLA" storage/logs/laravel-2026-01-27.log
```

## ✅ Test Results (27 Jan 2026)

```
Database: ✅ OK
Total Tickets: 15
Active Tickets: 12

Notifications Test:
├── Overdue Found: 2 tickets
├── Emails Sent: 2 ✅
├── Failed: 0
├── Skipped: 0
└── Success Rate: 100%

Recipients:
✅ hafizzuddin@mof.gov.my
✅ faizal.pauzi@treasury.gov.my
```

## 🐛 Troubleshooting

### Email not sent?
1. Check `.env` email configuration
2. Verify recipient has valid email address
3. Check `escalate_to` in chronology
4. Review Laravel logs

### No tickets found?
1. Check `sla_duedate` in chronologies
2. Verify ticket status (not 5 or 6)
3. Check date calculations
4. Review query logic

### Authentication error?
1. Get valid Bearer token
2. Check middleware
3. Verify user permissions

See [12_SLA_NOTIFICATION_GUIDE.md](12_SLA_NOTIFICATION_GUIDE.md#troubleshooting) for more details.

## 📚 Additional Resources

- Laravel Notifications: https://laravel.com/docs/notifications
- Laravel Mail: https://laravel.com/docs/mail
- Laravel Task Scheduling: https://laravel.com/docs/scheduling
- Carbon Dates: https://carbon.nesbot.com/docs/

## 🔗 Related Documentation

- **Ticket Management**: [../ticket/README.md](../ticket/README.md)
- **Implementation Summary**: [../../IMPLEMENTATION_SUMMARY.md](../../IMPLEMENTATION_SUMMARY.md)

---

**Last Updated**: January 27, 2026  
**Version**: 1.0.0  
**Status**: ✅ Production Ready
