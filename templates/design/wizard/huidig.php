<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit Hypotheek\Design\ViewModel */
?>
<!-- oversluiten: huidige situatie -->
  <!--t--><?php if ($v['is']['kHuidig']): ?><!--t-->
  <div style="animation:stepIn .3s ease both">
    <h1 style="font-weight:600;font-size:33px;line-height:1.12;letter-spacing:-0.033em;margin:0 0 10px"><!--b:t.currentTitle--><?= e($v['t']['currentTitle']) ?></h1>
    <p style="margin:0 0 24px;font-size:15.5px;line-height:1.55;color:var(--ink2)"><!--b:t.currentIntro--><?= e($v['t']['currentIntro']) ?></p>

    <div style="display:grid;gap:10px;margin-bottom:14px">
      <label style="display:block">
        <span style="display:block;font-size:12px;font-weight:600;color:var(--ink2);margin-bottom:7px"><!--b:t.currentBalance--><?= e($v['t']['currentBalance']) ?></span>
        <span style="display:flex;align-items:center;gap:10px;background:var(--surface);border:1.5px solid var(--line2);border-radius:12px;padding:0 15px">
          <span style="font-size:17px;color:var(--ink3)">€</span>
          <input type="text" inputmode="numeric" value="<?= e($v['f']['curBalance']) ?>" name="curBalance" style="flex:1;min-width:0;border:0;outline:none;background:transparent;font-family:'JetBrains Mono',monospace;font-size:21px;font-weight:500;padding:13px 0" data-b-value="{f.curBalance}">
        </span>
      </label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <label style="display:block">
          <span style="display:block;font-size:12px;font-weight:600;color:var(--ink2);margin-bottom:7px"><!--b:t.currentRate--><?= e($v['t']['currentRate']) ?></span>
          <span style="display:flex;align-items:center;gap:8px;background:var(--surface);border:1.5px solid var(--line2);border-radius:12px;padding:0 15px">
            <input type="text" inputmode="decimal" value="<?= e($v['f']['curRate']) ?>" name="curRate" style="flex:1;min-width:0;border:0;outline:none;background:transparent;font-family:'JetBrains Mono',monospace;font-size:21px;font-weight:500;padding:13px 0" data-b-value="{f.curRate}">
            <span style="font-size:15px;color:var(--ink3)">%</span>
          </span>
        </label>
        <label style="display:block">
          <span style="display:block;font-size:12px;font-weight:600;color:var(--ink2);margin-bottom:7px"><!--b:t.remaining--><?= e($v['t']['remaining']) ?></span>
          <span style="display:flex;align-items:center;gap:8px;background:var(--surface);border:1.5px solid var(--line2);border-radius:12px;padding:0 15px">
            <input type="text" inputmode="numeric" value="<?= e($v['f']['curRemaining']) ?>" name="curRemaining" style="flex:1;min-width:0;border:0;outline:none;background:transparent;font-family:'JetBrains Mono',monospace;font-size:21px;font-weight:500;padding:13px 0" data-b-value="{f.curRemaining}">
            <span style="font-size:14px;color:var(--ink3)"><!--b:t.years--><?= e($v['t']['years']) ?></span>
          </span>
        </label>
      </div>
    </div>

    <span style="display:block;font-size:12px;font-weight:600;color:var(--ink2);margin-bottom:7px"><!--b:t.currentForm--><?= e($v['t']['currentForm']) ?></span>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:14px">
      <!--list:curFormOpts-1--><?php foreach ($v['curFormOpts'] as $o): ?><!--t-->
        <button name="do" value="curForm:<?= e($o['key']) ?>" style="padding:13px 4px;border-radius:11px;border:1.5px solid <?= e($o['b']) ?>;background:<?= e($o['bg']) ?>;font-size:13px;font-weight:600;cursor:pointer"><?= e($o['short']) ?></button>
      <!--t--><?php endforeach; ?><!--/list:curFormOpts-1-->
    </div>

    <button name="do" value="togglePre" style="display:flex;align-items:flex-start;gap:11px;width:100%;padding:15px;border-radius:12px;border:1.5px solid var(--line);background:var(--surface);cursor:pointer;text-align:left">
      <span style="width:20px;height:20px;border-radius:6px;border:1.5px solid <?= e($v['st']['preB']) ?>;background:<?= e($v['st']['preFill']) ?>;flex:none;margin-top:1px" data-b-style="width:20px;height:20px;border-radius:6px;border:1.5px solid {st.preB};background:{st.preFill};flex:none;margin-top:1px"></span>
      <span style="flex:1;min-width:0">
        <span style="display:block;font-size:14.5px;font-weight:600"><!--b:t.pre2013--><?= e($v['t']['pre2013']) ?></span>
        <span style="display:block;font-size:13px;line-height:1.5;color:var(--ink2);margin-top:2px"><!--b:t.pre2013Desc--><?= e($v['t']['pre2013Desc']) ?></span>
      </span>
    </button>

    <div style="margin-top:14px;padding:16px;border-radius:12px;background:var(--surface2);border:1px solid var(--line);display:flex;justify-content:space-between;align-items:baseline;gap:12px">
      <span style="font-size:14px;color:var(--ink2)"><!--b:t.payNow--><?= e($v['t']['payNow']) ?></span>
      <span style="font-family:'JetBrains Mono',monospace;font-size:22px;font-weight:500;letter-spacing:-0.02em">€ <!--b:f.curPayment--><?= e($v['f']['curPayment']) ?></span>
    </div>
  </div>
  <!--t--><?php endif; ?><!--t-->
