#!/usr/bin/env python3
"""Vergelijkt twee PNG's pixel voor pixel.

Puur Python: decodeert de PNG zelf, zodat er geen Pillow of ImageMagick nodig
is. Bedoeld om schermen van de gebouwde site te vergelijken met schermen van
het Claude Design-ontwerp.

    python3 tests/pixeldiff.py ontwerp.png site.png [--tolerantie 8] [--uit diff.png]
                               [--negeer x1,y1,x2,y2]

Met --negeer blijft een rechthoek buiten beschouwing; dat is nodig voor het
"Made with Claude Design"-label dat de ontwerptool zelf over de pagina legt.

Afwijkende pixels worden geteld met een drempel per kanaal; met --uit schrijft
het script een rood masker weg van de verschillen.
"""
import struct
import sys
import zlib


def lees_png(pad):
    data = open(pad, 'rb').read()
    if data[:8] != b'\x89PNG\r\n\x1a\n':
        raise SystemExit(f'{pad}: geen PNG')
    pos, idat, breedte, hoogte, diepte, kleur = 8, b'', 0, 0, 0, 0
    while pos < len(data):
        lengte = struct.unpack('>I', data[pos:pos + 4])[0]
        soort = data[pos + 4:pos + 8]
        blok = data[pos + 8:pos + 8 + lengte]
        if soort == b'IHDR':
            breedte, hoogte, diepte, kleur = struct.unpack('>IIBB', blok[:10])
        elif soort == b'IDAT':
            idat += blok
        pos += 12 + lengte
    if diepte != 8 or kleur not in (2, 6):
        raise SystemExit(f'{pad}: alleen 8-bits RGB(A) wordt ondersteund')

    kanalen = 3 if kleur == 2 else 4
    ruw = zlib.decompress(idat)
    stap = breedte * kanalen
    vorige = bytearray(stap)
    regels = []
    i = 0
    for _ in range(hoogte):
        filter_type = ruw[i]
        i += 1
        regel = bytearray(ruw[i:i + stap])
        i += stap
        for x in range(stap):
            a = regel[x - kanalen] if x >= kanalen else 0
            b = vorige[x]
            c = vorige[x - kanalen] if x >= kanalen else 0
            if filter_type == 1:
                regel[x] = (regel[x] + a) & 255
            elif filter_type == 2:
                regel[x] = (regel[x] + b) & 255
            elif filter_type == 3:
                regel[x] = (regel[x] + (a + b) // 2) & 255
            elif filter_type == 4:
                p = a + b - c
                pa, pb, pc = abs(p - a), abs(p - b), abs(p - c)
                pr = a if (pa <= pb and pa <= pc) else (b if pb <= pc else c)
                regel[x] = (regel[x] + pr) & 255
        vorige = regel
        regels.append(bytes(regel))
    return breedte, hoogte, kanalen, regels


def schrijf_png(pad, breedte, hoogte, regels_rgb):
    ruw = b''.join(b'\x00' + r for r in regels_rgb)
    blokken = b''
    for soort, inhoud in [
        (b'IHDR', struct.pack('>IIBBBBB', breedte, hoogte, 8, 2, 0, 0, 0)),
        (b'IDAT', zlib.compress(ruw, 6)),
        (b'IEND', b''),
    ]:
        blokken += struct.pack('>I', len(inhoud)) + soort + inhoud
        blokken += struct.pack('>I', zlib.crc32(soort + inhoud) & 0xffffffff)
    open(pad, 'wb').write(b'\x89PNG\r\n\x1a\n' + blokken)


def inktaandeel(regels, breedte, hoogte, kanalen):
    """Aandeel pixels dat duidelijk afwijkt van wit; vangt lege schermen af."""
    geteld = 0
    bekeken = 0
    for y in range(0, hoogte, 3):
        regel = regels[y]
        for x in range(0, breedte, 3):
            p = regel[x * kanalen:x * kanalen + 3]
            bekeken += 1
            if min(p) < 235:
                geteld += 1
    return geteld / max(1, bekeken)


def main():
    args = sys.argv[1:]
    if len(args) < 2:
        raise SystemExit(__doc__)
    tolerantie = 8
    uit = None
    if '--tolerantie' in args:
        i = args.index('--tolerantie')
        tolerantie = int(args[i + 1])
        del args[i:i + 2]
    negeer = []
    while '--negeer' in args:
        i = args.index('--negeer')
        x1, y1, x2, y2 = (int(n) for n in args[i + 1].split(','))
        negeer.append((x1, y1, x2, y2))
        del args[i:i + 2]
    if '--uit' in args:
        i = args.index('--uit')
        uit = args[i + 1]
        del args[i:i + 2]

    b1, h1, k1, r1 = lees_png(args[0])
    b2, h2, k2, r2 = lees_png(args[1])
    breedte, hoogte = min(b1, b2), min(h1, h2)
    if (b1, h1) != (b2, h2):
        print(f'formaat verschilt: {b1}x{h1} vs {b2}x{h2}; vergelijk {breedte}x{hoogte}')

    anders = 0
    diff = []
    for y in range(hoogte):
        rij1, rij2 = r1[y], r2[y]
        uitrij = bytearray(breedte * 3)
        for x in range(breedte):
            p1 = rij1[x * k1:x * k1 + 3]
            p2 = rij2[x * k2:x * k2 + 3]
            verschil = max(abs(p1[0] - p2[0]), abs(p1[1] - p2[1]), abs(p1[2] - p2[2]))
            if any(x1 <= x <= x2 and y1 <= y <= y2 for x1, y1, x2, y2 in negeer):
                verschil = 0
            if verschil > tolerantie:
                anders += 1
                uitrij[x * 3] = 255
            else:
                grijs = (p1[0] + p1[1] + p1[2]) // 3
                licht = 200 + grijs // 5
                uitrij[x * 3] = uitrij[x * 3 + 1] = uitrij[x * 3 + 2] = min(255, licht)
        diff.append(bytes(uitrij))

    totaal = breedte * hoogte
    inkt1 = inktaandeel(r1, breedte, hoogte, k1)
    inkt2 = inktaandeel(r2, breedte, hoogte, k2)
    print(f'{anders} van {totaal} pixels anders ({anders / totaal * 100:.3f}%), tolerantie {tolerantie}'
          f' | inkt {inkt1 * 100:.1f}% vs {inkt2 * 100:.1f}%')
    if inkt1 < 0.005 or inkt2 < 0.005:
        print('LET OP: een van beide afbeeldingen is vrijwel leeg - vergelijking zegt niets')
        return 1
    if uit:
        schrijf_png(uit, breedte, hoogte, diff)
        print(f'masker: {uit}')
    return 0 if anders / totaal < 0.01 else 1


if __name__ == '__main__':
    sys.exit(main())
