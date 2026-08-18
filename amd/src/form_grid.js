/**
 * Javascript module for Clickable Boxes form.
 * Handles the grid view, drag and drop, and dynamic text extraction.
 */
define(['jquery'], function($) {
    return {
        init: function(strings) {
            $(document).ready(function() {
                var maxRepeats = $('input[name="box_repeats"]').val();
                if (!maxRepeats || maxRepeats == 0) {
                    return;
                }

                var firstItem = $('[id^="fitem_id_boxheading_0"]').first();
                if (firstItem.length === 0) {
                    return;
                }

                var gridHtml = '<div id="clickableboxes-admin-grid"></div>';
                var backBtn = $('<button type="button" id="back-to-grid-btn" class="btn btn-secondary mb-3" style="display:none;">'
                                + ' &larr; ' + strings.backtogrid + '</button>');

                firstItem.before(gridHtml);
                firstItem.before(backBtn);

                var grid = $('#clickableboxes-admin-grid');
                var addBoxBtnContainer = $('input[name="box_add_fields"]').closest('.form-group, .fitem');

                // Loop through all boxes to create cards and hide the actual form fields.
                for (var i = 0; i < maxRepeats; i++) {
                    var elements = $();
                    elements = elements.add($('[id^="fitem_id_boxheading_'+i+'"]'));
                    elements = elements.add($('[id^="fitem_id_boximage_'+i+'"]'));
                    var linkGroupWrapper = $('[id^="fgroup_id_boxlinkgroup_'+i+'"]');
                    elements = elements.add(linkGroupWrapper);

                    var contentWrapper = $('[id^="fitem_id_boxcontent_'+i+'"]');
                    elements = elements.add(contentWrapper);

                    elements.addClass('admin-box-group-'+i);
                    elements.hide();

                    var textarea = contentWrapper.find('textarea');
                    var rawHtml = textarea.val() || '';
                    var tempDiv = $('<div>').html(rawHtml);
                    var plainText = tempDiv.text().trim();
                    var previewText = plainText.length > 0 ? plainText.substring(0, 40) + '...' : strings.clicktoedit;

                    // Use the localized string passed from PHP
                    var boxTitle = strings.boxnumber.replace('###', (i + 1));

                    // Create the visual Card with draggable attribute.
                    var card = $('<div class="clickablebox-admin-card" draggable="true" data-target="' + i + '">'
                                 + '<strong>' + boxTitle + '</strong><br>'
                                 + '<small style="color:#666; display:block; margin-top:10px;">' + previewText + '</small></div>');
                    grid.append(card);
                }

                // Hover effects for the cards.
                $('.clickablebox-admin-card').hover(function(){
                    $(this).css({'box-shadow': '0 6px 12px rgba(0,0,0,0.1)'});
                }, function(){
                    $(this).css({'box-shadow': 'none'});
                });

                // Click event: Hide grid AND 'Add box' button, show specific form fields.
                $('.clickablebox-admin-card').on('click', function(e) {
                    e.preventDefault();
                    var target = $(this).data('target');
                    grid.hide();
                    addBoxBtnContainer.hide();
                    $('.admin-box-group-' + target).fadeIn();
                    $('#back-to-grid-btn').fadeIn();
                });

                // Back button event: Hide fields, UPDATE live text, show grid AND 'Add box' button.
                $('#back-to-grid-btn').on('click', function() {
                    for (var i = 0; i < maxRepeats; i++) {
                        $('.admin-box-group-' + i).hide();

                        var contentWrapper = $('[id^="fitem_id_boxcontent_'+i+'"]');
                        var rawHtml = '';

                        if (contentWrapper.find('.editor_atto_content').length > 0) {
                            rawHtml = contentWrapper.find('.editor_atto_content').html();
                        } else if (contentWrapper.find('iframe').length > 0) {
                            try {
                                rawHtml = contentWrapper.find('iframe').contents().find('body').html();
                            } catch(e) {
                                rawHtml = contentWrapper.find('textarea').val();
                            }
                        } else {
                            rawHtml = contentWrapper.find('textarea').val() || '';
                        }

                        if (!rawHtml) {
                            rawHtml = '';
                        }

                        var tempDiv = $('<div>').html(rawHtml);
                        var plainText = tempDiv.text().trim();
                        var previewText = plainText.length > 0 ? plainText.substring(0, 40) + '...' : strings.clicktoedit;

                        $('.clickablebox-admin-card[data-target="'+i+'"] small').text(previewText);
                    }
                    $(this).hide();
                    grid.fadeIn();
                    addBoxBtnContainer.fadeIn();
                });

                // HTML5 Drag & Drop Logic
                var draggedCard = null;

                $(document).on('dragstart', '.clickablebox-admin-card', function(e) {
                    draggedCard = this;
                    $(this).css('opacity', '0.4');
                    e.originalEvent.dataTransfer.effectAllowed = 'move';
                    e.originalEvent.dataTransfer.setData('text/html', this.innerHTML);
                });

                $(document).on('dragover', '.clickablebox-admin-card', function(e) {
                    e.preventDefault();
                    e.originalEvent.dataTransfer.dropEffect = 'move';
                    return false;
                });

                $(document).on('dragenter', '.clickablebox-admin-card', function(e) {
                    e.preventDefault();
                    $(this).addClass('drag-over');
                });

                $(document).on('dragleave', '.clickablebox-admin-card', function(e) {
                    e.preventDefault();
                    $(this).removeClass('drag-over');
                });

                $(document).on('drop', '.clickablebox-admin-card', function(e) {
                    e.stopPropagation();
                    $(this).removeClass('drag-over');

                    if (draggedCard !== this) {
                        if ($(draggedCard).index() < $(this).index()) {
                            $(draggedCard).insertAfter(this);
                        } else {
                            $(draggedCard).insertBefore(this);
                        }
                        updateSortOrder();
                    }
                    return false;
                });

                $(document).on('dragend', '.clickablebox-admin-card', function(e) {
                    e.preventDefault();
                    $(this).css('opacity', '1');
                    $('.clickablebox-admin-card').removeClass('drag-over');
                });

                /**
                 * Function to sync visual order with hidden inputs
                 *
                 * @return void
                 */
                function updateSortOrder() {
                    $('#clickableboxes-admin-grid .clickablebox-admin-card').each(function(index) {
                        var target = $(this).data('target');
                        $('input[name="boxsortorder[' + target + ']"]').val(index + 1);
                    });
                }

                updateSortOrder();
            });
        }
    };
});
