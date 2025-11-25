# Ticket Module Styling Guide

## Overview
The ticket module styling has been organized following Laravel and Sneat template best practices to improve maintainability and team collaboration.

## File Structure

```
resources/
├── assets/
│   └── scss/
│       ├── components/
│       │   └── offcanvas-tickets.scss    # Reusable offcanvas components
│       └── pages/
│           └── tickets.scss              # Ticket-specific styles
└── views/
    └── tickets/
        ├── index.blade.php              # Main ticket listing page
        └── partials/
            └── styles.blade.php         # Dynamic/PHP-generated styles
```

## Styling Organization

### 1. **SCSS Files** (Recommended for most styles)

#### `resources/assets/scss/pages/tickets.scss`
- **Purpose**: All ticket-specific styles
- **Contains**:
  - Form styling (form-control, form-select)
  - Select2 customizations
  - File input styling
  - Validation states
  - User search sections
  - Typography and sizing
- **Usage**: Automatically loaded via Vite

#### `resources/assets/scss/components/offcanvas-tickets.scss`
- **Purpose**: Reusable offcanvas components
- **Contains**:
  - Responsive offcanvas mixins
  - Size variants (.offcanvas-30, .offcanvas-50, etc.)
  - Form styling for offcanvas
  - Header and footer styling
  - Loading states
- **Usage**: Can be used across different modules

### 2. **Blade Partial** (For dynamic styles only)

#### `resources/views/tickets/partials/styles.blade.php`
- **Purpose**: Dynamic or PHP-generated styles that cannot be in SCSS
- **Use Cases**:
  - Status-dependent colors using PHP variables
  - Dynamic theme overrides
  - Temporary quick fixes
- **Usage**: `@include('tickets.partials.styles')`

## Benefits of This Structure

### For Development Team:
1. **Maintainability**: Styles are organized by purpose and reusability
2. **Performance**: SCSS files are compiled and cached by Vite
3. **Reusability**: Offcanvas components can be used in other modules
4. **Team Collaboration**: Clear separation makes it easier for multiple developers
5. **Version Control**: SCSS changes are easier to track and review

### For Sneat Template Integration:
1. **Consistent**: Follows Sneat's SCSS organization pattern
2. **Extensible**: Easy to add new page-specific styles
3. **Themeable**: Supports Sneat's theming system
4. **Responsive**: Built-in responsive utilities and mixins

## How to Use

### 1. Adding New Ticket Styles
Add to `resources/assets/scss/pages/tickets.scss`:

```scss
// New ticket feature styles
.ticket-priority-urgent {
  background-color: #ff4757;
  color: white;
}
```

### 2. Creating Reusable Components
Add to `resources/assets/scss/components/offcanvas-tickets.scss`:

```scss
// New offcanvas variant
.offcanvas-large {
  @include offcanvas-responsive(80%);
}
```

### 3. Dynamic Styles (Only when necessary)
Add to `resources/views/tickets/partials/styles.blade.php`:

```blade
<style>
@if($ticket->priority === 'urgent')
  .ticket-{{ $ticket->id }} {
    border-left: 5px solid {{ $urgentColor }};
  }
@endif
</style>
```

## Migration Guide

The original inline styles have been moved as follows:

| Original Location | New Location | Type |
|------------------|-------------|------|
| Offcanvas responsive widths | `components/offcanvas-tickets.scss` | SCSS |
| Form styling (form-select, etc.) | `pages/tickets.scss` | SCSS |
| Select2 customizations | `pages/tickets.scss` | SCSS |
| File input styling | `pages/tickets.scss` | SCSS |
| Offcanvas typography | Both SCSS files | SCSS |
| Dynamic/PHP styles | `partials/styles.blade.php` | Blade |

## Best Practices

### DO:
- ✅ Add new ticket styles to `pages/tickets.scss`
- ✅ Create reusable components in `components/offcanvas-tickets.scss`
- ✅ Use CSS variables for consistent theming
- ✅ Follow BEM naming conventions
- ✅ Use mixins for responsive behavior

### DON'T:
- ❌ Add inline styles in Blade templates
- ❌ Put dynamic PHP variables in SCSS files
- ❌ Mix component styles with page-specific styles
- ❌ Use !important unless absolutely necessary

## Vite Configuration

The following has been added to `vite.config.js`:

```javascript
// Processing Custom Pages & Components Scss Files
const CustomScssFiles = GetFilesArray('resources/assets/scss/**/*.scss');
```

This ensures all custom SCSS files are automatically processed by Vite.

## Team Workflow

1. **For new features**: Add styles to appropriate SCSS files
2. **For quick fixes**: Use `partials/styles.blade.php` temporarily, then move to SCSS
3. **For reusable components**: Always use `components/` directory
4. **For code reviews**: Check that styles are in the correct location

## Support

If you need to add styles for other modules, follow the same pattern:
- Create `resources/assets/scss/pages/{module-name}.scss`
- Create reusable components in `resources/assets/scss/components/`
- Update Vite configuration if needed
- Document the structure for your team
