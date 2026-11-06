/**
 * Hilfen für die SVG-Diagramme (ohne Vue-Abhängigkeit, daher gut testbar).
 */

/** Saubere Achsenwerte von 0 bis mindestens max, mit 1-2-5-Schritten (z. B. 0/10/20/30/40). */
export function niceTicks(max, targetCount = 4) {
    if (!Number.isFinite(max) || max <= 0) {
        return [0, 1];
    }

    const rough = max / targetCount;
    const magnitude = 10 ** Math.floor(Math.log10(rough));
    const step = [1, 2, 5, 10].map((m) => m * magnitude).find((s) => s >= rough);
    const ticks = [];

    for (let value = 0; value < max + step; value += step) {
        ticks.push(value);
        if (value >= max) break;
    }

    return ticks;
}

/**
 * Jede wievielte x-Beschriftung passt (1, 2, 5, 10 …), damit Beschriftungen mindestens
 * minSpacing Pixel auseinander liegen. Jahre werden so auf runde Werte ausgerichtet.
 */
export function labelStep(count, width, minSpacing = 40) {
    if (count <= 0 || width <= 0) {
        return 1;
    }

    const perItem = width / count;

    return [1, 2, 5, 10, 20, 50].find((step) => step * perItem >= minSpacing) ?? 50;
}

/**
 * Pfad einer Säule: oben (Datenende) mit Radius r abgerundet, unten an der Grundlinie eckig.
 * Ist die Säule niedriger als der Radius, wird er verkleinert.
 */
export function columnPath(x, y, width, height, radius = 4) {
    if (height <= 0 || width <= 0) {
        return '';
    }

    const r = Math.min(radius, width / 2, height);
    const bottom = y + height;

    return [
        `M${x},${bottom}`,
        `V${y + r}`,
        `Q${x},${y} ${x + r},${y}`,
        `H${x + width - r}`,
        `Q${x + width},${y} ${x + width},${y + r}`,
        `V${bottom}`,
        'Z',
    ].join(' ');
}

/** Säulenbreite: höchstens maxWidth, sonst Platz je Säule minus Abstand (mindestens 1 px). */
export function columnWidth(slot, maxWidth = 24, gap = 2) {
    return Math.max(1, Math.min(maxWidth, slot - gap));
}
