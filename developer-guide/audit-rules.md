#untuk kegunaan auditing bagi development envi purpose only, not need to run this on production envi

1. run php artisan db:seed --class=AuditSampleSeeder  -- untuk seed data audit di table audit
2. run php artisan audit:test   -- untuk test command audit function or not

