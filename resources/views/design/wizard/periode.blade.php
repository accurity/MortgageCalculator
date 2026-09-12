<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit App\Services\Mortgage\ViewModel */
?>
<!-- periode -->
  <!--t--><?php if ($v['is']['kPeriode']): ?><!--t-->
  <div style="animation:stepIn .3s ease both">
    <h1 style="font-weight:600;font-size:33px;line-height:1.12;letter-spacing:-0.033em;margin:0 0 10px"><!--b:t.periodTitle--><?= e($v['t']['periodTitle']) ?></h1>
    <p style="margin:0 0 24px;font-size:15.5px;line-height:1.55;color:var(--ink2)"><!--b:t.periodIntro--><?= e($v['t']['periodIntro']) ?></p>

    <div style="padding:20px;border-radius:14px;background:var(--surface);border:1px solid var(--line);margin-bottom:10px">
      <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:2px">
        <span style="font-size:12.5px;font-weight:600;color:var(--ink2)"><!--b:t.term--><?= e($v['t']['term']) ?></span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:27px;font-weight:500;letter-spacing:-0.03em"><!--b:f.termY--><?= e($v['f']['termY']) ?><!--t--> <!--b:t.years--><?= e($v['t']['years']) ?></span>
      </div>
      <span style="display:block;font-size:12.5px;color:var(--ink3);margin-bottom:10px"><!--b:f.termNote--><?= e($v['f']['termNote']) ?></span>
      <input type="range" min="5" max="30" step="1" value="<?= e($v['n']['termY']) ?>" name="termY" style="width:100%;display:block" data-b-value="{n.termY}">
    </div>

    <div style="padding:20px;border-radius:14px;background:var(--surface);border:1px solid var(--line)">
      <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:12px">
        <span style="font-size:12.5px;font-weight:600;color:var(--ink2)"><!--b:t.fixedPeriod--><?= e($v['t']['fixedPeriod']) ?></span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:27px;font-weight:500;letter-spacing:-0.03em"><!--b:f.fixedY--><?= e($v['f']['fixedY']) ?><!--t--> <!--b:t.years--><?= e($v['t']['years']) ?></span>
      </div>
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:6px">
        <!--list:fixOpts-1--><?php foreach ($v['fixOpts'] as $o): ?><!--t-->
          <button name="do" value="fixed:<?= e($o['y']) ?>" style="padding:13px 2px;border-radius:11px;border:1.5px solid <?= e($o['b']) ?>;background:<?= e($o['bg']) ?>;cursor:pointer">
            <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:600"><?= e($o['y']) ?></span>
            <span style="display:block;font-size:11px;color:var(--ink3);margin-top:2px"><?= e($o['rate']) ?><!--t-->%</span>
          </button>
        <!--t--><?php endforeach; ?><!--/list:fixOpts-1-->
      </div>
      <p style="margin:14px 0 0;font-size:13px;line-height:1.55;color:var(--ink2)"><!--b:f.fixedNote--><?= e($v['f']['fixedNote']) ?></p>
    </div>
  </div>
  <!--t--><?php endif; ?><!--t-->
