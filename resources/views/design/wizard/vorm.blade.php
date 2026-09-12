<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit App\Services\Mortgage\ViewModel */
?>
<!-- vorm -->
  <!--t--><?php if ($v['is']['kVorm']): ?><!--t-->
  <div style="animation:stepIn .3s ease both">
    <h1 style="font-weight:600;font-size:33px;line-height:1.12;letter-spacing:-0.033em;margin:0 0 10px"><!--b:f.vormTitle--><?= e($v['f']['vormTitle']) ?></h1>
    <p style="margin:0 0 24px;font-size:15.5px;line-height:1.55;color:var(--ink2)"><!--b:f.vormIntro--><?= e($v['f']['vormIntro']) ?></p>
    <div style="display:grid;gap:10px">
      <!--list:formOpts-1--><?php foreach ($v['formOpts'] as $o): ?><!--t-->
        <button name="do" value="form:<?= e($o['key']) ?>" style="text-align:left;padding:18px;border-radius:14px;border:1.5px solid <?= e($o['b']) ?>;background:<?= e($o['bg']) ?>;cursor:pointer">
          <span style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:5px">
            <span style="font-size:16.5px;font-weight:600;letter-spacing:-0.02em"><?= e($o['name']) ?></span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:500;white-space:nowrap">€ <!--t--><?= e($o['first']) ?></span>
          </span>
          <span style="display:block;font-size:13.5px;line-height:1.5;color:var(--ink2)"><?= e($o['desc']) ?></span>
          <!--t--><?php if ($o['flag']): ?><!--t-->
            <span style="display:block;font-size:12.5px;line-height:1.5;color:var(--warn);margin-top:7px"><?= e($o['flagText']) ?></span>
          <!--t--><?php endif; ?><!--t-->
        </button>
      <!--t--><?php endforeach; ?><!--/list:formOpts-1-->
    </div>
  </div>
  <!--t--><?php endif; ?><!--t-->
