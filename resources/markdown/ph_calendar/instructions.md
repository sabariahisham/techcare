# AI Context & Coding Instructions for Public Holiday Scraper

You are an expert backend developer specializing in Laravel 12 and PHP 8.4. You must follow these strict project boundaries and architectural rules for every code generation task.

## 1. Project Context
This project manages Malaysia's public holidays with two entry points: automated web scraping from `publicholidays.com.my`, and a manual CRUD admin interface.

## 2. Core Technical Stack
- **Framework:** Laravel 12.x
- **PHP Version:** PHP 8.4+ (Strict typing, constructor promotion, and modern PHP features are encouraged).
- **HTML Parsing Engine:** `voku/simple_html_dom` (Do NOT substitute with standard Guzzle regex or other legacy DOM parsers).

## 3. Database Architecture Rules (DO NOT DEVIATE)
- We use **JSON columns** (`states`, `states_except`) directly on the `public_holidays` table. There is NO pivot table and NO Many-to-Many relationship.
- **State Logic & Condition Rules:**
  1. **National / All States Holiday:** If the holiday applies to all states, set `states = null` in the database. Do NOT store all 16 state names.
  2. **State-Specific Holiday:** Store state names as a JSON array e.g. `["Johor", "Kedah"]`.
  3. **Except States (Optional):** Store excluded states in `states_except` as a JSON array e.g. `["Sabah", "Sarawak"]`. This field is nullable. `national` is not a valid value for `states_except`.

## 4. Coding & Scraping Constraints
- All scraping logic must reside inside the Custom Artisan Command `App\Console\Commands\ScrapeHolidays`.
- Always use `PublicHoliday::updateOrCreate()` based on a unique combination of `holiday_date` and `holiday_name` to prevent data duplication.
- Since web tables lack year strings, always pair the parsed date text with the dynamic target `$year` argument before parsing via `Carbon::parse()`.
- Store states as JSON directly on the model — do NOT use `states()->sync()` or any pivot table operation.

## 5. Documentation Reference
For full code implementation and step-by-step layouts, always cross-reference:
- `resources/markdown/ph_calendar/overview.md`
- `resources/markdown/ph_calendar/files.md`
- `resources/markdown/ph_calendar/flow.md`
