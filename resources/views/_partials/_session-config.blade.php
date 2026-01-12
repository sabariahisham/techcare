{{-- Session Timeout Configuration --}}
{{-- This partial passes session configuration to JavaScript for timeout handling --}}
@auth
<script>
  window.sessionConfig = {
    lifetime: {{ config('session.lifetime', 120) }},
    warningTime: 5, // minutes before session expires to show warning
    pingInterval: 5 // minutes between session pings
  };
</script>
@endauth
