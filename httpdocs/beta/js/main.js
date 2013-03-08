/**
 * Main Javascript File
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */

/*global document: false */

// Contains a list of games whose achievements have been loaded recently.
// Is used to limit achievement refreshes from the steam servers.
var gamelock = [];

// Used to display AJAX errors
function errorMessage(message) {
    "use strict";
    $('#alert').html('<div class="ui-state-error ui-corner-all"><p class="state-title"><span class="ui-icon ui-icon-alert state-title-icon"></span>Sorry! Something went wrong..</p><p class="state-content">' + message + '</p></div>').dialog({
        draggable: false,
        title: 'Alert',
        resizable: false,
        width: 400,
        modal: true,
        buttons: {
            Ok: function () {
                $(this).dialog("close");
            }
        }
    });
}

// Is used to display the "ajax is in progress" loader at the top
$(document).ajaxStart(function () {
    "use strict";
    $('.loader').css('display', 'inline-block').fadeIn(1000);
});

// Is used to remove the "ajax is in progress" loader at the top
$(document).ajaxStop(function () {
    "use strict";
    $('.loader').fadeOut(1000);
    $('#refresh').button('enable');
});

// Preloader to preload some images to prevent ugly mouseover transitions
function preload(arrayOfImages) {
    "use strict";
    $(arrayOfImages).each(function () {
        jQuery.get(this);
    });
}

// AJAX function to load and set data
function loadData(mode, gameid, value) {
    "use strict";
    gameid = (gameid === 'undefined') ? 0 : gameid;
    value = (value === 'undefined') ? 0 : value;
    $.get('ajax.php', {
            mode: mode,
            gid: gameid,
            value: value
        },

        function (data) {
            var i;
            if (typeof data !== 'undefined') {
                if (typeof data.errorlog !== 'undefined') {
                    errorMessage(data.errorlog);
                }

                if (typeof data.steamuser !== 'undefined') {
                    $('.avatar').removeClass().addClass('avatar' + data.steamuser['class']);
                    $('.statustext').removeClass().addClass('statustext' + data.steamuser['class']).html(data.steamuser.name + '<br/>' + data.steamuser.status);
                }
                if (typeof data.debuglog !== 'undefined') {
                    $('#debuglog').append(data.debuglog);
                }
                if (typeof data.steamgames !== 'undefined') {
                    for (var key in data.steamgames) {
                        if (!$('#' + key).length) {
                            $('.list_boxes').prepend('<div id="' + key + '" class="list_box"><img class="game_image" width="184" height="69" data-hours2weeks="' + data.steamgames[key].hours2weeks + '" data-hourstotal="' + data.steamgames[key].hourstotal + '" data-gameid="' + key + '" alt="' + data.steamgames[key].name + '" src="./img/game/' + key + '.jpg" data-achievper="' + data.steamgames[key].achievper + '" /></div>');
                            $('#' + key).setListDrag();
                            $('#' + key).children('.game_image').addTooltip();
                            $('#' + key).on('click', '.game_image', addInfoCard);
                        } else {
                            $('.game_image[data-gameid="' + key + '"]').attr('data-hours2weeks', data.steamgames[key].hours2weeks).attr('data-hourstotal', data.steamgames[key].hourstotal).attr('data-achievper', data.steamgames[key].achievper);
                        }
                    }
                }
                if (typeof data.deletelist !== 'undefined') {
                    for (var key in data.deletelist) {
                        $('.game_box > .game_image[data-gameid="' + key + '"]').parent().draggable('destroy').empty().append('<div class="empty_game_box"></div>');
                        $('#' + key).remove();
                    }
                }
                if (typeof data.gameachiev !== 'undefined') {
                    if($('.game_image[data-gameid="' + data.gameachiev.gameid + '"]').attr('data-achievper') != 142 || data.gameachiev.percentage != 142)
                    {
                        $('.game_image[data-gameid="' + data.gameachiev.gameid + '"]').attr('data-achievper', data.gameachiev.percentage);
                        $(this).achievementBarAnimate(data.gameachiev.gameid, data.gameachiev.percentage);
                    }
                }
            }

        }, 'json');
}

// jQuery extension to make games in the main games list draggable
$.fn.setListDrag = function () {
    "use strict";
    $(this).draggable({
        revert: true,
        distance: 10,
        helper: 'clone',
        cursor: 'move',
        opacity: 0.75,
        addClasses: false,
        revertDuration: 150,
        scroll: false,
        zIndex: 3,
        start: function () {
            $(this).infoCardDisable();
            $('.fader').css('display', 'block').fadeTo('slow', 0.6);
        },
        stop: function () {
            $('.fader').fadeOut('fast', function () {
                $('.fader').css('opacity', '0');
            });
            $(this).draggable('option', 'revertDuration', 150);
        }
    });
};

// jQuery extension to make the "to beat" boxes droppable
$.fn.setListDrop = function () {
    "use strict";
    $(this).droppable({
        accept: '.list_box',
        addClasses: false,
        hoverClass: 'over',
        tolerance: 'pointer',
        drop: function (event, ui) {
            loadData('savegameslot', ui.draggable[0].id, this.id.substr(9));
            var gameid = $(this).find('.game_image').attr('data-gameid');
            if (gameid) {
                $('.list_boxes').find('#' + gameid).draggable('enable').on('click', '.game_image', addInfoCard);
            }
            $(ui.draggable).infoCardHardDisable();
            $(this).html(ui.draggable[0].innerHTML);
            $(ui.draggable).draggable('disable');
            $(ui.draggable).draggable('option', 'revertDuration', 0);
            $(ui.draggable).off('click', '.game_image', addInfoCard);

            $(this).setGameBoxDrag();
            $(this).children('.game_image').activeInfoCard();
            $(this).addTooltip();
        }
    });
};

// jQuery extension to make the "to beat" games draggable
$.fn.setGameBoxDrag = function () {
    "use strict";
    $(this).draggable({
        revert: true,
        distance: 10,
        helper: 'clone',
        cursor: 'move',
        opacity: 0.75,
        addClasses: false,
        revertDuration: 150,
        scroll: false,
        zIndex: 3,
        stop: function () {
            if ($(this).data('delete') === '1') {
                $(this).removeData('delete');
                $(this).draggable('destroy');
                $(this).setListDrop();
            }
        }
    });
};

// jQuery extension to make the entire list area droppable for "to beat" games
$.fn.setGameBoxDrop = function () {
    "use strict";
    $(this).droppable({
        accept: '.game_box',
        addClasses: false,
        tolerance: 'pointer',
        drop: function (event, ui) {
            var gameid = $(ui.draggable).find('.game_image').attr('data-gameid');
            loadData('savegameslot', gameid, 0);
            $('.list_boxes').find('#' + gameid).draggable('enable').on('click', '.game_image', addInfoCard);
            $(ui.draggable).data('delete', '1');
            $(ui.draggable).draggable('option', 'revertDuration', 0);
            $(ui.draggable).empty();
            $(ui.draggable).append('<div class="empty_game_box"></div>');
            $('.pin').button('enable');
            $(ui.draggable).stop().animate({
                height: '69',
                marginBottom: '2em'
            }, 400, function () {
                $(this).removeClass('game_box_active');
                $(this).children('.act_btn').remove();
                $(this).children('.achiev_bar').remove();
            });
        }
    });
};

// jQuery extension animation to close the info card, followed by removing the contained elements
$.fn.infoCardDisable = function () {
    "use strict";
    $('.list_box_active').stop().animate({
        height: '69',
        marginBottom: '2em'
    }, 400, function () {
        $(this).removeClass('list_box_active');
        $(this).children('.act_btn').remove();
        $(this).children('.achiev_bar').remove();
    });
};

// jQuery extension instantaneous disable of the info card, to prevent display problems through spamming
$.fn.infoCardHardDisable = function () {
    "use strict";
    $('.list_box_active').stop().css({'height' : '69', 'margin-bottom' : '2em'});
    $(this).removeClass('list_box_active');
    $(this).children('.act_btn').remove();
    $(this).children('.achiev_bar').remove();
};

// jQuery extension to add tooltips to things
$.fn.addTooltip = function () {
    "use strict";
    $(this).tooltip({
        content: function () {
            return $(this).attr('alt');
        },
        items: "img[alt]",
        show: {
            delay: 1000
        },
        tooltipClass: "custom_ttip",
        position: {
            my: "center top",
            at: "center top-35",
            collision: "flipfit"
        }
    });
};

// jQuery extension to animate the achievement percentage bar
$.fn.achievementBarAnimate = function (gameid, percentage) {
    var myPer = parseInt(percentage, 10);

    if(myPer > 100) {
        $('.game_image[data-gameid="' + gameid + '"]').siblings('.sel_progress').progressbar( 'option', 'value', 100).children('.ui-progressbar-value').animate({
            width: '100%'
        }, 1200);
        $('.game_image[data-gameid="' + gameid + '"]').siblings('.sel_progress').progressbar( 'option', 'disabled', true).children('.percent_box').html('');
    } else {
        $('.game_image[data-gameid="' + gameid + '"]').siblings('.sel_progress').children('.percent_box').html(percentage + '%');

        var target_r = 200;
        var target_g = 200;

        if (myPer > 50) {
            target_r = 200 - ((myPer - 50) * 4);
        } else if (myPer < 50) {
            target_g = 200 - ((50 - myPer) * 4);
        }

        $('.game_image[data-gameid="' + gameid + '"]').siblings('.sel_progress').children('.ui-progressbar-value').animate({
            width: myPer + '%',
            backgroundColor: 'rgb(' + target_r + ',' + target_g + ',0)'
        }, 1200);
    }
}

// jQuery extension to display the achievement percentage bar
$.fn.achievementBar = function (load) {
    "use strict";
    var myPer = parseInt($(this).attr('data-achievper'), 10);

    if(myPer > 100) {
        $(this).siblings('.sel_progress').progressbar({
            value: 100
        });
        $(this).siblings('.sel_progress').append('<div class="percent_box"></div>');
        $(this).siblings('.sel_progress').progressbar( 'option', 'disabled', true );
    } else {
        $(this).siblings('.sel_progress').progressbar({
            value: 0.0001
        });
        $(this).siblings('.sel_progress').append('<div class="percent_box">' + myPer + '%</div>');

        var target_r = 200;
        var target_g = 200;

        if (myPer > 50) {
            target_r = 200 - ((myPer - 50) * 4);
        } else if (myPer < 50) {
            target_g = 200 - ((50 - myPer) * 4);
        }

        $(this).siblings('.sel_progress').children('.ui-progressbar-value').animate({
            width: myPer + '%',
            backgroundColor: 'rgb(' + target_r + ',' + target_g + ',0)'
        }, 1200);

        $(this).siblings('.sel_progress').click(function () {
            window.location = 'steam://url/SteamIDAchievementsPage/' + $(this).siblings(".game_image").attr('data-gameid');
            $('.ui-tooltip').remove();
        });

    }

    if(typeof load !== 'undefined' && load) {
        loadData('achievpercent', $(this).attr('data-gameid'), 0);
    }

};

// jQuery extension to display the info card for all "to beat" games
$.fn.activeInfoCard = function () {
    "use strict";
    this.each(function () {
        var gameid = $(this).attr('data-gameid');

        $(this).parent().append('<button class="act_btn unpin">Remove from my &quot;to beat&quot; list</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Mark game as beat</button><button class="act_btn blacklist">Blacklist this game</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div>');

        $(this).siblings('.unpin').button({
            icons: {
                primary: "ui-icon-pin-w"
            },
            text: false
        }).click(function () {
            var gameid = $(this).siblings('.game_image').attr('data-gameid');
            var parent = $(this).parent();
            loadData('savegameslot', gameid, 0);
            $('.list_boxes').find('#' + gameid).draggable('enable').on('click', '.game_image', addInfoCard);
            parent.empty();
            parent.append('<div class="empty_game_box"></div>');
            $('.pin').button('enable');
            parent.stop().animate({
                height: '69',
                marginBottom: '2em'
            }, 400, function () {
                parent.removeClass('game_box_active');
            });
            parent.draggable('destroy');
            parent.setListDrop();
        });

        $(this).siblings('.blacklist').button({
            icons: {
                primary: "ui-icon-closethick"
            },
            text: false
        });

        $(this).siblings('.beat').button({
            icons: {
                primary: "ui-icon-check"
            },
            text: false
        });

        $(this).siblings('.play')
            .button({
            icons: {
                primary: "ui-icon-play"
            },
            text: false
        })
            .click(function () {
            window.location = 'steam://run/' + gameid;
            $('.ui-tooltip').remove();
            $(this).removeClass('ui-state-focus');
        });

        $(this).click(function () {
            if(typeof gamelock[gameid] === 'undefined') {
                gamelock[gameid] = 1;
                var timeoutID = window.setTimeout(function() {
                    delete gamelock[gameid];
                }, 30000);
                loadData('achievpercent', $(this).attr('data-gameid'), 0);
            }
        });

        if (typeof gamelock[gameid] !== 'undefined') {
            $(this).achievementBar(false);
        } else {
            gamelock[gameid] = 1;
            var timeoutID = window.setTimeout(function() {
                delete gamelock[gameid];
            }, 30000);
            $(this).achievementBar(true);
        }

        $(this).parent().addClass('game_box_active').stop().animate({
            height: '103',
            marginBottom: '-10'
        }, 400);
    });
};

// javascript function to add a infocard on click
function addInfoCard() {
    "use strict";
    if ($(this).parent().hasClass('list_box_active')) {
        $(this).infoCardDisable();
    } else {
        $(this).infoCardDisable();

        var gameid = $(this).attr('data-gameid');

        $(this).parent().append('<button class="act_btn pin">Add to my &quot;to beat&quot; list</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Mark game as beat</button><button class="act_btn blacklist">Blacklist this game</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div>');

        $(this).siblings('.pin').button({
            icons: {
                primary: "ui-icon-pin-s"
            },
            text: false
        }).click(function () {
            var target_slot = $('.game_box > .empty_game_box:first').parent();
            var game = $(this).parent();
            loadData('savegameslot', game.children('.game_image').attr('data-gameid'), target_slot.attr('id').substr(9));
            game.infoCardHardDisable();
            target_slot.html(game.html());
            game.draggable('disable');
            game.off('click', '.game_image', addInfoCard);
            target_slot.setGameBoxDrag();
            target_slot.children('.game_image').activeInfoCard();
            target_slot.addTooltip();
        });

        if(!$('.game_box > .empty_game_box').length) {
            $(this).siblings('.pin').button('disable');
        }

        $(this).siblings('.blacklist').button({
            icons: {
                primary: "ui-icon-closethick"
            },
            text: false
        });

        $(this).siblings('.beat').button({
            icons: {
                primary: "ui-icon-check"
            },
            text: false
        });

        $(this).siblings('.play')
            .button({
            icons: {
                primary: "ui-icon-play"
            },
            text: false
            }).click(function () {
                window.location = 'steam://run/' + gameid;
                $('.ui-tooltip').remove();
                $(this).removeClass('ui-state-focus');
            });

        if (typeof gamelock[gameid] !== 'undefined') {
            $(this).achievementBar(false);
        } else {
            gamelock[gameid] = 1;
            var timeoutID = window.setTimeout(function() {
                delete gamelock[gameid];
            }, 30000);
            $(this).achievementBar(true);
        }

        $(this).parent().addClass('list_box_active').stop().animate({
            height: '103',
            marginBottom: '-10'
        }, 400);
    }
    return false;
}

$(document).ready(function () {
    "use strict";
    
    // list of images to preload
    preload([
        '/img/loading.gif',
        '/css3/images/ui-bg_flat_30_cccccc_40x100.png',
        '/css3/images/ui-bg_flat_50_5c5c5c_40x100.png',
        '/css3/images/ui-bg_glass_20_555555_1x400.png',
        '/css3/images/ui-bg_glass_40_0078a3_1x400.png',
        '/css3/images/ui-bg_glass_40_ffc73d_1x400.png',
        '/css3/images/ui-bg_gloss-wave_25_333333_500x100.png',
        '/css3/images/ui-bg_highlight-soft_80_eeeeee_1x100.png',
        '/css3/images/ui-bg_inset-soft_25_000000_1x100.png',
        '/css3/images/ui-bg_inset-soft_30_f58400_1x100.png',
        '/css3/images/ui-icons_222222_256x240.png',
        '/css3/images/ui-icons_4b8e0b_256x240.png',
        '/css3/images/ui-icons_a83300_256x240.png',
        '/css3/images/ui-icons_cccccc_256x240.png',
        '/css3/images/ui-icons_ffffff_256x240.png']);

    // The refresh button sends a ajax request to reload data from steam
    // The data is then used to update the user's displayed data, refreshing all elements
    // Can be used to show a game after you've bought it, get your current user status etc
    $('#refresh')
        .button({
        text: false,
        disabled: true,
        icons: {
            primary: 'ui-icon-refresh'
        }
    })
        .click(function () {
        loadData('getsteamdata');
        $(this).button('disable');
        $(this).removeClass('ui-state-focus');
        $('.ui-tooltip').remove();
    });

    // Opens the settings menu, currently not done
    $('#settings')
        .button({
        text: false,
        icons: {
            primary: 'ui-icon-wrench'
        }
    })
        .click(function () {
        window.alert('Will work on this later');
        $(this).removeClass('ui-state-focus');
        $('.ui-tooltip').remove();
    });

    // Adds tooltips to all game images
    $('.game_image').addTooltip();
    
    // Binds the addInfoCard function to mouseclick on game images.
    $('.list_box:not(.disabled)').on('click', '.game_image', addInfoCard);

    // Enables dragging of list_box elements
    $('.list_box').setListDrag();
    
    // Disables dragging on list_box elements that are already in the "to beat" list
    $('.list_box.disabled').draggable('disable').removeClass('disabled');
    
    // Makes the "to beat" boxes droppable
    $('.game_box').setListDrop();

    // Activates the InfoCards for games in the "to beat" list
    $('.game_box > .game_image').activeInfoCard();

    // Makes "to beat" games draggable
    $('.game_box > .game_image').parent().setGameBoxDrag();

    // Makes the entire lower area droppable
    $('.list_wrapper').setGameBoxDrop();

    // Tooltip setup
    $(document).tooltip({
        track: true,
        show: {
            delay: 500
        }
    });

    if($('#error').length) {
        errorMessage($('#error').html());
    }

    // Triggers an initial AJAX request
    //loadData('getsteamdata');
});