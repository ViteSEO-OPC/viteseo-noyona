/**
 * Mosaic Grid block editor script.
 */
(function () {
    if (!window.wp || !wp.blocks || !wp.blockEditor || !wp.element) {
        return;
    }

    const { registerBlockType } = wp.blocks;
    const { RichText, useBlockProps } = wp.blockEditor;
    const { createElement: el } = wp.element;

    const TILE_ORDER = ['tl', 'tb', 'tg', 'tr', 'll', 'cc', 'rr', 'bb', 'bg'];
    const DEFAULT_TILES = {
        tl: {
            heading: 'Heading',
            body: 'Your content goes here. Edit or remove this text inline.',
        },
        tb: {
            heading: 'Heading',
            body: 'Your content goes here.',
        },
        tg: {
            heading: 'Heading',
            body: 'Your content goes here.',
        },
        tr: {
            heading: 'Heading',
            body: 'Your content goes here.',
        },
        ll: {
            heading: 'Heading',
            body: 'Your content goes here.',
        },
        cc: {
            heading: 'Heading',
            body: 'Your content goes here. Edit or remove this text inline in the module Content settings.',
        },
        rr: {
            heading: 'Heading',
            body: 'Your content goes here. Edit or remove this text inline.',
        },
        bb: {
            heading: 'Heading',
            body: 'Your content goes here.',
        },
        bg: {
            heading: 'Heading',
            body: 'Your content goes here.',
        },
    };

    function normalizeTiles(tiles) {
        const list = Array.isArray(tiles) ? tiles : [];
        return TILE_ORDER.map((id) => {
            const existing = list.find((tile) => tile.id === id) || {};
            const defaults = DEFAULT_TILES[id] || { heading: '', body: '' };
            return {
                id: id,
                heading: typeof existing.heading === 'string' ? existing.heading : defaults.heading,
                body: typeof existing.body === 'string' ? existing.body : defaults.body,
            };
        });
    }

    registerBlockType('noyona/mosaic-grid', {
        title: 'Mosaic Grid',
        icon: 'grid-view',
        category: 'design',
        description: 'Mosaic grid of editorial tiles.',
        supports: {
            align: ['full', 'wide'],
            html: false,
        },
        attributes: {
            tiles: {
                type: 'array',
                default: TILE_ORDER.map((id) => ({
                    id: id,
                    heading: DEFAULT_TILES[id].heading,
                    body: DEFAULT_TILES[id].body,
                })),
            },
        },
        edit: function (props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps({
                className: 'mosaic-grid alignwide',
            });
            const tiles = normalizeTiles(attributes.tiles);

            function updateTile(id, field, value) {
                const next = tiles.map((tile) => {
                    if (tile.id !== id) return tile;
                    return Object.assign({}, tile, { [field]: value });
                });
                setAttributes({ tiles: next });
            }

            return el(
                'section',
                blockProps,
                el(
                    'div',
                    { className: 'mosaic-grid__grid' },
                    tiles.map((tile) =>
                        el(
                            'div',
                            {
                                key: tile.id,
                                className: 'mosaic-grid__tile mosaic-grid__tile--' + tile.id,
                            },
                            el(RichText, {
                                tagName: 'h3',
                                className: 'mosaic-grid__heading',
                                value: tile.heading,
                                onChange: function (value) {
                                    updateTile(tile.id, 'heading', value);
                                },
                                placeholder: 'Heading',
                            }),
                            el(RichText, {
                                tagName: 'p',
                                className: 'mosaic-grid__body',
                                value: tile.body,
                                onChange: function (value) {
                                    updateTile(tile.id, 'body', value);
                                },
                                placeholder: 'Body text',
                            })
                        )
                    )
                )
            );
        },
        save: function () {
            return null;
        },
    });
})();
