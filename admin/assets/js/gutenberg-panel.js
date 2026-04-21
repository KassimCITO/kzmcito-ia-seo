/**
 * Gutenberg Panel for KzmCITO IA SEO
 * 
 * Registers a PluginDocumentSettingPanel in the Block Editor sidebar
 * with a toggle to enable/disable IA processing per-post.
 * 
 * Compatible with: Gutenberg (Block Editor)
 * For Classic Editor / WPBakery / Elementor: see meta box in main plugin file.
 * 
 * @package KzmcitoIASEO
 * @since 3.1.0
 */

(function () {
    'use strict';

    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var ToggleControl = wp.components.ToggleControl;
    var PanelRow = wp.components.PanelRow;
    var Icon = wp.components.Icon;
    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var Fragment = wp.element.Fragment;

    /**
     * Format a date string for display
     */
    function formatDate(dateStr) {
        if (!dateStr) return null;
        try {
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString() + ' ' + d.toLocaleTimeString();
        } catch (e) {
            return dateStr;
        }
    }

    /**
     * KzmCITO IA SEO Panel Component
     */
    function KzmcitoIASEOPanel() {
        var meta = useSelect(function (select) {
            return select('core/editor').getEditedPostAttribute('meta') || {};
        }, []);

        var editPost = useDispatch('core/editor').editPost;

        var iaEnabled = meta._kzmcito_ia_enabled;
        // Default to true if not set (undefined/null)
        if (iaEnabled === undefined || iaEnabled === null || iaEnabled === '') {
            iaEnabled = true;
        }

        var lastProcessed = meta._kzmcito_last_processed || '';
        var categoryDetected = meta._kzmcito_category_detected || '';

        function onToggleChange(newValue) {
            editPost({
                meta: { _kzmcito_ia_enabled: newValue }
            });
        }

        // Build status elements
        var statusElements = [];

        // Toggle control
        statusElements.push(
            el(PanelRow, { key: 'toggle' },
                el(ToggleControl, {
                    label: __('Activar KzmCITO IA SEO', 'kzmcito-ia-seo'),
                    help: iaEnabled
                        ? __('El procesamiento IA se ejecutará al guardar/actualizar.', 'kzmcito-ia-seo')
                        : __('El procesamiento IA está desactivado para este contenido.', 'kzmcito-ia-seo'),
                    checked: iaEnabled,
                    onChange: onToggleChange
                })
            )
        );

        // Status info (only shown when enabled)
        if (iaEnabled) {
            // Last processed
            if (lastProcessed) {
                statusElements.push(
                    el(PanelRow, { key: 'status' },
                        el('div', { className: 'kzmcito-gutenberg-status' },
                            el('span', {
                                className: 'dashicons dashicons-yes-alt',
                                style: { color: '#46b450', marginRight: '6px', fontSize: '16px', width: '16px', height: '16px' }
                            }),
                            el('span', null,
                                __('Procesado: ', 'kzmcito-ia-seo'),
                                el('strong', null, formatDate(lastProcessed))
                            )
                        )
                    )
                );
            } else {
                statusElements.push(
                    el(PanelRow, { key: 'status' },
                        el('div', { className: 'kzmcito-gutenberg-status' },
                            el('span', {
                                className: 'dashicons dashicons-warning',
                                style: { color: '#ffb900', marginRight: '6px', fontSize: '16px', width: '16px', height: '16px' }
                            }),
                            el('span', null, __('No procesado aún', 'kzmcito-ia-seo'))
                        )
                    )
                );
            }

            // Category detected
            if (categoryDetected) {
                statusElements.push(
                    el(PanelRow, { key: 'category' },
                        el('div', { className: 'kzmcito-gutenberg-status' },
                            el('span', {
                                className: 'dashicons dashicons-category',
                                style: { color: '#0073aa', marginRight: '6px', fontSize: '16px', width: '16px', height: '16px' }
                            }),
                            el('span', null,
                                __('Categoría: ', 'kzmcito-ia-seo'),
                                el('span', {
                                    className: 'kzmcito-category-badge',
                                    style: {
                                        display: 'inline-block',
                                        padding: '2px 8px',
                                        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                        color: 'white',
                                        borderRadius: '3px',
                                        fontSize: '11px',
                                        fontWeight: '600',
                                        textTransform: 'uppercase',
                                        letterSpacing: '0.5px'
                                    }
                                }, categoryDetected)
                            )
                        )
                    )
                );
            }

            // Info
            statusElements.push(
                el(PanelRow, { key: 'info' },
                    el('p', {
                        className: 'description',
                        style: { fontSize: '12px', color: '#757575', margin: '4px 0 0 0' }
                    },
                        __('Pipeline: Análisis → Transformación → SEO → Localización', 'kzmcito-ia-seo')
                    )
                )
            );
        }

        return el(
            PluginDocumentSettingPanel,
            {
                name: 'kzmcito-ia-seo-panel',
                title: __('KzmCITO IA SEO', 'kzmcito-ia-seo'),
                icon: el('span', {
                    className: 'dashicons dashicons-superhero',
                    style: { fontSize: '18px', width: '18px', height: '18px' }
                }),
                className: 'kzmcito-ia-seo-gutenberg-panel'
            },
            el(Fragment, null, statusElements)
        );
    }

    // Register the plugin panel
    registerPlugin('kzmcito-ia-seo', {
        render: KzmcitoIASEOPanel,
        icon: 'superhero'
    });

})();
