// Sharetronix Htmlarea namespace
var Htmlarea = function () {
    var autocompleteActive = false;
    var searchString = '';
    var startPos = 0;
    var endPos = 0;
    var currentPos = 0;
    var aliases = new Array();

    var tagsToReplace = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;'
        };

    function replaceTag(tag) {
        return tagsToReplace[tag] || tag;
    }
    
    function acMoveSelection(key) {
        acList = $('.htmlarea-ac-container');
        if (key == 38) { // up
            if ($('li.selection', acList).length > 0) {
                prev = $('li.selection', acList).prev();
                $('.selection').removeClass('selection');
                $(prev).addClass('selection');
            } else {
                $('li:last', acList).addClass('selection');
            }
        } else if (key == 40) { // down
            if ($('li.selection', acList).length > 0) {
                next = $('li.selection', acList).next();
                $('.selection').removeClass('selection');
                $(next).addClass('selection');
            } else {
                $('li:first', acList).addClass('selection');
            }
        }
    }

    function generateACList(result) { // build autocomplete list and attach events - click/hover
        if (result.users.length == 0) {
            //var users = $('<div />').addClass('htmlarea-ac-title').html('There are no users matching your query!');
            var users = $('<span />');
        } else {
            var users = $('<ul />');
            for (var i = 0; i < result.users.length; i++) {
                var userItem = $('<li />').data('alias', result.users[i].username);
                var userImage = $('<img />').attr('src', result.users[i].avatar_url);

                searchStr = $('.ac-placeholder').text().replace(/@/gi, '');
                tmpName = result.users[i].fullname.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + searchStr.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
                var userName = $('<span />').html(tmpName);

                var userClear = $('<div />').addClass('clear');
                $(userItem).append(userImage);
                $(userItem).append(userName);
                $(userItem).append(userClear);
                $(users).append($(userItem));
            }

            $('li:first', users).addClass('selection');

            $('li', users).click(function () {
                insertUserLink($(this));
                stopAC();
            }).hover(
                function () { $(this).addClass('hover'); },
                function () { $(this).removeClass('hover'); }
            );
        }
        return $(users);
    }

    function HtmlAreaACSuccess(result, userContext) { $(userContext).html(generateACList(result)); } // append autocomplete list

    function HtmlAreaACFail() { }

    function startAC(htmlAreaEl) {
        autocompleteActive = true;
        accontainer = $(htmlAreaEl).parents('.data-content-placeholder').find('.htmlarea-ac-container');
        searchString = '';
        //var char = '';
        $(accontainer).html('<div class="htmlarea-ac-title">Please start typing user name ...</div>').show();
        startPos = currentPos;

        $(htmlAreaEl).bind('keydown.ac', function (e) {
            if (e.which != 37 && e.which != 38 && e.which != 39 && e.which != 40) { // do not make ajax calls on arrow key press
                setTimeout(function () {
                    var text = $(htmlAreaEl).val();
                    endPos = getCaretPosition($(htmlAreaEl)[0]);
                    cnt = getSearchString($(htmlAreaEl)[0], startPos, endPos);
                    //Users.autocomplete(cnt, 10, HtmlAreaACSuccess, HtmlAreaACFail, accontainer);
                    
                    var args = {
        					//type: 'post',
        					module: 'users',
        					action: 'autocomplete',
        					data: { users_name: cnt }
        				}
        			Services.invoke(args, HtmlAreaACSuccess, HtmlAreaACFail, accontainer);
                    
                    
                }, 10);
            }
        });
    }

    function stopAC() {
        autocompleteActive = false;
        $('.htmlarea textarea').unbind('keydown.ac');
        $('.htmlarea-ac-container').hide();
    }

    function getLinks(text) {
        var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
        //var exp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/gi;
        return text.match(exp);
    }

    /*--------------------*/

    function getCaretPosition(el) {
        var caretPos = 0;
        // IE Support
        if (document.selection) {

            //el.focus();

            var r = document.selection.createRange();
            var range = el.createTextRange();
            var rc = range.duplicate();
            range.moveToBookmark(r.getBookmark());
            rc.setEndPoint('EndToStart', range);
            //return rc.text.length;
            caretPos = rc.text.length;

            /*
            el.focus();
            var range = el.createTextRange();
            var startCharMove = offsetToRangeCharacterMove(el, 0);
            range.moveStart("character", startCharMove);
            caretPos = range.text.length;
            */
        }
        // Firefox support
        else if (el.selectionStart || el.selectionStart == '0')
            caretPos = el.selectionStart;
        return (caretPos);
    }

    function setCaretPosition(el, pos) {
        if (el.setSelectionRange) {
            el.focus();
            el.setSelectionRange(pos, pos);
        } else if (el.createTextRange) {
            var range = el.createTextRange();
            range.collapse(true);
            range.moveEnd('character', pos);
            range.moveStart('character', pos);
            range.select();
        }
    }

    function getSearchString(el, startOffset, endOffset) {
        if (document.selection) { //ie
            var range = el.createTextRange();
            var startCharMove = offsetToRangeCharacterMove(el, startOffset);
            range.collapse(true);
            if (startOffset == endOffset) {
                range.move("character", startCharMove);
            } else {
                range.moveEnd("character", offsetToRangeCharacterMove(el, endOffset));
                range.moveStart("character", startCharMove);
            }
            var cnt = range.text;
        } else if (el.selectionStart || el.selectionStart == '0') { //ff
            var text = $(el).val();
            cnt = text.substr(startOffset, endOffset - startOffset);
        }
        searchStr = cnt.replace(/@/gi, '');
        return searchStr;
    }

    function insertUserLink(el, editor) {
        alias = $(el).data('alias');
        if (!editor) {
            editor = $(el).parents('.data-content-placeholder').find('textarea');
        }


        if (aliases == null || aliases[alias] == null) {
            aliases[alias] = '@' + alias;
        }

        if (document.selection) {
            var range = $(editor)[0].createTextRange();
            var startCharMove = offsetToRangeCharacterMove($(editor)[0], startPos);
            range.collapse(true);
            if (startPos == endPos) {
                range.move("character", startCharMove);
            } else {
                range.moveEnd("character", offsetToRangeCharacterMove($(editor)[0], endPos));
                range.moveStart("character", startCharMove);
            }
            range.text = '@' + alias;
        } else {
            val = $(editor).val();
            alias = '@' + alias;
            replaced = val.substr(0, startPos) + alias + val.substr(endPos, val.length);
            $(editor).val(replaced);
            setTimeout(function () {
                setCaretPosition($(editor)[0], startPos + alias.length + 1)
            }, 10);

            //console.log(startPos);
            //console.log(endPos);
            //console.log(alias);
        }
        currentPos = getCaretPosition($(editor)[0]);
        highlighter($(editor));
        //alert('asd');

    }

    function offsetToRangeCharacterMove(el, offset) {
        return offset - (el.value.slice(0, offset).split("\r\n").length - 1);
    }

    function highlighter(el) {

        var cnt = $(el).val();
        cnt = cnt.replace(/[&<>]/g, replaceTag);
        cnt = cnt.replace(/\n/gi, '<br/>').replace(/\s/gi, '&nbsp;');
        cnt = cnt + '&nbsp;';


        if (aliases != null) {
            for (var a in aliases) {
                cnt = cnt.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + aliases[a].replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<span>$1</span>");
            };
        }

        if ($(el).parents('.htmlarea').length > 0) {
            $(el).parents('.htmlarea').find('.textarea-highlighter').html(cnt);
        } else {
            $(el).parents('.req.editor').find('.textarea-highlighter').html(cnt);
        }

    }





    function insertAtCursor(editor, str) {
        startPos = currentPos;

        if (document.selection) {
            var range = $(editor)[0].createTextRange();
            var startCharMove = offsetToRangeCharacterMove($(editor)[0], startPos);
            range.collapse(true);
            if (startPos == endPos) {
                range.move("character", startCharMove);
            } else {
                range.moveEnd("character", offsetToRangeCharacterMove($(editor)[0], endPos));
                range.moveStart("character", startCharMove);
            }
            range.text = str;
            //currentPos = getCaretPosition($(editor)[0]);
        } else {
            val = $(editor).val();
            replaced = val.substr(0, startPos) + str + val.substr(startPos + str.length, val.length);
            //alert(replaced);
            $(editor).val(replaced);
            setTimeout(function () {
                setCaretPosition($(editor)[0], startPos + str.length + 1);
                //currentPos = getCaretPosition($(editor)[0]);
            }, 10);
        }
    }


    function countCharacters(editor) {
    	counter = $(editor).parents('.status-editor-container').find('.characters-counter');
    	if (counter.length > 0) {
    		
    		counterValue = counter.data('value');
    		charactersCount = editor.val().length;
    		charactersLeft = counterValue - charactersCount;
    		//console.log(charactersCount);
    		counter.text(charactersLeft);
    		if (charactersCount > counterValue) {
    			//console.log('limit');
    			//return false;
    			editorString = editor.val();
    			editorString = editorString.substring(0,counterValue);
    			editor.val(editorString);
    			
    			charactersCount = editor.val().length;
    			charactersLeft = counterValue - charactersCount;
    			counter.text(charactersLeft);
    			
    		}
    		
    	}
    }

    // --- declare public methods --- //
    return {
        init: function (el) {
            htmlAreaEl = ($(el).length > 0) ? $(el) : $('.htmlarea');
            //console.log(htmlAreaEl);

            $(htmlAreaEl).focus(function () {
                if ($(this).val().trim() == $(this).data('placeholder')) { $(this).val(''); }
                $(this).parents('.htmlarea').addClass('focus');
                currentPos = getCaretPosition($(this)[0]);
            }).blur(function () {
                if ($(this).val().trim() == '') $(this).val($(this).data('placeholder'));
                $(this).parents('.htmlarea').removeClass('focus');
            }).keypress(function (e) {
                //$(this).parents('.htmlarea').find('.textarea-highlighter span').text($(this).val());
                // start autocomplete on "@" press
                highlighter($(this));
                countCharacters($(this));
                if (e.which == 64/* && !autocompleteActive*/) {
                    //e.preventDefault();
                    currentPos = getCaretPosition($(this)[0]);
                    stopAC();
                    startAC($(this));
                }
            }).keyup(function () {
                //currentPos = getCaretPosition($(this)[0]);
                highlighter($(this));
                countCharacters($(this));
                //var content = $(this).val();
                //content = content.replace(/\n/gi, '<br />');
                //$(this).parents('.htmlarea').find('.textarea-highlighter span').html(content);
            }).keydown(function (e) {
                //$(this).parents('.htmlarea').find('.textarea-highlighter span').text($(this).val());
                /*
                // 8  - backspace
                // 13 - enter
                // 27 - escape
                // 32 - space
                // 37 - arrow left
                // 38 - arrow up
                // 39 - arrow right
                // 40 - arrow down
                // 46 - delete
                // 64 - @
                */

                //currentPos = getCaretPosition($(this)[0]);
                highlighter($(this));
                countCharacters($(this));
                txteditor = $(this);

                if (e.which == 13) { // stop autocomplete 
                    if (e.which == 13 && autocompleteActive) {
                        e.preventDefault();
                        endPos = getCaretPosition($(txteditor)[0]);
                        selected = $('li.selection', '.htmlarea-ac-container');
                        insertUserLink($(selected), txteditor);
                    }
                    stopAC();
                }

                if ((e.which == 38 || e.which == 40) && autocompleteActive) { // up/down arrow keys
                    e.preventDefault();
                    acMoveSelection(e.which);

                }

                if (e.which == 27) { // esc key
                    e.preventDefault();
                    stopAC();
                }

                if (e.which == 32) {
                    el = $(this);
                    setTimeout(function () {
                        urls = getLinks($(el).val());
                        if (urls != null) {
                            for (var i = 0; i < urls.length; i++) {
                                Attachments.attachLink($(el), urls[i]);
                            }
                        }
                    }, 200);
                }



            }).bind('paste', function () { // on paste clean html
                el = $(this);
                setTimeout(function () {
                    urls = getLinks($(el).val());
                    if (urls != null) {
                        for (var i = 0; i < urls.length; i++) {
                            Attachments.attachLink($(el), urls[i]);
                        }
                    }
                }, 200);
            });

            $('body').click(function (event) {
                caller = event.target;
                if ($(caller).parents('.htmlarea-ac').length == 0 && !$(caller).hasClass('htmlarea-ac')) {
                    stopAC();
                }
            });











            //comment editor when user is not logged
            commentAreaEl = $('.req textarea');
            $(commentAreaEl).focus(function () {
                if ($(this).val().trim() == $(this).data('placeholder')) { $(this).val(''); }
                $(this).parents('.editor').addClass('focus');
                currentPos = getCaretPosition($(this)[0]);
            }).blur(function () {
                if ($(this).val().trim() == '') $(this).val($(this).data('placeholder'));
                $(this).parents('.editor').removeClass('focus');
            }).keypress(function (e) {
                highlighter($(this));
            }).keyup(function () {
                highlighter($(this));
            }).keydown(function (e) {
                highlighter($(this));
            });













            $('.ac-btn').live('click', function (e) {
                e.preventDefault();
                targetEditor = $(this).parents('.data-content-placeholder').find('textarea');
                $(targetEditor).focus();
                setCaretPosition($(targetEditor)[0], currentPos);
                //insertAtCursor($(targetEditor), '@');
                setTimeout(function () {
                    insertAtCursor($(targetEditor), '@');
                    startAC($(targetEditor));
                }, 20);

            });



        },

        reset: function (el, type) {
            editorContainer = $(el).parents('.data-content-placeholder');
            $(el).parents('.htmlarea').find('.textarea-highlighter').html('');
            $(el).val($(el).data('placeholder'));
            $(editorContainer).find('.attachments .images').html('');
            $(editorContainer).find('.attachments .links').html('');
            $(editorContainer).find('.attachments .files').html('');
            $(editorContainer).find('.uploads').hide();
            Attachments.reset(type);
        },

        highlightAlias: function (alias, el) {
            $(el).text('@' + alias);
            $(el).focus();

            aliases[alias] = '@' + alias;
            highlighter(el);
        }

    }
} ();

$(document).ready(function () {
    //HtmlAutocompleteService = new STXServices.BTSearchService();
    //Htmlarea.init();
    Htmlarea.init();
    //Htmlarea.init($('#comments-textarea'));
    
});