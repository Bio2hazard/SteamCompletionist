/**
 * Main Javascript File
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */

/*global document: false */
/*jslint browser: true*/

// Contains a list of games whose achievements have been loaded recently.
// Is used to limit achievement refreshes from the steam servers.
var gamelock = [],
    showbeat = true,
    showblacklisted = true,
    showcompleted = true,
    showplayed = true,
    showunplayed = true,
    loggeduser = 0,
    select,
    slider,
    tobeatnum,
    considerbeaten,
    hidequickstats,
    hideaccountstats,
    addInfoCard,
    stats = [],
    updateChart,
    chart,
    chartData,
    chartOptions;

String.prototype.getHHMM = function () {
    "use strict";
    var minutes_total    = parseInt(this, 10),
        hours   = Math.floor(minutes_total / 60),
        minutes = Math.floor(minutes_total - (hours * 60)),
        time;

    if (hours   < 10) {hours   = "0"+hours;}
    if (minutes < 10) {minutes = "0"+minutes;}
    time    = hours+':'+minutes;
    return time;
};

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
    gameid = (Math.floor(gameid) === parseInt(gameid, 10) && $.isNumeric(gameid)) ? gameid : 0;
    value = (Math.floor(value) === parseInt(value, 10) && $.isNumeric(value)) ? value : 0;

    $.get('ajax.php', {
            mode: mode,
            gid: gameid,
            value: value
        },

        function (data) {
            var game;

            if (data !== undefined) {
                if (data.errorlog !== undefined) {
                    errorMessage(data.errorlog);
                }

                if (data.steamuser !== undefined) {
                    $('.avatar').removeClass().addClass('avatar' + data.steamuser['class']).attr("src", data.steamuser.avatar);
                    $('.statustext').removeClass().addClass('statustext' + data.steamuser['class']).html(data.steamuser.name + '<br/>' + data.steamuser.status + '<br/><span id="points">' + data.steamuser.points + '</span>  Points');
                }
                // Debug display
                //if (typeof data.debuglog !== 'undefined') {
                //    $('#debuglog').append(data.debuglog);
                //}
                if (data.steamgames !== undefined) {
                    $.each(data.steamgames, function () {
                        var game = $('#' + this.appid);
                        if (!game.length) {
                            if (this.achievper.length) {
                                $('.list_boxes').prepend('<div id="' + this.appid + '" class="list_box"><img class="game_image" width="184" height="69" data-status="' + this.status + '" data-minutes2weeks="' + this.minutes2weeks + '" data-minutestotal="' + this.minutestotal + '" data-gameid="' + this.appid + '" alt="' + this.name + '" data-numowned="' + this.numowned + '" data-numbeaten="' + this.numbeaten + '" data-numblacklisted="' + this.numblacklisted + '" src="http://media.steampowered.com/steamcommunity/public/images/apps/' + this.appid + '/' + this.imagehash + '.jpg" data-achievper="' + this.achievper + '" /></div>');
                            } else {
                                $('.list_boxes').prepend('<div id="' + this.appid + '" class="list_box"><img class="game_image" width="184" height="69" data-status="' + this.status + '" data-minutes2weeks="' + this.minutes2weeks + '" data-minutestotal="' + this.minutestotal + '" data-gameid="' + this.appid + '" alt="' + this.name + '" data-numowned="' + this.numowned + '" data-numbeaten="' + this.numbeaten + '" data-numblacklisted="' + this.numblacklisted + '" src="http://media.steampowered.com/steamcommunity/public/images/apps/' + this.appid + '/' + this.imagehash + '.jpg"/></div>');
                            }
                            game.setListDrag();
                            //noinspection JSValidateTypes
                            game.children('.game_image').addTooltip();
                            game.on('click', '.game_image', addInfoCard);
                        } else {
                            $('.game_image[data-gameid="' + this.appid + '"]').attr('data-minutes2weeks', this.minutes2weeks).attr('data-minutestotal', this.minutestotal).attr('data-achievper', this.achievper);
                        }
                    });
                    $('.list_box').sortElements(function (a, b) {
                        //noinspection JSValidateTypes
                        var aminutes = parseInt($(a).children('.game_image').attr('data-minutes2weeks'), 10),
                            bminutes = parseInt($(b).children('.game_image').attr('data-minutes2weeks'), 10),
                            aname = $(a).children('.game_image').attr('alt').toLowerCase(),
                            bname = $(b).children('.game_image').attr('alt').toLowerCase();

                        if (aminutes > bminutes) {
                            return -1;
                        }
                        if (bminutes > aminutes) {
                            return 1;
                        }
                        if (aname > bname) {
                            return 1;
                        }
                        return -1;
                    });
                    // Update stats
                    updateChart();
                }
                if (data.deletelist !== undefined) {
                    $.each(data.deletelist, function () {
                        $('.game_box > .game_image[data-gameid="' + this.appid + '"]').parent().draggable('destroy').empty().append('<div class="empty_game_box"></div>');
                        $('#' + this.appid).remove();
                    });
                }
                if (data.gameachiev !== undefined) {
                    game = $('.game_image[data-gameid="' + data.gameachiev.gameid + '"]');
                    if (parseInt(game.attr('data-achievper'), 10) !== 142 || parseInt(data.gameachiev.percentage, 10) !== 142) {
                        game.attr('data-achievper', data.gameachiev.percentage);
                        $(this).achievementBarAnimate(data.gameachiev.gameid, data.gameachiev.percentage);
                    }
                }
            }
        }, 'json');
}

/**
 * Selects a random element
 *
 * @returns {*}
 */
jQuery.fn.random = function () {
    "use strict";
    var randomIndex = Math.floor(Math.random() * this.length);
    return jQuery(this[randomIndex]);
};

/**
 * jQuery.fn.sortElements
 * --------------
 * @author:  James Padolsey ( http://james.padolsey.com/javascript/sorting-elements-with-jquery/ )
 */
jQuery.fn.sortElements = (function () {
    "use strict";
    var sort = [].sort;

    return function (comparator, getSortable) {

        getSortable = getSortable || function () {
            return this;
        };

        var placements = this.map(function () {

            var sortElement = getSortable.call(this),
                parentNode = sortElement.parentNode,

            // Since the element itself will change position, we have
            // to have some way of storing its original position in
            // the DOM. The easiest way is to have a 'flag' node:
                nextSibling = parentNode.insertBefore(
                    document.createTextNode(''),
                    sortElement.nextSibling
                );

            return function () {

                if (parentNode === this) {
                    throw new Error(
                        "You can't sort elements if any one is a descendant of another."
                    );
                }

                // Insert before flag:
                parentNode.insertBefore(this, nextSibling);
                // Remove flag:
                parentNode.removeChild(nextSibling);

            };

        });

        return sort.call(this, comparator).each(function (i) {
            placements[i].call(getSortable.call(this));
        });

    };

}());

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
            var gameid = $(this).find('.game_image').attr('data-gameid'),
                status = $(ui.draggable).find('.game_image').attr('data-status'),
                played = $(ui.draggable).find('.game_image').attr('data-minutestotal');

            if (gameid) {
                $('.list_boxes').find('#' + gameid).draggable('enable').on('click', '.game_image', addInfoCard);
            }
            $(ui.draggable).infoCardHardDisable();
            $(this).html(ui.draggable[0].innerHTML);
            if (status === '1') {
                $(this).addClass('beaten');
            } else if (played === '0') {
                $(this).addClass('unplayed');
            } else {
                $(this).removeClass('beaten unplayed');
            }
            $(ui.draggable).draggable('disable');
            $(ui.draggable).draggable('option', 'revertDuration', 0);
            $(ui.draggable).off('click', '.game_image', addInfoCard);

            $(this).setGameBoxDrag();
            //noinspection JSValidateTypes
            $(this).children('.game_image').activeInfoCard();
            $(this).addTooltip();
            if (!$('.game_box > .empty_game_box').length) {
                $('#fillslot').button('disable');
            }
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
            var gameid = $(ui.draggable).find('.game_image').attr('data-gameid'),
                status = $(ui.draggable).find('.game_image').attr('data-status'),
                played = $(ui.draggable).find('.game_image').attr('data-minutestotal');
            loadData('savegameslot', gameid, 0);
            $('.list_boxes').find('#' + gameid).draggable('enable').on('click', '.game_image', addInfoCard);
            $(ui.draggable).data('delete', '1');
            $(ui.draggable).draggable('option', 'revertDuration', 0);
            $(ui.draggable).empty();
            $(ui.draggable).append('<div class="empty_game_box"></div>');
            if (status === '1') {
                $(ui.draggable).removeClass('beaten');
            }  else if (played === '0') {
                $(ui.draggable).removeClass('unplayed');
            }
            $('.pin').button('enable');
            $('#fillslot').button('enable');
            $(ui.draggable).stop().animate({
                height: '69',
                marginBottom: '2em'
            }, 400, function () {
                $(this).removeClass('game_box_active');
                //noinspection JSValidateTypes
                $(this).children('.act_btn').remove();
                //noinspection JSValidateTypes
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
        //noinspection JSValidateTypes
        $(this).children('.act_btn').remove();
        //noinspection JSValidateTypes
        $(this).children('.achiev_bar').remove();
        //noinspection JSValidateTypes
        $(this).children('.stat_box').remove();
        //noinspection JSValidateTypes
        $(this).children('.time_box').remove();
    });
};

// jQuery extension instantaneous disable of the info card, to prevent display problems through spamming
$.fn.infoCardHardDisable = function () {
    "use strict";
    $('.list_box_active').stop().css({'height': '69', 'margin-bottom': '2em'});
    $(this).removeClass('list_box_active');
    //noinspection JSValidateTypes
    $(this).children('.act_btn').remove();
    //noinspection JSValidateTypes
    $(this).children('.achiev_bar').remove();
    //noinspection JSValidateTypes
    $(this).children('.stat_box').remove();
    //noinspection JSValidateTypes
    $(this).children('.time_box').remove();
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
    "use strict";
    var myPer = parseInt(percentage, 10),
        game = $('.game_image[data-gameid="' + gameid + '"]'),
        target_r = 200,
        target_g = 200;

    if (myPer > 100) {
        game.siblings('.sel_progress').unbind('click');
        game.siblings('.sel_progress').progressbar('option', 'value', 100).children('.ui-progressbar-value').animate({
            width: '100%'
        }, 1200);
        game.siblings('.sel_progress').progressbar('option', 'disabled', true).children('.percent_box').html('');
    } else {
        game.siblings('.sel_progress').children('.percent_box').html(percentage + '%');
        if (myPer > 50) {
            target_r = 200 - ((myPer - 50) * 4);
        } else if (myPer < 50) {
            target_g = 200 - ((50 - myPer) * 4);
        }
        game.siblings('.sel_progress').children('.ui-progressbar-value').animate({
            width: myPer + '%',
            backgroundColor: 'rgb(' + target_r + ',' + target_g + ',0)'
        }, 1200);
    }
};

// jQuery extension to display the achievement percentage bar
$.fn.achievementBar = function (load) {
    "use strict";
    var myPer = 142,
        target_r = 200,
        target_g = 200;

    if ($(this).attr('data-achievper')) {
        myPer = parseInt($(this).attr('data-achievper'), 10);
    }

    if (myPer > 100) {
        $(this).siblings('.sel_progress').progressbar({
            value: 100
        });
        $(this).siblings('.sel_progress').append('<div class="percent_box"></div>');
        $(this).siblings('.sel_progress').progressbar('option', 'disabled', true);
    } else {
        $(this).siblings('.sel_progress').progressbar({
            value: 0.0001
        });
        $(this).siblings('.sel_progress').append('<div class="percent_box">' + myPer + '%</div>');

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

    if (load !== undefined && load && myPer < 100) {
        loadData('achievpercent', $(this).attr('data-gameid'), 0);
    }

};

// jQuery extension to display the info card for all "to beat" games
$.fn.activeInfoCard = function () {
    "use strict";
    this.each(function () {
        var gameid = $(this).attr('data-gameid'),
            status = $(this).attr('data-status'),
            played = $(this).attr('data-minutestotal'),
            numbeaten = parseInt($(this).attr('data-numbeaten'), 10),
            numblacklisted = parseInt($(this).attr('data-numblacklisted'), 10),
            numowned = parseInt($(this).attr('data-numowned'), 10) - (numbeaten + numblacklisted);

        if (status === '1') {
            $(this).parent().append('<button class="act_btn unpin">Remove from my &quot;to beat&quot; list</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Game is already marked as beat</button><button class="act_btn blacklist">Game is already marked as beat</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time you\'ve spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        } else {
            $(this).parent().append('<button class="act_btn unpin">Remove from my &quot;to beat&quot; list</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Mark game as beat</button><button class="act_btn blacklist">Blacklist this game</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time you\'ve spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        }

        if (hidequickstats === '1') {
            $('.stat_box').css('display', 'none');
            $('.time_box').css('display', 'none');
        }

        $(this).siblings('.unpin').button({
            icons: {
                primary: "ui-icon-pin-w"
            },
            text: false
        }).click(function () {
                var gameid = $(this).siblings('.game_image').attr('data-gameid'),
                    status = $(this).siblings('.game_image').attr('data-status'),
                    played = $(this).siblings('.game_image').attr('data-minutestotal'),
                    parent = $(this).parent();
                loadData('savegameslot', gameid, 0);
                $('.list_boxes').find('#' + gameid).draggable('enable').on('click', '.game_image', addInfoCard);
                parent.empty();
                parent.append('<div class="empty_game_box"></div>');
                $('.pin').button('enable');
                $('#fillslot').button('enable');
                parent.stop().animate({
                    height: '69',
                    marginBottom: '2em'
                }, 400, function () {
                    parent.removeClass('game_box_active');
                });
                parent.draggable('destroy');
                parent.setListDrop();
                if (status === '1') {
                    parent.removeClass('beaten');
                } else if (played === '0') {
                    parent.removeClass('unplayed');
                }
            });

        $(this).siblings('.blacklist').button({
            icons: {
                primary: "ui-icon-cancel"
            },
            text: false
        }).click(function () {
                var gameid = $(this).siblings('.game_image').attr('data-gameid'),
                    numblacklisted = parseInt($(this).siblings('.game_image').attr('data-numblacklisted'), 10),
                    played = $(this).siblings('.game_image').attr('data-minutestotal'),
                    parent = $(this).parent(),
                    listboxes = $('.list_boxes');
                loadData('savegamestatus', gameid, 2);
                listboxes.find('#' + gameid).removeClass('ui-state-disabled unplayed').addClass('blacklisted').on('click', '.game_image', addInfoCard).children('.game_image').attr('data-status', '2').attr('data-numblacklisted', numblacklisted + 1);
                parent.empty();
                parent.append('<div class="empty_game_box"></div>');
                $('.pin').button('enable');
                $('#fillslot').button('enable');
                parent.stop().animate({
                    height: '69',
                    marginBottom: '2em'
                }, 400, function () {
                    parent.removeClass('game_box_active');
                });
                parent.draggable('destroy');
                parent.setListDrop();
                if(played === '0') {
                    parent.removeClass('unplayed');
                }
                if (!showblacklisted) {
                    listboxes.find('#' + gameid).fadeOut("slow");
                }
                updateChart();
            });

        $(this).siblings('.beat').button({
            icons: {
                primary: "ui-icon-check"
            },
            text: false
        }).click(function () {
                var gameid = $(this).siblings('.game_image').attr('data-gameid'),
                    numbeaten = parseInt($(this).siblings('.game_image').attr('data-numbeaten'), 10),
                    played = $(this).siblings('.game_image').attr('data-minutestotal'),
                    parent = $(this).parent(),
                    listboxes = $('.list_boxes');
                loadData('savegamestatus', gameid, 1);
                listboxes.find('#' + gameid).draggable('enable').addClass('beaten').on('click', '.game_image', addInfoCard).children('.game_image').attr('data-status', '1').attr('data-numbeaten', numbeaten + 1);
                parent.empty();
                parent.append('<div class="empty_game_box"></div>');
                $('.pin').button('enable');
                $('#fillslot').button('enable');
                parent.stop().animate({
                    height: '69',
                    marginBottom: '2em'
                }, 400, function () {
                    parent.removeClass('game_box_active');
                });
                parent.draggable('destroy');
                parent.setListDrop();
                if(played === '0') {
                    parent.removeClass('unplayed');
                }
                if (!showbeat) {
                    listboxes.find('#' + gameid).fadeOut("slow");
                }
                updateChart();
            });

        if (status === '1') {
            $(this).siblings('.blacklist').button('disable');
            $(this).siblings('.beat').button('disable').addClass('ui-state-focus');
        }

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
            if (gamelock[gameid] === undefined) {
                gamelock[gameid] = 1;
                window.setTimeout(function () {
                    delete gamelock[gameid];
                }, 30000);
                loadData('achievpercent', $(this).attr('data-gameid'), 0);
            }
        });

        if (gamelock[gameid] !== undefined) {
            $(this).achievementBar(false);
        } else {
            gamelock[gameid] = 1;
            window.setTimeout(function () {
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
addInfoCard = function (event) {
    "use strict";
    var target = event.target,
        gameid,
        status,
        played,
        numbeaten,
        numblacklisted,
        numowned;

    if ($(target).parent().hasClass('list_box_active')) {
        $(target).infoCardDisable();
    } else {
        $(target).infoCardDisable();

        gameid = $(target).attr('data-gameid');
        status = $(target).attr('data-status');
        played = $(target).attr('data-minutestotal');
        numbeaten = parseInt($(target).attr('data-numbeaten'), 10);
        numblacklisted = parseInt($(target).attr('data-numblacklisted'), 10);
        numowned = parseInt($(target).attr('data-numowned'), 10) - (numbeaten + numblacklisted);

        // Blacklisted game:
        if (status === '2') {
            $(target).parent().append('<button class="act_btn pin">Cannot add a blacklisted game</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Mark game as beat</button><button class="act_btn blacklist">Un-Blacklist this game</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time you\'ve spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        } else if (status === '1') {
            $(target).parent().append('<button class="act_btn pin">Add to my &quot;to beat&quot; list</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Game is already marked as beat</button><button class="act_btn blacklist">Game is already marked as beat</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time you\'ve spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        } else {
            $(target).parent().append('<button class="act_btn pin">Add to my &quot;to beat&quot; list</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Mark game as beat</button><button class="act_btn blacklist">Blacklist this game</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time you\'ve spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        }

        if (hidequickstats === '1') {
            $('.stat_box').css('display', 'none');
            $('.time_box').css('display', 'none');
        }

        $(target).siblings('.pin').button({
            icons: {
                primary: "ui-icon-pin-s"
            },
            text: false
        }).click(function () {
                var target_slot = $('.game_box > .empty_game_box:first').parent(),
                    game = $(this).parent();
                loadData('savegameslot', game.children('.game_image').attr('data-gameid'), target_slot.attr('id').substr(9));
                game.infoCardHardDisable();
                target_slot.html(game.html());
                if (status === '1') {
                    target_slot.addClass('beaten');
                } else if (played === '0') {
                    target_slot.addClass('unplayed');
                }
                game.draggable('disable');
                game.off('click', '.game_image', addInfoCard);
                target_slot.setGameBoxDrag();
                target_slot.children('.game_image').activeInfoCard();
                target_slot.addTooltip();
                if (!$('.game_box > .empty_game_box').length) {
                    $('#fillslot').button('disable');
                }
            });

        if (!$('.game_box > .empty_game_box').length) {
            $(target).siblings('.pin').button('disable');
        }

        $(target).siblings('.blacklist').button({
            icons: {
                primary: "ui-icon-cancel"
            },
            text: false
        }).click(function () {
                var game = $(this).parent(),
                    numblacklisted = parseInt(game.children('.game_image').attr('data-numblacklisted'), 10);
                if (status === '2') {
                    // Un-Blacklist
                    loadData('savegamestatus', gameid, 0);
                    game.children('.game_image').attr('data-status', 0);
                    game.children('.game_image').attr('data-numblacklisted', numblacklisted - 1);
                    game.draggable('enable');
                    game.removeClass('blacklisted');
                    game.infoCardHardDisable();
                    game.children('.game_image').trigger('click');
                    game.removeAttr('style');
                    if(played === '0') {
                        game.addClass('unplayed');
                    }
                } else {
                    // Blacklist
                    loadData('savegamestatus', gameid, 2);
                    game.children('.game_image').attr('data-status', 2);
                    game.children('.game_image').attr('data-numblacklisted', numblacklisted + 1);
                    game.draggable('disable');
                    game.addClass('blacklisted');
                    game.removeClass('ui-state-disabled unplayed');
                    game.infoCardHardDisable();
                    game.children('.game_image').trigger('click');
                    if (!showblacklisted) {
                        game.fadeOut("slow");
                    }
                }
                updateChart();
            });

        $(target).siblings('.beat').button({
            icons: {
                primary: "ui-icon-check"
            },
            text: false
        }).click(function () {
                var game = $(this).parent(),
                    numbeaten = parseInt(game.children('.game_image').attr('data-numbeaten'), 10);
                loadData('savegamestatus', gameid, 1);
                game.children('.game_image').attr('data-status', 1);
                game.children('.game_image').attr('data-numbeaten', numbeaten + 1);
                game.draggable('enable');
                game.removeClass('blacklisted unplayed');
                game.addClass('beaten');
                game.removeClass('ui-state-disabled');
                game.infoCardHardDisable();
                game.children('.game_image').trigger('click');
                if (!showbeat) {
                    game.fadeOut("slow");
                }
                updateChart();
            });

        if (status === '1') {
            $(target).siblings('.blacklist').button('disable');
            $(target).siblings('.beat').button('disable').addClass('ui-state-focus');
        } else if (status === '2') {
            $(target).siblings('.pin').button('disable');
            $(target).siblings('.blacklist').addClass('ui-state-focus');
        }


        $(target).siblings('.play')
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

        if (gamelock[gameid] !== undefined) {
            $(target).achievementBar(false);
        } else {
            gamelock[gameid] = 1;
            window.setTimeout(function () {
                delete gamelock[gameid];
            }, 30000);
            $(target).achievementBar(true);
        }

        $(target).parent().addClass('list_box_active').stop().animate({
            height: '103',
            marginBottom: '-10'
        }, 400);
    }
    return false;
};

function scrollerCheck() {
    "use strict";
    var games_selector = $('header.games'),
        wrapper_selector = $('div.list_wrapper');
    if(games_selector[0].scrollWidth > games_selector[0].clientWidth)
    {
        games_selector.css('height', '154px');
        wrapper_selector.css('top', '215px');
    } else
    {
        games_selector.css('height', '137px');
        wrapper_selector.css('top', '198px');
    }
}

function chartSelectHandler() {
    "use strict";
    var listbox = $('.list_box > .game_image'),
        selectedItem = chart.getSelection()[0],
        selectedId;

    if (selectedItem) {
        selectedId = selectedItem.row;
        switch(selectedId)
        {
            case 0: // 100% Completed
            break;

            case 1: // Beaten
                if (showbeat) {
                    showbeat = false;
                    listbox.filter('[data-status=1]').parent().fadeOut("slow");
                } else {
                    showbeat = true;
                    listbox.filter('[data-status=1]').parent().fadeIn("slow");
                }
                $('#showbeat').toggleClass('focus');
            break;

            case 2: // Played
                if (showplayed) {
                    showplayed = false;
                    listbox.filter('[data-minutestotal!=0][data-status=0]').parent().fadeOut("slow");
                } else {
                    showplayed = true;
                    listbox.filter('[data-minutestotal!=0][data-status=0]').parent().fadeIn("slow");
                }
            break;

            case 3: // Unplayed
                if (showunplayed) {
                    showunplayed = false;
                    listbox.filter('[data-minutestotal=0][data-status=0]').parent().fadeOut("slow");
                } else {
                    showunplayed = true;
                    listbox.filter('[data-minutestotal=0][data-status=0]').parent().fadeIn("slow");
                }
            break;

            case 4: // Blacklisted
                if (showblacklisted) {
                    showblacklisted = false;
                    listbox.filter('[data-status=2]').parent().fadeOut("slow");
                } else {
                    showblacklisted = true;
                    listbox.filter('[data-status=2]').parent().fadeIn("slow");
                }
                $('#showblacklisted').toggleClass('focus');
            break;
        }
        chart.setSelection();
    }
}

updateChart = function () {
    "use strict";
    var listbox = $('.list_box > .game_image');

    stats.owned = listbox.length;
    stats.played = listbox.filter('[data-minutestotal!=0][data-achievper!=100][data-status=0]').length;
    stats.beaten = listbox.filter('[data-achievper!=100][data-status=1]').length;
    stats.blacklisted = listbox.filter('[data-achievper!=100][data-status=2]').length;
    stats.completed = listbox.filter('[data-achievper=100]').length;
    stats.unplayed = stats.owned - (stats.played + stats.beaten + stats.blacklisted + stats.completed);

    chartData.setCell(0, 1, stats.completed);
    chartData.setCell(1, 1, stats.beaten);
    chartData.setCell(2, 1, stats.played);
    chartData.setCell(3, 1, stats.unplayed);
    chartData.setCell(4, 1, stats.blacklisted);

    chart.draw(chartData, chartOptions);
};

function newChart() {
    "use strict";
    // Create the data table.
    chartData = new google.visualization.DataTable();
    chartData.addColumn('string', 'Status');
    chartData.addColumn('number', 'Games');
    chartData.addRows([
        ['Completed', stats.completed],
        ['Beaten', stats.beaten],
        ['Played', stats.played],
        ['Unplayed', stats.unplayed],
        ['Blacklisted', stats.blacklisted]
    ]);

    // Set chart options
    chartOptions = {'backgroundColor':'#2d2d2b',
        'chartArea':{'left':7, 'top':0, 'width':'100%', 'height':'100%'},
        'legend':{'position':'right', 'alignment':'center', 'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':9}},
        'slices':{0: {'color':'#ffcc00'}, 1: {'color':'#8bc53f'}, 2: {'color':'#62a7e3'}, 3: {'color':'#ff9933'}, 4: {'color':'#c80000'}},
        'pieSliceTextStyle':{'color':'black', 'fontName':'Arial', 'fontSize':11},
        'is3D':true,
        'width':210,
        'height':115
    };

    // Instantiate and draw our chart, passing in some options.
    chart = new google.visualization.PieChart(document.getElementById('account_stats'));
    google.visualization.events.addListener(chart, 'select', chartSelectHandler);

    chart.draw(chartData, chartOptions);
}


$(document).ready(function () {
    "use strict";
    var settings = $('#settings'),
        fillslot = $('#fillslot'),
        gamebox = $('.game_box > .game_image'),
        error = $('#error'),
        listbox = $('.list_box > .game_image');
    loggeduser = $('#terms').attr('data-user');

    if (loggeduser > 0) {
        tobeatnum = parseInt(settings.attr('data-tobeat'), 10);
        considerbeaten = settings.attr('data-considerbeaten');
        hidequickstats = settings.attr('data-hidequickstats');
        hideaccountstats = settings.attr('data-hideaccountstats');

        select = $('#numgames');

        slider = $("#numgamesslider").slider({
            min: 1,
            max: 10,
            range: "min",
            value: select[ 0 ].selectedIndex + 1,
            slide: function (event, ui) {
                select[ 0 ].selectedIndex = ui.value - 1;
            }
        });

        select.change(function () {
            slider.slider("value", this.selectedIndex + 1);
        });
    }

    // Preloading disabled for now
    // list of images to preload
//    preload([
//        '/img/loading.gif',
//        '/css3/images/ui-bg_flat_30_cccccc_40x100.png',
//        '/css3/images/ui-bg_flat_50_5c5c5c_40x100.png',
//        '/css3/images/ui-bg_glass_20_555555_1x400.png',
//        '/css3/images/ui-bg_glass_40_0078a3_1x400.png',
//        '/css3/images/ui-bg_glass_40_ffc73d_1x400.png',
//        '/css3/images/ui-bg_gloss-wave_25_333333_500x100.png',
//        '/css3/images/ui-bg_highlight-soft_80_eeeeee_1x100.png',
//        '/css3/images/ui-bg_inset-soft_25_000000_1x100.png',
//        '/css3/images/ui-bg_inset-soft_30_f58400_1x100.png',
//        '/css3/images/ui-icons_222222_256x240.png',
//        '/css3/images/ui-icons_4b8e0b_256x240.png',
//        '/css3/images/ui-icons_a83300_256x240.png',
//        '/css3/images/ui-icons_cccccc_256x240.png',
//        '/css3/images/ui-icons_ffffff_256x240.png']);

    // The refresh button sends a ajax request to reload data from steam
    // The data is then used to update the user's displayed data, refreshing all elements
    // Can be used to show a game after you've bought it, get your current user status etc

    $('#showbeat')
        .button({
            text: false,
            icons: {
                primary: 'ui-icon-check'
            }
        }).addClass('focus')
        .click(function () {
            var listbox = $('.list_box > .game_image');
            if (showbeat) {
                showbeat = false;
                listbox.filter('[data-status=1]').parent().fadeOut("slow");
            } else {
                showbeat = true;
                listbox.filter('[data-status=1]').parent().fadeIn("slow");
            }
            $(this).toggleClass('focus');
            $(this).removeClass('ui-state-focus');
        });

    $('#showblacklisted')
        .button({
            text: false,
            icons: {
                primary: 'ui-icon-cancel'
            }
        }).addClass('focus')
        .click(function () {
            var listbox = $('.list_box > .game_image');
            if (showblacklisted) {
                showblacklisted = false;
                listbox.filter('[data-status=2]').parent().fadeOut("slow");
            } else {
                showblacklisted = true;
                listbox.filter('[data-status=2]').parent().fadeIn("slow");
            }
            $(this).toggleClass('focus');
            $(this).removeClass('ui-state-focus');
        });

    fillslot
        .button({
            text: false,
            icons: {
                primary: 'ui-icon-plusthick'
            }
        })
        .click(function () {
            var game,
                target_slot = $('.game_box > .empty_game_box:first').parent(),
                status,
                played;

            if (considerbeaten === '1') {
                game = $('.list_box:not(.disabled,.blacklisted,.ui-state-disabled)').random();
            } else {
                game = $('.list_box:not(.disabled,.beaten,.blacklisted,.ui-state-disabled)').random();
            }

            if (game.length) {
                loadData('savegameslot', game.children('.game_image').attr('data-gameid'), target_slot.attr('id').substr(9));
                status = game.children('.game_image').attr('data-status');
                played = game.children('.game_image').attr('data-minutestotal');


                game.infoCardHardDisable();
                target_slot.html(game.html());
                if (status === '1') {
                    target_slot.addClass('beaten');
                } else if(played === '0') {
                    target_slot.addClass('unplayed');
                }
                game.draggable('disable');
                game.off('click', '.game_image', addInfoCard);
                target_slot.setGameBoxDrag();
                target_slot.children('.game_image').activeInfoCard();
                target_slot.addTooltip();

                if (!$('.game_box > .empty_game_box').length) {
                    $(this).button('disable');
                }
            } else {
                errorMessage('No eligible game found. :(');
            }

            $(this).removeClass('ui-state-focus');
            $('.ui-tooltip').remove();
        });

    if (!$('.game_box > .empty_game_box').length) {
        fillslot.button('disable');
    }

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
    $('#showsettings')
        .button({
            text: false,
            icons: {
                primary: 'ui-icon-wrench'
            }
        })
        .click(function () {
            $('#settings').dialog({
                draggable: true,
                title: 'Settings',
                width: '80%',
                modal: true,
                buttons: [
                    {
                        text: "Save & Close", click: function () {
                        var x,
                            newtobeatnum = parseInt($('#numgames').val(), 10),
                            gameid,
                            box,
                            newconsiderbeaten = $('#considerbeaten').attr('checked') ? '1' : '0',
                            newhidequickstats = $('#hidequickstats').attr('checked') ? '1' : '0',
                            newhideaccountstats = $('#hideaccountstats').attr('checked') ? '1' : '0';

                        if (tobeatnum !== newtobeatnum) {
                            loadData('savenumtobeat', 0, newtobeatnum);

                            if (newtobeatnum > tobeatnum) {
                                // Add more cards
                                for (x = (parseInt(tobeatnum, 10) + 1); x <= newtobeatnum; x = x + 1) {
                                    $('.game_boxes').append('<div id="game_box_' + x + '" class="game_box"><div class="empty_game_box"></div></div>');
                                    $('#game_box_' + x).setListDrop();
                                }
                                $('.pin').button('enable');
                                $('#fillslot').button('enable');
                            } else {
                                for (x = parseInt(tobeatnum, 10); x > newtobeatnum; x = x - 1) {
                                    box = $('#game_box_' + x);
                                    gameid = box.children('.game_image').attr('data-gameid');
                                    $('.list_boxes').find('#' + gameid).draggable('enable').on('click', '.game_image', addInfoCard);
                                    box.remove();
                                }
                                if (!$('.game_box > .empty_game_box').length) {
                                    $('#fillslot').button('disable');
                                    $('.pin').button('disable');
                                }
                            }

                            tobeatnum = newtobeatnum;
                            scrollerCheck();
                        }

                        if (considerbeaten !== newconsiderbeaten) {
                            loadData('saveconsiderbeaten', 0, newconsiderbeaten);

                            considerbeaten = newconsiderbeaten;
                        }

                        if (hidequickstats !== newhidequickstats) {
                            loadData('savehidequickstats', 0, newhidequickstats);
                            hidequickstats = newhidequickstats;
                            if (hidequickstats === '1') {
                                $('.stat_box').css('display', 'none');
                                $('.time_box').css('display', 'none');
                            } else {
                                $('.stat_box').css('display', '');
                                $('.time_box').css('display', '');
                            }
                        }

                        if (hideaccountstats !== newhideaccountstats) {
                            loadData('savehideaccountstats', 0, newhideaccountstats);
                            hideaccountstats = newhideaccountstats;
                            if (hideaccountstats === '1') {
                                $('#account_stats').css('display', 'none');
                            } else {
                                $('#account_stats').css('display', '');
                            }
                        }

                        $(this).dialog("close");
                    }},
                    { text: "Cancel", click: function () {
                        select[0].selectedIndex = tobeatnum - 1;
                        slider.slider("value", select[0].selectedIndex + 1);
                        if (considerbeaten === '1') {
                            $('#considerbeaten').prop("checked", true);
                        } else {
                            $('#considerbeaten').prop("checked", false);
                        }
                        if (hidequickstats === '1') {
                            $('#hidequickstats').prop("checked", true);
                        } else {
                            $('#hidequickstats').prop("checked", false);
                        }
                        if (hideaccountstats === '1') {
                            $('#hideaccountstats').prop("checked", true);
                        } else {
                            $('#hideaccountstats').prop("checked", false);
                        }
                        $(this).dialog("close");
                    }}
                ]
            });
        });


    $('#logout')
        .button({
            text: false,
            icons: {
                primary: 'ui-icon-power'
            }
        })
        .click(function () {
            window.location.href = "?logout";
        });

    $('#showhelp')
        .button({
            text: false,
            icons: {
                primary: 'ui-icon-help'
            }
        })
        .click(function () {
            $('#terms').dialog({
                draggable: true,
                title: 'FAQ & Terms',
                width: '80%',
                modal: true,
                buttons: [
                    {
                        text: "Ok", click: function () {
                        $(this).dialog("close");
                    }}
                ]
            });
        });

    // Adds tooltips to all game images
    $('.game_image').addTooltip();

    // Binds the addInfoCard function to mouseclick on game images.
    $('.list_box:not(.disabled)').on('click', '.game_image', addInfoCard);

    // Enables dragging of list_box elements
    $('.list_box').setListDrag();

    // Disables dragging on list_box elements that are already in the "to beat" list
    $('.list_box.disabled').draggable('disable').removeClass('disabled');

    $('.list_box.blacklisted').draggable('disable').removeClass('ui-state-disabled');

    // Makes the "to beat" boxes droppable
    $('.game_box').setListDrop();

    // Activates the InfoCards for games in the "to beat" list
    gamebox.activeInfoCard();

    // Makes "to beat" games draggable
    gamebox.parent().setGameBoxDrag();

    // Makes the entire lower area droppable
    $('.list_wrapper').setGameBoxDrop();

    // Register the resize handler to make room for the scrollbar
    $(window).on('resize', scrollerCheck);

    // Tooltip setup
    $(document).tooltip({
        track: true,
        show: {
            delay: 500
        }
    });

    scrollerCheck();

    stats.owned = listbox.length;
    stats.played = listbox.filter('[data-minutestotal!=0][data-achievper!=100][data-status=0]').length;
    stats.beaten = listbox.filter('[data-achievper!=100][data-status=1]').length;
    stats.blacklisted = listbox.filter('[data-achievper!=100][data-status=2]').length;
    stats.completed = listbox.filter('[data-achievper=100]').length;
    stats.unplayed = stats.owned - (stats.played + stats.beaten + stats.blacklisted + stats.completed);

    google.load('visualization', '1.0', {'packages':['corechart'], 'callback' : newChart});

    if (error.length) {
        errorMessage(error.html());
    }

    // Triggers an initial AJAX request
    if (loggeduser > 0) {
        loadData('getsteamdata');
    }
});