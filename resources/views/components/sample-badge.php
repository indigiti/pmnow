<?php
// Render ONLY for content explicitly classified as synthetic test data.
// The accessible description prevents the red check icon being mistaken for
// independent editorial verification or an authenticity seal.
?>
<span class="pm-sample-mark" role="img"
      aria-label="Synthetic test content — not verified reporting"
      title="Synthetic test content · Not verified reporting">
  <img class="pm-sample-mark__icon"
       src="<?=pm_e(pm_url('/media/sample-content-badge.svg'))?>"
       alt="" width="16" height="16" decoding="async" aria-hidden="true">
</span>
