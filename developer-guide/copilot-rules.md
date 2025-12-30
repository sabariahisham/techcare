# Copilot Rules for Helpdesk System (Laravel 12)

## Purpose
This document defines how GitHub Copilot must generate code for this project.  
All suggestions must follow: simple, clean, predictable, secure, readable, high-performance, and aligned with Laravel 12 best practices.

---

## 1. Coding Style Expectations
- Always generate simple and direct solutions (no over-engineering).
- Follow Laravel 12 default MVC structure.
- Use Eloquent ORM directly. Avoid Repository Pattern unless specifically requested.
- Avoid creating unnecessary classes, traits, helpers, or advanced patterns.
- Maintain clean, readable, human-friendly code.
- Include comments only when necessary.

---

## 2. Controller Rules
- Use RESTful methods: index, create, store, show, edit, update, destroy.
- Validate input using Laravel Validator or FormRequest.
- Apply sanitization before saving: strip_tags(), htmlspecialchars().
- Return clean JSON for AJAX or Blade for views.
- Keep logic short and simple.

---

## 3. Blade Rules
- Use Bootstrap 5 and Sneat structure.
- Avoid inline JavaScript and CSS.
- Use Blade {{ }} for output sanitization.
- Use components or partials if it improves clarity (not complexity).

---

## 4. AJAX Rules (Very Important)
- Use jQuery only.
- Use simple $.ajax() style:
  - method
  - url
  - data
  - success
  - error
- Return JSON with: status, message, data.
- Avoid fetch(), axios(), promises unless specifically asked.

---

## 5. Database Rules
- Use Eloquent relationships.
- Avoid raw SQL unless required.
- Ensure migration fields are simple & clearly named.

---

## 6. Security & Sanitization Rules
Copilot must always:
- sanitize user input using strip_tags() and htmlspecialchars() before saving.
- validate input with Laravel validation rules.
- use fillable in models.
- return output using Blade escaping.
- avoid echoing unescaped HTML unless explicitly instructed.

---

## 7. Frontend Rules
- Use Bootstrap 5, Sneat layout.
- All forms should use clean markup, no unnecessary complexity.
- Use jQuery for DOM interactions.
- Use modals for create/edit when appropriate.

---

## 8. Project Structure
Copilot must follow the official project structure defined in `project-architecture.md`.

---

## 9. Quality Expectation
Code must:
- be easy to read
- be consistent across modules
- avoid circular logic
- avoid dead code
- avoid unused variables
- be secure

---

## 10. Suggestion Behavior
Copilot should:
- recommend best practice Laravel solutions
- suggest optimizations where appropriate
- avoid overly complex abstractions
- always prioritize clarity + maintainability
