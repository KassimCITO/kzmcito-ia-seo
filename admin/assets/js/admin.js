/**
 * Admin JavaScript for Kzmcito IA SEO
 * 
 * @package KzmcitoIASEO
 * @since 2.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Initialize admin functionality
     */
    $(document).ready(function() {
        initProcessButton();
        initTranslationButtons();
        initPromptEditor();
    });
    
    /**
     * Initialize process button
     */
    function initProcessButton() {
        $('#kzmcito-process-now').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const postId = $('#post_ID').val();
            
            if (!postId) {
                alert(kzmcitoIASEO.i18n.error);
                return;
            }
            
            if (!confirm('¿Estás seguro de que deseas procesar este contenido con IA?')) {
                return;
            }
            
            // Disable button and show loading
            $button.prop('disabled', true)
                   .html('<span class="dashicons dashicons-update spin"></span> ' + kzmcitoIASEO.i18n.processing);
            
            // AJAX request
            $.ajax({
                url: kzmcitoIASEO.ajax_url,
                type: 'POST',
                data: {
                    action: 'kzmcito_process_post',
                    nonce: kzmcitoIASEO.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        alert(kzmcitoIASEO.i18n.success);
                        location.reload();
                    } else {
                        alert(response.data.message || kzmcitoIASEO.i18n.error);
                        $button.prop('disabled', false)
                               .html('<span class="dashicons dashicons-update"></span> Procesar Ahora');
                    }
                },
                error: function() {
                    alert(kzmcitoIASEO.i18n.error);
                    $button.prop('disabled', false)
                           .html('<span class="dashicons dashicons-update"></span> Procesar Ahora');
                }
            });
        });
    }
    
    /**
     * Initialize translation buttons
     */
    function initTranslationButtons() {
        $('.kzmcito-translate-btn').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const postId = $('#post_ID').val();
            const language = $button.data('language');
            
            if (!postId || !language) {
                alert(kzmcitoIASEO.i18n.error);
                return;
            }
            
            // Disable button and show loading
            $button.prop('disabled', true)
                   .html('<span class="dashicons dashicons-update spin"></span> Traduciendo...');
            
            // AJAX request
            $.ajax({
                url: kzmcitoIASEO.ajax_url,
                type: 'POST',
                data: {
                    action: 'kzmcito_translate_content',
                    nonce: kzmcitoIASEO.nonce,
                    post_id: postId,
                    language: language
                },
                success: function(response) {
                    if (response.success) {
                        alert('Traducción completada: ' + response.data.language_name);
                        $button.prop('disabled', false)
                               .html('<span class="dashicons dashicons-yes"></span> Traducido');
                    } else {
                        alert(response.data.message || kzmcitoIASEO.i18n.error);
                        $button.prop('disabled', false)
                               .html('<span class="dashicons dashicons-translation"></span> Traducir');
                    }
                },
                error: function() {
                    alert(kzmcitoIASEO.i18n.error);
                    $button.prop('disabled', false)
                           .html('<span class="dashicons dashicons-translation"></span> Traducir');
                }
            });
        });
    }
    
    /**
     * Initialize prompt editor
     */
    function initPromptEditor() {
        // Auto-save prompt on blur
        $('#prompt_content').on('blur', function() {
            const $textarea = $(this);
            const content = $textarea.val();
            
            // Store in localStorage for recovery
            localStorage.setItem('kzmcito_prompt_draft_' + $('.kzmcito-prompts-sidebar a.active').data('category'), content);
        });
        
        // Restore draft if exists
        const currentCategory = $('.kzmcito-prompts-sidebar a.active').data('category');
        const draft = localStorage.getItem('kzmcito_prompt_draft_' + currentCategory);
        
        if (draft && draft !== $('#prompt_content').val()) {
            if (confirm('Se encontró un borrador guardado. ¿Deseas restaurarlo?')) {
                $('#prompt_content').val(draft);
            }
        }
        
        // Clear draft on save
        $('form').on('submit', function() {
            const currentCategory = $('.kzmcito-prompts-sidebar a.active').data('category');
            localStorage.removeItem('kzmcito_prompt_draft_' + currentCategory);
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
})(jQuery);
