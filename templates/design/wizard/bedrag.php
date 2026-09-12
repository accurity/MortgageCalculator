<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit Hypotheek\Design\ViewModel */
?>
<!-- kopen: bedrag -->
  <!--t--><?php if ($v['is']['kBedrag']): ?><!--t-->
  <div style="animation:stepIn .3s ease both">
    <h1 style="font-weight:600;font-size:33px;line-height:1.12;letter-spacing:-0.033em;margin:0 0 10px"><!--b:t.amountTitle--><?= e($v['t']['amountTitle']) ?></h1>
    <p style="margin:0 0 24px;font-size:15.5px;line-height:1.55;color:var(--ink2)"><!--b:t.amountIntro--><?= e($v['t']['amountIntro']) ?></p>

    <div style="padding:20px;border-radius:14px;background:var(--surface);border:1px solid var(--line);margin-bottom:10px">
      <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:10px">
        <span style="font-size:12.5px;font-weight:600;color:var(--ink2)"><!--b:t.price--><?= e($v['t']['price']) ?></span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:27px;font-weight:500;letter-spacing:-0.03em">€ <!--b:f.price--><?= e($v['f']['price']) ?></span>
      </div>
      <input type="range" min="100000" max="1500000" step="5000" value="<?= e($v['n']['price']) ?>" name="price" style="width:100%;display:block" data-b-value="{n.price}">
    </div>

    <div style="padding:20px;border-radius:14px;background:var(--surface);border:1px solid var(--line)">
      <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:10px">
        <span style="font-size:12.5px;font-weight:600;color:var(--ink2)"><!--b:t.ownMoney--><?= e($v['t']['ownMoney']) ?></span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:27px;font-weight:500;letter-spacing:-0.03em">€ <!--b:f.own--><?= e($v['f']['own']) ?></span>
      </div>
      <input type="range" min="0" max="500000" step="1000" value="<?= e($v['n']['own']) ?>" name="own" style="width:100%;display:block" data-b-value="{n.own}">
    </div>

    <div style="margin-top:14px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div style="padding:16px;border-radius:14px;background:var(--accent-soft);border:1px solid var(--accent-line)">
        <span style="display:block;font-size:10.5px;letter-spacing:0.13em;text-transform:uppercase;color:var(--ink2);font-weight:600;margin-bottom:6px"><!--b:t.youBorrow--><?= e($v['t']['youBorrow']) ?></span>
        <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:23px;font-weight:500;letter-spacing:-0.03em">€ <!--b:f.loan--><?= e($v['f']['loan']) ?></span>
      </div>
      <div style="padding:16px;border-radius:14px;background:var(--surface2);border:1px solid var(--line)">
        <span style="display:block;font-size:10.5px;letter-spacing:0.13em;text-transform:uppercase;color:var(--ink2);font-weight:600;margin-bottom:6px"><!--b:t.rateClass--><?= e($v['t']['rateClass']) ?></span>
        <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:23px;font-weight:500;letter-spacing:-0.03em"><!--b:f.ltv--><?= e($v['f']['ltv']) ?><!--t-->%</span>
        <span style="display:block;font-size:12px;color:var(--ink3);margin-top:3px"><!--b:f.ltvNote--><?= e($v['f']['ltvNote']) ?></span>
      </div>
    </div>
  </div>
  <!--t--><?php endif; ?><!--t-->
