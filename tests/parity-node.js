/**
 * Draait calc.js op de testgevallen die parity.php aanlevert en schrijft de
 * uitkomsten als JSON naar stdout. Zie tests/parity.php.
 */
'use strict';

const fs = require('fs');
const path = require('path');

const payload = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));

global.window = global;
global.DESIGN = { constants: payload.constants };
// viewmodel.js leest zijn gegevens uit het script-blok op de pagina; hier
// zetten we een minimale document-stub neer met dezelfde JSON.
global.document = {
  getElementById: function () {
    return { textContent: JSON.stringify({
      translations: payload.translations, lenders: payload.lenders, constants: payload.constants
    }) };
  }
};

require(path.join(__dirname, '..', 'public', 'assets', 'js', 'calc.js'));
require(path.join(__dirname, '..', 'public', 'assets', 'js', 'viewmodel.js'));

const uit = payload.cases.map((c) => {
  const S = c.state;
  const C = global.Calc.compute(S, c.costs);
  return {
    name: c.name,
    totalLoan: global.Calc.derive.totalLoan(S),
    ltv: global.Calc.derive.ltv(S),
    rate: global.Calc.derive.rate(S),
    ioMax: global.Calc.derive.ioMax(S),
    termY: C.termY,
    grossMonthly: C.grossMonthly,
    grossTotal: C.grossTotal,
    netTotal: C.netTotal,
    interestY1: C.interestY1,
    principalY1: C.principalY1,
    costTotal: C.costTotal,
    marginal: C.marginal,
    ewf: C.ewf,
    dedInterest: C.dedInterest,
    benefit: C.benefit,
    residual: C.residual,
    years: C.years.length,
    lastBalance: C.years.length ? C.years[C.years.length - 1].balance : 0,
    totalInterest: C.years.reduce((a, y) => a + y.interest, 0),
    currentPayment: global.Calc.derive.currentPayment(S),
    afterFixUp: global.Calc.derive.afterFix(S, 1),
    afterFixDown: global.Calc.derive.afterFix(S, -1),
    vm: global.ViewModel.render(S)
  };
});

process.stdout.write(JSON.stringify(uit));
