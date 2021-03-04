
jQuery(document).ready(function($) {

    // ADD TO CART
    $('#wprfq_button').click(function(e) {
        e.preventDefault();
        var rfq_value = jQuery(this).attr("value")
        var nonce = jQuery(this).attr("data-nonce")
        var session = jQuery(this).attr("session")
        var data = {
            action: 'wprfq',
            method: 'add',
            user: session,
            security : rfqAjax.security,
            product: rfq_value
        };
        $.post(rfqAjax.ajaxurl, data, function(response) {
            obj = JSON.parse(response);
            $('.rfqcart').html(obj.count);
            $('.wprfqform').html(obj.form);
        });
    });

    // DELETE FROM CART
    $('body').on('click', '#cartdelete', function(e) {
        e.preventDefault();
        var rfq_value = jQuery(this).attr("value")
        var nonce = jQuery(this).attr("data-nonce")
        var session = jQuery(this).attr("session")

            var data = {
                action: 'wprfq',
                user: session,
                method: 'delete',
                security : rfqAjax.security,
                product: rfq_value
            };

        $.post(rfqAjax.ajaxurl, data, function(response) {
            RowCount = $(response).find('tr').length;
            $('.rfqcart').html(RowCount);
            $('.wprfqform').replaceWith(response);
            RFQItems();
        });

        // location.reload();
    });

    // ==========================================
    // REQUEST FORM CONTENTS
    // ==========================================

    $( document ).ready(function() {
        RFQItems();
    });

    // $('body').on('click', '#cartdelete', function(e) {
    //     RFQItems();
    //     // location.reload();
    // });

    $('body').on('click', '#rfqsubmit', function(e) {
        RFQItems();
    });


    function RFQItems() {
        var table=document.getElementById("rfqtable");
        var r=0; //start counting rows in table
        content = 'Items Requested: &#10;&#10;';
        while(row=table.rows[r++])
            {
                var c=1; //start counting columns in row
                while(cell=row.cells[c++])
                    {
                        content += cell.innerHTML;
                        if (  c % 2 == 1 ) {
                            content +=  ' ) &#10;';
                        }
                        else {
                            content += ' ( ';
                        }
                    }
            }
            document.getElementById("rfqitems").innerHTML = content;
    }
});

