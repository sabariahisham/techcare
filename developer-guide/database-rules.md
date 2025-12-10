# Database Rules

## 1. Naming
- table: tickets
- foreign key: user_id
- timestamps: yes
- softdelete: optional

---

## 2. Migration Example
```php
$table->id();
$table->string('title');
$table->text('message')->nullable();
$table->timestamps();
```
