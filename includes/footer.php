<?php // includes/footer.php ?>

<style>
  @keyframes ims-spin { to { transform: rotate(360deg); } }
  .ims-loading-spinner {
    width: 1.1rem;
    height: 1.1rem;
    border: 2px solid rgba(40, 167, 69, 0.25);
    border-top-color: #28a745;
    border-radius: 9999px;
    animation: ims-spin 0.8s linear infinite;
    flex-shrink: 0;
  }
</style>

<div id="imsLoadingOverlay"
     class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/35">
  <div class="flex items-center gap-3 rounded-lg border border-border-light dark:border-border-dark bg-card-light dark:bg-card-dark px-5 py-4 shadow-xl">
    <span class="ims-loading-spinner" aria-hidden="true"></span>
    <span id="imsLoadingText" class="text-sm font-semibold text-text-light dark:text-text-dark">Processing...</span>
  </div>
</div>

<script>
  (function () {
    const overlay = document.getElementById('imsLoadingOverlay');
    const textEl = document.getElementById('imsLoadingText');

    function showLoading(message) {
      if (!overlay) return;
      textEl.textContent = message || 'Processing...';
      overlay.classList.remove('hidden');
      overlay.classList.add('flex');
    }

    function setButtonLoadingState(btn) {
      if (!btn || btn.dataset.loadingBound === '1') return;
      btn.dataset.loadingBound = '1';

      if (btn.tagName === 'INPUT') {
        btn.dataset.originalValue = btn.value;
        btn.value = 'Processing...';
      } else {
        btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="inline-flex items-center gap-2"><span class="ims-loading-spinner"></span><span>Processing...</span></span>';
      }
    }

    document.addEventListener('submit', function (e) {
      const form = e.target;
      if (!(form instanceof HTMLFormElement)) return;
      if (form.hasAttribute('data-no-loading')) return;
      if (e.defaultPrevented) return;

      if (form.dataset.loadingLocked === '1') {
        e.preventDefault();
        return;
      }
      form.dataset.loadingLocked = '1';

      const submitter = e.submitter && e.submitter instanceof HTMLElement ? e.submitter : null;
      if (submitter) setButtonLoadingState(submitter);

      form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
        btn.disabled = true;
      });

      showLoading(form.getAttribute('data-loading-text') || 'Processing transaction...');
    });

    document.addEventListener('click', function (e) {
      const rawLink = e.target.closest('a');
      if (!rawLink) return;
      const href = rawLink.getAttribute('href') || '';
      const isLogoutLink = /\bpage=logout\b/i.test(href) || /\/logout(?:[/?#]|$)/i.test(href);
      const link = rawLink.matches('a[data-loading]') || isLogoutLink ? rawLink : null;
      if (!link) return;
      if (link.target === '_blank' || link.hasAttribute('download')) return;
      if (href === '' || href.startsWith('#') || href.startsWith('javascript:')) return;
      const fallbackText = isLogoutLink ? 'Signing out...' : 'Loading...';
      showLoading(link.getAttribute('data-loading-text') || fallbackText);
    });
  })();
</script>

</body>
</html>
