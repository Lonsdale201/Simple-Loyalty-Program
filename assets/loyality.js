jQuery(document).ready(function ($) {
    function toggleDiscountFields() {
        var discountType = $('input[name="hw_loyalty_discount_type"]:checked').val();
        $('#hw_loyalty_percentage_discount').closest('tr').hide();
        $('#hw_loyalty_fixed_discount').closest('tr').hide();

        if (discountType === 'cart_based_percentage') {
            $('#hw_loyalty_percentage_discount').closest('tr').show();
        } else if (discountType === 'cart_based_fix') {
            $('#hw_loyalty_fixed_discount').closest('tr').show();
        }
    }

    // Kezdeti állapot beállítása és eseménykezelő hozzáadása
    toggleDiscountFields();
    $('input[name="hw_loyalty_discount_type"]').change(toggleDiscountFields);
});
