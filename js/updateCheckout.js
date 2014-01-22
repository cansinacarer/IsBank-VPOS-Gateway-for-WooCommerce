 jQuery(document).ready(function($){
     $(document.body).on('change', 'input[name="payment_method"]', function() {
        $('body').trigger('update_checkout');
    });
     $(document.body).on('change', 'select[name="taksitSecimi"]', function() {
        


        //$('body').trigger('update_checkout');
        //$("form[name='checkout']").submit();
    });
 });