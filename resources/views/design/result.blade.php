<?php

declare(strict_types=1);

/** @var array<string, mixed> $v Viewmodel uit App\Services\Mortgage\ViewModel */
?>
<!-- ══════════════ RESULTAAT ══════════════ -->
<!--t--><?php if ($v['is']['calc']): ?><!--t-->
<div style="display:flex;flex-wrap:wrap;flex-direction:row-reverse;align-items:flex-start;gap:20px;padding-top:20px">

  <section style="flex:2.2 1 420px;min-width:0;display:flex;flex-direction:column;gap:14px">

    <div style="padding:26px;border-radius:16px;background:var(--hero-bg);color:var(--hero-ink)">
      <span style="display:block;font-size:10.5px;letter-spacing:0.15em;text-transform:uppercase;color:var(--hero-ink2);font-weight:600;margin-bottom:12px"><!--b:f.heroLabel--><?= e($v['f']['heroLabel']) ?></span>
      <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:54px;line-height:1;font-weight:500;letter-spacing:-0.05em;margin-bottom:10px">€ <!--b:f.heroAmount--><?= e($v['f']['heroAmount']) ?></span>
      <span style="display:block;font-size:14px;line-height:1.5;color:var(--hero-ink2)"><!--b:f.heroSub--><?= e($v['f']['heroSub']) ?></span>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:18px">
        <!--list:heroTags-1--><?php foreach ($v['heroTags'] as $tg): ?><!--t-->
          <span style="padding:7px 12px;border-radius:8px;background:var(--hero-chip);font-size:12px"><?= e($tg['v']) ?></span>
        <!--t--><?php endforeach; ?><!--/list:heroTags-1-->
      </div>
    </div>

    <!--t--><?php if ($v['is']['renew']): ?><!--t-->
    <div style="padding:22px;border-radius:16px;background:var(--surface);border:1px solid var(--line)">
      <h2 style="margin:0 0 3px;font-size:20px;font-weight:600;letter-spacing:-0.025em"><!--b:t.nowThenTitle--><?= e($v['t']['nowThenTitle']) ?></h2>
      <p style="margin:0 0 18px;font-size:13.5px;line-height:1.5;color:var(--ink2)"><!--b:t.nowThenIntro--><?= e($v['t']['nowThenIntro']) ?></p>
      <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:stretch">
        <div style="flex:1 1 130px;min-width:0;padding:16px;border-radius:12px;background:var(--surface2);border:1px solid var(--line)">
          <span style="display:block;font-size:10.5px;letter-spacing:0.13em;text-transform:uppercase;color:var(--ink3);font-weight:600;margin-bottom:7px"><!--b:t.now--><?= e($v['t']['now']) ?></span>
          <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:25px;font-weight:500;letter-spacing:-0.03em">€ <!--b:f.curPayment--><?= e($v['f']['curPayment']) ?></span>
          <span style="display:block;font-size:12.5px;color:var(--ink3);margin-top:4px"><!--b:f.curSummary--><?= e($v['f']['curSummary']) ?></span>
        </div>
        <div style="flex:1 1 130px;min-width:0;padding:16px;border-radius:12px;background:var(--accent-soft);border:1px solid var(--accent-line)">
          <span style="display:block;font-size:10.5px;letter-spacing:0.13em;text-transform:uppercase;color:var(--ink3);font-weight:600;margin-bottom:7px"><!--b:t.then--><?= e($v['t']['then']) ?></span>
          <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:25px;font-weight:500;letter-spacing:-0.03em">€ <!--b:f.grossMonthly--><?= e($v['f']['grossMonthly']) ?></span>
          <span style="display:block;font-size:12.5px;color:var(--ink3);margin-top:4px"><!--b:f.newSummary--><?= e($v['f']['newSummary']) ?></span>
        </div>
        <div style="flex:1 1 130px;min-width:0;padding:16px;border-radius:12px;background:<?= e($v['st']['deltaBg']) ?>;border:1px solid <?= e($v['st']['deltaLine']) ?>" data-b-style="flex:1 1 130px;min-width:0;padding:16px;border-radius:12px;background:{st.deltaBg};border:1px solid {st.deltaLine}">
          <span style="display:block;font-size:10.5px;letter-spacing:0.13em;text-transform:uppercase;color:var(--ink3);font-weight:600;margin-bottom:7px"><!--b:t.difference--><?= e($v['t']['difference']) ?></span>
          <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:25px;font-weight:500;letter-spacing:-0.03em;color:<?= e($v['st']['deltaInk']) ?>" data-b-style="display:block;font-family:'JetBrains Mono',monospace;font-size:25px;font-weight:500;letter-spacing:-0.03em;color:{st.deltaInk}"><!--b:f.deltaValue--><?= e($v['f']['deltaValue']) ?></span>
          <span style="display:block;font-size:12.5px;color:var(--ink3);margin-top:4px"><!--b:t.perMonthGross--><?= e($v['t']['perMonthGross']) ?></span>
        </div>
      </div>
    </div>
    <!--t--><?php endif; ?><!--t-->

    <div style="padding:22px;border-radius:16px;background:var(--surface);border:1px solid var(--line)">
      <h2 style="margin:0 0 3px;font-size:20px;font-weight:600;letter-spacing:-0.025em"><!--b:t.splitTitle--><?= e($v['t']['splitTitle']) ?></h2>
      <p style="margin:0 0 16px;font-size:13.5px;line-height:1.5;color:var(--ink2)"><!--b:t.splitIntro--><?= e($v['t']['splitIntro']) ?></p>
      <div style="display:flex;height:10px;border-radius:3px;overflow:hidden;margin-bottom:16px;background:var(--sunk);gap:2px">
        <!--list:splitBars-1--><?php foreach ($v['splitBars'] as $b): ?><!--t-->
          <div style="height:100%;width:<?= e($b['w']) ?>;background:<?= e($b['c']) ?>"></div>
        <!--t--><?php endforeach; ?><!--/list:splitBars-1-->
      </div>
      <!--list:split-1--><?php foreach ($v['split'] as $r): ?><!--t-->
        <div style="display:flex;align-items:baseline;gap:11px;padding:10px 0;border-bottom:1px solid var(--line)">
          <span style="width:8px;height:8px;border-radius:2px;background:<?= e($r['c']) ?>;flex:none"></span>
          <span style="flex:1;min-width:0;font-size:14px;color:var(--ink2)"><?= e($r['l']) ?></span>
          <span style="font-family:'JetBrains Mono',monospace;font-size:14.5px;font-weight:500;white-space:nowrap"><?= e($r['v']) ?></span>
        </div>
      <!--t--><?php endforeach; ?><!--/list:split-1-->
      <div style="display:flex;justify-content:space-between;align-items:baseline;gap:14px;padding:16px 0 0">
        <span style="font-size:15px;font-weight:600"><!--b:f.totalLabel--><?= e($v['f']['totalLabel']) ?></span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:26px;font-weight:500;letter-spacing:-0.035em">€ <!--b:f.heroAmount--><?= e($v['f']['heroAmount']) ?></span>
      </div>
    </div>

    <div style="padding:22px;border-radius:16px;background:var(--surface);border:1px solid var(--line)">
      <div style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:8px;margin-bottom:3px">
        <h2 style="margin:0;font-size:20px;font-weight:600;letter-spacing:-0.025em"><!--b:t.balanceTitle--><?= e($v['t']['balanceTitle']) ?></h2>
        <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--ink3)"><!--b:f.chartNote--><?= e($v['f']['chartNote']) ?></span>
      </div>
      <p style="margin:0 0 18px;font-size:13.5px;line-height:1.5;color:var(--ink2)"><!--b:t.balanceIntro--><?= e($v['t']['balanceIntro']) ?></p>
      <div style="display:flex;align-items:flex-end;gap:2px;height:140px;margin-bottom:8px">
        <!--list:bars-1--><?php foreach ($v['bars'] as $b): ?><!--t-->
          <div title="<?= e($b['title']) ?>" style="flex:1;min-width:0;border-radius:2px 2px 0 0;background:<?= e($b['c']) ?>;height:<?= e($b['h']) ?>;transition:height .35s cubic-bezier(.4,0,.2,1)"></div>
        <!--t--><?php endforeach; ?><!--/list:bars-1-->
      </div>
      <div style="display:flex;justify-content:space-between;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink3);margin-bottom:14px"><span><!--b:t.chartNow--><?= e($v['t']['chartNow']) ?></span><span><!--b:f.chartEnd--><?= e($v['f']['chartEnd']) ?></span></div>
      <div style="display:flex;flex-wrap:wrap;gap:14px">
        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--ink2)"><span style="width:8px;height:8px;border-radius:2px;background:var(--bar1)"></span><!--b:t.legendFixed--><?= e($v['t']['legendFixed']) ?></span>
        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--ink2)"><span style="width:8px;height:8px;border-radius:2px;background:var(--bar2)"></span><!--b:t.legendAfter--><?= e($v['t']['legendAfter']) ?></span>
      </div>
      <!--t--><?php if ($v['is']['hasResidual']): ?><!--t-->
        <div style="margin-top:16px;padding:14px;border-radius:12px;background:var(--warn-soft);border:1px solid var(--warn-line);font-size:13px;line-height:1.55;color:var(--warn)"><!--b:f.residualNote--><?= e($v['f']['residualNote']) ?></div>
      <!--t--><?php endif; ?><!--t-->
    </div>

    <div style="padding:22px;border-radius:16px;background:var(--surface);border:1px solid var(--line)">
      <div style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:10px;margin-bottom:14px">
        <h2 style="margin:0;font-size:20px;font-weight:600;letter-spacing:-0.025em"><!--b:t.yearTableTitle--><?= e($v['t']['yearTableTitle']) ?></h2>
        <!--t--><?php if ($v['is']['premium']): ?><!--t-->
          <button name="do" value="csv" style="padding:8px 13px;border-radius:9px;border:1px solid var(--line2);background:var(--surface);font-size:12px;font-weight:600;color:var(--ink2);cursor:pointer">CSV</button>
        <!--t--><?php endif; ?><!--t-->
      </div>
      <div style="overflow-x:auto;-webkit-overflow-scrolling:touch">
        <div style="min-width:420px">
          <div style="display:grid;grid-template-columns:40px 1fr 1fr 1.15fr 1fr;gap:8px;padding-bottom:9px;border-bottom:1px solid var(--line2);font-size:10px;letter-spacing:0.11em;text-transform:uppercase;color:var(--ink3);font-weight:600">
            <span><!--b:t.colYear--><?= e($v['t']['colYear']) ?></span><span style="text-align:right"><!--b:t.colInterest--><?= e($v['t']['colInterest']) ?></span><span style="text-align:right"><!--b:t.colPrincipal--><?= e($v['t']['colPrincipal']) ?></span><span style="text-align:right"><!--b:t.colBalance--><?= e($v['t']['colBalance']) ?></span><span style="text-align:right"><!--b:t.colMonthly--><?= e($v['t']['colMonthly']) ?></span>
          </div>
          <!--list:years-1--><?php foreach ($v['years'] as $y): ?><!--t-->
            <div style="display:grid;grid-template-columns:40px 1fr 1fr 1.15fr 1fr;gap:8px;padding:9px 0;border-bottom:1px solid var(--line);font-family:'JetBrains Mono',monospace;font-size:12.5px">
              <span style="color:<?= e($y['c']) ?>"><?= e($y['y']) ?></span>
              <span style="text-align:right;color:var(--ink2)"><?= e($y['r']) ?></span>
              <span style="text-align:right;color:var(--ink2)"><?= e($y['a']) ?></span>
              <span style="text-align:right;font-weight:600"><?= e($y['s']) ?></span>
              <span style="text-align:right;color:var(--ink2)"><?= e($y['m']) ?></span>
            </div>
          <!--t--><?php endforeach; ?><!--/list:years-1-->
        </div>
      </div>
      <!--t--><?php if ($v['is']['free']): ?><!--t-->
        <button name="do" value="openPay" style="display:flex;align-items:center;gap:10px;width:100%;margin-top:14px;padding:14px;border-radius:12px;border:1px solid var(--gold-line);background:var(--gold-soft);cursor:pointer;text-align:left">
          <span style="width:6px;height:6px;border-radius:99px;background:var(--gold-dot);flex:none"></span>
          <span style="flex:1;min-width:0;font-size:13.5px;line-height:1.5;color:var(--gold)"><!--b:f.tableUpsell--><?= e($v['f']['tableUpsell']) ?></span>
        </button>
      <!--t--><?php endif; ?><!--t-->
    </div>

    <div style="padding:22px;border-radius:16px;background:var(--surface);border:1px solid var(--line)">
      <h2 style="margin:0 0 3px;font-size:20px;font-weight:600;letter-spacing:-0.025em"><!--b:t.taxTitle--><?= e($v['t']['taxTitle']) ?></h2>
      <p style="margin:0 0 16px;font-size:13.5px;line-height:1.5;color:var(--ink2)"><!--b:f.taxIntro--><?= e($v['f']['taxIntro']) ?></p>
      <!--list:tax-1--><?php foreach ($v['tax'] as $r): ?><!--t-->
        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:14px;padding:10px 0;border-bottom:1px solid var(--line)">
          <span style="flex:1;min-width:0;font-size:14px;color:var(--ink2)"><?= e($r['l']) ?></span>
          <span style="font-family:'JetBrains Mono',monospace;font-size:14.5px;font-weight:500;white-space:nowrap"><?= e($r['v']) ?></span>
        </div>
      <!--t--><?php endforeach; ?><!--/list:tax-1-->
      <div style="display:flex;justify-content:space-between;align-items:baseline;gap:14px;padding:16px 0 0">
        <span style="font-size:15px;font-weight:600"><!--b:f.taxResultLabel--><?= e($v['f']['taxResultLabel']) ?></span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:24px;font-weight:500;letter-spacing:-0.035em">€ <!--b:f.taxResult--><?= e($v['f']['taxResult']) ?></span>
      </div>
    </div>

    <!--t--><?php if (!empty($v['lenders'])): ?><!--t-->
    <div style="padding:22px;border-radius:16px;background:var(--surface);border:1px solid var(--line)">
      <h2 style="margin:0 0 3px;font-size:20px;font-weight:600;letter-spacing:-0.025em"><!--b:t.lendersTitle--><?= e($v['t']['lendersTitle']) ?></h2>
      <p style="margin:0 0 16px;font-size:13.5px;line-height:1.5;color:var(--ink2)"><!--b:f.ratesNote--><?= e($v['f']['ratesNote']) ?></p>
      <!--list:lenders-1--><?php foreach ($v['lenders'] as $l): ?><!--t-->
        <button name="do" value="lender:<?= e($l['index']) ?>" style="display:flex;align-items:center;gap:12px;width:100%;text-align:left;padding:13px;margin-bottom:7px;border-radius:12px;border:1.5px solid <?= e($l['b']) ?>;background:<?= e($l['bg']) ?>;cursor:pointer">
          <?php if (!empty($l['logo'])): ?>
            <img src="<?= e($l['logo']) ?>" alt="" style="width:32px;height:32px;border-radius:8px;object-fit:contain;flex:none;background:var(--surface2)">
          <?php else: ?>
            <span style="width:32px;height:32px;border-radius:8px;background:var(--surface2);color:var(--ink3);font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:center;flex:none"><?= e(mb_substr($l['name'], 0, 1)) ?></span>
          <?php endif; ?>
          <span style="flex:1;min-width:0">
            <span style="display:block;font-size:14.5px;font-weight:600"><?= e($l['name']) ?></span>
            <span style="display:block;font-size:12px;color:var(--ink3);margin-top:1px"><?= e($l['note']) ?></span>
          </span>
          <span style="text-align:right;white-space:nowrap">
            <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:600"><?= e($l['rate']) ?><!--t-->%</span>
            <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:11.5px;color:<?= e($l['deltaC']) ?>"><?= e($l['delta']) ?></span>
          </span>
        </button>
      <!--t--><?php endforeach; ?><!--/list:lenders-1-->
    </div>
    <!--t--><?php endif; ?><!--t-->

    <!--t--><?php if ($v['is']['premium']): ?><!--t-->
    <div style="padding:22px;border-radius:16px;background:var(--surface);border:1px solid var(--gold-line)">
      <div style="display:flex;align-items:center;gap:7px;margin-bottom:4px">
        <span style="width:5px;height:5px;border-radius:99px;background:var(--gold-dot)"></span>
        <span style="font-size:10px;letter-spacing:0.15em;text-transform:uppercase;color:var(--gold);font-weight:600">Premium</span>
      </div>
      <h2 style="margin:0 0 3px;font-size:20px;font-weight:600;letter-spacing:-0.025em"><!--b:t.scenariosTitle--><?= e($v['t']['scenariosTitle']) ?></h2>
      <p style="margin:0 0 16px;font-size:13.5px;line-height:1.5;color:var(--ink2)"><!--b:t.scenariosIntro--><?= e($v['t']['scenariosIntro']) ?></p>
      <div style="display:grid;gap:8px">
        <!--list:scenarios-1--><?php foreach ($v['scenarios'] as $s): ?><!--t-->
          <div style="display:flex;align-items:center;gap:14px;padding:14px;border-radius:12px;background:var(--surface2);border:1px solid var(--line)">
            <span style="flex:1;min-width:0">
              <span style="display:block;font-size:14px;font-weight:600"><?= e($s['l']) ?></span>
              <span style="display:block;font-size:12px;color:var(--ink3);margin-top:1px"><?= e($s['note']) ?></span>
            </span>
            <span style="text-align:right;white-space:nowrap">
              <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:600">€ <!--t--><?= e($s['v']) ?></span>
              <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:11.5px;color:<?= e($s['dc']) ?>"><?= e($s['d']) ?></span>
            </span>
          </div>
        <!--t--><?php endforeach; ?><!--/list:scenarios-1-->
      </div>
      <button name="do" value="pdf" style="width:100%;margin-top:14px;padding:14px;border-radius:12px;border:0;background:var(--accent);color:var(--accent-ink);font-size:14.5px;font-weight:600;cursor:pointer"><!--b:t.reportBtn--><?= e($v['t']['reportBtn']) ?></button>
    </div>
    <!--t--><?php endif; ?><!--t-->

    <!--t--><?php if ($v['is']['free']): ?><!--t-->
    <div style="padding:22px;border-radius:16px;background:var(--surface);border:1px dashed var(--line2)">
      <h2 style="margin:0 0 4px;font-size:20px;font-weight:600;letter-spacing:-0.025em"><!--b:t.lockedTitle--><?= e($v['t']['lockedTitle']) ?></h2>
      <p style="margin:0 0 18px;font-size:13.5px;line-height:1.5;color:var(--ink2)"><!--b:t.lockedIntro--><?= e($v['t']['lockedIntro']) ?></p>
      <div style="display:grid;gap:8px">
        <!--list:locked-1--><?php foreach ($v['locked'] as $l): ?><!--t-->
          <div style="display:flex;align-items:flex-start;gap:12px;padding:14px;border-radius:12px;background:var(--surface2)">
            <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink3);font-weight:600;padding-top:2px;flex:none"><?= e($l['n']) ?></span>
            <span style="flex:1;min-width:0">
              <span style="display:block;font-size:14.5px;font-weight:600;margin-bottom:2px"><?= e($l['t']) ?></span>
              <span style="display:block;font-size:13px;line-height:1.5;color:var(--ink2)"><?= e($l['d']) ?></span>
            </span>
          </div>
        <!--t--><?php endforeach; ?><!--/list:locked-1-->
      </div>
      <button name="do" value="openPay" style="width:100%;margin-top:16px;padding:15px;border-radius:12px;border:0;background:var(--accent);color:var(--accent-ink);font-size:15px;font-weight:600;cursor:pointer"><!--b:t.unlockBtn--><?= e($v['t']['unlockBtn']) ?></button>
    </div>
    <!--t--><?php endif; ?><!--t-->

    <p style="margin:4px 2px 0;font-size:12px;line-height:1.6;color:var(--ink3)"><!--b:f.disclaimer--><?= e($v['f']['disclaimer']) ?></p>
  </section>

  <aside style="flex:1 1 300px;min-width:0;display:flex;flex-direction:column;gap:12px;position:sticky;top:72px">

    <!--t--><?php if ($v['is']['simple']): ?><!--t-->
    <div style="padding:20px;border-radius:16px;background:var(--surface);border:1px solid var(--line);display:flex;flex-direction:column;gap:17px">
      <div>
        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:10px;margin-bottom:4px">
          <span style="font-size:11.5px;font-weight:600;color:var(--ink2)"><!--b:t.loanAmount--><?= e($v['t']['loanAmount']) ?></span>
          <span style="font-family:'JetBrains Mono',monospace;font-size:19px;font-weight:500;letter-spacing:-0.025em">€ <!--b:f.loan--><?= e($v['f']['loan']) ?></span>
        </div>
        <input type="range" min="25000" max="1500000" step="5000" value="<?= e($v['n']['loan']) ?>" name="loan" style="width:100%;display:block" data-b-value="{n.loan}">
      </div>
      <div>
        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:10px;margin-bottom:4px">
          <span style="font-size:11.5px;font-weight:600;color:var(--ink2)"><!--b:t.rate--><?= e($v['t']['rate']) ?></span>
          <span style="font-family:'JetBrains Mono',monospace;font-size:19px;font-weight:500;letter-spacing:-0.025em"><!--b:f.rate--><?= e($v['f']['rate']) ?><!--t-->%</span>
        </div>
        <input type="range" min="50" max="800" step="5" value="<?= e($v['n']['rateSlider']) ?>" name="rateSlider" style="width:100%;display:block" data-b-value="{n.rateSlider}">
      </div>
      <div>
        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:10px;margin-bottom:4px">
          <span style="font-size:11.5px;font-weight:600;color:var(--ink2)"><!--b:t.term--><?= e($v['t']['term']) ?></span>
          <span style="font-family:'JetBrains Mono',monospace;font-size:19px;font-weight:500;letter-spacing:-0.025em"><!--b:f.termY--><?= e($v['f']['termY']) ?><!--t--> <!--b:t.yrShort--><?= e($v['t']['yrShort']) ?></span>
        </div>
        <input type="range" min="5" max="30" step="1" value="<?= e($v['n']['termY']) ?>" name="termY" style="width:100%;display:block" data-b-value="{n.termY}">
      </div>
      <div>
        <span style="display:block;font-size:11.5px;font-weight:600;color:var(--ink2);margin-bottom:7px"><!--b:t.fixedPeriod--><?= e($v['t']['fixedPeriod']) ?></span>
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:5px">
          <!--list:fixOpts-2--><?php foreach ($v['fixOpts'] as $o): ?><!--t-->
            <button name="do" value="fixed:<?= e($o['y']) ?>" style="padding:9px 2px;border-radius:9px;border:1.5px solid <?= e($o['b']) ?>;background:<?= e($o['bg']) ?>;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;cursor:pointer"><?= e($o['y']) ?></button>
          <!--t--><?php endforeach; ?><!--/list:fixOpts-2-->
        </div>
      </div>
      <div>
        <span style="display:block;font-size:11.5px;font-weight:600;color:var(--ink2);margin-bottom:7px"><!--b:t.repaymentType--><?= e($v['t']['repaymentType']) ?></span>
        <div style="display:grid;gap:5px">
          <!--list:formOpts-2--><?php foreach ($v['formOpts'] as $o): ?><!--t-->
            <button name="do" value="form:<?= e($o['key']) ?>" style="text-align:left;padding:11px 12px;border-radius:10px;border:1.5px solid <?= e($o['b']) ?>;background:<?= e($o['bg']) ?>;font-size:13.5px;font-weight:500;cursor:pointer"><?= e($o['name']) ?></button>
          <!--t--><?php endforeach; ?><!--/list:formOpts-2-->
        </div>
      </div>
      <div>
        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:10px;margin-bottom:4px">
          <span style="font-size:11.5px;font-weight:600;color:var(--ink2)"><!--b:t.ioPart--><?= e($v['t']['ioPart']) ?></span>
          <span style="font-family:'JetBrains Mono',monospace;font-size:19px;font-weight:500;letter-spacing:-0.025em">€ <!--b:f.io--><?= e($v['f']['io']) ?></span>
        </div>
        <input type="range" min="0" max="<?= e($v['n']['ioMax']) ?>" step="5000" value="<?= e($v['n']['io']) ?>" name="io" style="width:100%;display:block" data-b-max="{n.ioMax}" data-b-value="{n.io}">
      </div>
      <button name="do" value="modeAdv" style="padding:12px;border-radius:11px;border:1.5px solid var(--line2);background:var(--surface);font-size:14px;font-weight:600;cursor:pointer"><!--b:t.moreSettings--><?= e($v['t']['moreSettings']) ?></button>
    </div>
    <!--t--><?php endif; ?><!--t-->

    <!--t--><?php if ($v['is']['adv']): ?><!--t-->
    <div style="display:flex;flex-direction:column;gap:12px;max-height:calc(100vh - 96px);overflow-y:auto;overflow-x:hidden">

      <div style="padding:18px;border-radius:16px;background:var(--surface);border:1px solid var(--line)">
        <h3 style="margin:0 0 3px;font-size:17px;font-weight:600;letter-spacing:-0.02em"><!--b:t.partsTitle--><?= e($v['t']['partsTitle']) ?></h3>
        <p style="margin:0 0 13px;font-size:12.5px;line-height:1.5;color:var(--ink2)"><!--b:f.partsNote--><?= e($v['f']['partsNote']) ?></p>
        <div style="display:flex;flex-direction:column;gap:10px">
          <!--list:parts-1--><?php foreach ($v['parts'] as $p): ?><!--t-->
            <div style="padding:13px;border-radius:13px;background:var(--surface2);border:1px solid var(--line)">
              <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px">
                <span style="font-size:10px;letter-spacing:0.13em;text-transform:uppercase;color:var(--ink3);font-weight:600"><?= e($p['title']) ?></span>
                <!--t--><?php if ($p['canRemove']): ?><!--t-->
                  <button name="do" value="part.remove:<?= e($p['id']) ?>" style="width:26px;height:26px;border-radius:8px;border:0;background:var(--sunk);color:var(--ink3);font-size:14px;line-height:1;cursor:pointer">×</button>
                <!--t--><?php endif; ?><!--t-->
              </div>
              <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:4px;margin-bottom:10px">
                <!--list:p.forms-1--><?php foreach ($p['forms'] as $fo): ?><!--t-->
                  <button name="do" value="part.form:<?= e($p['id'] . ':' . $fo['key']) ?>" style="padding:8px 2px;border-radius:8px;border:1.5px solid <?= e($fo['b']) ?>;background:<?= e($fo['bg']) ?>;font-size:11px;font-weight:600;cursor:pointer"><?= e($fo['short']) ?></button>
                <!--t--><?php endforeach; ?><!--/list:p.forms-1-->
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px">
                <label style="display:block">
                  <span style="display:block;font-size:10.5px;font-weight:600;color:var(--ink3);margin-bottom:4px"><?= e($v['t']['principal']) ?></span>
                  <input type="text" inputmode="numeric" value="<?= e($p['sum']) ?>" name="parts[<?= (int)$p['id'] ?>][sum]" style="width:100%;border:1.5px solid var(--line2);border-radius:9px;background:var(--surface);outline:none;font-family:'JetBrains Mono',monospace;font-size:13.5px;font-weight:500;padding:9px 10px">
                </label>
                <label style="display:block">
                  <span style="display:block;font-size:10.5px;font-weight:600;color:var(--ink3);margin-bottom:4px"><?= e($v['t']['ratePct']) ?></span>
                  <input type="text" inputmode="decimal" value="<?= e($p['rate']) ?>" name="parts[<?= (int)$p['id'] ?>][rate]" style="width:100%;border:1.5px solid var(--line2);border-radius:9px;background:var(--surface);outline:none;font-family:'JetBrains Mono',monospace;font-size:13.5px;font-weight:500;padding:9px 10px">
                </label>
                <label style="display:block">
                  <span style="display:block;font-size:10.5px;font-weight:600;color:var(--ink3);margin-bottom:4px"><?= e($v['t']['termShort']) ?></span>
                  <input type="text" inputmode="numeric" value="<?= e($p['term']) ?>" name="parts[<?= (int)$p['id'] ?>][term]" style="width:100%;border:1.5px solid var(--line2);border-radius:9px;background:var(--surface);outline:none;font-family:'JetBrains Mono',monospace;font-size:13.5px;font-weight:500;padding:9px 10px">
                </label>
                <label style="display:block">
                  <span style="display:block;font-size:10.5px;font-weight:600;color:var(--ink3);margin-bottom:4px"><?= e($v['t']['elapsedShort']) ?></span>
                  <input type="text" inputmode="numeric" value="<?= e($p['elapsed']) ?>" name="parts[<?= (int)$p['id'] ?>][elapsed]" style="width:100%;border:1.5px solid var(--line2);border-radius:9px;background:var(--surface);outline:none;font-family:'JetBrains Mono',monospace;font-size:13.5px;font-weight:500;padding:9px 10px">
                </label>
              </div>
              <button name="do" value="part.ded:<?= e($p['id']) ?>" style="display:flex;align-items:center;gap:8px;width:100%;margin-top:10px;padding:0;border:0;background:none;cursor:pointer;text-align:left">
                <span style="width:17px;height:17px;border-radius:5px;border:1.5px solid <?= e($p['dedB']) ?>;background:<?= e($p['dedFill']) ?>;flex:none"></span>
                <span style="font-size:12.5px;color:var(--ink2)"><?= e($v['t']['deductible']) ?></span>
              </button>
              <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--line);display:flex;justify-content:space-between;gap:10px;font-size:12px">
                <span style="color:var(--ink3)"><?= e($v['t']['firstMonth']) ?></span>
                <span style="font-family:'JetBrains Mono',monospace;font-weight:600">€ <!--t--><?= e($p['first']) ?></span>
              </div>
            </div>
          <!--t--><?php endforeach; ?><!--/list:parts-1-->
        </div>
        <button name="do" value="addPart" style="width:100%;margin-top:11px;padding:11px;border-radius:11px;border:1.5px dashed var(--line2);background:none;font-size:13.5px;font-weight:600;color:var(--ink2);cursor:pointer"><!--b:t.addPart--><?= e($v['t']['addPart']) ?></button>
      </div>

      <div style="padding:18px;border-radius:16px;background:var(--surface);border:1px solid var(--line)">
        <h3 style="margin:0 0 3px;font-size:17px;font-weight:600;letter-spacing:-0.02em"><!--b:t.monthlyCosts--><?= e($v['t']['monthlyCosts']) ?></h3>
        <p style="margin:0 0 13px;font-size:12.5px;line-height:1.5;color:var(--ink2)"><!--b:t.monthlyCostsIntro--><?= e($v['t']['monthlyCostsIntro']) ?></p>
        <div style="display:grid;gap:7px">
          <!--list:costs-2--><?php foreach ($v['costs'] as $c): ?><!--t-->
            <div style="display:flex;align-items:center;gap:5px;background:var(--surface2);border:1px solid var(--line);border-radius:11px;padding:3px 5px 3px 11px">
              <input type="text" value="<?= e($c['label']) ?>" name="costs[<?= (int)$c['id'] ?>][label]" style="flex:1;min-width:0;border:0;outline:none;background:transparent;font-size:13px;font-weight:500;padding:9px 0">
              <input type="text" inputmode="numeric" value="<?= e($c['amount']) ?>" name="costs[<?= (int)$c['id'] ?>][amount]" style="width:56px;border:0;outline:none;background:transparent;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;padding:9px 0;text-align:right">
              <button name="do" value="cost.remove:<?= e($c['id']) ?>" style="width:28px;height:28px;border-radius:8px;border:0;background:var(--sunk);color:var(--ink3);font-size:14px;line-height:1;cursor:pointer;flex:none">×</button>
            </div>
          <!--t--><?php endforeach; ?><!--/list:costs-2-->
        </div>
        <button name="do" value="addCost" style="width:100%;margin-top:9px;padding:10px;border-radius:10px;border:1.5px dashed var(--line2);background:none;font-size:13px;font-weight:600;color:var(--ink2);cursor:pointer"><!--b:t.addCost--><?= e($v['t']['addCost']) ?></button>
      </div>

      <div style="padding:18px;border-radius:16px;background:var(--surface);border:1px solid var(--line)">
        <h3 style="margin:0 0 3px;font-size:17px;font-weight:600;letter-spacing:-0.02em"><!--b:t.taxTitle--><?= e($v['t']['taxTitle']) ?></h3>
        <p style="margin:0 0 13px;font-size:12.5px;line-height:1.5;color:var(--ink2)"><!--b:t.taxSideIntro--><?= e($v['t']['taxSideIntro']) ?></p>
        <div style="display:grid;gap:9px">
          <label style="display:block">
            <span style="display:block;font-size:10.5px;font-weight:600;color:var(--ink3);margin-bottom:4px"><!--b:t.incomeMain--><?= e($v['t']['incomeMain']) ?></span>
            <input type="text" inputmode="numeric" value="<?= e($v['f']['income']) ?>" name="income" placeholder="0" style="width:100%;border:1.5px solid var(--line2);border-radius:10px;background:var(--surface);outline:none;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:500;padding:10px 11px" data-b-value="{f.income}">
          </label>
          <label style="display:block">
            <span style="display:block;font-size:10.5px;font-weight:600;color:var(--ink3);margin-bottom:4px"><!--b:t.incomePartner--><?= e($v['t']['incomePartner']) ?></span>
            <input type="text" inputmode="numeric" value="<?= e($v['f']['income2']) ?>" name="income2" placeholder="0" style="width:100%;border:1.5px solid var(--line2);border-radius:10px;background:var(--surface);outline:none;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:500;padding:10px 11px" data-b-value="{f.income2}">
          </label>
          <label style="display:block">
            <span style="display:block;font-size:10.5px;font-weight:600;color:var(--ink3);margin-bottom:4px"><!--b:t.woz--><?= e($v['t']['woz']) ?></span>
            <input type="text" inputmode="numeric" value="<?= e($v['f']['woz']) ?>" name="woz" style="width:100%;border:1.5px solid var(--line2);border-radius:10px;background:var(--surface);outline:none;font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:500;padding:10px 11px" data-b-value="{f.woz}">
          </label>
        </div>
        <div style="margin-top:12px;padding-top:11px;border-top:1px solid var(--line);display:flex;justify-content:space-between;gap:10px;font-size:12.5px">
          <span style="color:var(--ink3)"><!--b:t.combined--><?= e($v['t']['combined']) ?></span>
          <span style="font-family:'JetBrains Mono',monospace;font-weight:600">€ <!--b:f.incomeTotal--><?= e($v['f']['incomeTotal']) ?></span>
        </div>
        <div style="margin-top:5px;display:flex;justify-content:space-between;gap:10px;font-size:12.5px">
          <span style="color:var(--ink3)"><!--b:t.marginalRate--><?= e($v['t']['marginalRate']) ?></span>
          <span style="font-family:'JetBrains Mono',monospace;font-weight:600"><!--b:f.marginal--><?= e($v['f']['marginal']) ?><!--t-->%</span>
        </div>
        <div style="margin-top:5px;display:flex;justify-content:space-between;gap:10px;font-size:12.5px">
          <span style="color:var(--ink3)"><!--b:t.capRate--><?= e($v['t']['capRate']) ?></span>
          <span style="font-family:'JetBrains Mono',monospace;font-weight:600"><!--b:f.capRate--><?= e($v['f']['capRate']) ?><!--t-->%</span>
        </div>
      </div>

      <button name="do" value="restart" style="padding:12px;border-radius:11px;border:1.5px solid var(--line2);background:var(--surface);font-size:14px;font-weight:600;color:var(--ink2);cursor:pointer"><!--b:t.restart--><?= e($v['t']['restart']) ?></button>
    </div>
    <!--t--><?php endif; ?><!--t-->

  </aside>
</div>
<!--t--><?php endif; ?><!--t-->
