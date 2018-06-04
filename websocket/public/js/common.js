/**
 * File:        function.js
 *
 * JS 函数集
 *
 * @package     基础类库
 * @version     1.0 
 */

$(document).ready(function(){
    //数据提交表单
    $(".form").submit(function(){
        $(this).find("input[type='submit']").attr('disabled', true);//disabled提交按钮
        $(this).find("input[type='submit']").val('处理中...');//提交按钮
        var options = { 
            //target:'#data_list',
            success: formCallBack,		//回调
            dataType:'json',   
            data:{format:'json'},
            beforeSubmit:validation			//提交前验证表单，暂不使用
        }
        $("#msg_form").remove();//移除错误提醒
        if(typeof(ueditors) !== 'undefined'){
            for(var ed in ueditors){
                ueditors[ed].sync(); 
            }
        }
        $(this).ajaxSubmit(options);
        return false;
    });
    //搜索提交处理
    $("#search_form").submit(function(){
        $("#search_form input[type='submit']").val($("#search_form input[type='submit']").val() + '...');
        $("#search_form input[type='submit']").data('opt_disabled', true);
        $("#data_list").html('<div style="text-align:center;padding-top:20px;"><div><img src="http://news.sina.com.cn/deco/2010/0309/20070311141345452.gif" /></div><div>数据载入中...</div></div>');

        if(/\?/g.test($(this).attr('action'))) {
            var action = $(this).attr('action') + '&' + $(this).formSerialize();
        } else {
            var action = $(this).attr('action') + '?' + $(this).formSerialize();
        }

        $('#data_list').load(action + ' #data_list', function(){
            $("#search_form input[type='submit']").val($("#search_form input[type='submit']").val().replace(/\.\.\.$/, ''));
            $("#search_form input[type='submit']").data('opt_disabled', false);
        });
        return false;
    });
    //全选操作
    $(".select_all").live("click", function() {
        if($(this).attr("checked") == true) { // 全选
            $("input[name=ids[]]").each(function() {
                $(this).attr("checked", true);
            });
        } else { // 取消全选
            $("input[name=ids[]]").each(function() {
                $(this).attr("checked", false);
            });
        }
    });
    //操作绑定
    $(".opt").live({
        click : bindOptClick,
        mouseenter : bindOptIn,
        mouseleave : bindOptOut
    });
});

//验证表单
function validation(data,form) {
    return true;
}

function bindOptIn() {
    var obj = $(this);
    var opt_type = obj.data('opt_type');
    if(opt_type == 'order'){
        obj.find(".sortremove").show();
        
    }
}

function bindOptOut() {
    var obj = $(this);
    var opt_type = obj.data('opt_type');
    if(opt_type == 'order'){
        obj.find(".sortremove").hide();
    }
}

function bindOptClick() {
    var obj = $(this);
    //开始执行扩展函数
    if (typeof(beginOpt) == 'function') {
        if (!beginOpt(obj)){
            return false;
        }
    }
    obj.data('opt_disabled',true);//只对button生效
    var opt_type = obj.data('opt_type');
    var url = obj.data('opt_url');
    $("#loading_div").show();
    //dialog类型
    if (opt_type == "dialog") {
        //用于表单提交后处理
        window.obj = obj;
        var end = false;
        //重置表单
        $("#" + obj.data("opt_id") + " form").resetForm();
        //重置multiselect
        if(typeof(multiselectors) !== 'undefined'){
            for(var ms in multiselectors){
                multiselectors[ms].lozengeGroup.removeAllItems();
            }
        }
        //重置ueditor
        if(typeof(ueditors) !== 'undefined'){
            for(var u in ueditors){
                ueditors[u].setContent("");
            }
        }
        //修改dialog的标题栏及表单action
        var title = obj.attr('title');
        var action = obj.data('opt_action');
        if (typeof(title) != 'undefined' && title != '') {
            $("#" + obj.data('opt_id')).attr('title', title);
        }
        if (typeof(action) != 'undefined' && action != '') {
            $("#" + obj.data('opt_id') + " form").attr('action', action);
        }
        //初始化dialog
        var width = $("#" + obj.data("opt_id")).data('dialog_width');
        if (typeof(width) == 'undefined' || width == '') {
            $("#" + obj.data("opt_id")).data('dialog_width',  $("#" + obj.data('opt_id')).width() + 50);
            width = $("#" + obj.data("opt_id")).data('dialog_width');
        }
        $("#" + obj.data("opt_id")).dialog({
            title: title,
            autoOpen: false,
            minHeight: 100,
            width: width,
            modal: true,
            close: function(){
                $("#msg_form").remove();//移除错误提醒
                $("input").css("backgroundColor","");//恢复表单颜色
                obj.data('opt_disabled',false);//只对button生效
            }
        });
        //获取dialog中表单数据
        if (typeof(url) != 'undefined') {
            $.ajax({
                url: url, 
                dataType: 'json', 
                async: false, 
                data: {format: 'json', _: (new Date).getTime()}, 
                success: function(json){
                    if (json.result.status.code == '0') {
                        for(var i in json.result.data) {
                            try{
                                var tmp = String(json.result.data[i]);
                                if (/^SELECTED:\[\'.*\'\]$/.test(tmp)) {
                                    tmp = tmp.replace(/^SELECTED:\[\'(.*)\'\]/g, "$1");
                                    tmp = eval("['" + tmp + "']");//转为数组，兼顾checkbox，radio
			    	    if((typeof(multiselectors) !== 'undefined') && (typeof(multiselectors[i]) !== 'undefined')){
					for(var v in tmp){
					    var option = $('select[name="'+i+'[]"] option[value="'+tmp[v]+'"]');
					    multiselectors[i]._addItem({value:option.attr("value"), label:option.text()});
					}
				    }else{
				        $("#" + obj.data("opt_id") + " [name='" + i + "[]']").val(tmp);
                                    }
                                } else if((typeof(ueditors) !== 'undefined') && (typeof(ueditors[i]) !== 'undefined')) {
                                    ueditors[i].setContent(tmp);
                                } else {
                                    tmp = tmp.replace(/\'/g, "\\'");
                                    tmp = tmp.replace(/\n/g, "\\n");
                                    tmp = tmp.replace(/\r/g, "\\r");
                                    //tmp = tmp.replace(/;/g, "&#59;");
                                    tmp = eval("['" + tmp + "']");//转为数组，兼顾checkbox，radio
                                    $("#" + obj.data("opt_id") + " [name='" + i + "']").val(tmp);
                                }
                            }catch(e){
                            }
                        }
                        if (typeof(endGetData) == 'function') {
                            if (!endGetData(obj, json)){
                                return false;
                            }
                        }
                    } else {
                        end = true;
                        showMsg(json.result.status.msg, obj);
                    }
                }
            });
        }
        $("#loading_div").hide();
        if (end == true) {
            return false;
        }
        //打开dialog
        openDialog(obj.data("opt_id"));
    } else if(opt_type == "order") {
        var currentClass = $(this).attr("class");
        var orderAttr = obj.data("opt_name");
        if(currentClass.indexOf("sortremove") !== -1){
            $.orderBy.remove(obj);
        }else if(currentClass.indexOf("ascending") !== -1){
            $.orderBy.desc(obj);
        }else if(currentClass.indexOf("sortable") !== -1 || currentClass.indexOf("descending") !== -1){
            $.orderBy.asc(obj);
        }
        $("#loading_div").hide();
    } else {
        if (typeof(url) == 'undefined') {
            $("#loading_div").hide();
            showMsg('无操作', obj);
            return false;
        }
        var t = (new Date()).getTime();
        $.getJSON(url, {format: 'json', _: (new Date).getTime()}, function(json){
            $("#loading_div").hide();
            if (obj.data('opt_disabled') != 'true') {
                obj.data('opt_disabled',false);//只对button生效
            }
            //结束执行扩展函数
            if (typeof(endOpt) == 'function') {
                if (!endOpt(obj, json)){
                    return false;
                }
            }
            if(typeof(json.result.status.msg) != 'undefined') {
                showMsg(json.result.status.msg, obj);
            }
        });	
    }
}

//重新加载列表数据
function reloadList(url, data) {
    if (typeof(url) == 'undefined' || url == '') {
        url = location.href;
    }
    $("#sortable_tbody").html('<div style="text-align:center;padding-top:20px;"><div><img src="http://news.sina.com.cn/deco/2010/0309/20070311141345452.gif" /></div><div>数据载入中...</div></div>');
    $('#sortable_tbody').load(url + ' #sortable_tbody tr', data, function(){
        if (typeof(endReload) == "function") {
            endReload();
        }
        return true;
    });
}
//显示提示信息
function showMsg(data, obj){
    var left = obj.offset().left; 
    var top = obj.offset().top;
    left = left - 20;
    top = top - 45;
    typeof(window.z_index) == 'undefined' ? window.z_index = 0 : 1;
    var index = 2000 + window.z_index;
    var id = "msg_show_" + (new Date).getTime();
    if ($("#msg_show").length == 0){
        $("body").prepend('<table id="' + id + '" style=" font-size:12px;position:absolute;left:' + left + 'px;top:' + top + 'px;display:none;z-index:' + index + ';" border="0" cellpadding="0" cellspacing="0"><tr><td style="width:19px;height:15px;background:url(http://news.sina.com.cn/deco/2010/0318/tips.png);"></td><td style="height:15px;background:url(http://news.sina.com.cn/deco/2010/0318/tips-x.png) repeat-x;"></td><td style="width:19px;height:15px;background:url(http://news.sina.com.cn/deco/2010/0318/tips.png) -19px 0;"></td></tr><tr><td style="width:19px;background:url(http://news.sina.com.cn/deco/2010/0318/tips-y.png) repeat-y;"></td><td style="height:30px;background-color:#FFF;" id="' + id + '_contnet"></td><td style="width:19px;background:url(http://news.sina.com.cn/deco/2010/0318/tips-y.png) -9px 0 repeat-y;"></td></tr><tr><td style="width:19px;height:29px;background:url(http://news.sina.com.cn/deco/2010/0318/tips.png) 0 -15px;"></td><td style="height:29px;background:url(http://news.sina.com.cn/deco/2010/0318/tips-x.png) 0 -6px repeat-x;text-align:left;"><img src="http://news.sina.com.cn/deco/2010/0318/tips-d.png" style="display:inline;" /></td><td style="width:19px;height:29px;background:url(http://news.sina.com.cn/deco/2010/0318/tips.png) -19px -15px;"></td></tr></table>');
    }
    window.z_index++;
    $("#" + id + "_contnet").html(data);
    $("#" + id).css('opacity', 0);
    $("#" + id).show();
    $("#" + id).click(function(){
        $(this).remove();
        return;
    });
    $("#" + id).animate({top: '-=25px',opacity: 1}, 250, 'swing', function() {});
    if ($.browser.msie && $.browser.version == 6) {
        setTimeout("$('#"+id+"').remove();",5000)
    }else {
        $("#" + id).fadeOut(10000,function(){
            //$(this).remove();
            return;
        }); 
    }
}

//列表checkbox拼接,id以“,”隔开
function getIds(s, attr) {
    if (typeof(s) == 'undefined' || s == '') {
        s = ',';
    }
    var ids = '';
    $("#data_list input[name='ids[]']").each(function(){
        if ($(this).attr('checked') == true) {
            if(attr){
                ids += $(this).attr(attr) + s;
            }else{
                ids += $(this).val() + s;
            }
        }
    });
    ids = ids.replace(/,$/, "");
    return ids;
}
//表单提交回调
function formCallBack(json, status, xhr, form){
    form.find("input[type='submit']").attr('disabled', false);
    form.find("input[type='submit']").val('提交');
    if (window.obj.data('opt_disabled') != 'true') {
        window.obj.data('opt_disabled',false);//只对button生效
    }
    //结束执行扩展函数
    if (typeof(endOpt) == 'function') {
        if (!endOpt(window.obj, json)){
            return false;
        }
    }
    if (json.result.status.code == '0') {
        closeDialog(window.obj.data("opt_id"));
        showMsg(json.result.status.msg, window.obj);
    } else {
        //showMsg(json.result.status.msg, form.find("input[name='" + json.result.data.field + "']"));
        //后台返回错误信息，在表单开头处打印
        form.prepend('<div class="form_div" style="text-align:center;color:#f00;" id="msg_form">' + json.result.status.msg + '</div>');
        //input变量用于记录出错的表单输入框
        var input = form.find("input[name='" + json.result.data.field + "'],textarea[name='" + json.result.data.field + "'],select[name='" + json.result.data.field + "'],radio[name='" + json.result.data.field + "'],checkbox[name='" + json.result.data.field + "']");
        input.keyup(function() {
            //用户重新输入则将错误提示的css去掉
            $("#msg_form").remove();
            $("input,textarea,select,radio,checkbox").css("backgroundColor","");
        });
        input.css("backgroundColor","#FB5454");
        for (var i = 0; i < 3; i++) {
            input.animate({opacity:'0.3'},500);
            input.animate({opacity:'1'},500);
        }
        input.focus();
    }
}

//dialog控制
function closeDialog(dialog_id){
    $("#" + dialog_id).dialog("close");
}
function openDialog(dialog_id){
    $("#" + dialog_id).dialog('open');
}
