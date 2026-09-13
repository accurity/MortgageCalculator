<?php

declare(strict_types=1);

namespace App\Services\Mortgage;

/**
 * De PHP-spiegel van renderVals() uit het ontwerp.
 *
 * Levert exact dezelfde sleutels als de browserversie (t, is, n, st, f en de
 * lijsten), zodat server- en clientrender inwisselbaar zijn en de templates
 * maar één vorm hoeven te kennen.
 */
final class ViewModel
{
    private State $state;
    private Calculator $calc;
    /** @var array<string, mixed> */
    private array $t;
    private bool $en;

    public function __construct(State $state)
    {
        $this->state = $state;
        $this->calc = new Calculator($state);
        $this->t = Translations::voor($state->lang);
        $this->en = $state->lang === 'en';

        $aantalVerstrekkers = (string)\App\Models\Lender::query()->where('active', true)->count();
        $this->t['rateUpsell'] = str_replace('{n}', $aantalVerstrekkers, $this->t['rateUpsell']);
        $this->t['payFeatures'][0][0] = str_replace('{n}', $aantalVerstrekkers, $this->t['payFeatures'][0][0]);
    }

    public function state(): State
    {
        return $this->state;
    }

    public function calculator(): Calculator
    {
        return $this->calc;
    }

    /** Hele euro's, met de scheidingstekens van de taal. */
    public function fmt(float $v): string
    {
        $afgerond = round($v);

        return $this->en
            ? number_format($afgerond, 0, '.', ',')
            : number_format($afgerond, 0, ',', '.');
    }

    /** Getal met maximaal twee decimalen, zonder nullen erachter. */
    public function dec(float $v): string
    {
        $afgerond = round($v * 100) / 100;
        $s = rtrim(rtrim(number_format($afgerond, 2, '.', ''), '0'), '.');
        if ($s === '' || $s === '-') {
            $s = '0';
        }

        return $this->en ? $s : str_replace('.', ',', $s);
    }

    /** @return array<string, mixed> */
    public function build(): array
    {
        $S = $this->state;
        $C = $this->calc->compute();
        $t = $this->t;
        $en = $this->en;
        $prem = $S->isPremium();
        $renew = $S->isRenew();

        $steps = $S->steps();
        $key = $S->stapSleutel();
        $total = $this->calc->totalLoan();
        $rate = $this->calc->rate();
        $io = $this->calc->io();
        $cur = $renew ? $this->calc->currentPayment() : 0.0;
        $delta = $C['grossMonthly'] - $cur;
        $up = $delta > 0;
        $costs = $S->kostenLijst();
        $formNamen = $t['forms'];
        $vormIndex = array_search($S->form, Constants::FORMS, true);
        $curVormIndex = array_search($S->curForm, Constants::FORMS, true);

        $split = [
            ['l' => $t['rowInterest'],  'v' => '€ ' . $this->fmt($C['interestY1']),  'c' => 'var(--bar1)'],
            ['l' => $t['rowPrincipal'], 'v' => '€ ' . $this->fmt($C['principalY1']), 'c' => 'var(--bar2)'],
            ['l' => $t['rowCosts'],     'v' => '€ ' . $this->fmt($C['costTotal']),   'c' => 'var(--bar3)'],
        ];
        if ($C['income'] > 0) {
            $split[] = ['l' => $t['rowTaxBenefit'], 'v' => '− € ' . $this->fmt($C['benefit'] / 12), 'c' => 'var(--bar4)'];
        }
        $bt = ($C['interestY1'] + $C['principalY1'] + $C['costTotal']) ?: 1.0;

        $taxRows = $C['income'] > 0
            ? [
                ['l' => $t['taxRowInterest'], 'v' => '€ ' . $this->fmt($C['dedInterest'])],
                ['l' => $t['taxRowEwf'],      'v' => '− € ' . $this->fmt($C['ewf'])],
                ['l' => $t['taxRowBracket'],  'v' => $this->dec($C['marginal']) . '%'],
                ['l' => $t['taxRowCap'],      'v' => $this->dec(Constants::capRate()) . '%'],
            ]
            : [
                ['l' => $t['taxRowGross'], 'v' => '€ ' . $this->fmt($C['grossMonthly'])],
                ['l' => $t['taxRowOther'], 'v' => '€ ' . $this->fmt($C['costTotal'])],
            ];

        if ($C['income'] > 0 && $C['hillen'] > 0) {
            $label = ($en ? 'Hillen Act, ' : 'Wet Hillen, ')
                . round(Constants::hillen() * 100)
                . ($en ? '% extra relief' : '% extra aftrek');
            array_splice($taxRows, 2, 0, [['l' => $label, 'v' => '€ ' . $this->fmt($C['hillen'])]]);
        }
        if ($io > 0 && $C['income'] > 0) {
            $taxRows[] = [
                'l' => $t['taxRowIo'],
                'v' => ($renew && $S->preTwentyThirteen) ? $t['taxIoYes'] : $t['taxIoNo'],
            ];
        }

        return [
            'theme' => $S->theme,
            't' => $t,
            'is' => [
                'wizard' => $S->view === 'wizard', 'calc' => $S->view === 'calc',
                'simple' => $S->mode === 'simple', 'adv' => $S->mode === 'advanced',
                'free' => !$prem, 'premium' => $prem, 'pay' => $S->pay, 'renew' => $renew,
                'canBack' => $S->step > 0, 'hasIo' => $io > 0, 'noIo' => $io <= 0,
                'hasResidual' => $C['residual'] > 0,
                'kStart' => $key === 'start', 'kBedrag' => $key === 'bedrag', 'kHuidig' => $key === 'huidig',
                'kPeriode' => $key === 'periode', 'kRente' => $key === 'rente', 'kVorm' => $key === 'vorm',
                'kIo' => $key === 'io', 'kInkomen' => $key === 'inkomen', 'kLasten' => $key === 'lasten',
            ],
            'n' => [
                'price' => $S->price, 'own' => $S->own, 'loan' => $total, 'termY' => $S->termY,
                'rateSlider' => (int)round($rate * 100), 'io' => $io, 'ioMax' => $this->calc->ioMax(),
            ],
            'st' => [
                'simpleBg'  => $S->mode === 'simple' ? 'var(--surface)' : 'transparent',
                'simpleInk' => $S->mode === 'simple' ? 'var(--ink)' : 'var(--ink3)',
                'advBg'     => $S->mode === 'advanced' ? 'var(--surface)' : 'transparent',
                'advInk'    => $S->mode === 'advanced' ? 'var(--ink)' : 'var(--ink3)',
                'nlBg'      => $en ? 'transparent' : 'var(--surface)',
                'nlInk'     => $en ? 'var(--ink3)' : 'var(--ink)',
                'enBg'      => $en ? 'var(--surface)' : 'transparent',
                'enInk'     => $en ? 'var(--ink)' : 'var(--ink3)',
                'themeDot'  => $S->theme === 'dark' ? 'var(--ink2)' : 'transparent',
                'buyB'      => self::sel(!$renew)['b'],  'buyBg'   => self::sel(!$renew)['bg'],
                'renewB'    => self::sel($renew)['b'],   'renewBg' => self::sel($renew)['bg'],
                'preB'      => $S->preTwentyThirteen ? 'var(--accent)' : 'var(--line2)',
                'preFill'   => $S->preTwentyThirteen ? 'var(--accent)' : 'transparent',
                'deltaBg'   => $renew ? ($up ? 'var(--warn-soft)' : 'var(--accent-soft)') : 'var(--surface2)',
                'deltaLine' => $renew ? ($up ? 'var(--warn-line)' : 'var(--accent-line)') : 'var(--line)',
                'deltaInk'  => $renew ? ($up ? 'var(--warn)' : 'var(--ink)') : 'var(--ink)',
            ],
            'stepTicks' => array_map(
                static fn (int $i): array => ['c' => $i <= $S->step ? 'var(--accent)' : 'var(--line2)'],
                range(0, count($steps) - 1)
            ),
            'f' => $this->velden($C, $total, $rate, $io, $cur, $delta, $up, $key, $steps, $prem, $renew, $formNamen, (int)$curVormIndex),
            'heroTags' => [
                ['v' => '€ ' . $this->fmt($total) . ($en ? ' mortgage' : ' hypotheek')],
                ['v' => $this->dec($rate) . '% · ' . $S->fixedY . '/' . $C['termY'] . ' ' . $t['years']],
                ['v' => $io > 0
                    ? '€ ' . $this->fmt($io) . ' ' . $this->kleineLetter($formNamen[2]['name'])
                    : $formNamen[(int)$vormIndex]['name']],
            ],
            'split' => $split,
            'splitBars' => [
                ['w' => ($C['interestY1'] / $bt * 100) . '%',  'c' => 'var(--bar1)'],
                ['w' => ($C['principalY1'] / $bt * 100) . '%', 'c' => 'var(--bar2)'],
                ['w' => ($C['costTotal'] / $bt * 100) . '%',   'c' => 'var(--bar3)'],
            ],
            'bars' => array_map(function (array $y) use ($C, $S, $en): array {
                $eerste = $C['years'][0]['balance'] ?: 1.0;

                return [
                    'h' => max(2, $y['balance'] / max(1.0, $eerste) * 100) . '%',
                    'c' => $y['y'] <= $S->fixedY ? 'var(--bar1)' : 'var(--bar2)',
                    'title' => ($en ? 'Year ' : 'Jaar ') . $y['y'] . ': € ' . $this->fmt($y['balance']),
                ];
            }, $C['years']),
            'years' => array_map(function (array $y) use ($S): array {
                return [
                    'y' => $y['y'],
                    'r' => '€ ' . $this->fmt($y['interest']),
                    'a' => '€ ' . $this->fmt($y['principal']),
                    's' => '€ ' . $this->fmt($y['balance']),
                    'm' => '€ ' . $this->fmt($y['payment'] / max(1, $y['months'])),
                    'c' => $y['y'] <= $S->fixedY ? 'var(--accent)' : 'var(--ink3)',
                ];
            }, $prem ? $C['years'] : array_slice($C['years'], 0, 6)),
            'tax' => $taxRows,
            'fixOpts' => array_map(function (int $v) use ($S): array {
                $r = \App\Services\Mortgage\Domain\RateRepository::rate($v, $this->calc->riskClass());

                return [
                    'y' => $v, 'rate' => $this->dec($r),
                    'b' => self::sel($S->fixedY === $v)['b'], 'bg' => self::sel($S->fixedY === $v)['bg'],
                ];
            }, Constants::fixedOptions()),
            'formOpts' => $this->vormOpties($C, $total, $rate, $io, $formNamen),
            'curFormOpts' => array_map(function (array $f, int $i) use ($S): array {
                $k = Constants::FORMS[$i];

                return [
                    'key' => $k, 'short' => $f['short'],
                    'b' => self::sel($S->curForm === $k)['b'], 'bg' => self::sel($S->curForm === $k)['bg'],
                ];
            }, $formNamen, array_keys($formNamen)),
            'parts' => $this->delen($C, $formNamen),
            'costs' => $costs,
            'costSuggest' => $this->suggesties($costs),
            'lenders' => $this->verstrekkers($C, $rate),
            'scenarios' => $this->scenarios($C, $total, $rate, $io),
            'locked' => array_map(
                static fn (array $rij, int $i): array => ['n' => str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT), 't' => $rij[0], 'd' => $rij[1]],
                $t['locked'],
                array_keys($t['locked'])
            ),
            'payFeatures' => array_map(
                static fn (array $rij, int $i): array => ['n' => str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT), 't' => $rij[0], 'd' => $rij[1]],
                $t['payFeatures'],
                array_keys($t['payFeatures'])
            ),
        ];
    }

    /** @return array{b:string,bg:string} */
    private static function sel(bool $aan): array
    {
        return [
            'b'  => $aan ? 'var(--accent)' : 'var(--line)',
            'bg' => $aan ? 'var(--accent-soft)' : 'var(--surface)',
        ];
    }

    private function kleineLetter(string $s): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    }

    /**
     * @param array<string, mixed> $C
     * @param list<string> $steps
     * @param list<array<string, string>> $formNamen
     * @return array<string, mixed>
     */
    private function velden(array $C, float $total, float $rate, float $io, float $cur, float $delta, bool $up, string $key, array $steps, bool $prem, bool $renew, array $formNamen, int $curVormIndex): array
    {
        $S = $this->state;
        $t = $this->t;
        $en = $this->en;
        $termY = $C['termY'];
        $ioMax = $this->calc->ioMax();
        $ioRate = $this->calc->ioRate();

        $ioBesparing = max(0.0,
            Calculator::firstPay(['form' => $S->form, 'sum' => $io, 'rate' => $rate, 'term' => $termY, 'elapsed' => 0, 'deductible' => true, 'dedMonths' => 360])
            - Calculator::firstPay(['form' => 'av', 'sum' => $io, 'rate' => $ioRate, 'term' => $termY, 'elapsed' => 0, 'deductible' => false, 'dedMonths' => 360])
        );

        return [
            'stepNo' => str_pad((string)($S->step + 1), 2, '0', STR_PAD_LEFT),
            'stepTotal' => count($steps),
            'stepName' => $t['stepNames'][$key] ?? '',
            'nextLabel' => $S->step === count($steps) - 1 ? $t['finish'] : $t['next'],

            'price' => $this->fmt($S->price),
            'own' => $this->fmt($S->own),
            'loan' => $this->fmt($total),
            'ltv' => $this->calc->ltv(),
            'ltvNote' => $this->calc->ltvAdj() < 0
                ? $this->dec($this->calc->ltvAdj()) . ($en ? '% discount' : '% korting')
                : ($en ? 'no discount' : 'geen korting'),

            'curBalance' => $this->fmt($S->curBalance),
            'curRate' => $this->dec($S->curRate),
            'curRemaining' => $this->dec($S->curRemaining),
            'curPayment' => $this->fmt($cur),
            'curSummary' => $this->dec($S->curRate) . '% · ' . $this->kleineLetter($formNamen[$curVormIndex]['name']),
            'newSummary' => $this->dec($rate) . '% · ' . $S->fixedY . ($en ? ' yrs fixed' : ' jaar vast'),

            'termY' => $termY,
            'termNote' => $renew
                ? ($en ? 'What is still left to run on your current mortgage.' : 'Wat er nog te gaan is op je huidige hypotheek.')
                : ($en ? 'Thirty years is standard. Shorter means higher monthly costs but far less interest.' : 'Dertig jaar is standaard. Korter betekent hogere maandlasten maar veel minder rente.'),
            'fixedY' => $S->fixedY,
            'fixedNote' => $S->fixedY <= 5
                ? ($en
                    ? 'Fixing short is cheaper now, but in ' . $S->fixedY . ' years you face an unknown rate again.'
                    : 'Kort vastzetten is nu goedkoper, maar over ' . $S->fixedY . ' jaar sta je opnieuw voor een onbekende rente.')
                : ($en
                    ? 'You know exactly where you stand for ' . $S->fixedY . ' years. After that the mortgage runs on for another ' . max(0, $termY - $S->fixedY) . ' years at a new rate.'
                    : 'Je weet ' . $S->fixedY . ' jaar precies waar je aan toe bent. Daarna loopt de hypotheek nog ' . max(0, $termY - $S->fixedY) . ' jaar door tegen een nieuwe rente.'),

            'rate' => $this->dec($rate),
            'rateMin' => $this->dec(0.5),
            'rateMax' => $this->dec(8),
            'rateIntro' => $en
                ? 'We fill in a market average for ' . $S->fixedY . ' years fixed at your rate class. Drag it to whatever your lender offers.'
                : 'We vullen een marktgemiddelde in voor ' . $S->fixedY . ' jaar vast bij jouw tariefklasse. Schuif hem naar wat jouw geldverstrekker biedt.',
            'rateSource' => $prem
                ? ($en ? 'Average of fourteen lenders, pulled today' : 'Gemiddelde van veertien geldverstrekkers, vandaag opgehaald')
                : ($en ? 'Market average ' . $S->fixedY . ' years fixed, rate class ' . $this->calc->ltv() . '%' : 'Marktgemiddelde ' . $S->fixedY . ' jaar vast, tariefklasse ' . $this->calc->ltv() . '%'),

            'grossMonthly' => $this->fmt($C['grossMonthly']),
            'deltaLabel' => $renew ? $t['difference'] : ($en ? 'Of which interest' : 'Waarvan rente'),
            'deltaValue' => $renew
                ? (($up ? '+ ' : '− ') . '€ ' . $this->fmt(abs($delta)))
                : ('€ ' . $this->fmt($C['interestY1'])),

            'vormTitle' => $renew
                ? ($en ? 'Do you want to switch type?' : 'Wil je van vorm wisselen?')
                : ($en ? 'How do you want to repay?' : 'Hoe wil je aflossen?'),
            'vormIntro' => $renew
                ? ($en
                    ? 'When a new fixed-rate period starts you may usually convert your mortgage. Below is what each type does to your monthly cost.'
                    : 'Bij een nieuwe rentevaste periode mag je je hypotheek vaak omzetten. Hieronder zie je wat elke vorm met je maandlast doet.')
                : ($en
                    ? 'With annuity and linear the loan is fully gone after ' . $termY . ' years. The difference is in how your monthly cost moves.'
                    : 'Bij annuïtair en lineair is de lening na ' . $termY . ' jaar helemaal weg. Het verschil zit in het verloop van je maandlast.'),

            'io' => $this->fmt($io),
            'ioMaxLabel' => 'max € ' . $this->fmt($ioMax),
            'ioShare' => $io > 0
                ? round($io / max(1.0, $this->calc->homeValue()) * 100) . ($en ? '% of the property value · 50% maximum' : '% van de woningwaarde · maximaal 50%')
                : ($en ? 'Drag to make part of it interest-only' : 'Sleep om een deel aflossingsvrij te maken'),
            'ioIntro' => $renew
                ? ($en
                    ? 'You may keep or make part of your mortgage interest-only, up to half the property value. That lowers your monthly cost now, but the amount stays owed.'
                    : 'Je mag een deel van je hypotheek aflossingsvrij houden of maken, tot de helft van de woningwaarde. Dat verlaagt je maandlast nu, maar het bedrag blijft staan.')
                : ($en
                    ? 'On a new mortgage part of it may be interest-only, up to half the property value. Your monthly cost drops, but those euros get no interest relief and the debt is still there at the end.'
                    : 'Bij een nieuwe hypotheek mag een deel aflossingsvrij, tot de helft van de woningwaarde. Je maandlast gaat omlaag, maar over die euro’s krijg je geen renteaftrek en aan het eind staat de schuld er nog.'),
            'ioSaving' => $this->fmt($ioBesparing),
            'ioRate' => $this->dec($ioRate),
            'ioRateNote' => $en
                ? 'Lenders typically add ' . $this->dec(Constants::ioSurcharge()) . '% on an interest-only part, because nothing is being repaid against it.'
                : 'Geldverstrekkers rekenen op een aflossingsvrij deel doorgaans ' . $this->dec(Constants::ioSurcharge()) . '% opslag, omdat er geen aflossing tegenover staat.',
            'ioTaxNote' => ($renew && $S->preTwentyThirteen)
                ? ($en
                    ? 'Because your mortgage predates 2013, interest on this part stays deductible until your thirty years are up.'
                    : 'Omdat je hypotheek van vóór 2013 is, blijft de rente op dit deel aftrekbaar tot je dertig jaar vol zijn.')
                : ($en
                    ? 'You get no interest relief on this part. Since 2013 that only applies if you repay in full within thirty years.'
                    : 'Over dit deel krijg je geen hypotheekrenteaftrek. Die geldt sinds 2013 alleen als je binnen dertig jaar volledig aflost.'),

            'income' => $S->income > 0 ? $this->fmt($S->income) : '',
            'income2' => $S->income2 > 0 ? $this->fmt($S->income2) : '',
            'incomeTotal' => $this->fmt($C['income']),
            'woz' => $this->fmt($S->woz),
            'incomeNote' => $C['income2'] > 0
                ? ($en
                    ? 'Together € ' . $this->fmt($C['income']) . '. Relief is calculated at the rate of the higher of the two.'
                    : 'Samen € ' . $this->fmt($C['income']) . '. De aftrek rekenen we tegen het tarief van de hoogste van de twee.')
                : ($en ? 'If you have a tax partner, fill in the second income as well.' : 'Heb je een fiscaal partner, vul dan ook het tweede inkomen in.'),

            'costTotal' => $this->fmt($C['costTotal']),
            'taxTeaserTitle' => $C['income'] > 0
                ? ($en ? 'Marginal rate ' . $this->dec($C['marginal']) . '%' : 'Marginaal tarief ' . $this->dec($C['marginal']) . '%')
                : ($en ? 'Without an income we calculate gross' : 'Zonder inkomen rekenen we bruto door'),
            'taxTeaser' => $C['income'] > 0
                ? ($en
                    ? 'You should get roughly € ' . $this->fmt($C['benefit'] / 12) . ' back per month, after the imputed rental value is offset.'
                    : 'Je krijgt naar schatting € ' . $this->fmt($C['benefit'] / 12) . ' per maand terug, na verrekening van het eigenwoningforfait.')
                : ($en ? 'Add an income later and the net figure appears by itself.' : 'Vul je later alsnog een inkomen in, dan verschijnt de nettolast vanzelf.'),

            'heroLabel' => $C['income'] > 0 ? $t['heroNet'] : $t['heroGross'],
            'heroAmount' => $this->fmt($C['income'] > 0 ? $C['netTotal'] : $C['grossTotal']),
            'heroSub' => $C['income'] > 0
                ? ($en
                    ? 'Gross € ' . $this->fmt($C['grossTotal']) . ' · € ' . $this->fmt($C['benefit'] / 12) . ' per month back from the tax office'
                    : 'Bruto € ' . $this->fmt($C['grossTotal']) . ' · € ' . $this->fmt($C['benefit'] / 12) . ' per maand terug van de Belastingdienst')
                : ($en ? 'Add an income to see what you keep net' : 'Vul een inkomen in om te zien wat je netto overhoudt'),
            'totalLabel' => $C['income'] > 0 ? $t['netPerMonth'] : $t['grossPerMonth'],

            'chartNote' => $S->fixedY . ($en ? ' of ' : ' van ') . $termY . ($en ? ' yrs fixed' : ' jaar vast'),
            'chartEnd' => '+' . $termY . ' ' . $t['years'],
            'residualNote' => $en
                ? 'After ' . $termY . ' years € ' . $this->fmt($C['residual']) . ' is still owed from your interest-only part. You then have to repay it, refinance it, or settle it when the house is sold.'
                : 'Na ' . $termY . ' jaar staat er nog € ' . $this->fmt($C['residual']) . ' open uit je aflossingsvrije deel. Dat bedrag moet je dan aflossen, oversluiten of met de verkoop van de woning voldoen.',
            'tableUpsell' => $en
                ? 'Free shows six years. Premium shows all ' . $termY . ' years, broken down per month, with CSV export.'
                : 'Gratis zie je zes jaar. Premium toont alle ' . $termY . ' jaar, per maand uitgesplitst, met CSV-export.',
            'partsNote' => $prem
                ? ($en ? 'As many parts as you like, each with its own type and rate.' : 'Zoveel delen als je wilt, elk met een eigen vorm en rente.')
                : ($en ? 'Two parts on the free plan. Premium makes it unlimited.' : 'Gratis tot twee delen. Premium maakt het onbeperkt.'),

            'taxIntro' => $C['income'] > 0
                ? ($en
                    ? 'Mortgage interest relief minus the imputed rental value, at ' . Constants::TAX_YEAR . ' rates.'
                    : 'Hypotheekrenteaftrek min eigenwoningforfait, tegen de tarieven van ' . Constants::TAX_YEAR . '.')
                : ($en
                    ? 'Without an annual income this part drops out and the rest of the calculation keeps working.'
                    : 'Zonder jaarinkomen blijft dit deel weg en werkt de rest van de berekening gewoon.'),
            'taxResultLabel' => $C['income'] > 0 ? $t['taxBenefitMonth'] : $t['grossPerMonth'],
            'taxResult' => $this->fmt($C['income'] > 0 ? $C['benefit'] / 12 : $C['grossTotal']),
            'marginal' => $this->dec($C['marginal']),
            'capRate' => $this->dec(Constants::capRate()),

            'ratesNote' => $en
                ? 'Example rates for € ' . $this->fmt($total) . ', ' . $S->fixedY . ' years fixed, rate class ' . $this->calc->ltv() . '%. Not live market data.'
                : 'Voorbeeldtarieven voor € ' . $this->fmt($total) . ', ' . $S->fixedY . ' jaar vast, tariefklasse ' . $this->calc->ltv() . '%. Geen echte marktdata.',
            'disclaimer' => $en
                ? 'Indicative calculation, not financial advice. Tax figures are those of ' . Constants::TAX_YEAR . '; check them with the Dutch tax office before relying on the outcome.'
                : 'Indicatieve berekening, geen financieel advies. Fiscale tarieven zijn die van ' . Constants::TAX_YEAR . '; controleer ze bij de Belastingdienst voordat je op de uitkomst vertrouwt.',
        ];
    }

    /**
     * @param array<string, mixed> $C
     * @param list<array<string, string>> $formNamen
     * @return list<array<string, mixed>>
     */
    private function vormOpties(array $C, float $total, float $rate, float $io, array $formNamen): array
    {
        $S = $this->state;
        $uit = [];
        foreach ($formNamen as $i => $vorm) {
            $k = Constants::FORMS[$i];
            $eerste = Calculator::firstPay([
                'form' => $k, 'sum' => $total - $io, 'rate' => $rate, 'term' => $C['termY'],
                'elapsed' => 0, 'deductible' => true, 'dedMonths' => 360,
            ]);
            if ($io > 0) {
                $eerste += Calculator::firstPay([
                    'form' => 'av', 'sum' => $io, 'rate' => $this->calc->ioRate(), 'term' => $C['termY'],
                    'elapsed' => 0, 'deductible' => false, 'dedMonths' => 360,
                ]);
            }

            $uit[] = [
                'key' => $k, 'name' => $vorm['name'], 'desc' => $vorm['desc'],
                'b' => self::sel($S->form === $k)['b'], 'bg' => self::sel($S->form === $k)['bg'],
                'first' => $this->fmt($eerste),
                'flag' => $k === 'av', 'flagText' => $this->t['ioFlag'],
            ];
        }

        return $uit;
    }

    /**
     * @param array<string, mixed> $C
     * @param list<array<string, string>> $formNamen
     * @return list<array<string, mixed>>
     */
    private function delen(array $C, array $formNamen): array
    {
        $S = $this->state;
        $uit = [];
        foreach ($C['parts'] as $idx => $deel) {
            $rows = $C['scheds'][$idx]->rows;
            $vormen = [];
            foreach ($formNamen as $i => $vorm) {
                $k = Constants::FORMS[$i];
                $vormen[] = [
                    'key' => $k, 'short' => $vorm['short'],
                    'b' => self::sel($deel['form'] === $k)['b'], 'bg' => self::sel($deel['form'] === $k)['bg'],
                ];
            }

            $uit[] = [
                'id' => $deel['id'],
                'title' => $this->t['part'] . ' ' . ($idx + 1),
                'canRemove' => count($C['parts']) > 1,
                'sum' => $this->fmt((float)$deel['sum']),
                'rate' => $this->dec((float)$deel['rate']),
                'term' => $deel['term'],
                'elapsed' => $deel['elapsed'],
                'deductible' => !empty($deel['deductible']),
                'first' => $this->fmt($rows === [] ? 0.0 : $rows[0]->bruto()),
                'dedB' => !empty($deel['deductible']) ? 'var(--accent)' : 'var(--line2)',
                'dedFill' => !empty($deel['deductible']) ? 'var(--accent)' : 'transparent',
                'forms' => $vormen,
                'form' => $deel['form'],
                'dedMonths' => $deel['dedMonths'] ?? 360,
            ];
        }

        return $uit;
    }

    /**
     * @param list<array{id:int,label:string,amount:float}> $costs
     * @return list<array{label:string,amount:float}>
     */
    private function suggesties(array $costs): array
    {
        $gebruikt = array_column($costs, 'label');
        $uit = [];
        foreach ($this->t['costNames'] as $rij) {
            if (!in_array($rij[0], $gebruikt, true)) {
                $uit[] = ['label' => $rij[0], 'amount' => (float)$rij[1]];
            }
        }

        return $uit;
    }

    /**
     * @param array<string, mixed> $C
     * @return list<array<string, mixed>>
     */
    private function verstrekkers(array $C, float $rate): array
    {
        $rijen = \App\Models\Lender::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $uit = [];

        foreach ($rijen as $rij) {
            $d = $rij->delta;
            $r = round(($rate + $d) * 100) / 100;
            $verschil = $this->calc->atRate($r) - $C['grossMonthly'];

            $logo = $rij->logoUrl();
            $uit[] = [
                'index' => $rij->id,
                'name' => $rij->name,
                'note' => (string)($rij->description ?? ''),
                'logo' => $logo,
                'noLogo' => $logo === null,
                'initial' => mb_substr($rij->name, 0, 1),
                'rate' => $this->dec($r),
                'delta' => ($d < 0 ? '−' : '+') . ' € ' . $this->fmt(abs($verschil)) . ' p/m',
                'deltaC' => $d < 0 ? 'var(--accent)' : 'var(--warn)',
                'b' => $this->state->lender === $rij->id ? 'var(--gold-line)' : 'var(--line)',
                'bg' => $this->state->lender === $rij->id ? 'var(--gold-soft)' : 'var(--surface)',
                'rateValue' => $r,
            ];
        }

        return $uit;
    }

    /**
     * @param array<string, mixed> $C
     * @return list<array<string, string>>
     */
    private function scenarios(array $C, float $total, float $rate, float $io): array
    {
        $S = $this->state;
        $en = $this->en;
        $omhoog = $this->calc->afterFix(1.0);
        $omlaag = $this->calc->afterFix(-1.0);
        $totaleRente = 0.0;
        foreach ($C['years'] as $jaar) {
            $totaleRente += $jaar['interest'];
        }

        return [
            [
                'l' => $en ? 'Rate +1% after the fixed period' : 'Rente +1% na de vaste periode',
                'note' => ($en ? 'from year ' : 'vanaf jaar ') . ($S->fixedY + 1),
                'v' => $this->fmt($omhoog),
                'd' => '+ € ' . $this->fmt(max(0.0, $omhoog - $C['grossMonthly'])),
                'dc' => 'var(--warn)',
            ],
            [
                'l' => $en ? 'Rate −1% after the fixed period' : 'Rente −1% na de vaste periode',
                'note' => ($en ? 'from year ' : 'vanaf jaar ') . ($S->fixedY + 1),
                'v' => $this->fmt($omlaag),
                'd' => '− € ' . $this->fmt(max(0.0, $C['grossMonthly'] - $omlaag)),
                'dc' => 'var(--accent)',
            ],
            [
                'l' => $en ? 'Repay everything on annuity' : 'Alles annuïtair aflossen',
                'note' => $en ? 'no interest-only part' : 'geen aflossingsvrij deel',
                'v' => $this->fmt(Calculator::firstPay([
                    'form' => 'ann', 'sum' => $total, 'rate' => $rate, 'term' => $C['termY'],
                    'elapsed' => 0, 'deductible' => true, 'dedMonths' => 360,
                ])),
                'd' => $io > 0
                    ? ($en ? 'nothing left owing' : 'restschuld € 0')
                    : ($en ? 'current choice' : 'huidige keuze'),
                'dc' => 'var(--ink3)',
            ],
            [
                'l' => $en ? 'Total interest over the term' : 'Totale rente over de looptijd',
                'note' => $en ? 'at these settings' : 'bij deze instellingen',
                'v' => $this->fmt($totaleRente),
                'd' => '',
                'dc' => 'var(--ink3)',
            ],
        ];
    }
}
