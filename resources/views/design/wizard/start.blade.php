<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit App\Services\Mortgage\ViewModel */
?>
<!-- start -->
  <!--t--><?php if ($v['is']['kStart']): ?><!--t-->
  <div style="animation:stepIn .3s ease both">
    <h1 style="font-weight:600;font-size:33px;line-height:1.12;letter-spacing:-0.033em;margin:0 0 10px"><!--b:t.startTitle--><?= e($v['t']['startTitle']) ?></h1>
    <p style="margin:0 0 26px;font-size:15.5px;line-height:1.55;color:var(--ink2)"><!--b:t.startIntro--><?= e($v['t']['startIntro']) ?></p>
    <div style="display:grid;gap:10px">
      <button name="do" value="pathBuy" style="text-align:left;padding:20px;border-radius:14px;border:1.5px solid <?= e($v['st']['buyB']) ?>;background:<?= e($v['st']['buyBg']) ?>;cursor:pointer" data-b-style="text-align:left;padding:20px;border-radius:14px;border:1.5px solid {st.buyB};background:{st.buyBg};cursor:pointer">
        <span style="display:block;font-size:10.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--ink3);font-weight:600;margin-bottom:8px"><!--b:t.buyEyebrow--><?= e($v['t']['buyEyebrow']) ?></span>
        <span style="display:block;font-size:18px;font-weight:600;letter-spacing:-0.02em;margin-bottom:5px"><!--b:t.buyTitle--><?= e($v['t']['buyTitle']) ?></span>
        <span style="display:block;font-size:14px;line-height:1.5;color:var(--ink2)"><!--b:t.buyDesc--><?= e($v['t']['buyDesc']) ?></span>
      </button>
      <button name="do" value="pathRenew" style="text-align:left;padding:20px;border-radius:14px;border:1.5px solid <?= e($v['st']['renewB']) ?>;background:<?= e($v['st']['renewBg']) ?>;cursor:pointer" data-b-style="text-align:left;padding:20px;border-radius:14px;border:1.5px solid {st.renewB};background:{st.renewBg};cursor:pointer">
        <span style="display:block;font-size:10.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--ink3);font-weight:600;margin-bottom:8px"><!--b:t.renewEyebrow--><?= e($v['t']['renewEyebrow']) ?></span>
        <span style="display:block;font-size:18px;font-weight:600;letter-spacing:-0.02em;margin-bottom:5px"><!--b:t.renewTitle--><?= e($v['t']['renewTitle']) ?></span>
        <span style="display:block;font-size:14px;line-height:1.5;color:var(--ink2)"><!--b:t.renewDesc--><?= e($v['t']['renewDesc']) ?></span>
      </button>
    </div>
  </div>
  <!--t--><?php endif; ?><!--t-->
