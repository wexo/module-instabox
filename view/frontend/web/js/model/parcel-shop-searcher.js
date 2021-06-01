define([
    'ko',
    'mage/storage',
    'Magento_Checkout/js/model/quote',
    'jquery'
], function(ko, storage, quote, $) {

    var currentRequest = null;

    return function(wexoShippingData, shippingCountryId) {
        if (currentRequest && currentRequest.abort) {
            currentRequest.abort();
        }
        $('body').trigger('processStart');
        let shippingAddress = quote.shippingAddress();
        let totals =  quote.getTotals()();
        let items = quote.getItems().map( item => {
            return {
                qty: item.qty,
                sku: item.sku,
                weight: item.weight
            }
        })
        console.log(shippingAddress);
        return storage.get('/rest/V1/wexo-instabox/get-parcel-shops?' + $.param({
            email: shippingAddress.email,
            phone: shippingAddress.telephone,
            street: shippingAddress.street !== undefined ? shippingAddress.street[0] || '' : '',
            zip: wexoShippingData.postcode,
            city: shippingAddress.city,
            country_code: shippingCountryId,
            currency_code: totals.quote_currency_code || '',
            items: items,
            grand_total: totals.grand_total,
            cache: true
        })).always(function() {
            currentRequest = null;
            $('body').trigger('processStop');
        });
    };
});
