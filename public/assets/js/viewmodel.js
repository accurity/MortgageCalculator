/**
 * Het viewmodel uit het Claude Design-ontwerp, letterlijk overgenomen.
 *
 * Twee verschillen met het ontwerp:
 *  - de closures (set/remove/pick en het h-blok) zijn vervangen door de
 *    identiteit van het item (key, id, index). Klikken lopen namelijk via het
 *    formulier, net als zonder JavaScript;
 *  - de geldverstrekkers komen uit de database (tabel lenders) in plaats van
 *    uit een vaste lijst in de code, en zijn niet langer premium-only.
 *
 * De sleutels zijn identiek aan die van App\Services\Mortgage\ViewModel, zodat
 * server- en clientrender inwisselbaar zijn.
 */
(function (global) {
  'use strict';

  var Calc = global.Calc;
  var D = Calc.derive, num = Calc.num;
  var DATA = JSON.parse(document.getElementById('design-data').textContent);
  var T_ALL = DATA.translations;
  var LENDERS = DATA.lenders || [];
  var CONST = DATA.constants;
  var CAP_RATE = CONST.CAP_RATE, HILLEN = CONST.HILLEN;
  var TAX_YEAR = CONST.TAX_YEAR, IO_SURCHARGE = CONST.IO_SURCHARGE;

  function fmtFor(lang) {
    return new Intl.NumberFormat(lang === 'en' ? 'en-GB' : 'nl-NL', {maximumFractionDigits: 0});
  }

  function steps(S) {
    return S.path === 'renew'
      ? ['start', 'huidig', 'periode', 'rente', 'vorm', 'io', 'inkomen', 'lasten']
      : ['start', 'bedrag', 'periode', 'rente', 'vorm', 'io', 'inkomen', 'lasten'];
  }

  function costList(S) {
    if (S.costs) return S.costs;
    var c = T_ALL[S.lang].costNames;
    return [{id: 1, label: c[1][0], amount: c[1][1]}, {id: 2, label: c[2][0], amount: c[2][1]}];
  }

  var atRate = D.atRate, afterFix = D.afterFix, firstPay = Calc.firstPay;

  function renderVals(S) {
    var C = Calc.compute(S, costList(S));
    var T = T_ALL[S.lang] || T_ALL.nl;
    var prem = S.plan === 'premium';
    var renew = S.path === 'renew';
    var nf = fmtFor(S.lang);
    var fmt = function (v) { return nf.format(Math.round(v || 0)); };
    var dec = function (v) {
      var s = String(Math.round(v * 100) / 100);
      return S.lang === 'en' ? s : s.replace('.', ',');
    };
    var aantalVerstrekkers = LENDERS.length;
    var t = Object.assign({}, T, {
      rateUpsell: T.rateUpsell.replace('{n}', String(aantalVerstrekkers)),
      payFeatures: T.payFeatures.map(function (rij, i) {
        return i === 0 ? [rij[0].replace('{n}', String(aantalVerstrekkers)), rij[1]] : rij;
      })
    });
    var en = (S.lang === 'en');
    var stapLijst = steps(S), key = stapLijst[S.step] || stapLijst[0];
    const sel = on => ({b: on ? 'var(--accent)' : 'var(--line)', bg: on ? 'var(--accent-soft)' : 'var(--surface)'});
    const total = D.totalLoan(S), rate = D.rate(S), io = Math.min(S.io, D.ioMax(S));
    const cur = renew ? D.currentPayment(S) : 0, delta = C.grossMonthly - cur, up = delta > 0;
    const costs = costList(S);

    const split = [
      {l:t.rowInterest, v:'€ ' + fmt(C.interestY1), c:'var(--bar1)'},
      {l:t.rowPrincipal, v:'€ ' + fmt(C.principalY1), c:'var(--bar2)'},
      {l:t.rowCosts, v:'€ ' + fmt(C.costTotal), c:'var(--bar3)'}
    ];
    if (C.income > 0) split.push({l:t.rowTaxBenefit, v:'− € ' + fmt(C.benefit / 12), c:'var(--bar4)'});
    const bt = (C.interestY1 + C.principalY1 + C.costTotal) || 1;

    const taxRows = C.income > 0 ? [
      {l:t.taxRowInterest, v:'€ ' + fmt(C.dedInterest)},
      {l:t.taxRowEwf, v:'− € ' + fmt(C.ewf)},
      {l:t.taxRowBracket, v: dec(C.marginal) + '%'},
      {l:t.taxRowCap, v: dec(CAP_RATE) + '%'}
    ] : [
      {l:t.taxRowGross, v:'€ ' + fmt(C.grossMonthly)},
      {l:t.taxRowOther, v:'€ ' + fmt(C.costTotal)}
    ];
    if (C.income > 0 && C.hillen > 0) taxRows.splice(2, 0, {l:(en ? 'Hillen Act, ' : 'Wet Hillen, ') + Math.round(HILLEN * 100) + (en ? '% extra relief' : '% extra aftrek'), v:'€ ' + fmt(C.hillen)});
    if (io > 0 && C.income > 0) taxRows.push({l:t.taxRowIo, v:(renew && S.preTwentyThirteen) ? t.taxIoYes : t.taxIoNo});

    return {
      theme: S.theme, t,
      is: {
        wizard: S.view === 'wizard', calc: S.view === 'calc',
        simple: S.mode === 'simple', adv: S.mode === 'advanced',
        free: !prem, premium: prem, pay: S.pay, renew,
        canBack: S.step > 0, hasIo: io > 0, noIo: io === 0, hasResidual: C.residual > 0,
        kStart: key === 'start', kBedrag: key === 'bedrag', kHuidig: key === 'huidig',
        kPeriode: key === 'periode', kRente: key === 'rente', kVorm: key === 'vorm',
        kIo: key === 'io', kInkomen: key === 'inkomen', kLasten: key === 'lasten'
      },
      n: {price:S.price, own:S.own, loan:total, termY:S.termY, rateSlider:Math.round(rate * 100), io, ioMax:D.ioMax(S)},
      st: {
        simpleBg: S.mode === 'simple' ? 'var(--surface)' : 'transparent',
        simpleInk: S.mode === 'simple' ? 'var(--ink)' : 'var(--ink3)',
        advBg: S.mode === 'advanced' ? 'var(--surface)' : 'transparent',
        advInk: S.mode === 'advanced' ? 'var(--ink)' : 'var(--ink3)',
        nlBg: en ? 'transparent' : 'var(--surface)', nlInk: en ? 'var(--ink3)' : 'var(--ink)',
        enBg: en ? 'var(--surface)' : 'transparent', enInk: en ? 'var(--ink)' : 'var(--ink3)',
        themeDot: S.theme === 'dark' ? 'var(--ink2)' : 'transparent',
        buyB: sel(!renew).b, buyBg: sel(!renew).bg, renewB: sel(renew).b, renewBg: sel(renew).bg,
        preB: S.preTwentyThirteen ? 'var(--accent)' : 'var(--line2)',
        preFill: S.preTwentyThirteen ? 'var(--accent)' : 'transparent',
        deltaBg: renew ? (up ? 'var(--warn-soft)' : 'var(--accent-soft)') : 'var(--surface2)',
        deltaLine: renew ? (up ? 'var(--warn-line)' : 'var(--accent-line)') : 'var(--line)',
        deltaInk: renew ? (up ? 'var(--warn)' : 'var(--ink)') : 'var(--ink)'
      },
      stepTicks: stapLijst.map((s, i) => ({c: i <= S.step ? 'var(--accent)' : 'var(--line2)'})),
      f: {
        stepNo: String(S.step + 1).padStart(2, '0'), stepTotal: stapLijst.length, stepName: t.stepNames[key],
        nextLabel: S.step === stapLijst.length - 1 ? t.finish : t.next,
        price: fmt(S.price), own: fmt(S.own), loan: fmt(total),
        ltv: D.ltv(S), ltvNote: D.ltvAdj(S) < 0 ? dec(D.ltvAdj(S)) + (en ? '% discount' : '% korting') : (en ? 'no discount' : 'geen korting'),
        curBalance: fmt(num(S.curBalance)), curRate: dec(num(S.curRate)), curRemaining: S.curRemaining,
        curPayment: fmt(cur),
        curSummary: dec(num(S.curRate)) + '% · ' + t.forms[['ann','lin','av'].indexOf(S.curForm)].name.toLowerCase(),
        newSummary: dec(rate) + '% · ' + S.fixedY + (en ? ' yrs fixed' : ' jaar vast'),
        termY: C.termY,
        termNote: renew
          ? (en ? 'What is still left to run on your current mortgage.' : 'Wat er nog te gaan is op je huidige hypotheek.')
          : (en ? 'Thirty years is standard. Shorter means higher monthly costs but far less interest.' : 'Dertig jaar is standaard. Korter betekent hogere maandlasten maar veel minder rente.'),
        fixedY: S.fixedY,
        fixedNote: S.fixedY <= 5
          ? (en ? 'Fixing short is cheaper now, but in ' + S.fixedY + ' years you face an unknown rate again.'
                : 'Kort vastzetten is nu goedkoper, maar over ' + S.fixedY + ' jaar sta je opnieuw voor een onbekende rente.')
          : (en ? 'You know exactly where you stand for ' + S.fixedY + ' years. After that the mortgage runs on for another ' + Math.max(0, C.termY - S.fixedY) + ' years at a new rate.'
                : 'Je weet ' + S.fixedY + ' jaar precies waar je aan toe bent. Daarna loopt de hypotheek nog ' + Math.max(0, C.termY - S.fixedY) + ' jaar door tegen een nieuwe rente.'),
        rate: dec(rate), rateMin: dec(0.5), rateMax: dec(8),
        rateIntro: en
          ? 'We fill in a market average for ' + S.fixedY + ' years fixed at your rate class. Drag it to whatever your lender offers.'
          : 'We vullen een marktgemiddelde in voor ' + S.fixedY + ' jaar vast bij jouw tariefklasse. Schuif hem naar wat jouw geldverstrekker biedt.',
        rateSource: prem
          ? (en ? 'Average of fourteen lenders, pulled today' : 'Gemiddelde van veertien geldverstrekkers, vandaag opgehaald')
          : (en ? 'Market average ' + S.fixedY + ' years fixed, rate class ' + D.ltv(S) + '%' : 'Marktgemiddelde ' + S.fixedY + ' jaar vast, tariefklasse ' + D.ltv(S) + '%'),
        grossMonthly: fmt(C.grossMonthly),
        deltaLabel: renew ? t.difference : (en ? 'Of which interest' : 'Waarvan rente'),
        deltaValue: renew ? ((up ? '+ ' : '− ') + '€ ' + fmt(Math.abs(delta))) : ('€ ' + fmt(C.interestY1)),
        vormTitle: renew
          ? (en ? 'Do you want to switch type?' : 'Wil je van vorm wisselen?')
          : (en ? 'How do you want to repay?' : 'Hoe wil je aflossen?'),
        vormIntro: renew
          ? (en ? 'When a new fixed-rate period starts you may usually convert your mortgage. Below is what each type does to your monthly cost.'
                : 'Bij een nieuwe rentevaste periode mag je je hypotheek vaak omzetten. Hieronder zie je wat elke vorm met je maandlast doet.')
          : (en ? 'With annuity and linear the loan is fully gone after ' + C.termY + ' years. The difference is in how your monthly cost moves.'
                : 'Bij annuïtair en lineair is de lening na ' + C.termY + ' jaar helemaal weg. Het verschil zit in het verloop van je maandlast.'),
        io: fmt(io), ioMaxLabel: (en ? 'max € ' : 'max € ') + fmt(D.ioMax(S)),
        ioShare: io > 0
          ? Math.round(io / Math.max(1, D.homeValue(S)) * 100) + (en ? '% of the property value · 50% maximum' : '% van de woningwaarde · maximaal 50%')
          : (en ? 'Drag to make part of it interest-only' : 'Sleep om een deel aflossingsvrij te maken'),
        ioIntro: renew
          ? (en ? 'You may keep or make part of your mortgage interest-only, up to half the property value. That lowers your monthly cost now, but the amount stays owed.'
                : 'Je mag een deel van je hypotheek aflossingsvrij houden of maken, tot de helft van de woningwaarde. Dat verlaagt je maandlast nu, maar het bedrag blijft staan.')
          : (en ? 'On a new mortgage part of it may be interest-only, up to half the property value. Your monthly cost drops, but those euros get no interest relief and the debt is still there at the end.'
                : 'Bij een nieuwe hypotheek mag een deel aflossingsvrij, tot de helft van de woningwaarde. Je maandlast gaat omlaag, maar over die euro’s krijg je geen renteaftrek en aan het eind staat de schuld er nog.'),
        ioSaving: fmt(Math.max(0, firstPay({form:S.form, sum:io, rate, term:C.termY, elapsed:0, deductible:true}) - firstPay({form:'av', sum:io, rate:D.ioRate(S), term:C.termY, elapsed:0, deductible:false}))),
        ioRate: dec(D.ioRate(S)),
        ioRateNote: en
          ? 'Lenders typically add ' + dec(IO_SURCHARGE) + '% on an interest-only part, because nothing is being repaid against it.'
          : 'Geldverstrekkers rekenen op een aflossingsvrij deel doorgaans ' + dec(IO_SURCHARGE) + '% opslag, omdat er geen aflossing tegenover staat.',
        ioTaxNote: (renew && S.preTwentyThirteen)
          ? (en ? 'Because your mortgage predates 2013, interest on this part stays deductible until your thirty years are up.'
                : 'Omdat je hypotheek van vóór 2013 is, blijft de rente op dit deel aftrekbaar tot je dertig jaar vol zijn.')
          : (en ? 'You get no interest relief on this part. Since 2013 that only applies if you repay in full within thirty years.'
                : 'Over dit deel krijg je geen hypotheekrenteaftrek. Die geldt sinds 2013 alleen als je binnen dertig jaar volledig aflost.'),
        income: S.income ? fmt(num(S.income)) : '', income2: S.income2 ? fmt(num(S.income2)) : '',
        incomeTotal: fmt(C.income), woz: fmt(num(S.woz)),
        incomeNote: C.income2 > 0
          ? (en ? 'Together € ' + fmt(C.income) + '. Relief is calculated at the rate of the higher of the two.'
                : 'Samen € ' + fmt(C.income) + '. De aftrek rekenen we tegen het tarief van de hoogste van de twee.')
          : (en ? 'If you have a tax partner, fill in the second income as well.' : 'Heb je een fiscaal partner, vul dan ook het tweede inkomen in.'),
        costTotal: fmt(C.costTotal),
        taxTeaserTitle: C.income > 0
          ? (en ? 'Marginal rate ' + dec(C.marginal) + '%' : 'Marginaal tarief ' + dec(C.marginal) + '%')
          : (en ? 'Without an income we calculate gross' : 'Zonder inkomen rekenen we bruto door'),
        taxTeaser: C.income > 0
          ? (en ? 'You should get roughly € ' + fmt(C.benefit / 12) + ' back per month, after the imputed rental value is offset.'
                : 'Je krijgt naar schatting € ' + fmt(C.benefit / 12) + ' per maand terug, na verrekening van het eigenwoningforfait.')
          : (en ? 'Add an income later and the net figure appears by itself.' : 'Vul je later alsnog een inkomen in, dan verschijnt de nettolast vanzelf.'),
        heroLabel: C.income > 0 ? t.heroNet : t.heroGross,
        heroAmount: fmt(C.income > 0 ? C.netTotal : C.grossTotal),
        heroSub: C.income > 0
          ? (en ? 'Gross € ' + fmt(C.grossTotal) + ' · € ' + fmt(C.benefit / 12) + ' per month back from the tax office'
                : 'Bruto € ' + fmt(C.grossTotal) + ' · € ' + fmt(C.benefit / 12) + ' per maand terug van de Belastingdienst')
          : (en ? 'Add an income to see what you keep net' : 'Vul een inkomen in om te zien wat je netto overhoudt'),
        totalLabel: C.income > 0 ? t.netPerMonth : t.grossPerMonth,
        chartNote: S.fixedY + (en ? ' of ' : ' van ') + C.termY + (en ? ' yrs fixed' : ' jaar vast'),
        chartEnd: '+' + C.termY + ' ' + t.years,
        residualNote: en
          ? 'After ' + C.termY + ' years € ' + fmt(C.residual) + ' is still owed from your interest-only part. You then have to repay it, refinance it, or settle it when the house is sold.'
          : 'Na ' + C.termY + ' jaar staat er nog € ' + fmt(C.residual) + ' open uit je aflossingsvrije deel. Dat bedrag moet je dan aflossen, oversluiten of met de verkoop van de woning voldoen.',
        tableUpsell: en
          ? 'Free shows six years. Premium shows all ' + C.termY + ' years, broken down per month, with CSV export.'
          : 'Gratis zie je zes jaar. Premium toont alle ' + C.termY + ' jaar, per maand uitgesplitst, met CSV-export.',
        partsNote: prem
          ? (en ? 'As many parts as you like, each with its own type and rate.' : 'Zoveel delen als je wilt, elk met een eigen vorm en rente.')
          : (en ? 'Two parts on the free plan. Premium makes it unlimited.' : 'Gratis tot twee delen. Premium maakt het onbeperkt.'),
        taxIntro: C.income > 0
          ? (en ? 'Mortgage interest relief minus the imputed rental value, at ' + TAX_YEAR + ' rates.'
                : 'Hypotheekrenteaftrek min eigenwoningforfait, tegen de tarieven van ' + TAX_YEAR + '.')
          : (en ? 'Without an annual income this part drops out and the rest of the calculation keeps working.'
                : 'Zonder jaarinkomen blijft dit deel weg en werkt de rest van de berekening gewoon.'),
        taxResultLabel: C.income > 0 ? t.taxBenefitMonth : t.grossPerMonth,
        taxResult: fmt(C.income > 0 ? C.benefit / 12 : C.grossTotal),
        marginal: dec(C.marginal), capRate: dec(CAP_RATE),
        ratesNote: en
          ? 'Example rates for € ' + fmt(total) + ', ' + S.fixedY + ' years fixed, rate class ' + D.ltv(S) + '%. Not live market data.'
          : 'Voorbeeldtarieven voor € ' + fmt(total) + ', ' + S.fixedY + ' jaar vast, tariefklasse ' + D.ltv(S) + '%. Geen echte marktdata.',
        disclaimer: en
          ? 'Indicative calculation, not financial advice. Tax figures are those of ' + TAX_YEAR + '; check them with the Dutch tax office before relying on the outcome.'
          : 'Indicatieve berekening, geen financieel advies. Fiscale tarieven zijn die van ' + TAX_YEAR + '; controleer ze bij de Belastingdienst voordat je op de uitkomst vertrouwt.'
      },
      heroTags: [
        {v:'€ ' + fmt(total) + (en ? ' mortgage' : ' hypotheek')},
        {v: dec(rate) + '% · ' + S.fixedY + '/' + C.termY + ' ' + t.years},
        {v: io > 0 ? '€ ' + fmt(io) + ' ' + t.forms[2].name.toLowerCase() : t.forms[['ann','lin','av'].indexOf(S.form)].name}
      ],
      split,
      splitBars: [
        {w:(C.interestY1 / bt * 100) + '%', c:'var(--bar1)'},
        {w:(C.principalY1 / bt * 100) + '%', c:'var(--bar2)'},
        {w:(C.costTotal / bt * 100) + '%', c:'var(--bar3)'}
      ],
      bars: C.years.map(y => ({
        h: Math.max(2, (y.balance / Math.max(1, C.years[0].balance || 1)) * 100) + '%',
        c: y.y <= S.fixedY ? 'var(--bar1)' : 'var(--bar2)',
        title: (en ? 'Year ' : 'Jaar ') + y.y + ': € ' + fmt(y.balance)
      })),
      years: (prem ? C.years : C.years.slice(0, 6)).map(y => ({
        y: y.y, r:'€ ' + fmt(y.interest), a:'€ ' + fmt(y.principal), s:'€ ' + fmt(y.balance),
        m:'€ ' + fmt(y.payment / Math.max(1, y.months)),
        c: y.y <= S.fixedY ? 'var(--accent)' : 'var(--ink3)'
      })),
      tax: taxRows,
      fixOpts: [1, 5, 10, 20, 30].map(v => {
        const klasse = D.riskClass(S);
        const r = CONST.RATE_TABLE[klasse.code][v] != null ? CONST.RATE_TABLE[klasse.code][v] : 4.0;
        return {y:v, rate: dec(r), b: sel(S.fixedY === v).b, bg: sel(S.fixedY === v).bg};
      }),
      formOpts: t.forms.map((f, i) => {
        const k = ['ann','lin','av'][i];
        return {key:k, name:f.name, desc:f.desc, b: sel(S.form === k).b, bg: sel(S.form === k).bg,
          first: fmt(firstPay({form:k, sum:total - io, rate, term:C.termY, elapsed:0, deductible:true}) + (io > 0 ? firstPay({form:'av', sum:io, rate:D.ioRate(S), term:C.termY, elapsed:0, deductible:false}) : 0)),
          flag: k === 'av', flagText: t.ioFlag};
      }),
      curFormOpts: t.forms.map((f, i) => {
        const k = ['ann','lin','av'][i];
        return {key:k, short:f.short, b: sel(S.curForm === k).b, bg: sel(S.curForm === k).bg};
      }),
      parts: C.parts.map((p, idx) => ({
        id: p.id, form: p.form, deductible: !!p.deductible, dedMonths: p.dedMonths == null ? 360 : p.dedMonths,
        title: t.part + ' ' + (idx + 1), canRemove: C.parts.length > 1,
        sum: fmt(num(p.sum)), rate: dec(p.rate), term: p.term, elapsed: p.elapsed,
        first: fmt((C.scheds[idx].rows[0] || {}).payment || 0),
        dedB: p.deductible ? 'var(--accent)' : 'var(--line2)', dedFill: p.deductible ? 'var(--accent)' : 'transparent',
        forms: t.forms.map((f, i) => {
          const k = ['ann','lin','av'][i];
          return {key:k, short:f.short, b: sel(p.form === k).b, bg: sel(p.form === k).bg};
        })
      })),
      costs: costs.map(c => ({id: c.id, label: c.label, amount: c.amount})),
      costSuggest: t.costNames
        .filter(([l]) => !costs.some(c => c.label === l))
        .map(([l, a]) => ({label:l, amount:a})),
      lenders: LENDERS.map(function (rij) {
        var d = rij.delta;
        var r = Math.round((rate + d) * 100) / 100;
        return {index: rij.id, name: rij.name, note: rij.note || '',
          logo: rij.logo || null, noLogo: !rij.logo, initial: (rij.name || '').slice(0, 1),
          rate: dec(r), rateValue: r,
          delta: (d < 0 ? '−' : '+') + ' € ' + fmt(Math.abs(atRate(S, r) - C.grossMonthly)) + ' p/m',
          deltaC: d < 0 ? 'var(--accent)' : 'var(--warn)',
          b: S.lender === rij.id ? 'var(--gold-line)' : 'var(--line)',
          bg: S.lender === rij.id ? 'var(--gold-soft)' : 'var(--surface)'};
      }),
      scenarios: [
        {l: en ? 'Rate +1% after the fixed period' : 'Rente +1% na de vaste periode',
         note: (en ? 'from year ' : 'vanaf jaar ') + (S.fixedY + 1), v: fmt(afterFix(S, 1)),
         d:'+ € ' + fmt(Math.max(0, afterFix(S, 1) - C.grossMonthly)), dc:'var(--warn)'},
        {l: en ? 'Rate −1% after the fixed period' : 'Rente −1% na de vaste periode',
         note: (en ? 'from year ' : 'vanaf jaar ') + (S.fixedY + 1), v: fmt(afterFix(S, -1)),
         d:'− € ' + fmt(Math.max(0, C.grossMonthly - afterFix(S, -1))), dc:'var(--accent)'},
        {l: en ? 'Repay everything on annuity' : 'Alles annuïtair aflossen',
         note: en ? 'no interest-only part' : 'geen aflossingsvrij deel',
         v: fmt(firstPay({form:'ann', sum:total, rate, term:C.termY, elapsed:0, deductible:true})),
         d: io > 0 ? (en ? 'nothing left owing' : 'restschuld € 0') : (en ? 'current choice' : 'huidige keuze'), dc:'var(--ink3)'},
        {l: en ? 'Total interest over the term' : 'Totale rente over de looptijd',
         note: en ? 'at these settings' : 'bij deze instellingen',
         v: fmt(C.years.reduce((a, y) => a + y.interest, 0)), d:'', dc:'var(--ink3)'}
      ],
      locked: t.locked.map(([title, d], i) => ({n: String(i + 1).padStart(2, '0'), t: title, d})),
      payFeatures: t.payFeatures.map(([title, d], i) => ({n: String(i + 1).padStart(2, '0'), t: title, d})),
    };
  }
  global.ViewModel = {render: renderVals, steps: steps, costList: costList};
})(window);
