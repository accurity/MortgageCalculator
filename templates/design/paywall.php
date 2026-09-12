<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit Hypotheek\Design\ViewModel */
?>
<!-- ══════════════ PAYWALL ══════════════ -->
<!--t--><?php if ($v['is']['pay']): ?><!--t-->
<div name="do" value="closePay" style="position:fixed;inset:0;z-index:60;background:rgba(8,9,11,.5);backdrop-filter:blur(3px);display:flex;align-items:flex-end;justify-content:center;animation:fadeIn .2s ease both">
  <div name="do" value="stop" style="width:100%;max-width:450px;background:var(--surface);border-radius:20px 20px 0 0;padding:26px 22px calc(26px + env(safe-area-inset-bottom));animation:sheetUp .28s cubic-bezier(.4,0,.2,1) both;max-height:92vh;overflow-y:auto">
    <div style="width:36px;height:4px;border-radius:99px;background:var(--line2);margin:0 auto 20px"></div>
    <div style="display:flex;align-items:center;gap:7px;margin-bottom:8px">
      <span style="width:5px;height:5px;border-radius:99px;background:var(--gold-dot)"></span>
      <span style="font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--gold);font-weight:600">Premium</span>
    </div>
    <h2 style="margin:0 0 8px;font-size:26px;font-weight:600;line-height:1.15;letter-spacing:-0.03em"><!--b:t.payTitle--><?= e($v['t']['payTitle']) ?></h2>
    <p style="margin:0 0 20px;font-size:14.5px;line-height:1.55;color:var(--ink2)"><!--b:t.payIntro--><?= e($v['t']['payIntro']) ?></p>
    <div style="display:grid;gap:11px;margin-bottom:22px">
      <!--list:payFeatures-1--><?php foreach ($v['payFeatures'] as $p): ?><!--t-->
        <div style="display:flex;align-items:flex-start;gap:11px">
          <span style="font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--accent);font-weight:600;padding-top:3px;flex:none"><?= e($p['n']) ?></span>
          <span style="flex:1;min-width:0">
            <span style="display:block;font-size:14.5px;font-weight:600;margin-bottom:1px"><?= e($p['t']) ?></span>
            <span style="display:block;font-size:13px;line-height:1.5;color:var(--ink2)"><?= e($p['d']) ?></span>
          </span>
        </div>
      <!--t--><?php endforeach; ?><!--/list:payFeatures-1-->
    </div>
    <button name="do" value="buy" style="width:100%;padding:15px;border-radius:12px;border:0;background:var(--accent);color:var(--accent-ink);font-size:15.5px;font-weight:600;cursor:pointer"><!--b:t.payBtn--><?= e($v['t']['payBtn']) ?></button>
    <button name="do" value="closePay" style="width:100%;margin-top:7px;padding:13px;border-radius:12px;border:0;background:none;font-size:14px;font-weight:500;color:var(--ink2);cursor:pointer"><!--b:t.payDecline--><?= e($v['t']['payDecline']) ?></button>
    <p style="margin:13px 0 0;text-align:center;font-size:11.5px;line-height:1.5;color:var(--ink3)"><!--b:t.payDemo--><?= e($v['t']['payDemo']) ?></p>
  </div>
</div>
<!--t--><?php endif; ?><!--t-->

</div>
