/**
 * De rekenkern uit het Claude Design-ontwerp, letterlijk overgenomen.
 *
 * Twee dingen wijken bewust af van het ontwerp:
 *  - de fiscale constanten komen van de server (window.DESIGN.constants), zodat
 *    PHP en JS met dezelfde schijven rekenen;
 *  - de renteaftrek telt de al verstreken maanden mee (m + el < dedMonths in
 *    plaats van m < dedMonths). De aftrek loopt vanaf het ontstaan van de
 *    schuld, niet vanaf het moment dat je gaat rekenen. Voor een nieuwe
 *    hypotheek (elapsed = 0) verandert er niets.
 */
(function (global) {
  'use strict';

  // De constanten komen van de server, uit het JSON-blok op de pagina.
  // window.DESIGN is de ingang voor tests, die geen pagina hebben.
  var C = (function () {
    if (global.DESIGN && global.DESIGN.constants) return global.DESIGN.constants;
    try {
      var el = global.document && global.document.getElementById('design-data');
      if (el) return JSON.parse(el.textContent).constants;
    } catch (e) {}
    throw new Error('calc.js: geen constanten gevonden (design-data ontbreekt)');
  })();

  var IO_SURCHARGE = C.IO_SURCHARGE;
  var IO_MAX_SHARE = C.IO_MAX_SHARE;
  var MARKET = C.MARKET;
  var BRACKETS = (C.BRACKETS || []).map(function (b) {
    return [b[0] === null ? Infinity : b[0], b[1]];
  });

  function num(s) {
    var t = String(s == null ? '' : s)
      .replace(/[^0-9,.-]/g, '')
      .replace(/\.(?=\d{3}\b)/g, '')
      .replace(',', '.');
    var v = parseFloat(t);
    return isNaN(v) ? 0 : v;
  }

  function schedule(part) {
    var n = Math.max(1, Math.round(part.term * 12));
    var i = part.rate / 100 / 12;
    var el = Math.min(Math.max(0, Math.round(part.elapsed || 0)), n - 1);
    var bal = part.sum;
    if (el > 0) {
      if (part.form === 'lin') bal = part.sum * (n - el) / n;
      else if (part.form === 'ann') {
        var A0 = i === 0 ? part.sum / n : part.sum * i / (1 - Math.pow(1 + i, -n));
        bal = i === 0 ? part.sum - A0 * el : A0 * (1 - Math.pow(1 + i, -(n - el))) / i;
      }
    }
    var rem = n - el, rows = [];
    var dedMonths = part.dedMonths == null ? Infinity : part.dedMonths;
    var A = i === 0 ? bal / rem : bal * i / (1 - Math.pow(1 + i, -rem));
    var linA = bal / rem;
    for (var m = 0; m < rem; m++) {
      var interest = bal * i;
      var principal = 0;
      if (part.form === 'ann') principal = Math.min(bal, A - interest);
      else if (part.form === 'lin') principal = Math.min(bal, linA);
      if (m === rem - 1 && part.form !== 'av') principal = bal;
      bal = Math.max(0, bal - principal);
      rows.push({
        interest: interest,
        principal: principal,
        payment: interest + principal,
        balance: bal,
        deductible: !!part.deductible && (m + el) < dedMonths
      });
    }
    return { rows: rows, residual: part.form === 'av' ? bal : 0 };
  }

  function firstPay(p) {
    var s = schedule(p);
    return s.rows[0] ? s.rows[0].payment : 0;
  }

  /** Afgeleiden uit het ontwerp, met de state als argument. */
  var derive = {
    totalLoan: function (S) {
      if (S.loan != null) return S.loan;
      return S.path === 'renew' ? num(S.curBalance) : Math.max(0, num(S.price) - num(S.own));
    },
    homeValue: function (S) {
      return S.path === 'renew' ? num(S.woz) : num(S.price);
    },
    ltv: function (S) {
      var p = num(S.price) || 1;
      return Math.min(125, Math.round(derive.totalLoan(S) / p * 100));
    },
    ltvAdj: function (S) {
      var l = derive.ltv(S);
      return l <= 60 ? -0.25 : l <= 80 ? -0.12 : l <= 90 ? -0.04 : 0;
    },
    marketRate: function (S) {
      return Math.round((MARKET[S.fixedY] + (S.path === 'renew' ? 0 : derive.ltvAdj(S))) * 100) / 100;
    },
    rate: function (S) {
      return S.rate != null ? S.rate : derive.marketRate(S);
    },
    ioRate: function (S) {
      return S.ioRate != null ? S.ioRate : Math.round((derive.rate(S) + IO_SURCHARGE) * 100) / 100;
    },
    ioMax: function (S) {
      return Math.max(0, Math.min(derive.totalLoan(S),
        Math.round(derive.homeValue(S) * IO_MAX_SHARE / 5000) * 5000));
    },
    dedMonths: function (S) {
      return S.path === 'renew' ? Math.min(360, (num(S.curRemaining) || 30) * 12) : 360;
    },
    parts: function (S) {
      if (S.parts && S.parts.length) return S.parts;
      var total = derive.totalLoan(S), io = Math.min(S.io, derive.ioMax(S));
      var term = S.path === 'renew' ? Math.min(S.termY, num(S.curRemaining) || S.termY) : S.termY;
      var ded = derive.dedMonths(S);
      var list = [];
      if (total - io > 0) {
        list.push({ id: 0, form: S.form, sum: total - io, rate: derive.rate(S), term: term, elapsed: 0, deductible: true, dedMonths: ded });
      }
      if (io > 0) {
        list.push({ id: 1, form: 'av', sum: io, rate: derive.ioRate(S), term: term, elapsed: 0, deductible: S.path === 'renew' && !!S.preTwentyThirteen, dedMonths: ded });
      }
      return list.length ? list : [{ id: 0, form: S.form, sum: 1000, rate: derive.rate(S), term: term, elapsed: 0, deductible: true, dedMonths: ded }];
    },
    currentPayment: function (S) {
      return firstPay({ form: S.curForm, sum: num(S.curBalance), rate: num(S.curRate), term: Math.max(1, num(S.curRemaining)), elapsed: 0, deductible: true });
    },
    atRate: function (S, rate) {
      return derive.parts(S).reduce(function (t, p) {
        return t + firstPay(Object.assign({}, p, { rate: p.form === 'av' ? rate + IO_SURCHARGE : rate }));
      }, 0);
    },
    afterFix: function (S, delta) {
      var m = S.fixedY * 12;
      return derive.parts(S).reduce(function (t, p) {
        var s = schedule(p), idx = Math.min(m, s.rows.length - 1);
        var bal = s.rows[idx] ? s.rows[idx].balance : 0;
        if (bal <= 0) return t;
        return t + firstPay({ form: p.form, sum: bal, rate: p.rate + delta, term: Math.max(1, p.term - S.fixedY), elapsed: 0, deductible: p.deductible });
      }, 0);
    }
  };

  function compute(S, costList) {
    var parts = derive.parts(S), scheds = parts.map(schedule);
    var maxM = Math.max(1, Math.max.apply(null, scheds.map(function (s) { return s.rows.length; })));
    var termY = Math.ceil(maxM / 12), years = [];
    for (var y = 0; y < termY; y++) {
      var r = 0, a = 0, pay = 0, bal = 0, ded = 0, months = 0;
      for (var m = y * 12; m < Math.min((y + 1) * 12, maxM); m++) {
        months++; bal = 0;
        scheds.forEach(function (s) {
          var row = s.rows[m];
          if (row) { r += row.interest; a += row.principal; pay += row.payment; bal += row.balance; if (row.deductible) ded += row.interest; }
        });
      }
      years.push({ y: y + 1, interest: r, principal: a, payment: pay, months: months, balance: bal, deductible: ded });
    }
    var residual = scheds.reduce(function (t, s) { return t + s.residual; }, 0);
    var costTotal = costList.reduce(function (t, c) { return t + num(c.amount); }, 0);
    var income1 = num(S.income), income2 = num(S.income2);
    var income = income1 + income2, topIncome = Math.max(income1, income2);
    var marginal = 0;
    if (income > 0) {
      for (var b = 0; b < BRACKETS.length; b++) {
        marginal = BRACKETS[b][1];
        if (topIncome <= BRACKETS[b][0]) break;
      }
    }
    var y1 = years[0] || { interest: 0, principal: 0, payment: 0, months: 12, deductible: 0 };
    var ewf = num(S.woz) * C.EWF_RATE;
    var dedInterest = y1.deductible * (12 / Math.max(1, y1.months));
    var saldo = ewf - dedInterest, effRate = Math.min(marginal, C.CAP_RATE) / 100;
    var benefit = 0, hillen = 0;
    if (income > 0) {
      if (saldo < 0) benefit = -saldo * effRate;
      else { hillen = saldo * C.HILLEN; benefit = -(saldo - hillen) * (marginal / 100); }
    }
    var grossMonthly = y1.payment / Math.max(1, y1.months);
    var grossTotal = grossMonthly + costTotal;
    return {
      parts: parts, scheds: scheds, years: years, termY: termY, residual: residual, costTotal: costTotal,
      income: income, income1: income1, income2: income2, marginal: marginal, ewf: ewf,
      dedInterest: dedInterest, hillen: hillen, benefit: benefit,
      grossMonthly: grossMonthly, grossTotal: grossTotal, netTotal: grossTotal - benefit / 12,
      interestY1: y1.interest / Math.max(1, y1.months), principalY1: y1.principal / Math.max(1, y1.months)
    };
  }

  global.Calc = { num: num, schedule: schedule, firstPay: firstPay, derive: derive, compute: compute, C: C };
})(typeof window !== 'undefined' ? window : globalThis);
