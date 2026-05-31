import katex from 'katex';
import 'katex/dist/katex.min.css';

export function normalizeLatex(value) {
    const raw = String(value ?? '').trim();

    if ((raw.startsWith('$$') && raw.endsWith('$$')) || (raw.startsWith('\\[') && raw.endsWith('\\]'))) {
        return raw.slice(2, -2).trim();
    }

    if (raw.startsWith('\\(') && raw.endsWith('\\)')) {
        return raw.slice(2, -2).trim();
    }

    if (raw.startsWith('$') && raw.endsWith('$')) {
        return raw.slice(1, -1).trim();
    }

    return raw;
}

export function renderFormulaMarkup(value, displayMode = false) {
    const latex = normalizeLatex(value);

    if (!latex) {
        return '<span class="text-slate-500">No formula</span>';
    }

    return katex.renderToString(latex, {
        displayMode,
        throwOnError: false,
        strict: false,
        trust: false,
    });
}
