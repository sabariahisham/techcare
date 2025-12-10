# AJAX Rules

## 1. Library
- Use jQuery only
- Avoid axios, fetch, promise chains

---

## 2. Template
```js
$.ajax({
    url: '/tickets/store',
    method: 'POST',
    data: {
        title: $.trim($('#title').val()),
        message: $('<div>').text($('#message').val()).html(),
        _token: csrfToken
    },
    success: function(res) {
        if (res.status) {
            alert(res.message);
        }
    },
    error: function(err) {
        console.log(err);
    }
});
```

---

## 3. Response Format
JSON only:
```json
{
  "status": true,
  "message": "Success",
  "data": {}
}
```
