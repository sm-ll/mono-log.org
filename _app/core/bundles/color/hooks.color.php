<?php

class Hooks_color extends Hooks
{
        /**
         * Creates CSS tags to add to the Control Panel's head tag
         *
         * @return string
         */
        function control_panel__add_to_head()
        {
            if (URL::getCurrent(false) == '/publish') {
                return $this->css->link('spectrum.css');
            }
        }


        /**
         * Creates JavaScript to add to the Control Panel's footer
         *
         * @return string
         */
        function control_panel__add_to_foot()
        {
            if (URL::getCurrent(false) == '/publish') {
                $html  = $this->js->link('jquery.spectrum.js');

                // load in global options
                $spectrum_options = json_encode($this->getConfig());

                $html .= $this->js->inline("
                    var spectrum_options = {$spectrum_options};
                    $(document).ready(function() {
                        $('input[type=text].colorpicker').each(function() {
                            var preferences = $.extend({}, spectrum_options, $(this).data('spectrum'));
                            $(this)
                                .spectrum({
                                    color: $(this).val() || preferences.starting_color,
                                    flat: preferences.select_on_page,
                                    showInput: preferences.show_input,
                                    showInitial: preferences.show_initial,
                                    showAlpha: preferences.show_alpha,
                                    localStorageKey: preferences.local_storage_key,
                                    showPalette: preferences.show_palette,
                                    showPaletteOnly: preferences.show_palette_only,
                                    showSelectionPalette: preferences.show_selection_palette,
                                    cancelText: preferences.cancel_text,
                                    chooseText: preferences.choose_text,
                                    preferredFormat: preferences.preferred_format,
                                    maxSelectionSize: preferences.max_selection_size,
                                    palette: preferences.palette
                                });
                        });

                        // for dynamically loaded rows
                        $('body').on('addRow', '.grid', function() {
                            var input = $(this).find('input[type=text].colorpicker');

                            input
                                .each(function() {
                                    var preferences = $.extend({}, spectrum_options, $(this).data('spectrum'));

                                    // check for already-initialized fields
                                    if ($(this).next().is('.sp-replacer')) {
                                        return;
                                    }

                                    $(this)
                                        .spectrum({
                                            color: $(this).val() || preferences.starting_color,
                                            flat: preferences.select_on_page,
                                            showInput: preferences.show_input,
                                            showInitial: preferences.show_initial,
                                            showAlpha: preferences.show_alpha,
                                            localStorageKey: preferences.local_storage_key,
                                            showPalette: preferences.show_palette,
                                            showPaletteOnly: preferences.show_palette_only,
                                            showSelectionPalette: preferences.show_selection_palette,
                                            cancelText: preferences.cancel_text,
                                            chooseText: preferences.choose_text,
                                            preferredFormat: preferences.preferred_format,
                                            maxSelectionSize: preferences.max_selection_size,
                                            palette: preferences.palette
                                        });
                                });
                        });
                    });
                ");

                return $html;
            }
        }
}