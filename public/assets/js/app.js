/**
 * De JS-laag: dezelfde berekening als de server, live in de browser.
 *
 * Het formulier is leidend. Zonder JavaScript is elke knop een submit en elk
 * veld gewone invoer; met JavaScript onderscheppen we precies dezelfde
 * elementen, rekenen we lokaal door en werken we de pagina bij:
 *
 *   <!--b:f.heroAmount-->          de tekstnode erachter
 *   [data-b-style="...{st.buyBg}"] attribuut, opgebouwd uit het sjabloon
 *   <!--list:years-1--> … <!--/list:years-1-->  lijst, gevuld met het
 *                                  bijbehorende <script type="text/x-item">
 *
 * Handelingen die de structuur veranderen (stap verder, taal, modus, paywall,
 * leningdeel toevoegen) laten we gewoon naar de server gaan: dan rendert PHP
 * het scherm opnieuw en blijft er één bron van waarheid.
 */
(function () {
  'use strict';

  var form = document.getElementById('app');
  var databron = document.getElementById('design-data');
  if (!form || !databron || !window.Calc || !window.ViewModel) return;

  var DATA = JSON.parse(databron.textContent);
  var S = DATA.state;
  S.costs = DATA.costs;

  // Acties die alleen waarden en kleuren veranderen: die doen we lokaal.
  var LIVE = {
    fixed: function (arg) {
      S.fixedY = parseInt(arg, 10);
      S.rate = null; S.parts = null; S.lender = null;
    },
    form: function (arg) { S.form = arg; S.parts = null; },
    curForm: function (arg) { S.curForm = arg; },
    togglePre: function () { S.preTwentyThirteen = !S.preTwentyThirteen; S.parts = null; },
    lender: function (arg) {
      var i = parseInt(arg, 10), rij = DATA.lenders[i];
      if (!rij) return;
      S.lender = i;
      S.rate = Math.round((window.Calc.derive.rate(S) + rij.delta) * 100) / 100;
      S.parts = null;
    },
    theme: function () {
      S.theme = S.theme === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', S.theme);
    }
  };

  // Invoervelden: dezelfde bijwerkingen als de handlers in het ontwerp.
  var INVOER = {
    price: function (v) { S.price = num(v); S.woz = S.price; S.parts = null; S.loan = null; S.rate = null; },
    own: function (v) { S.own = num(v); S.parts = null; S.loan = null; S.rate = null; },
    loan: function (v) { S.loan = num(v); S.parts = null; },
    curBalance: function (v) { S.curBalance = num(v); S.parts = null; S.loan = null; },
    curRate: function (v) { S.curRate = num(v); },
    curRemaining: function (v) { S.curRemaining = num(v); S.parts = null; },
    termY: function (v) { S.termY = num(v); S.parts = null; },
    fixedY: function (v) { S.fixedY = num(v); S.rate = null; S.parts = null; },
    rateSlider: function (v) { S.rate = num(v) / 100; S.parts = null; S.lender = null; },
    io: function (v) { S.io = num(v); S.parts = null; },
    income: function (v) { S.income = num(v); },
    income2: function (v) { S.income2 = num(v); },
    woz: function (v) { S.woz = num(v); }
  };

  function num(v) { return window.Calc.num(v); }

  function esc(waarde) {
    return String(waarde === null || waarde === undefined ? '' : waarde)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  }

  /** 'f.heroAmount' of 'o.key' opzoeken in het viewmodel of in het lus-item. */
  function waardeVan(pad, vm, lusnaam, item) {
    var delen = pad.split('.');
    var bron = vm;
    if (delen[0] === lusnaam && item) {
      bron = item;
      delen = delen.slice(1);
    }
    for (var i = 0; i < delen.length && bron != null; i++) bron = bron[delen[i]];
    return bron;
  }

  function vulSjabloon(sjabloon, vm, lusnaam, item) {
    return sjabloon.replace(/\{([\w.]+)\}/g, function (_, pad) {
      var w = waardeVan(pad, vm, lusnaam, item);
      return esc(w === undefined ? '' : w);
    });
  }

  /** <!--if:o.flag--> … <!--/if--> in een itemsjabloon afhandelen. */
  function pasCondities(html, vm, lusnaam, item) {
    return html.replace(/<!--if:([\w.]+)-->([\s\S]*?)<!--\/if-->/g, function (_, pad, inhoud) {
      return waardeVan(pad, vm, lusnaam, item) ? inhoud : '';
    });
  }

  // ── DOM-onderdelen die we bijwerken ───────────────────────────────────────

  var teksten = [];
  var attributen = [];
  var lijsten = [];
  var sjablonen = {};

  function inventariseer() {
    attributen = [];
    [].forEach.call(document.querySelectorAll('*'), function (el) {
      [].forEach.call(el.attributes, function (attr) {
        if (attr.name.indexOf('data-b-') === 0) {
          attributen.push({ el: el, attr: attr.name.slice(7), sjabloon: attr.value });
        }
      });
    });

    [].forEach.call(document.querySelectorAll('script[type="text/x-item"]'), function (el) {
      sjablonen[el.getAttribute('data-tpl')] = {
        html: el.textContent,
        lusnaam: el.getAttribute('data-var')
      };
    });

    lijsten = [];
    teksten = [];
    var loper = document.createTreeWalker(document.body, NodeFilter.SHOW_COMMENT, null);
    var open = {};
    var node;
    while ((node = loper.nextNode())) {
      var tekst = node.nodeValue.trim();
      if (tekst.indexOf('b:') === 0) {
        // De waarde staat in de tekstnode direct achter de markering; alleen
        // die vervangen we, zodat vaste tekst als '€ ' blijft staan.
        var buur = node.nextSibling;
        if (buur && buur.nodeType === 3) {
          teksten.push({ node: buur, pad: tekst.slice(2) });
        }
      } else if (tekst.indexOf('list:') === 0) {
        open[tekst.slice(5)] = node;
      } else if (tekst.indexOf('/list:') === 0) {
        var id = tekst.slice(6);
        if (open[id]) {
          lijsten.push({ id: id, start: open[id], eind: node, lijst: id.replace(/-\d+$/, '') });
          delete open[id];
        }
      }
    }
  }

  function werkLijstBij(blok, vm) {
    var sjabloon = sjablonen[blok.id];
    var items = vm[blok.lijst];
    if (!sjabloon || !items) return;

    var html = items.map(function (item) {
      return vulSjabloon(pasCondities(sjabloon.html, vm, sjabloon.lusnaam, item), vm, sjabloon.lusnaam, item);
    }).join('');

    var node = blok.start.nextSibling;
    while (node && node !== blok.eind) {
      var volgende = node.nextSibling;
      node.parentNode.removeChild(node);
      node = volgende;
    }

    var houder = document.createElement('div');
    houder.innerHTML = html;
    while (houder.firstChild) {
      blok.eind.parentNode.insertBefore(houder.firstChild, blok.eind);
    }
  }

  function teken() {
    var vm = window.ViewModel.render(S);

    teksten.forEach(function (t) {
      var w = waardeVan(t.pad, vm, null, null);
      if (w === undefined || w === null) return;
      if (t.node.nodeValue !== String(w)) t.node.nodeValue = String(w);
    });

    attributen.forEach(function (a) {
      var waarde = vulSjabloon(a.sjabloon, vm, null, null)
        .replace(/&quot;/g, '"').replace(/&amp;/g, '&');
      if (a.el.getAttribute(a.attr) !== waarde) a.el.setAttribute(a.attr, waarde);
    });

    lijsten.forEach(function (blok) { werkLijstBij(blok, vm); });

    // Velden die de gebruiker niet zelf aanraakt (bijv. het maximum van de
    // aflossingsvrij-schuif) lopen mee met de berekening.
    var ioSchuif = form.querySelector('input[name="io"]');
    if (ioSchuif) ioSchuif.max = String(vm.n.ioMax);

    synchroniseerFormulier();
  }

  /** Het formulier moet na een lokale wijziging de juiste state meesturen. */
  function synchroniseerFormulier() {
    Object.keys(S).forEach(function (sleutel) {
      if (sleutel === 'costs' || sleutel === 'parts' || sleutel === 'preTwentyThirteen') return;
      // rateSlider is de zichtbare schuif; het verborgen veld heet rate.
      var waarde = S[sleutel];
      zetVerborgen(sleutel, waarde === null || waarde === undefined ? null : waarde);
    });
    zetVerborgen('pre2013', S.preTwentyThirteen ? '1' : '');
    zetVerborgen('pay', S.pay ? '1' : '');

    // Altijd alle velden: ze staan vooraan, dus een zichtbaar veld met dezelfde
    // naam overschrijft ze bij het versturen.
    zetLijst('costs', S.costs || []);
    zetLijst('parts', S.parts);
  }

  /**
   * Bakje vooraan het formulier voor velden die de JS-laag beheert. Vooraan,
   * omdat PHP bij dubbele namen de laatste waarde aanhoudt: een zichtbaar veld
   * moet het dus altijd winnen van deze kopie. En één bakje, zodat de volgorde
   * binnen een lijst blijft zoals hij is.
   */
  function stateBak() {
    var bak = document.getElementById('js-state');
    if (!bak) {
      bak = document.createElement('div');
      bak.id = 'js-state';
      bak.hidden = true;
      form.insertBefore(bak, form.firstChild);
    }
    return bak;
  }

  function zetVerborgen(naam, waarde) {
    var el = form.querySelector('input[type=hidden][name="' + naam + '"]');
    if (waarde === null) {
      if (el) el.parentNode.removeChild(el);
      return;
    }
    if (!el) {
      el = document.createElement('input');
      el.type = 'hidden';
      el.name = naam;
      stateBak().appendChild(el);
    }
    el.value = typeof waarde === 'boolean' ? (waarde ? '1' : '') : String(waarde);
  }

  function zetLijst(naam, rijen) {
    // Zonder eigen lijst laat de JS-laag de velden van de server staan: die
    // dragen ook de waarden die geen invoerveld hebben (vorm, aftrekbaarheid).
    if (!rijen) return;
    [].forEach.call(form.querySelectorAll('input[type=hidden][name^="' + naam + '["]'), function (el) {
      el.parentNode.removeChild(el);
    });
    var bak = stateBak();
    rijen.forEach(function (rij) {
      Object.keys(rij).forEach(function (veld) {
        var el = document.createElement('input');
        el.type = 'hidden';
        el.name = naam + '[' + rij.id + '][' + veld + ']';
        el.value = typeof rij[veld] === 'boolean' ? (rij[veld] ? '1' : '') : String(rij[veld] == null ? '' : rij[veld]);
        bak.appendChild(el);
      });
    });
  }

  // ── Gebeurtenissen ────────────────────────────────────────────────────────

  form.addEventListener('input', function (e) {
    var el = e.target;
    if (!el.name) return;

    var lijstveld = el.name.match(/^(costs|parts)\[(\d+)\]\[(\w+)\]$/);
    if (lijstveld) {
      werkLijstveldBij(lijstveld[1], parseInt(lijstveld[2], 10), lijstveld[3], el.value);
      teken();
      return;
    }

    var handler = INVOER[el.name];
    if (!handler) return;
    handler(el.value);
    teken();
  });

  function werkLijstveldBij(soort, id, veld, waarde) {
    if (soort === 'costs') {
      S.costs = (S.costs || []).map(function (c) {
        if (c.id !== id) return c;
        var kopie = Object.assign({}, c);
        kopie[veld] = veld === 'amount' ? waarde.replace(/[^0-9]/g, '') : waarde;
        return kopie;
      });
      return;
    }
    var delen = S.parts || window.Calc.derive.parts(S);
    S.parts = delen.map(function (p) {
      if (p.id !== id) return p;
      var kopie = Object.assign({}, p);
      if (veld === 'sum' || veld === 'rate') kopie[veld] = num(waarde);
      else if (veld === 'term') kopie.term = Math.max(1, Math.min(40, num(waarde)));
      else if (veld === 'elapsed') kopie.elapsed = Math.max(0, num(waarde));
      return kopie;
    });
  }

  form.addEventListener('click', function (e) {
    var knop = e.target.closest('[name="do"]');
    if (!knop) return;

    var waarde = knop.getAttribute('value') || '';
    var scheiding = waarde.indexOf(':');
    var actie = scheiding < 0 ? waarde : waarde.slice(0, scheiding);
    var argument = scheiding < 0 ? '' : waarde.slice(scheiding + 1);

    if (!LIVE[actie]) return;   // de rest handelt de server af

    e.preventDefault();
    LIVE[actie](argument);
    teken();
  });

  // Handig bij het vergelijken met het ontwerp: de state en het hertekenen zijn
  // van buitenaf bereikbaar.
  window.__app = { state: S, teken: teken };

  inventariseer();
  teken();
})();
