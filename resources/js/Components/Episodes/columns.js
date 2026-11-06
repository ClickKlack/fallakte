/**
 * Spaltenraster der Folgenliste ab Tablet-Breite (md). Kopfzeile und Zeilen nutzen dieselben Klassen,
 * damit alles exakt untereinander steht. Sind beide Reihen gelistet, steht vor der Nummer das
 * Reihen-Symbol – die Nummernspalte ist dann etwas breiter.
 */
export const gridClass = (withSeries) =>
    withSeries
        ? 'md:grid md:grid-cols-[5rem_minmax(0,1fr)_8rem_5.5rem_minmax(0,13rem)]'
        : 'md:grid md:grid-cols-[3.5rem_minmax(0,1fr)_8rem_5.5rem_minmax(0,13rem)]';

/** Breite der festen Spalten links: Haken + „gesehen am“. */
export const SEEN_COLUMN = 'w-[5.5rem] sm:w-32';
