


//图片旋转  左 正；右负
function imgRotate(domId, rotate) {
    var imgDom = $('#' + domId);
    var url = imgDom.attr("src");

    console.log(url);

    //width, height,move_width, move_height, rotate
    imgProperty = {"rotate": rotate, "width": 0, "height": 0, "move_width": 0, "move_height": 0};
    url = transferImgUrl(url, imgProperty);
    console.log(url);
    imgDom.attr("src", url);
}

function reset(domId) {
    var imgDom = $('#' + domId);
    var url = imgDom.attr("src") ;
    var index = url.lastIndexOf("?imageMogr2") ;
    if(index >= 0){
        url = url.substring(0,index) ;
    }
    imgDom.attr("src",url) ;
}

function save(accountId, imgUrl) {

}

function transferImgUrl(url, imgProperty) {
    console.log(imgProperty);
    //imgProperty
    var index = url.lastIndexOf("?imageMogr2/");
    if (index == -1) {
        return url + getImgUrlSuffix(imgProperty);
    } else {
        var urlPrefix = url.substring(0, index);
        var urlSuffix = url.substring(index, url.length);
        var obj = getImgParam(urlSuffix);
        imgProperty.rotate +=  parseInt(obj.rotate) ;
        (imgProperty.rotate  >= 360) ? imgProperty.rotate  -= 360 : '';
        (imgProperty.rotate  <= 360) ? imgProperty.rotate  += 360 : '';
        return urlPrefix + getImgUrlSuffix(imgProperty);
    }
}

function getImgUrlSuffix(imgProperty) {
    //return "?imageMogr2/rotate/" + rotate + "/crop/!" + width + "x" + height + "a" + move_width + "a" + move_height;
    return "?imageMogr2/rotate/" + imgProperty.rotate; //暂时只保留角度;参数名称 区分大小写
}

function getImgParam(urlBack) {

    var obj = new Object();
    obj.width = 0;
    obj.height = 0;
    obj.move_width = 0;
    obj.move_height = 0;
    obj.rotate = 0;

    if (urlBack.lastIndexOf("/crop/") != -1) {
        urlBack = urlBack.replace(/\?imageMogr2\/rotate\//, "");
        urlBack = urlBack.replace(/\/crop\/!/g, ";");
        urlBack = urlBack.replace(/x/g, ";");
        urlBack = urlBack.replace(/a/g, ";");
        var arr = urlBack.split(";");
        obj.rotate = arr[0];
        obj.width = arr[1];
        obj.height = arr[2];
        obj.move_width = arr[3];
        obj.move_height = arr[4];
    } else {
        // 老数据
        var index = urlBack.lastIndexOf("/");
        var urlTemp = urlBack.substring(index + 1, urlBack.length);
        obj.rotate = parseInt(urlTemp);
    }
    return obj;
}