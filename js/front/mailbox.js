var show_message = false;
var current_folder = 1;

function discard_reply()
{
    document.getElementById('reply_content').style.display = 'none';
    document.getElementById('reply_button').style.display = 'block';
    document.getElementById('edit_subject').style.display = 'none';
    document.getElementById('edit_subject_link').style.display = 'block';
}

function reply_click()
{
    document.getElementById('reply_content').style.display = 'block';
    document.getElementById('reply_button').style.display = 'none';
    var m = document.getElementById('reply_message');
    m.value = document.getElementById('reply_message_copy').value;
    m.focus();
    setCursorPosition(m, 0, 0);

    document.getElementById('subject').value = document.getElementById('subject_copy').value;
}

function folder_click(id)
{
    if (show_message && id == current_folder) {
        document.getElementById('message').style.display = 'none';
        document.getElementById('messages').style.display = 'block';
        show_message = false;
    }
    else {
        loc = 'profile/messages/?folder=' + id;
        window.location.href = loc;
    }
}

function select_all()
{
    var f = document.forms['messages_form'];

    for (var i = 0; i < f.elements.length; i++) {
        el = f.elements[i];
        if ('checkbox' == el.type) {
            el.checked = true;
        }
    }
}

function checkAll(elForm, check)
{
    var alreadyChecked = false;
    for (i = 0; i < elForm.elements.length; i++) {
        if ((alreadyChecked == false) && ('checkbox' == elForm.elements[i].type)) {
            alreadyChecked = elForm.elements[i].checked;
        }
        elForm.elements[i].checked = check;
    }
}

function select_none()
{
    var f = document.forms['messages_form'];

    for (var i = 0; i < f.elements.length; i++) {
        el = f.elements[i];
        if ('checkbox' == el.type) {
            el.checked = false;
        }
    }
}

function select_read()
{
    var f = document.forms['messages_form'];

    for (var i = 0; i < f.elements.length; i++) {
        el = f.elements[i];
        if ('checkbox' == el.type && 'read' == el.className) {
            el.checked = true;
        }
    }
}

function select_unread()
{
    var f = document.forms['messages_form'];

    for (var i = 0; i < f.elements.length; i++) {
        el = f.elements[i];
        if ('checkbox' == el.type && 'unread' == el.className) {
            el.checked = true;
        }
    }
}

function mark_change(ctrl)
{
    switch (ctrl.value) {
        case 'mark_as_read':
            mark_as_read(ctrl);
            break;

        case 'mark_as_unread':
            mark_as_unread(ctrl);
    }
}

function mark_as_read(ctrl)
{
    var f = document.forms['messages_form'];
    var cnt = getNumSelected(f);

    if (cnt > 0) {
        document.getElementById('action').value = 'mark_as_read';
        f.submit();
    }
    else {
        alert('Please select messages.');
        ctrl.options[0].selected = true;
    }
}

function mark_as_unread(ctrl)
{
    var f = document.forms['messages_form'];
    var cnt = getNumSelected(f);

    if (cnt > 0) {
        document.getElementById('action').value = 'mark_as_unread';
        f.submit();
    }
    else {
        alert('Please select messages.');
        ctrl.options[0].selected = true;
    }
}

function move_to_change(ctrl)
{
    var v = parseInt(ctrl.value);
    if (v > 0) {
        move_to(v, ctrl);
    }
    else {
        if (-1 == v) {
            move_to_new(ctrl);
        }
    }
}

function move_to(folder_id, ctrl)
{
    var f = document.forms['messages_form'];
    var cnt = getNumSelected(f);

    if (cnt > 0) {
        document.getElementById('action').value = 'move_to';
        document.getElementById('action_param').value = folder_id;
        f.submit();
    }
    else {
        alert('Please select messages.');
        ctrl.options[0].selected = true;
    }
}

function move_to_new(ctrl)
{
    var f = document.forms['messages_form'];
    var num = getNumSelected(f);

    if (num > 0) {
        var name = prompt();
        if (name != undefined && name.length > 0) {
            document.getElementById('action').value = 'move_to_new';
            document.getElementById('action_param').value = name;
            f.submit();
        }
        else {
            ctrl.options[0].selected = true;
        }
    }
    else {
        alert('Please select messages.');
        ctrl.options[0].selected = true;
    }
}

function delete_messages()
{
    var cnt = 0;
    var f = document.forms['messages_form'];

    for (var i = 0; i < f.elements.length; i++) {
        el = f.elements[i];
        if ('checkbox' == el.type && el.checked) {
            cnt++;
        }
    }

    if (cnt > 0) {
        document.getElementById('action').value = 'delete';
        f.submit();
    }
    else {
        alert('Please select messages.');
        document.getElementById('move_to').options[0].selected = true;
    }
}

function delete_messages_forever()
{
    var cnt = 0;
    var f = document.forms['messages_form'];

    for (var i = 0; i < f.elements.length; i++) {
        el = f.elements[i];
        if ('checkbox' == el.type && el.checked) {
            cnt++;
        }
    }

    if (cnt > 0) {
        document.getElementById('action').value = 'delete_forever';
        f.submit();
    }
    else {
        alert('Please select messages.');
        document.getElementById('move_to').options[0].selected = true;
    }
}
function setCursorPosition(oInput, oStart, oEnd)
{
    oInput.focus();
    if (oInput.setSelectionRange) {
        oInput.setSelectionRange(oStart, oEnd);
    }
    else {
        if (oInput.createTextRange) {
            var range = oInput.createTextRange();
            range.collapse(true);
            range.moveEnd('character', oEnd);
            range.moveStart('character', oStart);
            range.select();
        }
    }
}

function getNumSelected(aForm)
{
    var cnt = 0;
    for (var i = 0; i < aForm.elements.length; i++) {
        el = aForm.elements[i];
        if ('checkbox' == el.type && el.checked) {
            cnt++;
        }
    }
    return cnt;
}

function editSubjectClick()
{
    document.getElementById('edit_subject_link').style.display = 'none';
    document.getElementById('edit_subject').style.display = 'block';
    var input = document.getElementById('subject');
    input.focus();
    setCursorPosition(input, 0, input.value.length);
}

function mkdir()
{
    var name = $('#js-folder-name').val();

    if (null != name && name.length > 0) {
        $.get('profile/messages/read.json', {action: 'mkdir', name: name}, function (data)
        {
            if ('boolean' == typeof data.error && !data.error) {
                // add new folder to list
                var folder = '\
<tr id="folder_' + data.folder + '">\
<td>\
    <a href="profile/messages/?folder=' + data.folder + '">' + name + '</a>\
</td>\
<td class="text-right">\
<span class="folder-actions">\
<button class="btn btn-xs btn-info" onclick="rename_folder(\'' + data.folder + '\', \'' + name + '\'); return false;">' + _f(
                        'rename') + '</button> \
<button class="btn btn-xs btn-danger" onclick="rmdir(\'' + data.folder + '\', \'0\'); return false;">' + _f('delete') + '</button>\
</span>\
                    </td>\
                    <td class="text-right">0</td>\
                    <td class="text-right">0</td>\
                    </tr>';
                $("tr[id^='folder_']:last").after(folder);

                intelli.notifFloatBox({msg: data.message, type: 'success', autohide: true});
            }
            else {
                intelli.notifFloatBox({msg: data.message, type: 'error', autohide: true});
            }
        });
    }
}

function rename_folder(id, old_name)
{
    var new_name = prompt(_t('folder_new_name'), old_name);

    if (null != new_name && new_name.length > 0) {
        $.get(intelli.config.url + "profile/messages/read.json", {action: "rename", folder_id: id, name: new_name},
            function (data)
            {
                if ('boolean' == typeof data.error && !data.error) {
                    $("tr#folder_" + id + " a").text(new_name);

                    intelli.notifFloatBox({msg: data.message, type: 'success', autohide: true});
                }
            });
    }
}

function rmdir(id, num)
{
    var msg = '';

    if (num > 0) {
        msg = _f('folder_contains_messages');
        msg = msg.replace(':count', num);
    }
    msg += ' ' + _f('want_del_folder');

    if (confirm(msg)) {
        $.get('profile/messages/read.json', {action: 'rmdir', folder_id: id}, function (data)
        {
            $('tr#folder_' + id).remove();

            if ('boolean' == typeof data.error && !data.error) {
                intelli.notifFloatBox({msg: data.message, type: 'success', autohide: true});
            }
        });
    }
}

$(function ()
{
    if ($('#to').length) {
        $('#to').typeahead(
            {
                source: function (query, process)
                {
                    return $.ajax(
                        {
                            url: intelli.config.url + 'profile/messages.json',
                            type: 'get',
                            dataType: 'json',
                            data: {q: query},
                            success: function (response)
                            {
                                $('.typeahead.dropdown-menu').css({'opacity':'1', 'visibility':'visible'});
                                return process(response);
                            }
                        });
                }
            });
    }

    $('.btn-remove-ignore').click(function (e)
    {
        if (!window.confirm(intelli.lang.you_sure_want_to_exclude_from_ignore)) {
            e.preventDefault();
        }
    });
});