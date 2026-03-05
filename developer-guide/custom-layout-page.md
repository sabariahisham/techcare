# Custom Layout Page Background

## Overview
This document explains how to customize the main layout page background gradient in TechCare UAT system. The background is applied to the main content area using CSS gradients.

---

## File Location
**File Path:** `resources/assets/css/custom-layout.css`

The background styles are defined for the following selectors:
```css
.content-wrapper,
.layout-page {
  /* Background gradient styles */
}
```

---

## Current Active Background

**Active Gradient:** Medium Primary (Purple-Blue)

```css
background: linear-gradient(135deg, #6d84e9 0%, #a55eec 100%) !important;
```

**Colors:**
- Start: `#6d84e9` (Medium Blue)
- End: `#a55eec` (Light Purple)
- Direction: `135deg` (Diagonal from bottom-left to top-right)

---

## How to Change Background

### Step 1: Open the File
Navigate to: `resources/assets/css/custom-layout.css`

### Step 2: Locate the Background Section
Find lines 10-20 containing the `.content-wrapper, .layout-page` selector.

### Step 3: Comment/Uncomment
1. Comment out the current active line by adding `/*` at the start and `*/` at the end
2. Uncomment your desired option by removing the `/*` and `*/`

**Example:**
```css
.content-wrapper,
.layout-page {
  /* background: linear-gradient(135deg, #667eea 0%, #764ba2 75%) !important; */ /* Dark */
  background: linear-gradient(135deg, #ff9f43 0%, #ffd86f 100%) !important; /* Medium Warning - NOW ACTIVE */
  /* background: linear-gradient(135deg, #6d84e9 0%, #a55eec 100%) !important; */ /* Medium Primary - DISABLED */
}
```

### Step 4: Rebuild Assets
After making changes, compile the assets:
```bash
npm run build
```

Or for development with auto-reload:
```bash
npm run dev
```

---

## Creating Custom Gradients

You can create your own custom gradient by modifying the values:

### Gradient Syntax
```css
background: linear-gradient(DIRECTION, START_COLOR POSITION, END_COLOR POSITION) !important;
```

**Parameters:**
- `DIRECTION`: Angle in degrees (e.g., `135deg`, `90deg`, `180deg`)
  - `90deg` = Left to right
  - `180deg` = Top to bottom
  - `135deg` = Diagonal (bottom-left to top-right)
- `START_COLOR`: Hex color code for the starting color
- `END_COLOR`: Hex color code for the ending color
- `POSITION`: Percentage where the color should be at full strength (optional)

## Color Guidelines

When choosing colors for your gradient:

1. **Contrast**: Ensure sufficient contrast between background and content
2. **Readability**: Cards and text should remain readable
3. **Brand Alignment**: Match your organization's color scheme
4. **Accessibility**: Consider color-blind users; avoid red-green combinations
5. **Professional**: Avoid overly bright or neon colors for business applications

### Recommended Color Ranges
- **Primary (Blue/Purple)**: `#3a7bd5` to `#a855f7`
- **Success (Green)**: `#06b6d4` to `#10b981`
- **Warning (Orange/Yellow)**: `#f59e0b` to `#fbbf24`
- **Danger (Red)**: `#dc2626` to `#f87171`

---

## Testing Your Changes

After changing the background:

1. ✅ Check dashboard visibility
2. ✅ Verify card readability
3. ✅ Test with different themes (light/dark)
4. ✅ Ensure text contrast is sufficient
5. ✅ Check on different screen sizes
6. ✅ Review with stakeholders

---

## Troubleshooting

### Background Not Changing?
1. Clear browser cache (`Ctrl + F5`)
2. Ensure you ran `npm run build`
3. Check if `!important` flag is present
4. Verify file path is correct

### Cards Not Visible?
- The gradient might be too dark or too bright
- Adjust color values for better contrast
- Consider semi-transparent card backgrounds

### Gradient Not Smooth?
- Ensure both colors are defined
- Check the degree value (should be between 0-360)
- Verify syntax is correct (commas, semicolons)

---

## Related Files

- **Main Layout File**: `resources/views/layouts/layoutMaster.blade.php`
- **Content Layout**: `resources/views/layouts/contentNavbarLayout.blade.php`
- **Dashboard Styles**: `resources/assets/css/dashboard-layout.css`
- **Custom Auth Styles**: `resources/assets/css/custom-auth.css`

---

## Best Practices

1. **Always keep a backup** of the original gradient before changing
2. **Test thoroughly** before deploying to production
3. **Document custom colors** with comments for future reference
4. **Use CSS variables** for easier theme switching (future enhancement)
5. **Consider user feedback** when selecting colors

---

## Future Enhancements

Consider implementing:
- Dynamic theme switching (light/dark mode)
- User-selectable background themes
- CSS custom properties for easy color management
- Theme configuration in admin panel

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | March 2026 | Initial documentation |

---

## Contact

For questions or suggestions about layout customization, contact the development team.