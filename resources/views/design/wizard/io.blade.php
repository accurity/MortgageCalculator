<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit App\Services\Mortgage\ViewModel */
?>
<!-- aflossingsvrij -->
  <!--t--><?php if ($v['is']['kIo']): ?><!--t-->
  <div style="animation:stepIn .3s ease both">
    <h1 style="font-weight:600;font-size:33px;line-height:1.12;letter-spacing:-0.033em;margin:0 0 10px"><!--b:t.ioTitle--><?= e($v['t']['ioTitle']) ?></h1>
    <p style="margin:0 0 22px;font-size:15.5px;line-height:1.55;color:var(--ink2)"><!--b:f.ioIntro--><?= e($v['f']['ioIntro']) ?></p>

    <div style="padding:20px;border-radius:14px;background:var(--surface);border:1px solid var(--line);margin-bottom:10px">
      <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:2px">
        <span style="font-size:12.5px;font-weight:600;color:var(--ink2)"><!--b:t.ioPart--><?= e($v['t']['ioPart']) ?></span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:27px;font-weight:500;letter-spacing:-0.03em">€ <!--b:f.io--><?= e($v['f']['io']) ?></span>
      </div>
      <span style="display:block;font-size:12.5px;color:var(--ink3);margin-bottom:10px"><!--b:f.ioShare--><?= e($v['f']['ioShare']) ?></span>
      <input type="range" min="0" max="<?= e($v['n']['ioMax']) ?>" step="5000" value="<?= e($v['n']['io']) ?>" name="io" style="width:100%;display:block" data-b-max="{n.ioMax}" data-b-value="{n.io}">
      <div style="display:flex;justify-content:space-between;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink3)"><span>€ 0</span><span><!--b:f.ioMaxLabel--><?= e($v['f']['ioMaxLabel']) ?></span></div>
    </div>

    <!--t--><?php if ($v['is']['hasIo']): ?><!--t-->
    <div style="animation:fadeIn .25s ease both">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
        <div style="padding:16px;border-radius:14px;background:var(--accent-soft);border:1px solid var(--accent-line)">
          <span style="display:block;font-size:10.5px;letter-spacing:0.13em;text-transform:uppercase;color:var(--ink2);font-weight:600;margin-bottom:6px"><!--b:t.ioSavingLabel--><?= e($v['t']['ioSavingLabel']) ?></span>
          <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:23px;font-weight:500;letter-spacing:-0.03em">− € <!--b:f.ioSaving--><?= e($v['f']['ioSaving']) ?></span>
        </div>
        <div style="padding:16px;border-radius:14px;background:var(--warn-soft);border:1px solid var(--warn-line)">
          <span style="display:block;font-size:10.5px;letter-spacing:0.13em;text-transform:uppercase;color:var(--ink2);font-weight:600;margin-bottom:6px"><!--b:t.ioLeftLabel--><?= e($v['t']['ioLeftLabel']) ?></span>
          <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:23px;font-weight:500;letter-spacing:-0.03em;color:var(--warn)">€ <!--b:f.io--><?= e($v['f']['io']) ?></span>
        </div>
      </div>
      <div style="padding:16px;border-radius:12px;background:var(--surface2);border:1px solid var(--line)">
        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;padding-bottom:10px;border-bottom:1px solid var(--line)">
          <span style="font-size:13.5px;color:var(--ink2)"><!--b:t.ioRateLabel--><?= e($v['t']['ioRateLabel']) ?></span>
          <span style="font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:600"><!--b:f.ioRate--><?= e($v['f']['ioRate']) ?><!--t-->%</span>
        </div>
        <p style="margin:10px 0 0;font-size:13px;line-height:1.55;color:var(--ink2)"><!--b:f.ioRateNote--><?= e($v['f']['ioRateNote']) ?></p>
        <p style="margin:8px 0 0;font-size:13px;line-height:1.55;color:var(--ink2)"><!--b:f.ioTaxNote--><?= e($v['f']['ioTaxNote']) ?></p>
      </div>
    </div>
    <!--t--><?php endif; ?><!--t-->
    <!--t--><?php if ($v['is']['noIo']): ?><!--t-->
      <div style="padding:16px;border-radius:12px;background:var(--surface2);border:1px solid var(--line);font-size:13.5px;line-height:1.55;color:var(--ink2)"><!--b:t.ioNone--><?= e($v['t']['ioNone']) ?></div>
    <!--t--><?php endif; ?><!--t-->
  </div>
  <!--t--><?php endif; ?><!--t-->
