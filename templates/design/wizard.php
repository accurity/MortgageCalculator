<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit Hypotheek\Design\ViewModel */
?>
<!-- ══════════════ WIZARD ══════════════ -->
<!--t--><?php if ($v['is']['wizard']): ?><!--t-->
<div style="max-width:600px;margin:0 auto;padding:26px 0 140px">

  <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:10px">
    <span style="font-size:10.5px;letter-spacing:0.14em;text-transform:uppercase;color:var(--ink3);font-weight:600"><!--b:f.stepNo--><?= e($v['f']['stepNo']) ?><!--t--> / <!--b:f.stepTotal--><?= e($v['f']['stepTotal']) ?><!--t--> · <!--b:f.stepName--><?= e($v['f']['stepName']) ?></span>
    <button name="do" value="skipWizard" style="background:none;border:0;padding:0;font-size:12px;color:var(--ink2);cursor:pointer;text-decoration:underline;text-underline-offset:3px"><!--b:t.skipWizard--><?= e($v['t']['skipWizard']) ?></button>
  </div>
  <div style="display:flex;gap:3px;margin-bottom:34px">
    <!--list:stepTicks-1--><?php foreach ($v['stepTicks'] as $tk): ?><!--t-->
      <div style="flex:1;height:3px;border-radius:2px;background:<?= e($tk['c']) ?>;transition:background .3s ease"></div>
    <!--t--><?php endforeach; ?><!--/list:stepTicks-1-->
  </div>

<?php require __DIR__ . '/wizard/start.php'; ?>
<?php require __DIR__ . '/wizard/bedrag.php'; ?>
<?php require __DIR__ . '/wizard/huidig.php'; ?>
<?php require __DIR__ . '/wizard/periode.php'; ?>
<?php require __DIR__ . '/wizard/rente.php'; ?>
<?php require __DIR__ . '/wizard/vorm.php'; ?>
<?php require __DIR__ . '/wizard/io.php'; ?>
<?php require __DIR__ . '/wizard/inkomen.php'; ?>
<?php require __DIR__ . '/wizard/lasten.php'; ?>

<div style="position:fixed;left:0;right:0;bottom:0;z-index:40;padding:12px 16px calc(12px + env(safe-area-inset-bottom));background:var(--bg);border-top:1px solid var(--line)">
  <div style="max-width:600px;margin:0 auto;display:flex;gap:8px">
    <!--t--><?php if ($v['is']['canBack']): ?><!--t-->
      <button name="do" value="back" style="padding:15px 20px;border-radius:12px;border:1.5px solid var(--line2);background:var(--surface);font-size:15.5px;font-weight:600;cursor:pointer"><!--b:t.back--><?= e($v['t']['back']) ?></button>
    <!--t--><?php endif; ?><!--t-->
    <button name="do" value="next" style="flex:1;padding:15px 20px;border-radius:12px;border:0;background:var(--accent);color:var(--accent-ink);font-size:15.5px;font-weight:600;cursor:pointer"><!--b:f.nextLabel--><?= e($v['f']['nextLabel']) ?></button>
  </div>
</div>
<!--t--><?php endif; ?><!--t-->
