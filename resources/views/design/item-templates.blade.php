<?php

declare(strict_types=1);

/**
 * Itemsjablonen voor de JS-laag, gegenereerd uit dezelfde ontwerpmarkup
 * als de PHP-templates. De browser vult ze met het viewmodel dat calc.js
 * uitrekent; zonder JavaScript doet dit bestand niets.
 */
?>
<script type="text/x-item" data-tpl="stepTicks-1" data-var="tk"><div style="flex:1;height:3px;border-radius:2px;background:{tk.c};transition:background .3s ease"></div></script>
<script type="text/x-item" data-tpl="curFormOpts-1" data-var="o"><button name="do" value="curForm:{o.key}" style="padding:13px 4px;border-radius:11px;border:1.5px solid {o.b};background:{o.bg};font-size:13px;font-weight:600;cursor:pointer">{o.short}</button></script>
<script type="text/x-item" data-tpl="fixOpts-1" data-var="o"><button name="do" value="fixed:{o.y}" style="padding:13px 2px;border-radius:11px;border:1.5px solid {o.b};background:{o.bg};cursor:pointer">
            <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:600">{o.y}</span>
            <span style="display:block;font-size:11px;color:var(--ink3);margin-top:2px">{o.rate}<!--t-->%</span>
          </button></script>
<script type="text/x-item" data-tpl="formOpts-1" data-var="o"><button name="do" value="form:{o.key}" style="text-align:left;padding:18px;border-radius:14px;border:1.5px solid {o.b};background:{o.bg};cursor:pointer">
          <span style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:5px">
            <span style="font-size:16.5px;font-weight:600;letter-spacing:-0.02em">{o.name}</span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:500;white-space:nowrap">€ <!--t-->{o.first}</span>
          </span>
          <span style="display:block;font-size:13.5px;line-height:1.5;color:var(--ink2)">{o.desc}</span>
          <!--if:o.flag-->
            <span style="display:block;font-size:12.5px;line-height:1.5;color:var(--warn);margin-top:7px">{o.flagText}</span>
          <!--/if-->
        </button></script>
<script type="text/x-item" data-tpl="costSuggest-1" data-var="c"><button name="do" value="cost.add:{c.label}" style="padding:9px 14px;border-radius:999px;border:1px solid var(--line2);background:var(--surface);font-size:13px;font-weight:500;cursor:pointer;color:var(--ink2)">+ <!--t-->{c.label}</button></script>
<script type="text/x-item" data-tpl="costs-1" data-var="c"><div style="display:flex;align-items:center;gap:6px;background:var(--surface);border:1.5px solid var(--line);border-radius:12px;padding:3px 5px 3px 14px">
          <input type="text" value="{c.label}" name="costs[{c.id}][label]" style="flex:1;min-width:0;border:0;outline:none;background:transparent;font-size:14.5px;font-weight:500;padding:11px 0">
          <span style="font-size:14px;color:var(--ink3)">€</span>
          <input type="text" inputmode="numeric" value="{c.amount}" name="costs[{c.id}][amount]" style="width:70px;border:0;outline:none;background:transparent;font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:500;padding:11px 0;text-align:right">
          <button name="do" value="cost.remove:{c.id}" style="width:32px;height:32px;border-radius:9px;border:0;background:var(--sunk);color:var(--ink3);font-size:16px;line-height:1;cursor:pointer;flex:none">×</button>
        </div></script>
<script type="text/x-item" data-tpl="heroTags-1" data-var="tg"><span style="padding:7px 12px;border-radius:8px;background:var(--hero-chip);font-size:12px">{tg.v}</span></script>
<script type="text/x-item" data-tpl="splitBars-1" data-var="b"><div style="height:100%;width:{b.w};background:{b.c}"></div></script>
<script type="text/x-item" data-tpl="split-1" data-var="r"><div style="display:flex;align-items:baseline;gap:11px;padding:10px 0;border-bottom:1px solid var(--line)">
          <span style="width:8px;height:8px;border-radius:2px;background:{r.c};flex:none"></span>
          <span style="flex:1;min-width:0;font-size:14px;color:var(--ink2)">{r.l}</span>
          <span style="font-family:'JetBrains Mono',monospace;font-size:14.5px;font-weight:500;white-space:nowrap">{r.v}</span>
        </div></script>
<script type="text/x-item" data-tpl="bars-1" data-var="b"><div title="{b.title}" style="flex:1;min-width:0;border-radius:2px 2px 0 0;background:{b.c};height:{b.h};transition:height .35s cubic-bezier(.4,0,.2,1)"></div></script>
<script type="text/x-item" data-tpl="years-1" data-var="y"><div style="display:grid;grid-template-columns:40px 1fr 1fr 1.15fr 1fr;gap:8px;padding:9px 0;border-bottom:1px solid var(--line);font-family:'JetBrains Mono',monospace;font-size:12.5px">
              <span style="color:{y.c}">{y.y}</span>
              <span style="text-align:right;color:var(--ink2)">{y.r}</span>
              <span style="text-align:right;color:var(--ink2)">{y.a}</span>
              <span style="text-align:right;font-weight:600">{y.s}</span>
              <span style="text-align:right;color:var(--ink2)">{y.m}</span>
            </div></script>
<script type="text/x-item" data-tpl="tax-1" data-var="r"><div style="display:flex;justify-content:space-between;align-items:baseline;gap:14px;padding:10px 0;border-bottom:1px solid var(--line)">
          <span style="flex:1;min-width:0;font-size:14px;color:var(--ink2)">{r.l}</span>
          <span style="font-family:'JetBrains Mono',monospace;font-size:14.5px;font-weight:500;white-space:nowrap">{r.v}</span>
        </div></script>
<script type="text/x-item" data-tpl="lenders-1" data-var="l"><button name="do" value="lender:{l.index}" style="display:flex;align-items:center;gap:12px;width:100%;text-align:left;padding:13px;margin-bottom:7px;border-radius:12px;border:1.5px solid {l.b};background:{l.bg};cursor:pointer">
          <!--if:l.logo--><img src="{l.logo}" alt="" style="width:32px;height:32px;border-radius:8px;object-fit:contain;flex:none;background:var(--surface2)"><!--/if-->
          <!--if:l.noLogo--><span style="width:32px;height:32px;border-radius:8px;background:var(--surface2);color:var(--ink3);font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:center;flex:none">{l.initial}</span><!--/if-->
          <span style="flex:1;min-width:0">
            <span style="display:block;font-size:14.5px;font-weight:600">{l.name}</span>
            <span style="display:block;font-size:12px;color:var(--ink3);margin-top:1px">{l.note}</span>
          </span>
          <span style="text-align:right;white-space:nowrap">
            <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:600">{l.rate}<!--t-->%</span>
            <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:11.5px;color:{l.deltaC}">{l.delta}</span>
          </span>
        </button></script>
<script type="text/x-item" data-tpl="scenarios-1" data-var="s"><div style="display:flex;align-items:center;gap:14px;padding:14px;border-radius:12px;background:var(--surface2);border:1px solid var(--line)">
            <span style="flex:1;min-width:0">
              <span style="display:block;font-size:14px;font-weight:600">{s.l}</span>
              <span style="display:block;font-size:12px;color:var(--ink3);margin-top:1px">{s.note}</span>
            </span>
            <span style="text-align:right;white-space:nowrap">
              <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:600">€ <!--t-->{s.v}</span>
              <span style="display:block;font-family:'JetBrains Mono',monospace;font-size:11.5px;color:{s.dc}">{s.d}</span>
            </span>
          </div></script>
<script type="text/x-item" data-tpl="locked-1" data-var="l"><div style="display:flex;align-items:flex-start;gap:12px;padding:14px;border-radius:12px;background:var(--surface2)">
            <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink3);font-weight:600;padding-top:2px;flex:none">{l.n}</span>
            <span style="flex:1;min-width:0">
              <span style="display:block;font-size:14.5px;font-weight:600;margin-bottom:2px">{l.t}</span>
              <span style="display:block;font-size:13px;line-height:1.5;color:var(--ink2)">{l.d}</span>
            </span>
          </div></script>
<script type="text/x-item" data-tpl="fixOpts-2" data-var="o"><button name="do" value="fixed:{o.y}" style="padding:9px 2px;border-radius:9px;border:1.5px solid {o.b};background:{o.bg};font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;cursor:pointer">{o.y}</button></script>
<script type="text/x-item" data-tpl="formOpts-2" data-var="o"><button name="do" value="form:{o.key}" style="text-align:left;padding:11px 12px;border-radius:10px;border:1.5px solid {o.b};background:{o.bg};font-size:13.5px;font-weight:500;cursor:pointer">{o.name}</button></script>
<script type="text/x-item" data-tpl="costs-2" data-var="c"><div style="display:flex;align-items:center;gap:5px;background:var(--surface2);border:1px solid var(--line);border-radius:11px;padding:3px 5px 3px 11px">
              <input type="text" value="{c.label}" name="costs[{c.id}][label]" style="flex:1;min-width:0;border:0;outline:none;background:transparent;font-size:13px;font-weight:500;padding:9px 0">
              <input type="text" inputmode="numeric" value="{c.amount}" name="costs[{c.id}][amount]" style="width:56px;border:0;outline:none;background:transparent;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:500;padding:9px 0;text-align:right">
              <button name="do" value="cost.remove:{c.id}" style="width:28px;height:28px;border-radius:8px;border:0;background:var(--sunk);color:var(--ink3);font-size:14px;line-height:1;cursor:pointer;flex:none">×</button>
            </div></script>
<script type="text/x-item" data-tpl="payFeatures-1" data-var="p"><div style="display:flex;align-items:flex-start;gap:11px">
          <span style="font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--accent);font-weight:600;padding-top:3px;flex:none">{p.n}</span>
          <span style="flex:1;min-width:0">
            <span style="display:block;font-size:14.5px;font-weight:600;margin-bottom:1px">{p.t}</span>
            <span style="display:block;font-size:13px;line-height:1.5;color:var(--ink2)">{p.d}</span>
          </span>
        </div></script>
