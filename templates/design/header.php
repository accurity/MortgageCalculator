<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit Hypotheek\Design\ViewModel */
?>
<div data-theme="<?= e($v['theme']) ?>" style="min-height:100vh;display:flex;flex-direction:column;background:var(--bg)" data-b-data-theme="{theme}">

<header style="position:sticky;top:0;z-index:30;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding:11px 16px;background:var(--bg);border-bottom:1px solid var(--line)">
  <div style="display:flex;align-items:center;gap:11px;min-width:0">
    <img src="assets/img/accurity-logo.png" alt="Accurity" style="height:26px;width:auto;display:block;border-radius:3px;flex:none">
    <span style="font-size:9.5px;color:var(--ink3);letter-spacing:0.16em;text-transform:uppercase;white-space:nowrap"><!--b:t.tagline--><?= e($v['t']['tagline']) ?></span>
  </div>
  <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:7px">
    <!--t--><?php if ($v['is']['calc']): ?><!--t-->
      <div style="display:flex;background:var(--sunk);border-radius:9px;padding:2px">
        <button name="do" value="modeSimple" style="padding:7px 12px;border:0;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;background:<?= e($v['st']['simpleBg']) ?>;color:<?= e($v['st']['simpleInk']) ?>" data-b-style="padding:7px 12px;border:0;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;background:{st.simpleBg};color:{st.simpleInk}"><!--b:t.navOverview--><?= e($v['t']['navOverview']) ?></button>
        <button name="do" value="modeAdv" style="padding:7px 12px;border:0;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;background:<?= e($v['st']['advBg']) ?>;color:<?= e($v['st']['advInk']) ?>" data-b-style="padding:7px 12px;border:0;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;background:{st.advBg};color:{st.advInk}"><!--b:t.navAdvanced--><?= e($v['t']['navAdvanced']) ?></button>
      </div>
    <!--t--><?php endif; ?><!--t-->
    <div style="display:flex;background:var(--sunk);border-radius:9px;padding:2px">
      <button name="do" value="langNl" style="padding:7px 9px;border:0;border-radius:7px;font-size:11.5px;font-weight:600;cursor:pointer;background:<?= e($v['st']['nlBg']) ?>;color:<?= e($v['st']['nlInk']) ?>" data-b-style="padding:7px 9px;border:0;border-radius:7px;font-size:11.5px;font-weight:600;cursor:pointer;background:{st.nlBg};color:{st.nlInk}">NL</button>
      <button name="do" value="langEn" style="padding:7px 9px;border:0;border-radius:7px;font-size:11.5px;font-weight:600;cursor:pointer;background:<?= e($v['st']['enBg']) ?>;color:<?= e($v['st']['enInk']) ?>" data-b-style="padding:7px 9px;border:0;border-radius:7px;font-size:11.5px;font-weight:600;cursor:pointer;background:{st.enBg};color:{st.enInk}">EN</button>
    </div>
    <button name="do" value="theme" title="<?= e($v['t']['themeTitle']) ?>" style="width:34px;height:34px;border-radius:9px;border:1px solid var(--line);background:var(--surface);cursor:pointer;display:flex;align-items:center;justify-content:center;flex:none" data-b-title="{t.themeTitle}">
      <span style="width:13px;height:13px;border-radius:99px;border:2px solid var(--ink2);background:<?= e($v['st']['themeDot']) ?>;display:block" data-b-style="width:13px;height:13px;border-radius:99px;border:2px solid var(--ink2);background:{st.themeDot};display:block"></span>
    </button>
    <!--t--><?php if ($v['is']['premium']): ?><!--t-->
      <span style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:9px;background:var(--gold-soft);border:1px solid var(--gold-line);font-size:11.5px;font-weight:600;color:var(--gold);white-space:nowrap"><span style="width:5px;height:5px;border-radius:99px;background:var(--gold-dot)"></span>Premium</span>
    <!--t--><?php endif; ?><!--t-->
    <!--t--><?php if ($v['is']['free']): ?><!--t-->
      <button name="do" value="openPay" style="padding:8px 13px;border-radius:9px;border:1px solid var(--gold-line);background:var(--gold-soft);color:var(--gold);font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap">Premium</button>
    <!--t--><?php endif; ?><!--t-->
  </div>
</header>

<main style="flex:1;width:100%;max-width:1240px;margin:0 auto;padding:0 16px 40px">
