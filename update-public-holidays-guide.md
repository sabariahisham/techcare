# 📅 Panduan Update Public Holidays Annually

## 🎯 Bila Perlu Update?

Update public holidays **setiap tahun sebelum tahun baru** (best practice: bulan November/Disember).

## 🔧 Cara Update (5 Minit Je!)

### Step 1: Dapatkan Official Gazette
Sumber rasmi public holidays Malaysia:
- **Website**: https://www.federalgazette.agc.gov.my/
- **Portal**: https://www.pmo.gov.my/
- **Atau**: Google "Malaysia public holidays [tahun]"

### Step 2: Edit File Helpers.php
Location: `app/Helpers/Helpers.php`

Cari function `getFloatingHolidays()` dan tambah tahun baru:

```php
private static function getFloatingHolidays()
{
    return [
        // ... existing years ...

        // 2028 Floating Holidays (TAMBAH INI)
        '2028-01-XX', // Thaipusam (dapatkan dari gazette)
        '2028-01-XX', // Chinese New Year
        '2028-01-XX', // Chinese New Year (Day 2)
        '2028-XX-XX', // Hari Raya Aidilfitri (dapatkan dari gazette)
        '2028-XX-XX', // Hari Raya Aidilfitri (Day 2)
        '2028-XX-XX', // Wesak Day
        '2028-XX-XX', // Agong's Birthday
        '2028-XX-XX', // Hari Raya Aidiladha
        '2028-XX-XX', // Awal Muharram
        '2028-XX-XX', // Maulidur Rasul
        '2028-XX-XX', // Deepavali
    ];
}
```

### Step 3: Fixed Holidays (Auto-Generated)
Holidays ni auto-generate, tak perlu update:
- ✅ New Year (1 Jan)
- ✅ Federal Territory Day (1 Feb)
- ✅ Labour Day (1 May)
- ✅ National Day (31 Aug)
- ✅ Malaysia Day (16 Sep)
- ✅ Christmas (25 Dec)

### Step 4: Test After Update
```bash
php test-working-days.php
```

Check output untuk tahun baru yang ditambah.

## 📊 Holiday Categories

### 1. Fixed Holidays (Auto-Generated) ✅
Sistem auto tambah setiap tahun, tak perlu maintain:
- New Year's Day - 1 January
- Federal Territory Day - 1 February
- Labour Day - 1 May
- National Day - 31 August
- Malaysia Day - 16 September
- Christmas Day - 25 December

### 2. Floating Holidays (Manual Update Required) ⚠️
Perlu update setiap tahun dari official gazette:

#### Islamic Calendar Holidays:
- Hari Raya Aidilfitri (2 days)
- Hari Raya Aidiladha
- Awal Muharram (Islamic New Year)
- Maulidur Rasul (Prophet's Birthday)

#### Hindu/Buddhist Holidays:
- Thaipusam
- Wesak Day
- Deepavali

#### Chinese Calendar:
- Chinese New Year (2 days)

#### Government Declared:
- Agong's Birthday (Yang di-Pertuan Agong)

## 🔔 Warning System

Sistem ada auto-warning dalam logs jika:
1. ⚠️ Current year kurang dari 12 holidays
2. ⚠️ Next year kurang dari 12 holidays

Check logs: `storage/logs/laravel.log`

## 📝 Example Update untuk 2028

### Official Gazette Example (Contoh):
```
FEDERAL GOVERNMENT GAZETTE
PUBLIC HOLIDAYS 2028 (PUTRAJAYA)

Fixed:
- 1 Jan 2028 (Saturday) - New Year
- 1 Feb 2028 (Tuesday) - Federal Territory Day
- 1 May 2028 (Monday) - Labour Day
- 31 Aug 2028 (Thursday) - National Day
- 16 Sep 2028 (Saturday) - Malaysia Day
- 25 Dec 2028 (Monday) - Christmas

Floating:
- 14 Jan 2028 (Friday) - Thaipusam
- 10 Feb 2028 (Thursday) - Chinese New Year
- 11 Feb 2028 (Friday) - Chinese New Year Day 2
- 10 Mar 2028 (Friday) - Hari Raya Aidilfitri
- 11 Mar 2028 (Saturday) - Hari Raya Aidilfitri Day 2
- 5 May 2028 (Friday) - Wesak Day
- 17 May 2028 (Wednesday) - Hari Raya Aidiladha
- 4 Jun 2028 (Sunday) - Agong's Birthday (Replacement: Monday 5 Jun)
- 7 Jun 2028 (Wednesday) - Awal Muharram
- 15 Aug 2028 (Tuesday) - Maulidur Rasul
- 19 Oct 2028 (Thursday) - Deepavali
```

### Update Code:
```php
// ADD 2028 HOLIDAYS
'2028-01-14', // Thaipusam
'2028-02-10', // Chinese New Year
'2028-02-11', // Chinese New Year (Day 2)
'2028-03-10', // Hari Raya Aidilfitri
'2028-03-11', // Hari Raya Aidilfitri (Day 2)
'2028-05-05', // Wesak Day
'2028-05-17', // Hari Raya Aidiladha
'2028-06-05', // Agong's Birthday (Replacement)
'2028-06-07', // Awal Muharram
'2028-08-15', // Maulidur Rasul
'2028-10-19', // Deepavali
```

## ✅ Checklist Update

- [ ] Dapatkan official gazette untuk tahun baru
- [ ] Edit `app/Helpers/Helpers.php`
- [ ] Tambah floating holidays untuk tahun baru
- [ ] Mark tentative dates dengan comment `(tentative - verify)`
- [ ] Run test: `php test-working-days.php`
- [ ] Check logs untuk warning: `storage/logs/laravel.log`
- [ ] Test SLA notifications: `php test-sla-simple.php`
- [ ] Commit changes ke git
- [ ] Deploy ke production

## 🚨 Important Notes

1. **Replacement Days**: Jika holiday jatuh pada weekend, kerajaan akan announce replacement day. Pastikan tambah replacement date!

2. **Tentative Dates**: Islamic/Hindu calendar dates untuk 2 tahun akan datang biasanya tentative. Mark dengan comment untuk verify later.

3. **Federal vs State**: Code ni untuk **Federal Territory (Putrajaya)** holidays only. Setiap negeri ada extra cuti sendiri.

4. **Advance Notice**: Government biasanya announce next year holidays pada bulan Oktober/November. Update awal untuk avoid issues.

## 📞 Support Contacts

Jika ada masalah:
1. Check Federal Gazette: https://www.federalgazette.agc.gov.my/
2. Email PMO: publicholidays@pmo.gov.my (example)
3. Check logs: `storage/logs/laravel.log`
4. Run test: `php test-working-days.php`

## 🎓 Tips

1. **Set Reminder**: Set calendar reminder setiap November untuk update next year holidays
2. **Document Source**: Tambah comment dengan URL gazette untuk reference
3. **Test Early**: Update dan test pada November, jangan tunggu last minute
4. **Backup**: Keep old dates untuk reference and rollback jika perlu

---

**Last Updated**: January 2026  
**Next Update Due**: November 2026 (for 2027 holidays verification)  
**Maintained by**: eHelp Development Team
