(function( $ ) {
    var waxPayment = {
        init: function () {

            $.initialize("#wax-qr", function() {
                //Get form
                if ( $( '#wax-form' ).length ) {
                    this.form = $('#wax-form');
                    this.email = this.form.data('email');
                    this.amount = this.form.data('amount');
                    this.currency = this.form.data('currency');
                    this.waxAddress = this.form.data('wax-address');
                    this.waxAmount = this.form.data('wax-amount');
                    this.waxRef = this.form.data('wax-ref');
                    this.infoWrapper = '#wax-info';
                    this.amountWrapper = '#wax-amount-wrapper';
                    this.process = '#wax-process';
                }

                // TODO: WAX payment button
                $("#wax-pay-button").off('click');
                $("#wax-pay-button").on('click', function(){
                    waxPayment.payWithWax(this.waxAddress, this.waxAmount, this.waxRef);
                }.bind(this));

                /*Add copy functinality to amount, ref and wax address*/
                // if(Clipboard.isSupported()){
                //     new Clipboard('#wax-amount-wrapper');
                //     new Clipboard('#wax-address-wrapper');
                //     new Clipboard('#wax-ref-wrapper');
                // }

                //Set payment button to disabled if whole chech is updated.
                if($( 'div.payment_box.payment_method_wax' ).is(':visible')){
                    $( '#place_order' ).attr( 'disabled', true);
                }else{
                    $( '#place_order' ).attr( 'disabled', false)
                }

                /*Set pay button to disabled and start waiting for payments*/
                $('.wc_payment_methods  > li').on( 'click', 'input[name="payment_method"]',function () {
                    if ( $( this ).is( '#payment_method_wax' ) ) {
                        $( '#place_order' ).attr( 'disabled', false);
                    }else{
                        $( '#place_order' ).attr( 'disabled', false)
                    }
                });

                var options = {
                    classname: 'nanobar-wax',
                    id: 'wax-nanobar',
                    target: document.getElementById('wax-process')
                };

                waxPayment.nanobar = new Nanobar( options );
            });


        },
        payWithWax: function (receiver, amount, memo) {
            var wax = new waxjs.WaxJS('https://wax.greymass.com', null, null, false);
            wax.login().then(function (ret) {
                console.log(ret);
                console.log(wax.userAccount);
                console.log(receiver);
                console.log(amount.toFixed(8) + ' WAX');
                console.log(memo);

                wax.api.transact({
                    actions: [{
                      account: 'eosio.token',
                      name: 'transfer',
                      authorization: [{
                        actor: wax.userAccount,
                        permission: 'active',
                      }],
                      data: {
                        from: wax.userAccount,
                        to: receiver,
                        quantity: amount.toFixed(8) + ' WAX',
                        memo: memo,
                      },
                    }],
                  }, {
                    blocksBehind: 3,
                    expireSeconds: 1200
                  }).then(function (result) {
                    // contains the transaction id
                    console.log("transaction result ->", result);
                    // TODO: should lock the button, show feedback to tell the user the payment has been done -> wait for confirmation
                    waxPayment.showPendingToConfirm();
                  });
            }).catch(function (e) {
                console.error(e);
              // User rejected the transaction
            });
        },
        showPendingToConfirm: function () {
            $('#wax-pay-button').css("display","none");
            $('#wax-tx-accepted').css("display","block");
        },
        updateWaxAmount: function () {
            this.ajaxGetWaxAmount().done(function (res) {
                console.log(res);

                if(res.success === true && res.data.amount > 0){
                    $(this.amountWrapper).text(res.data.amount)
                }
            });

        },
        checkForWaxPayment: function () {
            this.nanobar.go(25);
            $.ajax({
                url: wc_wax_params.wc_ajax_url,
                type: 'post',
                data: {
                    action: 'woocommerce_check_for_payment',
                    nounce: wc_wax_params.nounce
                }
            }).done(function (res) {
                $('#wax-check').html('<p id="wax-check">Checking..</p>');
                //console.log(res);
                //console.log("Match: " + res.data.match);
                if(res.success === true && res.data.match === true){
                    $( '#place_order' ).attr( 'disabled', false);
                    $( '#place_order' ).trigger( 'click');
                }
                setTimeout(function() {
                    waxPayment.checkForWaxPayment();
                }, 5000);
            });
            this.nanobar.go(100);
        },

        ajaxGetWaxAmount: function () {
            return $.ajax({
                url: wc_wax_params.wc_ajax_url,
                type: 'post',
                data: {
                    action: 'woocommerce_get_wax_amount',
                    nounce: wc_wax_params.nounce
                }
            })
        }
    };

    waxPayment.init();
    setTimeout(function() {
        waxPayment.checkForWaxPayment();
    }, 5000);

})( jQuery );
