/**
 * <p>ThrowsSpamAway</p> JavaScript
 * WordPress's Plugin
 * @version 3.2.3
 * @author Takeshi Satoh@GTI Inc. 2020
 * @since version2.6
 *
 * -- updated --
 * 2020/09/03 テーマによってJavaScriptエラーが発生するため修正
 * 2014/05/10 debug for IE8
 * 2015/07/25 インラインスタイルでdisplay:none指定したため hide削除
 * 2020/07/16 jQuery 排除し JavaScript へ
 * 2020/10/13 JavaScript修正
 */

document.addEventListener('DOMContentLoaded', function() {

    if (
        document.querySelector('.tsa_param_field_tsa_2 input#tsa_param_field_tsa_3') &&
        document.querySelector('.tsa_param_field_tsa_ input')
    ) {
        document.querySelector('.tsa_param_field_tsa_2 input#tsa_param_field_tsa_3').value =
            document.querySelector('.tsa_param_field_tsa_ input').value

        var date = new Date();
        var iso = null;
        if (typeof date.toISOString != 'undefined') {
            iso = date.toISOString().match(/(\d{4}\-\d{2}\-\d{2})T(\d{2}:\d{2}:\d{2})/);
            current_date = iso[1] + ' ' + iso[2];

            if (document.querySelector('#comments form') && !document.querySelector('#comments form input#tsa_param_field_tsa_3')) {
                const tsa_field = document.createElement("input");
                tsa_field.setAttribute("type", "hidden");
                tsa_field.setAttribute("name", "tsa_param_field_tsa_3");
                tsa_field.setAttribute("id", "tsa_param_field_tsa_3");
                tsa_field.setAttribute("value", current_date);
                document.querySelector('#comments form').appendChild(tsa_field);
            }

            if (document.querySelector('#respond form') && !document.querySelector('#respond form input#tsa_param_field_tsa_3')) {
                const tsa_field2 = document.createElement("input");
                tsa_field2.setAttribute("type", "hidden");
                tsa_field2.setAttribute("name", "tsa_param_field_tsa_3");
                tsa_field2.setAttribute("id", "tsa_param_field_tsa_3");
                tsa_field2.setAttribute("value", current_date);
                document.querySelector('#respond form').appendChild(tsa_field2);
            }

            if (document.querySelector('form#commentform') && !document.querySelector('form#commentform input#tsa_param_field_tsa_3')) {
                const tsa_field3 = document.createElement("input");
                tsa_field3.setAttribute("type", "hidden");
                tsa_field3.setAttribute("name", "tsa_param_field_tsa_3");
                tsa_field3.setAttribute("id", "tsa_param_field_tsa_3");
                tsa_field3.setAttribute("value", current_date);
                document.querySelector('form#commentform').appendChild(tsa_field3);
            }

        }
    }

});