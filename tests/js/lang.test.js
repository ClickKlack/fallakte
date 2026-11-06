import { describe, expect, it } from 'vitest';
import { t, translate } from '@/lang';
import de from '@/lang/de.js';

describe('translate', () => {
    const messages = {
        greeting: 'Hallo :name, du hast :count Folgen gesehen (:count!)',
        nested: { deeper: { text: 'Tief verschachtelt' } },
    };

    it('resolves nested keys in dot notation', () => {
        expect(translate(messages, 'nested.deeper.text')).toBe('Tief verschachtelt');
    });

    it('replaces all occurrences of placeholders', () => {
        expect(translate(messages, 'greeting', { name: 'Ami', count: 3 })).toBe(
            'Hallo Ami, du hast 3 Folgen gesehen (3!)',
        );
    });

    it('returns the key for unknown keys', () => {
        expect(translate(messages, 'does.not.exist')).toBe('does.not.exist');
    });

    it('returns the key when the key points to a group instead of a text', () => {
        expect(translate(messages, 'nested')).toBe('nested');
    });

    it('returns the key when walking into a text value', () => {
        expect(translate(messages, 'greeting.more')).toBe('greeting.more');
    });
});

describe('t', () => {
    it('uses the German messages', () => {
        expect(t('auth.login')).toBe(de.auth.login);
        expect(t('nav.logout')).toBe('Abmelden');
    });
});

describe('German messages', () => {
    const collectLeaves = (node, prefix = '') =>
        Object.entries(node).flatMap(([key, value]) =>
            typeof value === 'object'
                ? collectLeaves(value, `${prefix}${key}.`)
                : [[`${prefix}${key}`, value]],
        );

    it('contains only non-empty strings', () => {
        for (const [key, value] of collectLeaves(de)) {
            expect(typeof value, key).toBe('string');
            expect(value.trim(), key).not.toBe('');
        }
    });
});
