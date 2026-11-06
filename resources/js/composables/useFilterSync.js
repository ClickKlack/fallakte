import { reactive, watch } from 'vue';
import { normalizeFilters, teamMatchesSeries, toQuery } from '@/lib/filters.js';

/**
 * Hält das Filterformular und die URL synchron.
 * - Jede Änderung ruft navigate(query) auf; die Titelsuche erst nach einer kurzen Tipp-Pause.
 * - Wechselt die Reihe, wird ein unpassender Teamfilter entfernt.
 * - setFromServer() übernimmt neue Werte (z. B. nach Zurück/Vor im Browser), ohne erneut zu navigieren.
 *
 * @param {object} initial  Filter aus den Server-Props
 * @param {(query: object) => void} navigate
 * @param {{ teams?: Array, debounceMs?: number }} options
 */
export function useFilterSync(initial, navigate, { teams = [], debounceMs = 350 } = {}) {
    const form = reactive(normalizeFilters(initial));
    let suppress = false;
    let timer = null;

    const send = () => {
        clearTimeout(timer);
        timer = null;
        navigate(toQuery(form));
    };

    // Teamfilter entfernen, wenn er nicht zur gewählten Reihe passt (ohne eigene Navigation)
    const dropForeignTeam = () => {
        if (!teamMatchesSeries(teams, form.team, form.series)) {
            suppress = true;
            form.team = null;
            suppress = false;
        }
    };

    watch(
        () => ({ ...form }),
        (now, before) => {
            if (suppress) {
                return;
            }

            dropForeignTeam();

            const onlySearchChanged = Object.keys(now).every(
                (key) => key === 'q' || now[key] === before[key],
            );

            if (onlySearchChanged) {
                clearTimeout(timer);
                timer = setTimeout(send, debounceMs);
            } else {
                send();
            }
        },
        { flush: 'sync' },
    );

    function setFromServer(filters) {
        suppress = true;
        Object.assign(form, normalizeFilters(filters));
        suppress = false;
    }

    /** Übernimmt mehrere Werte auf einmal (z. B. Sortierung + Richtung, Zurücksetzen) mit nur einer Navigation. */
    function update(values) {
        suppress = true;
        Object.assign(form, values);
        suppress = false;
        dropForeignTeam();
        send();
    }

    return { form, setFromServer, update, flush: () => timer && send() };
}
