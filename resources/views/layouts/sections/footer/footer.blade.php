@php
$containerFooter = (isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
  <div class="{{ $containerFooter }}">
    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
      <div class="text-body">
       <span style="color: white;">© <script>document.write(new Date().getFullYear())</script> Sistem TechCare MOF </span> 
      </div>
      <div class="d-none d-lg-inline-block">
        {{-- <a href="{{ config('variables.licenseUrl') ? config('variables.licenseUrl') : '#' }}" class="footer-link me-4" target="_blank">License</a>
        <a href="{{ config('variables.moreThemes') ? config('variables.moreThemes') : '#' }}" target="_blank" class="footer-link me-4">More Themes</a>
        <a href="{{ config('variables.documentation') ? config('variables.documentation').'/laravel-introduction.html' : '#' }}" target="_blank" class="footer-link me-4">Documentation</a> --}}
        <span style="font-size: ; color: white;">Dibangunkan oleh Bahagian Teknologi Maklumat, Kementerian Kewangan</span> 
      </div>
    </div>
  </div>
</footer>
<!--/ Footer-->
