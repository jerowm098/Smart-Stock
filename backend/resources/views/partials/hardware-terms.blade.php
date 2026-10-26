{{--
    Shared product-name helpers.

    Store staff often type product names in Filipino ("pako", "martilyo",
    "turnilyo"). The NAME is saved exactly as typed, but everything the system
    derives from it — the auto-generated SKU prefix and the suggested category —
    is built from the English meaning, so codes and categories stay consistent
    with the rest of the catalogue.

    Included by the Products page (Add/Edit product modals) and the Stock-In
    page (Receive Stock modal) so both suggest identically.

    Exposes window.SmartStockLexicon:
        translate(name)               -> English form of the typed name
        skuPrefix(name)               -> 3-letter code prefix ("NAI")
        suggestSku(name)              -> "NAI-4F2A19"
        categoryFor(name, products)   -> "Fasteners" | "Uncategorized"
--}}
<script>
    window.SmartStockLexicon = (function () {
        const TERMS = @json($terms ?? []);
        const KEYWORDS = @json($keywords ?? []);
        const ALIASES = @json($aliases ?? []);

        // Multi-word phrases must win over their single words
        // ("pipe wrench" before "pipe"), so match longest first.
        const PHRASES = Object.keys(TERMS)
            .map(phrase => ({ phrase, words: phrase.split(' '), value: TERMS[phrase] }))
            .sort((a, b) => b.words.length - a.words.length || b.phrase.length - a.phrase.length);

        const KEYWORD_LIST = Object.keys(KEYWORDS)
            .map(keyword => ({ keyword, category: KEYWORDS[keyword] }))
            .sort((a, b) => b.keyword.length - a.keyword.length);

        /** Lowercase, collapse whitespace, keep letters/digits/spaces only. */
        function normalize(text) {
            return String(text || '')
                .toLowerCase()
                .replace(/[^a-z0-9\s]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        }

        /**
         * Rewrites Filipino/Tagalog hardware words into English, leaving
         * everything it does not recognise untouched (brand names, sizes,
         * model numbers).
         */
        function translate(name) {
            let tokens = normalize(name).split(' ').filter(Boolean);
            if (!tokens.length) return '';

            for (const { words, value } of PHRASES) {
                const len = words.length;
                const out = [];
                for (let i = 0; i < tokens.length; i++) {
                    if (i + len <= tokens.length && tokens.slice(i, i + len).join(' ') === words.join(' ')) {
                        if (value) out.push(value);
                        i += len - 1;
                    } else {
                        out.push(tokens[i]);
                    }
                }
                tokens = out;
            }

            return tokens.join(' ').trim();
        }

        /** English words of a typed name, for fuzzy catalogue matching. */
        function englishWords(name) {
            return translate(name).split(' ').filter(word => word.length >= 3);
        }

        /** Canonical spelling of a category, so "Nail" and "Fasteners" agree. */
        function canonicalCategory(category) {
            const key = normalize(category);
            if (!key) return '';
            return ALIASES[key] || String(category || '').trim();
        }

        /** 3-letter prefix taken from the ENGLISH name (mirrors the server). */
        function skuPrefix(name) {
            const source = translate(name) || String(name || '');
            const letters = source.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            return letters.slice(0, 3) || 'NEW';
        }

        /** "pako" -> "NAI-4F2A19" (same shape as the server's generateSku). */
        function suggestSku(name) {
            const bytes = new Uint8Array(4);
            if (window.crypto && window.crypto.getRandomValues) {
                window.crypto.getRandomValues(bytes);
            } else {
                for (let i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 256);
            }
            const rand = Array.from(bytes)
                .map(b => b.toString(16).padStart(2, '0'))
                .join('')
                .slice(0, 6)
                .toUpperCase();
            return skuPrefix(name) + '-' + rand;
        }

        /**
         * Recommended category for a typed name.
         *
         * 1. The curated English keyword map (most confident, longest match).
         * 2. A category already used by a catalogue product sharing a strong
         *    English word with the typed name.
         * 3. "Uncategorized".
         */
        function categoryFor(name, products) {
            const english = translate(name);
            if (!english) return 'Uncategorized';

            for (const { keyword, category } of KEYWORD_LIST) {
                const pattern = new RegExp('(^|\\s)' + keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '(\\s|$)');
                if (pattern.test(english)) return category;
            }

            const words = englishWords(name);
            if (words.length && Array.isArray(products)) {
                for (const product of products) {
                    const category = canonicalCategory(product.category);
                    if (!category || category.toLowerCase() === 'uncategorized') continue;
                    const productWords = englishWords(product.name);
                    if (words.some(word => productWords.includes(word))) return category;
                }
            }

            return 'Uncategorized';
        }

        return { translate, englishWords, skuPrefix, suggestSku, categoryFor, canonicalCategory };
    })();
</script>
