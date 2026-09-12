<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit App\Services\Mortgage\ViewModel */
?>
<!-- inkomen -->
  <!--t--><?php if ($v['is']['kInkomen']): ?><!--t-->
  <div style="animation:stepIn .3s ease both">
    <h1 style="font-weight:600;font-size:33px;line-height:1.12;letter-spacing:-0.033em;margin:0 0 10px"><!--b:t.incomeTitle--><?= e($v['t']['incomeTitle']) ?></h1>
    <p style="margin:0 0 24px;font-size:15.5px;line-height:1.55;color:var(--ink2)"><!--b:t.incomeIntro--><?= e($v['t']['incomeIntro']) ?></p>
    <div style="display:grid;gap:10px">
      <label style="display:block">
        <span style="display:block;font-size:12px;font-weight:600;color:var(--ink2);margin-bottom:7px"><!--b:t.incomeMain--><?= e($v['t']['incomeMain']) ?></span>
        <span style="display:flex;align-items:center;gap:10px;background:var(--surface);border:1.5px solid var(--line2);border-radius:12px;padding:0 15px">
          <span style="font-size:17px;color:var(--ink3)">€</span>
          <input type="text" inputmode="numeric" value="<?= e($v['f']['income']) ?>" name="income" placeholder="0" style="flex:1;min-width:0;border:0;outline:none;background:transparent;font-family:'JetBrains Mono',monospace;font-size:21px;font-weight:500;padding:13px 0" data-b-value="{f.income}">
        </span>
      </label>
      <label style="display:block">
        <span style="display:block;font-size:12px;font-weight:600;color:var(--ink2);margin-bottom:7px"><!--b:t.incomePartner--><?= e($v['t']['incomePartner']) ?></span>
        <span style="display:flex;align-items:center;gap:10px;background:var(--surface);border:1.5px solid var(--line2);border-radius:12px;padding:0 15px">
          <span style="font-size:17px;color:var(--ink3)">€</span>
          <input type="text" inputmode="numeric" value="<?= e($v['f']['income2']) ?>" name="income2" placeholder="0" style="flex:1;min-width:0;border:0;outline:none;background:transparent;font-family:'JetBrains Mono',monospace;font-size:21px;font-weight:500;padding:13px 0" data-b-value="{f.income2}">
        </span>
        <span style="display:block;font-size:12.5px;line-height:1.5;color:var(--ink2);margin-top:6px"><!--b:f.incomeNote--><?= e($v['f']['incomeNote']) ?></span>
      </label>
      <label style="display:block">
        <span style="display:block;font-size:12px;font-weight:600;color:var(--ink2);margin-bottom:7px"><!--b:t.woz--><?= e($v['t']['woz']) ?></span>
        <span style="display:flex;align-items:center;gap:10px;background:var(--surface);border:1.5px solid var(--line2);border-radius:12px;padding:0 15px">
          <span style="font-size:17px;color:var(--ink3)">€</span>
          <input type="text" inputmode="numeric" value="<?= e($v['f']['woz']) ?>" name="woz" style="flex:1;min-width:0;border:0;outline:none;background:transparent;font-family:'JetBrains Mono',monospace;font-size:21px;font-weight:500;padding:13px 0" data-b-value="{f.woz}">
        </span>
      </label>
    </div>
    <div style="margin-top:14px;padding:16px;border-radius:12px;background:var(--accent-soft);border:1px solid var(--accent-line)">
      <span style="display:block;font-size:13px;font-weight:600;margin-bottom:4px"><!--b:f.taxTeaserTitle--><?= e($v['f']['taxTeaserTitle']) ?></span>
      <span style="display:block;font-size:13.5px;line-height:1.55;color:var(--ink2)"><!--b:f.taxTeaser--><?= e($v['f']['taxTeaser']) ?></span>
    </div>
  </div>
  <!--t--><?php endif; ?><!--t-->
