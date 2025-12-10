# API JSON Format

All AJAX responses must follow:

```json
{
  "status": true,
  "message": "Saved successfully",
  "data": {}
}
```

## Error Format:
```json
{
  "status": false,
  "message": "Validation error",
  "errors": {}
}
```
