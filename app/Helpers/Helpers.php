<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Helpers
{
  /**
   * Get Malaysian public holidays (hardcoded)
   *
   * @return array Array of holiday dates in Y-m-d format
   */
  public static function getMalaysianPublicHolidays()
  {
    return [
      // 2025 Federal Holidays
      '2025-01-01', // New Year's Day
      '2025-01-25', // Thaipusam
      '2025-01-29', // Chinese New Year
      '2025-01-30', // Chinese New Year (Day 2)
      '2025-02-01', // Federal Territory Day
      '2025-03-31', // Hari Raya Aidilfitri
      '2025-04-01', // Hari Raya Aidilfitri (Day 2)
      '2025-05-01', // Labour Day
      '2025-05-12', // Wesak Day
      '2025-06-02', // Agong's Birthday
      '2025-06-07', // Hari Raya Aidiladha
      '2025-06-28', // Awal Muharram
      '2025-08-31', // National Day
      '2025-09-06', // Maulidur Rasul
      '2025-09-16', // Malaysia Day
      '2025-10-24', // Deepavali
      '2025-12-25', // Christmas Day

      // 2026 Federal Holidays
      '2026-01-01', // New Year's Day
      '2026-01-14', // Thaipusam
      '2026-02-01', // Federal Territory Day
      '2026-02-17', // Chinese New Year
      '2026-02-18', // Chinese New Year (Day 2)
      '2026-03-21', // Hari Raya Aidilfitri
      '2026-03-22', // Hari Raya Aidilfitri (Day 2)
      '2026-05-01', // Labour Day / Wesak Day
      '2026-05-28', // Hari Raya Aidiladha
      '2026-06-01', // Agong's Birthday
      '2026-06-17', // Awal Muharram
      '2026-08-27', // Maulidur Rasul
      '2026-08-31', // National Day
      '2026-09-16', // Malaysia Day
      '2026-11-12', // Deepavali
      '2026-12-25', // Christmas Day

      // 2027 Federal Holidays (add more years as needed)
      '2027-01-01', // New Year's Day
      '2027-02-01', // Federal Territory Day
      '2027-02-06', // Chinese New Year
      '2027-02-07', // Chinese New Year (Day 2)
      '2027-03-11', // Hari Raya Aidilfitri
      '2027-03-12', // Hari Raya Aidilfitri (Day 2)
      '2027-05-01', // Labour Day
      '2027-05-17', // Hari Raya Aidiladha
      '2027-05-20', // Wesak Day
      '2027-06-05', // Agong's Birthday
      '2027-06-07', // Awal Muharram
      '2027-08-16', // Maulidur Rasul
      '2027-08-31', // National Day
      '2027-09-16', // Malaysia Day
      '2027-11-01', // Deepavali
      '2027-12-25', // Christmas Day
    ];
  }

  /**
   * Check if a given date is a public holiday
   *
   * @param Carbon $date
   * @return bool
   */
  public static function isPublicHoliday($date)
  {
    $holidays = self::getMalaysianPublicHolidays();
    return in_array($date->format('Y-m-d'), $holidays);
  }

  /**
   * Calculate working days (excluding weekends and Malaysian public holidays)
   *
   * @param Carbon $startDate Starting date
   * @param int $workingDays Number of working days to add
   * @return Carbon The calculated due date
   */
  public static function addWorkingDays($startDate, $workingDays)
  {
    $currentDate = $startDate->copy();
    $addedDays = 0;

    while ($addedDays < $workingDays) {
      $currentDate->addDay();

      // Skip weekends (Saturday = 6, Sunday = 0)
      if ($currentDate->dayOfWeek === Carbon::SATURDAY || $currentDate->dayOfWeek === Carbon::SUNDAY) {
        continue;
      }

      // Skip public holidays
      if (self::isPublicHoliday($currentDate)) {
        continue;
      }

      $addedDays++;
    }

    return $currentDate;
  }

  /**
   * Calculate working days between two dates (excluding weekends and public holidays)
   *
   * @param Carbon $startDate
   * @param Carbon $endDate
   * @return int Number of working days
   */
  public static function getWorkingDaysBetween($startDate, $endDate)
  {
    $currentDate = $startDate->copy();
    $workingDays = 0;

    while ($currentDate->lessThan($endDate)) {
      $currentDate->addDay();

      // Skip weekends
      if ($currentDate->dayOfWeek === Carbon::SATURDAY || $currentDate->dayOfWeek === Carbon::SUNDAY) {
        continue;
      }

      // Skip public holidays
      if (self::isPublicHoliday($currentDate)) {
        continue;
      }

      $workingDays++;
    }

    return $workingDays;
  }


  public static function appClasses()
  {

    $data = config('custom.custom');


    // default data array
    $DefaultData = [
      'myLayout' => 'vertical',
      'myTheme' => 'theme-default',
      'myStyle' => 'light',
      'myRTLSupport' => true,
      'myRTLMode' => true,
      'hasCustomizer' => true,
      'showDropdownOnHover' => true,
      'displayCustomizer' => true,
      'contentLayout' => 'compact',
      'headerType' => 'fixed',
      'navbarType' => 'fixed',
      'menuFixed' => true,
      'menuCollapsed' => false,
      'footerFixed' => false,
      'customizerControls' => [
        'rtl',
        'style',
        'headerType',
        'contentLayout',
        'layoutCollapsed',
        'showDropdownOnHover',
        'layoutNavbarOptions',
        'themes',
      ],
      //   'defaultLanguage'=>'en',
    ];

    // if any key missing of array from custom.php file it will be merge and set a default value from dataDefault array and store in data variable
    $data = array_merge($DefaultData, $data);

    // All options available in the template
    $allOptions = [
      'myLayout' => ['vertical', 'horizontal', 'blank', 'front'],
      'menuCollapsed' => [true, false],
      'hasCustomizer' => [true, false],
      'showDropdownOnHover' => [true, false],
      'displayCustomizer' => [true, false],
      'contentLayout' => ['compact', 'wide'],
      'headerType' => ['fixed', 'static'],
      'navbarType' => ['fixed', 'static', 'hidden'],
      'myStyle' => ['light', 'dark', 'system'],
      'myTheme' => ['theme-default', 'theme-bordered', 'theme-semi-dark'],
      'myRTLSupport' => [true, false],
      'myRTLMode' => [true, false],
      'menuFixed' => [true, false],
      'footerFixed' => [true, false],
      'customizerControls' => [],
      // 'defaultLanguage'=>array('en'=>'en','fr'=>'fr','de'=>'de','ar'=>'ar'),
    ];

    //if myLayout value empty or not match with default options in custom.php config file then set a default value
    foreach ($allOptions as $key => $value) {
      if (array_key_exists($key, $DefaultData)) {
        if (gettype($DefaultData[$key]) === gettype($data[$key])) {
          // data key should be string
          if (is_string($data[$key])) {
            // data key should not be empty
            if (isset($data[$key]) && $data[$key] !== null) {
              // data key should not be exist inside allOptions array's sub array
              if (!array_key_exists($data[$key], $value)) {
                // ensure that passed value should be match with any of allOptions array value
                $result = array_search($data[$key], $value, 'strict');
                if (empty($result) && $result !== 0) {
                  $data[$key] = $DefaultData[$key];
                }
              }
            } else {
              // if data key not set or
              $data[$key] = $DefaultData[$key];
            }
          }
        } else {
          $data[$key] = $DefaultData[$key];
        }
      }
    }
    $styleVal = $data['myStyle'] == "dark" ? "dark" : "light";
    $styleUpdatedVal = $data['myStyle'] == "dark" ? "dark" : $data['myStyle'];
    // Determine if the layout is admin or front based on cookies
    $layoutName = $data['myLayout'];
    $isAdmin = Str::contains($layoutName, 'front') ? false : true;

    $modeCookieName = $isAdmin ? 'admin-mode' : 'front-mode';
    $colorPrefCookieName = $isAdmin ? 'admin-colorPref' : 'front-colorPref';

    // Determine style based on cookies, only if not 'blank-layout'
    if ($layoutName !== 'blank') {
      if (isset($_COOKIE[$modeCookieName])) {
        $styleVal = $_COOKIE[$modeCookieName];
        if ($styleVal === 'system') {
          $styleVal = isset($_COOKIE[$colorPrefCookieName]) ? $_COOKIE[$colorPrefCookieName] : 'light';
        }
        $styleUpdatedVal = $_COOKIE[$modeCookieName];
      }
    }

    isset($_COOKIE['theme']) ? $themeVal = $_COOKIE['theme'] : $themeVal = $data['myTheme'];

    $directionVal = isset($_COOKIE['direction']) ? ($_COOKIE['direction'] === "true" ? 'rtl' : 'ltr') : $data['myRTLMode'];

    //layout classes
    $layoutClasses = [
      'layout' => $data['myLayout'],
      'theme' => $themeVal,
      'themeOpt' => $data['myTheme'],
      'style' => $styleVal,
      'styleOpt' => $data['myStyle'],
      'styleOptVal' => $styleUpdatedVal,
      'rtlSupport' => $data['myRTLSupport'],
      'rtlMode' => $data['myRTLMode'],
      'textDirection' => $directionVal, //$data['myRTLMode'],
      'menuCollapsed' => $data['menuCollapsed'],
      'hasCustomizer' => $data['hasCustomizer'],
      'showDropdownOnHover' => $data['showDropdownOnHover'],
      'displayCustomizer' => $data['displayCustomizer'],
      'contentLayout' => $data['contentLayout'],
      'headerType' => $data['headerType'],
      'navbarType' => $data['navbarType'],
      'menuFixed' => $data['menuFixed'],
      'footerFixed' => $data['footerFixed'],
      'customizerControls' => $data['customizerControls'],
    ];

    // sidebar Collapsed
    if ($layoutClasses['menuCollapsed'] == true) {
      $layoutClasses['menuCollapsed'] = 'layout-menu-collapsed';
    }

    // Header Type
    if ($layoutClasses['headerType'] == 'fixed') {
      $layoutClasses['headerType'] = 'layout-menu-fixed';
    }
    // Navbar Type
    if ($layoutClasses['navbarType'] == 'fixed') {
      $layoutClasses['navbarType'] = 'layout-navbar-fixed';
    } elseif ($layoutClasses['navbarType'] == 'static') {
      $layoutClasses['navbarType'] = '';
    } else {
      $layoutClasses['navbarType'] = 'layout-navbar-hidden';
    }

    // Menu Fixed
    if ($layoutClasses['menuFixed'] == true) {
      $layoutClasses['menuFixed'] = 'layout-menu-fixed';
    }


    // Footer Fixed
    if ($layoutClasses['footerFixed'] == true) {
      $layoutClasses['footerFixed'] = 'layout-footer-fixed';
    }

    // RTL Supported template
    if ($layoutClasses['rtlSupport'] == true) {
      $layoutClasses['rtlSupport'] = '/rtl';
    }

    // RTL Layout/Mode
    if ($layoutClasses['rtlMode'] == true) {
      $layoutClasses['rtlMode'] = 'rtl';
      $layoutClasses['textDirection'] = isset($_COOKIE['direction']) ? ($_COOKIE['direction'] === "true" ? 'rtl' : 'ltr') : 'rtl';
    } else {
      $layoutClasses['rtlMode'] = 'ltr';
      $layoutClasses['textDirection'] = isset($_COOKIE['direction']) && $_COOKIE['direction'] === "true" ? 'rtl' : 'ltr';
    }

    // Show DropdownOnHover for Horizontal Menu
    if ($layoutClasses['showDropdownOnHover'] == true) {
      $layoutClasses['showDropdownOnHover'] = true;
    } else {
      $layoutClasses['showDropdownOnHover'] = false;
    }

    // To hide/show display customizer UI, not js
    if ($layoutClasses['displayCustomizer'] == true) {
      $layoutClasses['displayCustomizer'] = true;
    } else {
      $layoutClasses['displayCustomizer'] = false;
    }

    return $layoutClasses;
  }

  public static function updatePageConfig($pageConfigs)
  {
    $demo = 'custom';
    if (isset($pageConfigs)) {
      if (count($pageConfigs) > 0) {
        foreach ($pageConfigs as $config => $val) {
          Config::set('custom.' . $demo . '.' . $config, $val);
        }
      }
    }
  }
}
