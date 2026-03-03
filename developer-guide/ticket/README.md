# 📚 Ticket System Documentation Index

Dokumentasi lengkap untuk sistem tiket eHelp.

## 📋 Table of Contents

### Ticket Management Features
1. [**Ticket List Datatable**](01_TICKET_LIST_DATATABLE.md)
   - DataTable implementation untuk senarai tiket
   - Filtering, sorting, dan search functionality

2. [**Ticket Form - Log Baru**](02_TICKET_FORM_LOG_BARU.md)
   - Form untuk create tiket baru
   - Validation dan submission flow

3. [**Ticket Attachment**](03_TICKET_ATTACHMENT.md)
   - File upload dan attachment management
   - Document handling untuk tiket

4. [**View Kronologi Tiket**](04_VIEW_KRONOLOGI_TIKET.md)
   - Timeline dan history tiket
   - Status changes tracking

5. [**View Maklumat Aduan**](05_VIEW_MAKLUMAT_ADUAN.md)
   - Display ticket details
   - Complaint information view

6. [**View Tindakan Pegawai**](06_VIEW_TINDAKAN_PEGAWAI.md)
   - Officer action interface
   - Assignment dan escalation

7. [**View Maklumat Pengadu**](07_VIEW_MAKLUMAT_PENGADU.md)
   - Complainant information display
   - User details view

### System Features
8. [**UUID Implementation Guide**](08_UUID_IMPLEMENTATION_GUIDE.md)
   - UUID usage dalam sistem
   - Security dan unique identifiers

9. [**Ticket Form Validation**](09_TICKET_FORM_VALIDATION.md) ⭐ NEW
   - Client & server-side validation
   - Auto-scroll to validation errors
   - Error display & handling

## 📧 Email & Notifications

For email and notification documentation, see:
**[../notification/README.md](../notification/README.md)**

Including:
- Laravel Markdown Email Guide
- Mail Notification Path CAS
- Due Date Reminder System
- SLA Notification System (Complete Guide)
- SLA Flow Diagrams
- SLA Quick Start Guide

## 🎯 Quick Navigation

### For Developers
- **Starting new?** → Start with [01_TICKET_LIST_DATATABLE.md](01_TICKET_LIST_DATATABLE.md)
- **Working on forms?** → Check [09_TICKET_FORM_VALIDATION.md](09_TICKET_FORM_VALIDATION.md)
- **Working on notifications?** → Check [../notification/README.md](../notification/README.md)
- **Chronology tracking** → [04_VIEW_KRONOLOGI_TIKET.md](04_VIEW_KRONOLOGI_TIKET.md)
- **Officer actions** → [06_VIEW_TINDAKAN_PEGAWAI.md](06_VIEW_TINDAKAN_PEGAWAI.md)

### For System Architecture
- **Ticket Management** → This folder
- **Notifications & Email** → [../notification/](../notification/)
- **API Documentation** → See individual feature docs

## 🆕 Latest Updates

### March 3, 2026
- ✅ **Form Validation Enhancement** - Added auto-scroll to validation errors
- ✅ **New Documentation** - [09_TICKET_FORM_VALIDATION.md](09_TICKET_FORM_VALIDATION.md)
- ✅ **Improved Error Display** - Consistent error styling for Add & Edit forms
- ✅ **Select2 Validation** - Enhanced dropdown field validation

### January 27, 2026
- ✅ **Documentation Reorganization** - Separated ticket and notification docs
- ✅ **SLA Notification System** - Moved to [../notification/](../notification/)
- ✅ **Email System** - Consolidated in notification folder

## 🔍 Search by Feature

### Ticket Management
- Create ticket: [02_TICKET_FORM_LOG_BARU.md](02_TICKET_FORM_LOG_BARU.md)
- Form validation: [09_TICKET_FORM_VALIDATION.md](09_TICKET_FORM_VALIDATION.md)
- List tickets: [01_TICKET_LIST_DATATABLE.md](01_TICKET_LIST_DATATABLE.md)
- View ticket: [05_VIEW_MAKLUMAT_ADUAN.md](05_VIEW_MAKLUMAT_ADUAN.md)
- Track history: [04_VIEW_KRONOLOGI_TIKET.md](04_VIEW_KRONOLOGI_TIKET.md)
- Attachments: [03_TICKET_ATTACHMENT.md](03_TICKET_ATTACHMENT.md)

### Officer Features
- Assign tickets: [06_VIEW_TINDAKAN_PEGAWAI.md](06_VIEW_TINDAKAN_PEGAWAI.md)
- View complainant: [07_VIEW_MAKLUMAT_PENGADU.md](07_VIEW_MAKLUMAT_PENGADU.md)

### Email & Notifications
See [../notification/README.md](../notification/README.md) for:
- Email templates
- SLA notifications
- CAS notifications
- Due date reminders

## 📊 System Components

```
Ticket System
├── Frontend (Views)
│   ├── List & Filter
│   ├── Create Form
│   ├── Detail View
│   └── Action Interface
│
├── Backend (Controllers)
│   └── TicketController
│
└── Database
    ├── tickets
    ├── tickets_chronologies
    └── users
```

For notification system components, see [../notification/](../notification/)

## 🔧 Configuration Files

- Routes: `routes/web.php`, `routes/api.php`
- Models: `app/Models/Ticket.php`, `app/Models/TicketChronology.php`
- Controllers: `app/Http/Controllers/Ticket/`

## 📞 Support

For questions or issues:
1. Check relevant documentation file
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check test scripts for examples
4. Review API endpoints documentation

---

**Last Updated**: March 3, 2026  
**Version**: 1.1.0  
**Status**: ✅ Production Ready
