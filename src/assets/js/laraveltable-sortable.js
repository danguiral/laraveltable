var getUrl = window.location;
var currentUrlWithoutParams = document.location.protocol + "//" + document.location.hostname + document.location.pathname;
$(document).ready(function () {
    sortChange();
    pageChange();
    search();
});

/**
 * wait to set search param
 */
function search() {
    $('#search-button').on('click',function(){
        var search = $('#search-input').val();
        var params = getparams();
        params.search = search;
        params.page = 1;
        window.location.href = currentUrlWithoutParams + generateGetParams(params);
    });
}

/**
 * wait to set current page param url
 * and
 * wait for reset search from url
 */
function pageChange() {
    $('.a-pagination').on('click', function () {
        var page = $(this).attr('data-page');
        var params = getparams();
        params.page = page;
        window.location.href = currentUrlWithoutParams + generateGetParams(params);
    });
    $('#reset-search-button').on('click',function(){
        var params = getparams();
        delete params.search;
        window.location.href = currentUrlWithoutParams + generateGetParams(params);
    });
}

/**
 * listen click to sort table
 */
function sortChange() {

    $('.sortable').on('click', function () {
        var columnClicked = $(this).attr('data-column');
        var params = getparams();
        params.page = 1;
        //on met a jour la column et la direction
        console.log(params.orderby);
        if (params.orderby != columnClicked) {
            params.orderby = columnClicked;
            params.direction = 'asc';
        } else {
            if (params.direction == null || params.direction == 'desc') {
                params.direction = 'asc';
            } else {
                params.direction = 'desc';
            }
        }
        window.location.href = currentUrlWithoutParams + generateGetParams(params);

    });
}

/**
 * get url param
 */

function getparams() {
    var query = location.search.substr(1);
    var result = {};
    query.split("&").forEach(function(part) {
        var item = part.split("=");
        result[item[0]] = decodeURIComponent(item[1]);
    });
    return result;
}

/**
 * generate url param
 */
function generateGetParams(array) {
    var paramsString = '?';
    $.each(array, function (key, value) {
        if(value != undefined && value != '' && value !=null&& value !=false) {
            paramsString += key + '=' + value + '&';
        }
    });
    return paramsString.slice(0,-1)
}
