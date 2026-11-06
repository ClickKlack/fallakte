/**
 * Zentrale deutsche UI-Texte des Frontends.
 * Schlüssel sind englisch und nach Bereichen gruppiert; Platzhalter im Format :name.
 */
export default {
    nav: {
        episodes: 'Folgen',
        dashboard: 'Dashboard',
        statistics: 'Statistik',
        profile: 'Profil',
        logout: 'Abmelden',
        menu: 'Menü',
    },
    auth: {
        login_title: 'Anmelden',
        email: 'E-Mail',
        password: 'Passwort',
        remember: 'Angemeldet bleiben',
        login: 'Anmelden',
        confirm_title: 'Passwort bestätigen',
        confirm_hint:
            'Dies ist ein geschützter Bereich. Bitte bestätige dein Passwort, um fortzufahren.',
        confirm: 'Bestätigen',
    },
    episodes: {
        title: 'Folgen',
        count: ':count Folgen',
    },
    dashboard: {
        title: 'Statistik',
        coming_soon: 'Hier entsteht die Statistik zu deinen gesehenen Folgen.',
    },
    status: {
        announced: 'Angekündigt',
    },
    seen: {
        toggle: 'Als gesehen markieren',
        toggle_label: '„:title“ als gesehen markieren',
        not_yet_aired: 'Noch nicht ausgestrahlt',
        change_date: 'Datum ändern',
        no_date: 'ohne Datum',
        seen_on: 'gesehen am :date',
        seen_without_date: 'gesehen, ohne Datum',
        dialog_title: 'Gesehen am',
    },
    datepicker: {
        previous_month: 'Vorheriger Monat',
        next_month: 'Nächster Monat',
        month: 'Monat',
        year: 'Jahr',
        today: 'Heute',
        no_date: 'Ohne Datum',
    },
    progress: {
        of: ':done von :total gesehen',
    },
    filters: {
        title: 'Filter',
    },
    pagination: {
        label: 'Seiten',
        previous: 'Zurück',
        next: 'Weiter',
        page_of: 'Seite :page von :pages',
    },
    profile: {
        title: 'Profil',
        info_title: 'Profilinformationen',
        info_hint: 'Name und E-Mail-Adresse deines Kontos ändern.',
        name: 'Name',
        email: 'E-Mail',
        password_title: 'Passwort ändern',
        password_hint: 'Verwende ein langes, zufälliges Passwort.',
        current_password: 'Aktuelles Passwort',
        new_password: 'Neues Passwort',
        confirm_password: 'Passwort bestätigen',
    },
    common: {
        save: 'Speichern',
        cancel: 'Abbrechen',
        saved: 'Gespeichert.',
    },
};
