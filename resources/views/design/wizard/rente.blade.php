<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit App\Services\Mortgage\ViewModel */
?>
<!-- rente -->
  <!--t--><?php if ($v['is']['kRente']): ?><!--t-->
  <div style="animation:stepIn .3s ease both">
    <h1 style="font-weight:600;font-size:33px;line-height:1.12;letter-spacing:-0.033em;margin:0 0 10px"><!--b:t.rateTitle--><?= e($v['t']['rateTitle']) ?></h1>
    <p style="margin:0 0 24px;font-size:15.5px;line-height:1.55;color:var(--ink2)"><!--b:f.rateIntro--><?= e($v['f']['rateIntro']) ?></p>

    <div style="padding:22px;border-radius:14px;background:var(--surface);border:1px solid var(--line)">
      <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:2px">
        <span style="font-size:12.5px;font-weight:600;color:var(--ink2)"><!--b:t.rate--><?= e($v['t']['rate']) ?></span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:36px;font-weight:500;letter-spacing:-0.04em"><!--b:f.rate--><?= e($v['f']['rate']) ?><!--t-->%</span>
      </div>
      <span style="display:block;font-size:12.5px;color:var(--ink3);margin-bottom:12px"><!--b:f.rateSource--><?= e($v['f']['rateSource']) ?></span>
      <input type="range" min="50" max="800" step="5" value="<?= e($v['n']['rateSlider']) ?>" name="rateSlider" style="width:100%;display:block" data-b-value="{n.rateSlider}">
      <div style="display:flex;justify-content:space-between;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink3)"><span><!--b:f.rateMin--><?= e($v['f']['rateMin']) ?><!--t-->%</span><span><!--b:f.rateMax--><?= e($v['f']['rateMax']) ?><!--t-->%</span></div>
    </div>

    <div style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div style="padding:16px;border-radius:14px;background:var(--surface2);border:1px solid var(--line)">
        <span style="display:block;font-size:10.5px;letter-spacing:0.13em;text-transform:uppercase;color:var(--ink2);font-weight:600;margin-bottom:6px"><!--b:t.monthlyThen--><?= e($v['t']['monthlyThen']) ?></span>
        <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:23px;font-weight:500;letter-spacing:-0.03em">€ <!--b:f.grossMonthly--><?= e($v['f']['grossMonthly']) ?></span>
      </div>
      <div style="padding:16px;border-radius:14px;background:<?= e($v['st']['deltaBg']) ?>;border:1px solid <?= e($v['st']['deltaLine']) ?>" data-b-style="padding:16px;border-radius:14px;background:{st.deltaBg};border:1px solid {st.deltaLine}">
        <span style="display:block;font-size:10.5px;letter-spacing:0.13em;text-transform:uppercase;color:var(--ink2);font-weight:600;margin-bottom:6px"><!--b:f.deltaLabel--><?= e($v['f']['deltaLabel']) ?></span>
        <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:23px;font-weight:500;letter-spacing:-0.03em;color:<?= e($v['st']['deltaInk']) ?>" data-b-style="display:block;font-family:'JetBrains Mono',monospace;font-size:23px;font-weight:500;letter-spacing:-0.03em;color:{st.deltaInk}"><!--b:f.deltaValue--><?= e($v['f']['deltaValue']) ?></span>
      </div>
    </div>

    <!--t--><?php if ($v['is']['free']): ?><!--t-->
    <button name="do" value="openPay" style="display:flex;align-items:flex-start;gap:10px;width:100%;margin-top:10px;padding:15px;border-radius:12px;border:1px solid var(--gold-line);background:var(--gold-soft);cursor:pointer;text-align:left">
      <span style="width:6px;height:6px;border-radius:99px;background:var(--gold-dot);flex:none;margin-top:6px"></span>
      <span style="flex:1;min-width:0;font-size:13.5px;line-height:1.5;color:var(--gold)"><!--b:t.rateUpsell--><?= e($v['t']['rateUpsell']) ?></span>
    </button>
    <!--t--><?php endif; ?><!--t-->
  </div>
  <!--t--><?php endif; ?><!--t-->
