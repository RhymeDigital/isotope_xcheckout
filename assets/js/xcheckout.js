/**
 * Isotope XCheckout for Contao Open Source CMS
 *
 * Copyright (C) 2014 HB Agency
 *
 * @package    XCheckout
 * @link       http://www.hbagency.org
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


var IsotopeXCheckout = (function() {
    "use strict";

    var spinner = null;
    var step,
        form,
        loadMessage,
        refreshMessage,
        billingadd,
        shippingadd,
        orderinfo,
        shippingmethod,
        paymentmethod,
        buttons,
        buttonContainer,
        isSending,
        url,
        xhr;

    function initCheckout(config) {

        isSending = false;
        step = config.step;
        form = document.getElementById('iso_mod_checkout_'+step);

        //Find addresses, methods & review
        billingadd 			= document.getElementById(step+'_billingaddress');
        shippingadd 		= document.getElementById(step+'_shippingaddress');
        shippingmethod 		= document.getElementById(step+'_shippingmethod');
        paymentmethod 		= document.getElementById(step+'_paymentmethod');
        orderinfo 			= document.getElementById(step+'_orderinfo');

        //Customize language settings
        loadMessage     = config.loadMessage || 'Loading checkout data...';
        refreshMessage  = config.refreshMessage || 'Refresh Shipping Options';

        // Custom address fields toggler
        if (shippingadd) {
            setToggleAddressFields();
            triggerBillingShippingClick(); // Do defaults
        }

        // Refresh button
        if (shippingmethod){
            makeShippingRefreshButton();
        }

        // Payment forms
        if (paymentmethod) {
            setPaymentForms();
        }

        // Get inputs/buttons
        buttons = document.getElementsByTagName('input');

        if (form) {
            setXHR(form, config, handleResponse);
        }

    }


    function setToggleAddressFields()
    {
        Isotope.toggleAddressFields = function(el, id) {
            var blnRequired = true;
            if (el.value == '0' && el.checked) {
                document.getElementById(id).style.display = 'block';
            } else {
                document.getElementById(id).style.display = 'none';
                blnRequired = false;
            }
            IsotopeXCheckout.enableDisableRequiredFields(document.getElementById(id), blnRequired);
        };
    }

    /*
     * Taken from https://plainjs.com/javascript/ajax/serialize-form-data-into-a-query-string-45/	
     */
    function serialize(form) {
        var field, l, s = [];
        if (typeof form == 'object' && form.nodeName == "FORM") {
            var len = form.elements.length;
            for (var i=0; i<len; i++) {
                field = form.elements[i];
                if (field.name && !field.disabled && field.type != 'file' && field.type != 'reset' && field.type != 'submit' && field.type != 'button') {
                    if (field.type == 'select-multiple') {
                        l = form.elements[i].options.length;
                        for (var j=0; j<l; j++) {
                            if(field.options[j].selected)
                                s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.options[j].value);
                        }
                    } else if ((field.type != 'checkbox' && field.type != 'radio') || field.checked) {
                        s[s.length] = encodeURIComponent(field.name) + "=" + encodeURIComponent(field.value);
                    }
                }
            }
        }
        return s.join('&').replace(/%20/g, '+');
    }

    function enableDisableRequiredFields(parent, blnRequired)
    {
        try {
            var ins = parent.getElementsByTagName('input');
            for (var i = 0; i < ins.length; i++) {
                if((' ' + ins[i].className + ' ').indexOf(' mandatory ') != -1) {
                    ins[i].required = blnRequired;
                }
            }
            var sels = parent.getElementsByTagName('select');
            for (var i = 0; i < sels.length; i++) {
                if((' ' + sels[i].className + ' ').indexOf(' mandatory ') != -1) {
                    sels[i].required = blnRequired;
                }
            }
            var txts = parent.getElementsByTagName('textarea');
            for (var i = 0; i < txts.length; i++) {
                if((' ' + txts[i].className + ' ').indexOf(' mandatory ') != -1) {
                    txts[i].required = blnRequired;
                }
            }
        }
        catch (err) {

        }
    }


    function triggerBillingShippingClick()
    {
        if (document.getElementById('opt_ShippingAddress_0') && document.getElementById('opt_ShippingAddress_1')) {
            if (document.getElementById('opt_ShippingAddress_1').checked) {
                Isotope.toggleAddressFields(document.getElementById('opt_ShippingAddress_1'), 'ShippingAddress_new');
            }
            else {
                Isotope.toggleAddressFields(document.getElementById('opt_ShippingAddress_0'), 'ShippingAddress_new');
            }
        }
    }


    function makeShippingRefreshButton()
    {
        var shiprefreshParent = shippingmethod ? shippingmethod : (billingadd ? billingadd : null);

        if (shiprefreshParent) {
            var shiprefresh = document.createElement('input');
            shiprefresh.type = 'button';
            shiprefresh.value = refreshMessage;
            shiprefresh.setAttribute('id', 'shiprefresh');
            shiprefreshParent.appendChild(shiprefresh);

            if (shiprefresh.attachEvent) {
                shiprefresh.attachEvent('onclick', sendAndRefresh);
            }
            else {
                shiprefresh.addEventListener('click', sendAndRefresh, true);
            }
        }
    }


    function setPaymentForms()
    {
        var wrapper = document.getElementById('ctrl_PaymentMethod');
        if (paymentmethod && wrapper) {

            // Get radio buttons
            var radios = [];
            var ins = wrapper.getElementsByTagName('input');
            for (var i = 0; i < ins.length; i++) {
                if((' ' + ins[i].className + ' ').indexOf(' radio ') != -1) {
                    radios.push(ins[i]);
                }
            }

            // Set events
            var checked;
            for (var j = 0; j < radios.length; j++) {
                radios[j].paymentIndex = j;
                if (radios[j].attachEvent) {
                    radios[j].attachEvent('onclick', togglePaymentForms);
                }
                else {
                    radios[j].addEventListener('click', togglePaymentForms, true);
                }
                // Get default option if any
                if (radios[j].checked) {
                    checked = radios[k];
                }
            }
            // Set default option, otherwise set first
            if (checked) {
                checked.click();
            }
            else if (radios.length) {
                radios[0].click();
            }
        }
    }


    function togglePaymentForms(e)
    {
        // Get paymentForms element
        var paymentFormsWrapper;
        var divs = document.getElementsByTagName('div');
        for (var i = 0; i < divs.length; i++) {
            if((' ' + divs[i].className + ' ').indexOf(' paymentForms ') != -1) {
                paymentFormsWrapper = divs[i];
                break;
            }
        }
        if (!paymentFormsWrapper) return;

        // Get paymentForm elements
        var paymentForms = [];
        var divs = paymentFormsWrapper.getElementsByTagName('div');
        for (var i = 0; i < divs.length; i++) {
            if((' ' + divs[i].className + ' ').indexOf(' paymentForm ') != -1) {
                paymentForms.push(divs[i]);
            }
        }

        // Show/hide
        for (var i = 0; i < paymentForms.length; i++) {
            if (e && e.target && e.target.paymentIndex === i) {
                paymentForms[i].style.display = 'block';
                enableDisableRequiredFields(paymentForms[i], true);
            }
            else {
                paymentForms[i].style.display = 'none';
                enableDisableRequiredFields(paymentForms[i], false);
            }
        }
    }


    function sendAndRefresh()
    {
        if (isSending) return;

        isSending = true;
        xhr.open("POST", url, true);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        form = document.getElementById('iso_mod_checkout_'+step); // "form" was not sending the correct values
        xhr.send(serialize(form));
    }


    function setXHR(form, config, callback) {
        var i, el;
        var spinnerParent = shippingmethod ? shippingmethod : (billingadd ? billingadd : null);

        try {
            xhr = new XMLHttpRequest();
        } catch (e) {
            try {
                xhr = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    xhr = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e){
                    return null;
                }
            }
        }

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 1) {
                //Add a spinner
                if(spinnerParent){
                    try{
                        spinnerParent.removeChild(spinner);
                    }catch(err){}
                    spinner = null;
                    spinner = document.createElement('div');
                    spinner.setAttribute('id', 'spinner');
                    spinnerParent.appendChild(spinner);
                    spinnerParent.className += ' spinning';
                }
            }
            if (xhr.readyState == 4) {
                isSending = false;
                //Check for redirect and simply enable buttons
                try {
                    if(xhr.status == 204 || xhr.status == 1223) { // IE needs 1223 for some stupid reason
                        try{
                            spinnerParent.removeChild(spinner);
                        }catch(err){}
                        spinner = null;
                        spinnerParent.className = spinnerParent.className.replace( /(?:^|\s)spinning(?!\S)/g , '' );
                        return;
                    }

                    if(xhr.responseText) {
                        handleResponse(xhr.responseText);
                    }
                    try{
                        spinnerParent.removeChild(spinner);
                    }catch(err){}
                    spinner = null;
                    spinnerParent.className = spinnerParent.className.replace( /(?:^|\s)spinning(?!\S)/g , '' );

                }catch(err){}
            }
        };

        url = 'ajax/?action=sendxcheckout&page='+config.page+'&id='+config.module+'&step='+config.step;
    }


    function handleResponse(responseText) {

        var json = JSON.parse(responseText);

        //Replace request tokens
        var inputs = document.getElementsByTagName('input'), i;
        if (inputs.length) {
            for (i in inputs) {
                if((' ' + inputs[i].name + ' ').indexOf(' REQUEST_TOKEN ') > -1) {
                    inputs[i].setAttribute('value', json.token);
                }
            }
        }

        // Replace steps
        var steps = json.content.steps, j;

        if (steps.length) {
            for (j in steps) {
                if((' ' + steps[j].id + ' ').indexOf(' '+step+'_billingaddress ') > -1 && billingadd) {
                    billingadd.innerHTML = steps[j].html;
                }
                if((' ' + steps[j].id + ' ').indexOf(' '+step+'_shippingaddress ') > -1 && shippingadd) {
                    shippingadd.innerHTML = steps[j].html;
                    triggerBillingShippingClick(); // Do defaults
                }
                if((' ' + steps[j].id + ' ').indexOf(' '+step+'_shippingmethod ') > -1 && shippingmethod) {
                    shippingmethod.innerHTML = steps[j].html;

                    var ptags = shippingmethod.getElementsByTagName('p');
                    for (var i = 0; i < ptags.length; i++){
                        if (ptags[i].textContent.indexOf('Invalid') > -1) {
                            ptags[i].textContent = 'Please select.';
                        }
                    }
                }
                else if((' ' + steps[j].id + ' ').indexOf(' '+step+'_orderinfo ') > -1 && orderinfo) {
                    orderinfo.innerHTML = steps[j].html;
                }
            }
        }

        // Create refresh shipping button again
        makeShippingRefreshButton();

        // Execute scripts that have to run again
        try {
            if (json.scripts) {
                eval(json.scripts);
            }
        }
        catch (err) {
            // todo: send error messages via Ajax
        }
    }

    function setSelectedIndex(s, v)
    {
        for ( var i = 0; i < s.options.length; i++ ) {
            if (s.options[i].value == v) {
                s.options[i].selected = true;
                return;
            }
        }
    }

    return {
        'attach': function(checkoutConfig) {
            initCheckout(checkoutConfig);
        },

        /**
         * Overwrite the default message
         */
        'setLoadMessage': function(message) {
            loadMessage = message || 'Loading checkout data...';
        },
        'setRefreshMessage': function(message) {
            refreshMessage = message || 'Refresh Shipping Options';
        },

        'enableDisableRequiredFields': function(parent, blnRequired) {
            enableDisableRequiredFields(parent, blnRequired);
        },

        'setSelectedIndex': function(s, v) {
            setSelectedIndex(s, v);
        }
    };
})();
