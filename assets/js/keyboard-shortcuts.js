/**
 * Keyboard-First UX (Sprint 1B-2)
 * 
 * @package DODO_AI_SEO
 * @since 1.1.3
 */

(function($) {
    'use strict';
    
    window.DodoKeyboard = {
        initialized: false,
        
        /**
         * Initialize keyboard shortcuts
         */
        init: function() {
            // Duplicate init protection
            if (this.initialized) {
                return;
            }
            
            this.bindShortcuts();
            this.showHints();
            this.initialized = true;
        },
        
        /**
         * Destroy keyboard shortcuts (cleanup)
         */
        destroy: function() {
            this.unbindShortcuts();
            this.initialized = false;
        },
        
        /**
         * Bind keyboard shortcuts with namespace
         */
        bindShortcuts: function() {
            const self = this;
            
            // Cleanup before binding
            this.unbindShortcuts();
            
            $(document).on('keydown.dodoKeyboard', function(e) {
                // ESC - Close modal/collapse section
                if (e.key === 'Escape') {
                    self.handleEscape(e);
                }
                
                // CMD+ENTER or CTRL+ENTER - Apply/Submit
                if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                    self.handleApply(e);
                }
                
                // CMD+K or CTRL+K - Focus search/keyword
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    self.handleFocusKeyword(e);
                }
                
                // Arrow keys - Navigate sections
                if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                    if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                        self.handleArrowNavigation(e);
                    }
                }
                
                // Tab - Navigate between tabs
                if (e.key === 'Tab' && $('.dodo-modal:visible').length > 0) {
                    self.handleTabNavigation(e);
                }
                
                // ? - Show keyboard shortcuts help
                if (e.key === '?' && !$(e.target).is('input, textarea')) {
                    e.preventDefault();
                    self.showShortcutsHelp();
                }
            });
        },
        
        /**
         * Handle ESC key
         */
        handleEscape: function(e) {
            // Close modal if open
            if ($('.dodo-modal:visible').length > 0) {
                e.preventDefault();
                $('.dodo-modal-close').trigger('click');
                return;
            }
            
            // Collapse expanded section
            const $expandedSection = $('.dodo-section-item.expanded');
            if ($expandedSection.length > 0) {
                e.preventDefault();
                if (window.DodoInlineEditor) {
                    window.DodoInlineEditor.collapseSection($expandedSection);
                }
                return;
            }
            
            // Close toast notifications
            if ($('.dodo-toast:visible').length > 0) {
                e.preventDefault();
                $('.dodo-toast:visible').find('.dodo-toast-close').trigger('click');
            }
        },
        
        /**
         * Handle CMD+ENTER / CTRL+ENTER
         */
        handleApply: function(e) {
            // Apply in modal
            if ($('.dodo-modal:visible').length > 0) {
                const $applyBtn = $('.dodo-modal-apply:visible:not(:disabled)');
                if ($applyBtn.length > 0) {
                    e.preventDefault();
                    $applyBtn.trigger('click');
                    return;
                }
            }
            
            // Apply in inline editor
            const $inlineApplyBtn = $('.dodo-inline-apply-btn:visible:not(:disabled)');
            if ($inlineApplyBtn.length > 0) {
                e.preventDefault();
                $inlineApplyBtn.trigger('click');
                return;
            }
            
            // Submit analyze form
            if ($('#dodo-analyze-content:visible:not(:disabled)').length > 0) {
                e.preventDefault();
                $('#dodo-analyze-content').trigger('click');
            }
        },
        
        /**
         * Handle CMD+K / CTRL+K - Focus keyword input
         */
        handleFocusKeyword: function(e) {
            const $keywordInput = $('#dodo-focus-keyword');
            if ($keywordInput.length > 0) {
                $keywordInput.focus().select();
            }
        },
        
        /**
         * Handle arrow navigation
         */
        handleArrowNavigation: function(e) {
            const $sections = $('.dodo-section-item:visible');
            if ($sections.length === 0) return;
            
            const $focused = $('.dodo-section-item.keyboard-focused');
            let nextIndex = 0;
            
            if ($focused.length > 0) {
                const currentIndex = $sections.index($focused);
                
                if (e.key === 'ArrowDown') {
                    nextIndex = Math.min(currentIndex + 1, $sections.length - 1);
                } else if (e.key === 'ArrowUp') {
                    nextIndex = Math.max(currentIndex - 1, 0);
                }
                
                e.preventDefault();
            }
            
            // Update focus
            $sections.removeClass('keyboard-focused');
            const $nextSection = $sections.eq(nextIndex);
            $nextSection.addClass('keyboard-focused');
            
            // Scroll into view
            $nextSection[0].scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        },
        
        /**
         * Handle tab navigation in modal
         */
        handleTabNavigation: function(e) {
            const $modal = $('.dodo-modal:visible');
            if ($modal.length === 0) return;
            
            const $tabs = $modal.find('.dodo-tab-btn:visible');
            if ($tabs.length === 0) return;
            
            const $activeTab = $tabs.filter('.active');
            const currentIndex = $tabs.index($activeTab);
            
            let nextIndex;
            if (e.shiftKey) {
                // Shift+Tab - Previous tab
                nextIndex = currentIndex > 0 ? currentIndex - 1 : $tabs.length - 1;
            } else {
                // Tab - Next tab
                nextIndex = currentIndex < $tabs.length - 1 ? currentIndex + 1 : 0;
            }
            
            e.preventDefault();
            $tabs.eq(nextIndex).trigger('click');
        },
        
        /**
         * Show keyboard hints
         */
        showHints: function() {
            // Add hints to buttons
            $('.dodo-modal-apply').attr('title', 'CMD+Enter veya CTRL+Enter');
            $('.dodo-modal-close').attr('title', 'ESC');
            $('#dodo-focus-keyword').attr('title', 'CMD+K veya CTRL+K ile odaklan');
        },
        
        /**
         * Show shortcuts help modal
         */
        showShortcutsHelp: function() {
            const shortcuts = [
                { key: 'ESC', desc: 'Modal kapat / Bölümü daralt' },
                { key: 'CMD+Enter', desc: 'İyileştirmeyi uygula / Formu gönder' },
                { key: 'CMD+K', desc: 'Anahtar kelime alanına odaklan' },
                { key: '↑ ↓', desc: 'Bölümler arasında gezin' },
                { key: 'Tab', desc: 'Modal tab\'ları arasında gezin' },
                { key: '?', desc: 'Bu yardımı göster' }
            ];
            
            let html = '<div class="dodo-shortcuts-help">';
            html += '<h3>⌨️ Klavye Kısayolları</h3>';
            html += '<div class="dodo-shortcuts-list">';
            
            shortcuts.forEach(function(shortcut) {
                html += '<div class="dodo-shortcut-item">';
                html += '<kbd class="dodo-shortcut-key">' + shortcut.key + '</kbd>';
                html += '<span class="dodo-shortcut-desc">' + shortcut.desc + '</span>';
                html += '</div>';
            });
            
            html += '</div>';
            html += '<button class="dodo-shortcuts-close">Kapat (ESC)</button>';
            html += '</div>';
            
            const $overlay = $('<div class="dodo-shortcuts-overlay"></div>');
            $overlay.html(html);
            $('body').append($overlay);
            
            // Close on click
            $overlay.on('click', function(e) {
                if ($(e.target).hasClass('dodo-shortcuts-overlay') || $(e.target).hasClass('dodo-shortcuts-close')) {
                    $overlay.fadeOut(200, function() {
                        $overlay.remove();
                    });
                }
            });
            
            // Close on ESC
            $(document).one('keydown.dodoShortcutsModal', function(e) {
                if (e.key === 'Escape') {
                    $overlay.fadeOut(200, function() {
                        $overlay.remove();
                    });
                }
            });
        },
        
        /**
         * Unbind keyboard shortcuts (cleanup)
         */
        unbindShortcuts: function() {
            $(document).off('.dodoKeyboard');
            $(document).off('.dodoShortcutsModal');
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        window.DodoKeyboard.init();
    });
    
})(jQuery);
