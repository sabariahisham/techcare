<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

  /*
  |--------------------------------------------------------------------------
  | Default Log Channel
  |--------------------------------------------------------------------------
  |
  | This option defines the default log channel that is utilized to write
  | messages to your logs. The value provided here should match one of
  | the channels present in the list of "channels" configured below.
  |
  */

  'default' => env('LOG_CHANNEL', 'stack'),

  /*
  |--------------------------------------------------------------------------
  | Deprecations Log Channel
  |--------------------------------------------------------------------------
  |
  | This option controls the log channel that should be used to log warnings
  | regarding deprecated PHP and library features. This allows you to get
  | your application ready for upcoming major versions of dependencies.
  |
  */

  'deprecations' => [
    'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
    'trace' => env('LOG_DEPRECATIONS_TRACE', false),
  ],

  /*
  |--------------------------------------------------------------------------
  | Log Channels
  |--------------------------------------------------------------------------
  |
  | Here you may configure the log channels for your application. Laravel
  | utilizes the Monolog PHP logging library, which includes a variety
  | of powerful log handlers and formatters that you're free to use.
  |
  | Available Drivers: "single", "daily", "slack", "syslog",
  |                    "errorlog", "monolog", "custom", "stack"
  |
  */

  'channels' => [

    'stack' => [
      'driver' => 'stack',
      // 'channels' => explode(',', env('LOG_STACK', 'single')),
      'channels' => ['daily'],
      'ignore_exceptions' => false,
    ],

    'single' => [
      'driver' => 'single',
      'path' => storage_path('logs/laravel.log'),
      'level' => env('LOG_LEVEL', 'debug'),
      'replace_placeholders' => true,
    ],

    // ============================================================================
    // DAILY LOG - General application log with daily rotation
    // ============================================================================
    'daily' => [
      'driver' => 'daily',
      'path' => storage_path('logs/laravel.log'),
      'level' => env('LOG_LEVEL', 'debug'),
      'days' => env('LOG_DAILY_DAYS', 14),
      'replace_placeholders' => true,
    ],

    // ============================================================================
    // SLA LOG - Dedicated channel for SLA notification tracking
    // ============================================================================
    // Purpose: Track all SLA-related processes (reminders, overdue notifications)
    // Format: Structured logs with tags [SLA][TYPE][STATUS]
    // Retention: 30 days for audit compliance
    // ============================================================================
    'sla' => [
      'driver' => 'daily',
      'path' => storage_path('logs/sla.log'),
      'level' => env('LOG_LEVEL', 'info'),
      'days' => 30, // Keep for 30 days for government audit requirements
      'replace_placeholders' => true,
    ],

    // ============================================================================
    // MAIL LOG - Dedicated channel for email sending tracking
    // ============================================================================
    // Purpose: Track all email operations (send attempts, success, failures)
    // Format: Structured logs with recipient info (email masked for privacy)
    // Retention: 30 days for audit compliance
    // ============================================================================
    'mail' => [
      'driver' => 'daily',
      'path' => storage_path('logs/mail.log'),
      'level' => env('LOG_LEVEL', 'info'),
      'days' => 30, // Keep for 30 days for government audit requirements
      'replace_placeholders' => true,
    ],

    'slack' => [
      'driver' => 'slack',
      'url' => env('LOG_SLACK_WEBHOOK_URL'),
      'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
      'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
      'level' => env('LOG_LEVEL', 'critical'),
      'replace_placeholders' => true,
    ],

    'papertrail' => [
      'driver' => 'monolog',
      'level' => env('LOG_LEVEL', 'debug'),
      'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
      'handler_with' => [
        'host' => env('PAPERTRAIL_URL'),
        'port' => env('PAPERTRAIL_PORT'),
        'connectionString' => 'tls://' . env('PAPERTRAIL_URL') . ':' . env('PAPERTRAIL_PORT'),
      ],
      'processors' => [PsrLogMessageProcessor::class],
    ],

    'stderr' => [
      'driver' => 'monolog',
      'level' => env('LOG_LEVEL', 'debug'),
      'handler' => StreamHandler::class,
      'formatter' => env('LOG_STDERR_FORMATTER'),
      'with' => [
        'stream' => 'php://stderr',
      ],
      'processors' => [PsrLogMessageProcessor::class],
    ],

    'syslog' => [
      'driver' => 'syslog',
      'level' => env('LOG_LEVEL', 'debug'),
      'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
      'replace_placeholders' => true,
    ],

    'errorlog' => [
      'driver' => 'errorlog',
      'level' => env('LOG_LEVEL', 'debug'),
      'replace_placeholders' => true,
    ],

    'null' => [
      'driver' => 'monolog',
      'handler' => NullHandler::class,
    ],

    'emergency' => [
      'path' => storage_path('logs/laravel.log'),
    ],

  ],

];
