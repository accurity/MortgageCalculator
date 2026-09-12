<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit Hypotheek\Design\ViewModel */
?>
<!-- lasten -->
  <!--t--><?php if ($v['is']['kLasten']): ?><!--t-->
  <div style="animation:stepIn .3s ease both">
    <h1 style="font-weight:600;font-size:33px;line-height:1.12;letter-spacing:-0.033em;margin:0 0 10px"><!--b:t.costsTitle--><?= e($v['t']['costsTitle']) ?></h1>
    <p style="margin:0 0 20px;font-size:15.5px;line-height:1.55;color:var(--ink2)"><!--b:t.costsIntro--><?= e($v['t']['costsIntro']) ?></p>
    <div style="display:flex;flex-wrap:wrap;gap:7px;margin-bottom:18px">
      <!--list:costSuggest-1--><?php foreach ($v['costSuggest'] as $c): ?><!--t-->
        <button name="do" value="cost.add:<?= e($c['label']) ?>" style="padding:9px 14px;border-radius:999px;border:1px solid var(--line2);background:var(--surface);font-size:13px;font-weight:500;cursor:pointer;color:var(--ink2)">+ <!--t--><?= e($c['label']) ?></button>
      <!--t--><?php endforeach; ?><!--/list:costSuggest-1-->
    </div>
    <div style="display:grid;gap:8px">
      <!--list:costs-1--><?php foreach ($v['costs'] as $c): ?><!--t-->
        <div style="display:flex;align-items:center;gap:6px;background:var(--surface);border:1.5px solid var(--line);border-radius:12px;padding:3px 5px 3px 14px">
          <input type="text" value="<?= e($c['label']) ?>" name="costs[<?= (int)$c['id'] ?>][label]" style="flex:1;min-width:0;border:0;outline:none;background:transparent;font-size:14.5px;font-weight:500;padding:11px 0">
          <span style="font-size:14px;color:var(--ink3)">€</span>
          <input type="text" inputmode="numeric" value="<?= e($c['amount']) ?>" name="costs[<?= (int)$c['id'] ?>][amount]" style="width:70px;border:0;outline:none;background:transparent;font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:500;padding:11px 0;text-align:right">
          <button name="do" value="cost.remove:<?= e($c['id']) ?>" style="width:32px;height:32px;border-radius:9px;border:0;background:var(--sunk);color:var(--ink3);font-size:16px;line-height:1;cursor:pointer;flex:none">×</button>
        </div>
      <!--t--><?php endforeach; ?><!--/list:costs-1-->
    </div>
    <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:baseline;gap:12px;padding:16px;border-radius:12px;background:var(--surface2);border:1px solid var(--line)">
      <span style="font-size:14px;color:var(--ink2)"><!--b:t.costsTotal--><?= e($v['t']['costsTotal']) ?></span>
      <span style="font-family:'JetBrains Mono',monospace;font-size:22px;font-weight:500;letter-spacing:-0.02em">€ <!--b:f.costTotal--><?= e($v['f']['costTotal']) ?></span>
    </div>
  </div>
  <!--t--><?php endif; ?><!--t-->

</div>
