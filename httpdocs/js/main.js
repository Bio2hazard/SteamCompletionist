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
    isowner = false,
    select,
    slider,
    tobeatnum,
    considerbeaten,
    hidequickstats,
    hideaccountstats,
    hidesocial,
    accountprivacy,
    addInfoCard,
    newStatsChart,
    stats = [],
    updateChart,
    chart,
    chartData,
    chartOptions,
    statsData;

String.prototype.getDDHHMM = function () {
    "use strict";
    var minutes_total    = parseInt(this, 10),
        days = Math.floor(((minutes_total / 60) / 24)),
        hours   = Math.floor((minutes_total / 60) - (days * 24)),
        minutes = Math.floor(minutes_total - ((hours * 60) + ((days * 24) * 60) )),
        time;

    if (days    < 10) {days    = "0"+days;}
    if (hours   < 10) {hours   = "0"+hours;}
    if (minutes < 10) {minutes = "0"+minutes;}
    time    = days+' Days '+hours+':'+minutes;
    return time;
};

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
function loadData(mode, gameid, value, search) {
    "use strict";
    gameid = (Math.floor(gameid) === parseInt(gameid, 10) && $.isNumeric(gameid)) ? gameid : 0;
    value = (Math.floor(value) === parseInt(value, 10) && $.isNumeric(value)) ? value : 0;

    $.get('/ajax.php', {
            user: loggeduser,
            mode: mode,
            gid: gameid,
            value: value,
            search: search
        },

        function (data) {
            var game;

            if (data !== undefined) {
                if (data.errorlog !== undefined) {
                    errorMessage(data.errorlog);
                }

                if (data.searchresult !== undefined) {
                    window.location.href = "/" + data.searchresult + "/";
                }

                if (data.stats !== undefined) {
                    statsData = data.stats;
                    google.load('visualization', '1.0', {'packages':['corechart'], 'callback' : newStatsChart});
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
                        var game = $('#' + this.appid),
                            game_image;
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
                            // We need to use the less fast .game_image search so that items in the "to beat" list are affected as well.
                            // This can be potentially optimized by using game.children('.game_image'). and additionally scanning the "to beat" slots.
                            game_image = $('.game_image[data-gameid="' + this.appid + '"]');
                            game_image.attr('data-minutes2weeks', this.minutes2weeks).attr('data-minutestotal', this.minutestotal).attr('data-achievper', this.achievper);

                            // Update any active time boxes
                            game_image.siblings('.time_box').children('span').html(this.minutestotal.toString().getHHMM());

                            // Remove unplayed class if the game has been played now
                            if(game_image.parent('div').hasClass('unplayed') && this.minutestotal > 0) {
                                game_image.parent('div').removeClass('unplayed');
                            }
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
                        updateChart();
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
        accept: '.list_box,.game_box_active',
        addClasses: false,
        hoverClass: 'over',
        tolerance: 'pointer',
        drop: function (event, ui) {
            var gameid, status, played, box_old_id, box_new_id, box_old, box_new;

            if($(ui.draggable).hasClass('game_box_active')) {
                box_old_id = this.id.substr(9);
                box_new_id = ui.draggable[0].id.substr(9);
                box_old = this.innerHTML;
                box_new = ui.draggable[0].innerHTML;

                loadData('swapgameslot', box_new_id, box_old_id);

                $(ui.draggable).draggable('option', 'revertDuration', 0);
                $(this).html(box_new);
                $(ui.draggable).html(box_old);

                if(!$(this).hasClass('game_box_active')) {
                    // We move the game to a inactive game box, so we need to resize appropriately
                    $(ui.draggable).data('delete', '1');
                    $(this).setGameBoxDrag();

                    $(this).addClass('game_box_active').stop().animate({
                        height: '103',
                        marginBottom: '-10'
                    }, 400);

                    $(ui.draggable).stop().animate({
                        height: '69',
                        marginBottom: '2em'
                    }, 400, function () {
                        $(ui.draggable).removeClass('game_box_active');
                        //noinspection JSValidateTypes
                        $(ui.draggable).children('.act_btn').remove();
                        //noinspection JSValidateTypes
                        $(ui.draggable).children('.achiev_bar').remove();
                    });
                }
            } else {
                loadData('savegameslot', ui.draggable[0].id, this.id.substr(9));
                gameid = $(this).find('.game_image').attr('data-gameid');
                status = $(ui.draggable).find('.game_image').attr('data-status');
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
            if(isowner) {
                window.location = 'steam://url/SteamIDAchievementsPage/' + $(this).siblings(".game_image").attr('data-gameid');
            } else {
                window.location = 'steam://url/SteamIDPage/' + loggeduser;
            }
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

        if(!isowner) {
            $(this).parent().append('<button class="act_btn store">View on Steam Store</button><button class="act_btn beat">Beaten status</button><button class="act_btn blacklist">Blacklisted status</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time user spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        } else if (status === '1') {
            $(this).parent().append('<button class="act_btn unpin">Remove from my &quot;to beat&quot; list</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Un-mark game as beat</button><button class="act_btn blacklist">Blacklist this game</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time you\'ve spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        } else {
            $(this).parent().append('<button class="act_btn unpin">Remove from my &quot;to beat&quot; list</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Mark game as beat</button><button class="act_btn blacklist">Blacklist this game</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time you\'ve spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        }

        if (hidequickstats === '1') {
            $('.stat_box').css('display', 'none');
            $('.time_box').css('display', 'none');
        }

        if(isowner) {
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
        }

        $(this).siblings('.blacklist').button({
            icons: {
                primary: "ui-icon-cancel"
            },
            text: false
        }).click(function () {
                var gameid = $(this).siblings('.game_image').attr('data-gameid'),
                    status = $(this).siblings('.game_image').attr('data-status'),
                    numblacklisted = parseInt($(this).siblings('.game_image').attr('data-numblacklisted'), 10),
                    numbeaten = parseInt($(this).siblings('.game_image').attr('data-numbeaten'), 10),
                    parent = $(this).parent(),
                    listboxes = $('.list_boxes');
                loadData('savegamestatus', gameid, 2);
                if(status === '1') {
                    listboxes.find('#' + gameid).children('.game_image').attr('data-numbeaten', numbeaten - 1);
                }
                listboxes.find('#' + gameid).removeClass('ui-state-disabled unplayed beaten').addClass('blacklisted').on('click', '.game_image', addInfoCard).children('.game_image').attr('data-status', '2').attr('data-numblacklisted', numblacklisted + 1);
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
                parent.removeClass('unplayed beaten');
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
                    status = $(this).siblings('.game_image').attr('data-status'),
                    numblacklisted = parseInt($(this).siblings('.game_image').attr('data-numblacklisted'), 10),
                    numbeaten = parseInt($(this).siblings('.game_image').attr('data-numbeaten'), 10),
                    played = $(this).siblings('.game_image').attr('data-minutestotal'),
                    parent = $(this).parent(),
                    listboxes = $('.list_boxes');
                if(status === '1') {
                    // Un-Beat game
                    loadData('savegamestatus', gameid, 0);
                    listboxes.find('#' + gameid).removeClass('beaten').draggable('enable').on('click', '.game_image', addInfoCard).children('.game_image').attr('data-status', '0').attr('data-numbeaten', numbeaten - 1);
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
                    parent.removeClass('unplayed beaten');
                    if(played === '0') {
                        listboxes.find('#' + gameid).addClass('unplayed');
                    }
                    if (!showbeat) {
                        listboxes.find('#' + gameid).fadeIn("slow");
                    }
                } else {
                    // Beat game
                    loadData('savegamestatus', gameid, 1);
                    if(status === '2') {
                        listboxes.find('#' + gameid).children('.game_image').attr('data-numblacklisted', numblacklisted - 1);
                    }
                    listboxes.find('#' + gameid).removeClass('unplayed').draggable('enable').addClass('beaten').on('click', '.game_image', addInfoCard).children('.game_image').attr('data-status', '1').attr('data-numbeaten', numbeaten + 1);
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
                }
                updateChart();
            });

        if (status === '1') {
            $(this).siblings('.beat').addClass('ui-state-focus');
        }

        if (!isowner) {
            $(this).siblings('.beat,.blacklist').button('disable');
        }

        if(isowner) {
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
        } else {
            $(this).siblings('.store')
                .button({
                    icons: {
                        primary: "ui-icon-cart"
                    },
                    text: false
                })
                .click(function () {
                    window.location = 'steam://store/' + gameid;
                    $('.ui-tooltip').remove();
                    $(this).removeClass('ui-state-focus');
                });
        }

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

        if(!isowner) {
            $(this).parent().append('<button class="act_btn store">View on Steam Store</button><button class="act_btn beat">Beaten status</button><button class="act_btn blacklist">Blacklisted status</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time user spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        } else if (status === '2') {
            $(target).parent().append('<button class="act_btn pin">Cannot add a blacklisted game</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Mark game as beat</button><button class="act_btn blacklist">Un-Blacklist this game</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time you\'ve spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        } else if (status === '1') {
            $(target).parent().append('<button class="act_btn pin">Add to my &quot;to beat&quot; list</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Un-mark game as beat</button><button class="act_btn blacklist">Blacklist this game</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time you\'ve spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        } else {
            $(target).parent().append('<button class="act_btn pin">Add to my &quot;to beat&quot; list</button><button class="act_btn play">Play/Install game</button><button class="act_btn beat">Mark game as beat</button><button class="act_btn blacklist">Blacklist this game</button><div class="achiev_bar sel_progress" title="Achievement percentage"></div><div class="time_box" title="Quick Stats: Total time you\'ve spent playing this game"><span>' + played.getHHMM() + '</span></div><div class="stat_box" title="Quick Stats: Steam Completionist members who Own (blue), Beaten (green) and Blacklisted (red) this game"><span class="stat_owned">' + numowned + '</span> <span class="stat_beaten">' + numbeaten + '</span> <span class="stat_blacklisted">' + numblacklisted + '</span> </div>');
        }

        if (hidequickstats === '1') {
            $('.stat_box').css('display', 'none');
            $('.time_box').css('display', 'none');
        }

        if(isowner) {
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
        }

        $(target).siblings('.blacklist').button({
            icons: {
                primary: "ui-icon-cancel"
            },
            text: false
        }).click(function () {
                var game = $(this).parent(),
                    numblacklisted = parseInt(game.children('.game_image').attr('data-numblacklisted'), 10),
                    numbeaten = parseInt(game.children('.game_image').attr('data-numbeaten'), 10);

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
                    if(status === '1') {
                        game.children('.game_image').attr('data-numbeaten', numbeaten - 1);
                    }
                    game.children('.game_image').attr('data-numblacklisted', numblacklisted + 1);
                    game.draggable('disable');
                    game.addClass('blacklisted');
                    game.removeClass('ui-state-disabled unplayed beaten');
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
                    numbeaten = parseInt(game.children('.game_image').attr('data-numbeaten'), 10),
                    numblacklisted = parseInt(game.children('.game_image').attr('data-numblacklisted'), 10);
                if(status === '1') {
                    // Un-Beat
                    loadData('savegamestatus', gameid, 0);
                    game.children('.game_image').attr('data-status', 0);
                    game.children('.game_image').attr('data-numbeaten', numbeaten - 1);
                    game.draggable('enable');
                    game.removeClass('beaten');
                    game.infoCardHardDisable();
                    game.children('.game_image').trigger('click');
                    game.removeAttr('style');
                    if(played === '0') {
                        game.addClass('unplayed');
                    }
                } else {
                    // Beat
                    loadData('savegamestatus', gameid, 1);
                    game.children('.game_image').attr('data-status', 1);
                    if(status === '2') {
                        game.children('.game_image').attr('data-numblacklisted', numblacklisted - 1);
                    }
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
                }
                updateChart();
            });

        if (status === '1') {
            $(target).siblings('.beat').addClass('ui-state-focus');
        } else if (status === '2') {
            if(isowner) {
                $(target).siblings('.pin').button('disable');
            }
            $(target).siblings('.blacklist').addClass('ui-state-focus');
        }

        if (!isowner) {
            $(this).siblings('.beat,.blacklist').button('disable');
        }

        if(isowner) {
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
        } else {
            $(target).siblings('.store')
                .button({
                    icons: {
                        primary: "ui-icon-cart"
                    },
                    text: false
                }).click(function () {
                    window.location = 'steam://store/' + gameid;
                    $('.ui-tooltip').remove();
                    $(this).removeClass('ui-state-focus');
                });
        }

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
    $('#stats').dialog('option','height',$(window).height() * 0.90);
    $('#terms').dialog('option','height',$(window).height() * 0.80);
}

function sendSearchForm(e) {
    "use strict";
    if (e.preventDefault) {
        e.preventDefault();
    }

    var searchbar = $('#searchbar');

    if(searchbar.val() === searchbar.attr('placeholder')) {
        searchbar.val('');
    }

    if(searchbar.val() !== '') {
        loadData('search', 0, 0, searchbar.val());
    }

    // You must return false to prevent the default form behavior
    return false;
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
    $('<span style="font-family: Arial; font-size: 9px; color:#B3B3B3;">Click category to filter</span>').appendTo('.home').position({
        my: "right bottom",
        at: "right-8 bottom",
        of: "#account_stats"
    });
}

newStatsChart = function() {
    "use strict";

    var statsOwnedChart,
        statsOwnedChartData,
        statsOwnedChartOptions,
        statsBeatenChart,
        statsBeatenChartData,
        statsBeatenChartOptions,
        statsBlacklistedChart,
        statsBlacklistedChartData,
        statsBlacklistedChartOptions,
        statsLeastPlayedChart,
        statsLeastPlayedChartData,
        statsLeastPlayedChartOptions,
        statsUnbeatenChart,
        statsUnbeatenChartData,
        statsUnbeatenChartOptions,
    //statsLeastOwnedChart,
    //statsLeastOwnedChartData,
    //statsLeastOwnedChartOptions,
        statsMostTimeChart,
        statsMostTimeChartData,
        statsMostTimeChartOptions,
        statsRecentTimeChart,
        statsRecentTimeChartData,
        statsRecentTimeChartOptions,
        acc_stats = '',
        listbox_selector = $('.list_box'),
        totalminutes = 0;

    if(loggeduser > 0) {
        listbox_selector.each(function() {
            totalminutes += parseInt($(this).children('.game_image').attr('data-minutestotal'), 10);
        });

        acc_stats = 'This account has ' + listbox_selector.length + ' games ( not counting DLC ), and has spent ' + totalminutes.toString().getDDHHMM() + ' playing them.<br><br>';
    }

    $('#stats').empty().append(
        '<p><br>' + acc_stats +
            '<div id="statsmosttimechart" class="statsChart"></div>' +
            '<div id="statsrecenttimechart" class="statsChart"></div>' +
            '<div id="statsleastplayedchart" class="statsChart"></div>' +
            '<div id="statsownedchart" class="statsChart"></div>' +
            '<div id="statsbeatenchart" class="statsChart"></div>' +
            '<div id="statsblacklistedchart" class="statsChart"></div>' +
            '<div id="statsunbeatenchart" class="statsChart"></div>' +
            //'<div id="statsleastownedchart" class="statsChart"></div>' +
            '<div>Please keep in mind that these stats are not entirely accurate.<br>' +
            'They only include users of this website, and user stats only get refreshed when they visit the website.<br>' +
            'In short: Inactive people and people who don\'t update / categorize their games affect these stats.</div>' +
        '</p>');



    statsOwnedChartData = new google.visualization.DataTable(statsData[1]);
    statsBeatenChartData = new google.visualization.DataTable(statsData[2]);
    statsBlacklistedChartData = new google.visualization.DataTable(statsData[3]);
    statsLeastPlayedChartData = new google.visualization.DataTable(statsData[4]);
    statsUnbeatenChartData = new google.visualization.DataTable(statsData[5]);
    //statsLeastOwnedChartData = new google.visualization.DataTable(statsData[6]);
    statsMostTimeChartData = new google.visualization.DataTable(statsData[7]);
    statsRecentTimeChartData = new google.visualization.DataTable(statsData[8]);

    statsOwnedChartOptions = {
        'chartArea':{'left':150, 'top':30, 'width':'300', 'height':'440'},
        'hAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15}},
        'vAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':12}},
        'title': 'Most Owned Games',
        'titleTextStyle': {'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15},
        'colors': ['#62A7E3'],
        'backgroundColor': '#000',
        'legend':{'position':'none'}
    };

    statsBeatenChartOptions = {
        'chartArea':{'left':150, 'top':30, 'width':'300', 'height':'440'},
        'hAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15}},
        'vAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':12}},
        'title': 'Most Beaten Games',
        'titleTextStyle': {'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15},
        'colors': ['#8BC53F'],
        'backgroundColor': '#000',
        'legend':{'position':'none'}
    };

    statsBlacklistedChartOptions = {
        'chartArea':{'left':150, 'top':30, 'width':'300', 'height':'440'},
        'hAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15}},
        'vAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':12}},
        'title': 'Most Blacklisted Games',
        'titleTextStyle': {'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15},
        'colors': ['#C80000'],
        'backgroundColor': '#000',
        'legend':{'position':'none'}
    };

    statsLeastPlayedChartOptions = {
        'chartArea':{'left':150, 'top':30, 'width':'300', 'height':'440'},
        'hAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15}},
        'vAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':12}},
        'title': 'Games that havn\'t even been booted up once',
        'titleTextStyle': {'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':13},
        'colors': ['#FF9933'],
        'backgroundColor': '#000',
        'legend':{'position':'none'}
    };

    statsUnbeatenChartOptions = {
        'chartArea':{'left':150, 'top':30, 'width':'300', 'height':'440'},
        'hAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15}},
        'vAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':12}},
        'title': 'Most owned, least beaten games',
        'titleTextStyle': {'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':13},
        'colors': ['#0078A3'],
        'backgroundColor': '#000',
        'legend':{'position':'none'}
    };

//    statsLeastOwnedChartOptions = {
//        'chartArea':{'left':150, 'top':30, 'width':'300', 'height':'440'},
//        'hAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15}},
//        'vAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':12}},
//        'title': 'Least owned games',
//        'titleTextStyle': {'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':13},
//        'colors': ['#0078A3'],
//        'backgroundColor': '#000',
//        'legend':{'position':'none'}
//    };

    statsMostTimeChartOptions = {
        'chartArea':{'left':150, 'top':30, 'width':'300', 'height':'440'},
        'hAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15}},
        'vAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':12}},
        'title': 'Most time spent playing',
        'titleTextStyle': {'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15},
        'colors': ['#53439B'],
        'backgroundColor': '#000',
        'legend':{'position':'none'}
    };

    statsRecentTimeChartOptions = {
        'chartArea':{'left':150, 'top':30, 'width':'300', 'height':'440'},
        'hAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':15}},
        'vAxis':{'textStyle':{'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':12}},
        'title': 'Most time spent playing ( last 2 weeks )',
        'titleTextStyle': {'color':'#B3B3B3', 'fontName':'Arial', 'fontSize':14},
        'colors': ['#8E439B'],
        'backgroundColor': '#000',
        'legend':{'position':'none'}
    };

    statsOwnedChart = new google.visualization.BarChart(document.getElementById('statsownedchart'));
    statsBeatenChart = new google.visualization.BarChart(document.getElementById('statsbeatenchart'));
    statsBlacklistedChart = new google.visualization.BarChart(document.getElementById('statsblacklistedchart'));
    statsLeastPlayedChart = new google.visualization.BarChart(document.getElementById('statsleastplayedchart'));
    statsUnbeatenChart = new google.visualization.BarChart(document.getElementById('statsunbeatenchart'));
    //statsLeastOwnedChart = new google.visualization.BarChart(document.getElementById('statsleastownedchart'));
    statsMostTimeChart = new google.visualization.BarChart(document.getElementById('statsmosttimechart'));
    statsRecentTimeChart = new google.visualization.BarChart(document.getElementById('statsrecenttimechart'));

    statsOwnedChart.draw(statsOwnedChartData, statsOwnedChartOptions);
    statsBeatenChart.draw(statsBeatenChartData, statsBeatenChartOptions);
    statsBlacklistedChart.draw(statsBlacklistedChartData, statsBlacklistedChartOptions);
    statsLeastPlayedChart.draw(statsLeastPlayedChartData, statsLeastPlayedChartOptions);
    statsUnbeatenChart.draw(statsUnbeatenChartData, statsUnbeatenChartOptions);
    //statsLeastOwnedChart.draw(statsLeastOwnedChartData, statsLeastOwnedChartOptions);
    statsMostTimeChart.draw(statsMostTimeChartData, statsMostTimeChartOptions);
    statsRecentTimeChart.draw(statsRecentTimeChartData, statsRecentTimeChartOptions);
};


$(document).ready(function () {
    "use strict";
    var settings = $('#settings'),
        fillslot = $('#fillslot'),
        gamebox = $('.game_box > .game_image'),
        error = $('#error'),
        listbox = $('.list_box > .game_image'),
        terms = $('#terms'),
        searchform = $('#usersearch');

    loggeduser = terms.attr('data-user');

    if (loggeduser > 0) {
        if(terms.attr('data-owner') === '1') {
            isowner = true;
        }

        tobeatnum = parseInt(settings.attr('data-tobeat'), 10);
        considerbeaten = settings.attr('data-considerbeaten');
        hidequickstats = settings.attr('data-hidequickstats');
        hideaccountstats = settings.attr('data-hideaccountstats');
        hidesocial = settings.attr('data-hidesocial');
        accountprivacy = settings.attr('data-private');

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

    if(isowner) {
        // These buttons are only available to owners
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
                                newhideaccountstats = $('#hideaccountstats').attr('checked') ? '1' : '0',
                                newaccountprivacy = $('#private').attr('checked') ? '1' : '0',
                                newhidesocial = $('#hidesocial').attr('checked') ? '1' : '0',

                                socialdiv = $('#social');

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

                            if (hidesocial !== newhidesocial) {
                                loadData('savehidesocial', 0, newhidesocial);
                                hidesocial = newhidesocial;
                                if (hidesocial === '1') {
                                    socialdiv.css('display', 'none');
                                } else {

                                    if(socialdiv.children().length === 0) {
                                        socialdiv.html('<a class="addthis_button_facebook"></a><a class="addthis_button_twitter"></a><a class="addthis_button_google_plusone_share"></a><a class="addthis_button_compact"></a><a class="addthis_counter addthis_bubble_style"></a>');
                                        $.getScript("http://s7.addthis.com/js/300/addthis_widget.js#pubid=ra-518cf75f752f8e26&domready=1");
                                    }
                                    socialdiv.css('display', '');
                                }
                            }

                            if (accountprivacy !== newaccountprivacy) {
                                loadData('saveprivate', 0, newaccountprivacy);
                                accountprivacy = newaccountprivacy;
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
                            if (hidesocial === '1') {
                                $('#hidesocial').prop('checked', true);
                            } else {
                                $('#hidesocial').prop('checked', false);
                            }
                            if (accountprivacy === '1') {
                                $('#private').prop('checked', true);
                            } else {
                                $('#private').prop('checked', false);
                            }
                            $(this).dialog("close");
                        }}
                    ]
                });
            });
    }

    $('[placeholder]').focus(function() {
        var input = $(this);
        if (input.val() === input.attr('placeholder')) {
            input.val('');
            input.removeClass('placeholder');
        }
    }).blur(function() {
            var input = $(this);
            if (input.val() === '' || input.val() === input.attr('placeholder')) {
                input.addClass('placeholder');
                input.val(input.attr('placeholder'));
            }
        }).blur();

    searchform.on('submit', sendSearchForm);

    $('#search')
        .button({
            text: false,
            icons: {
                primary: 'ui-icon-search'
            }
        })
        .click(function () {
            searchform.submit();
            $(this).removeClass('ui-state-focus');
            $('.ui-tooltip').remove();
        });

    $('#stats').dialog({
        draggable: true,
        title: 'Stats',
        width: '95%',
        height: $(window).height() * 0.90,
        autoOpen: false,
        modal: true,
        buttons: [
            {
                text: "Close", click: function () {
                $(this).dialog("close");
            }}
        ]
    });

    $('#showstats')
        .button({
            text: false,
            icons: {
                primary: 'ui-icon-clipboard'
            }
        })
        .click(function () {
            var stats = $('#stats');

            if(statsData === undefined) {
                stats.empty().append('<p><br><img src="/img/loading.gif" alt="Loading" height="16" width="16"><br>Stats are loading, please wait.</p>');
                loadData('stats', 0, 0);
            }
            stats.dialog("open");
        });

    if(!isowner) {
        $('#home')
            .button({
                text: false,
                icons: {
                    primary: 'ui-icon-home'
                }
            })
            .click(function () {
                window.location.href = "/";
            });
    } else {
        $('#logout')
            .button({
                text: false,
                icons: {
                    primary: 'ui-icon-power'
                }
            })
            .click(function () {
                window.location.href = "/?logout";
            });
    }


    terms.dialog({
        draggable: true,
        title: 'FAQ & Terms',
        width: '80%',
        height: $(window).height() * 0.80,
        autoOpen: false,
        modal: true,
        buttons: [
            {
                text: "Ok", click: function () {
                $(this).dialog("close");
            }}
        ]
    });

    $('#showhelp')
        .button({
            text: false,
            icons: {
                primary: 'ui-icon-help'
            }
        })
        .click(function () {
            $('#terms').dialog("open");
        });

    // Adds tooltips to all game images
    $('.game_image').addTooltip();

    // Binds the addInfoCard function to mouseclick on game images.
    $('.list_box:not(.disabled)').on('click', '.game_image', addInfoCard);

    // Activates the InfoCards for games in the "to beat" list
    gamebox.activeInfoCard();

    // Enables dragging of list_box elements
    if(isowner) {
        $('.list_box').setListDrag();

        // Disables dragging on list_box elements that are already in the "to beat" list
        $('.list_box.disabled').draggable('disable').removeClass('disabled');

        $('.list_box.blacklisted').draggable('disable').removeClass('ui-state-disabled');

        // Makes the "to beat" boxes droppable
        $('.game_box').setListDrop();

        // Makes the entire lower area droppable
        $('.list_wrapper').setGameBoxDrop();

        // Makes "to beat" games draggable
        gamebox.parent().setGameBoxDrag();
    } else {
        $('.list_box.disabled').addClass('ui-state-disabled').removeClass('disabled');
    }

    // Register the resize handler to make room for the scrollbar
    $(window).on('resize', scrollerCheck);

    // Tooltip setup
    $(document).tooltip({
        track: true,
        show: {
            delay: 500
        }
    });

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
        scrollerCheck();
        loadData('getsteamdata');
    }
});