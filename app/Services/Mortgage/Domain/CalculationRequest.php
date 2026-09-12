<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain;

/**
 * Vertaalt ruwe formulierinvoer naar domeinobjecten en houdt de ingevulde
 * waarden vast zodat het formulier opnieuw getoond kan worden.
 */
final class CalculationRequest
{
    /** @var array<int, array<string, mixed>> */
    public array $leningdelen = [];
    /** @var array<int, array<string, mixed>> */
    public array $maandkosten = [];

    public int $startJaar;
    public int $startMaand;
    public ?float $inkomen = null;
    public float $inkomenIndexatie = 0.0;
    public float $wozWaarde = 0.0;
    public bool $toonMaandDetail = false;

    /** @var list<string> */
    public array $fouten = [];

    private function __construct()
    {
        $this->startJaar = (int)date('Y');
        $this->startMaand = (int)date('n');
    }

    public static function leeg(): self
    {
        $req = new self();
        $req->leningdelen = [
            [
                'naam' => 'Leningdeel 1', 'type' => LoanPart::TYPE_ANNUITAIR,
                'hoofdsom' => '250000', 'rente' => '3,9', 'looptijd_jaren' => '30',
                'verstreken_maanden' => '0', 'aftrekbaar' => '1',
            ],
            [
                'naam' => 'Leningdeel 2', 'type' => LoanPart::TYPE_AFLOSSINGSVRIJ,
                'hoofdsom' => '100000', 'rente' => '4,1', 'looptijd_jaren' => '30',
                'verstreken_maanden' => '0', 'aftrekbaar' => '',
            ],
        ];
        $req->maandkosten = [
            ['omschrijving' => 'Opstalverzekering', 'bedrag' => ''],
            ['omschrijving' => 'Gemeentelijke lasten', 'bedrag' => ''],
        ];

        return $req;
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function fromPost(array $post): self
    {
        $req = new self();

        $req->startJaar  = Input::toInt($post['start_jaar'] ?? '', (int)date('Y'));
        $req->startMaand = min(12, max(1, Input::toInt($post['start_maand'] ?? '', (int)date('n'))));

        $delen = $post['leningdeel'] ?? [];
        if (!is_array($delen) || $delen === []) {
            $delen = [];
        }
        foreach ($delen as $deel) {
            if (!is_array($deel)) {
                continue;
            }
            // Volledig lege regels worden stilzwijgend genegeerd.
            if (trim((string)($deel['hoofdsom'] ?? '')) === '' && trim((string)($deel['naam'] ?? '')) === '') {
                continue;
            }
            $req->leningdelen[] = [
                'naam'               => (string)($deel['naam'] ?? ''),
                'type'               => (string)($deel['type'] ?? LoanPart::TYPE_ANNUITAIR),
                'hoofdsom'           => (string)($deel['hoofdsom'] ?? ''),
                'rente'              => (string)($deel['rente'] ?? ''),
                'looptijd_jaren'     => (string)($deel['looptijd_jaren'] ?? '30'),
                'verstreken_maanden' => (string)($deel['verstreken_maanden'] ?? '0'),
                'aftrekbaar'         => !empty($deel['aftrekbaar']) ? '1' : '',
            ];
        }

        $kosten = $post['maandkosten'] ?? [];
        if (is_array($kosten)) {
            foreach ($kosten as $kost) {
                if (!is_array($kost)) {
                    continue;
                }
                $omschrijving = trim((string)($kost['omschrijving'] ?? ''));
                $bedrag = (string)($kost['bedrag'] ?? '');
                if ($omschrijving === '' && trim($bedrag) === '') {
                    continue;
                }
                $req->maandkosten[] = ['omschrijving' => $omschrijving, 'bedrag' => $bedrag];
            }
        }

        $inkomenRuw = trim((string)($post['inkomen'] ?? ''));
        if ($inkomenRuw !== '') {
            $req->inkomen = max(0.0, Input::toFloat($inkomenRuw));
        }
        $req->inkomenIndexatie = Input::toFloat($post['inkomen_indexatie'] ?? 0);
        $req->wozWaarde = max(0.0, Input::toFloat($post['woz'] ?? 0));
        $req->toonMaandDetail = !empty($post['toon_maand_detail']);

        return $req;
    }

    /** @return list<LoanPart> */
    public function loanParts(): array
    {
        $parts = [];
        foreach ($this->leningdelen as $i => $deel) {
            $parts[] = LoanPart::fromArray($deel, $i + 1);
        }

        return $parts;
    }

    /** @return list<MonthlyCost> */
    public function monthlyCosts(): array
    {
        $kosten = [];
        foreach ($this->maandkosten as $kost) {
            $bedrag = Input::toFloat($kost['bedrag'] ?? 0);
            if ($bedrag <= 0.0) {
                continue;
            }
            $omschrijving = trim((string)($kost['omschrijving'] ?? ''));
            $kosten[] = new MonthlyCost($omschrijving !== '' ? $omschrijving : 'Overige kosten', $bedrag);
        }

        return $kosten;
    }

    public function fiscaalMogelijk(): bool
    {
        return $this->inkomen !== null && $this->inkomen > 0.0;
    }

    /**
     * Inkomen per kalenderjaar, eventueel geïndexeerd. Null wanneer er geen
     * inkomen is opgegeven: de rest van de berekening werkt dan gewoon door.
     *
     * @return array<int,float>|null
     */
    public function inkomenPerJaar(int $vanJaar, int $totJaar): ?array
    {
        if (!$this->fiscaalMogelijk()) {
            return null;
        }

        $groei = 1 + $this->inkomenIndexatie / 100;
        $reeks = [];
        for ($jaar = $vanJaar; $jaar <= $totJaar; $jaar++) {
            $reeks[$jaar] = $this->inkomen * $groei ** ($jaar - $vanJaar);
        }

        return $reeks;
    }

    public function valideer(): bool
    {
        $this->fouten = [];

        $parts = $this->loanParts();
        if ($parts === []) {
            $this->fouten[] = 'Voeg minimaal één leningdeel met een hoofdsom toe.';
        }
        foreach ($parts as $part) {
            foreach ($part->validatieFouten() as $fout) {
                $this->fouten[] = $fout;
            }
        }
        if ($this->startJaar < 1970 || $this->startJaar > 2200) {
            $this->fouten[] = 'Startjaar moet tussen 1970 en 2200 liggen.';
        }
        if ($this->inkomenIndexatie < -20 || $this->inkomenIndexatie > 20) {
            $this->fouten[] = 'Indexatie van het inkomen moet tussen -20% en 20% liggen.';
        }

        return $this->fouten === [];
    }
}
